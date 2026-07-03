<?php

namespace App\Jobs;

use App\Models\Project;
use App\Models\ApisHubRelease;
use App\Services\ConfigPayloadService;
use App\Services\RemoteEngineService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class HydrateProjectConfigJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $project;

    /**
     * Create a new job instance.
     *
     * @param Project $project
     */
    public function __construct(Project $project)
    {
        $this->project = $project;
    }

    /**
     * Execute the job.
     */
    public function handle(RemoteEngineService $remoteService, ConfigPayloadService $configPayloadService): void
    {
        $syncConfig = $this->project->sync_config ?? [];

        try {
            $remoteService->updateCredentials($this->project, [
                'type' => 'global',
                'jobs_timeout_hours' => $syncConfig['jobs_timeout_hours'] ?? 1,
            ]);
            Log::info("Hydrated global configuration into project {$this->project->id} via HydrateProjectConfigJob");
        } catch (\Exception $e) {
            Log::error("Failed to hydrate global config for project {$this->project->id}: " . $e->getMessage());
        }

        if (!empty($syncConfig)) {
            $release = $this->project->apisHubRelease ?? ApisHubRelease::where('is_active', true)->first();
            
            if ($release) {
                foreach ($syncConfig as $channel => $channelConfig) {
                    if (!is_array($channelConfig)) continue;
                    
                    $payloadData = $configPayloadService->buildPayload($this->project, $release, $channel, $channelConfig, $syncConfig[$channel] ?? []);
                    if ($payloadData) {
                        try {
                            $remoteService->updateCredentials($this->project, $payloadData['payload']);
                            Log::info("Hydrated {$channel} configuration into project {$this->project->id} via HydrateProjectConfigJob");
                        } catch (\Exception $e) {
                            Log::error("Failed to hydrate {$channel} config for project {$this->project->id}: " . $e->getMessage());
                        }
                    }
                }
            }
        }
    }
}
