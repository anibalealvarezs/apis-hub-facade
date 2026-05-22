<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SubscriptionPlan;
use App\Models\BillingProfile;
use Illuminate\Support\Facades\Log;

class StripeCheckoutController extends Controller
{
    public function checkout(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:subscription_plans,id',
            'billing_profile_id' => 'required|exists:billing_profiles,id',
        ]);

        $plan = SubscriptionPlan::findOrFail($request->plan_id);
        
        if (empty($plan->stripe_price_id)) {
            return back()->with('error', 'This plan is not configured for Stripe yet.');
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
            // Initiate Stripe Checkout using Cashier
            return $profile->newSubscription('default', $plan->stripe_price_id)
                ->checkout([
                    'success_url' => route('stripe.return', ['plan_id' => $plan->id]) . '&session_id={CHECKOUT_SESSION_ID}',
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

        if (!$sessionId || !$planId) {
            return redirect()->route('filament.account.pages.account-subscription')
                ->with('error', 'Invalid Stripe session.');
        }

        $plan = SubscriptionPlan::find($planId);

        if ($plan) {
            $request->user()->update([
                'tier' => $plan->tier
            ]);
            return redirect()->route('filament.account.pages.account-subscription')
                ->with('success', "You have successfully subscribed to the {$plan->name} plan via Stripe!");
        }

        return redirect()->route('filament.account.pages.account-subscription');
    }
}
