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

class DeployProjectJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600; // 10 minutes maximum

    public function __construct(
        public Project $project
    ) {}

    public function handle(DeployerService $deployer): void
    {
        // 1. Create a Deployment Log
        $deploymentLog = ProjectDeploymentLog::create([
            'project_id' => $this->project->id,
            'status' => 'running',
            'started_at' => now(),
            'output' => 'Starting deployment process...',
        ]);

        try {
            // 2. Run Deployment
            $result = $deployer->deploy($this->project);

            // 3. Update Log with Result
            $deploymentLog->update([
                'status' => $result['status'] === 'success' ? 'success' : 'failed',
                'output' => $deploymentLog->output . "\n\n=== DEPLOYMENT OUTPUT ===\n" . $result['output'],
                'completed_at' => now(),
            ]);

            // 4. If success, update project active status
            if ($result['status'] === 'success') {
                $this->project->update([
                    'is_active' => true, 
                    'health_status' => 'online',
                    'last_deployed_at' => now(),
                ]);

                // 5. Hydrate remote API Hub with existing social tokens if present
                $providers = ['facebook', 'google'];
                foreach ($providers as $provider) {
                    $profileIdColumn = "{$provider}_profile_id";
                    if ($this->project->{$profileIdColumn}) {
                        $profile = \App\Models\ChannelProfile::find($this->project->{$profileIdColumn});
                        if ($profile && $profile->access_token) {
                            $config = config("services.{$provider}")['channel_scopes'] ?? [];
                            $scopes = $config['default'] ?? [];
                            
                            $deployer->injectSocialTokens($this->project, $profile->access_token, $provider, [
                                'user_id' => $profile->provider_account_id,
                                'email' => $profile->email,
                                'refresh_token' => $profile->refresh_token,
                                'scopes' => $scopes,
                            ]);
                            Log::info("Hydrated {$provider} tokens into newly deployed project {$this->project->id}");
                        }
                    }
                }
            } else {
                Log::error("Deployment failed for project {$this->project->id}");
            }

        } catch (\Throwable $e) {
            // Update log on Exception
            $deploymentLog->update([
                'status' => 'failed',
                'output' => $deploymentLog->output . "\n\n=== EXCEPTION ===\n" . $e->getMessage() . "\n" . $e->getTraceAsString(),
                'completed_at' => now(),
            ]);
            Log::error("Deployment exception for project {$this->project->id}", ['exception' => $e]);
        }
    }
}
