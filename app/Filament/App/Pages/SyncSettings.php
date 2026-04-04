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
                ->label('Check Node Status')
                ->icon('heroicon-o-shield-check')
                ->color('info')
                ->action(function (RemoteEngineService $service) {
                    $tenant = Filament::getTenant();
                    $response = $service->getStatus($tenant);
                    
                    $isOnline = ($response['success'] ?? false) || ($response['status'] ?? '') === 'success';

                    Notification::make()
                        ->title($isOnline ? 'Node is Online' : 'Node Offline')
                        ->body($isOnline ? 'Instance is responding correctly and all services are up.' : 'Could not contact the remote engine.')
                        ->status($isOnline ? 'success' : 'danger')
                        ->send();
                }),

            Action::make('stopJobs')
                ->label('Stop All Jobs')
                ->icon('heroicon-o-stop-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->action(function (RemoteEngineService $service) {
                    $tenant = Filament::getTenant();
                    $response = $service->stopJobs($tenant);
                    
                    Notification::make()
                        ->title(($response['status'] ?? '') === 'success' ? 'Jobs Stopped' : 'Command Failed')
                        ->body($response['message'] ?? '')
                        ->send();
                }),
        ];
    }

    public function mount(): void
    {
        $tenant = Filament::getTenant();
        $this->form->fill([
            ...($tenant->sync_config ?? []),
            'app_api_key' => $tenant->app_api_key,
            'facebook_user_token' => $tenant->facebook_user_token,
            'google_refresh_token' => $tenant->google_refresh_token,
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
                                    ->columnSpanFull(),
                                Select::make('fb_history_range')
                                    ->options([
                                        '6 months' => '6 Months',
                                        '1 year' => '1 Year',
                                        '2 years' => '2 Years',
                                        '5 years' => '5 Years',
                                    ])
                                    ->default('2 years'),
                            ])->columns(2),
                    ]),

                Section::make('Google Search Console (GSC)')
                    ->description('Authorize the APIs Hub Facade to fetch your GSC performance data.')
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
                                    ->columnSpanFull(),
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

                Section::make('Advanced Entity Filters')
                    ->schema([
                        TextInput::make('campaign_filter')
                            ->helperText('Regex or comma separated list of campaigns to sync.')
                            ->placeholder('.*'),
                        TextInput::make('page_filter')
                            ->helperText('Filter specific pages by ID or Regex.')
                            ->placeholder('.*'),
                    ])->collapsed(),

                Section::make('API Access (External Integration)')
                    ->description('Use these credentials to access your data via third-party apps (PowerBI, Looker, etc.)')
                    ->schema([
                        TextInput::make('api_url')
                            ->label('API Endpoint')
                            ->default('https://' . Filament::getTenant()->subdomain . '.' . config('app.network_domain') . '/api')
                            ->disabled()
                            ->suffixIcon('heroicon-m-globe-alt'),
                        TextInput::make('app_api_key')
                            ->label('Secret API Key')
                            ->password()
                            ->revealable()
                            ->helperText('Keep this key secure. It provides full access to your cached data.')
                            ->suffixIcon('heroicon-m-key'),
                    ])->columns(2),
            ])
            ->statePath('data');
    }

    public function save(RemoteEngineService $service): void
    {
        $tenant = Filament::getTenant();
        $this->validate();
        $data = $this->form->getState();
        
        $modelAttributes = [
            'remote_app_api_key' => $data['remote_app_api_key'] ?? ($data['app_api_key'] ?? null),
            'facebook_user_token' => $data['facebook_user_token'] ?? null,
            'google_refresh_token' => $data['google_refresh_token'] ?? null,
        ];

        $syncConfig = collect($data)->except(array_keys($modelAttributes))->except(['api_url', 'app_api_key'])->toArray();

        $tenant->update(array_merge($modelAttributes, [
            'sync_config' => $syncConfig,
        ]));

        // Push credentials to remote Hub node (Phase 2 & 3 Integration)
        // These master credentials are fixed in the Facade config and pushed to nodes.
        $pushData = [
            'FACEBOOK_APP_ID' => config('services.facebook.client_id'),
            'FACEBOOK_APP_SECRET' => config('services.facebook.client_secret'),
            'GOOGLE_CLIENT_ID' => config('services.google.client_id'),
            'GOOGLE_CLIENT_SECRET' => config('services.google.client_secret'),
            'FACEBOOK_USER_TOKEN' => $modelAttributes['facebook_user_token'],
            'GOOGLE_REFRESH_TOKEN' => $modelAttributes['google_refresh_token'],
            'MONITOR_TOKEN' => $tenant->monitoring_token,
            'MONITOR_FACADE_URL' => config('app.url') . '/api/heartbeat',
        ];

        $response = $service->updateCredentials($tenant, $pushData);

        if (($response['success'] ?? false) || ($response['status'] ?? '') === 'success') {
            Notification::make()
                ->title('Secure Settings Saved & Synchronized!')
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Settings Saved Locally')
                ->warning()
                ->body('Could not push credentials to remote node. Please ensure the instance is online.')
                ->send();
        }
    }
}
