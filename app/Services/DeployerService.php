<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Server;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class DeployerService
{
    /**
     * Deploy a new APIs Hub instance for a project on a specific server.
     */
    public function deploy(Project $project)
    {
        // 0. Ensure unique secure tokens for this instance
        if (!$project->remote_admin_api_key) {
            $project->remote_admin_api_key = bin2hex(random_bytes(32));
        }
        
        if (!$project->monitoring_token) {
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
            "cd {$path} && git checkout {$project->git_branch} && git pull origin {$project->git_branch}",
        ];

        // 2. Build .env from Facade Data
        $envContent = $this->generateEnvContent($project);
        $commands[] = "echo '{$envContent}' > {$path}/.env";

        // 3. Fire deployment (full-deploy.sh)
        $commands[] = "cd {$path} && sh bin/full-deploy.sh";

        // 4. Register in Caddy (Reverse Proxy for the specific container)
        $caddyVhostDir = "/var/www/apis-hub/caddy_vhosts";
        $caddyVhostPath = "{$caddyVhostDir}/{$project->subdomain}.caddy";
        $caddyHost = "{$project->subdomain}.apis-hub.cloud";
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

        $dbName = $project->db_name ?: "apis_hub_{$project->subdomain}";
        $dbUser = $project->db_user ?: "postgres";
        $dbPass = $project->db_password ?: "secret-pass";

        return <<<EOT
APP_ENV=production
PROJECT_NAME={$project->name}
DEPLOYMENT_NAME=apis-hub-{$project->subdomain}

# Database (Instance specific)
DB_DRIVER=pdo_pgsql
DB_HOST=db
DB_PORT=5432
DB_NAME={$dbName}
DB_USER={$dbUser}
DB_PASSWORD={$dbPass}

# Security & API
APP_API_KEY={$project->public_api_key}
ADMIN_API_KEY={$project->remote_admin_api_key}

# Channel Credentials (Master Apps + User Tokens)
FACEBOOK_APP_ID={$fbAppId}
FACEBOOK_APP_SECRET={$fbAppSecret}
FACEBOOK_USER_TOKEN={$project->facebook_user_token}

GOOGLE_CLIENT_ID={$googleClientId}
GOOGLE_CLIENT_SECRET={$googleClientSecret}
GOOGLE_REFRESH_TOKEN={$project->google_refresh_token}

# Monitoring Link (Report back to Facade)
MONITOR_FACADE_URL=https://facade.anibalalvarez.com
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
            "docker container prune -f",
        ];

        return $this->runSshCommands($server, $commands);
    }

    /**
     * Update environment variables on the remote server and restart containers.
     */
    public function updateCredentials(Project $project, array $credentials): array
    {
        $path = "/var/www/apis-hub/tenants/{$project->subdomain}";
        $commands = ["cd {$path}"];

        foreach ($credentials as $key => $value) {
            // Usamos sed para buscar la línea de la variable y reemplazarla, o añadirla si no existe
            // Usamos '|' como delimitador para permitir URLs con barras
            $commands[] = "grep -q '^{$key}=' .env && sed -i 's|^{$key}=.*|{$key}={$value}|' .env || echo '{$key}={$value}' >> .env";
        }

        // Reiniciamos el contenedor master para que cargue el nuevo .env
        // Regeneramos el manifiesto con la configuración dinámica
        $commands[] = "docker run --rm -v {$path}:/app -e \"ENV_FILE=.env\" --env-file .env -w /app php:8.3-cli php bin/build-deployment.php";
        // Levantamos todos los contenedores necesarios (incluyendo db si faltaba)
        $commands[] = "docker compose up -d --remove-orphans";

        return $this->runSshCommands($project->server, $commands);
    }
}
