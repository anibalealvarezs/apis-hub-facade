<?php

namespace App\Filament\App\Pages;

use App\Models\ProjectDeploymentLog;
use App\Services\RemoteEngineService;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class SyncSettings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    public static function getNavigationGroup(): ?string
    {
        return __('Data & Integrations');
    }

    public static function getNavigationLabel(): string
    {
        return __('Synchronization Settings');
    }

    protected static string $view = 'filament.app.pages.sync-settings';
    protected static ?string $slug = 'sync-settings';

    public ?array $data = [];
    public bool $isSyncable = false;

    /**
     * TTL for the "sync sequence in progress" banner (seconds).
     * start-sync.sh should complete well within this window.
     */
    public const SYNC_SEQUENCE_TTL_SECONDS = 3600; // 1 hour

    /**
     * Re-hydrate the tenant from DB so wire:poll picks up health_status / last_sync_started_at changes.
     * Also auto-clears last_sync_started_at if it has exceeded the TTL, preventing the banner
     * from being stuck forever when start-sync.sh fails silently on the remote node.
     */
    public function refreshTenantStatus(): void
    {
        /** @var \App\Models\Project $tenant */
        $tenant = Filament::getTenant()->fresh();
        $this->isSyncable = $tenant->is_active && $tenant->health_status !== 'provisioning';

        // Auto-clear a stale sync-in-progress marker
        if (
            $tenant->last_sync_started_at
            && $tenant->last_sync_started_at->diffInSeconds(now()) > self::SYNC_SEQUENCE_TTL_SECONDS
        ) {
            $tenant->update(['last_sync_started_at' => null]);
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('viewLogs')
                ->label(__('View Last Deployment Log'))
                ->icon('heroicon-o-document-text')
                ->color('gray')
                ->visible(fn () => auth()->user()->can('edit_preferences'))
                ->modalHeading(__('Deployment Log Output'))
                ->modalSubmitAction(false)
                ->modalCancelActionLabel(__('Close'))
                ->modalContent(function () {
                    $log = ProjectDeploymentLog::where('project_id', Filament::getTenant()->id)
                        ->latest('id')
                        ->first();
                    
                    return view('filament.app.components.deployment-log-modal', ['log' => $log]);
                }),
            Action::make('triggerSync')
                ->label(__('Deploy Infrastructure Updates'))
                ->icon('heroicon-o-arrow-path')
                ->color('success')
                ->disabled(fn () => ! Filament::getTenant()->fresh()->is_active
                    || Filament::getTenant()->fresh()->billing_status === 'suspended'
                    || in_array(Filament::getTenant()->fresh()->health_status, ['redeploying', 'syncing'])
                    || ! auth()->user()->can('deploy_project'))
                ->requiresConfirmation()
                ->action(function (RemoteEngineService $service) {
                    $tenant = Filament::getTenant()->fresh();

                    // Record that a sync sequence was requested (used for UI progress banner)
                    $tenant->update(['last_sync_started_at' => now()]);

                    $response = $service->startSync($tenant);

                    Notification::make()
                        ->title(($response['status'] ?? '') === 'success' ? __('Synchronization Sequence Started') : __('Sync Deployment Failed'))
                        ->body($response['message'] ?? __('Applying configuration, restarting workers, and scheduling initial jobs.'))
                        ->send();
                }),

        ];
    }

    public function mount(RemoteEngineService $service): void
    {
        $tenant = Filament::getTenant();
        $this->isSyncable = $tenant->is_active && $tenant->health_status !== 'provisioning';

        // RECONCILIATION: Pull latest tokens from Node if reachable
        if ($this->isSyncable) {
            $validation = $service->validateTokens($tenant, 'facebook');
            $fbData = $validation['results']['facebook'] ?? [];

            if (($fbData['status'] ?? '') === 'valid' && ! empty($fbData['access_token'])) {
                if ($tenant->facebook_user_token !== $fbData['access_token']) {
                    // Update Facade silently with Node's truth
                    $tenant->facebook_user_token = $fbData['access_token'];
                    $tenant->facebook_user_id = $fbData['user_id'] ?? $tenant->facebook_user_id;
                    $tenant->save();
                }
            }
        }

        $this->form->fill([
            ...($tenant->sync_config ?? []),
            'app_api_key' => $tenant->public_api_key,
            'facebook_user_token' => $tenant->facebook_user_token,
            'facebook_user_id' => $tenant->facebook_user_id,
            'google_refresh_token' => $tenant->google_refresh_token,
            'google_user_id' => $tenant->google_user_id,
        ]);

        $pendingAssets = \App\Models\AssetBillingLock::where('project_id', $tenant->id)
            ->where('status', 'locked')
            ->whereNull('disabled_at')
            ->where(function ($query) use ($tenant) {
                if ($tenant->last_deployed_at) {
                    $query->where('locked_at', '>', $tenant->last_deployed_at);
                }
            })
            ->count();

        if ($pendingAssets > 0) {
            Notification::make()
                ->title(__('Action Recommended'))
                ->body(__('You have :count newly confirmed asset(s). We recommend clicking "Deploy Infrastructure Updates" above to start tracking their full history.', ['count' => $pendingAssets]))
                ->warning()
                ->persistent()
                ->send();
        }
    }

    public function form(Form $form): Form
    {
        $tenant = Filament::getTenant();
        $isSuspended = ! $tenant->is_active || $tenant->billing_status === 'suspended';

        return $form
            ->schema([
                Section::make(__('Global Processing Settings'))
                    ->description(__('Configure how long your dedicated explorers should wait before considering a synchronization job as stuck.'))
                    ->schema([
                        Select::make('jobs_timeout_hours')
                            ->label(__('Jobs Timeout (Hours)'))
                            ->options([
                                '1' => __('1 Hour (Recommended)'),
                                '2' => __('2 Hours'),
                                '6' => __('6 Hours'),
                                '12' => __('12 Hours'),
                            ])
                            ->default('1')
                            ->helperText(__('If a job takes longer than this duration, it will be automatically aborted to free up resources.')),
                    ])->collapsed(),

                Section::make(__('API Access (External Integration)'))
                    ->description(__('Use these credentials to access your data via third-party apps (PowerBI, Looker, etc.)'))
                    ->visible(function () {
                        $tier = Filament::getTenant()->billingProfile?->tier?->value;
                        return !in_array($tier, ['free', 'pro']);
                    })
                    ->schema([
                        TextInput::make('api_url')
                            ->label(__('API Endpoint'))
                            ->formatStateUsing(fn () => 'https://' . Filament::getTenant()->subdomain . '.' . (config('app.network_domain') ?: 'apis-hub.cloud') . '/api')
                            ->disabled()
                            ->suffixIcon('heroicon-m-globe-alt'),
                        TextInput::make('app_api_key')
                            ->label(__('Secret API Key'))
                            ->password()
                            ->revealable()
                            ->disabled()
                            ->helperText(__('Keep this key secure. It provides full access to your cached data.'))
                            ->hintAction(
                                \Filament\Forms\Components\Actions\Action::make('rotateKey')
                                    ->icon('heroicon-m-arrow-path')
                                    ->color('warning')
                                    ->disabled(fn () => ! Filament::getTenant()->is_active || Filament::getTenant()->billing_status === 'suspended' || ! auth()->user()->can('edit_preferences'))
                                    ->requiresConfirmation()
                                    ->modalHeading(__('Rotate API Key?'))
                                    ->modalDescription(__('Generating a new key will immediately invalidate the current one. You must update all your external integrations (PowerBI, Looker, etc.) with the new key.'))
                                    ->modalSubmitActionLabel(__('Yes, rotate and push'))
                                    ->action(function (\App\Services\DeployerService $deployer) {
                                        $tenant = Filament::getTenant();
                                        $newKey = bin2hex(random_bytes(32));

                                        // 1. Save locally
                                        $tenant->update(['public_api_key' => $newKey]);

                                        // 2. Push to remote server via SSH
                                        $deployer->updateCredentials($tenant, [
                                            'APP_API_KEY' => $newKey,
                                        ]);

                                        \Filament\Notifications\Notification::make()
                                            ->title(__('API Key Rotated!'))
                                            ->success()
                                            ->body(__('The new key has been generated and synchronized with your node.'))
                                            ->send();

                                        // 3. Update the form state
                                        $this->form->fill(['app_api_key' => $newKey]);
                                    })
                            ),
                    ])->columns(2),

                Section::make(__('API Access (External Integration)'))
                    ->description(__('Use these credentials to access your data via third-party apps (PowerBI, Looker, etc.)'))
                    ->visible(function () {
                        $tier = Filament::getTenant()->billingProfile?->tier?->value;
                        return in_array($tier, ['free', 'pro']);
                    })
                    ->schema([
                        \Filament\Forms\Components\Placeholder::make('upgrade_required')
                            ->label('')
                            ->content(new \Illuminate\Support\HtmlString('
                                <div class="p-4 bg-warning-50 dark:bg-warning-500/10 rounded-xl text-warning-600 dark:text-warning-400 border border-warning-200 dark:border-warning-500/20">
                                    <div class="flex items-center gap-3 mb-2">
                                        <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                                        </svg>
                                        <h3 class="font-bold">' . __('Upgrade Required') . '</h3>
                                    </div>
                                    <p class="text-sm mb-3">' . __('API Access is exclusively available on Ultra and Enterprise tiers. Please upgrade your associated billing profile to one of these tiers to unlock external integration capabilities.') . '</p>
                                    ' . (Filament::getTenant()->billingProfile?->user_id === auth()->id() ? '
                                    <a href="/account/account-subscription?profile=' . Filament::getTenant()->billingProfile?->id . '" class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-white bg-warning-600 border border-transparent rounded-lg shadow-sm hover:bg-warning-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-warning-500">
                                        ' . __('Manage Subscription') . '
                                    </a>
                                    ' : '
                                    <span class="inline-block px-3 py-1.5 text-sm font-medium text-warning-700 bg-warning-100 dark:bg-warning-500/20 dark:text-warning-300 rounded-lg">
                                        ' . __('Please contact the billing profile owner to upgrade the subscription.') . '
                                    </span>
                                    ') . '
                                </div>
                            '))
                    ]),
            ])
            ->statePath('data')
            ->disabled($isSuspended || ! auth()->user()->can('edit_preferences'));
    }

    public function save(RemoteEngineService $service, \App\Services\DeployerService $deployer): void
    {
        $tenant = Filament::getTenant();
        if (! $tenant->is_active || $tenant->billing_status === 'suspended') {
            Notification::make()->title(__('Action Blocked'))->body(__('The project is suspended and is in read-only mode.'))->danger()->send();

            return;
        }

        if (in_array($tenant->health_status, ['redeploying', 'syncing'])) {
            Notification::make()->title(__('Action Blocked'))->body(__('A deployment or synchronization is currently running. Please wait for it to finish.'))->warning()->send();

            return;
        }

        if (! auth()->user()->can('edit_preferences')) {
            Notification::make()->title(__('Permission Denied'))->body(__('You do not have permission to modify sync preferences.'))->danger()->send();

            return;
        }

        $this->validate();
        $data = $this->form->getState();

        $modelAttributes = [
            'public_api_key' => $data['app_api_key'] ?? $tenant->public_api_key,
            'facebook_user_token' => $data['facebook_user_token'] ?? $tenant->facebook_user_token,
            'facebook_user_id' => $data['facebook_user_id'] ?? $tenant->facebook_user_id,
            'google_refresh_token' => $data['google_refresh_token'] ?? $tenant->google_refresh_token,
            'google_user_id' => $data['google_user_id'] ?? $tenant->google_user_id,
        ];

        $syncConfig = collect($data)->except(array_keys($modelAttributes))->except(['api_url', 'app_api_key'])->toArray();
        $existingSyncConfig = is_array($tenant->sync_config) ? $tenant->sync_config : [];

        $tenant->update(array_merge($modelAttributes, [
            'sync_config' => array_merge($existingSyncConfig, $syncConfig),
        ]));

        // 1.5 Push global application logic configurations to the APIs Hub (Node)
        $service->updateCredentials($tenant, [
            'type' => 'global',
            'jobs_timeout_hours' => $data['jobs_timeout_hours'] ?? 1,
        ]);

        // 2. Push fixed Infrastructure credentials (Requires restart)
        $pushData = [
            'DB_HOST' => 'db',
            'DB_PORT' => '5432',
            'FACEBOOK_APP_ID' => config('services.facebook.client_id'),
            'FACEBOOK_APP_SECRET' => config('services.facebook.client_secret'),
            'GOOGLE_CLIENT_ID' => config('services.google.client_id'),
            'GOOGLE_CLIENT_SECRET' => config('services.google.client_secret'),
            'MONITOR_TOKEN' => $tenant->monitoring_token,
            'MONITOR_FACADE_URL' => config('app.url') . '/api/heartbeat',
            'APP_API_KEY' => $tenant->public_api_key,
            'TOKEN_AUTHORITY_ENABLED' => 'true',
            'TOKEN_AUTHORITY_URL' => config('app.url') . '/api/token-authority/refresh',
            'TOKEN_AUTHORITY_BEARER' => $tenant->public_api_key,
        ];

        $response = $deployer->updateCredentials($tenant, $pushData);

        if (($response['success'] ?? false) || ($response['status'] ?? '') === 'success') {
            Notification::make()
                ->title(__('Secure Settings Saved & Synchronized!'))
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title(__('Settings Saved Locally'))
                ->warning()
                ->body(__('Could not push credentials to your server. Please ensure the server is online.'))
                ->send();
        }
    }
}
