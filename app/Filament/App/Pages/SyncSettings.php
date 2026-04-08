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
                    $response = $service->triggerSync($tenant);
                    
                    Notification::make()
                        ->title(($response['status'] ?? '') === 'success' ? 'Sync Started' : 'Sync Failed')
                        ->body($response['message'] ?? '')
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
                Section::make('Facebook Marketing & Organic')
                    ->description('Automatically link your Facebook accounts to start syncing pages and ads.')
                    ->schema([
                        Placeholder::make('facebook_oauth')
                            ->label('')
                            ->content(fn () => new \Illuminate\Support\HtmlString(\Illuminate\Support\Facades\Blade::render('<x-oauth-buttons provider="facebook" />'))),

                        Section::make('Advanced FB Configuration (Manual)')
                            ->collapsed()
                            ->schema([
                                Toggle::make('fb_organic_enabled')
                                    ->label('Enable FB Organic (Pages & Posts)')
                                    ->default(true),
                                Toggle::make('fb_marketing_enabled')
                                    ->label('Enable FB Marketing (Ads & Campaigns)')
                                    ->default(true),
                                TextInput::make('facebook_user_token')
                                    ->label('FB User Access Token (Manual Override)')
                                    ->password()
                                    ->revealable()
                                    ->columnSpanFull()
                                    ->hint(fn ($state) => $state ? 'Existing token detected' : null)
                                    ->hintIcon(fn ($state) => $state ? 'heroicon-m-exclamation-triangle' : null)
                                    ->hintColor('warning')
                                    ->helperText('Warning: Overriding this manually may disrupt active Explorers. Once saved, APIs Hub will automatically exchange this for a long-lived platform token and update the connection status.'),
                                TextInput::make('facebook_user_id')
                                    ->label('FB User ID (Manual Override)')
                                    ->columnSpanFull()
                                    ->hint(fn ($state) => $state ? 'Existing ID detected' : null)
                                    ->hintIcon(fn ($state) => $state ? 'heroicon-m-check-badge' : null)
                                    ->hintColor('success')
                                    ->helperText('The numerical ID of the Meta account owner. This is typically captured automatically during the connection flow.'),
                                Select::make('fb_history_range')
                                    ->options([
                                        '6 months' => '6 Months',
                                        '1 year' => '1 Year',
                                        '2 years' => '2 Years',
                                    ])
                                    ->default('2 years'),
                            ])->columns(2),
                    ]),

                Section::make('Google Search Console (GSC)')
                    ->description('Authorize APIs Hub to fetch your GSC performance data.')
                    ->schema([
                        Placeholder::make('google_oauth')
                            ->label('')
                            ->content(fn () => new \Illuminate\Support\HtmlString(\Illuminate\Support\Facades\Blade::render('<x-oauth-buttons provider="google" />'))),

                        Section::make('Advanced GSC Configuration (Manual)')
                            ->collapsed()
                            ->schema([
                                Toggle::make('gsc_enabled')
                                    ->label('Enable GSC Synchronization')
                                    ->default(true),
                                TextInput::make('google_refresh_token')
                                    ->label('Google Refresh Token (Manual Override)')
                                    ->password()
                                    ->revealable()
                                    ->columnSpanFull()
                                    ->hint(fn ($state) => $state ? 'Existing token detected' : null)
                                    ->hintIcon(fn ($state) => $state ? 'heroicon-m-exclamation-triangle' : null)
                                    ->hintColor('warning')
                                    ->helperText('Warning: Overriding this manually may disrupt active Explorers. Ensure the refresh token is valid and has long-term offline access.'),
                                TextInput::make('google_user_id')
                                    ->label('Google User ID (Manual Override)')
                                    ->columnSpanFull()
                                    ->hint(fn ($state) => $state ? 'Existing ID detected' : null)
                                    ->hintIcon(fn ($state) => $state ? 'heroicon-m-check-badge' : null)
                                    ->hintColor('success')
                                    ->helperText('The numerical ID of the Google account owner.'),
                                Select::make('gsc_history_range')
                                    ->options([
                                        '1 month' => '1 Month',
                                        '3 months' => '3 Months',
                                        '6 months' => '6 Months',
                                        '16 months' => '16 Months (Max)',
                                    ])
                                    ->default('16 months'),
                            ])->columns(2),
                    ]),

                Section::make('Advanced Filters')
                    ->description(new \Illuminate\Support\HtmlString('Use patterns to include/exclude specific resources. Need help? Use <a href="https://regex101.com/?flavor=php" target="_blank" rel="nofollow noopener noreferrer" style="color: #00A7F9; text-decoration: underline;">Regex101 (PHP flavor)</a> to build your rules.'))
                    ->schema([
                        TextInput::make('campaign_filter')
                            ->label('Campaign filter')
                            ->placeholder('/^CAMP_/i')
                            ->live(onBlur: true)
                            ->helperText(fn ($state) => $this->humanizeRegex($state, 'Campaigns that match this pattern will be synced.'))
                            ->afterStateUpdated(fn () => $this->validate()),
                        TextInput::make('page_filter')
                            ->label('Page filter')
                            ->placeholder('/[0-9]{15}/')
                            ->live(onBlur: true)
                            ->helperText(fn ($state) => $this->humanizeRegex($state, 'Pages that match this pattern will be synced.'))
                            ->afterStateUpdated(fn () => $this->validate()),
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
                                        $tenant = \Filament\Facades\Filament::getTenant();
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
            'public_api_key' => $data['app_api_key'] ?? null,
            'facebook_user_token' => $data['facebook_user_token'] ?? null,
            'facebook_user_id' => $data['facebook_user_id'] ?? null,
            'google_refresh_token' => $data['google_refresh_token'] ?? null,
            'google_user_id' => $data['google_user_id'] ?? null,
        ];

        $syncConfig = collect($data)->except(array_keys($modelAttributes))->except(['api_url', 'app_api_key'])->toArray();

        $tenant->update(array_merge($modelAttributes, [
            'sync_config' => $syncConfig,
        ]));

        // 1. Push Social Tokens (Hot-reload)
        $deployer->injectSocialTokens($tenant, [
            'facebook_user_token' => $modelAttributes['facebook_user_token'],
            'facebook_user_id' => $modelAttributes['facebook_user_id'],
            'google_refresh_token' => $modelAttributes['google_refresh_token'],
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
    protected function humanizeRegex(?string $regex, string $default): string
    {
        if (!$regex) {
            return $default;
        }

        // Basic humanizations for common patterns
        if ($regex === '.*' || $regex === '/.*/') {
            return "Sync all resources (No filtering).";
        }

        $clean = trim($regex, '/');
        $isCaseInsensitive = str_ends_with($regex, 'i');
        
        if (str_starts_with($clean, '^')) {
            $value = rtrim(substr($clean, 1), '$');
            return "Sync only resources starting with '" . $value . "'" . ($isCaseInsensitive ? " (case insensitive)." : ".");
        }

        if (str_ends_with($clean, '$')) {
            $value = ltrim(substr($clean, 0, -1), '^');
            return "Sync only resources ending with '" . $value . "'" . ($isCaseInsensitive ? " (case insensitive)." : ".");
        }

        if (!str_starts_with($regex, '/')) {
            return "Simple match: Sync resources containing '" . $regex . "'.";
        }

        return "Active pattern: Matching against '" . $clean . "'.";
    }
}
