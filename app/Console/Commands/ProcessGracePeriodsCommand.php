<?php

namespace App\Console\Commands;

use App\Models\AssetBillingLock;
use Illuminate\Console\Command;

class ProcessGracePeriodsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'billing:process-grace-periods';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Evaluate staged asset locks and promote them to locked if the grace period has expired.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting grace period evaluation...');
        
        // Find all staged locks
        $stagedLocks = AssetBillingLock::with('project')->where('status', 'staged')->get();
        
        $promotedCount = 0;

        foreach ($stagedLocks as $lock) {
            $project = $lock->project;
            
            if (!$project) {
                $lock->delete();
                continue;
            }

            // The grace period only starts counting if the server is deployed.
            // If it hasn't been deployed yet, the grace period is paused.
            if (!$project->last_deployed_at) {
                continue;
            }

            // The countdown begins at staged_at. Do NOT use last_deployed_at here —
            // that would reset the grace period on every redeploy.
            $gracePeriodEndsAt = $lock->staged_at->copy()->addHours(2);
            
            if (now()->greaterThanOrEqualTo($gracePeriodEndsAt)) {
                $lock->update([
                    'status' => 'locked',
                    'locked_at' => now(),
                ]);
                $promotedCount++;
                $this->info("Promoted asset {$lock->asset_identifier} in project {$project->id} to locked.");
            }
        }

        $this->info("Grace period evaluation completed. Promoted {$promotedCount} assets.");
    }
}
