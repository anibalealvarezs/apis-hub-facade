<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Srmklive\PayPal\Services\PayPal as PayPalClient;
use App\Models\SubscriptionPlan;
use App\Models\BillingProfile;
use App\Models\Subscription;
use App\Models\SubscriptionItem;

class PayPalCheckoutController extends Controller
{
    /**
     * Start the checkout process and redirect to PayPal.
     */
    public function checkout(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:subscription_plans,id',
            'billing_profile_id' => 'required|exists:billing_profiles,id',
        ]);

        $plan = SubscriptionPlan::findOrFail($request->plan_id);
        $profile = BillingProfile::findOrFail($request->billing_profile_id);

        // Security check: ensure user has access to this billing profile
        if (!$request->user()->getAvailableBillingProfiles()->contains($profile->id)) {
            abort(403, 'Unauthorized access to this billing profile.');
        }

        if (!$plan->paypal_plan_id) {
            return back()->with('error', 'This plan is not configured for PayPal yet.');
        }

        try {
            $provider = new PayPalClient;
            $provider->getAccessToken();

            $data = [
                "plan_id" => $plan->paypal_plan_id,
                "custom_id" => json_encode([
                    'user_id' => $request->user()->id,
                    'billing_profile_id' => $profile->id,
                    'plan_id' => $plan->id,
                ]),
                "application_context" => [
                    "brand_name" => config('app.name'),
                    "locale" => "en-US",
                    "shipping_preference" => "NO_SHIPPING",
                    "user_action" => "SUBSCRIBE_NOW",
                    "return_url" => route('paypal.return'),
                    "cancel_url" => route('filament.account.pages.account-subscription'),
                ]
            ];

            $response = $provider->createSubscription($data);

            if (isset($response['id']) && $response['id'] != null) {
                // Find approval URL
                foreach ($response['links'] as $links) {
                    if ($links['rel'] == 'approve') {
                        return redirect()->away($links['href']);
                    }
                }
                return back()->with('error', 'Something went wrong while generating the PayPal link.');
            } else {
                return back()->with('error', $response['message'] ?? 'Something went wrong.');
            }

        } catch (\Exception $e) {
            return back()->with('error', 'Error connecting to PayPal: ' . $e->getMessage());
        }
    }

    /**
     * Handle the return from PayPal.
     */
    public function return(Request $request)
    {
        $subscriptionId = $request->query('subscription_id');

        if (!$subscriptionId) {
            return redirect()->route('filament.account.pages.account-subscription')
                ->with('error', 'Subscription ID missing.');
        }

        try {
            $provider = new PayPalClient;
            $provider->getAccessToken();

            $response = $provider->showSubscriptionDetails($subscriptionId);

            if (isset($response['status']) && in_array($response['status'], ['ACTIVE', 'APPROVAL_PENDING'])) {
                
                $customId = json_decode($response['custom_id'], true);
                
                $plan = SubscriptionPlan::find($customId['plan_id']);
                $profile = BillingProfile::find($customId['billing_profile_id']);

                if (!$plan || !$profile) {
                    return redirect()->route('filament.account.pages.account-subscription')
                        ->with('error', 'Invalid local record mapping.');
                }

                // Create local subscription record
                $subscription = Subscription::updateOrCreate(
                    ['paypal_subscription_id' => $subscriptionId],
                    [
                        'billing_profile_id' => $profile->id,
                        'subscription_plan_id' => $plan->id,
                        'type' => 'default', // standard Cashier type
                        'paypal_status' => $response['status'],
                    ]
                );

                // Update the user's tier immediately
                $request->user()->update([
                    'tier' => $plan->tier
                ]);

                // Assuming success
                return redirect()->route('filament.account.pages.account-subscription')
                    ->with('success', 'Subscription created successfully!');
            }

            return redirect()->route('filament.account.pages.account-subscription')
                ->with('error', 'Subscription is not active.');

        } catch (\Exception $e) {
            return redirect()->route('filament.account.pages.account-subscription')
                ->with('error', 'Error verifying subscription: ' . $e->getMessage());
        }
    }
}
