<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Srmklive\PayPal\Services\PayPal as PayPalClient;
use Illuminate\Support\Facades\Log;
use App\Models\Subscription;
use App\Models\Invoice;
use App\Models\BillingProfile;

class PayPalWebhookController extends Controller
{
    /**
     * Handle incoming PayPal Webhooks.
     */
    public function handle(Request $request)
    {
        // TODO: Validate webhook signature using PayPal SDK

        $eventType = $request->input('event_type');
        $resource = $request->input('resource');

        Log::info('PayPal Webhook Received', ['event' => $eventType, 'resource_id' => $resource['id'] ?? null]);

        switch ($eventType) {
            case 'BILLING.SUBSCRIPTION.ACTIVATED':
                $this->handleSubscriptionActivated($resource);
                break;
            case 'BILLING.SUBSCRIPTION.CANCELLED':
            case 'BILLING.SUBSCRIPTION.SUSPENDED':
            case 'BILLING.SUBSCRIPTION.EXPIRED':
                $this->handleSubscriptionEnded($resource, $eventType);
                break;
            case 'PAYMENT.SALE.COMPLETED':
                $this->handlePaymentSaleCompleted($resource);
                break;
            // Add more as needed
        }

        return response()->json(['status' => 'success']);
    }

    protected function handleSubscriptionActivated(array $resource)
    {
        $subscriptionId = $resource['id'];
        Subscription::where('paypal_id', $subscriptionId)->update([
            'paypal_status' => 'ACTIVE',
        ]);
    }

    protected function handleSubscriptionEnded(array $resource, string $eventType)
    {
        $subscriptionId = $resource['id'];
        
        $statusMap = [
            'BILLING.SUBSCRIPTION.CANCELLED' => 'CANCELLED',
            'BILLING.SUBSCRIPTION.SUSPENDED' => 'SUSPENDED',
            'BILLING.SUBSCRIPTION.EXPIRED' => 'EXPIRED',
        ];

        Subscription::where('paypal_id', $subscriptionId)->update([
            'paypal_status' => $statusMap[$eventType] ?? 'INACTIVE',
            'ends_at' => now(), // Or parse from resource if available
        ]);
    }

    protected function handlePaymentSaleCompleted(array $resource)
    {
        $billingAgreementId = $resource['billing_agreement_id'] ?? null;
        
        if (!$billingAgreementId) {
            return; // Not a subscription payment
        }

        $subscription = Subscription::where('paypal_id', $billingAgreementId)->first();

        if ($subscription) {
            Invoice::updateOrCreate(
                ['gateway_invoice_id' => $resource['id']],
                [
                    'billing_profile_id' => $subscription->billing_profile_id,
                    'gateway' => 'paypal',
                    'amount' => $resource['amount']['total'] ?? 0,
                    'currency' => $resource['amount']['currency'] ?? 'USD',
                    'status' => 'paid',
                    'paid_at' => now(), // You might want to parse $resource['create_time']
                ]
            );
        }
    }
}
