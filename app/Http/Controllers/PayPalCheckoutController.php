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

        \Illuminate\Support\Facades\Log::info('PayPal Return triggered', ['subscription_id' => $subscriptionId, 'query' => $request->query()]);

        if (!$subscriptionId) {
            return redirect()->route('filament.account.pages.account-subscription')
                ->with('error', 'Subscription ID missing.');
        }

        try {
            $provider = new PayPalClient;
            $provider->getAccessToken();

            $response = $provider->showSubscriptionDetails($subscriptionId);
            
            \Illuminate\Support\Facades\Log::info('PayPal showSubscriptionDetails response', ['response' => $response]);

            if (isset($response['status']) && in_array($response['status'], ['ACTIVE', 'APPROVAL_PENDING'])) {
                
                $customId = json_decode($response['custom_id'] ?? '{}', true);
                
                \Illuminate\Support\Facades\Log::info('PayPal parsed custom_id', ['custom_id' => $customId]);
                
                $plan = SubscriptionPlan::find($customId['plan_id'] ?? null);
                $profile = BillingProfile::find($customId['billing_profile_id'] ?? null);

                if (!$plan || !$profile) {
                    \Illuminate\Support\Facades\Log::error('PayPal local record mapping failed', ['plan' => $plan, 'profile' => $profile]);
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
                
                \Illuminate\Support\Facades\Log::info('Local subscription updated/created', ['subscription_id' => $subscription->id]);

                // Update the user's tier immediately
                if ($request->user()->tier?->value !== $plan->tier->value) {
                    $request->user()->update([
                        'tier' => $plan->tier
                    ]);
                    $request->user()->notify(new \App\Notifications\TierUpgradedNotification($plan->name));
                }
                
                \Illuminate\Support\Facades\Log::info('User tier updated', ['new_tier' => $plan->tier]);

                // Assuming success
                return redirect()->route('filament.account.pages.account-subscription')
                    ->with('success', 'Subscription created successfully!');
            }

            \Illuminate\Support\Facades\Log::warning('Subscription status invalid', ['status' => $response['status'] ?? 'unknown']);

            return redirect()->route('filament.account.pages.account-subscription')
                ->with('error', 'Subscription is not active.');

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('PayPal return exception', ['error' => $e->getMessage()]);
            return redirect()->route('filament.account.pages.account-subscription')
                ->with('error', 'Error verifying subscription: ' . $e->getMessage());
        }
    }
}
