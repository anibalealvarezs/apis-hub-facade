<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Subscription;
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
            $user = $sub->billingProfile?->user;
            if (!$user) continue;

            // Check if user has ANY OTHER active subscriptions that haven't expired
            $hasOtherActiveSub = $user->subscriptions()
                ->where(function($q) {
                    $q->whereNull('ends_at')->orWhere('ends_at', '>', Carbon::now());
                })
                ->where(function($q) {
                    $q->where('stripe_status', 'active')
                      ->orWhere('paypal_status', 'ACTIVE');
                })
                ->exists();

            if (!$hasOtherActiveSub && $user->tier?->value !== 'free') {
                $this->info("Enforcing downgrade for User {$user->id} due to expired subscription {$sub->id}.");
                
                // Downgrade and suspend
                $targetTier = $user->tier === \App\Enums\UserTier::ENTERPRISE ? \App\Enums\UserTier::SUSPENDED : \App\Enums\UserTier::FREE;
                $suspendedCount = $lifecycleService->enforceDowngradeLimits($user, $targetTier);
                
                // Notify User
                $user->notify(new \App\Notifications\ProjectsSuspendedNotification($suspendedCount));
            }
        }

        $this->info('Completed checking expired subscriptions.');
    }
}
