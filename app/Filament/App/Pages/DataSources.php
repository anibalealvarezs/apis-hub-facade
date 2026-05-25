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
        $config = $tenant->sync_config ?? [];
        
        // Seed default true values for facebook_organic to ensure Livewire hydration has strict booleans
        if (isset($config['facebook_organic']['pages']) && is_array($config['facebook_organic']['pages'])) {
            foreach ($config['facebook_organic']['pages'] as &$page) {
                $page['page_metrics'] = $page['page_metrics'] ?? true;
                $page['posts'] = $page['posts'] ?? true;
                $page['post_metrics'] = $page['post_metrics'] ?? true;
                $page['ig_accounts'] = $page['ig_accounts'] ?? true;
                $page['ig_account_metrics'] = $page['ig_account_metrics'] ?? true;
                $page['ig_account_media'] = $page['ig_account_media'] ?? true;
                $page['ig_account_media_metrics'] = $page['ig_account_media_metrics'] ?? true;
            }
        }
        
        $this->form->fill($config);
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
            return $tenant->facebook_user_id !== null;
        }
        if (str_contains($channel, 'google')) {
            return $tenant->google_user_id !== null;
        }
        return false;
    }

    public function getLastSyncTime($channel): string
    {
        // To be implemented via SDK or local tenant timestamp
        return 'Never';
    }

    public function isProfileShared($channel): bool
    {
        $tenant = Filament::getTenant();
        $provider = str_contains($channel, 'facebook') ? 'facebook' : 'google';
        $profileIdColumn = "{$provider}_profile_id";

        if (!$tenant->{$profileIdColumn}) {
            return false;
        }

        // Check if there are other projects using this exact same profile ID
        $sharedCount = \App\Models\Project::where($profileIdColumn, $tenant->{$profileIdColumn})
            ->where('id', '!=', $tenant->id)
            ->count();

        return $sharedCount > 0;
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
                if ($this->activeChannel === 'facebook_organic') {
                    $local['page_metrics'] = $local['page_metrics'] ?? true;
                    $local['posts'] = $local['posts'] ?? true;
                    $local['post_metrics'] = $local['post_metrics'] ?? true;
                    $local['ig_accounts'] = $local['ig_accounts'] ?? true;
                    $local['ig_account_metrics'] = $local['ig_account_metrics'] ?? true;
                    $local['ig_account_media'] = $local['ig_account_media'] ?? true;
                    $local['ig_account_media_metrics'] = $local['ig_account_media_metrics'] ?? true;
                }
                
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
            
            if ($this->activeChannel === 'facebook_organic') {
                $live['page_metrics'] = true;
                $live['posts'] = true;
                $live['post_metrics'] = true;
                $live['ig_accounts'] = true;
                $live['ig_account_metrics'] = true;
                $live['ig_account_media'] = true;
                $live['ig_account_media_metrics'] = true;
            }
            
            $mergedAssets[$identifier] = $live;
        }

        // Get the entire DB state so we don't accidentally wipe out other channels not currently on the screen
        $fullDbState = $tenant->sync_config ?? [];
        
        \Illuminate\Support\Arr::set($currentData, $this->activeChannel . '.' . $assetListKey, array_values($mergedAssets));
        
        // Merge the active channel's data back into the full DB state
        $fullDbState[$this->activeChannel] = \Illuminate\Support\Arr::get($currentData, $this->activeChannel, []);
        
        $tenant->update(['sync_config' => $fullDbState]); // Persist full dataset immediately to preserve unmapped keys
        
        // Fill the form with the updated active channel data
        $this->form->fill($fullDbState);
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
        $schema = $this->buildComponentsFromSchema($fields, $this->activeChannel . '.');
        
        if ($this->activeChannel === 'facebook_marketing') {
            // Insert custom extraction granularity UI at the top
            array_unshift($schema, \Filament\Forms\Components\Section::make('Extraction Granularity')
                ->schema([
                    \Filament\Forms\Components\Select::make($this->activeChannel . '.entity_sync_depth')
                        ->label('ENTITY SYNC DEPTH (INFRASTRUCTURE)')
                        ->options([
                            'ACCOUNT' => 'Level 1: Account',
                            'CAMPAIGN' => 'Level 2: Campaigns',
                            'ADSET' => 'Level 3: Adsets',
                            'AD' => 'Level 4: Ads',
                        ])
                        ->default('AD')
                        ->live()
                        ->helperText('Select the deepest level of entities you want to sync from Facebook.'),
                    
                    \Filament\Forms\Components\Select::make($this->activeChannel . '.metrics_level')
                        ->label('METRICS LEVEL (REPORTING)')
                        ->options(function (\Filament\Forms\Get $get) {
                            $entityDepth = $get('facebook_marketing.entity_sync_depth') ?? 'AD';
                            $allOptions = [
                                'ACCOUNT' => 'Level 1: Account Metrics',
                                'CAMPAIGN' => 'Level 2: Campaign Metrics',
                                'ADSET' => 'Level 3: Adset Metrics',
                                'AD' => 'Level 4: Ad Metrics',
                            ];
                            
                            $levels = ['ACCOUNT' => 1, 'CAMPAIGN' => 2, 'ADSET' => 3, 'AD' => 4];
                            $maxLevel = $levels[$entityDepth] ?? 4;
                            
                            return array_filter($allOptions, fn($k) => $levels[$k] <= $maxLevel, ARRAY_FILTER_USE_KEY);
                        })
                        ->default('AD')
                        ->helperText('Metrics cannot be synced at a deeper depth than the entity sync depth.'),
                ])->columns(2));
        }

        return $schema;
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
        if ($this->activeChannel === 'facebook_organic') {
            return $this->buildFacebookOrganicRepeater($fieldKey, $label);
        }

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

    protected function buildFacebookOrganicRepeater(string $fieldKey, string $label): Repeater
    {
        $headerComponents = [];
        
        // Hidden fields required for payload integrity
        foreach (['id', 'url', 'title', 'name', 'hostname', 'created_time', 'link', 'ig_account', 'ig_account_name', 'ig_hostname', 'ig_created_time', 'data', 'ig_data', 'lost_access'] as $hk) {
            $headerComponents[] = \Filament\Forms\Components\Hidden::make($hk);
        }
        
        // Force exclude_from_caching to false per requirements
        $headerComponents[] = \Filament\Forms\Components\Hidden::make('exclude_from_caching')->default(false);

        // Header View: Logo, Title, ID, Link, and Main Toggle
        $headerComponents[] = Grid::make(12)->schema([
            \Filament\Forms\Components\Placeholder::make('asset_info')
                ->label('')
                ->content(fn (callable $get) => new \Illuminate\Support\HtmlString('
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-lg bg-gray-100 dark:bg-gray-800 flex items-center justify-center text-gray-500">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"></path></svg>
                        </div>
                        <div>
                            <div class="font-bold text-lg leading-tight">'.($get('title') ?? $get('name') ?? 'Unknown Asset').'</div>
                            <div class="text-xs text-gray-500 mt-1 flex items-center gap-3">
                                <span><span class="text-gray-400">ID:</span> '.$get('id').'</span>
                                <a href="'.$get('link').'" target="_blank" class="text-primary-500 hover:underline">'.$get('link').'</a>
                            </div>
                        </div>
                    </div>
                '))
                ->columnSpan(11),
            
            Toggle::make('enabled')
                ->label('')
                ->inline(false)
                ->default(true)
                ->live()
                ->columnSpan(1)
                ->extraAttributes(['style' => 'margin-top: auto; margin-bottom: auto; display: flex; justify-content: flex-end;']),
        ]);

        $itemComponents = [
            Grid::make(2)->schema([
                // LEFT COLUMN: Facebook Extraction
                Section::make('FACEBOOK EXTRACTION')
                    ->schema([
                        Toggle::make('page_metrics')
                            ->label('Page Metrics')
                            ->inline(false)
                            ->default(true),
                        Toggle::make('posts')
                            ->label('Posts Content')
                            ->inline(false)
                            ->default(true)
                            ->live()
                            ->afterStateUpdated(function (\Filament\Forms\Get $get, \Filament\Forms\Set $set, $state) {
                                if (!(bool) $state) {
                                    $set('post_metrics', false);
                                }
                            }),
                        Toggle::make('post_metrics')
                            ->label('Post Insights')
                            ->inline(false)
                            ->default(true)
                            ->extraAttributes(['class' => 'ml-8'])
                            ->visible(fn (\Filament\Forms\Get $get): bool => (bool) $get('posts'))
                            ->dehydrated(),
                    ])
                    ->columnSpan(1)
                    ->compact(),

                // RIGHT COLUMN: Instagram Extraction
                Section::make(fn (\Filament\Forms\Get $get) => $get('ig_account_name') ? mb_strtoupper($get('ig_account_name')) : 'INSTAGRAM EXTRACTION')
                    ->schema([
                        Toggle::make('ig_accounts')
                            ->label('Sync Instagram')
                            ->inline(false)
                            ->default(true)
                            ->live()
                            ->afterStateUpdated(function (\Filament\Forms\Get $get, \Filament\Forms\Set $set, $state) {
                                if (!(bool) $state) {
                                    $set('ig_account_metrics', false);
                                    $set('ig_account_media', false);
                                    $set('ig_account_media_metrics', false);
                                }
                            }),
                        Toggle::make('ig_account_metrics')
                            ->label('Account Metrics')
                            ->inline(false)
                            ->default(true)
                            ->extraAttributes(['class' => 'ml-8'])
                            ->visible(fn (\Filament\Forms\Get $get): bool => (bool) $get('ig_accounts'))
                            ->dehydrated(),
                        Toggle::make('ig_account_media')
                            ->label('Media Content')
                            ->inline(false)
                            ->default(true)
                            ->live()
                            ->extraAttributes(['class' => 'ml-8'])
                            ->afterStateUpdated(function (\Filament\Forms\Get $get, \Filament\Forms\Set $set, $state) {
                                if (!(bool) $state) {
                                    $set('ig_account_media_metrics', false);
                                }
                            })
                            ->visible(fn (\Filament\Forms\Get $get): bool => (bool) $get('ig_accounts'))
                            ->dehydrated(),
                        Toggle::make('ig_account_media_metrics')
                            ->label('Media Insights')
                            ->inline(false)
                            ->default(true)
                            ->extraAttributes(['class' => 'ml-16'])
                            ->visible(fn (\Filament\Forms\Get $get): bool => 
                                (bool) $get('ig_accounts') && 
                                (bool) $get('ig_account_media')
                            )
                            ->dehydrated(),
                    ])
                    ->columnSpan(1)
                    ->compact()
                    ->visible(fn (\Filament\Forms\Get $get) => !empty($get('ig_account'))),
            ])
        ];

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
                    ->visible(fn (callable $get) => $get('enabled'))
            ])
            ->grid(1) // Full width for each asset block
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
        }
        
        // We will update the tenant DB at the end of the method with the fully merged dbState
        
        // Push the configuration to the remote engine via APIs Hub SDK
        $service = app(\App\Services\RemoteEngineService::class);
        $remoteAssetKeyMap = [
            'google_search_console' => 'gsc',
            'facebook_marketing'    => 'ad_accounts',
            'facebook_organic'      => 'pages',
        ];
        
        $release = $tenant->apisHubRelease ?? \App\Models\ApisHubRelease::where('is_active', true)->first();
        $rejectedAssets = [];

        foreach ($uiState as $channel => $channelConfig) {
            if (!is_array($channelConfig)) {
                continue;
            }
            
            $fields = $release->config_schemas[$channel]['fields'] ?? [];
            $assetListKey = null;

            // Dynamically find which field is the array of assets (e.g., 'ad_accounts', 'assets.sites')
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
            
            if (!$assetListKey) {
                continue; // No assets array found for this channel
            }

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
                
                // Force global extraction granularity instructions for the worker cache
                $payload['feature_toggles'] = [
                    'page_metrics' => true,
                    'posts' => true,
                    'post_metrics' => true,
                    'ig_accounts' => true,
                    'ig_account_metrics' => true,
                    'ig_account_media' => true,
                    'ig_account_media_metrics' => true,
                ];
            } elseif ($channel === 'facebook_marketing') {
                $payload['max_workers'] = 2;
                
                // Map Custom UI to APIs Hub Payload schema
                $entLevel = strtolower($channelConfig['entity_sync_depth'] ?? 'ad');
                $metLevel = strtolower($channelConfig['metrics_level'] ?? 'ad');
                
                if ($entLevel === 'account') $entLevel = 'ad_account';
                if ($metLevel === 'account') $metLevel = 'ad_account';

                $payload['feature_toggles'] = [
                    'campaigns' => true, // API always expects this
                    'adsets' => in_array($entLevel, ['adset', 'ad', 'creative']),
                    'ads' => in_array($entLevel, ['ad', 'creative']),
                    'creatives' => ($entLevel === 'creative'),
                    
                    'ad_account_metrics' => ($metLevel === 'ad_account'),
                    'campaign_metrics' => ($metLevel === 'campaign'),
                    'adset_metrics' => ($metLevel === 'adset'),
                    'ad_metrics' => ($metLevel === 'ad'),
                    'creative_metrics' => ($metLevel === 'creative'),
                ];
                
                $payload['metrics_strategy'] = 'default';
                $payload['metrics_config'] = [];
                
                $payload['entity_filters'] = [
                    'CAMPAIGN' => '',
                    'ADSET' => '',
                    'AD' => '',
                    'CREATIVE' => '',
                ];

                unset($payload['entity_sync_depth']);
                unset($payload['metrics_level']);
            }

            // Merge UI boolean toggles back into the pristine DB state to preserve unmapped keys (id, url, data)
            $assetsListUi = array_values(\Illuminate\Support\Arr::get($channelConfig, $assetListKey, []));
            $assetsListDb = array_values(\Illuminate\Support\Arr::get($dbState[$channel] ?? [], $assetListKey, []));
            
            
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
            // Clean up the top-level list to avoid duplicate or conflicting structures
            \Illuminate\Support\Arr::forget($payload, $assetListKey);

            // Re-map the pristine assets list to the nested structure the backend drivers expect
            $payload['assets'] = [
                $remoteAssetKey => $assetsListDb
            ];

            try {
                $response = $service->updateCredentials($tenant, $payload);
                
                // If successful, we update the DB state with the merged assets so it remains the source of truth
                if (!isset($dbState[$channel])) {
                    $dbState[$channel] = [];
                }
                
                // Sync status back from Remote Node
                // Remote node may have disabled assets due to permission checks
                $remoteListKey = last(explode('.', $assetListKey));
                if (isset($response['config'][$channel][$remoteListKey])) {
                    $remoteAssets = $response['config'][$channel][$remoteListKey];
                    // Create a map by URL or ID from remote
                    $remoteMap = [];
                    foreach ($remoteAssets as $ra) {
                        $id = $ra['url'] ?? $ra['id'] ?? null;
                        if ($id) {
                            $remoteMap[$id] = $ra;
                        }
                    }
                    
                    // Update local db state with remote status
                    foreach ($assetsListDb as $index => &$dbAsset) {
                        $id = $dbAsset['url'] ?? $dbAsset['id'] ?? null;
                        if ($id && isset($remoteMap[$id])) {
                            $intendedEnabled = filter_var($assetsListUi[$index]['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN);
                            $remoteEnabled = filter_var($remoteMap[$id]['enabled'] ?? true, FILTER_VALIDATE_BOOLEAN);
                            
                            $dbAsset['enabled'] = $remoteEnabled;
                            
                            // If the user turned it on, but the remote engine turned it off, track it
                            if ($intendedEnabled && !$remoteEnabled) {
                                $assetName = $dbAsset['title'] ?? $dbAsset['name'] ?? $id;
                                $rejectedAssets[] = $assetName;
                            }
                            
                            if (isset($remoteMap[$id]['lost_access'])) {
                                $dbAsset['lost_access'] = filter_var($remoteMap[$id]['lost_access'], FILTER_VALIDATE_BOOLEAN);
                            }
                        }
                    }
                    unset($dbAsset);
                }

                
                // Persist UI configuration values (like cache_history_range and the channel toggle)
                foreach ($channelConfig as $k => $v) {
                    if (!is_array($v)) {
                        $dbState[$channel][$k] = $v;
                    }
                }
                
                \Illuminate\Support\Arr::set($dbState[$channel], $assetListKey, $assetsListDb);
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

        // Refresh UI state seamlessly via Livewire so the user sees the actual final state
        $this->form->fill($dbState);

        if (count($rejectedAssets) > 0) {
            $rejectedList = implode(', ', array_slice($rejectedAssets, 0, 5));
            if (count($rejectedAssets) > 5) {
                $rejectedList .= ' and ' . (count($rejectedAssets) - 5) . ' more';
            }
            
            \Filament\Notifications\Notification::make()
                ->title('Configuration Saved Partially')
                ->body("Some assets were automatically disabled by the remote server due to insufficient permissions or invalid state: <strong>{$rejectedList}</strong>.")
                ->warning()
                ->persistent()
                ->send();
        } else {
            \Filament\Notifications\Notification::make()
                ->title('Configuration Saved')
                ->success()
                ->send();
        }
    }
}
