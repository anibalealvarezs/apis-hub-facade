<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Server;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class DeployerService
{
    protected RemoteEngineService $remoteEngineService;

    public function __construct(RemoteEngineService $remoteEngineService)
    {
        $this->remoteEngineService = $remoteEngineService;
    }

    /**
     * Deploy a new APIs Hub instance for a project on a specific server.
     */
    public function deploy(Project $project)
    {
        // 0. Ensure unique secure tokens for this instance
        if (! $project->remote_admin_api_key) {
            $project->remote_admin_api_key = bin2hex(random_bytes(32));
        }

        if (! $project->monitoring_token) {
            $project->monitoring_token = (string) \Illuminate\Support\Str::uuid();
        }

        if ($project->isDirty()) {
            $project->save();
        }

        $server = $project->server;
        $path = "/var/www/apis-hub/tenants/{$project->subdomain}";

        Log::info("Starting deployment for {$project->name} on {$server->ip_address}");

        // 1. Prepare Directory & Clone
        $commands = [
            "mkdir -p {$path}",
            "cd {$path} && if [ ! -d .git ]; then git clone {$project->git_repo} .; fi",
        ];

        // 1.5 Version Pinning (Checkout specific Release if assigned)
        if ($project->apis_hub_release_id && $project->apisHubRelease) {
            $versionTag = escapeshellarg($project->apisHubRelease->version_tag);
            Log::info("Deploying pinned release {$versionTag} for project {$project->name}");
            $commands[] = "cd {$path} && git fetch --tags && git checkout {$versionTag}";
        } else {
            $branch = escapeshellarg($project->git_branch);
            Log::info("Deploying branch {$branch} for project {$project->name}");
            $commands[] = "cd {$path} && git checkout {$branch} && git pull origin {$branch}";
        }

        // 2. Build .env from Facade Data
        $envContent = $this->generateEnvContent($project);
        $commands[] = "echo '{$envContent}' > {$path}/.env";

        // 3. Fire deployment (full-deploy.sh)
        // Clean up any existing containers or orphans from failed previous deployments to avoid naming conflicts
        $commands[] = "docker ps -aq --filter name=apis-hub-{$project->subdomain}- | xargs -r docker rm -f || true";
        $commands[] = "cd {$path} && sh bin/full-deploy.sh";

        // 4. Register in Caddy (Reverse Proxy for the specific container)
        $caddyVhostDir = "/var/www/apis-hub/caddy_vhosts";
        $caddyVhostPath = "{$caddyVhostDir}/{$project->subdomain}.caddy";
        $baseDomain = config('app.network_domain');
        $caddyHost = "{$project->subdomain}.{$baseDomain}";
        $containerName = "apis-hub-{$project->subdomain}-master"; // Con sufijo -master definido en docker-compose

        $caddyConfig = "{$caddyHost} {
    reverse_proxy {$containerName}:8080
}";
        $commands[] = "mkdir -p {$caddyVhostDir} && echo '{$caddyConfig}' > {$caddyVhostPath} && cd /root/n8n-docker-caddy && docker compose exec -T caddy caddy reload --config /etc/caddy/Caddyfile";

        return $this->runSshCommands($server, $commands);
    }

    /**
     * Generate the .env content dynamically based on Project credentials and config.
     */
    protected function generateEnvContent(Project $project): string
    {
        $fbAppId = config('services.facebook.client_id');
        $fbAppSecret = config('services.facebook.client_secret');
        $googleClientId = config('services.google.client_id');
        $googleClientSecret = config('services.google.client_secret');
        $facadeUrl = config('app.url') . '/api/heartbeat';

        $dbName = $project->db_name ?: "apis_hub_{$project->subdomain}";
        $dbUser = $project->db_user ?: "postgres";
        $dbPass = $project->db_password ?: "secret-pass";

        // Generate deterministic, unique host ports based on project ID to prevent Docker conflicts
        $basePort = 11000 + ($project->id * 10);
        $externalPort = $basePort;
        $mcpPort = $basePort + 1;
        $dbHostPort = $basePort + 2;
        $redisHostPort = $basePort + 3;

        return <<<EOT
APP_ENV=production
PROJECT_NAME={$project->name}
DEPLOYMENT_NAME=apis-hub-{$project->subdomain}
SHARED_GATEWAY_NETWORK=apis-hub_default
USE_SWOOLE=true
USE_SSL=false
APP_TIMEZONE={$project->timezone}
CLI_MEMORY_LIMIT=4G

# Database (Instance specific)
DB_DRIVER=pdo_pgsql
DB_HOST=db
DB_PORT=5432
DB_NAME={$dbName}
DB_USER={$dbUser}
DB_PASSWORD={$dbPass}

# Redis
REDIS_HOST=redis
REDIS_PORT=6379

# Host Ports Mapping (Dynamically generated to prevent collisions)
STARTING_HOST_PORT={$externalPort}
EXTERNAL_PORT={$externalPort}
MCP_PORT={$mcpPort}
DB_HOST_PORT={$dbHostPort}
REDIS_HOST_PORT={$redisHostPort}

# Security & API
APP_API_KEY={$project->public_api_key}
ADMIN_API_KEY={$project->remote_admin_api_key}

# Channel Master Credentials (Apps)
FACEBOOK_APP_ID={$fbAppId}
FACEBOOK_APP_SECRET={$fbAppSecret}
FACEBOOK_TOKEN_PATH=./storage/tokens/facebook_tokens.json

GOOGLE_CLIENT_ID={$googleClientId}
GOOGLE_CLIENT_SECRET={$googleClientSecret}
GOOGLE_TOKEN_PATH=./storage/tokens/google_tokens.json

# Aggregation Telemetry
AGGREGATION_TELEMETRY_PATH=storage/logs/aggregation-telemetry.jsonl

# Monitoring Link (Report back to Facade)
MONITOR_FACADE_URL={$facadeUrl}
MONITOR_TOKEN={$project->monitoring_token}
MONITOR_ENABLED=true
EOT;
    }

    /**
     * Run commands over SSH on the remote server using the provided identity.
     */
    protected function runSshCommands(Server $server, array $commands)
    {
        $allCommands = implode(" && ", $commands);

        // 1. Escribir la clave SSH privada en un archivo temporal seguro
        $tmpKeyPath = tempnam(sys_get_temp_dir(), 'ssh_key_');
        file_put_contents($tmpKeyPath, $server->ssh_private_key . "\n");
        chmod($tmpKeyPath, 0600); // Requisito estricto de SSH para llaves privadas

        try {
            // 2. Ejecutar con la identity explicitamente - Aumentamos timeout a 600s (10 min)
            $sshCmd = "ssh -i {$tmpKeyPath} -o StrictHostKeyChecking=no {$server->ssh_user}@{$server->ip_address} \"{$allCommands}\"";
            $result = Process::timeout(600)->run($sshCmd);

            if ($result->failed()) {
                Log::error("Deployment failed: " . $result->errorOutput());

                return ['status' => 'error', 'output' => $result->errorOutput()];
            }

            return ['status' => 'success', 'output' => $result->output()];
        } finally {
            // 3. Limpiar la llave temporal siempre
            if (file_exists($tmpKeyPath)) {
                unlink($tmpKeyPath);
            }
        }
    }

    /**
     * Start the containers for a project (resume).
     */
    public function startContainers(Project $project)
    {
        $path = "/var/www/apis-hub/tenants/{$project->subdomain}";
        $commands = ["cd {$path} && docker compose up -d"];

        return $this->runSshCommands($project->server, $commands);
    }

    /**
     * Stop the containers for a project (pause).
     */
    public function stopContainers(Project $project)
    {
        $path = "/var/www/apis-hub/tenants/{$project->subdomain}";
        $commands = ["cd {$path} && docker compose stop"];

        return $this->runSshCommands($project->server, $commands);
    }

    /**
     * Temporarily disable Caddy routing for a project (Soft Delete).
     */
    public function suspendDomain(Project $project)
    {
        $server = $project->server;
        $caddyVhostPath = "/var/www/apis-hub/caddy_vhosts/{$project->subdomain}.caddy";
        $caddySuspendedPath = "/var/www/apis-hub/caddy_vhosts/{$project->subdomain}.suspended";

        $commands = [
            "if [ -f {$caddyVhostPath} ]; then mv {$caddyVhostPath} {$caddySuspendedPath}; fi",
            "cd /root/n8n-docker-caddy && docker compose exec -T caddy caddy reload --config /etc/caddy/Caddyfile",
        ];

        return $this->runSshCommands($server, $commands);
    }

    /**
     * Restore Caddy routing for a project (Restore Soft Delete).
     */
    public function restoreDomain(Project $project)
    {
        $server = $project->server;
        $caddyVhostPath = "/var/www/apis-hub/caddy_vhosts/{$project->subdomain}.caddy";
        $caddySuspendedPath = "/var/www/apis-hub/caddy_vhosts/{$project->subdomain}.suspended";

        $commands = [
            "if [ -f {$caddySuspendedPath} ]; then mv {$caddySuspendedPath} {$caddyVhostPath}; fi",
            "cd /root/n8n-docker-caddy && docker compose exec -T caddy caddy reload --config /etc/caddy/Caddyfile",
        ];

        return $this->runSshCommands($server, $commands);
    }

    /**
     * Fully remove a project's infrastructure from the server.
     */
    public function removeInstance(Project $project)
    {
        $server = $project->server;
        $path = "/var/www/apis-hub/tenants/{$project->subdomain}";
        $caddyVhostPath = "/var/www/apis-hub/caddy_vhosts/{$project->subdomain}.caddy";

        Log::info("Starting infrastructure removal for project '{$project->name}' (subdomain: {$project->subdomain}) on server {$server->ip_address}");

        $commands = [
            "if [ -d {$path} ]; then cd {$path} && docker compose down -v; fi",
            "rm -rf {$path}",
            "rm -f {$caddyVhostPath}",
            "cd /root/n8n-docker-caddy && docker compose exec -T caddy caddy reload --config /etc/caddy/Caddyfile",
        ];

        return $this->runSshCommands($server, $commands);
    }

    /**
     * Inject social tokens directly into the remote node via API (Hot-reload).
     */
    public function injectSocialTokens(Project $project, array $tokens, string $provider = 'facebook'): array
    {
        try {
            return $this->remoteEngineService->execute($project, function ($client) use ($provider, $tokens) {
                return $client->importCredentials($provider, $tokens);
            });
        } catch (\Exception $e) {
            Log::error("Error injecting tokens for {$provider}: " . $e->getMessage());

            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Update fixed environment variables on the remote server and restart containers.
     * Only for Infrastructure keys (Client IDs, Secrets, API Keys).
     */
    public function updateCredentials(Project $project, array $credentials): array
    {
        $path = "/var/www/apis-hub/tenants/{$project->subdomain}";
        $commands = ["cd {$path}"];

        foreach ($credentials as $key => $value) {
            // Método ultra-robusto: Filtramos la variable si existe y la añadimos de nuevo.
            // Esto evita problemas de delimitadores (sed) con URLs y caracteres especiales.
            $commands[] = "(grep -v '^{$key}=' .env > .env.tmp 2>/dev/null || true) && mv .env.tmp .env && echo '{$key}={$value}' >> .env";
        }

        // Reiniciamos el contenedor master para que cargue el nuevo .env
        // Regeneramos el manifiesto con la configuración dinámica
        $commands[] = "docker run --rm -v {$path}:/app -e \"ENV_FILE=.env\" --env-file .env -w /app php:8.3-cli php bin/build-deployment.php";
        // Levantamos todos los contenedores necesarios (incluyendo db si faltaba)
        $commands[] = "docker compose up -d --remove-orphans";

        return $this->runSshCommands($project->server, $commands);
    }
}
