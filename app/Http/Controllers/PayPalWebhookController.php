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
        Subscription::where('paypal_subscription_id', $subscriptionId)->update([
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

        Subscription::where('paypal_subscription_id', $subscriptionId)->update([
            'paypal_status' => $statusMap[$eventType] ?? 'INACTIVE',
            'ends_at' => now(),
        ]);
    }

    protected function handlePaymentSaleCompleted(array $resource)
    {
        $billingAgreementId = $resource['billing_agreement_id'] ?? null;
        
        if (!$billingAgreementId) {
            return;
        }

        $subscription = Subscription::where('paypal_subscription_id', $billingAgreementId)->first();

        if ($subscription) {
            $totalAmount = (float) ($resource['amount']['total'] ?? 0);
            $taxData = app(\App\Services\TaxCalculationService::class)->calculateTaxes($subscription->billingProfile, $totalAmount);

            Invoice::updateOrCreate(
                ['gateway_invoice_id' => $resource['id']],
                array_merge([
                    'billing_profile_id' => $subscription->billing_profile_id,
                    'subscription_id' => $subscription->id,
                    'gateway' => 'paypal',
                    'amount' => $totalAmount,
                    'currency' => $resource['amount']['currency'] ?? 'USD',
                    'status' => 'paid',
                    'paid_at' => now(),
                ], $taxData)
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
                    $billingProfile->user->notify(new \App\Notifications\BillingPaymentFailedNotification());
                    Log::info("PayPal Webhook: Payment failed for Billing Profile {$billingProfile->id}. User notified.");
                }
            }
        }
    }

    protected function handleDisputeCreated(array $resource)
    {
        $saleId = $resource['disputed_transactions'][0]['seller_transaction_id'] ?? null;
        if (!$saleId) {
            Log::warning('PayPal Dispute: No seller_transaction_id found', ['resource' => $resource]);
            return;
        }

        $invoice = Invoice::where('gateway_invoice_id', $saleId)->first();
        if (!$invoice) {
            Log::warning('PayPal Dispute: No local invoice found for sale', ['sale_id' => $saleId]);
            return;
        }

        $invoice->update(['status' => 'disputed']);

        $profile = $invoice->billingProfile;
        if ($profile) {
            $projects = $profile->authorizedProjects()->where('billing_status', 'active')->get();
            foreach ($projects as $project) {
                $project->update(['billing_status' => 'suspended']);
            }

            if ($profile->user) {
                $profile->user->notify(new \App\Notifications\PaymentFailedNotification());
            }
        }

        Log::info("PayPal Webhook: Dispute created for invoice {$invoice->id}");
    }

    protected function handleDisputeResolved(array $resource)
    {
        $saleId = $resource['disputed_transactions'][0]['seller_transaction_id'] ?? null;
        if (!$saleId) {
            Log::warning('PayPal Dispute resolved: No seller_transaction_id found');
            return;
        }

        $invoice = Invoice::where('gateway_invoice_id', $saleId)->first();
        if (!$invoice) {
            Log::warning('PayPal Dispute resolved: No local invoice found', ['sale_id' => $saleId]);
            return;
        }

        $outcome = $resource['dispute_outcome']['outcome_code'] ?? '';
        $newStatus = in_array($outcome, ['RESOLVED_SELLER_FAVOUR', 'RESOLVED_BUYER_FAVOUR_WITH_GUARANTEE'])
            ? 'paid'
            : 'refunded';

        $invoice->update(['status' => $newStatus]);

        $profile = $invoice->billingProfile;
        if ($profile && $newStatus === 'paid') {
            $projects = $profile->authorizedProjects()->where('billing_status', 'suspended')->get();
            foreach ($projects as $project) {
                $project->update(['billing_status' => 'active']);
            }
        }

        Log::info("PayPal Webhook: Dispute resolved for invoice {$invoice->id}, status set to {$newStatus}");
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
