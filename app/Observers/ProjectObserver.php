<?php

namespace App\Observers;

use App\Models\Project;
use App\Models\User;
use App\Enums\UserTier;
use App\Models\Subscription;
use Srmklive\PayPal\Services\PayPal as PayPalClient;
use Illuminate\Support\Facades\Log;

class ProjectObserver
{
    /**
     * Handle the Project "created" event.
     */
    public function created(Project $project): void
    {
        $this->syncSubscriptionQuantity($project->trueOwner);
    }

    /**
     * Handle the Project "deleted" event.
     */
    public function deleted(Project $project): void
    {
        $this->syncSubscriptionQuantity($project->trueOwner);
    }

    /**
     * Handle the Project "restored" event.
     */
    public function restored(Project $project): void
    {
        $this->syncSubscriptionQuantity($project->trueOwner);
    }

    /**
     * Synchronize the quantity of projects to active subscriptions for Enterprise users.
     */
    protected function syncSubscriptionQuantity(?User $user): void
    {
        if (!$user) return;

        // Only Enterprise tier has tiered/graduated pricing based on quantity
        if ($user->tier !== UserTier::ENTERPRISE) {
            return;
        }

        // Count all owned projects regardless of status
        $count = $user->ownedProjects()->count();
        
        // Payment providers usually require a minimum quantity of 1
        $quantity = max(1, $count);

        $billingProfileIds = $user->billingProfiles()->pluck('billing_profiles.id');
        
        $activeSubscriptions = Subscription::whereIn('billing_profile_id', $billingProfileIds)
            ->where(function ($q) {
                $q->where('stripe_status', 'active')
                  ->orWhere('paypal_status', 'ACTIVE');
            })
            ->get();

        foreach ($activeSubscriptions as $subscription) {
            try {
                if ($subscription->stripe_status === 'active') {
                    // Update Stripe via Cashier
                    $subscription->updateQuantity($quantity);
                    Log::info("ProjectObserver: Synced Stripe quantity for user {$user->id} to {$quantity}.");
                } 
                
                if ($subscription->paypal_status === 'ACTIVE' && $subscription->paypal_subscription_id) {
                    // Update PayPal via native API
                    $provider = new PayPalClient;
                    $provider->getAccessToken();
                    
                    $payload = [
                        'plan_id' => $subscription->plan->paypal_plan_id,
                        'quantity' => (string) $quantity
                    ];
                    
                    $response = $provider->reviseSubscription($subscription->paypal_subscription_id, $payload);
                    
                    if (isset($response['id']) || isset($response['status']) || isset($response['links'])) {
                         Log::info("ProjectObserver: Synced PayPal quantity for user {$user->id} to {$quantity}.");
                    } else {
                         Log::warning("ProjectObserver: Failed to sync PayPal quantity.", ['response' => $response]);
                    }
                }
            } catch (\Exception $e) {
                Log::error("ProjectObserver Exception syncing quantity", ['message' => $e->getMessage()]);
            }
        }
    }
}
