<?php

namespace App\Observers;

use App\Models\SubscriptionPlan;
use Srmklive\PayPal\Services\PayPal as PayPalClient;
use Illuminate\Support\Facades\Log;

class SubscriptionPlanObserver
{
    /**
     * Handle the SubscriptionPlan "created" event.
     */
    public function created(SubscriptionPlan $plan): void
    {
        $this->syncWithPayPal($plan);
    }

    /**
     * Handle the SubscriptionPlan "updated" event.
     */
    public function updated(SubscriptionPlan $plan): void
    {
        // If price or cycle changes, we must create a NEW plan in PayPal 
        // because PayPal doesn't allow changing prices of active billing plans easily.
        if ($plan->wasChanged(['price', 'currency', 'billing_cycle'])) {
            $this->syncWithPayPal($plan);
        } else if ($plan->wasChanged(['name', 'description'])) {
            // Srmklive doesn't have a clean wrapper for patching plan details easily, 
            // and it doesn't affect billing. We'll skip for now unless specifically requested.
        }
    }

    /**
     * Creates a Product and a Plan in PayPal, then saves the paypal_plan_id.
     */
    protected function syncWithPayPal(SubscriptionPlan $plan)
    {
        // Only sync if price is > 0. Free plans are handled locally.
        if ($plan->price <= 0) {
            return;
        }

        try {
            $provider = new PayPalClient;
            $provider->getAccessToken();

            // 1. Create Product (or we could use a generic product for all plans, but one per plan is cleaner)
            $productData = [
                "name" => "APIs Hub " . ucfirst($plan->name) . " Plan",
                "description" => $plan->description ?? "Subscription to APIs Hub",
                "type" => "SERVICE",
                "category" => "SOFTWARE",
            ];
            
            $productResponse = $provider->createProduct($productData);
            
            if (isset($productResponse['error'])) {
                Log::error('PayPal Product Creation Failed', $productResponse);
                return;
            }

            $productId = $productResponse['id'];

            // 2. Create Billing Plan
            $planData = [
                "product_id" => $productId,
                "name" => $plan->name . " " . ucfirst($plan->billing_cycle),
                "description" => $plan->name . " billing plan",
                "status" => "ACTIVE",
                "billing_cycles" => [
                    [
                        "frequency" => [
                            "interval_unit" => $plan->billing_cycle === 'yearly' ? 'YEAR' : 'MONTH',
                            "interval_count" => 1
                        ],
                        "tenure_type" => "REGULAR",
                        "sequence" => 1,
                        "total_cycles" => 0, // Infinite
                        "pricing_scheme" => [
                            "fixed_price" => [
                                "value" => (string) $plan->price,
                                "currency_code" => strtoupper($plan->currency)
                            ]
                        ]
                    ]
                ],
                "payment_preferences" => [
                    "auto_bill_outstanding" => true,
                    "setup_fee" => [
                        "value" => "0",
                        "currency_code" => strtoupper($plan->currency)
                    ],
                    "setup_fee_failure_action" => "CONTINUE",
                    "payment_failure_threshold" => 3
                ],
                "taxes" => [
                    "percentage" => "0",
                    "inclusive" => false
                ]
            ];

            $planResponse = $provider->createPlan($planData);

            if (isset($planResponse['error'])) {
                Log::error('PayPal Plan Creation Failed', $planResponse);
                return;
            }

            // 3. Save the paypal_plan_id back to our database silently without triggering updated event
            $plan->refresh();
            $plan->paypal_plan_id = $planResponse['id'];
            $plan->saveQuietly();

        } catch (\Exception $e) {
            Log::error('PayPal Sync Exception', ['message' => $e->getMessage()]);
        }
    }
}
