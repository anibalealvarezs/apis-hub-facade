<?php

namespace App\Filament\Account\Pages;

use Filament\Pages\Page;
use App\Models\SubscriptionPlan;
use Illuminate\Support\Facades\Auth;

class AccountSubscription extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-star';

    protected static string $view = 'filament.account.pages.account-subscription';

    protected static ?string $navigationGroup = 'Billing & Payments';

    protected static ?string $title = 'My Subscription';

    public $plans;

    public function mount()
    {
        $this->plans = SubscriptionPlan::where('is_active', true)->orderBy('price', 'asc')->get();
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
                    $user = auth()->user();
                    if ($user->tier === \App\Enums\UserTier::FREE) {
                        return 'You are already on the Free tier.';
                    }
                    
                    if ($user->tier === \App\Enums\UserTier::ENTERPRISE) {
                        return 'Are you sure you want to cancel your Enterprise subscription? Your account will remain active until the end of your billing cycle. At that time, your account will be SUSPENDED and ALL your projects will stop functioning.';
                    }
                    
                    return 'Are you sure you want to cancel your paid subscription? Your current tier will remain active until the end of your billing cycle. At that time, you will be downgraded to the Free plan and any projects exceeding the Free limits will be suspended.';
                })
                ->modalSubmitActionLabel('Yes, Cancel Subscription')
                ->action(function () {
                    $user = auth()->user();
                    
                    // 1. Cancel Stripe subscriptions (at end of period)
                    $user->subscriptions()->where('stripe_status', 'active')->each(function ($sub) {
                        $sub->cancel();
                    });
                    
                    // 2. Cancel PayPal subscriptions
                    $paypalSubs = $user->subscriptions()->where('paypal_status', 'ACTIVE')->get();
                    if ($paypalSubs->isNotEmpty()) {
                        $provider = new \Srmklive\PayPal\Services\PayPal;
                        $provider->getAccessToken();
                        foreach ($paypalSubs as $sub) {
                            if ($sub->paypal_subscription_id) {
                                try {
                                    $provider->cancelSubscription($sub->paypal_subscription_id, 'User requested cancellation');
                                    // Local record update will be handled by webhook, but we mark it as pending cancellation
                                    $sub->update(['paypal_status' => 'CANCEL_PENDING']);
                                } catch (\Exception $e) {
                                    \Illuminate\Support\Facades\Log::error('Downgrade: Failed to cancel PayPal Sub', ['msg' => $e->getMessage()]);
                                }
                            }
                        }
                    }

                    // We NO LONGER enforce local downgrade and suspensions here.
                    // This is the Netflix model: they keep the tier until ends_at.
                    
                    \Filament\Notifications\Notification::make()
                        ->title('Subscription Cancelled')
                        ->body('Your subscription will not renew. Your current plan remains active until the end of the billing cycle.')
                        ->success()
                        ->send();
                        
                    return redirect()->route('filament.account.pages.account-subscription');
                })
                ->visible(fn () => auth()->user()->tier !== \App\Enums\UserTier::FREE)
        ];
    }
}
