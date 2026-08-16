<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProjectResource\Pages\CreateProject as CreatePage;
use App\Filament\Resources\ProjectResource\Pages\EditProject as EditPage;
use App\Filament\Resources\ProjectResource\Pages\ListProjects as ListPage;
use App\Models\BillingProfile;
use App\Models\Project;
use App\Models\User;
use App\Services\DeployerService;
use App\Services\RemoteEngineService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static ?string $navigationIcon = 'heroicon-o-server-stack';

    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return __('Projects');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Infrastructure');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Projects');
    }

    public static function getModelLabel(): string
    {
        return __('Project');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Project Identity')
                    ->description(__('Primary details and active status.'))
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('subdomain')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->rule(function () {
                                return function (string $attribute, $value, \Closure $fail) {
                                    if (config('app.env') === 'production' && str_ends_with($value, '-dev')) {
                                        $fail('No se permiten subdominios terminados en "-dev" en producción.');
                                    }
                                    
                                    $reservedFile = database_path('data/reserved_subdomains.json');
                                    $reserved = file_exists($reservedFile) ? json_decode(file_get_contents($reservedFile), true) : [];
                                    $cleanValue = strtolower(str_replace('-dev', '', $value));
                                    
                                    if (in_array($cleanValue, $reserved)) {
                                        $fail('The subdomain "' . $cleanValue . '" is reserved for internal infrastructure and cannot be used.');
                                    }
                                };
                            })
                            ->live(onBlur: true)
                            ->disabled(fn (?Project $record) => $record !== null)
                            ->suffix(function () {
                                $domain = config('app.network_domain') ?: 'apis-hub.cloud';
                                return (config('app.env') !== 'production') ? "-dev.{$domain}" : ".{$domain}";
                            })
                            ->dehydrateStateUsing(function ($state) {
                                if (config('app.env') !== 'production' && !str_ends_with($state, '-dev')) {
                                    return $state . '-dev';
                                }
                                return $state;
                            })
                            ->afterStateUpdated(function ($state, Forms\Set $set, $get) {
                                if (config('app.env') !== 'production' && !str_ends_with($state, '-dev')) {
                                    $state .= '-dev';
                                }
                                if (empty($get('db_name'))) {
                                    $set('db_name', 'apis_hub_' . str_replace('-', '_', $state));
                                }
                                if (empty($get('db_user'))) {
                                    $set('db_user', 'user_' . str_replace('-', '_', $state));
                                }
                                if (empty($get('db_password'))) {
                                    $set('db_password', \Illuminate\Support\Str::random(16));
                                }
                            })
                            ->helperText(function () {
                                $domain = config('app.network_domain') ?: 'apis-hub.cloud';
                                $msg = 'Assign a subdomain (e.g. "client1") for the instance. Checked against DB. Full URL will be: subdomain.' . $domain;
                                if (config('app.env') !== 'production') {
                                    $msg .= ' (Non-production Environment: "-dev" will be automatically appended to prevent SSL routing issues).';
                                }
                                return $msg;
                            }),
                        Forms\Components\Toggle::make('is_active')
                            ->required()
                            ->default(true),
                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'name')
                            ->default(fn () => filament()->auth()->id())
                            ->searchable()
                            ->preload()
                            ->label(__('Account Holder'))
                            ->helperText(__('The user who owns this instance.')),
                    ])->columns(4),

                Forms\Components\Section::make('Repository & Deployment')
                    ->description(__('Where the source code lives and which server hosts the application.'))
                    ->schema([
                        Forms\Components\TextInput::make('git_repo')
                            ->required()
                            ->default('anibalealvarezs/apis-hub'),
                        Forms\Components\TextInput::make('git_branch')
                            ->required()
                            ->default('main'),
                        Forms\Components\Select::make('server_id')
                            ->relationship('server', 'name')
                            ->default(1)
                            ->disabled(fn (?Project $record) => $record !== null)
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('apis_hub_release_id')
                            ->relationship('apisHubRelease', 'version_tag')
                            ->label(__('APIs Hub Release'))
                            ->searchable()
                            ->preload()
                            ->placeholder(__('Auto (use active release)'))
                            ->helperText(__('Pin a specific release version. If empty, the active release is used as fallback.')),
                    ])->columns(4),

                Forms\Components\Section::make('Database Configuration')
                    ->description(__('Isolated DB settings for this project instance.'))
                    ->schema([
                        Forms\Components\TextInput::make('db_name')
                            ->placeholder(__('Auto-generated if empty'))
                            ->disabled(fn (?Project $record) => $record !== null)
                            ->maxLength(255),
                        Forms\Components\TextInput::make('db_user')
                            ->placeholder(__('Auto-generated if empty'))
                            ->disabled(fn (?Project $record) => $record !== null)
                            ->maxLength(255),
                        Forms\Components\TextInput::make('db_password')
                            ->password()
                            ->revealable()
                            ->disabled(fn (?Project $record) => $record !== null)
                            ->dehydrated(fn ($state) => filled($state))
                            ->placeholder(__('Auto-generated if empty')),
                    ])->columns(3),



                Forms\Components\Section::make('API Credentials (Encrypted)')
                    ->description(__('These secrets are injected into the project instance\'s .env file.'))
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                 Forms\Components\Group::make([
                                    Forms\Components\Placeholder::make('FB')->label(__('Facebook API')),
                                    Forms\Components\TextInput::make('facebook_user_token')
                                        ->password()
                                        ->revealable()
                                        ->dehydrated(fn ($state) => filled($state)),
                                ]),
                                Forms\Components\Group::make([
                                    Forms\Components\Placeholder::make('GSC')->label(__('Google Search Console (GSC)')),
                                    Forms\Components\TextInput::make('google_refresh_token')
                                        ->password()
                                        ->revealable()
                                        ->dehydrated(fn ($state) => filled($state)),
                                ]),
                                Forms\Components\Group::make([
                                    Forms\Components\Placeholder::make('Pub')->label(__('Public Access')),
                                    Forms\Components\TextInput::make('public_api_key')
                                        ->label(__('Public API Key'))
                                        ->password()
                                        ->revealable()
                                        ->disabled()
                                        ->helperText(__('Used by external consumers.')),
                                ]),
                            ]),
                    ]),

                Forms\Components\Section::make('Associated Billing Profile')
                    ->description(__('Current billing profile linked to this project.'))
                    ->schema([
                        Forms\Components\Placeholder::make('billing_profile_name')
                            ->label(__('Profile Name'))
                            ->content(fn (?Project $record) => $record?->billingProfile?->reference_name ?? 'None'),
                        Forms\Components\Placeholder::make('billing_tier')
                            ->label(__('Tier'))
                            ->content(fn (?Project $record) => $record?->billingProfile?->tier instanceof \App\Enums\UserTier ? $record->billingProfile->tier->value : ($record?->billingProfile?->tier ?? 'None')),
                        Forms\Components\Placeholder::make('billing_status')
                            ->label(__('Status'))
                            ->content(fn (?Project $record) => $record?->billingProfile?->status ?? 'None'),
                    ])
                    ->columns(3)
                    ->headerActions([
                        Forms\Components\Actions\Action::make('manage_billing_profile')
                            ->label(__('Manage Tier & Status'))
                            ->icon('heroicon-o-pencil')
                            ->color('warning')
                            ->hidden(fn (?Project $record) => !$record || !$record->billing_profile_id)
                            ->form([
                                Forms\Components\Select::make('tier')
                                    ->options(\App\Enums\UserTier::class)
                                    ->required()
                                    ->default(fn (?Project $record) => $record?->billingProfile?->tier instanceof \App\Enums\UserTier ? $record->billingProfile->tier->value : $record?->billingProfile?->tier)
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
                                    ->default(fn (?Project $record) => $record?->billingProfile?->status)
                                    ->required(),
                            ])
                            ->action(function (Project $record, array $data, Forms\Set $set) {
                                $bp = $record->billingProfile;
                                if (!$bp) return;

                                $newTier = \App\Enums\UserTier::tryFrom($data['tier']);
                                $newStatus = $data['status'] ?? $bp->status;
                                $cycle = $data['billing_cycle'] ?? 'monthly';
                                
                                $sub = $bp->subscriptions()->active()->first();
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
                                            }
                                        } catch (\Exception $e) {
                                            \Illuminate\Support\Facades\Log::error('PayPal admin sync failed', ['error' => $e->getMessage()]);
                                            \Filament\Notifications\Notification::make()->danger()->title(__('PayPal sync failed: :error', ['error' => $e->getMessage()]))->send();
                                        }
                                    }
                                }

                                // Local update
                                $bp->tier = $newTier;
                                $bp->status = $newStatus;
                                if (!empty($data['next_billing_date'])) {
                                    $bp->current_cycle_ends_at = \Carbon\Carbon::parse($data['next_billing_date']);
                                }
                                $bp->save();
                                
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
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label(__('Owner'))
                    ->description(fn (Project $record): string => $record->user->email ?? '')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.email')
                    ->label(__('Owner Email'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('subdomain')
                    ->badge()
                    ->color('gray')
                    ->sortable(),
                Tables\Columns\TextColumn::make('health_status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'online' => 'success',
                        'offline' => 'gray',
                        'error' => 'danger',
                        'upgrading' => 'info',
                        default => 'warning',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('last_heartbeat_at')
                    ->label(__('Last Heartbeat'))
                    ->since()
                    ->sortable(),
                Tables\Columns\TextColumn::make('error_count')
                    ->label(__('Errors'))
                    ->numeric()
                    ->color(fn (int $state): string => $state > 0 ? 'danger' : 'gray')
                    ->sortable(),
                Tables\Columns\TextColumn::make('server.name')
                    ->label(__('Target Server'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('is_active')
                    ->label(__('Status'))
                    ->badge()
                    ->color(fn ($state) => $state ? 'success' : 'danger')
                    ->formatStateUsing(fn ($state) => $state ? 'Active' : 'Suspended'),
                Tables\Columns\TextColumn::make('billingProfile.reference_name')
                    ->label(__('Billing Profile'))
                    ->formatStateUsing(fn (?string $state, Project $record) => $state ? $state . ' ( Legal Name: ' . $record->billingProfile?->name . ' )' : null)
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('billingProfile.tier')
                    ->label(__('Tier'))
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(query: fn ($query, $direction) => $query->orderBy(BillingProfile::select('tier')->whereColumn('billing_profiles.id', 'projects.billing_profile_id'), $direction)),
                Tables\Columns\TextColumn::make('billingProfile.user.name')
                    ->label(__('Billing Owner'))
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(query: fn ($query, $direction) => $query->orderBy(User::select('name')->whereColumn('users.id', function ($q) {
                        $q->select('user_id')->from('billing_profiles')->whereColumn('billing_profiles.id', 'projects.billing_profile_id');
                    }), $direction)),
                Tables\Columns\TextColumn::make('billingProfile.user.email')
                    ->label(__('Billing Email'))
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(query: fn ($query, $direction) => $query->orderBy(User::select('email')->whereColumn('users.id', function ($q) {
                        $q->select('user_id')->from('billing_profiles')->whereColumn('billing_profiles.id', 'projects.billing_profile_id');
                    }), $direction)),
                Tables\Columns\TextColumn::make('billing_status')
                    ->label(__('Billing'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'past_due' => 'warning',
                        'suspended' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->sortable(),
                Tables\Columns\TextColumn::make('apisHubRelease.version_tag')
                    ->label(__('Release'))
                    ->badge()
                    ->color('info')
                    ->placeholder(__('Auto (active)'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('channels_count')
                    ->label(__('Channels'))
                    ->getStateUsing(fn (Project $record) => $record->countEnabledChannels())
                    ->badge()
                    ->color('info')
                    ->sortable(false),
                Tables\Columns\TextColumn::make('assets_count')
                    ->label(__('Total Assets'))
                    ->getStateUsing(fn (Project $record) => $record->countEnabledAssets(false))
                    ->badge()
                    ->color('gray')
                    ->sortable(false),
                Tables\Columns\TextColumn::make('active_assets_count')
                    ->label(__('Active Assets'))
                    ->tooltip(__('Assets in currently enabled channels'))
                    ->getStateUsing(fn (Project $record) => $record->countEnabledAssets(true))
                    ->badge()
                    ->color('primary')
                    ->sortable(false),
                Tables\Columns\TextColumn::make('locked_assets_count')
                    ->label(__('Locked'))
                    ->tooltip(__('Assets locked due to exceeding limits'))
                    ->getStateUsing(fn (Project $record) => $record->countLockedAssets())
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'gray')
                    ->sortable(false),
                Tables\Columns\TextColumn::make('grace_period_assets_count')
                    ->label(__('Grace Period'))
                    ->tooltip(__('Assets in grace period prior to locking'))
                    ->getStateUsing(fn (Project $record) => $record->countGracePeriodAssets())
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'warning' : 'gray')
                    ->sortable(false),
                Tables\Columns\IconColumn::make('redeploy_pending')
                    ->label(__('Pending Deploy'))
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('users_count')
                    ->label(__('Collaborators'))
                    ->counts('users')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('deployment_logs_count')
                    ->label(__('Deploys'))
                    ->counts('deploymentLogs')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('last_deployed_at')
                    ->dateTime()
                    ->sortable()
                    ->placeholder(__('Never deployed')),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active'),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\Action::make('checkAuth')
                    ->label(__('Check Auth'))
                    ->icon('heroicon-o-shield-check')
                    ->color('info')
                    ->visible(fn (Project $record) => $record->health_status === 'online')
                    ->requiresConfirmation()
                    ->modalHeading(__('Validate Channel Tokens'))
                    ->modalDescription(__('This will query the remote node to validate the tokens for all configured channels. This might take a few seconds.'))
                    ->action(function (Project $record, RemoteEngineService $service) {
                        if (!$record->is_active) {
                            Notification::make()->warning()->title(__('Project is suspended'))->send();
                            return;
                        }
                        
                        $validation = $service->validateTokens($record, 'all');
                        
                        if (($validation['status'] ?? '') === 'error') {
                            Notification::make()
                                ->danger()
                                ->title(__('Validation Failed'))
                                ->body($validation['message'] ?? 'Unknown error connecting to remote engine.')
                                ->persistent()
                                ->send();
                            return;
                        }
                        $results = $validation['results'] ?? [];
                        $validCount = 0;
                        $warningCount = 0;
                        $invalidCount = 0;
                        $details = [];
                        
                        foreach ($results as $channel => $data) {
                            $name = ucfirst($channel);
                            if (($data['status'] ?? '') === 'valid') {
                                $validCount++;
                                $details[] = "✅ <strong>{$name}</strong>";
                            } elseif (($data['status'] ?? '') === 'warning') {
                                $warningCount++;
                                $details[] = "⚠️ <strong>{$name}</strong> - Rate Limited";
                            } else {
                                $invalidCount++;
                                $details[] = "❌ <strong>{$name}</strong>";
                            }
                        }
                        
                        if (empty($details)) {
                            Notification::make()
                                ->info()
                                ->title(__('No channels to validate'))
                                ->send();
                            return;
                        }

                        $notification = Notification::make()
                            ->title(__("Validation Complete: $validCount valid, $warningCount warnings, $invalidCount invalid"))
                            ->body(implode('<br>', $details));

                        if ($invalidCount > 0) {
                            $notification->danger()->persistent();
                        } elseif ($warningCount > 0) {
                            $notification->warning()->persistent();
                        } else {
                            $notification->success();
                        }
                        
                        $notification->send();
                    }),
                Tables\Actions\Action::make('toggleActive')
                    ->label(fn (Project $record) => $record->is_active ? 'Suspend' : 'Activate')
                    ->icon(fn (Project $record) => $record->is_active ? 'heroicon-o-pause-circle' : 'heroicon-o-play-circle')
                    ->color(fn (Project $record) => $record->is_active ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->modalHeading(fn (Project $record) => $record->is_active ? 'Suspend Infrastructure?' : 'Resume Infrastructure?')
                    ->modalDescription(fn (Project $record) => $record->is_active 
                        ? 'This will stop all Docker containers and synchronization jobs for this instance on the remote server.'
                        : 'This will restart the containers on the server and resume sync jobs.')
                    ->disabled(fn (Project $record) => $record->health_status === 'provisioning')
                    ->action(function (Project $record, DeployerService $deployer, Tables\Actions\Action $action) {
                        $newStatus = !$record->is_active;
                        
                        // 1. SSH Command (Blocking)
                        $result = $newStatus ? $deployer->startContainers($record) : $deployer->stopContainers($record);

                        if ($result['status'] !== 'success') {
                             Notification::make()
                                ->title(__('Infrastructure Command Failed'))
                                ->body('Could not ' . ($newStatus ? 'start' : 'stop') . ' containers: ' . ($result['output'] ?? 'SSH Error'))
                                ->danger()
                                ->persistent()
                                ->send();
                            
                            $action->halt(); // Cancel DB update
                            return;
                        }

                        // 2. Update DB ONLY on success
                        $record->update(['is_active' => $newStatus]);

                        // 3. Log into History
                        \App\Models\ProjectStatusLog::create([
                            'project_id' => $record->id,
                            'is_active' => $newStatus,
                            'event_type' => 'manual_toggle',
                            'created_by_id' => Auth::id(),
                            'notes' => $newStatus ? 'Project resumed manually' : 'Project suspended manually',
                        ]);

                        Notification::make()
                            ->title($newStatus ? 'Infrastructure Online' : 'Infrastructure Suspended')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\RestoreAction::make()
                    ->modalHeading(__('Restore Project & Resume Infra'))
                    ->before(function (Project $record, DeployerService $deployer, Tables\Actions\RestoreAction $action) {
                        // 1. Quota Validation
                        if (!$record->billing_profile_id) {
                            Notification::make()
                                ->title(__('Missing Billing Profile'))
                                ->body('This project has no billing profile assigned. Please assign one before restoring.')
                                ->danger()
                                ->persistent()
                                ->send();
                            $action->halt();
                            return;
                        }

                        $billingService = app(\App\Services\BillingLifecycleService::class);
                        $profile = $record->billingProfile;
                        $maxProjects = $billingService->getMaxProjectsForTier($profile->tier);
                        
                        // We do not count the current project since it is currently soft-deleted
                        $activeProjectsCount = $profile->projects()->where('billing_status', 'active')->count();

                        if ($activeProjectsCount >= $maxProjects) {
                            Notification::make()
                                ->title(__('Quota Exceeded'))
                                ->body('The billing profile for this project has reached its maximum active projects limit (' . $maxProjects . '). Please upgrade the tier or suspend another project before restoring.')
                                ->danger()
                                ->persistent()
                                ->send();
                            $action->halt();
                            return;
                        }

                        // 2. Resume containers
                        $result = $deployer->startContainers($record);

                        if ($result['status'] !== 'success') {
                             Notification::make()
                                ->title(__('Restoration Aborted'))
                                ->body('Failed to restart containers on server: ' . ($result['output'] ?? 'SSH Error'))
                                ->danger()
                                ->persistent()
                                ->send();
                            
                            $action->halt(); // Cancel Restoration
                            return;
                        }
                        
                        // 2. Log into History
                        \App\Models\ProjectStatusLog::create([
                            'project_id' => $record->id,
                            'is_active' => true,
                            'event_type' => 'restore',
                            'created_by_id' => Auth::id(),
                            'notes' => 'Project restored from archive',
                        ]);
                    }),
                Tables\Actions\Action::make('checkNodeStatus')
                    ->label(__('Check Engine'))
                    ->icon('heroicon-o-shield-check')
                    ->color('info')
                    ->action(function (Project $record, RemoteEngineService $service) {
                        $response = $service->getStatus($record);
                        $isOnline = ($response['success'] ?? false) || ($response['status'] ?? '') === 'success';

                        Notification::make()
                            ->title($isOnline ? "{$record->name} is Online" : "{$record->name} Offline")
                            ->status($isOnline ? 'success' : 'danger')
                            ->send();
                    }),

                Tables\Actions\Action::make('triggerNodeRedeploy')
                    ->label(__('API Redeploy'))
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalDescription(__('This will trigger a background redeployment via the Node\'s internal API. Continue?'))
                    ->action(function (Project $record, RemoteEngineService $service) {
                        $response = $service->triggerRedeploy($record);
                        
                        Notification::make()
                            ->title(($response['success'] ?? false) ? 'Redeploy Triggered' : 'Trigger Failed')
                            ->status(($response['success'] ?? false) ? 'success' : 'danger')
                            ->send();
                    }),

                Tables\Actions\Action::make('deploy')
                    ->label(__('Force Async Deploy'))
                    ->icon('heroicon-o-cloud-arrow-up')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalDescription(__('This will dispatch a background SSH-based deployment job for the remote server. You can monitor the progress in the Edit view below. Continue?'))
                    ->action(function (Project $record) {
                        \App\Jobs\DeployProjectJob::dispatch($record);
                        
                        Notification::make()
                            ->title(__('Deployment Job Queued'))
                            ->body('You can check the logs inside the Project edit page to see the progress.')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('upgradeRelease')
                    ->label(__('Upgrade Release'))
                    ->icon('heroicon-o-arrow-up-circle')
                    ->color('info')
                    ->requiresConfirmation()
                    ->visible(fn (Project $record) => $record->apisHubRelease !== null)
                    ->modalHeading(fn (Project $record) => "Upgrade {$record->name}")
                    ->modalDescription(fn (Project $record) => sprintf(
                        'Current: %s. Select a newer release to upgrade to.',
                        $record->apisHubRelease->version_tag
                    ))
                    ->form(fn (Project $record) => [
                        Forms\Components\Select::make('target_release_id')
                            ->label(__('Target Release'))
                            ->options(
                                \App\Models\ApisHubRelease::availableUpgradesFor($record->apisHubRelease->version_tag)
                                    ->mapWithKeys(fn ($r) => [$r->id => $r->version_tag])
                            )
                            ->required()
                            ->helperText(__('Newer releases only.')),
                    ])
                    ->action(function (Project $record, array $data) {
                        $target = \App\Models\ApisHubRelease::find($data['target_release_id']);
                        if (!$target) {
                            Notification::make()
                                ->title(__('Invalid Release'))
                                ->danger()
                                ->send();
                            return;
                        }

                        \App\Jobs\UpgradeProjectReleaseJob::dispatch($record, $target);

                        Notification::make()
                            ->title(__('Upgrade Queued'))
                            ->body("Upgrade to {$target->version_tag} has been dispatched.")
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('rotateApiKey')
                    ->label(__('Rotate Public Key'))
                    ->icon('heroicon-o-key')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalDescription(__('This will generate a new Public API Key and PUSH IT to the remote node. Existing consumers with the old key will be broken. Continue?'))
                    ->action(function (Project $record, RemoteEngineService $service) {
                        $newKey = \Illuminate\Support\Str::random(48);
                        $record->update(['public_api_key' => $newKey]);
                        
                        // Push to Node
                        $service->updateCredentials($record, [
                            'APP_API_KEY' => $newKey
                        ]);

                        Notification::make()
                            ->title(__('Key Rotated & Pushed'))
                            ->body('The new key has been synchronized with the remote instance.')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('hardDelete')
                    ->label(__('HARD DELETE (INFRA)'))
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading(fn (Project $record) => "Permanently Delete Project: {$record->subdomain}")
                    ->modalDescription(fn (Project $record) => "This will PERMANENTLY destroy all data, containers and configurations for '{$record->name}'. This action is IRREVERSIBLE.")
                    ->form([
                        Forms\Components\TextInput::make('confirm_subdomain')
                            ->label(fn (Project $record) => "Please type '{$record->subdomain}' to confirm.")
                            ->required()
                            ->rules([
                                fn (Project $record) => function (string $attribute, $value, $fail) use ($record) {
                                    if ($value !== $record->subdomain) {
                                        $fail("The subdomain you typed does not match.");
                                    }
                                },
                            ]),
                    ])
                    ->action(function (Project $record, DeployerService $deployer, array $data) {
                        // 1. SSH into server and clean up (Containers, files and Caddy)
                        $result = $deployer->removeInstance($record);
                        
                        if ($result['status'] === 'success') {
                            // 2. Perform Hard Delete in DB
                            $record->forceDelete();
                            
                            Notification::make()
                                ->title(__('Project & Infrastructure Deleted'))
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title(__('Cleanup Failed'))
                                ->body('The infrastructure removal failed. DB record kept for manual inspection: ' . ($result['output'] ?? ''))
                                ->danger()
                                ->persistent()
                                ->send();
                        }
                    })
                    ->visible(fn () => Auth::user()?->is_admin ?? false),

                Tables\Actions\DeleteAction::make()
                    ->label(__('Archive Project'))
                    ->modalHeading(__('Archive Project & Stop Infra'))
                    ->modalDescription(__('Archiving will hide the project from the tenant view and STOP its infrastructure on the server. Data will be kept.'))
                    ->before(function (Project $record, DeployerService $deployer, Tables\Actions\DeleteAction $action) {
                        // 1. Stop containers before archiving
                        $result = $deployer->stopContainers($record);

                        if ($result['status'] !== 'success') {
                             Notification::make()
                                ->title(__('Archive Aborted'))
                                ->body('Failed to stop containers on server: ' . ($result['output'] ?? 'SSH Error'))
                                ->danger()
                                ->persistent()
                                ->send();
                            
                            $action->halt(); // Cancel Deletion
                            return;
                        }
                        
                        // 2. Log into History
                        \App\Models\ProjectStatusLog::create([
                            'project_id' => $record->id,
                            'is_active' => false,
                            'event_type' => 'archive',
                            'created_by_id' => Auth::id(),
                            'notes' => 'Project archived (Soft deleted)',
                        ]);
                    })
                    ->successNotificationTitle(__('Project archived and infrastructure stopped')),
            ])
            ->bulkActions([
                // Deshabilitado el borrado masivo por seguridad (Instrucción #1)
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ])
            ->with([
                'user',
                'billingProfile.user',
                'server',
                'apisHubRelease',
            ]);
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\Resources\ProjectResource\RelationManagers\DeploymentLogsRelationManager::class,
            \App\Filament\Resources\ProjectResource\RelationManagers\StatusLogsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPage::route('/'),
            'create' => CreatePage::route('/create'),
            'edit' => EditPage::route('/{record}/edit'),
        ];
    }
}
