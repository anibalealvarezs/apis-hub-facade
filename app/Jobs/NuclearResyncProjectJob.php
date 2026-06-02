<?php

namespace App\Jobs;

use App\Models\Project;
use App\Models\ProjectDeploymentLog;
use App\Services\DeployerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class NuclearResyncProjectJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600; // 10 minutes maximum

    public function __construct(
        public Project $project,
        public string $channel = 'all'
    ) {}

    public function handle(DeployerService $deployer): void
    {
        // Mark the project as resyncing immediately so the UI can reflect this state
        $this->project->update([
            'health_status'    => 'syncing',
            'deploy_started_at' => now(),
        ]);

        // 1. Create a Deployment Log
        $deploymentLog = ProjectDeploymentLog::create([
            'project_id' => $this->project->id,
            'status'     => 'running',
            'started_at' => now(),
            'output'     => "Starting nuclear historical resync for channel: {$this->channel}...",
        ]);

        try {
            // 2. Run Nuclear Resync
            $result = $deployer->nuclearResync($this->project, $this->channel);

            // 3. Update Log with Result
            $deploymentLog->update([
                'status'       => $result['status'] === 'success' ? 'success' : 'failed',
                'output'       => $deploymentLog->output . "\n\n=== NUCLEAR RESYNC OUTPUT ===\n" . $result['output'],
                'completed_at' => now(),
            ]);

            // 4. Update project active status
            if ($result['status'] === 'success') {
                $this->project->update([
                    'health_status'     => 'online',
                    'deploy_started_at' => null, // clear the in-progress marker
                ]);
            } else {
                $this->project->update([
                    'health_status'     => 'error',
                    'deploy_started_at' => null,
                ]);
                Log::error("Nuclear Resync failed for project {$this->project->id}");
            }

        } catch (\Throwable $e) {
            // Update log on Exception
            $deploymentLog->update([
                'status'       => 'failed',
                'output'       => $deploymentLog->output . "\n\n=== EXCEPTION ===\n" . $e->getMessage() . "\n" . $e->getTraceAsString(),
                'completed_at' => now(),
            ]);
            $this->project->update([
                'health_status'     => 'error',
                'deploy_started_at' => null,
            ]);
            Log::error("Nuclear Resync exception for project {$this->project->id}", ['exception' => $e]);
        }
    }
}
