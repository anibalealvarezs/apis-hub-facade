<?php

namespace App\Filament\App\Pages;

use App\Models\ApisHubRelease;
use App\Services\LocalAssetDiscoveryService;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Str;

class DataSources extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-server-stack';

    public static function getNavigationLabel(): string
    {
        return __('Data Sources');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Data & Integrations');
    }

    public function getTitle(): string
    {
        return __('Data Sources Configuration');
    }
    protected static string $view = 'filament.app.pages.data-sources';
    protected static ?string $slug = 'data-sources';

    public ?string $activeChannel = null;
    public ?array $data = [];
    public bool $apiHubUnreachable = false;

    public function getLockedAssets(): array
    {
        $tenant = Filament::getTenant();

        return \App\Models\AssetBillingLock::where('project_id', $tenant->id)
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

        if (! $billingProfile) {
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
        if (! $billingProfile) {
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
        if ($firstProvider && ! empty($firstProvider['channels'])) {
            $this->activeChannel = $firstProvider['channels'][0]['key'];
        }

        // Now fill the form, which will generate the schema based on the correctly selected activeChannel
        $this->form->fill($config);

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
                ->body(__('You have :count newly confirmed asset(s). We recommend deploying infrastructure updates to start tracking their full history.', ['count' => $pendingAssets]))
                ->warning()
                ->persistent()
                ->actions([
                    \Filament\Notifications\Actions\Action::make('deploy')
                        ->label(__('Deploy Updates'))
                        ->button()
                        ->url(SyncSettings::getUrl()),
                ])
                ->send();
        }
    }

    public function getChannelAssetCount(string $channelKey): int
    {
        $count = 0;
        $channelData = $this->data[$channelKey] ?? [];
        if (! is_array($channelData)) {
            return 0;
        }

        array_walk_recursive($channelData, function ($item, $key) use (&$count, $channelData) {
            // Because array_walk_recursive only hits leaf nodes, it's better to iterate structurally.
        });

        // Better structural iteration
        $scan = function ($data) use (&$scan, &$count) {
            if (! is_array($data)) {
                return;
            }
            if (array_key_exists('enabled', $data) && (array_key_exists('id', $data) || array_key_exists('url', $data) || array_key_exists('lost_access', $data))) {
                if (! empty($data['enabled']) && empty($data['lost_access'])) {
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
                    ['key' => 'google_search_console', 'label' => 'Google Search Console', 'status' => 'Active'],
                    ['key' => 'google_analytics', 'label' => 'Google Analytics', 'status' => 'Maintenance'],
                    ['key' => 'google_ads', 'label' => 'Google Ads', 'status' => 'Maintenance'],
                ],
            ],
            'facebook' => [
                'label' => 'Facebook',
                'channels' => [
                    ['key' => 'facebook_marketing', 'label' => 'Facebook Marketing', 'status' => 'Active'],
                    ['key' => 'facebook_organic', 'label' => 'Facebook Organic', 'status' => 'Active'],
                    ['key' => 'facebook_leads', 'label' => 'Facebook Leads', 'status' => 'Maintenance'],
                ],
            ],
            'tiktok' => [
                'label' => 'TikTok',
                'channels' => [
                    ['key' => 'tiktok_marketing', 'label' => 'TikTok Marketing', 'status' => 'Coming Soon'],
                    ['key' => 'tiktok_organic', 'label' => 'TikTok Organic', 'status' => 'Coming Soon'],
                    ['key' => 'tiktok_leads', 'label' => 'TikTok Leads', 'status' => 'Coming Soon'],
                ],
            ],
            'klaviyo' => [
                'label' => 'Klaviyo',
                'channels' => [
                    ['key' => 'klaviyo_metrics', 'label' => 'Klaviyo Metrics', 'status' => 'Coming Soon'],
                    ['key' => 'klaviyo_events', 'label' => 'Klaviyo Events', 'status' => 'Coming Soon'],
                ],
            ],
            'shopify' => [
                'label' => 'Shopify',
                'channels' => [
                    ['key' => 'shopify_metrics', 'label' => 'Shopify Metrics', 'status' => 'Coming Soon'],
                    ['key' => 'shopify_orders', 'label' => 'Shopify Orders', 'status' => 'Coming Soon'],
                    ['key' => 'shopify_products', 'label' => 'Shopify Products', 'status' => 'Coming Soon'],
                    ['key' => 'shopify_customers', 'label' => 'Shopify Customers', 'status' => 'Coming Soon'],
                ],
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

            usort($provider['channels'], function ($a, $b) {
                if ($a['count'] !== $b['count']) {
                    return $b['count'] <=> $a['count']; // Higher count first
                }

                return strcmp($a['label'], $b['label']); // Then alphabetical
            });
        }
        unset($provider); // break reference

        // Sort providers
        uasort($providers, function ($a, $b) {
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

        return __('Configuration');
    }

    public function isConnected($channel): bool
    {
        $tenant = Filament::getTenant();
        $provider = str_contains($channel, 'facebook') ? 'facebook' : 'google';
        $profileIdColumn = "{$provider}_profile_id";

        if (! $tenant->{$profileIdColumn}) {
            return false;
        }

        $profile = \App\Models\ChannelProfile::find($tenant->{$profileIdColumn});
        if ($profile && is_array($profile->authorized_channels)) {
            return in_array($channel, $profile->authorized_channels);
        }

        // Fallback for legacy connections before the column was added
        if ($provider === 'facebook') {
            return $tenant->facebook_user_id !== null;
        }
        if ($provider === 'google') {
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

        if (! $tenant->{$profileIdColumn}) {
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
                ->view('filament.app.actions.tier-usage-target'),
        ];
    }

    public function discoverAssetsAction(): Action
    {
        return Action::make('discoverAssets')
                ->label(__('Refresh / Discover'))
                ->icon('heroicon-o-arrow-path')
                ->disabled(fn () => ! Filament::getTenant()->is_active || Filament::getTenant()->billing_status === 'suspended')
                ->action(function (LocalAssetDiscoveryService $service) {
                    $tenant = Filament::getTenant();
                    $response = $service->fetchAssets($tenant, $this->activeChannel);

                    if (isset($response['success']) && $response['success'] && isset($response['assets'])) {
                        // Hardcode correct resource keys for extraction since services.php is generic
                        $resourceKeyMap = [
                            'google_search_console' => 'sites',
                            'facebook_marketing' => 'ad_accounts',
                            'facebook_organic' => 'pages',
                            'shopify' => 'stores',
                        ];
                        $resourceKey = $resourceKeyMap[$this->activeChannel] ?? $this->activeChannel;

                        $liveAssets = $response['assets'][$resourceKey] ?? [];

                        // If still empty but the root assets has items, fallback to the first array found
                        if (empty($liveAssets) && ! empty($response['assets'])) {
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
                        Notification::make()->title(__('Assets Refreshed'))->success()->send();
                    } else {
                        // Check if it's a connection error (cURL)
                        $errMsg = $response['message'] ?? '';
                        if (str_contains($errMsg, 'cURL error') || str_contains($errMsg, 'Connection refused') || str_contains($errMsg, 'resolve host')) {
                            $this->apiHubUnreachable = true;
                            Notification::make()
                                ->title(__('Connection Failed'))
                                ->danger()
                                ->body(__('Your sync engine is currently inactive. The platform might be down. Please try again later.'))
                                ->send();
                        } else {
                            Notification::make()
                                ->title(__('Refresh Failed'))
                                ->danger()
                                ->body($errMsg ?: __('Unable to fetch assets from the sync engine.'))
                                ->send();
                        }
                    }
                });
    }

    protected function getChannelSelectionForm(): array
    {
        $provider = str_contains($this->activeChannel, 'facebook') ? 'facebook' : 'google';
        $config = config("services.{$provider}.channel_scopes") ?? [];
        unset($config['default']);

        $options = [];
        foreach (array_keys($config) as $channelKey) {
            $options[$channelKey] = \Illuminate\Support\Str::headline(str_replace('_', ' ', $channelKey));
        }

        $tenant = Filament::getTenant();
        $defaultChannels = [$this->activeChannel];
        if ($tenant && is_array($tenant->sync_config)) {
            foreach (array_keys($options) as $ch) {
                if (isset($tenant->sync_config[$ch]) && $ch !== $this->activeChannel) {
                    $defaultChannels[] = $ch;
                }
            }
        }

        return [
            \Filament\Forms\Components\CheckboxList::make('channels')
                ->label(__('Select channels to authorize'))
                ->options($options)
                ->default($defaultChannels)
                ->required()
                ->minItems(1),
        ];
    }

    public function connectAction(): Action
    {
        return Action::make('connect')
                ->label(__('Connect Account'))
                ->icon('heroicon-o-link')
                ->visible(fn () => auth()->user()->can('manage_channels') && ! $this->isConnected($this->activeChannel))
                ->disabled(fn () => ! Filament::getTenant()->is_active || Filament::getTenant()->billing_status === 'suspended')
                ->form(fn () => $this->getChannelSelectionForm())
                ->action(function (array $data) {
                    $tenant = Filament::getTenant();
                    $provider = str_contains($this->activeChannel, 'facebook') ? 'facebook' : 'google';
                    $types = implode(',', $data['channels']);

                    return redirect()->route('app.connect', [
                        'tenant' => $tenant->id,
                        'provider' => $provider,
                        'types' => $types,
                    ]);
                });
    }

    public function updateCredentialsAction(): Action
    {
        return Action::make('updateCredentials')
                ->label(__('Update Permissions'))
                ->icon('heroicon-o-key')
                ->visible(fn () => auth()->user()->can('manage_channels') && $this->isConnected($this->activeChannel))
                ->disabled(fn () => ! Filament::getTenant()->is_active || Filament::getTenant()->billing_status === 'suspended')
                ->form(fn () => $this->getChannelSelectionForm())
                ->requiresConfirmation()
                ->modalHeading(fn () => (! Filament::getTenant()->last_deployed_at || $this->apiHubUnreachable) ? __('Update Credentials') : __('Update Credentials Safely'))
                ->modalDescription(fn () => (! Filament::getTenant()->last_deployed_at || $this->apiHubUnreachable) ? __('Select the channels to re-authorize. Your sync engine is currently offline, so it is safe to update credentials immediately.') : __('Select the channels to re-authorize. To update these credentials safely, we must first stop active synchronizations. This process can take up to 2 hours. We will send you a notification when it is safe to proceed.'))
                ->action(function (array $data) {
                    $tenant = Filament::getTenant();
                    $provider = str_contains($this->activeChannel, 'facebook') ? 'facebook' : 'google';
                    $types = implode(',', $data['channels']);

                    if (! $tenant->last_deployed_at || $this->apiHubUnreachable) {
                        return redirect()->route('app.connect', [
                            'tenant' => $tenant->id,
                            'provider' => $provider,
                            'types' => $types,
                        ]);
                    }

                    \App\Jobs\PrepareSafeTokenUpdateJob::dispatch($tenant, $provider, auth()->id(), $types);

                    Notification::make()
                        ->title(__('Safe Update Initiated'))
                        ->body(__('We are safely pausing your workers. You will be notified when it is safe to proceed.'))
                        ->warning()
                        ->send();
                });
    }

    protected function mergeDiscoveredAssets(array $liveAssets): void
    {
        $tenant = Filament::getTenant();
        $release = $tenant->apisHubRelease ?? ApisHubRelease::where('is_active', true)->first();
        if (! $release) {
            return;
        }

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

        if (! $assetListKey) {
            return;
        }

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
        $isSuspended = ! $tenant->is_active || $tenant->billing_status === 'suspended';

        return $form
            ->schema($this->getDynamicSchema())
            ->statePath('data')
            ->disabled($isSuspended);
    }

    protected function getDynamicSchema(): array
    {
        $tenant = Filament::getTenant();
        $release = $tenant->apisHubRelease ?? ApisHubRelease::where('is_active', true)->first();

        if (! $release || empty($release->config_schemas[$this->activeChannel]['fields'])) {
            return [
                Toggle::make($this->activeChannel . '_enabled')
                    ->label(__('Enable Channel'))
                    ->default(true),
            ];
        }

        $fields = $release->config_schemas[$this->activeChannel]['fields'];
        $parts = $this->buildComponentsFromSchema($fields, $this->activeChannel . '.');

        $secondarySections = [];

        if ($this->activeChannel === 'facebook_marketing') {
            // Insert custom extraction granularity UI in the secondary column
            $secondarySections[] = \Filament\Forms\Components\Section::make(__('Extraction Granularity'))
                ->schema([
                    \Filament\Forms\Components\Select::make($this->activeChannel . '.entity_sync_depth')
                        ->label(__('Entity Depth'))
                        ->options([
                            'ACCOUNT' => __('Level 1: Account'),
                            'CAMPAIGN' => __('Level 2: Campaigns'),
                            'ADSET' => __('Level 3: Adsets'),
                            'AD' => __('Level 4: Ads'),
                        ])
                        ->default('AD')
                        ->live()
                        ->helperText(__('Deepest level of entities to sync.')),

                    \Filament\Forms\Components\Select::make($this->activeChannel . '.metrics_level')
                        ->label(__('Metrics Level'))
                        ->options(function (\Filament\Forms\Get $get) {
                            $entityDepth = $get('facebook_marketing.entity_sync_depth') ?? 'AD';
                            $allOptions = [
                                'ACCOUNT' => __('L1 Metrics'),
                                'CAMPAIGN' => __('L2 Metrics'),
                                'ADSET' => __('L3 Metrics'),
                                'AD' => __('L4 Metrics'),
                            ];

                            $levels = ['ACCOUNT' => 1, 'CAMPAIGN' => 2, 'ADSET' => 3, 'AD' => 4];
                            $maxLevel = $levels[$entityDepth] ?? 4;

                            return array_filter($allOptions, fn ($k) => $levels[$k] <= $maxLevel, ARRAY_FILTER_USE_KEY);
                        })
                        ->default('AD')
                        ->helperText(__('Cannot exceed entity sync depth.')),
                ])->columns(1);

            $secondarySections[] = \Filament\Forms\Components\Section::make(__('Asset Name Filters'))
                ->description(__('Filter which assets should be synced based on their names. Leave blank to sync all.'))
                ->schema([
                    \Filament\Forms\Components\Actions::make([
                        \Filament\Forms\Components\Actions\Action::make('generateRegex')
                            ->label(__('Regex Generator'))
                            ->icon('heroicon-m-beaker')
                            ->color('primary')
                            ->form([
                                \Filament\Forms\Components\Repeater::make('strings')
                                    ->label(__('Strings to Match'))
                                    ->helperText(__('Add multiple strings to generate a regex that matches any of them.'))
                                    ->simple(
                                        \Filament\Forms\Components\TextInput::make('string')->required()
                                    )
                                    ->defaultItems(2),
                                \Filament\Forms\Components\Select::make('target')
                                    ->label(__('Target Filter'))
                                    ->options([
                                        'CAMPAIGN' => __('Campaign Filter'),
                                        'ADSET' => __('Adset Filter'),
                                        'AD' => __('Ad Filter'),
                                    ])
                                    ->required()
                                    ->default('CAMPAIGN'),
                            ])
                            ->action(function (array $data, \Filament\Forms\Set $set) {
                                $strings = $data['strings'] ?? [];
                                if (empty($strings)) {
                                    return;
                                }

                                $escaped = array_map(fn ($s) => preg_quote($s, '/'), $strings);
                                $regex = '/(' . implode('|', $escaped) . ')/i';

                                $target = $data['target'];
                                $set('facebook_marketing.' . $target . '.cache_include', $regex);
                            }),
                    ])->alignRight(),

                    \Filament\Forms\Components\TextInput::make($this->activeChannel . '.CAMPAIGN.cache_include')
                        ->label(__('Campaign Filter'))
                        ->placeholder(__('Regex (e.g. /PATTERN/i) or string'))
                        ->helperText(__('Only sync Campaigns matching this pattern.')),

                    \Filament\Forms\Components\TextInput::make($this->activeChannel . '.ADSET.cache_include')
                        ->label(__('Adset Filter'))
                        ->placeholder(__('Regex (e.g. /PATTERN/i) or string'))
                        ->helperText(__('Only sync Adsets matching this pattern.')),

                    \Filament\Forms\Components\TextInput::make($this->activeChannel . '.AD.cache_include')
                        ->label(__('Ad Filter'))
                        ->placeholder(__('Regex (e.g. /PATTERN/i) or string'))
                        ->helperText(__('Only sync Ads matching this pattern.')),
                ])->columns(1);
        }

        if ($this->activeChannel === 'google_search_console') {
            $secondarySections[] = \Filament\Forms\Components\Section::make(__('Data Enrichment'))
                ->description(__('Advanced data recovery and attribution inference.'))
                ->schema([
                    \Filament\Forms\Components\Toggle::make($this->activeChannel . '.calculate_synthetics')
                        ->label(__('Enable Synthetic Calculations (Möbius Reconciliation)'))
                        ->default(true),
                    \Filament\Forms\Components\Placeholder::make('synthetic_explanation')
                        ->hiddenLabel()
                        ->content(new \Illuminate\Support\HtmlString('
                            <div class="space-y-3 mt-2" style="font-size: 0.875rem; opacity: 0.85;">
                                <p><strong>' . __('What is this?') . '</strong> ' . __('Synthetic calculations use an algorithmic method to infer attribution data that Google Search Console actively removes from your reports to protect user privacy.') . '</p>

                                <p><strong>' . __('The Problem:') . '</strong> ' . __('When you look at GSC data by a single dimension (like Page), Google gives you close to 100% of the actual events. However, when you break data down by multiple dimensions simultaneously (like Page + Query + Country + Device), Google hides almost 50% of the records because those specific combinations might identify users.') . '</p>

                                <p><strong>' . __('Our Solution:') . '</strong> ' . __('We query every possible subset of Google\'s data and run a reconciliation algorithm to deduce the missing pieces. This provides an almost complete picture of your traffic at the most granular level possible.') . '</p>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4 pt-3" style="border-top: 1px solid rgba(128, 128, 128, 0.2);">
                                    <div>
                                        <h4 class="font-medium flex items-center gap-1" style="color: #10b981;">✨ ' . __('The Benefits') . '</h4>
                                        <p class="mt-1" style="font-size: 0.75rem; opacity: 0.8;">' . __('Unlike Google, your totals will remain highly consistent no matter how deeply you filter or group the data. You get deep, granular attribution that is normally impossible to see.') . '</p>
                                    </div>
                                    <div>
                                        <h4 class="font-medium flex items-center gap-1" style="color: #f59e0b;">⚠️ ' . __('The Trade-offs') . '</h4>
                                        <p class="mt-1" style="font-size: 0.75rem; opacity: 0.8;">' . __('Because this is an inference engine, expect a slight margin of error (~2% on average) compared to Google\'s top-level totals. Additionally, <strong>syncing will take roughly 10x longer</strong> to process all the required subsets. Finally, API usage will be significantly more intense, which increases the chances of facing rate limit issues or token invalidations.') . '</p>
                                    </div>
                                </div>
                            </div>
                        ')),
                ])->columns(1);
        }

        if (! empty($parts['advanced'])) {
            $secondarySections[] = Section::make(__('Advanced Configuration'))
                ->schema(array_values($parts['advanced']))
                ->columns(1);
        }

        if ($this->activeChannel === 'facebook_organic') {
            // First time modal
            $secondarySections[] = \Filament\Forms\Components\Placeholder::make('fb_organic_first_time_modal')
                ->hiddenLabel()
                ->content(new \Illuminate\Support\HtmlString('
                    <div
                        x-data="{ showWarningModal: false }"
                        x-init="
                            setTimeout(() => {
                                if (!localStorage.getItem(\'fb_organic_warnings_seen_v1\')) {
                                    showWarningModal = true;
                                }
                            }, 500);
                        "
                    >
                        <style>
                            .fb-modal-warning-box { background-color: #fffbeb; border-color: #f59e0b; padding: 1.25rem; }
                            .dark .fb-modal-warning-box { background-color: rgba(245, 158, 11, 0.1); border-color: #f59e0b; }
                            .fb-modal-warning-text { color: #92400e; }
                            .dark .fb-modal-warning-text { color: #fcd34d; }
                            .fb-modal-warning-subtext { color: #b45309; }
                            .dark .fb-modal-warning-subtext { color: #fde68a; }
                            .fb-modal-warning-icon { color: #d97706; }
                            .dark .fb-modal-warning-icon { color: #fbbf24; }

                            .fb-modal-rl-warning-box { background-color: #eff6ff; border-color: #3b82f6; padding: 1.25rem; }
                            .dark .fb-modal-rl-warning-box { background-color: rgba(59, 130, 246, 0.1); border-color: #3b82f6; }
                            .fb-modal-rl-warning-text { color: #1e40af; }
                            .dark .fb-modal-rl-warning-text { color: #93c5fd; }
                            .fb-modal-rl-warning-subtext { color: #1d4ed8; }
                            .dark .fb-modal-rl-warning-subtext { color: #bfdbfe; }
                            .fb-modal-rl-warning-icon { color: #2563eb; }
                            .dark .fb-modal-rl-warning-icon { color: #60a5fa; }
                        </style>
                        <div x-show="showWarningModal" style="display: none;" class="fixed inset-0 z-[9999] flex items-center justify-center bg-gray-900/75 transition-opacity" x-transition.opacity>
                            <div @click.away="localStorage.setItem(\'fb_organic_warnings_seen_v1\', \'true\'); showWarningModal = false" class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl max-w-2xl w-full m-4 relative" style="padding: 2rem;" x-transition.scale.origin.bottom>
                                <button @click="localStorage.setItem(\'fb_organic_warnings_seen_v1\', \'true\'); showWarningModal = false" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>

                                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                                    <svg class="w-8 h-8 text-blue-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                      <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                                    </svg>
                                    ' . __('Important: Facebook Organic') . '
                                </h2>

                                <div class="space-y-6">
                                    <div class="rounded-r-xl border-l-4 fb-modal-warning-box shadow-sm">
                                        <div class="flex items-start gap-4">
                                            <svg class="w-6 h-6 shrink-0 mt-0.5 fb-modal-warning-icon" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                              <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                            </svg>
                                            <div>
                                                <h3 class="text-base font-bold tracking-tight fb-modal-warning-text">' . __('Historic Metrics Limitation') . '</h3>
                                                <p class="text-sm mt-1 leading-relaxed fb-modal-warning-subtext">
                                                    ' . __('Facebook does not provide historic metrics for posts and media; it only provides daily snapshots. Therefore, we will build the history for your assets by caching the daily data to provide time series starting from today.') . ' <strong class="font-semibold fb-modal-warning-text">' . __('To successfully build these time series without gaps, you must keep the channel and the asset enabled continuously.') . '</strong>
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="rounded-r-xl border-l-4 fb-modal-rl-warning-box shadow-sm mt-4">
                                        <div class="flex items-start gap-4">
                                            <svg class="w-6 h-6 shrink-0 mt-0.5 fb-modal-rl-warning-icon" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                              <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                            </svg>
                                            <div>
                                                <h3 class="text-base font-bold tracking-tight fb-modal-rl-warning-text">' . __('Rate Limits & Inactive Assets') . '</h3>
                                                <p class="text-sm mt-1 leading-relaxed fb-modal-rl-warning-subtext">
                                                    ' . __('Facebook\'s API rate limits are heavily influenced by the recent engagement your Pages and IG Accounts receive. Pages with a large volume of content but very low interaction face much stricter rate limits, increasing the risk of synchronization interruptions.') . ' <strong class="font-semibold fb-modal-rl-warning-text">' . __('We strongly recommend disabling inactive assets (those with minimal analytic value) to prevent rate limit bottlenecks and preserve your subscription quota.') . '</strong>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-8 flex justify-end">
                                    <button @click="localStorage.setItem(\'fb_organic_warnings_seen_v1\', \'true\'); showWarningModal = false" class="px-6 py-2.5 bg-primary-600 hover:bg-primary-500 text-white font-medium rounded-lg shadow-sm transition-colors">
                                        ' . __('I understand') . '
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                '));

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
                                <h3 class="text-base font-bold tracking-tight fb-warning-text">' . __('Historic Metrics Limitation') . '</h3>
                                <p class="text-sm mt-1 leading-relaxed fb-warning-subtext">
                                    ' . __('Facebook does not provide historic metrics for posts and media; it only provides daily snapshots. Therefore, we will build the history for your assets by caching the daily data to provide time series starting from today.') . ' <strong class="font-semibold fb-warning-text">' . __('To successfully build these time series without gaps, you must keep the channel and the asset enabled continuously.') . '</strong>
                                </p>
                            </div>
                        </div>
                    </div>
                '));

            $secondarySections[] = \Filament\Forms\Components\Placeholder::make('fb_organic_rate_limit_warning')
                ->hiddenLabel()
                ->content(new \Illuminate\Support\HtmlString('
                    <style>
                        .fb-rl-warning-box { background-color: #eff6ff; border-color: #3b82f6; padding: 1.25rem; }
                        .dark .fb-rl-warning-box { background-color: rgba(59, 130, 246, 0.1); border-color: #3b82f6; }
                        .fb-rl-warning-text { color: #1e40af; }
                        .dark .fb-rl-warning-text { color: #93c5fd; }
                        .fb-rl-warning-subtext { color: #1d4ed8; }
                        .dark .fb-rl-warning-subtext { color: #bfdbfe; }
                        .fb-rl-warning-icon { color: #2563eb; }
                        .dark .fb-rl-warning-icon { color: #60a5fa; }
                    </style>
                    <div class="rounded-r-xl border-l-4 fb-rl-warning-box shadow-sm mt-4">
                        <div class="flex items-start gap-4">
                            <svg class="w-6 h-6 shrink-0 mt-0.5 fb-rl-warning-icon" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <div>
                                <h3 class="text-base font-bold tracking-tight fb-rl-warning-text">' . __('Rate Limits & Inactive Assets') . '</h3>
                                <p class="text-sm mt-1 leading-relaxed fb-rl-warning-subtext">
                                    ' . __('Facebook\'s API rate limits are heavily influenced by the recent engagement your Pages and IG Accounts receive. Pages with a large volume of content but very low interaction face much stricter rate limits, increasing the risk of synchronization interruptions.') . ' <strong class="font-semibold fb-rl-warning-text">' . __('We strongly recommend disabling inactive assets (those with minimal analytic value) to prevent rate limit bottlenecks and preserve your subscription quota.') . '</strong>
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
                        ->extraAttributes(['class' => 'sticky top-4 self-start']),
                ]),
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
                    ->label(__('Enable :channel', ['channel' => $this->getChannelLabel($this->activeChannel)]))
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
                        $get('lost_access') ? __('⚠️ Lost Access') : (
                            $this->activeChannel === 'facebook_marketing' ? 'ID: ' . ($get('id') ?? 'N/A') :
                            ($this->activeChannel === 'google_search_console' ? 'ID: <a href="https://' . preg_replace('/^sc-domain:/', '', preg_replace('/^https?:\/\//', '', rtrim((string)($get('url') ?? $get('id')), '/'))) . '" target="_blank" rel="nofollow noopener noreferrer" class="text-primary-500 hover:underline">' . ($get('id') ?? $get('url') ?? 'N/A') . '</a>' :
                            'ID: <a href="' . ($get('link') ?? $get('url') ?? '#') . '" target="_blank" rel="nofollow noopener noreferrer" class="text-primary-500 hover:underline">' . ($get('id') ?? $get('url') ?? 'N/A') . '</a>')
                        )
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
        if (! empty($itemComponents)) {
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
                        <input type="text" x-model="assetFilter" class="block w-full pr-3 py-2 border border-gray-300 rounded-lg leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-primary-500 focus:border-primary-500 sm:text-sm transition duration-150 ease-in-out dark:bg-white/5 dark:border-white/10 dark:text-white" style="padding-left: 2.75rem;" placeholder="'.__('Live filter assets by name or ID...').'">
                    </div>
                ')),
            Repeater::make($fieldKey)
                ->label(Str::headline($label))
                ->hintActions([
                    \Filament\Forms\Components\Actions\Action::make('selectAll')
                        ->label(__('Select All'))
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
                        ->label(__('Deselect All'))
                        ->button()
                        ->color('danger')
                        ->action(function (\Filament\Forms\Components\Repeater $component) {
                            $state = $component->getState();
                            $newState = collect($state)->map(function ($item) {
                                $item['enabled'] = false;

                                return $item;
                            })->toArray();
                            $component->state($newState);
                        }),
                ])
                ->schema([
                    \Filament\Forms\Components\Group::make([
                        Grid::make(12)->schema($rowSchema),
                    ])->extraAttributes(function (\Filament\Forms\Get $get) {
                        $searchableText = strtolower(implode(' | ', [
                            $get('title') ?? '',
                            $get('name') ?? '',
                            $get('url') ?? '',
                            $get('id') ?? '',
                        ]));
                        $searchableText = str_replace(["'", "\\", "\n", "\r"], ["\'", "\\\\", " ", " "], $searchableText);

                        return [
                            'x-effect' => "\$el.closest('li').style.display = (assetFilter === '' || '" . $searchableText . "'.includes(assetFilter.toLowerCase())) ? '' : 'none'",
                        ];
                    }),
                ])
            ->grid(1)
            ->collapsible(false)
            ->addable(false)
            ->deletable(false)
            ->reorderable(false)
            ->columnSpanFull()
            ->extraAttributes(['class' => 'compact-repeater']),
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
                    <span>' . e($get('title') ?? $get('name') ?? __('Unknown Asset')) . '</span>
                    <template x-if="getAssetBadge(\'' . e($get('id') ?? $get('url')) . '\')">
                        <span x-html="getAssetBadge(\'' . e($get('id') ?? $get('url')) . '\')"></span>
                    </template>
                </div>
            '))
            ->helperText(fn (callable $get) => new \Illuminate\Support\HtmlString(
                'ID: <a href="' . $get('link') . '" target="_blank" rel="nofollow noopener noreferrer" class="text-primary-500 hover:underline">' . $get('id') . '</a>' .
                (! empty($get('ig_account_name')) ? '<br><span class="text-xs text-gray-500 mt-0.5 inline-block">IG: <a href="https://instagram.com/' . $get('ig_account_name') . '" target="_blank" class="text-pink-500 hover:underline">@' . $get('ig_account_name') . '</a></span>' : '')
            ))
            ->inline(false)
            ->default(true)
            ->live()
            ->columnSpan(4);

        $headerComponents[] = \Filament\Forms\Components\Grid::make(2)->schema([
            // Facebook Extraction Column
            \Filament\Forms\Components\Group::make()->schema([
                Toggle::make('page_metrics')->label(__('Page Metrics'))->inline(true)->default(true),
                Toggle::make('posts')->label(__('Posts Content'))->inline(true)->default(true)->live()
                    ->afterStateUpdated(function (\Filament\Forms\Get $get, \Filament\Forms\Set $set, $state) {
                        if (! (bool) $state) {
                            $set('post_metrics', false);
                        }
                    }),
                Toggle::make('post_metrics')->label(__('Post Insights'))->inline(true)->default(true)
                    ->extraAttributes(['class' => 'ml-8'])
                    ->visible(fn (\Filament\Forms\Get $get): bool => (bool) $get('posts'))->dehydrated(),
            ])->extraAttributes(['class' => 'flex flex-col gap-2']),

            // Instagram Extraction Column
            \Filament\Forms\Components\Group::make()->schema([
                Toggle::make('ig_accounts')->label(__('Sync Instagram'))->inline(true)->default(true)->live()
                    ->visible(fn (\Filament\Forms\Get $get) => ! empty($get('ig_account')))
                    ->afterStateUpdated(function (\Filament\Forms\Get $get, \Filament\Forms\Set $set, $state) {
                        if (! (bool) $state) {
                            $set('ig_account_metrics', false);
                            $set('ig_account_media', false);
                            $set('ig_account_media_metrics', false);
                        }
                    }),
                Toggle::make('ig_account_metrics')->label(__('Account Metrics'))->inline(true)->default(true)
                    ->extraAttributes(['class' => 'ml-8'])
                    ->visible(fn (\Filament\Forms\Get $get): bool => (bool) $get('ig_accounts') && ! empty($get('ig_account')))->dehydrated(),
                Toggle::make('ig_account_media')->label(__('Media Content'))->inline(true)->default(true)->live()
                    ->extraAttributes(['class' => 'ml-8'])
                    ->visible(fn (\Filament\Forms\Get $get): bool => (bool) $get('ig_accounts') && ! empty($get('ig_account')))
                    ->afterStateUpdated(function (\Filament\Forms\Get $get, \Filament\Forms\Set $set, $state) {
                        if (! (bool) $state) {
                            $set('ig_account_media_metrics', false);
                        }
                    })->dehydrated(),
                Toggle::make('ig_account_media_metrics')->label(__('Media Insights'))->inline(true)->default(true)
                    ->extraAttributes(['class' => 'ml-12'])
                    ->visible(fn (\Filament\Forms\Get $get): bool => (bool) $get('ig_accounts') && (bool) $get('ig_account_media') && ! empty($get('ig_account')))->dehydrated(),
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
                        <input type="text" x-model="assetFilter" class="block w-full pr-3 py-2 border border-gray-300 rounded-lg leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-primary-500 focus:border-primary-500 sm:text-sm transition duration-150 ease-in-out dark:bg-white/5 dark:border-white/10 dark:text-white" style="padding-left: 2.75rem;" placeholder="'.__('Live filter assets by name or ID...').'">
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
                        }),
                ])
                ->schema([
                    \Filament\Forms\Components\Group::make([
                        Grid::make(12)->schema($headerComponents),
                    ])->extraAttributes(function (\Filament\Forms\Get $get) {
                        $searchableText = strtolower(implode(' | ', [
                            $get('title') ?? '',
                            $get('name') ?? '',
                            $get('url') ?? '',
                            $get('id') ?? '',
                        ]));
                        $searchableText = str_replace(["'", "\\", "\n", "\r"], ["\'", "\\\\", " ", " "], $searchableText);

                        return [
                            'x-effect' => "\$el.closest('li').style.display = (assetFilter === '' || '" . $searchableText . "'.includes(assetFilter.toLowerCase())) ? '' : 'none'",
                        ];
                    }),
                ])
            ->grid(1)
            ->collapsible(false)
            ->addable(false)
            ->deletable(false)
            ->reorderable(false)
            ->columnSpanFull()
            ->extraAttributes(['class' => 'compact-repeater']),
        ])->extraAttributes(['x-data' => "{ assetFilter: '' }", 'class' => 'w-full']);
    }

    public function save(): void
    {
        $tenant = Filament::getTenant();
        if (! $tenant->is_active || $tenant->billing_status === 'suspended') {
            Notification::make()->title(__('Action Blocked'))->body(__('The project is suspended and is in read-only mode.'))->danger()->send();

            return;
        }

        if (! auth()->user()->can('manage_channels')) {
            Notification::make()->title(__('Permission Denied'))->body(__('You do not have permission to modify data sources.'))->danger()->send();

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
                            if (! empty($asset['enabled']) && empty($asset['lost_access'])) {
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
                ->title(__('Asset Limit Exceeded'))
                ->danger()
                ->body(__('You have selected assets that exceed your available quota (:limit). Please deselect some assets or upgrade your plan.', ['limit' => $limits['limit']]))
                ->send();

            return;
        }

        // We will update the tenant DB at the end of the method with the fully merged dbState

        // Push the configuration to the remote engine via APIs Hub SDK
        $service = app(\App\Services\RemoteEngineService::class);
        $remoteAssetKeyMap = [
            'google_search_console' => 'gsc',
            'facebook_marketing' => 'ad_accounts',
            'facebook_organic' => 'pages',
        ];

        $release = $tenant->apisHubRelease ?? \App\Models\ApisHubRelease::where('is_active', true)->first();
        $rejectedAssets = [];

        foreach ($uiState as $channel => $channelConfig) {
            if (! is_array($channelConfig)) {
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

            if (! $assetListKey) {
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
                if (isset($channelConfig['calculate_synthetics'])) {
                    $payload['feature_toggles']['calculate_synthetics'] = filter_var($channelConfig['calculate_synthetics'], FILTER_VALIDATE_BOOLEAN);
                }
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

                if ($entLevel === 'account') {
                    $entLevel = 'ad_account';
                }
                if ($metLevel === 'account') {
                    $metLevel = 'ad_account';
                }

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
                    'CAMPAIGN' => $channelConfig['CAMPAIGN']['cache_include'] ?? '',
                    'ADSET' => $channelConfig['ADSET']['cache_include'] ?? '',
                    'AD' => $channelConfig['AD']['cache_include'] ?? '',
                    'CREATIVE' => $channelConfig['CREATIVE']['cache_include'] ?? '',
                ];

                unset($payload['entity_sync_depth']);
                unset($payload['metrics_level']);
            }

            // ENFORCE FREE TIER CONSTRAINT RULES:
            $isFree = $tenant->billingProfile?->tier === \App\Enums\UserTier::FREE;
            if ($isFree) {
                // 1. Capping workers to a maximum of 1
                $payload['max_workers'] = 1;

                // 2. Disabling synthetic GSC calculations
                $payload['calculate_synthetics'] = false;
                if (isset($payload['google_search_console'])) {
                    $payload['google_search_console']['calculate_synthetics'] = false;
                }

                // 3. Capping historical sync range to 6 months
                $payload['cache_history_range'] = '6_months';
                $channelConfig['cache_history_range'] = '6_months';
                $payload[$channel . '.cache_history_range'] = '6_months';
                $channelConfig[$channel . '.cache_history_range'] = '6_months';
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
                $remoteAssetKey => $assetsListDb,
            ];

            try {
                $response = $service->updateCredentials($tenant, $payload);

                // If successful, we update the DB state with the merged assets so it remains the source of truth
                if (! isset($dbState[$channel])) {
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
                            if ($intendedEnabled && ! $remoteEnabled) {
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
                    if (! is_array($v) || in_array($k, ['CAMPAIGN', 'ADSET', 'AD', 'CREATIVE'])) {
                        $dbState[$channel][$k] = $v;
                    }
                }

                \Illuminate\Support\Arr::set($dbState[$channel], $assetListKey, $assetsListDb);
            } catch (\Exception $e) {
                \Filament\Notifications\Notification::make()
                    ->title(__('Failed to sync :channel to remote engine', ['channel' => $channel]))
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
                ->title(__('Configuration Saved Partially'))
                ->body(__('Some assets were automatically disabled by the remote server due to insufficient permissions or invalid state: <strong>:assets</strong>.', ['assets' => $rejectedList]))
                ->warning()
                ->persistent()
                ->send();
        } else {
            \Filament\Notifications\Notification::make()
                ->title(__('Configuration Saved'))
                ->success()
                ->send();
        }
    }
}
