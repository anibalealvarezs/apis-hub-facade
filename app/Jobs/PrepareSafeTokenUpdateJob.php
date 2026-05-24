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

    public function __construct(Project $project, string $provider)
    {
        $this->project = $project;
        $this->provider = $provider;
    }

    public function handle(DeployerService $deployerService)
    {
        Log::info("Initiating Safe Token Update for project {$this->project->id} ({$this->provider})");

        // Set status in project to lock the UI if necessary
        $this->project->update(['health_status' => 'stopping_workers']);

        $deploymentName = 'apis-hub'; // Defaults

        // Run nohup docker compose stop worker in background
        // The SSH connection will return immediately, but the server will wait for the graceful shutdown
        $cmd = "nohup docker compose -p {$deploymentName} stop -t 7200 worker > /dev/null 2>&1 < /dev/null &";

        try {
            $deployerService->executeCommand($this->project, clone $this->project->server, $cmd);
            Log::info("Sent background graceful stop command to project {$this->project->id}");

            // Dispatch the polling job to wait until workers are actually stopped
            PollWorkersStatusJob::dispatch($this->project, $this->provider)->delay(now()->addMinutes(1));
        } catch (\Exception $e) {
            Log::error("Failed to execute stop command for project {$this->project->id}: " . $e->getMessage());
            $this->project->update(['health_status' => 'healthy']); // Revert status
        }
    }
}
