<?php

namespace App\Filament\Resources\UserResource\Widgets;

use App\Models\BillingProfile;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class UserBillingProfilesTable extends BaseWidget
{
    public ?User $record = null;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                BillingProfile::query()
                    ->where('user_id', $this->record->id)
                    ->orWhereHas('sharedWithUsers', function (Builder $query) {
                        $query->where('users.id', $this->record->id);
                    })
            )
            ->heading(__('Accessed Billing Profiles'))
            ->description(__('Billing profiles this user owns or has been invited to collaborate on.'))
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('Profile Name'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->label(__('Relationship'))
                    ->getStateUsing(function (BillingProfile $record): string {
                        return $record->user_id === $this->record->id ? __('Owner') : __('Shared');
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        __('Owner'), 'Owner' => 'success',
                        __('Shared'), 'Shared' => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('owner.name')
                    ->label(__('Owner'))
                    ->url(fn (BillingProfile $record) => \App\Filament\Resources\UserResource::getUrl('edit', ['record' => $record->user_id]))
                    ->searchable(),

                Tables\Columns\TextColumn::make('tier')
                    ->badge()
                    ->color(fn (\App\Enums\UserTier $state): string => match ($state) {
                        \App\Enums\UserTier::FREE => 'gray',
                        \App\Enums\UserTier::PRO => 'info',
                        \App\Enums\UserTier::ULTRA => 'success',
                        \App\Enums\UserTier::FOUNDER => 'warning',
                        \App\Enums\UserTier::ENTERPRISE => 'success',
                        \App\Enums\UserTier::SUSPENDED => 'danger',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('edit_tier')
                    ->label(__('Change Tier'))
                    ->icon('heroicon-o-pencil')
                    ->color('warning')
                    ->modalHeading(__('Change Tier & Sync with Provider'))
                    ->modalDescription(__('Changing the tier here will attempt to automatically sync with Stripe/PayPal if an active subscription exists. If no subscription exists, it will only update the local database.'))
                    ->form([
                        Select::make('tier')
                            ->options(\App\Enums\UserTier::class)
                            ->required()
                            ->default(fn (BillingProfile $record) => $record->tier instanceof \App\Enums\UserTier ? $record->tier->value : $record->tier)
                            ->live(),
                        Select::make('billing_cycle')
                            ->label(__('Billing Cycle (If Syncing)'))
                            ->options(['monthly' => __('Monthly'), 'annual' => __('Annual')])
                            ->default('monthly')
                            ->visible(function (\Filament\Forms\Get $get) {
                                $tier = $get('tier');
                                $val = $tier instanceof \App\Enums\UserTier ? $tier->value : $tier;
                                return $val !== \App\Enums\UserTier::FREE->value && $val !== \App\Enums\UserTier::SUSPENDED->value;
                            }),
                        \Filament\Forms\Components\DatePicker::make('next_billing_date')
                            ->label(__('Next Billing Date / Grace Period End'))
                            ->helperText(__('If set, will push the next Stripe invoice to this date. (PayPal date sync is limited and may require manual merchant dashboard adjustment).'))
                            ->minDate(now()->addDay())
                            ->nullable(),
                        \Filament\Forms\Components\Checkbox::make('cancel_subscription')
                            ->label(__('Cancel Active Provider Subscription'))
                            ->default(true)
                            ->helperText(__('If checked, the current Stripe/PayPal subscription will be permanently canceled (user loses auto-renew).')),
                        TextInput::make('confirmation')
                            ->label(__('Type "CONFIRM" to proceed'))
                            ->required()
                            ->rule('in:CONFIRM,confirm,Confirm')
                            ->helperText(__('You must explicitly type confirm to apply this change.')),
                    ])
                    ->action(function (BillingProfile $record, array $data) {
                        $newTier = \App\Enums\UserTier::tryFrom($data['tier']);
                        $cycle = $data['billing_cycle'] ?? 'monthly';
                        
                        $sub = $record->subscriptions()->active()->first();
                        $plan = \App\Models\SubscriptionPlan::where('tier', $newTier)->first();
                        
                        $syncSuccess = false;
                        $wasCanceled = false;

                        if (!empty($data['cancel_subscription']) && $sub) {
                            if ($sub->stripe_id) {
                                try {
                                    $sub->cancel();
                                    $wasCanceled = true;
                                } catch (\Exception $e) {
                                    \Illuminate\Support\Facades\Log::error('Stripe cancel failed', ['error' => $e->getMessage()]);
                                }
                            } elseif ($sub->paypal_subscription_id) {
                                try {
                                    $provider = new \Srmklive\PayPal\Services\PayPal;
                                    $provider->getAccessToken();
                                    $provider->cancelSubscription($sub->paypal_subscription_id, 'Manual Admin Override');
                                    $sub->update(['paypal_status' => 'CANCELLED']);
                                    $wasCanceled = true;
                                } catch (\Exception $e) {
                                    \Illuminate\Support\Facades\Log::error('PayPal cancel failed', ['error' => $e->getMessage()]);
                                }
                            }
                        } elseif ($sub && $plan) {
                            if ($sub->stripe_id) {
                                try {
                                    $stripePlanId = $cycle === 'annual' ? $plan->stripe_annual_price_id : $plan->stripe_price_id;
                                    if ($stripePlanId) {
                                        $sub->swap($stripePlanId);
                                        $syncSuccess = true;
                                    }
                                    if (!empty($data['next_billing_date'])) {
                                        $sub->trialUntil(\Carbon\Carbon::parse($data['next_billing_date']));
                                    }
                                } catch (\Exception $e) {
                                    \Illuminate\Support\Facades\Log::error('Stripe admin sync failed', ['error' => $e->getMessage()]);
                                    Notification::make()->danger()->title(__('Stripe sync failed: :error', ['error' => $e->getMessage()]))->send();
                                }
                            } elseif ($sub->paypal_subscription_id) {
                                try {
                                    $paypalPlanId = $cycle === 'annual' ? $plan->paypal_annual_plan_id : $plan->paypal_plan_id;
                                    if ($paypalPlanId) {
                                        $provider = new \Srmklive\PayPal\Services\PayPal;
                                        $provider->getAccessToken();
                                        $provider->reviseSubscription($sub->paypal_subscription_id, [
                                            'plan_id' => $paypalPlanId
                                        ]);
                                        $syncSuccess = true;
                                        // Note: PayPal date sync is extremely limited via API, requires modifying subscription setup.
                                    }
                                } catch (\Exception $e) {
                                    \Illuminate\Support\Facades\Log::error('PayPal admin sync failed', ['error' => $e->getMessage()]);
                                    Notification::make()->danger()->title(__('PayPal sync failed: :error', ['error' => $e->getMessage()]))->send();
                                }
                            }
                        }

                        // Local update
                        $record->tier = $newTier;
                        if (!empty($data['next_billing_date'])) {
                            $record->current_cycle_ends_at = \Carbon\Carbon::parse($data['next_billing_date']);
                        }
                        $record->save();
                        
                        $msg = __('Tier updated locally.');
                        if ($wasCanceled) {
                            $msg = __('Tier updated and previous provider subscription was permanently canceled.');
                        } elseif ($syncSuccess) {
                            $msg = __('Tier updated and synced with payment provider.');
                        }

                        Notification::make()
                            ->success()
                            ->title($msg)
                            ->send();
                    }),
            ]);
    }
}
