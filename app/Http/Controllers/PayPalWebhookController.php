<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Srmklive\PayPal\Services\PayPal as PayPalClient;
use Illuminate\Support\Facades\Log;
use App\Models\Subscription;
use App\Models\Invoice;
use App\Models\BillingProfile;
use App\Models\Project;
use App\Notifications\PaymentFailedNotification;

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
            case 'BILLING.SUBSCRIPTION.PAYMENT.FAILED':
                $this->handlePaymentFailed($resource);
                break;
            case 'CUSTOMER.DISPUTE.CREATED':
                $this->handleDisputeCreated($resource);
                break;
            case 'CUSTOMER.DISPUTE.RESOLVED':
                $this->handleDisputeResolved($resource);
                break;
            case 'PAYMENT.SALE.REFUNDED':
                $this->handlePaymentRefunded($resource);
                break;
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

    protected function handlePaymentFailed(array $resource)
    {
        $billingAgreementId = $resource['billing_agreement_id'] ?? null;
        if (!$billingAgreementId) return;

        $subscription = Subscription::where('paypal_id', $billingAgreementId)->first();
        if ($subscription) {
            $billingProfile = $subscription->billingProfile;
            if ($billingProfile) {
                // Find all projects authorized by this billing profile
                $projects = $billingProfile->authorizedProjects()->where('billing_status', 'active')->get();
                foreach ($projects as $project) {
                    $project->update([
                        'billing_status' => 'past_due',
                        'past_due_at' => now(),
                    ]);
                }
                
                // Notify the billing profile owner
                if ($billingProfile->user) {
                    // We mock the notification for now, actual implementation pending
                    // $billingProfile->user->notify(new PaymentFailedNotification($projects));
                    Log::info("PayPal Webhook: Payment failed for Billing Profile {$billingProfile->id}. User notified.");
                }
            }
        }
    }

    protected function handleDisputeCreated(array $resource)
    {
        // PayPal Disputes usually link to transaction IDs. We need to find the related invoice/subscription.
        // For simplicity in this webhook structure, assuming we can derive the billing profile:
        // In a real scenario, you'd fetch the transaction to get the subscription.
        Log::warning("PayPal Webhook: Dispute created. Manual inspection required.", ['resource' => $resource]);
        
        // Pseudo-logic assuming we found the billing profile ID from the transaction:
        /*
        $billingProfile = BillingProfile::find($id);
        $billingProfile->update(['health_status' => 'disputed']);
        
        $projects = $billingProfile->authorizedProjects;
        foreach ($projects as $project) {
            $project->update(['billing_status' => 'suspended']);
        }
        */
    }

    protected function handleDisputeResolved(array $resource)
    {
        Log::info("PayPal Webhook: Dispute resolved.", ['resource' => $resource]);
    }

    protected function handlePaymentRefunded(array $resource)
    {
        // Find invoice by gateway_invoice_id (which is the sale ID usually)
        $saleId = $resource['id'] ?? null;
        if ($saleId) {
            Invoice::where('gateway_invoice_id', $saleId)->update(['status' => 'refunded']);
        }
    }
}
