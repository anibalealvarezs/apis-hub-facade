<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SubscriptionPlan;
use App\Models\BillingProfile;
use App\Models\BillingLog;
use Illuminate\Support\Facades\Log;

class StripeCheckoutController extends Controller
{
    public function checkout(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:subscription_plans,id',
            'billing_profile_id' => 'required|exists:billing_profiles,id',
            'billing_cycle' => 'required|in:monthly,annual',
            'coupon_code' => 'nullable|string',
        ]);

        $plan = SubscriptionPlan::findOrFail($request->plan_id);
        
        $priceId = $request->billing_cycle === 'annual' ? $plan->stripe_annual_price_id : $plan->stripe_price_id;

        if (empty($priceId)) {
            return back()->with('error', "This plan is not configured for Stripe {$request->billing_cycle} billing yet.");
        }

        $profile = BillingProfile::where('id', $request->billing_profile_id)
            ->where(function($query) use ($request) {
                $query->where('user_id', $request->user()->id)
                      ->orWhereHas('sharedWithUsers', function($q) use ($request) {
                          $q->where('users.id', $request->user()->id)
                            ->where('billing_profile_user.role', 'admin');
                      });
            })->firstOrFail();

        try {
            $coupon = null;
            if ($request->filled('coupon_code')) {
                $coupon = \App\Models\Coupon::where('code', strtoupper($request->coupon_code))->where('is_active', true)->first();
                if (!$coupon) {
                    return back()->with('error', 'Invalid or expired promo code.');
                }
            }

            // Check if user already has an active Stripe subscription on this profile
            if ($profile->subscribed('default')) {
                // Prorate and swap immediately
                $subscription = $profile->subscription('default');
                
                if ($coupon && $coupon->stripe_promotion_code_id) {
                    return back()->with('error', 'Promo codes can only be applied to new subscriptions.');
                }
                
                $subscription->swapAndInvoice($priceId);
                
                // Update tier locally
                if ($profile->tier?->value !== $plan->tier->value) {
                    $profile->update(['tier' => $plan->tier]);
                    
                    BillingLog::create([
                        'user_id' => $request->user()->id,
                        'billing_profile_id' => $profile->id,
                        'event_type' => 'subscription_created',
                        'gateway' => 'stripe',
                        'description' => "Tier manually upgraded to {$plan->tier} via UI swap.",
                        'metadata' => [
                            'price_id' => $priceId,
                            'plan_id' => $plan->id
                        ]
                    ]);
                    
                    $profile->user->notify(new \App\Notifications\TierUpgradedNotification($plan->name));
                }
                
                return redirect()->route('filament.account.pages.account-subscription')
                    ->with('success', "Your subscription was successfully upgraded to the {$plan->name} ({$request->billing_cycle}) plan via Stripe prorated swap!");
            }

            // Initiate Stripe Checkout using Cashier for new subscription
            $checkout = $profile->newSubscription('default', $priceId);
            
            if ($coupon) {
                if ($coupon->trial_days) {
                    $checkout->trialDays($coupon->trial_days);
                } elseif ($coupon->stripe_promotion_code_id) {
                    $checkout->withPromotionCode($coupon->stripe_promotion_code_id);
                }
            }
            
            return $checkout->checkout([
                    'success_url' => route('stripe.return', ['plan_id' => $plan->id, 'billing_profile_id' => $profile->id]) . '&session_id={CHECKOUT_SESSION_ID}',
                    'cancel_url' => route('filament.account.pages.account-subscription'),
                ]);
        } catch (\Exception $e) {
            Log::error('Stripe Checkout Exception', ['message' => $e->getMessage()]);
            return back()->with('error', 'Failed to initiate Stripe Checkout: ' . $e->getMessage());
        }
    }

    public function return(Request $request)
    {
        $sessionId = $request->get('session_id');
        $planId = $request->get('plan_id');
        $profileId = $request->get('billing_profile_id');

        if (!$sessionId || !$planId || !$profileId) {
            return redirect()->route('filament.account.pages.account-subscription')
                ->with('error', 'Invalid Stripe session.');
        }

        $plan = SubscriptionPlan::find($planId);
        $profile = BillingProfile::find($profileId);

        if ($plan && $profile) {
            if ($profile->tier?->value !== $plan->tier->value) {
                $profile->update([
                    'tier' => $plan->tier
                ]);
                $profile->user->notify(new \App\Notifications\TierUpgradedNotification($plan->name));
            }
            return redirect()->route('filament.account.pages.account-subscription')
                ->with('success', "You have successfully subscribed to the {$plan->name} plan via Stripe!");
        }

        return redirect()->route('filament.account.pages.account-subscription');
    }
}
