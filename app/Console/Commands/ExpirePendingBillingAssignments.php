<?php

namespace App\Console\Commands;

use App\Models\BillingProfile;
use App\Models\Project;
use App\Notifications\BillingProfileAssignmentCancelledNotification;
use App\Notifications\BillingProfileAssignmentExpiredNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ExpirePendingBillingAssignments extends Command
{
    protected $signature = 'billing:expire-pending-assignments';

    protected $description = 'Expires pending billing profile assignment requests older than 7 days and resets projects to their owner\'s default billing profile.';

    public function handle()
    {
        $this->info('Expiring stale pending billing assignments...');

        $expired = Project::where('billing_status', 'pending_approval')
            ->where('updated_at', '<=', Carbon::now()->subDays(7))
            ->get();

        if ($expired->isEmpty()) {
            $this->info('No expired pending assignments found.');
            return;
        }

        foreach ($expired as $project) {
            $user = $project->user;
            $sharedProfile = $project->billingProfile;

            if (!$user) {
                continue;
            }

            $this->info("Expiring pending assignment for project {$project->id} ({$project->name})");

            $defaultProfile = $user->billingProfiles()
                ->where('is_default', true)
                ->first();

            if ($defaultProfile && $defaultProfile->id !== $sharedProfile?->id) {
                $project->update([
                    'billing_profile_id' => $defaultProfile->id,
                    'billing_status' => 'active',
                    'is_active' => true,
                ]);
            } else {
                $fallback = $user->billingProfiles()
                    ->where('id', '!=', $sharedProfile?->id)
                    ->first();

                if ($fallback) {
                    $project->update([
                        'billing_profile_id' => $fallback->id,
                        'billing_status' => 'active',
                        'is_active' => true,
                    ]);
                } else {
                    $project->update([
                        'billing_profile_id' => null,
                        'billing_status' => 'suspended',
                        'is_active' => false,
                    ]);
                }
            }

            $user->notify(new BillingProfileAssignmentExpiredNotification(
                billingProfileName: $sharedProfile?->name ?? 'Unknown',
                projectName: $project->name,
            ));

            if ($sharedProfile && $sharedProfile->user_id !== $user->id) {
                $sharedProfile->user->notify(new BillingProfileAssignmentCancelledNotification(
                    billingProfileName: $sharedProfile->name,
                    projectName: $project->name,
                    reason: 'expired',
                ));
            }

            $this->line("  -> Expired for project {$project->id}");
            Log::info("Expired pending billing assignment for project {$project->id}");
        }

        $this->info('Completed expiring pending billing assignments.');
    }
}
