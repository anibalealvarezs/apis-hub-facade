<?php

namespace App\Listeners;

use Laravel\Cashier\Events\WebhookReceived;
use App\Models\SubscriptionPlan;
use App\Models\BillingProfile;
use App\Models\BillingLog;
use App\Models\Invoice;
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

        if ($type === 'invoice.payment_succeeded') {
            $this->handleInvoicePaymentSucceeded($payload);
        }

        if (in_array($type, [
            'customer.subscription.updated',
            'customer.subscription.deleted',
            'invoice.payment_failed',
            'invoice.payment_succeeded'
        ])) {
            $this->processSubscriptionStateChange($payload);
        }
    }

    protected function handleInvoicePaymentSucceeded($payload)
    {
        $data = $payload['data']['object'];
        $stripeCustomerId = $data['customer'] ?? null;

        if (!$stripeCustomerId) return;

        $profile = BillingProfile::where('stripe_id', $stripeCustomerId)->first();
        if (!$profile) return;

        $subscription = null;
        $stripeSubscriptionId = $data['subscription'] ?? null;
        if ($stripeSubscriptionId) {
            $subscription = $profile->subscriptions()->where('stripe_id', $stripeSubscriptionId)->first();
        }

        $amount = $data['amount_paid'] ?? 0;
        if ($amount > 0) {
            $amount = $amount / 100;
        }

        Invoice::updateOrCreate(
            ['gateway_invoice_id' => $data['id']],
            [
                'billing_profile_id' => $profile->id,
                'subscription_id' => $subscription?->id,
                'gateway' => 'stripe',
                'amount' => $amount,
                'currency' => strtoupper($data['currency'] ?? 'usd'),
                'status' => $data['status'] ?? 'paid',
                'invoice_pdf_url' => $data['invoice_pdf'] ?? null,
                'paid_at' => isset($data['paid_at']) ? now()->createFromTimestamp($data['paid_at']) : now(),
            ]
        );

        Log::info('Stripe Invoice record created/updated', ['invoice_id' => $data['id']]);
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
                if ($profile->tier?->value !== $plan->tier) {
                    $profile->update(['tier' => $plan->tier]);
                    
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
                    
                    Log::info('Stripe Webhook: Billing Profile tier upgraded/restored', ['billing_profile_id' => $profile->id, 'tier' => $plan->tier]);
                }
            }
        } else {
            // Subscription is invalid, canceled, or past due
            if ($profile->tier?->value !== 'free') {
                $profile->update(['tier' => 'free']);
                
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
                
                Log::info('Stripe Webhook: Billing Profile tier downgraded to free', ['billing_profile_id' => $profile->id]);
                
                // Suspend projects due to downgrade
                try {
                    app(\App\Services\BillingLifecycleService::class)->enforceDowngradeLimits($profile, \App\Enums\UserTier::FREE);
                    
                    // Notify User
                    $user->notify(new \App\Notifications\BillingPaymentFailedNotification());
                } catch (\Exception $e) {
                    Log::error('Stripe Webhook Downgrade Exception', ['message' => $e->getMessage()]);
                }
            }
        }
    }
}
