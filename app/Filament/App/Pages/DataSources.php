<?php

namespace App\Filament\App\Pages;

use App\Services\RemoteEngineService;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\ViewField;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use App\Models\ApisHubRelease;
use Illuminate\Support\Str;

class DataSources extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-server-stack';
    protected static ?string $navigationLabel = 'Data Sources';
    protected static ?string $title = 'Data Sources Configuration';
    protected static string $view = 'filament.app.pages.data-sources';
    protected static ?string $slug = 'data-sources';

    public $activeChannel = 'google_search_console';
    public ?array $data = [];

    public function getMaxAssets(): int
    {
        // Placeholder for actual tier logic
        $tenant = Filament::getTenant();
        // Return 100 for now, should be based on $tenant->subscription->plan->tier
        return 100; 
    }

    public function mount()
    {
        $tenant = Filament::getTenant();
        $this->form->fill($tenant->sync_config ?? []);
    }

    public function getChannels(): array
    {
        return [
            ['key' => 'google_search_console', 'label' => 'Google Search Console'],
            ['key' => 'facebook_marketing', 'label' => 'Facebook Marketing'],
            ['key' => 'facebook_organic', 'label' => 'Facebook Organic'],
        ];
    }

    public function isConnected($channel): bool
    {
        $tenant = Filament::getTenant();
        if (str_contains($channel, 'facebook')) {
            return !empty($tenant->facebook_user_token);
        }
        if (str_contains($channel, 'google')) {
            return !empty($tenant->google_refresh_token);
        }
        return false;
    }

    public function getLastSyncTime($channel): string
    {
        // To be implemented via SDK or local tenant timestamp
        return 'Never';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('discoverAssets')
                ->label('Refresh / Discover')
                ->icon('heroicon-o-arrow-path')
                ->action(function (RemoteEngineService $service) {
                    $tenant = Filament::getTenant();
                    $response = $service->fetchAssets($tenant, $this->activeChannel, true);
                    
                    if (isset($response['success']) && $response['success'] && isset($response['assets'])) {
                        $this->mergeDiscoveredAssets($response['assets']);
                        Notification::make()->title('Assets Refreshed')->success()->send();
                    } else {
                        Notification::make()->title('Refresh Failed')
                            ->danger()
                            ->body(is_array($response) ? ($response['error'] ?? 'Unknown error') : 'Invalid response')
                            ->send();
                    }
                }),

            Action::make('updateCredentials')
                ->label('Update Permissions')
                ->icon('heroicon-o-key')
                ->visible(fn () => $this->isConnected($this->activeChannel))
                ->requiresConfirmation()
                ->modalHeading('Update Credentials Safely')
                ->modalDescription('To update these credentials, we must first safely stop active synchronizations. This process can take up to 2 hours. We will send you a notification when it is safe to re-authorize.')
                ->action(function () {
                    $tenant = Filament::getTenant();
                    $provider = str_contains($this->activeChannel, 'facebook') ? 'facebook' : 'google';
                    
                    \App\Jobs\PrepareSafeTokenUpdateJob::dispatch($tenant, $provider);
                    
                    Notification::make()
                        ->title('Safe Update Initiated')
                        ->body('We are safely pausing your workers. You will be notified when it is safe to proceed.')
                        ->warning()
                        ->send();
                })
        ];
    }

    protected function mergeDiscoveredAssets(array $liveAssets): void
    {
        $tenant = Filament::getTenant();
        $release = $tenant->apisHubRelease ?? ApisHubRelease::where('is_active', true)->first();
        if (!$release) return;

        $fields = $release->config_schemas[$this->activeChannel]['fields'] ?? [];
        $assetListKey = null;

        // Find which field is the array of assets (e.g., 'ad_accounts', 'pages')
        foreach ($fields as $key => $def) {
            if (($def['type'] ?? '') === 'array' && isset($def['item_schema'])) {
                $assetListKey = $key;
                break;
            } elseif (($def['type'] ?? '') === 'object' && isset($def['schema'])) {
                foreach ($def['schema'] as $subKey => $subDef) {
                    if (($subDef['type'] ?? '') === 'array' && isset($subDef['item_schema'])) {
                        $assetListKey = $key . '.' . $subKey;
                        break 2;
                    }
                }
            }
        }

        if (!$assetListKey) return;

        $currentData = $this->form->getState();
        $localAssets = \Illuminate\Support\Arr::get($currentData, $this->activeChannel . '.' . $assetListKey, []);
        
        $mergedAssets = [];
        $liveMap = [];
        
        // Build map of live assets by their ID or URL
        foreach ($liveAssets as $live) {
            $identifier = $live['id'] ?? $live['url'] ?? null;
            if ($identifier) {
                $liveMap[$identifier] = $live;
            }
        }

        // Process existing local assets
        foreach ($localAssets as $local) {
            $identifier = $local['id'] ?? $local['url'] ?? null;
            if ($identifier) {
                if (isset($liveMap[$identifier])) {
                    // Still alive, merge new data over it but keep user settings
                    $merged = array_merge($local, $liveMap[$identifier]);
                    $merged['lost_access'] = false;
                    $mergedAssets[$identifier] = $merged;
                    unset($liveMap[$identifier]);
                } else {
                    // It was local but not in live anymore -> lost access!
                    $local['lost_access'] = true;
                    $mergedAssets[$identifier] = $local;
                }
            }
        }

        // Add remaining brand new live assets
        foreach ($liveMap as $identifier => $live) {
            $live['lost_access'] = false;
            $live['enabled'] = false; // Default to false so user has to explicitly enable
            $mergedAssets[$identifier] = $live;
        }

        \Illuminate\Support\Arr::set($currentData, $this->activeChannel . '.' . $assetListKey, array_values($mergedAssets));
        $this->form->fill($currentData);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema($this->getDynamicSchema())
            ->statePath('data');
    }

    protected function getDynamicSchema(): array
    {
        $tenant = Filament::getTenant();
        $release = $tenant->apisHubRelease ?? ApisHubRelease::where('is_active', true)->first();
        
        if (!$release || empty($release->config_schemas[$this->activeChannel]['fields'])) {
            return [
                Toggle::make($this->activeChannel . '_enabled')
                    ->label('Enable Channel')
                    ->default(true),
            ];
        }

        $fields = $release->config_schemas[$this->activeChannel]['fields'];
        return $this->buildComponentsFromSchema($fields, $this->activeChannel . '.');
    }

    protected function buildComponentsFromSchema(array $schema, string $prefix = ''): array
    {
        $components = [];
        $advancedComponents = [];
        $repeaters = [];

        foreach ($schema as $key => $definition) {
            $type = $definition['type'] ?? 'string';
            $fieldKey = $prefix . $key;
            
            // Channel-level toggle
            if ($key === 'enabled') {
                $components[] = Toggle::make($fieldKey)
                    ->label('Enable ' . collect($this->getChannels())->firstWhere('key', $this->activeChannel)['label'])
                    ->default($definition['default'] ?? true)
                    ->columnSpanFull();
                continue;
            }

            if ($type === 'boolean') {
                $advancedComponents[] = Toggle::make($fieldKey)
                    ->label(Str::headline($key))
                    ->default($definition['default'] ?? false);
            } elseif ($type === 'string' && str_contains($key, 'range')) {
                $advancedComponents[] = Select::make($fieldKey)
                    ->label(Str::headline($key))
                    ->options([
                        '1 month' => '1 Month',
                        '3 months' => '3 Months',
                        '6 months' => '6 Months',
                        '1 year' => '1 Year',
                        '2 years' => '2 Years',
                    ])
                    ->default($definition['default'] ?? '2 years');
            } elseif ($type === 'integer') {
                $advancedComponents[] = TextInput::make($fieldKey)
                    ->numeric()
                    ->label(Str::headline($key))
                    ->default($definition['default'] ?? null);
            } elseif ($type === 'array' && isset($definition['item_schema'])) {
                // This is the Assets array (Repeater)
                $repeaters[] = $this->buildAssetRepeater($fieldKey, $key, $definition['item_schema']);
            } elseif ($type === 'object' && isset($definition['schema'])) {
                // Nested object (like Google Search Console's assets->sites)
                $repeaters = array_merge($repeaters, $this->buildComponentsFromSchema($definition['schema'], $fieldKey . '.'));
            }
        }

        $result = $components;

        if (!empty($repeaters)) {
            $result = array_merge($result, $repeaters);
        }

        if (!empty($advancedComponents)) {
            $result[] = Section::make('Advanced Configuration')
                ->collapsed()
                ->schema(array_values($advancedComponents))
                ->columns(2);
        }

        return $result;
    }

    protected function buildAssetRepeater(string $fieldKey, string $label, array $itemSchema): Repeater
    {
        $itemComponents = [];
        $headerComponents = [];

        foreach ($itemSchema as $key => $definition) {
            $type = $definition['type'] ?? 'string';
            
            // Skip data objects
            if ($type === 'object' || in_array($key, ['id', 'url', 'title', 'name', 'hostname', 'created_time', 'link'])) {
                continue;
            }

            if ($key === 'enabled') {
                $headerComponents[] = Toggle::make($key)
                    ->label('Sync')
                    ->default(true);
            } elseif ($key === 'lost_access') {
                $headerComponents[] = Toggle::make($key)
                    ->label('Lost Access')
                    ->disabled()
                    ->helperText('This asset is no longer accessible via the API.');
            } elseif ($type === 'boolean') {
                $itemComponents[] = Toggle::make($key)
                    ->label(Str::headline($key))
                    ->default($definition['default'] ?? false);
            }
        }

        return Repeater::make($fieldKey)
            ->label(Str::headline($label))
            ->schema([
                Grid::make(3)->schema($headerComponents),
                Grid::make(3)->schema($itemComponents)->visible(fn ($state) => !empty($itemComponents))
            ])
            ->collapsible()
            ->collapsed(true)
            ->addable(false)
            ->deletable(false)
            ->reorderable(false)
            ->itemLabel(fn (array $state): ?string => $state['title'] ?? $state['name'] ?? $state['url'] ?? 'Unknown Asset')
            ->columnSpanFull();
    }

    public function save(): void
    {
        $tenant = Filament::getTenant();
        $state = $this->form->getState();
        
        // Validate limits before saving
        $totalEnabled = 0;
        foreach ($state as $channel => $channelConfig) {
            if (is_array($channelConfig)) {
                foreach ($channelConfig as $key => $value) {
                    if (is_array($value)) {
                        // It's the assets array
                        foreach ($value as $asset) {
                            if (!empty($asset['enabled']) && empty($asset['lost_access'])) {
                                $totalEnabled++;
                            }
                        }
                    }
                }
            }
        }

        $max = $this->getMaxAssets();
        if ($totalEnabled > $max) {
            Notification::make()
                ->title('Asset Limit Exceeded')
                ->danger()
                ->body("You have selected {$totalEnabled} assets, but your current tier limits you to {$max}. Please deselect some assets or upgrade your plan.")
                ->send();
            return;
        }

        $tenant->update(['sync_config' => $state]);
        Notification::make()->title('Configuration Saved')->success()->send();
    }
}
