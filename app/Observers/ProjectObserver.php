<?php

namespace App\Observers;

use App\Models\Project;
use App\Models\BillingProfile;
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
        $this->syncSubscriptionQuantity($project->billingProfile);
    }

    /**
     * Handle the Project "deleted" event.
     */
    public function deleted(Project $project): void
    {
        $this->syncSubscriptionQuantity($project->billingProfile);
    }

    /**
     * Handle the Project "restored" event.
     */
    public function restored(Project $project): void
    {
        $this->syncSubscriptionQuantity($project->billingProfile);
    }

    /**
     * Synchronize the quantity of projects to active subscriptions for Enterprise billing profiles.
     */
    protected function syncSubscriptionQuantity(?BillingProfile $profile): void
    {
        if (!$profile) return;

        // Only Enterprise tier has tiered/graduated pricing based on quantity
        if ($profile->tier !== UserTier::ENTERPRISE) {
            return;
        }

        // Count all projects assigned to this billing profile
        $count = $profile->projects()->count();
        
        // Payment providers usually require a minimum quantity of 1
        $quantity = max(1, $count);
        
        $activeSubscriptions = Subscription::where('billing_profile_id', $profile->id)
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
                    Log::info("ProjectObserver: Synced Stripe quantity for billing profile {$profile->id} to {$quantity}.");
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
                         Log::info("ProjectObserver: Synced PayPal quantity for billing profile {$profile->id} to {$quantity}.");
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
