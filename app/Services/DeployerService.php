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
        // This script in apis-hub will generate its own docker-compose.yml
        $commands[] = "cd {$path} && sh bin/full-deploy.sh";

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
DB_NAME={$dbName}
DB_USER={$dbUser}
DB_PASSWORD={$dbPass}

# Security & API
APP_API_KEY={$project->remote_app_api_key}
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
     * Run commands over SSH on the remote server.
     */
    protected function runSshCommands(Server $server, array $commands)
    {
        $allCommands = implode(" && ", $commands);
        
        // Using Laravel's process with SSH. 
        // Note: The server must have the Facade's public key in authorized_keys.
        $result = Process::run("ssh -o StrictHostKeyChecking=no {$server->ssh_user}@{$server->ip_address} \"{$allCommands}\"");

        if ($result->failed()) {
            Log::error("Deployment failed: " . $result->errorOutput());
            return ['status' => 'error', 'output' => $result->errorOutput()];
        }

        return ['status' => 'success', 'output' => $result->output()];
    }
}
