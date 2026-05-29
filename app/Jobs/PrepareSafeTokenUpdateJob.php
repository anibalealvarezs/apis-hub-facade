<?php

namespace App\Jobs;

use App\Models\Project;
use App\Services\DeployerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PrepareSafeTokenUpdateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Project $project;
    public string $provider;
    public ?int $initiatorId;
    public ?string $types;

    public function __construct(Project $project, string $provider, ?int $initiatorId = null, ?string $types = null)
    {
        $this->project = $project;
        $this->provider = $provider;
        $this->initiatorId = $initiatorId;
        $this->types = $types;
    }

    public function handle(\App\Services\DeployerService $deployerService)
    {
        \Illuminate\Support\Facades\Log::info("Preparing safe token update for project {$this->project->id} ({$this->provider})");

        // Set status in project to lock the UI if necessary
        $this->project->update(['health_status' => 'stopping_workers']);

        $path = "/var/www/apis-hub/tenants/{$this->project->subdomain}";
        $deploymentName = "apis-hub-{$this->project->subdomain}";
        
        // Command to stop worker gracefully. 
        $cmd = "cd {$path} && nohup docker compose -p {$deploymentName} stop -t 7200 worker > /dev/null 2>&1 < /dev/null &";

        try {
            $tmpKeyPath = tempnam(sys_get_temp_dir(), 'ssh_key_');
            file_put_contents($tmpKeyPath, $this->project->server->ssh_private_key . "\n");
            chmod($tmpKeyPath, 0600);
            
            $sshCmd = "ssh -i {$tmpKeyPath} -o StrictHostKeyChecking=no {$this->project->server->ssh_user}@{$this->project->server->ip_address} \"{$cmd}\"";
            
            // Use start() so we DO NOT block the PHP worker, completely avoiding SSH hangs
            \Illuminate\Support\Facades\Process::start($sshCmd);
            
            // Give it a tiny sleep to ensure SSH initiated before we delete the key
            sleep(2);
            if (file_exists($tmpKeyPath)) {
                unlink($tmpKeyPath);
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Failed to start graceful shutdown: " . $e->getMessage());
        }

        // Dispatch the polling job to wait until workers are actually stopped
        \App\Jobs\PollWorkersStatusJob::dispatch($this->project, $this->provider, $this->initiatorId, $this->types)
            ->delay(now()->addSeconds(10));
    }
}
