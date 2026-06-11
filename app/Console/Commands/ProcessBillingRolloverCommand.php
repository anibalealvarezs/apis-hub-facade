<?php

namespace App\Console\Commands;

use App\Models\AssetBillingLock;
use App\Models\Project;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class ProcessBillingRolloverCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'billing:process-rollover';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process monthly billing cycle rollovers, clearing untoggled assets from the ledger.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting billing rollover processing...');
        
        // Find all billing profiles whose billing cycle renewed today
        $profilesRenewingToday = \App\Models\BillingProfile::whereDay('current_cycle_starts_at', now()->day)->get();

        foreach ($profilesRenewingToday as $profile) {
            $this->info("Processing rollover for Profile ID: {$profile->id}");
            $this->processProfileRollover($profile);
        }

        $this->info('Billing rollover processing completed.');
    }

    protected function processProfileRollover(\App\Models\BillingProfile $profile)
    {
        // 1. Get all projects for this profile
        $projectIds = $profile->projects()->pluck('id');
        
        // 2. Get all locks across all their projects
        $locks = AssetBillingLock::whereIn('project_id', $projectIds)->get();

        // Group by project to minimize querying
        $locksByProject = $locks->groupBy('project_id');

        foreach ($locksByProject as $projectId => $projectLocks) {
            $project = Project::find($projectId);
            if (!$project) {
                // Project deleted, remove locks
                AssetBillingLock::whereIn('id', $projectLocks->pluck('id'))->delete();
                continue;
            }

            $syncConfig = $project->sync_config ?? [];
            $activeAssetIdentifiers = [];

            // Parse the current sync_config to find what is CURRENTLY enabled
            foreach ($syncConfig as $channelKey => $channelConfig) {
                if (!is_array($channelConfig)) continue;
                if (empty($channelConfig['enabled'])) continue;

                foreach ($channelConfig as $key => $value) {
                    if (is_array($value)) {
                        foreach ($value as $asset) {
                            if (!empty($asset['enabled']) && empty($asset['lost_access'])) {
                                $id = $asset['id'] ?? $asset['url'] ?? null;
                                if ($id) {
                                    $activeAssetIdentifiers[] = $id;
                                }
                            }
                        }
                    }
                }
            }

            // Cross-reference locks with currently active assets
            foreach ($projectLocks as $lock) {
                if (!in_array($lock->asset_identifier, $activeAssetIdentifiers)) {
                    // Asset is no longer enabled in the project config!
                    // Free the quota by deleting the lock since a new billing month started.
                    $lock->delete();
                    $this->info("Freed quota for asset {$lock->asset_identifier} in project {$project->id}");
                }
            }
        }
    }
}
