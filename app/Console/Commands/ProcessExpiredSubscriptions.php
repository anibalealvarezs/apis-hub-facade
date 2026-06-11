<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Subscription;
use App\Models\BillingLog;
use App\Services\BillingLifecycleService;
use Carbon\Carbon;

class ProcessExpiredSubscriptions extends Command
{
    protected $signature = 'billing:process-expired';

    protected $description = 'Process subscriptions that have passed their ends_at date and enforce downgrades.';

    public function handle(BillingLifecycleService $lifecycleService)
    {
        $this->info('Checking for expired subscriptions...');

        // Find subscriptions that ended today or earlier and are not already marked completely inactive
        // Usually ends_at is set when cancelled, and we process them once they pass that date.
        // In Cashier, stripe_status might become 'canceled', but we need to check if the local user tier is still elevated.
        
        $expiredSubscriptions = Subscription::whereNotNull('ends_at')
            ->where('ends_at', '<=', Carbon::now())
            // Ideally we also flag them so we don't process them twice, or we check if user tier is > free
            ->get();

        foreach ($expiredSubscriptions as $sub) {
            $profile = $sub->billingProfile;
            if (!$profile) continue;

            // Check if profile has ANY OTHER active subscriptions that haven't expired
            $hasOtherActiveSub = $profile->subscriptions()
                ->where(function($q) {
                    $q->whereNull('ends_at')->orWhere('ends_at', '>', Carbon::now());
                })
                ->where(function($q) {
                    $q->where('stripe_status', 'active')
                      ->orWhere('paypal_status', 'ACTIVE');
                })
                ->exists();

            if (!$hasOtherActiveSub && $profile->tier?->value !== 'free' && $profile->tier?->value !== 'suspended') {
                $this->info("Enforcing downgrade for Profile {$profile->id} due to expired subscription {$sub->id}.");
                
                // Downgrade and suspend
                $targetTier = $profile->tier === \App\Enums\UserTier::ENTERPRISE ? \App\Enums\UserTier::SUSPENDED : \App\Enums\UserTier::FREE;
                $results = $lifecycleService->enforceTierLimits($profile, $targetTier);
                $suspendedCount = count($results['suspended'] ?? []);
                
                BillingLog::create([
                    'user_id' => $profile->user_id,
                    'event_type' => 'downgrade_scheduled',
                    'gateway' => 'system',
                    'description' => "Downgraded profile {$profile->id} to {$targetTier->value} due to expired subscription {$sub->id}.",
                    'metadata' => [
                        'subscription_id' => $sub->id,
                        'target_tier' => $targetTier->value,
                        'suspended_projects_count' => $suspendedCount
                    ]
                ]);
                
                // Notify Owner
                if ($profile->user) {
                    $profile->user->notify(new \App\Notifications\ProjectsSuspendedNotification($suspendedCount));
                }
            }
        }

        // Also check BillingProfiles that have no active subscriptions but their current_cycle_ends_at has passed
        // This covers manual comped tiers that have expired!
        $expiredProfiles = \App\Models\BillingProfile::whereNotNull('current_cycle_ends_at')
            ->where('current_cycle_ends_at', '<=', Carbon::now())
            ->where('tier', '!=', 'free')
            ->where('tier', '!=', 'suspended')
            ->get();

        foreach ($expiredProfiles as $profile) {
            $hasActiveSub = $profile->subscriptions()
                ->where(function($q) {
                    $q->whereNull('ends_at')->orWhere('ends_at', '>', Carbon::now());
                })
                ->where(function($q) {
                    $q->where('stripe_status', 'active')
                      ->orWhere('paypal_status', 'ACTIVE');
                })
                ->exists();

            if (!$hasActiveSub) {
                $this->info("Enforcing downgrade for Profile {$profile->id} due to expired billing cycle (manual override).");
                $targetTier = $profile->tier === \App\Enums\UserTier::ENTERPRISE ? \App\Enums\UserTier::SUSPENDED : \App\Enums\UserTier::FREE;
                $results = $lifecycleService->enforceTierLimits($profile, $targetTier);
                $suspendedCount = count($results['suspended'] ?? []);
                
                // Nullify the ends_at so we don't process it again
                $profile->updateQuietly(['current_cycle_ends_at' => null]);
            }
        }

        $this->info('Completed checking expired subscriptions.');
    }
}
