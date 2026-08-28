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
                        Forms\Components\Placeholder::make('owner_profile_link')
                            ->label('')
                            ->columnSpanFull()
                            ->content(fn (?Project $record) => $record?->user
                                ? new \Illuminate\Support\HtmlString(
                                    '<a href="' . route('filament.admin.resources.users.edit', ['record' => $record->user_id]) . '" target="_blank" class="text-primary-600 hover:underline text-sm">' . __('Check user\'s profile') . '</a>'
                                )
                                : ''),
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
                            ->content(fn (?Project $record) => $record?->billingProfile
                                ? new \Illuminate\Support\HtmlString(
                                    '<a href="' . route('filament.admin.resources.billing-profiles.view', ['record' => $record->billing_profile_id]) . '" target="_blank" class="text-primary-600 hover:underline">' . e($record->billingProfile->reference_name) . '</a>'
                                )
                                : 'None')
                            ->columnSpan(1),
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
                Forms\Components\Section::make('Team & Collaborators')
                    ->description(__('Project members and their roles.'))
                    ->schema([
                        Forms\Components\Repeater::make('collaborators_display')
                            ->label(__('Members'))
                            ->schema([
                                Forms\Components\TextInput::make('name')->disabled(),
                                Forms\Components\TextInput::make('email')->disabled(),
                                Forms\Components\TextInput::make('role')->disabled(),
                                Forms\Components\TextInput::make('access')->disabled(),
                                Forms\Components\TextInput::make('invitation')->disabled(),
                            ])
                            ->columns(5)
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                            ->dehydrated(false)
                            ->itemLabel(fn (array $state): string => $state['email'] ?? $state['name'] ?? '')
                            ->default([]),
                    ]),

                Forms\Components\Section::make('Settings')
                    ->description(__('Timezone and content language preferences.'))
                    ->schema([
                        Forms\Components\Placeholder::make('timezone')
                            ->label(__('Timezone'))
                            ->content(fn (?Project $record) => $record?->timezone ?? 'UTC'),
                        Forms\Components\Placeholder::make('content_languages')
                            ->label(__('Content Languages'))
                            ->content(function (?Project $record) {
                                if (!$record || !$record->sync_config) return '—';
                                $langs = [];
                                foreach ($record->sync_config as $channelConfig) {
                                    if (is_array($channelConfig) && !empty($channelConfig['language'])) {
                                        $langs[] = $channelConfig['language'];
                                    }
                                }
                                return $langs ? implode(', ', array_unique($langs)) : '—';
                            }),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Sync Telemetry')
                    ->description(__('Live sync progress per channel, fetched from the remote node.'))
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Placeholder::make('overall_sync_percentage')
                                    ->label(__('Overall Sync'))
                                    ->content(fn (?Project $record) => ($pct = static::getSyncTelemetryData($record)['overall']) !== null ? "{$pct}%" : '—'),
                                Forms\Components\Placeholder::make('overall_fully_synced')
                                    ->label(__('Accounts Fully Synced'))
                                    ->content(fn (?Project $record) => ($pct = static::getSyncTelemetryData($record)['fully_synced']) !== null ? "{$pct}%" : '—'),
                                Forms\Components\Placeholder::make('overall_assets')
                                    ->label(__('Assets Synced'))
                                    ->content(fn (?Project $record) => static::getSyncTelemetryData($record)['assets'] ?: '—'),
                            ])
                            ->columnSpanFull(),
                        Forms\Components\Placeholder::make('sync_telemetry_note')
                            ->label('')
                            ->columnSpanFull()
                            ->extraAttributes(fn (?Project $record) => static::getSyncTelemetryData($record)['message']
                                ? ['class' => 'text-warning-600 dark:text-warning-400']
                                : [])
                            ->content(fn (?Project $record) => static::getSyncTelemetryData($record)['message'] ?? ''),
                        Forms\Components\Repeater::make('sync_telemetry_channels')
                            ->label(__('Per Channel'))
                            ->schema([
                                Forms\Components\TextInput::make('channel')->disabled(),
                                Forms\Components\TextInput::make('completion')->label(__('Sync %'))->disabled(),
                                Forms\Components\TextInput::make('fully_synced')->label(__('Fully Synced %'))->disabled(),
                                Forms\Components\TextInput::make('assets')->label(__('Assets Synced'))->disabled(),
                            ])
                            ->columns(4)
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                            ->dehydrated(false)
                            ->itemLabel(fn (array $state): string => $state['channel'] ?? '')
                            ->default([]),
                    ]),
            ]);
    }

    protected static ?array $syncTelemetryData = [];

    private static function getSyncTelemetryData(?Project $record): array
    {
        $key = $record?->getKey();

        if ($key === null || ! $record?->hasBeenDeployed()) {
            return ['overall' => null, 'fully_synced' => null, 'assets' => null, 'channels' => [], 'message' => null];
        }

        if (array_key_exists($key, static::$syncTelemetryData)) {
            return static::$syncTelemetryData[$key];
        }

        $unavailable = static::$syncTelemetryData[$key] = [
            'overall' => null,
            'fully_synced' => null,
            'assets' => null,
            'channels' => [],
            'message' => __('Telemetry unavailable.'),
        ];

        try {
            $result = app(RemoteEngineService::class)->getSyncTelemetry($record, 3);
        } catch (\Throwable $e) {
            return $unavailable;
        }

        if (! is_array($result) || ! isset($result['completion_percentage']) || ! is_array($result['channels'] ?? null)) {
            if (is_array($result) && isset($result['status']) && $result['status'] === 'error') {
                static::$syncTelemetryData[$key]['message'] = $result['message'] ?? __('Telemetry unavailable.');
            }

            return static::$syncTelemetryData[$key];
        }

        $channels = [];
        foreach ($result['channels'] as $channelKey => $stats) {
            if (! is_array($stats)) {
                continue;
            }

            $channels[] = [
                'channel' => ucfirst(str_replace('_', ' ', (string) $channelKey)),
                'completion' => (int) round((float) ($stats['completion_percentage'] ?? 0)),
                'fully_synced' => (int) round((float) ($stats['fully_synced_percentage'] ?? 0)),
                'assets' => (int) ($stats['fully_synced_count'] ?? 0) . ' / ' . (int) ($stats['total_assets'] ?? 0),
            ];
        }

        return static::$syncTelemetryData[$key] = [
            'overall' => (int) round((float) ($result['completion_percentage'] ?? 0)),
            'fully_synced' => (int) round((float) ($result['fully_synced_percentage'] ?? 0)),
            'assets' => (int) ($result['fully_synced_count'] ?? 0) . ' / ' . (int) ($result['total_assets'] ?? 0),
            'channels' => $channels,
            'message' => null,
        ];
    }

    public static function getCollaboratorDisplayData(Project $record): array
    {
        $memberIds = [];
        $members = [];

        foreach ($record->users()->withPivot('asset_access_unrestricted')->get() as $user) {
            $memberIds[] = $user->id;
            $members[] = [
                'name' => $user->name,
                'email' => $user->email,
                'role' => ucfirst((string) ($user->getRoleNames()->first() ?? 'member')),
                'access' => $user->pivot->asset_access_unrestricted ? __('Unrestricted') : __('Restricted'),
                'invitation' => __('Active'),
            ];
        }

        if ($owner = $record->user) {
            if (! in_array($owner->id, $memberIds, true)) {
                $members[] = [
                    'name' => $owner->name,
                    'email' => $owner->email,
                    'role' => __('Owner'),
                    'access' => '—',
                    'invitation' => __('Active'),
                ];
            }
        }

        foreach ($record->pendingInvitations()->where('expires_at', '>', now())->get() as $invitation) {
            $members[] = [
                'name' => '',
                'email' => $invitation->email,
                'role' => ucfirst((string) ($invitation->role ?? 'member')),
                'access' => '—',
                'invitation' => __('Pending Invitation'),
            ];
        }

        return $members;
    }

    public static function getSyncTelemetryChannels(Project $record): array
    {
        return static::getSyncTelemetryData($record)['channels'];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('subdomain')
                    ->badge()
                    ->color('gray')
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label(__('Owner'))
                    ->description(fn (Project $record): string => $record->user->email ?? '')
                    ->searchable()
                    ->sortable()
                    ->url(fn (Project $record): string => route('filament.admin.resources.users.edit', ['record' => $record->user_id]))
                    ->openUrlInNewTab(),
                Tables\Columns\TextColumn::make('billingProfile.reference_name')
                    ->label(__('Billing Profile'))
                    ->formatStateUsing(fn (?string $state) => $state)
                    ->searchable()
                    ->sortable()
                    ->url(fn (Project $record): string => route('filament.admin.resources.billing-profiles.view', ['record' => $record->billing_profile_id]))
                    ->openUrlInNewTab(),
                Tables\Columns\TextColumn::make('billingProfile.tier')
                    ->label(__('Tier'))
                    ->badge()
                    ->sortable(query: fn ($query, $direction) => $query->orderBy(BillingProfile::select('tier')->whereColumn('billing_profiles.id', 'projects.billing_profile_id'), $direction)),
                Tables\Columns\TextColumn::make('billingProfile.current_cycle_ends_at')
                    ->label(__('Next Billing Cycle'))
                    ->dateTime()
                    ->placeholder(__('N/A'))
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
                Tables\Columns\TextColumn::make('last_deployed_at')
                    ->dateTime()
                    ->sortable()
                    ->placeholder(__('Never deployed')),
                Tables\Columns\TextColumn::make('is_active')
                    ->label(__('Status'))
                    ->badge()
                    ->color(fn ($state) => $state ? 'success' : 'danger')
                    ->formatStateUsing(fn ($state) => $state ? 'Active' : 'Suspended'),
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
                Tables\Columns\TextColumn::make('server.name')
                    ->label(__('Target Server'))
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
                Tables\Columns\TextColumn::make('billingProfile.name')
                    ->label(__('Billing Profile Legal Name'))
                    ->placeholder(__('N/A'))
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('last_heartbeat_at')
                    ->label(__('Last Heartbeat'))
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('redeploy_pending')
                    ->label(__('Pending Deploy'))
                    ->boolean()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active'),
                Tables\Filters\TrashedFilter::make(),
                Tables\Filters\SelectFilter::make('user.name')
                    ->label(__('Owner (Name)'))
                    ->searchable()
                    ->options(fn () => \App\Models\User::query()
                        ->select('id', 'name')
                        ->whereNotNull('name')
                        ->where('name', '!=', '')
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->toArray()),
                Tables\Filters\SelectFilter::make('user.email')
                    ->label(__('Owner (Email)'))
                    ->searchable()
                    ->options(fn () => \App\Models\User::query()
                        ->select('id', 'email')
                        ->whereNotNull('email')
                        ->where('email', '!=', '')
                        ->orderBy('email')
                        ->pluck('email', 'id')
                        ->toArray()),
                Tables\Filters\SelectFilter::make('billingProfile.reference_name')
                    ->label(__('Billing Profile'))
                    ->searchable()
                    ->options(fn () => \App\Models\BillingProfile::query()
                        ->select('id', 'reference_name')
                        ->whereNotNull('reference_name')
                        ->where('reference_name', '!=', '')
                        ->orderBy('reference_name')
                        ->pluck('reference_name', 'id')
                        ->toArray()),
                Tables\Filters\SelectFilter::make('billingProfile.tier')
                    ->label(__('Tier'))
                    ->options([
                        \App\Enums\UserTier::FREE->value => \App\Enums\UserTier::FREE->getLabel(),
                        \App\Enums\UserTier::PRO->value => \App\Enums\UserTier::PRO->getLabel(),
                        \App\Enums\UserTier::ULTRA->value => \App\Enums\UserTier::ULTRA->getLabel(),
                        \App\Enums\UserTier::FOUNDER->value => \App\Enums\UserTier::FOUNDER->getLabel(),
                        \App\Enums\UserTier::ENTERPRISE->value => \App\Enums\UserTier::ENTERPRISE->getLabel(),
                        \App\Enums\UserTier::SUSPENDED->value => \App\Enums\UserTier::SUSPENDED->getLabel(),
                    ])
                    ->multiple(),
                Tables\Filters\SelectFilter::make('billing_status')
                    ->label(__('Billing'))
                    ->options([
                        'active' => 'Active',
                        'past_due' => 'Past Due',
                        'suspended' => 'Suspended',
                    ])
                    ->multiple(),
                Tables\Filters\SelectFilter::make('health_status')
                    ->label(__('Health Status'))
                    ->options([
                        'online' => 'Online',
                        'offline' => 'Offline',
                        'error' => 'Error',
                        'upgrading' => 'Upgrading',
                    ])
                    ->multiple(),
                Tables\Filters\SelectFilter::make('server.name')
                    ->label(__('Target Server'))
                    ->searchable()
                    ->options(fn () => \App\Models\Server::query()
                        ->select('id', 'name')
                        ->whereNotNull('name')
                        ->where('name', '!=', '')
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->toArray()),
                Tables\Filters\SelectFilter::make('apisHubRelease.version_tag')
                    ->label(__('Release'))
                    ->searchable()
                    ->options(fn () => \App\Models\ApisHubRelease::query()
                        ->select('id', 'version_tag')
                        ->where('is_active', true)
                        ->whereNotNull('version_tag')
                        ->where('version_tag', '!=', '')
                        ->orderBy('version_tag')
                        ->pluck('version_tag', 'id')
                        ->toArray()),
            ])
            ->actions([
                Tables\Actions\Action::make('checkAuth')
                    ->label(__('Check Auth'))
                    ->icon('heroicon-o-shield-check')
                    ->color('info')
                    ->visible(fn (Project $record) => in_array($record->health_status, ['online', 'healthy', 'syncing']))
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
                        $isJobStaleOrIdle = is_null($record->deploy_started_at) || $record->deploy_started_at->lt(now()->subMinutes(3));
                        if ($isJobStaleOrIdle) {
                            $record->update([
                                'health_status' => 'online',
                                'deploy_started_at' => null,
                            ]);
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

                        // Only clear syncing/redeploying if deploy_started_at is null or older than 3 minutes (stale job)
                        $isJobStaleOrIdle = is_null($record->deploy_started_at) || $record->deploy_started_at->lt(now()->subMinutes(3));

                        if ($isOnline && $isJobStaleOrIdle) {
                            $record->update([
                                'health_status' => 'online',
                                'deploy_started_at' => null,
                            ]);
                        }

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
                        'Current: %s. %s',
                        $record->apisHubRelease->version_tag,
                        is_null($record->last_deployed_at)
                            ? 'This project has not been deployed yet. The upgrade will only update the target version.'
                            : 'Select a newer release to upgrade to.'
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

                        if (is_null($record->last_deployed_at)) {
                            // Never deployed: just update the target release. Initial deploy will pick it up.
                            $record->update(['apis_hub_release_id' => $target->id]);

                            Notification::make()
                                ->title(__('Upgrade Saved'))
                                ->body("Target version updated to {$target->version_tag}. The initial deployment will use this version.")
                                ->success()
                                ->send();

                            return;
                        }

                        // Already deployed: trigger full upgrade deployment
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
