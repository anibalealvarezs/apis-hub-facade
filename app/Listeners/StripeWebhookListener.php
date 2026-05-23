<?php

namespace App\Listeners;

use Laravel\Cashier\Events\WebhookReceived;
use App\Models\SubscriptionPlan;
use App\Models\BillingProfile;
use App\Models\BillingLog;
use Illuminate\Support\Facades\Log;

class StripeWebhookListener
{
    /**
     * Handle received Stripe webhooks.
     */
    public function handle(WebhookReceived $event)
    {
        $payload = $event->payload;
        $type = $payload['type'] ?? '';

        Log::info('Stripe Webhook Received', ['type' => $type]);
        
        BillingLog::create([
            'event_type' => 'webhook_received',
            'gateway' => 'stripe',
            'description' => "Received Stripe webhook: {$type}",
            'metadata' => [
                'type' => $type,
                'id' => $payload['id'] ?? null
            ]
        ]);

        if (in_array($type, [
            'customer.subscription.updated',
            'customer.subscription.deleted',
            'invoice.payment_failed',
            'invoice.payment_succeeded'
        ])) {
            $this->processSubscriptionStateChange($payload);
        }
    }

    protected function processSubscriptionStateChange($payload)
    {
        $data = $payload['data']['object'];
        
        // Sometimes the object is an invoice, sometimes a subscription
        $stripeCustomerId = $data['customer'] ?? null;
        
        if (!$stripeCustomerId) return;

        $profile = BillingProfile::where('stripe_id', $stripeCustomerId)->first();
        if (!$profile) return;

        $user = $profile->user;
        if (!$user) return;

        // Cashier has likely already synchronized the `subscriptions` table.
        // Let's reload the profile's relations to get fresh subscription state.
        $profile->load('subscriptions');
        
        $subscription = $profile->subscription('default');
        
        if ($subscription && $subscription->valid()) {
            // Find the tier associated with the current stripe price
            // Cashier stores the price id in the subscription items, or on the subscription itself depending on configuration.
            $stripePriceId = $subscription->stripe_price;
            if (!$stripePriceId && $subscription->items->first()) {
                $stripePriceId = $subscription->items->first()->stripe_price;
            }
            
            $plan = SubscriptionPlan::where('stripe_price_id', $stripePriceId)->first();
            
            if ($plan) {
                if ($user->tier?->value !== $plan->tier) {
                    $user->update(['tier' => $plan->tier]);
                    
                    BillingLog::create([
                        'user_id' => $user->id,
                        'billing_profile_id' => $profile->id,
                        'event_type' => 'subscription_created',
                        'gateway' => 'stripe',
                        'description' => "Tier upgraded/restored to {$plan->tier} via webhook.",
                        'metadata' => [
                            'price_id' => $stripePriceId,
                            'plan_id' => $plan->id
                        ]
                    ]);
                    
                    Log::info('Stripe Webhook: Tier upgraded/restored', ['user_id' => $user->id, 'tier' => $plan->tier]);
                }
            }
        } else {
            // Subscription is invalid, canceled, or past due
            if ($user->tier?->value !== 'free') {
                $user->update(['tier' => 'free']);
                
                BillingLog::create([
                    'user_id' => $user->id,
                    'billing_profile_id' => $profile->id,
                    'event_type' => 'payment_failed',
                    'gateway' => 'stripe',
                    'description' => "Tier downgraded to free via webhook (subscription invalid/canceled).",
                    'metadata' => [
                        'stripe_customer_id' => $stripeCustomerId
                    ]
                ]);
                
                Log::info('Stripe Webhook: Tier downgraded to free', ['user_id' => $user->id]);
                
                // Suspend projects due to downgrade
                try {
                    app(\App\Services\BillingLifecycleService::class)->handleDowngradeSideEffects($user);
                    
                    // Notify User
                    $user->notify(new \App\Notifications\BillingPaymentFailedNotification());
                } catch (\Exception $e) {
                    Log::error('Stripe Webhook Downgrade Exception', ['message' => $e->getMessage()]);
                }
            }
        }
    }
}
