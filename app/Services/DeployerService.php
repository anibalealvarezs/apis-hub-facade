<?php

namespace App\Services;

use App\Models\Alert;
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

        // 1.5 Version Pinning & Patch-Level Updates (Checkout latest patch of assigned release)
        if ($project->apis_hub_release_id && $project->apisHubRelease) {
            $versionTag = escapeshellarg($project->apisHubRelease->version_tag);
            Log::info("Deploying pinned release series {$versionTag} (latest patch) for project {$project->name}");
            
            $commands[] = "cd {$path} && git fetch --tags --force";
            // Extract the major.minor prefix (e.g. 'v1.13') and resolve the highest patch tag.
            // If the repo doesn't contain the tags or resolution fails, fallback to the exact versionTag.
            $commands[] = "cd {$path} && PREFIX=\$(echo {$versionTag} | cut -d. -f1,2) && LATEST_PATCH=\$(git tag -l \"\${PREFIX}.*\" | sort -V | tail -n 1) && if [ -z \"\$LATEST_PATCH\" ]; then LATEST_PATCH={$versionTag}; fi && git checkout \$LATEST_PATCH";
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
        $alertFacadeUrl = config('app.url') . '/api/alerts/triggered';

        $dbName = $project->db_name ?: "apis_hub_{$project->subdomain}";
        $dbUser = $project->db_user ?: "postgres";
        $dbPass = $project->db_password ?: "secret-pass";

        $tokenAuthorityUrl = config('app.url') . '/api/token-authority/refresh';
        $tokenAuthorityEnabled = 'true';

        $billingTier = $project->billingProfile ? $project->billingProfile->tier->value : 'free';
        $apiRateLimit = app(\App\Services\BillingLifecycleService::class)
            ->getApiRateLimitForTier($project->billingProfile ? $project->billingProfile->tier : \App\Enums\UserTier::FREE);

        // Generate deterministic, unique host ports based on project ID and environment offset to prevent Docker conflicts
        $portOffset = env('DEPLOY_PORT_OFFSET', 11100);
        $basePort = $portOffset + ($project->id * 10);
        $externalPort = $basePort;
        $mcpPort = $basePort + 1;
        $dbHostPort = $basePort + 2;
        $redisHostPort = $basePort + 3;

        return <<<EOT
APP_ENV=production
PROJECT_NAME={$project->name}
BILLING_TIER={$billingTier}
DEPLOYMENT_NAME=apis-hub-{$project->subdomain}
SHARED_GATEWAY_NETWORK=apis-hub_default
USE_SWOOLE=true
USE_SSL=false
APP_TIMEZONE={$project->timezone}
CLI_MEMORY_LIMIT=4G
ENABLE_NON_API_ROUTES=false

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
DEPLOY_MCP_SERVER=false
DB_HOST_PORT={$dbHostPort}
REDIS_HOST_PORT={$redisHostPort}

# Security & API
APP_API_KEY={$project->public_api_key}
ADMIN_API_KEY={$project->remote_admin_api_key}
API_RATE_LIMIT_PER_MINUTE={$apiRateLimit}

# Channel Master Credentials (Apps)
FACEBOOK_APP_ID={$fbAppId}
FACEBOOK_APP_SECRET={$fbAppSecret}
FACEBOOK_TOKEN_PATH=./storage/tokens/facebook_tokens.json

GOOGLE_CLIENT_ID={$googleClientId}
GOOGLE_CLIENT_SECRET={$googleClientSecret}
GOOGLE_TOKEN_PATH=./storage/tokens/google_tokens.json

# Token Authority (Facade Integration)
TOKEN_AUTHORITY_ENABLED={$tokenAuthorityEnabled}
TOKEN_AUTHORITY_URL={$tokenAuthorityUrl}
TOKEN_AUTHORITY_BEARER={$project->remote_admin_api_key}

# Aggregation Telemetry
AGGREGATION_TELEMETRY_PATH=storage/logs/aggregation-telemetry.jsonl

# Monitoring Link (Report back to Facade)
MONITOR_FACADE_URL={$facadeUrl}
ALERT_FACADE_URL={$alertFacadeUrl}
MONITOR_TOKEN={$project->monitoring_token}
MONITOR_ENABLED=true
EOT;
    }

    /**
     * Synchronize alert configurations (alerts.json) to remote apis-hub node.
     */
    public function syncAlertConfig(Project $project): bool
    {
        if (app()->environment('testing')) {
            return true;
        }

        if (!$project->supportsAlerts()) {
            Log::info("Skipping alerts.json sync for project {$project->name}: release does not support alerts (< v1.15.0)");
            return false;
        }

        $server = $project->server;
        if (!$server) {
            return false;
        }

        $alerts = $project->alerts()
            ->with('calculationLines')
            ->get()
            ->map(fn ($alert) => [
                'id' => $alert->id,
                'name' => $alert->name,
                'is_active' => (bool) $alert->is_active,
                'source_type' => $alert->source_type,
                'source_config' => $alert->source_config,
                'ast' => $alert->ast,
                'filters' => $alert->filters,
                'aggregation_method' => $alert->aggregation_method,
                'upper_limit' => $alert->upper_limit !== null ? (float) $alert->upper_limit : null,
                'lower_limit' => $alert->lower_limit !== null ? (float) $alert->lower_limit : null,
                'schedule_type' => $alert->schedule_type,
                'schedule_config' => $alert->schedule_config,
                'next_evaluation_at' => $alert->next_evaluation_at?->toIso8601String(),
                'notify_ui' => $alert->notify_ui,
                'notify_email' => $alert->notify_email,
                'calculation_lines' => $alert->calculationLines->map(fn ($line) => [
                    'id' => $line->id,
                    'label' => $line->label,
                    'asset_filter' => $line->asset_filter,
                ])->toArray(),
            ])
            ->toArray();

        $jsonPayload = json_encode($alerts, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $path = "/var/www/apis-hub/tenants/{$project->subdomain}/config/alerts.json";

        $b64 = base64_encode($jsonPayload);
        $command = "mkdir -p /var/www/apis-hub/tenants/{$project->subdomain}/config && echo '{$b64}' | base64 -d > {$path} && chmod 664 {$path}";

        try {
            $this->runSshCommands($server, [$command]);
            Log::info("Successfully synchronized alerts.json for project {$project->name} (" . count($alerts) . " alerts)");
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to synchronize alerts.json for project {$project->name}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Manually trigger alert calculation on remote tenant worker over SSH.
     */
    public function evaluateAlert(Alert $alert): array
    {
        $project = $alert->project;
        if (!$project || !$project->server) {
            return ['success' => false, 'message' => 'Project or server configuration missing.'];
        }

        $this->syncAlertConfig($project);

        $subdomain = $project->subdomain;
        $tenantPath = "/var/www/apis-hub/tenants/{$subdomain}";
        $alertWebhookUrl = escapeshellarg(config('app.url') . '/api/alerts/triggered');
        $command = "cd {$tenantPath} && (docker compose exec -T -e ALERT_FACADE_URL={$alertWebhookUrl} master php bin/cli.php app:evaluate-alerts --config=/app/config/alerts.json --force --alert-id={$alert->id} 2>&1 || docker compose exec -T -e ALERT_FACADE_URL={$alertWebhookUrl} master php bin/cli.php app:evaluate-alerts --config=/app/config/alerts.json --force 2>&1 || docker compose exec -T master php bin/cli.php app:evaluate-alerts --force 2>&1)";

        try {
            $output = $this->runSshCommands($project->server, [$command]);
            $textOutput = is_array($output) ? implode("\n", $output) : (string) $output;
            return ['success' => true, 'output' => $textOutput];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Execute a single command over SSH on the remote server using the provided identity.
     */
    public function executeCommand(Project $project, Server $server, string $command)
    {
        return $this->runSshCommands($server, [$command]);
    }

    /**
     * Run commands over SSH on the remote server using the provided identity.
     */
    public function runSshCommands(Server $server, array $commands, ?int $timeout = null)
    {
        $allCommands = implode(" && ", $commands);
        $timeout = $timeout ?? 600;

        // 1. Escribir la clave SSH privada en un archivo temporal seguro
        $tmpKeyPath = tempnam(sys_get_temp_dir(), 'ssh_key_');
        file_put_contents($tmpKeyPath, $server->ssh_private_key . "\n");
        chmod($tmpKeyPath, 0600); // Requisito estricto de SSH para llaves privadas

        try {
            // 2. Ejecutar con la identity explicitamente - Aumentamos timeout a 600s (10 min)
            $sshCmd = "ssh -i {$tmpKeyPath} -o StrictHostKeyChecking=no {$server->ssh_user}@{$server->ip_address} \"{$allCommands}\"";
            $result = Process::timeout($timeout)->run($sshCmd);

            if ($result->failed()) {
                $combinedOutput = "STDOUT:\n" . $result->output() . "\n\nSTDERR:\n" . $result->errorOutput();
                Log::error("Deployment failed on {$server->ip_address}:\n" . $combinedOutput);

                return ['status' => 'error', 'output' => trim($combinedOutput)];
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
     * Trigger a lightweight synchronization over SSH.
     */
    public function startSync(Project $project)
    {
        $path = "/var/www/apis-hub/tenants/{$project->subdomain}";
        $commands = [
            "cd {$path}",
            "bash bin/start-sync.sh",
        ];

        return $this->runSshCommands($project->server, $commands);
    }

    /**
     * Trigger a nuclear historical resync over SSH.
     * 
     * @param Project $project
     * @param string[] $channels
     */
    public function nuclearResync(Project $project, array $channels)
    {
        $path = "/var/www/apis-hub/tenants/{$project->subdomain}";
        
        $channelArg = '--channel=' . implode(',', $channels);
        $commands = [
            "cd {$path}",
            "bash bin/nuclear-sync.sh {$channelArg}",
        ];

        return $this->runSshCommands($project->server, $commands);
    }

    /**
     * Trigger a nuclear historical resync for a single asset over SSH.
     * 
     * @param Project $project
     * @param string $channel
     * @param string $assetId
     */
    public function nuclearResyncAsset(Project $project, string $channel, string $assetId)
    {
        $path = "/var/www/apis-hub/tenants/{$project->subdomain}";
        
        $channelArg = '--channel=' . $channel;
        $assetArg = '--asset=' . escapeshellarg($assetId);
        $commands = [
            "cd {$path}",
            "bash bin/nuclear-sync.sh {$channelArg} {$assetArg}",
        ];

        return $this->runSshCommands($project->server, $commands);
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
     * Upgrade a project to a new APIs Hub release.
     *
     * Steps:
     * 1. git fetch && git checkout new version tag
     * 2. Execute per-version upgrade commands
     * 3. Rebuild Docker images and restart services (docker compose up --build)
     *
     * Note: Containers are replaced immediately (no graceful drain). APIs Hub handles
     * job re-scheduling on restart via its own resilient queue logic.
     */
    public function upgradeRelease(Project $project, \App\Models\ApisHubRelease $targetRelease): array
    {
        $path = "/var/www/apis-hub/tenants/{$project->subdomain}";
        $targetTag = escapeshellarg($targetRelease->version_tag);
        
        // Clean version tags to bare semantic numbers for the API
        $currentVersionRaw = $project->apisHubRelease ? ltrim($project->apisHubRelease->version_tag, 'v') : '1.13.0';
        $currentVersionArg = escapeshellarg($currentVersionRaw);
        
        $oldTag = escapeshellarg($project->apisHubRelease ? $project->apisHubRelease->version_tag : $targetRelease->version_tag);

        Log::info("Upgrading project {$project->name} from {$currentVersionRaw} to release {$targetRelease->version_tag}");

        $commands = [
            "cd {$path}",
            // 1. Fetch and checkout new version
            "git fetch --tags --force",
            "git reset --hard",
            "git checkout {$targetTag}",
            
            // 2. Kill current active workers instantly
            "docker compose stop",
            
            // 3. Build the new images based on the target version
            "docker compose build",
            
            // 3.5. Ensure the host's vendor directory matches the new composer.lock before mounting it in the isolated container
            "MSYS_NO_PATHCONV=1 docker run --rm -v {$path}:/app -w /app composer:latest install --no-dev --no-scripts --no-interaction --prefer-dist --optimize-autoloader --ignore-platform-reqs",
            
            // 4. Execute Migration Sequencer in an isolated container (bypassing entrypoint.sh so crons/workers don't start)
            "if ! docker compose run --rm --entrypoint \"php\" master bin/cli.php app:upgrade-version --current-version={$currentVersionArg}; then echo 'CRITICAL: Upgrade failed! Initiating Git rollback to {$oldTag}...'; git checkout {$oldTag}; bash bin/full-deploy.sh; exit 1; fi",
            
            // 5. If successful, use the robust full-deploy.sh to properly clean, boot, and register everything
            "bash bin/full-deploy.sh"
        ];

        return $this->runSshCommands($project->server, $commands, timeout: 900);
    }

    /**
     * Inject social tokens directly into the remote node via API (Hot-reload).
     */
    public function injectSocialTokens(Project $project, string $token, string $provider = 'facebook', array $payload = []): array
    {
        try {
            return $this->remoteEngineService->execute($project, function ($client) use ($provider, $token, $payload) {
                return $client->importCredentials($provider, $token, $payload);
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
