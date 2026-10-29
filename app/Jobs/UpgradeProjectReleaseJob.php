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
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UpgradeProjectReleaseJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public const TIMEOUT_SECONDS = 5400;
    public const RESERVATION_SECONDS = 5700;
    // Covers pre-flight/env transfer + the 3600s upgrade + recovery/cleanup.
    public int $timeout = self::TIMEOUT_SECONDS;

    public function __construct(
        protected Project $project,
        protected ApisHubRelease $targetRelease,
    ) {
    }

    /**
     * Get the middleware the job should pass through.
     * Prevents concurrent release upgrades on the same physical server to avoid Docker daemon contention and Caddy reload races.
     *
     * @return array
     */
    public function middleware(): array
    {
        $serverId = $this->project->server_id ?? 'default';

        return [
            (new WithoutOverlapping("server-deploy:{$serverId}"))
                ->releaseAfter(self::TIMEOUT_SECONDS)
                ->expireAfter(self::RESERVATION_SECONDS),
        ];
    }

    public function handle(DeployerService $deployer): void
    {
        $this->project->update(['health_status' => 'upgrading']);

        $deploymentLog = ProjectDeploymentLog::create([
            'project_id' => $this->project->id,
            'status' => 'running',
            'started_at' => now(),
            'progress_stage' => 'preparing',
            'progress_updated_at' => now(),
            'output' => "Starting upgrade to release {$this->targetRelease->version_tag}...",
        ]);

        try {
            $lastWrite = 0.0;
            $stage = 'preparing';
            $result = $deployer->upgradeRelease($this->project, $this->targetRelease, function (string $next) use ($deploymentLog, &$lastWrite, &$stage) {
                if ($next === $stage && microtime(true) - $lastWrite < 5) return;
                $lastWrite = microtime(true);
                $stage = $next;
                try {
                    $deploymentLog->update(['progress_stage' => $next, 'progress_updated_at' => now()]);
                } catch (\Throwable $error) {
                    Log::warning('Upgrade progress could not be saved', ['project_id' => $this->project->id, 'exception_class' => get_class($error)]);
                }
            });

            $deploymentLog->update([
                'status' => match ($result['status'] ?? 'error') {
                    'success' => 'success',
                    'rolled_back' => 'rolled_back',
                    default => 'failed',
                },
                'output' => $deploymentLog->output . "\n\n=== UPGRADE OUTPUT ===\n" . $result['output'],
                'completed_at' => now(),
            ]);

            // The deployer restored the previous release, so the tenant is serving again
            // and the pinned version is still the old one. Record it as its own outcome
            // instead of an error so the UI can tell "broken" from "never upgraded".
            if (($result['status'] ?? null) === 'rolled_back') {
                $this->project->update(['health_status' => 'online']);

                $restoredTag = $this->project->apisHubRelease?->version_tag ?? 'the previous release';

                \App\Models\ProjectStatusLog::create([
                    'project_id' => $this->project->id,
                    'is_active' => true,
                    'event_type' => 'upgrade',
                    'created_by_id' => null,
                    'notes' => "Upgrade to {$this->targetRelease->version_tag} was rolled back automatically; project remains on {$restoredTag}.",
                ]);

                Log::warning("Upgrade of {$this->project->name} to {$this->targetRelease->version_tag} was rolled back automatically and the project remains on {$restoredTag}.");

                return;
            }

            if ($result['status'] !== 'success') {
                $this->project->update(['health_status' => 'error']);

                Log::error("Upgrade failed for project {$this->project->name}: {$result['output']}");

                return;
            }

            $newHealthStatus = $this->project->hasBeenDeployed() ? 'online' : ($this->project->health_status === 'upgrading' ? 'offline' : $this->project->health_status);
            $this->project->update([
                'apis_hub_release_id' => $this->targetRelease->id,
                'health_status' => $newHealthStatus,
            ]);

            // Ensure default breakdown_passes and metric_set are pre-populated in sync_config so no unintentional nuclear resync occurs
            $syncConfig = $this->project->sync_config ?? [];
            $modified = false;
            if (isset($syncConfig['facebook_marketing']) && is_array($syncConfig['facebook_marketing'])) {
                if (!isset($syncConfig['facebook_marketing']['breakdown_passes'])) {
                    $syncConfig['facebook_marketing']['breakdown_passes'] = 'demographics';
                    $modified = true;
                }
                if (!isset($syncConfig['facebook_marketing']['metric_set'])) {
                    $syncConfig['facebook_marketing']['metric_set'] = 'full';
                    $modified = true;
                }
            }
            if ($modified) {
                $this->project->update(['sync_config' => $syncConfig]);
            }

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
                'output' => $deploymentLog->output . "\n\n=== EXCEPTION ===\nUnexpected upgrade error (" . get_class($e) . "). Remote state is unknown; inspect before retrying.",
                'completed_at' => now(),
            ]);

            $this->project->update(['health_status' => 'error']);

            Log::error("Upgrade exception for project {$this->project->id}", ['exception_class' => get_class($e)]);
        }
    }

    /**
     * Handle a job failure if the worker times out or receives a fatal termination.
     */
    public function failed(?\Throwable $exception): void
    {
        $this->project->update(['health_status' => 'error']);

        ProjectDeploymentLog::where('project_id', $this->project->id)
            ->where('status', 'running')
            ->latest('id')
            ->first()
            ?->update([
                'status' => 'failed',
                'output' => "FATAL ERROR / TIMEOUT: The upgrade process exceeded the queue execution limit or was terminated by the worker.\n\n" . ($exception ? get_class($exception) : 'Unknown error'),
                'completed_at' => now(),
            ]);

        Log::error("UpgradeProjectReleaseJob permanently failed for project {$this->project->id}", ['exception_class' => $exception ? get_class($exception) : null]);
    }
}
