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

    public ?string $activeChannel = null;
    public ?array $data = [];

    public function getLockedAssets(): array
    {
        $tenant = Filament::getTenant();
        return \App\Models\AssetBillingLock::where('project_id', $tenant->id)
            ->where('status', '!=', 'staged')
            ->pluck('asset_identifier')
            ->toArray();
    }

    public function getAssetLockStates(): array
    {
        $tenant = Filament::getTenant();
        return \App\Models\AssetBillingLock::where('project_id', $tenant->id)
            ->get()
            ->keyBy('asset_identifier')
            ->map(function ($lock) {
                return [
                    'status' => $lock->status,
                    'staged_at' => $lock->staged_at ? $lock->staged_at->toIso8601String() : null,
                    'disabled_at' => $lock->disabled_at ? $lock->disabled_at->toIso8601String() : null,
                ];
            })
            ->toArray();
    }

    public function getCycleBounds(): array
    {
        $tenant = Filament::getTenant();
        $billingProfile = $tenant->billingProfile;
        
        if (!$billingProfile) {
            return [
                'starts_at' => 'N/A',
                'ends_at' => 'N/A',
            ];
        }

        $starts = $billingProfile->current_cycle_starts_at ?? $billingProfile->created_at ?? now()->startOfMonth();
        $ends = $billingProfile->current_cycle_ends_at ?? $starts->copy()->addMonth();
        
        return [
            'starts_at' => $starts->format('M j, Y'),
            'ends_at' => $ends->format('M j, Y'),
        ];
    }

    public function getProjectDeploymentTime(): ?string
    {
        $tenant = Filament::getTenant();
        return $tenant->last_deployed_at ? $tenant->last_deployed_at->toIso8601String() : null;
    }

    public function getGlobalLedgerCount(): int
    {
        $tenant = Filament::getTenant();
        $quotaService = app(\App\Services\AssetQuotaService::class);
        $limits = $quotaService->calculateLimits($tenant, auth()->user());

        return $limits['usage'];
    }

    public function getOwnerLimit(): int
    {
        $tenant = Filament::getTenant();
        $quotaService = app(\App\Services\AssetQuotaService::class);
        $limits = $quotaService->calculateLimits($tenant, auth()->user());

        return $limits['limit'];
    }

    public function isOwner(): bool
    {
        $tenant = Filament::getTenant();
        $billingProfile = $tenant->billingProfile;
        if (!$billingProfile) {
            $ownerId = $tenant->owner_id ?? $tenant->user_id;
            return auth()->id() === $ownerId;
        }
        return auth()->id() === $billingProfile->user_id;
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
        
        // Pre-fill $this->data so that getProviders() can correctly count active assets for sorting
        $this->data = $config;

        // Dynamically set default active channel to the first one available BEFORE filling the form
        $providers = $this->getProviders();
        $firstProvider = reset($providers);
        if ($firstProvider && !empty($firstProvider['channels'])) {
            $this->activeChannel = $firstProvider['channels'][0]['key'];
        }

        // Now fill the form, which will generate the schema based on the correctly selected activeChannel
        $this->form->fill($config);
    }

    public function getChannelAssetCount(string $channelKey): int
    {
        $count = 0;
        $channelData = $this->data[$channelKey] ?? [];
        if (!is_array($channelData)) return 0;

        array_walk_recursive($channelData, function ($item, $key) use (&$count, $channelData) {
            // Because array_walk_recursive only hits leaf nodes, it's better to iterate structurally.
        });
        
        // Better structural iteration
        $scan = function($data) use (&$scan, &$count) {
            if (!is_array($data)) return;
            if (array_key_exists('enabled', $data) && (array_key_exists('id', $data) || array_key_exists('url', $data) || array_key_exists('lost_access', $data))) {
                if (!empty($data['enabled']) && empty($data['lost_access'])) {
                    $count++;
                }
                return;
            }
            foreach ($data as $val) {
                $scan($val);
            }
        };
        
        $scan($channelData);
        return $count;
    }

    public function getProviders(): array
    {
        $providers = [
            'google' => [
                'label' => 'Google',
                'channels' => [
                    ['key' => 'google_search_console', 'label' => 'Google Search Console'],
                ]
            ],
            'facebook' => [
                'label' => 'Facebook',
                'channels' => [
                    ['key' => 'facebook_marketing', 'label' => 'Facebook Marketing'],
                    ['key' => 'facebook_organic', 'label' => 'Facebook Organic'],
                ]
            ],
        ];

        // Sort channels inside providers
        foreach ($providers as $pKey => &$provider) {
            $providerCount = 0;
            foreach ($provider['channels'] as &$channel) {
                $channel['count'] = $this->getChannelAssetCount($channel['key']);
                $providerCount += $channel['count'];
            }
            $provider['count'] = $providerCount;
            
            usort($provider['channels'], function($a, $b) {
                if ($a['count'] !== $b['count']) {
                    return $b['count'] <=> $a['count']; // Higher count first
                }
                return strcmp($a['label'], $b['label']); // Then alphabetical
            });
        }
        unset($provider); // break reference

        // Sort providers
        uasort($providers, function($a, $b) {
            if ($a['count'] !== $b['count']) {
                return $b['count'] <=> $a['count'];
            }
            return strcmp($a['label'], $b['label']);
        });

        return $providers;
    }

    public function getChannelLabel(string $key): string
    {
        foreach ($this->getProviders() as $provider) {
            foreach ($provider['channels'] as $channel) {
                if ($channel['key'] === $key) {
                    return $channel['label'];
                }
            }
        }
        return 'Configuration';
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
            \Filament\Actions\Action::make('tierUsageTarget')
                ->label('')
                ->view('filament.app.actions.tier-usage-target')
        ];
    }

    public function discoverAssetsAction(): Action
    {
        return Action::make('discoverAssets')
                ->label('Refresh / Discover')
                ->icon('heroicon-o-arrow-path')
                ->disabled(fn () => !Filament::getTenant()->is_active || Filament::getTenant()->billing_status === 'suspended')
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
                });
    }

    public function updateCredentialsAction(): Action
    {
        return Action::make('updateCredentials')
                ->label('Update Permissions')
                ->icon('heroicon-o-key')
                ->visible(fn () => $this->isConnected($this->activeChannel))
                ->disabled(fn () => !Filament::getTenant()->is_active || Filament::getTenant()->billing_status === 'suspended')
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
                });
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
        $tenant = Filament::getTenant();
        $isSuspended = !$tenant->is_active || $tenant->billing_status === 'suspended';

        return $form
            ->schema($this->getDynamicSchema())
            ->statePath('data')
            ->disabled($isSuspended);
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
        $parts = $this->buildComponentsFromSchema($fields, $this->activeChannel . '.');
        
        $secondarySections = [];
        
        if ($this->activeChannel === 'facebook_marketing') {
            // Insert custom extraction granularity UI in the secondary column
            $secondarySections[] = \Filament\Forms\Components\Section::make('Extraction Granularity')
                ->schema([
                    \Filament\Forms\Components\Select::make($this->activeChannel . '.entity_sync_depth')
                        ->label('Entity Depth')
                        ->options([
                            'ACCOUNT' => 'Level 1: Account',
                            'CAMPAIGN' => 'Level 2: Campaigns',
                            'ADSET' => 'Level 3: Adsets',
                            'AD' => 'Level 4: Ads',
                        ])
                        ->default('AD')
                        ->live()
                        ->helperText('Deepest level of entities to sync.'),
                    
                    \Filament\Forms\Components\Select::make($this->activeChannel . '.metrics_level')
                        ->label('Metrics Level')
                        ->options(function (\Filament\Forms\Get $get) {
                            $entityDepth = $get('facebook_marketing.entity_sync_depth') ?? 'AD';
                            $allOptions = [
                                'ACCOUNT' => 'L1 Metrics',
                                'CAMPAIGN' => 'L2 Metrics',
                                'ADSET' => 'L3 Metrics',
                                'AD' => 'L4 Metrics',
                            ];
                            
                            $levels = ['ACCOUNT' => 1, 'CAMPAIGN' => 2, 'ADSET' => 3, 'AD' => 4];
                            $maxLevel = $levels[$entityDepth] ?? 4;
                            
                            return array_filter($allOptions, fn($k) => $levels[$k] <= $maxLevel, ARRAY_FILTER_USE_KEY);
                        })
                        ->default('AD')
                        ->helperText('Cannot exceed entity sync depth.'),
                ])->columns(1);
        }

        if ($this->activeChannel === 'google_search_console') {
            $secondarySections[] = \Filament\Forms\Components\Section::make('Data Enrichment')
                ->schema([
                    \Filament\Forms\Components\Toggle::make($this->activeChannel . '.calculate_synthetics')
                        ->label('Calculate Synthetics')
                        ->default(true)
                        ->helperText('Enable calculation of synthetic dimensions (e.g., Branded vs. Non-Branded classification).'),
                ])->columns(1);
        }

        if (!empty($parts['advanced'])) {
            $secondarySections[] = Section::make('Advanced Configuration')
                ->schema(array_values($parts['advanced']))
                ->columns(1);
        }

        if ($this->activeChannel === 'facebook_organic') {
            $secondarySections[] = \Filament\Forms\Components\Placeholder::make('fb_organic_warning')
                ->hiddenLabel()
                ->content(new \Illuminate\Support\HtmlString('
                    <style>
                        .fb-warning-box { background-color: #fffbeb; border-color: #f59e0b; padding: 1.25rem; }
                        .dark .fb-warning-box { background-color: rgba(245, 158, 11, 0.1); border-color: #f59e0b; }
                        .fb-warning-text { color: #92400e; }
                        .dark .fb-warning-text { color: #fcd34d; }
                        .fb-warning-subtext { color: #b45309; }
                        .dark .fb-warning-subtext { color: #fde68a; }
                        .fb-warning-icon { color: #d97706; }
                        .dark .fb-warning-icon { color: #fbbf24; }
                    </style>
                    <div class="rounded-r-xl border-l-4 fb-warning-box shadow-sm">
                        <div class="flex items-start gap-4">
                            <svg class="w-6 h-6 shrink-0 mt-0.5 fb-warning-icon" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <div>
                                <h3 class="text-base font-bold tracking-tight fb-warning-text">Historic Metrics Limitation</h3>
                                <p class="text-sm mt-1 leading-relaxed fb-warning-subtext">
                                    Facebook does not provide historic metrics for posts and media; it only provides daily snapshots. Therefore, we will build the history for your assets by caching the daily data to provide time series starting from today. <strong class="font-semibold fb-warning-text">To successfully build these time series without gaps, you must keep the channel and the asset enabled continuously.</strong>
                                </p>
                            </div>
                        </div>
                    </div>
                '));
        }

        $mainContent = array_merge($parts['main'], $parts['repeaters']);

        if (empty($secondarySections)) {
            return $mainContent; // Take full width if no secondary sections
        }

        return [
            Grid::make(4)
                ->schema([
                    \Filament\Forms\Components\Group::make()
                        ->schema($mainContent)
                        ->columnSpan(3),
                    
                    \Filament\Forms\Components\Group::make()
                        ->schema($secondarySections)
                        ->columnSpan(1)
                        ->extraAttributes(['class' => 'sticky top-4 self-start'])
                ])
        ];
    }

    protected function buildComponentsFromSchema(array $schema, string $prefix = ''): array
    {
        $main = [];
        $advanced = [];
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
                $main[] = Toggle::make($fieldKey)
                    ->label('Enable ' . $this->getChannelLabel($this->activeChannel))
                    ->default($definition['default'] ?? true)
                    ->columnSpanFull();
                continue;
            }

            if ($type === 'boolean') {
                $advanced[] = Toggle::make($fieldKey)
                    ->label(Str::headline($key))
                    ->default($definition['default'] ?? false);
            } elseif ($type === 'string' && isset($definition['options'])) {
                $advanced[] = Select::make($fieldKey)
                    ->label(Str::headline($key))
                    ->options($definition['options'])
                    ->default($definition['default'] ?? null);
            } elseif ($type === 'string') {
                $advanced[] = TextInput::make($fieldKey)
                    ->label(Str::headline($key))
                    ->default($definition['default'] ?? null);
            } elseif ($type === 'integer') {
                $advanced[] = TextInput::make($fieldKey)
                    ->numeric()
                    ->label(Str::headline($key))
                    ->default($definition['default'] ?? null);
            } elseif ($type === 'array' && isset($definition['item_schema'])) {
                // This is the Assets array (Repeater)
                $repeaters[] = $this->buildAssetRepeater($fieldKey, $key, $definition['item_schema']);
            } elseif ($type === 'object' && isset($definition['schema'])) {
                // Nested object (like Google Search Console's assets->sites)
                $sub = $this->buildComponentsFromSchema($definition['schema'], $fieldKey . '.');
                $main = array_merge($main, $sub['main']);
                $advanced = array_merge($advanced, $sub['advanced']);
                $repeaters = array_merge($repeaters, $sub['repeaters']);
            }
        }

        return [
            'main' => $main,
            'advanced' => $advanced,
            'repeaters' => $repeaters,
        ];
    }

    protected function buildAssetRepeater(string $fieldKey, string $label, array $itemSchema): \Filament\Forms\Components\Component
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
                    ->label(fn (callable $get) => new \Illuminate\Support\HtmlString('
                        <div class="flex items-center gap-2">
                            <span>' . e($get('title') ?? $get('name') ?? $get('url') ?? 'Unknown Asset') . '</span>
                            <template x-if="getAssetBadge(\'' . e($get('id') ?? $get('url')) . '\')">
                                <span x-html="getAssetBadge(\'' . e($get('id') ?? $get('url')) . '\')"></span>
                            </template>
                        </div>
                    '))
                    ->helperText(fn (callable $get) => new \Illuminate\Support\HtmlString(
                        $get('lost_access') ? '⚠️ Lost Access' : 'ID: <a href="' . ($get('link') ?? $get('url') ?? '#') . '" target="_blank" rel="nofollow noopener noreferrer" class="text-primary-500 hover:underline">' . ($get('id') ?? $get('url') ?? 'N/A') . '</a>'
                    ))
                    ->inline(false)
                    ->default(true)
                    ->columnSpan(4);
            } elseif ($key === 'lost_access') {
                $headerComponents[] = \Filament\Forms\Components\Hidden::make($key);
            } elseif ($type === 'boolean') {
                $itemComponents[] = Toggle::make($key)
                    ->label(Str::headline($key))
                    ->inline(true)
                    ->default($definition['default'] ?? false);
            }
        }

        $rowSchema = $headerComponents;
        if (!empty($itemComponents)) {
            $rowSchema[] = \Filament\Forms\Components\Group::make()->schema($itemComponents)
                ->extraAttributes(['class' => 'flex flex-row flex-wrap gap-4 items-center'])
                ->columnSpan(8)
                ->visible(fn (callable $get) => $get('enabled'));
        }

        return \Filament\Forms\Components\Group::make([
            \Filament\Forms\Components\Placeholder::make('filter_' . $fieldKey)
                ->hiddenLabel()
                ->content(new \Illuminate\Support\HtmlString('
                    <div class="relative w-full max-w-sm mb-4">
                        <div class="absolute inset-y-0 left-0 rtl:right-0 rtl:left-auto w-10 flex items-center justify-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <input type="text" x-model="assetFilter" class="block w-full pr-3 py-2 border border-gray-300 rounded-lg leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-primary-500 focus:border-primary-500 sm:text-sm transition duration-150 ease-in-out dark:bg-white/5 dark:border-white/10 dark:text-white" style="padding-left: 2.75rem;" placeholder="Live filter assets by name or ID...">
                    </div>
                ')),
            Repeater::make($fieldKey)
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
                    \Filament\Forms\Components\Group::make([
                        Grid::make(12)->schema($rowSchema)
                    ])->extraAttributes(function (\Filament\Forms\Get $get) {
                        $searchableText = strtolower(implode(' | ', [
                            $get('title') ?? '',
                            $get('name') ?? '',
                            $get('url') ?? '',
                            $get('id') ?? ''
                        ]));
                        $searchableText = str_replace(["'", "\\", "\n", "\r"], ["\'", "\\\\", " ", " "], $searchableText);
                        
                        return [
                            'x-effect' => "\$el.closest('li').style.display = (assetFilter === '' || '" . $searchableText . "'.includes(assetFilter.toLowerCase())) ? '' : 'none'",
                        ];
                    })
                ])
            ->grid(1)
            ->collapsible(false)
            ->addable(false)
            ->deletable(false)
            ->reorderable(false)
            ->columnSpanFull()
            ->extraAttributes(['class' => 'compact-repeater'])
        ])->extraAttributes(['x-data' => "{ assetFilter: '' }", 'class' => 'w-full']);
    }

    protected function buildFacebookOrganicRepeater(string $fieldKey, string $label): \Filament\Forms\Components\Component
    {
        $headerComponents = [];
        
        // Hidden fields required for payload integrity
        foreach (['id', 'url', 'title', 'name', 'hostname', 'created_time', 'link', 'ig_account', 'ig_account_name', 'ig_hostname', 'ig_created_time', 'data', 'ig_data', 'lost_access'] as $hk) {
            $headerComponents[] = \Filament\Forms\Components\Hidden::make($hk);
        }
        
        // Force exclude_from_caching to false per requirements
        $headerComponents[] = \Filament\Forms\Components\Hidden::make('exclude_from_caching')->default(false);

        // Header View: Name, ID as Link
        $headerComponents[] = Toggle::make('enabled')
            ->label(fn (callable $get) => new \Illuminate\Support\HtmlString('
                <div class="flex items-center gap-2">
                    <span>' . e($get('title') ?? $get('name') ?? 'Unknown Asset') . '</span>
                    <template x-if="getAssetBadge(\'' . e($get('id') ?? $get('url')) . '\')">
                        <span x-html="getAssetBadge(\'' . e($get('id') ?? $get('url')) . '\')"></span>
                    </template>
                </div>
            '))
            ->helperText(fn (callable $get) => new \Illuminate\Support\HtmlString('ID: <a href="' . $get('link') . '" target="_blank" rel="nofollow noopener noreferrer" class="text-primary-500 hover:underline">' . $get('id') . '</a>'))
            ->inline(false)
            ->default(true)
            ->live()
            ->columnSpan(4);

        $headerComponents[] = \Filament\Forms\Components\Grid::make(2)->schema([
            // Facebook Extraction Column
            \Filament\Forms\Components\Group::make()->schema([
                Toggle::make('page_metrics')->label('Page Metrics')->inline(true)->default(true),
                Toggle::make('posts')->label('Posts Content')->inline(true)->default(true)->live()
                    ->afterStateUpdated(function (\Filament\Forms\Get $get, \Filament\Forms\Set $set, $state) {
                        if (!(bool) $state) {
                            $set('post_metrics', false);
                        }
                    }),
                Toggle::make('post_metrics')->label('Post Insights')->inline(true)->default(true)
                    ->extraAttributes(['class' => 'ml-8'])
                    ->visible(fn (\Filament\Forms\Get $get): bool => (bool) $get('posts'))->dehydrated(),
            ])->extraAttributes(['class' => 'flex flex-col gap-2']),

            // Instagram Extraction Column
            \Filament\Forms\Components\Group::make()->schema([
                Toggle::make('ig_accounts')->label('Sync Instagram')->inline(true)->default(true)->live()
                    ->visible(fn (\Filament\Forms\Get $get) => !empty($get('ig_account')))
                    ->afterStateUpdated(function (\Filament\Forms\Get $get, \Filament\Forms\Set $set, $state) {
                        if (!(bool) $state) {
                            $set('ig_account_metrics', false);
                            $set('ig_account_media', false);
                            $set('ig_account_media_metrics', false);
                        }
                    }),
                Toggle::make('ig_account_metrics')->label('Account Metrics')->inline(true)->default(true)
                    ->extraAttributes(['class' => 'ml-8'])
                    ->visible(fn (\Filament\Forms\Get $get): bool => (bool) $get('ig_accounts') && !empty($get('ig_account')))->dehydrated(),
                Toggle::make('ig_account_media')->label('Media Content')->inline(true)->default(true)->live()
                    ->extraAttributes(['class' => 'ml-8'])
                    ->visible(fn (\Filament\Forms\Get $get): bool => (bool) $get('ig_accounts') && !empty($get('ig_account')))
                    ->afterStateUpdated(function (\Filament\Forms\Get $get, \Filament\Forms\Set $set, $state) {
                        if (!(bool) $state) {
                            $set('ig_account_media_metrics', false);
                        }
                    })->dehydrated(),
                Toggle::make('ig_account_media_metrics')->label('Media Insights')->inline(true)->default(true)
                    ->extraAttributes(['class' => 'ml-12'])
                    ->visible(fn (\Filament\Forms\Get $get): bool => (bool) $get('ig_accounts') && (bool) $get('ig_account_media') && !empty($get('ig_account')))->dehydrated(),
            ])->extraAttributes(['class' => 'flex flex-col gap-2']),
        ])
        ->columnSpan(8)
        ->visible(fn (callable $get) => $get('enabled'));

        return \Filament\Forms\Components\Group::make([
            \Filament\Forms\Components\Placeholder::make('filter_' . $fieldKey)
                ->hiddenLabel()
                ->content(new \Illuminate\Support\HtmlString('
                    <div class="relative w-full max-w-sm mb-4">
                        <div class="absolute inset-y-0 left-0 rtl:right-0 rtl:left-auto w-10 flex items-center justify-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <input type="text" x-model="assetFilter" class="block w-full pr-3 py-2 border border-gray-300 rounded-lg leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-primary-500 focus:border-primary-500 sm:text-sm transition duration-150 ease-in-out dark:bg-white/5 dark:border-white/10 dark:text-white" style="padding-left: 2.75rem;" placeholder="Live filter assets by name or ID...">
                    </div>
                ')),
            Repeater::make($fieldKey)
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
                    \Filament\Forms\Components\Group::make([
                        Grid::make(12)->schema($headerComponents)
                    ])->extraAttributes(function (\Filament\Forms\Get $get) {
                        $searchableText = strtolower(implode(' | ', [
                            $get('title') ?? '',
                            $get('name') ?? '',
                            $get('url') ?? '',
                            $get('id') ?? ''
                        ]));
                        $searchableText = str_replace(["'", "\\", "\n", "\r"], ["\'", "\\\\", " ", " "], $searchableText);
                        
                        return [
                            'x-effect' => "\$el.closest('li').style.display = (assetFilter === '' || '" . $searchableText . "'.includes(assetFilter.toLowerCase())) ? '' : 'none'",
                        ];
                    })
                ])
            ->grid(1)
            ->collapsible(false)
            ->addable(false)
            ->deletable(false)
            ->reorderable(false)
            ->columnSpanFull()
            ->extraAttributes(['class' => 'compact-repeater'])
        ])->extraAttributes(['x-data' => "{ assetFilter: '' }", 'class' => 'w-full']);
    }


    public function save(): void
    {
        $tenant = Filament::getTenant();
        if (!$tenant->is_active || $tenant->billing_status === 'suspended') {
            Notification::make()->title('Acción Bloqueada')->body('El proyecto está suspendido y se encuentra en modo de solo lectura.')->danger()->send();
            return;
        }

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

        $quotaService = app(\App\Services\AssetQuotaService::class);
        $user = auth()->user();
        $limits = $quotaService->calculateLimits($tenant, $user, $totalEnabled);

        if ($limits['usage'] > $limits['limit']) {
            Notification::make()
                ->title('Asset Limit Exceeded')
                ->danger()
                ->body("You have selected assets that exceed your available quota ({$limits['limit']}). Please deselect some assets or upgrade your plan.")
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

        // Process locks for the new configuration
        app(\App\Services\AssetQuotaService::class)->processGracePeriodLocks($tenant);

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
