<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BillingProfileResource\Pages\ListBillingProfiles;
use App\Filament\Resources\BillingProfileResource\Pages\ViewBillingProfile;
use App\Models\BillingProfile;
use App\Services\BillingLifecycleService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BillingProfileResource extends Resource
{
    protected static ?string $model = BillingProfile::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?int $navigationSort = 2;

    public static function getNavigationLabel(): string
    {
        return __('Billing Profiles');
    }

    public static function getModelLabel(): string
    {
        return __('Billing Profile');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Billing Profiles');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('SaaS Management');
    }


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Profile Info')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->disabled(),
                        Forms\Components\TextInput::make('reference_name')
                            ->label(__('Reference Name'))
                            ->disabled(),
                        Forms\Components\TextInput::make('tier')
                            ->disabled()
                            ->formatStateUsing(fn ($state): string => $state instanceof \App\Enums\UserTier ? $state->getLabel() : ucfirst($state)),
                        Forms\Components\TextInput::make('status')
                            ->disabled(),
                        Forms\Components\Toggle::make('is_default')
                            ->disabled(),
                    ])->columns(3),

                Forms\Components\Section::make('Owner')
                    ->schema([
                        Forms\Components\Placeholder::make('owner_name')
                            ->label(__('Name'))
                            ->content(fn (BillingProfile $record): ?string => $record->user?->name),
                        Forms\Components\Placeholder::make('owner_email')
                            ->label(__('Email'))
                            ->content(fn (BillingProfile $record): ?string => $record->user?->email),
                    ])->columns(2),

                Forms\Components\Section::make('Billing Cycle')
                    ->schema([
                        Forms\Components\TextInput::make('current_cycle_starts_at')
                            ->label(__('Cycle Start'))
                            ->disabled()
                            ->formatStateUsing(fn ($state): ?string => $state ? \Illuminate\Support\Carbon::parse($state)->format('Y-m-d H:i:s') : null),
                        Forms\Components\TextInput::make('current_cycle_ends_at')
                            ->label(__('Cycle End'))
                            ->disabled()
                            ->formatStateUsing(fn ($state): ?string => $state ? \Illuminate\Support\Carbon::parse($state)->format('Y-m-d H:i:s') : null),
                    ])->columns(2),

                Forms\Components\Section::make('Payment Method')
                    ->schema([
                        Forms\Components\TextInput::make('payment_method')
                            ->label(__('Card'))
                            ->disabled()
                            ->formatStateUsing(function ($state, BillingProfile $record): string {
                                if ($record->pm_type) {
                                    return ucfirst($record->pm_type) . ' (****' . ($record->pm_last_four ?? '') . ')';
                                }
                                return $record->paypal_email ? 'PayPal (' . $record->paypal_email . ')' : '—';
                            }),
                    ])->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference_name')
                    ->label(__('Reference Name'))
                    ->formatStateUsing(fn (?string $state, \App\Models\BillingProfile $record) => $state . ' ( Legal Name: ' . $record->name . ' )')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label(__('Owner'))
                    ->description(fn (BillingProfile $record): string => $record->user->email ?? '')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.email')
                    ->label(__('Owner Email'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('tier')
                    ->badge()
                    ->color(fn (\App\Enums\UserTier $state): string => match ($state) {
                        \App\Enums\UserTier::FREE => 'gray',
                        \App\Enums\UserTier::PRO => 'success',
                        \App\Enums\UserTier::ULTRA => 'warning',
                        \App\Enums\UserTier::FOUNDER => 'info',
                        \App\Enums\UserTier::ENTERPRISE => 'danger',
                        \App\Enums\UserTier::SUSPENDED => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'past_due' => 'warning',
                        'suspended' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('projects_count')
                    ->label(__('Projects'))
                    ->counts('projects')
                    ->sortable(),
                Tables\Columns\TextColumn::make('quota')
                    ->label(__('Usage'))
                    ->html()
                    ->state(function (BillingProfile $record): string {
                        $service = app(BillingLifecycleService::class);
                        $maxProjects = $service->getMaxProjectsForTier($record->tier);
                        $maxAccounts = $service->getMaxAccountsForTier($record->tier);
                        $projectCount = $record->projects()->count();

                        $assetCount = 0;
                        foreach ($record->projects as $project) {
                            $syncConfig = $project->sync_config ?? [];
                            if (!is_array($syncConfig)) continue;

                            $assetKeys = ['sites', 'ad_accounts', 'pages', 'locations', 'profiles', 'accounts', 'shops', 'properties'];
                            foreach ($syncConfig as $channelKey => $channelConfig) {
                                if (!is_array($channelConfig) || empty($channelConfig['enabled'])) continue;

                                foreach ($assetKeys as $assetKey) {
                                    $assets = $channelConfig[$assetKey] ?? ($channelConfig['assets'][$assetKey] ?? null);
                                    if (!is_array($assets)) continue;

                                    foreach ($assets as $asset) {
                                        if (is_array($asset) && !empty($asset['enabled']) && empty($asset['lost_access'])) {
                                            $assetCount++;
                                        }
                                    }
                                }
                            }
                        }

                        $pct = fn ($used, $max) => $max > 0 ? round(($used / $max) * 100) : 0;
                        $color = fn ($p) => $p >= 90 ? '#ef4444' : ($p >= 70 ? '#f59e0b' : '#22c55e');

                        $pPct = $pct($projectCount, $maxProjects);
                        $aPct = $pct($assetCount, $maxAccounts);

                        return '
                            <div class="text-xs leading-relaxed whitespace-nowrap">
                                <div class="flex items-center gap-1.5 mb-0.5">
                                    <span class="font-semibold w-16">Projects</span>
                                    <span class="text-gray-500 dark:text-gray-400">' . $projectCount . '/' . $maxProjects . '</span>
                                </div>
                                <div class="w-full h-1.5 rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden">
                                    <div style="width:' . $pPct . '%;height:100%;background:' . $color($pPct) . ';border-radius:9999px;transition:width .3s"></div>
                                </div>
                                <div class="flex items-center gap-1.5 mt-1.5 mb-0.5">
                                    <span class="font-semibold w-16">Accounts</span>
                                    <span class="text-gray-500 dark:text-gray-400">' . $assetCount . '/' . $maxAccounts . '</span>
                                </div>
                                <div class="w-full h-1.5 rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden">
                                    <div style="width:' . $aPct . '%;height:100%;background:' . $color($aPct) . ';border-radius:9999px;transition:width .3s"></div>
                                </div>
                            </div>';
                    }),
                Tables\Columns\TextColumn::make('current_cycle_starts_at')
                    ->label(__('Cycle Start'))
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('current_cycle_ends_at')
                    ->label(__('Cycle End'))
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('pm_type')
                    ->label(__('Payment'))
                    ->formatStateUsing(fn (?string $state, BillingProfile $record): string => $record->pm_type
                        ? ucfirst($record->pm_type) . ' (****' . ($record->pm_last_four ?? '') . ')'
                        : ($record->paypal_email ? 'PayPal' : '—'))
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('tier')
                    ->options(fn () => \App\Enums\UserTier::class),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'past_due' => 'Past Due',
                        'suspended' => 'Suspended',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('edit_tier')
                    ->label(__('Change Tier'))
                    ->icon('heroicon-o-pencil')
                    ->color('warning')
                    ->modalHeading(__('Change Tier & Sync with Provider'))
                    ->modalDescription(__('Changing the tier here will attempt to automatically sync with Stripe/PayPal if an active subscription exists. If no subscription exists, it will only update the local database.'))
                    ->form([
                        Forms\Components\Select::make('tier')
                            ->options(\App\Enums\UserTier::class)
                            ->required()
                            ->default(fn (BillingProfile $record) => $record->tier instanceof \App\Enums\UserTier ? $record->tier->value : $record->tier)
                            ->live(),
                        Forms\Components\Select::make('billing_cycle')
                            ->label(__('Billing Cycle (If Syncing)'))
                            ->options(['monthly' => __('Monthly'), 'annual' => __('Annual')])
                            ->default('monthly')
                            ->visible(function (\Filament\Forms\Get $get) {
                                $tier = $get('tier');
                                $val = $tier instanceof \App\Enums\UserTier ? $tier->value : $tier;
                                return $val !== \App\Enums\UserTier::FREE->value && $val !== \App\Enums\UserTier::SUSPENDED->value;
                            }),
                        Forms\Components\DatePicker::make('next_billing_date')
                            ->label(__('Next Billing Date / Grace Period End'))
                            ->helperText(__('If set, will push the next Stripe invoice to this date. (PayPal date sync is limited and may require manual merchant dashboard adjustment).'))
                            ->minDate(now()->addDay())
                            ->nullable(),
                        Forms\Components\Checkbox::make('cancel_subscription')
                            ->label(__('Cancel Active Provider Subscription'))
                            ->default(true)
                            ->helperText(__('If checked, the current Stripe/PayPal subscription will be permanently canceled (user loses auto-renew).')),
                        Forms\Components\TextInput::make('confirmation')
                            ->label(__('Type "CONFIRM" to proceed'))
                            ->required()
                            ->rule('in:CONFIRM,confirm,Confirm,CONFIRMAR,confirmar,Confirmar')
                            ->helperText(__('You must explicitly type confirm to apply this change.')),
                        Forms\Components\Select::make('status')
                            ->label(__('Status'))
                            ->options([
                                'active' => __('Active'),
                                'past_due' => __('Past Due'),
                                'suspended' => __('Suspended'),
                            ])
                            ->default(fn (BillingProfile $record) => $record->status)
                            ->required(),
                    ])
                    ->action(function (BillingProfile $record, array $data) {
                        $newTier = \App\Enums\UserTier::tryFrom($data['tier']);
                        $newStatus = $data['status'] ?? $record->status;
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
                                    \Filament\Notifications\Notification::make()->danger()->title(__('Stripe sync failed: :error', ['error' => $e->getMessage()]))->send();
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
                                    \Filament\Notifications\Notification::make()->danger()->title(__('PayPal sync failed: :error', ['error' => $e->getMessage()]))->send();
                                }
                            }
                        }

                        // Local update
                        $record->tier = $newTier;
                        $record->status = $newStatus;
                        if (!empty($data['next_billing_date'])) {
                            $record->current_cycle_ends_at = \Carbon\Carbon::parse($data['next_billing_date']);
                        }
                        $record->save();
                        
                        $msg = __('Tier and status updated locally.');
                        if ($wasCanceled) {
                            $msg = __('Tier updated and previous provider subscription was permanently canceled.');
                        } elseif ($syncSuccess) {
                            $msg = __('Tier updated and synced with payment provider.');
                        }

                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title($msg)
                            ->send();
                    }),
                Tables\Actions\Action::make('change_status')
                    ->label(__('Change Status'))
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->form([
                        Forms\Components\Select::make('status')
                            ->label(__('Status'))
                            ->options([
                                'active' => __('Active'),
                                'past_due' => __('Past Due'),
                                'suspended' => __('Suspended'),
                            ])
                            ->default(fn (BillingProfile $record) => $record->status)
                            ->required(),
                    ])
                    ->action(function (BillingProfile $record, array $data) {
                        $record->update(['status' => $data['status']]);
                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title(__('Status updated successfully.'))
                            ->send();
                    }),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\Resources\BillingProfileResource\RelationManagers\ProjectsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBillingProfiles::route('/'),
            'view' => ViewBillingProfile::route('/{record}'),
        ];
    }
}
