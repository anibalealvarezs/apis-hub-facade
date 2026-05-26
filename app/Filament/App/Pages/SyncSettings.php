<?php

namespace App\Filament\App\Pages;

use App\Services\RemoteEngineService;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\View;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class SyncSettings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationLabel = 'Synchronization Settings';
    protected static string $view = 'filament.app.pages.sync-settings';
    protected static ?string $slug = 'sync-settings';

    public ?array $data = [];
    public bool $isSyncable = false;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('triggerSync')
                ->label('Run Sync Now')
                ->icon('heroicon-o-arrow-path')
                ->color('success')
                ->requiresConfirmation()
                ->action(function (RemoteEngineService $service) {
                    $tenant = Filament::getTenant();
                    $response = $service->startSync($tenant);
                    
                    Notification::make()
                        ->title(($response['status'] ?? '') === 'success' ? 'Synchronization Sequence Started' : 'Sync Deployment Failed')
                        ->body($response['message'] ?? 'Applying configuration, restarting workers, and scheduling initial jobs.')
                        ->send();
                }),

            Action::make('checkStatus')
                ->label('Verify Server Health')
                ->icon('heroicon-o-shield-check')
                ->color('info')
                ->action(function (RemoteEngineService $service) {
                    $tenant = Filament::getTenant();
                    $response = $service->getStatus($tenant);
                    
                    $isOnline = ($response['success'] ?? false) || ($response['status'] ?? '') === 'success';

                    Notification::make()
                        ->title($isOnline ? 'Server is Online' : 'Server Unreachable')
                        ->body($isOnline ? 'Your dedicated server is responding correctly and all services are up.' : 'Could not reach your dedicated server. Please try again in a few minutes.')
                        ->status($isOnline ? 'success' : 'danger')
                        ->send();
                }),

            Action::make('stopJobs')
                ->label('Pause All Explorers')
                ->icon('heroicon-o-stop-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->action(function (RemoteEngineService $service) {
                    $tenant = Filament::getTenant();
                    $response = $service->stopJobs($tenant);
                    
                    Notification::make()
                        ->title(($response['status'] ?? '') === 'success' ? 'Explorers are resting' : 'Action Failed')
                        ->body($response['message'] ?? '')
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
            
            if (($fbData['status'] ?? '') === 'valid' && !empty($fbData['access_token'])) {
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
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Global Processing Settings')
                    ->description('Configure how long your dedicated explorers should wait before considering a synchronization job as stuck.')
                    ->schema([
                        Select::make('jobs_timeout_hours')
                            ->label('Jobs Timeout (Hours)')
                            ->options([
                                '1' => '1 Hour (Recommended)',
                                '2' => '2 Hours',
                                '6' => '6 Hours',
                                '12' => '12 Hours',
                            ])
                            ->default('1')
                            ->helperText('If a job takes longer than this duration, it will be automatically aborted to free up resources.'),
                    ])->collapsed(),

                Section::make('API Access (External Integration)')
                    ->description('Use these credentials to access your data via third-party apps (PowerBI, Looker, etc.)')
                    ->schema([
                        TextInput::make('api_url')
                            ->label('API Endpoint')
                            ->formatStateUsing(fn () => 'https://' . Filament::getTenant()->subdomain . '.' . (config('app.network_domain') ?: 'apis-hub.cloud') . '/api')
                            ->disabled()
                            ->suffixIcon('heroicon-m-globe-alt'),
                        TextInput::make('app_api_key')
                            ->label('Secret API Key')
                            ->password()
                            ->revealable()
                            ->disabled()
                            ->helperText('Keep this key secure. It provides full access to your cached data.')
                            ->hintAction(
                                \Filament\Forms\Components\Actions\Action::make('rotateKey')
                                    ->icon('heroicon-m-arrow-path')
                                    ->color('warning')
                                    ->requiresConfirmation()
                                    ->modalHeading('Rotate API Key?')
                                    ->modalDescription('Generating a new key will immediately invalidate the current one. You must update all your external integrations (PowerBI, Looker, etc.) with the new key.')
                                    ->modalSubmitActionLabel('Yes, rotate and push')
                                    ->action(function (\App\Services\DeployerService $deployer) {
                                        $tenant = Filament::getTenant();
                                        $newKey = bin2hex(random_bytes(32));
                                        
                                        // 1. Save locally
                                        $tenant->update(['public_api_key' => $newKey]);
                                        
                                        // 2. Push to remote server via SSH
                                        $deployer->updateCredentials($tenant, [
                                            'APP_API_KEY' => $newKey
                                        ]);

                                        \Filament\Notifications\Notification::make()
                                            ->title('API Key Rotated!')
                                            ->success()
                                            ->body('The new key has been generated and synchronized with your node.')
                                            ->send();

                                        // 3. Update the form state
                                        $this->form->fill(['app_api_key' => $newKey]);
                                    })
                            ),
                    ])->columns(2),
            ])
            ->statePath('data');
    }

    public function save(RemoteEngineService $service, \App\Services\DeployerService $deployer): void
    {
        $tenant = Filament::getTenant();
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

        $tenant->update(array_merge($modelAttributes, [
            'sync_config' => $syncConfig,
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
                ->title('Secure Settings Saved & Synchronized!')
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Settings Saved Locally')
                ->warning()
                ->body('Could not push credentials to your server. Please ensure the server is online.')
                ->send();
        }
    }

}
