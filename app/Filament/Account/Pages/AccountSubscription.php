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

    protected static ?string $navigationGroup = 'Billing & Payments';

    protected static ?string $title = 'Manage Subscriptions';

    public $plans;
    public $selectedProfileId;

    public function mount()
    {
        $this->plans = SubscriptionPlan::where('is_active', true)->orderBy('price', 'asc')->get();
        
        // Select the default billing profile or the first available one
        $defaultProfile = auth()->user()->billingProfiles()->where('is_default', true)->first();
        if ($defaultProfile) {
            $this->selectedProfileId = $defaultProfile->id;
        } else {
            $this->selectedProfileId = auth()->user()->getAvailableBillingProfiles()->first()?->id;
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
                ->label('Cancel & Downgrade to Free')
                ->color('danger')
                ->icon('heroicon-o-x-circle')
                ->requiresConfirmation()
                ->modalHeading('Confirm Downgrade')
                ->modalDescription(function () {
                    $profile = $this->getSelectedProfileProperty();
                    if (!$profile) {
                        return 'Please select a billing profile first.';
                    }

                    if ($profile->tier === \App\Enums\UserTier::FREE) {
                        return "The selected profile ({$profile->name}) is already on the Free tier.";
                    }
                    
                    if ($profile->tier === \App\Enums\UserTier::ENTERPRISE) {
                        return "Are you sure you want to cancel the Enterprise subscription for {$profile->name}? At the end of the billing cycle, the profile will be SUSPENDED and all associated projects will stop functioning.";
                    }
                    
                    $hasOtherFree = \App\Models\BillingProfile::where('user_id', auth()->id())
                        ->where('id', '!=', $profile->id)
                        ->where('tier', \App\Enums\UserTier::FREE)
                        ->exists();

                    if ($hasOtherFree) {
                        return "Ya tienes otro perfil de facturación gratuito. Si cancelas la suscripción de este perfil ({$profile->name}), al final del ciclo de facturación el perfil será SUSPENDIDO y todos sus proyectos asociados dejarán de funcionar, ya que solo se permite un único perfil de facturación gratuito por cuenta. Para evitar esto, te recomendamos mantener tu suscripción o eliminar tu perfil gratuito existente antes de que finalice el ciclo.";
                    }
                    
                    return "Are you sure you want to cancel the subscription for {$profile->name}? At the end of the billing cycle, the profile will be downgraded to the Free tier and projects exceeding limits will be suspended.";
                })
                ->modalSubmitActionLabel('Yes, Cancel Subscription')
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
                        ->title('Subscription Cancelled')
                        ->body("The subscription for {$profile->name} will not renew and will remain active until the end of the cycle.")
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
