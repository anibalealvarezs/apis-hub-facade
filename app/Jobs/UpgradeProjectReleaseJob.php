<?php

namespace App\Jobs;

use App\Models\ApisHubRelease;
use App\Models\Project;
use App\Models\ProjectDeploymentLog;
use App\Services\DeployerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UpgradeProjectReleaseJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;

    public function __construct(
        protected Project $project,
        protected ApisHubRelease $targetRelease,
    ) {
    }

    public function handle(DeployerService $deployer): void
    {
        $this->project->update(['health_status' => 'upgrading']);

        $deploymentLog = ProjectDeploymentLog::create([
            'project_id' => $this->project->id,
            'status' => 'running',
            'started_at' => now(),
            'output' => "Starting upgrade to release {$this->targetRelease->version_tag}...",
        ]);

        try {
            $result = $deployer->upgradeRelease($this->project, $this->targetRelease);

            $deploymentLog->update([
                'status' => $result['status'] === 'success' ? 'success' : 'failed',
                'output' => $deploymentLog->output . "\n\n=== UPGRADE OUTPUT ===\n" . $result['output'],
                'completed_at' => now(),
            ]);

            if ($result['status'] !== 'success') {
                $this->project->update(['health_status' => 'error']);

                Log::error("Upgrade failed for project {$this->project->name}: {$result['output']}");

                return;
            }

            $this->project->update([
                'apis_hub_release_id' => $this->targetRelease->id,
                'health_status' => 'online',
            ]);

            \App\Models\ProjectStatusLog::create([
                'project_id' => $this->project->id,
                'is_active' => true,
                'event_type' => 'upgrade',
                'created_by_id' => null,
                'notes' => "Upgraded to release {$this->targetRelease->version_tag}",
            ]);

            Log::info("Project {$this->project->name} upgraded to {$this->targetRelease->version_tag}");
        } catch (\Throwable $e) {
            $deploymentLog->update([
                'status' => 'failed',
                'output' => $deploymentLog->output . "\n\n=== EXCEPTION ===\n" . $e->getMessage() . "\n" . $e->getTraceAsString(),
                'completed_at' => now(),
            ]);

            $this->project->update(['health_status' => 'error']);

            Log::error("Upgrade exception for project {$this->project->id}", ['exception' => $e]);
        }
    }
}
