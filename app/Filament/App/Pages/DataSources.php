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
use Filament\Support\Enums\MaxWidth;
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

    public function getMaxContentWidth(): MaxWidth | string | null
    {
        return MaxWidth::Full;
    }

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
                        // Hardcode correct resource keys for extraction since services.php is generic
                        $resourceKeyMap = [
                            'google_search_console' => 'sites',
                            'facebook_marketing' => 'ad_accounts',
                            'facebook_organic' => 'pages',
                            'shopify' => 'stores'
                        ];
                        $resourceKey = $resourceKeyMap[$this->activeChannel] ?? $this->activeChannel;
                        
                        $liveAssets = $response['assets'][$resourceKey] ?? [];

                        // If still empty but the root assets has items, fallback to the first array found
                        if (empty($liveAssets) && !empty($response['assets'])) {
                            foreach ($response['assets'] as $key => $value) {
                                if (is_array($value)) {
                                    $liveAssets = $value;
                                    $resourceKey = $key; // Update the key so we wrap it correctly below
                                    break;
                                }
                            }
                        }

                        // Wrap it back in an associative array so mergeDiscoveredAssets can extract it properly
                        $payload = [$resourceKey => $liveAssets];

                        $this->mergeDiscoveredAssets($payload);
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
                    
                    \App\Jobs\PrepareSafeTokenUpdateJob::dispatch($tenant, $provider, auth()->id());
                    
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
        
        // Extract the actual list of assets from the associative array (e.g. ['sites' => [...]] or ['ad_accounts' => [...]])
        $actualLiveAssets = [];
        foreach ($liveAssets as $key => $value) {
            if (is_array($value)) {
                $actualLiveAssets = $value;
                break;
            }
        }

        // Build map of live assets by their ID or URL
        foreach ($actualLiveAssets as $live) {
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
        $tenant->update(['sync_config' => $currentData]); // Persist full dataset immediately to preserve unmapped keys
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
            
            // Skip system fields
            if (isset($definition['user_configurable']) && $definition['user_configurable'] === false) {
                continue;
            }

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
            } elseif ($type === 'string' && isset($definition['options'])) {
                $advancedComponents[] = Select::make($fieldKey)
                    ->label(Str::headline($key))
                    ->options($definition['options'])
                    ->default($definition['default'] ?? null);
            } elseif ($type === 'string') {
                $advancedComponents[] = TextInput::make($fieldKey)
                    ->label(Str::headline($key))
                    ->default($definition['default'] ?? null);
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
            
            // Preserve data objects and identifying strings in the form state invisibly
            if ($type === 'object' || in_array($key, ['id', 'url', 'title', 'name', 'hostname', 'created_time', 'link', 'ig_account', 'ig_account_name', 'ig_hostname', 'ig_created_time'])) {
                $headerComponents[] = \Filament\Forms\Components\Hidden::make($key);
                continue;
            }

            if ($key === 'enabled') {
                $headerComponents[] = Toggle::make($key)
                    ->label(fn (callable $get) => $get('title') ?? $get('name') ?? $get('url') ?? 'Unknown Asset')
                    ->helperText(fn (callable $get) => $get('lost_access') ? '⚠️ Lost Access: This asset is no longer accessible via the API.' : null)
                    ->inline(false)
                    ->default(true);
            } elseif ($key === 'lost_access') {
                // Hidden entirely, we use the helperText on the enabled toggle instead.
                $headerComponents[] = \Filament\Forms\Components\Hidden::make($key);
            } elseif ($type === 'boolean') {
                $itemComponents[] = Toggle::make($key)
                    ->label(Str::headline($key))
                    ->inline(false)
                    ->default($definition['default'] ?? false);
            }
        }

        return Repeater::make($fieldKey)
            ->label(Str::headline($label))
            ->hintActions([
                \Filament\Forms\Components\Actions\Action::make('selectAll')
                    ->label('Select All')
                    ->button()
                    ->color('success')
                    ->action(function (\Filament\Forms\Components\Repeater $component) {
                        $state = $component->getState();
                        $newState = collect($state)->map(function ($item) {
                            if (empty($item['lost_access'])) {
                                $item['enabled'] = true;
                            }
                            return $item;
                        })->toArray();
                        $component->state($newState);
                    }),
                \Filament\Forms\Components\Actions\Action::make('deselectAll')
                    ->label('Deselect All')
                    ->button()
                    ->color('danger')
                    ->action(function (\Filament\Forms\Components\Repeater $component) {
                        $state = $component->getState();
                        $newState = collect($state)->map(function ($item) {
                            $item['enabled'] = false;
                            return $item;
                        })->toArray();
                        $component->state($newState);
                    })
            ])
            ->schema([
                Grid::make(1)->schema($headerComponents),
                Grid::make(1)->schema($itemComponents)
                    ->visible(fn (callable $get) => $get('enabled') && !empty($itemComponents))
            ])
            ->grid(3)
            ->collapsible(false)
            ->addable(false)
            ->deletable(false)
            ->reorderable(false)
            ->columnSpanFull();
    }

    public function save(): void
    {
        $tenant = Filament::getTenant();
        $uiState = $this->form->getState();
        $dbState = $tenant->sync_config ?? [];
        
        // Validate limits before saving
        $totalEnabled = 0;
        foreach ($uiState as $channel => $channelConfig) {
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
        // We will update the tenant DB at the end of the method with the fully merged dbState
        
        // Push the configuration to the remote engine via APIs Hub SDK
        $service = app(\App\Services\RemoteEngineService::class);
        $localAssetKeyMap = [
            'google_search_console' => 'sites',
            'facebook_marketing'    => 'ad_accounts',
            'facebook_organic'      => 'facebook_pages',
        ];
        $remoteAssetKeyMap = [
            'google_search_console' => 'gsc',
            'facebook_marketing'    => 'ad_accounts',
            'facebook_organic'      => 'pages',
        ];

        foreach ($uiState as $channel => $channelConfig) {
            if (!is_array($channelConfig)) {
                continue;
            }

            $localAssetKey = $localAssetKeyMap[$channel] ?? 'assets';
            $remoteAssetKey = $remoteAssetKeyMap[$channel] ?? 'assets';

            $payload = $channelConfig;
            $payload['type'] = $channel;
            
            // Map the correct 'enabled' state from the toggle name
            $payload['enabled'] = filter_var($channelConfig[$channel . '_enabled'] ?? $channelConfig['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN);
            unset($payload[$channel . '_enabled']);

            // Enforce Global Defaults for Jobs
            $payload['granular_sync'] = true;
            if ($channel === 'google_search_console') {
                $payload['max_workers'] = 4;
            } elseif ($channel === 'facebook_organic') {
                $payload['max_workers'] = 1;
            } elseif ($channel === 'facebook_marketing') {
                $payload['max_workers'] = 2;
            }

            // Merge UI boolean toggles back into the pristine DB state to preserve unmapped keys (id, url, data)
            $assetsListUi = array_values($channelConfig[$localAssetKey] ?? []);
            $assetsListDb = array_values($dbState[$channel][$localAssetKey] ?? []);
            
            foreach ($assetsListUi as $index => $uiAsset) {
                if (isset($assetsListDb[$index])) {
                    // Update any boolean toggles (like enabled, page_metrics, etc) from UI into the pristine DB asset
                    foreach ($uiAsset as $key => $val) {
                        if (is_bool($val)) {
                            $assetsListDb[$index][$key] = $val;
                        }
                    }
                }
            }
            
            // Re-map the pristine assets list to the nested structure the backend drivers expect
            $payload['assets'] = [
                $remoteAssetKey => $assetsListDb
            ];
            
            // Clean up the top-level list
            unset($payload[$localAssetKey]);

            try {
                $service->updateCredentials($tenant, $payload);
                // If successful, we update the DB state with the merged assets so it remains the source of truth
                $dbState[$channel][$localAssetKey] = $assetsListDb;
            } catch (\Exception $e) {
                \Filament\Notifications\Notification::make()
                    ->title("Failed to sync {$channel} to remote engine")
                    ->body($e->getMessage())
                    ->danger()
                    ->send();
                return;
            }
        }
        
        $tenant->update(['sync_config' => $dbState]);

        \Filament\Notifications\Notification::make()
            ->title('Configuration Saved')
            ->success()
            ->send();
    }
}
