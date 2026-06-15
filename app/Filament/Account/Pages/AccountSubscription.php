<?php

namespace App\Filament\Account\Pages;

use Filament\Pages\Page;
use App\Models\SubscriptionPlan;
use App\Models\BillingProfile;
use Illuminate\Support\Facades\Auth;

class AccountSubscription extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-star';

    protected static string $view = 'filament.account.pages.account-subscription';
    public static function getNavigationGroup(): ?string
    {
        return __('Billing & Payments');
    }


    

    public function getTitle(): string
    {
        return __('Manage Subscriptions');
    }
    
    public static function getNavigationLabel(): string
    {
        return __('Manage Subscriptions');
    }

    public $monthlyPlans;
    public $annualPlans;
    
    #[\Livewire\Attributes\Url(as: 'profile', history: true)]
    public $selectedProfileId;

    public function mount()
    {
        $this->monthlyPlans = SubscriptionPlan::where('is_active', true)
            ->whereIn('billing_cycle', ['monthly', 'both'])
            ->orderBy('price', 'asc')
            ->get();

        $this->annualPlans = SubscriptionPlan::where('is_active', true)
            ->whereIn('billing_cycle', ['yearly', 'both'])
            ->orderBy('price', 'asc')
            ->get();
        
        $availableProfiles = auth()->user()->getAvailableBillingProfiles();

        if ($this->selectedProfileId && $availableProfiles->contains($this->selectedProfileId)) {
            // Valid profile provided via URL
        } else {
            // Select the default billing profile or the first available one
            $defaultProfile = auth()->user()->billingProfiles()->where('is_default', true)->first();
            if ($defaultProfile) {
                $this->selectedProfileId = $defaultProfile->id;
            } else {
                $this->selectedProfileId = $availableProfiles->first()?->id;
            }
        }
    }

    public function getSelectedProfileProperty(): ?BillingProfile
    {
        if (!$this->selectedProfileId) {
            return null;
        }

        // Security check: ensure user has access to this billing profile
        $profile = BillingProfile::find($this->selectedProfileId);
        if ($profile && auth()->user()->getAvailableBillingProfiles()->contains($profile->id)) {
            return $profile;
        }

        return null;
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('downgradeToFree')
                ->label(__('Cancel & Downgrade to Free'))
                ->color('danger')
                ->icon('heroicon-o-x-circle')
                ->requiresConfirmation()
                ->modalHeading(__('Confirm Downgrade'))
                ->modalDescription(function () {
                    $profile = $this->getSelectedProfileProperty();
                    if (!$profile) {
                        return __('Please select a billing profile first.');
                    }

                    if ($profile->tier === \App\Enums\UserTier::FREE) {
                        return __('The selected profile (:name) is already on the Free tier.', ['name' => $profile->name]);
                    }
                    
                    if ($profile->tier === \App\Enums\UserTier::ENTERPRISE) {
                        return __('Are you sure you want to cancel the Enterprise subscription for :name? At the end of the billing cycle, the profile will be SUSPENDED and all associated projects will stop functioning.', ['name' => $profile->name]);
                    }
                    
                    $hasOtherFree = \App\Models\BillingProfile::where('user_id', auth()->id())
                        ->where('id', '!=', $profile->id)
                        ->where('tier', \App\Enums\UserTier::FREE)
                        ->exists();

                    if ($hasOtherFree) {
                        return __('You already have another free billing profile. If you cancel the subscription for this profile (:name), at the end of the billing cycle the profile will be SUSPENDED and all its associated projects will stop working, since only one free billing profile is allowed per account. To avoid this, we recommend maintaining your subscription or deleting your existing free profile before the cycle ends.', ['name' => $profile->name]);
                    }
                    
                    return __('Are you sure you want to cancel the subscription for :name? At the end of the billing cycle, the profile will be downgraded to the Free tier and projects exceeding limits will be suspended.', ['name' => $profile->name]);
                })
                ->modalSubmitActionLabel(__('Yes, Cancel Subscription'))
                ->action(function () {
                    $profile = $this->getSelectedProfileProperty();
                    if (!$profile) {
                        return;
                    }
                    
                    // 1. Cancel Stripe subscriptions (at end of period)
                    $profile->subscriptions()->where('stripe_status', 'active')->each(function ($sub) {
                        $sub->cancel();
                    });
                    
                    // 2. Cancel PayPal subscriptions
                    $paypalSubs = $profile->subscriptions()->where('paypal_status', 'ACTIVE')->get();
                    if ($paypalSubs->isNotEmpty()) {
                        $provider = new \Srmklive\PayPal\Services\PayPal;
                        $provider->getAccessToken();
                        foreach ($paypalSubs as $sub) {
                            if ($sub->paypal_subscription_id) {
                                try {
                                    $provider->cancelSubscription($sub->paypal_subscription_id, 'User requested cancellation');
                                    $sub->update(['paypal_status' => 'CANCEL_PENDING']);
                                } catch (\Exception $e) {
                                    \Illuminate\Support\Facades\Log::error('Downgrade: Failed to cancel PayPal Sub', ['msg' => $e->getMessage()]);
                                }
                            }
                        }
                    }
                    
                    \Filament\Notifications\Notification::make()
                        ->title(__('Subscription Cancelled'))
                        ->body(__('The subscription for :name will not renew and will remain active until the end of the cycle.', ['name' => $profile->name]))
                        ->success()
                        ->send();
                        
                    return redirect()->route('filament.account.pages.account-subscription');
                })
                ->visible(function () {
                    $profile = $this->getSelectedProfileProperty();
                    return $profile && $profile->tier !== \App\Enums\UserTier::FREE;
                })
        ];
    }
}
