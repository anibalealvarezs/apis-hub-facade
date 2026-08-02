<?php

namespace App\Services\Analytics;

use Filament\Facades\Filament;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class KpiFormBuilder
{
    public static function getActiveChannels(): array
    {
        $tenant = Filament::getTenant();
        if (! $tenant) {
            return [];
        }

        $config = $tenant->sync_config ?? [];
        $providers = [];
        foreach ($config as $channelKey => $channelData) {
            if (isset($channelData['is_active']) && $channelData['is_active']) {
                $providers[] = $channelKey;
            }
        }

        $channels = \App\Services\Analytics\ChannelCapabilityRegistry::getTags();
        $active = [];

        foreach (array_keys($channels) as $channel) {
            if (in_array($channel, $providers)) {
                continue;
            }

            if (self::channelHasEnabledAssets($channel)) {
                $active[$channel] = self::getChannelDisplayName($channel);
            }
        }

        return $active;
    }

    /**
     * Check whether a sub-channel key in sync_config has at least one enabled asset.
     * Unlike getAllAssetsForChannel(), this does NOT require is_active on the
     * channel entry — is_active lives only on provider-level keys (meta, google …),
     * while assets live under sub-channel keys (facebook_marketing, google_analytics …).
     */
    private static function channelHasEnabledAssets(string $channel): bool
    {
        $tenant = Filament::getTenant();
        if (! $tenant) {
            return false;
        }

        $channelData = ($tenant->sync_config ?? [])[$channel] ?? [];
        if (! is_array($channelData)) {
            return false;
        }

        // Recursive scanner — mirrors DataSources::getChannelAssetCount
        $scan = function (array $data) use (&$scan): bool {
            // Leaf node that looks like an asset record
            if (
                array_key_exists('enabled', $data)
                && (array_key_exists('id', $data) || array_key_exists('url', $data) || array_key_exists('platformId', $data))
            ) {
                return ! empty($data['enabled']) && empty($data['lost_access']);
            }

            foreach ($data as $value) {
                if (is_array($value) && $scan($value)) {
                    return true;
                }
            }

            return false;
        };

        return $scan($channelData);
    }

    public static function getChannelDisplayName(string $name): string
    {
        return Str::headline($name);
    }

    public static function getCategoryOptions(?string $globalAssetGroup = null): array
    {
        return [
            'Specific Channels' => static::getChannelCategories($globalAssetGroup),
            'Channel' => [
                'cross-channel' => __('Cross-Channel'),
                'organic' => __('Organic'),
                'paid' => __('Paid'),
                'crm' => __('CRM & Email'),
                'ecommerce' => __('E-commerce'),
                'web-analytics' => __('Web Analytics'),
            ],
            'Type' => [
                'type_cost' => __('Cost / Spend'),
                'type_revenue' => __('Revenue / Value'),
                'type_volume' => __('Volume / Count'),
                'type_rate' => __('Rate / Ratio'),
            ],
            'Funnel Stage' => [
                'funnel_awareness' => __('Awareness'),
                'funnel_acquisition' => __('Acquisition'),
                'funnel_engagement' => __('Engagement'),
                'funnel_conversion' => __('Conversion'),
                'funnel_retention' => __('Retention'),
            ],
            'Analysis Method' => [
                'analysis_trend' => __('Trend & Seasonality'),
                'analysis_efficiency' => __('Efficiency (ROI/ROAS)'),
                'analysis_attribution' => __('Attribution / Contribution'),
                'analysis_anomaly' => __('Anomaly & Alerting'),
                'analysis_correlation' => __('Correlation & Dependence'),
            ],
        ];
    }

    private static function getChannelCategories(?string $globalAssetGroup = null): array
    {
        $activeChannels = self::getActiveChannels();
        $cats = [];

        if ($globalAssetGroup) {
            $group = \App\Models\AssetGroup::find($globalAssetGroup);
            if ($group) {
                $groupChannels = $group->active_items->pluck('channel')->unique()->toArray();
                foreach ($groupChannels as $channel) {
                    if (isset($activeChannels[$channel])) {
                        $cats['ch_' . $channel] = $activeChannels[$channel];
                    }
                }

                return $cats;
            }
        }

        foreach ($activeChannels as $channel => $name) {
            $cats['ch_' . $channel] = $name;
        }

        return $cats;
    }

    public static function getTemplateOptions(array $categoryFilter = [], ?string $globalAssetGroup = null): array
    {
        $activeChannels = array_keys(self::getActiveChannels());
        $channelTags = \App\Services\Analytics\ChannelCapabilityRegistry::getTags();

        $groupChannels = null;
        if ($globalAssetGroup) {
            $group = \App\Models\AssetGroup::find($globalAssetGroup);
            if ($group) {
                $groupChannels = $group->active_items->pluck('channel')->unique()->toArray();
                $activeChannels = array_unique(array_merge($activeChannels, $groupChannels));
            }
        } else {
            // No asset group — discover all sub-channels with enabled assets
            $providerKeys = $activeChannels;
            foreach (array_keys($channelTags) as $channel) {
                if (! in_array($channel, $providerKeys) && self::channelHasEnabledAssets($channel)) {
                    $activeChannels[] = $channel;
                }
            }
            $activeChannels = array_unique($activeChannels);
        }

        $kpis = PredefinedKpiRegistry::getAvailableKpis($activeChannels);
        $options = [];
        foreach ($kpis as $key => $kpi) {
            $kpiCats = $kpi['categories'] ?? [];
            $requiredTags = $kpi['required_tags'] ?? [];

            if ($groupChannels !== null) {
                $availableTags = [];
                foreach ($groupChannels as $channel) {
                    if (isset($channelTags[$channel])) {
                        $availableTags = array_merge($availableTags, $channelTags[$channel]);
                    }
                }
                $availableTags = array_unique($availableTags);

                $isValid = true;
                foreach ($requiredTags as $reqTag) {
                    if (! in_array($reqTag, $availableTags)) {
                        $isValid = false;

                        break;
                    }
                }
                if (! $isValid) {
                    continue;
                }
            }

            // Inyectar dinámicamente las categorías de canales
            foreach ($channelTags as $channel => $tags) {
                if (count(array_intersect($requiredTags, $tags)) > 0) {
                    $kpiCats[] = 'ch_' . $channel;
                }
            }
            $kpiCats = array_unique($kpiCats);

            if (! empty($categoryFilter)) {
                $intersection = array_intersect($categoryFilter, $kpiCats);
                if (count($intersection) !== count($categoryFilter)) {
                    continue;
                }
            }

            $metrics = [];
            if (isset($kpi['template']['ast']['metric'])) {
                $metrics[] = $kpi['template']['ast']['metric'];
            }
            if (isset($kpi['template']['ast']['left']['metric'])) {
                $metrics[] = $kpi['template']['ast']['left']['metric'];
            }
            if (isset($kpi['template']['ast']['right'])) {
                $extractMetrics = function ($node) use (&$extractMetrics, &$metrics) {
                    if (($node['type'] ?? '') === 'metric' && isset($node['metric'])) {
                        $metrics[] = $node['metric'];
                    } elseif (($node['type'] ?? '') === 'operator' && isset($node['left'], $node['right'])) {
                        $extractMetrics($node['left']);
                        $extractMetrics($node['right']);
                    }
                };
                $extractMetrics($kpi['template']['ast']['right']);
            }
            $metrics = array_unique($metrics);

            $metricLabels = [];
            foreach ($metrics as $m) {
                $metricLabels[] = self::getChannelDisplayName($m);
            }
            $description = implode(' / ', $metricLabels);

            $options[$key] = '<div class="flex flex-col">
                <span class="font-medium text-gray-900 dark:text-gray-100">' . e($kpi['name']) . '</span>
                <span class="text-xs text-gray-500 dark:text-gray-400">' . e($description) . '</span>
            </div>';
        }

        // Sort alphabetically by KPI name
        $names = [];
        foreach ($kpis as $key => $kpi) {
            if (isset($options[$key])) {
                $names[$key] = $kpi['name'];
            }
        }
        asort($names);

        $sorted = [];
        foreach ($names as $key => $name) {
            $sorted[$key] = $options[$key];
        }

        return $sorted;
    }

    public static function getMetricOptionsForChannel(?string $channel, ?string $granularity = null, ?string $dependency = null): array
    {
        if (empty($channel)) {
            return [];
        }

        $project = Filament::getTenant();
        if (! $project) {
            return [];
        }

        $activeChannels = array_keys(self::getActiveChannels());
        if (! in_array($channel, $activeChannels)) {
            return [];
        }

        // Scope-specific metrics override for Facebook Organic
        if ($channel === 'facebook_organic') {
            $isInstagram = $dependency === 'instagram_account' || in_array($granularity, ['instagram', 'ig_post', 'instagram_account'], true);
            $isFacebookPage = $dependency === 'facebook_page' || in_array($granularity, ['facebook', 'fb_pages', 'fb_posts'], true);

            if ($isInstagram) {
                return [
                    'likes' => __('Likes'),
                    'comments' => __('Comments'),
                    'reach' => __('Reach'),
                    'views' => __('Views'),
                    'profile_views' => __('Profile Views'),
                    'website_clicks' => __('Website Clicks'),
                    'profile_links_taps' => __('Profile Links Taps'),
                    'follows_and_unfollows' => __('Follows & Unfollows'),
                    'saves' => __('Saves'),
                    'shares' => __('Shares'),
                    'total_interactions' => __('Total Interactions'),
                    'replies' => __('Replies'),
                    'accounts_engaged' => __('Accounts Engaged'),
                    'content_views' => __('Content Views'),
                ];
            } elseif ($isFacebookPage) {
                return [
                    'reach' => __('Reach'),
                    'page_views_total' => __('Page Views'),
                    'video_views' => __('Video Views'),
                    'follows_and_unfollows' => __('Follows & Unfollows'),
                    'total_interactions' => __('Total Interactions'),
                    'likes' => __('Likes'),
                ];
            } else {
                // No specific scope selected yet — return ALL organic social metrics
                return [
                    'reach' => __('Reach'),
                    'page_views_total' => __('Page Views'),
                    'video_views' => __('Video Views'),
                    'follows_and_unfollows' => __('Follows & Unfollows'),
                    'total_interactions' => __('Total Interactions'),
                    'likes' => __('Likes'),
                    'comments' => __('Comments'),
                    'views' => __('Views'),
                    'profile_views' => __('Profile Views'),
                    'website_clicks' => __('Website Clicks'),
                    'profile_links_taps' => __('Profile Links Taps'),
                    'saves' => __('Saves'),
                    'shares' => __('Shares'),
                    'replies' => __('Replies'),
                    'accounts_engaged' => __('Accounts Engaged'),
                    'content_views' => __('Content Views'),
                    'engagements' => __('Engagements'),
                    'followers' => __('Followers'),
                    'engaged_users' => __('Engaged Users'),
                ];
            }
        }

        // Scope-specific metrics override for Facebook Marketing
        if ($channel === 'facebook_marketing') {
            return [
                'spend' => __('Spend'),
                'clicks' => __('Clicks'),
                'impressions' => __('Impressions'),
                'reach' => __('Reach'),
                'frequency' => __('Frequency'),
                'cpm' => __('CPM'),
                'ctr' => __('CTR'),
                'cpc' => __('CPC'),
                'results' => __('Results'),
                'purchase_roas' => __('ROAS'),
                'cost_per_result' => __('Cost per Result'),
                'result_rate' => __('Result Rate'),
            ];
        }

        // Scope-specific metrics override for Google Analytics
        if ($channel === 'google_analytics') {
            return match ($dependency) {
                'traffic_matrix' => [
                    'sessions' => __('Sessions'),
                    'pageviews' => __('Page Views'),
                    'bounce_rate' => __('Bounce Rate'),
                    'revenue' => __('Revenue'),
                    'conversions' => __('Conversions'),
                    'average_session_duration' => __('Average Session Duration'),
                ],
                'acquisition_matrix' => [
                    'new_users' => __('New Users'),
                    'total_users' => __('Total Users'),
                    'reach' => __('Active Users'),
                    'revenue' => __('Revenue'),
                ],
                'event_matrix' => [
                    'event_count' => __('Event Count'),
                    'conversions' => __('Conversions'),
                ],
                'ad_touchpoint_matrix' => [
                    'sessions' => __('Sessions'),
                    'conversions' => __('Conversions'),
                    'revenue' => __('Revenue'),
                ],
                default => [
                    'sessions' => __('Sessions'),
                    'pageviews' => __('Page Views'),
                    'bounce_rate' => __('Bounce Rate'),
                    'revenue' => __('Revenue'),
                    'conversions' => __('Conversions'),
                    'average_session_duration' => __('Average Session Duration'),
                    'new_users' => __('New Users'),
                    'total_users' => __('Total Users'),
                    'reach' => __('Active Users'),
                    'event_count' => __('Event Count'),
                ],
            };
        }

        $options = [];
        $channelTags = \App\Services\Analytics\ChannelCapabilityRegistry::getTags()[$channel] ?? [];

        $tagToMetricMap = self::getTagToMetricMap();

        foreach ($channelTags as $tag) {
            if (isset($tagToMetricMap[$tag])) {
                foreach ($tagToMetricMap[$tag] as $key => $label) {
                    $options[$key] = $label;
                }
            } else {
                $options["{$channel}_{$tag}_metric_placeholder"] = self::getChannelDisplayName($tag) . ' (Simulated Metric)';
            }
        }

        return $options;
    }

    public static function getAllAssetsForChannel(?string $channel): array
    {
        if (empty($channel)) {
            return [];
        }

        $project = Filament::getTenant();
        if (! $project) {
            return [];
        }

        $syncConfig = $project->sync_config ?? [];
        $channelData = $syncConfig[$channel] ?? [];
        if (! is_array($channelData)) {
            return [];
        }

        $assets = [];

        // DB items (custom assets or renamed)
        $items = \App\Models\AssetGroupItem::whereHas('group', function ($q) use ($project) {
            $q->where('project_id', $project->id);
        })
        ->where('channel', $channel)
        ->get();

        foreach ($items as $item) {
            $assets[$item->asset_id] = [
                'name' => $item->asset_name ?? $item->asset_id,
                'enabled' => true,
            ];
        }

        // Recursive scan to find all assets
        $scan = function (array $data) use (&$scan, &$assets) {
            if (
                array_key_exists('enabled', $data)
                && (array_key_exists('id', $data) || array_key_exists('url', $data) || array_key_exists('platformId', $data) || array_key_exists('platform_id', $data))
            ) {
                if (! empty($data['enabled']) && empty($data['lost_access'])) {
                    $id = (string) ($data['platformId'] ?? $data['platform_id'] ?? $data['id'] ?? $data['url'] ?? '');
                    $pageName = $data['title'] ?? $data['name'] ?? $data['page_name'] ?? $data['pageName'] ?? $data['account_name'] ?? $data['accountName'] ?? null;
                    $igName = $data['ig_account_name'] ?? $data['ig_username'] ?? $data['username'] ?? null;

                    if ($pageName && $igName && strtolower($pageName) !== strtolower($igName)) {
                        $name = "{$pageName} ({$igName})";
                    } else {
                        $name = $pageName ?? $igName ?? $data['url'] ?? $id;
                    }

                    if ($id !== '') {
                        $assets[$id] = [
                            'name' => (string) $name,
                            'enabled' => true,
                        ];
                    }
                }

                return;
            }

            foreach ($data as $value) {
                if (is_array($value)) {
                    $scan($value);
                }
            }
        };

        $scan($channelData);

        return $assets;
    }

    public static function getAssetOptionsForChannel(?string $channel): array
    {
        $allAssets = static::getAllAssetsForChannel($channel);
        $options = [];
        foreach ($allAssets as $id => $data) {
            if ($data['enabled']) {
                $options[$id] = $data['name'];
            }
        }

        return $options;
    }

    public static function getAssetGroupOptions(): array
    {
        $tenant = Filament::getTenant();
        if (! $tenant) {
            return [];
        }

        return app(\App\Services\CollaboratorAssetAccessService::class)
            ->getAllowedAssetGroupQuery($tenant, auth()->user()?->getAuthIdentifier())
            ->pluck('name', 'id')
            ->toArray();
    }

    public static function getDerivedMetricOptions(): array
    {
        $project = \Filament\Facades\Filament::getTenant();
        if (! $project) {
            return [];
        }

        return \App\Models\DerivedMetric::where('project_id', $project->id)
            ->where('is_active', true)
            ->pluck('name', 'id')
            ->toArray();
    }

    public static function getNodeSchema(string $name, string $label): array
    {
        return [
            Section::make($label)
                ->schema([
                    Hidden::make('_required_tag'),
                    Select::make($name . '_source_type')
                        ->label(__('Source Type'))
                        ->options([
                            'channel' => __('Channel Metric'),
                            'derived_metric' => __('Derived Metric'),
                        ])
                        ->default('channel')
                        ->live()
                        ->afterStateUpdated(function (Set $set, Get $get, $state) use ($name) {
                            if ($state === 'derived_metric') {
                                $set($name . '_channel', null);
                                $set($name . '_metric', null);
                                $set($name . '_asset_group', null);
                                $set($name . '_asset_filter', null);
                            } else {
                                $set($name . '_dm_id', null);
                            }
                        }),
                    Select::make($name . '_channel')
                        ->label(__('Channel (keep empty for runtime)'))
                        ->options(function (Get $get) {
                            $allChannels = self::getActiveChannels();
                            $requiredTag = $get('_required_tag');
                            if (empty($requiredTag)) {
                                return $allChannels;
                            }

                            $registryTags = \App\Services\Analytics\ChannelCapabilityRegistry::getTags();
                            $filtered = [];
                            foreach ($allChannels as $key => $label) {
                                $tags = $registryTags[$key] ?? [];
                                if (in_array($requiredTag, $tags)) {
                                    $filtered[$key] = $label;
                                }
                            }

                            return $filtered;
                        })
                        ->live()
                        ->visible(fn (Get $get) => $get($name . '_source_type') === 'channel')
                        ->extraAttributes(function (Get $get) use ($name) {
                            return empty($get($name . '_channel'))
                                ? ['class' => '[&_.fi-input-wrapper]:!ring-2 [&_.fi-input-wrapper]:!ring-amber-500 [&_.fi-input-wrapper]:!bg-amber-50 dark:[&_.fi-input-wrapper]:!bg-amber-900/20 [&_.fi-input-wrapper_*]:!text-amber-900 dark:[&_.fi-input-wrapper_*]:!text-amber-100']
                                : ['class' => '[&_.fi-input-wrapper]:!ring-2 [&_.fi-input-wrapper]:!ring-green-500 [&_.fi-input-wrapper]:!bg-green-50 dark:[&_.fi-input-wrapper]:!bg-green-900/20 [&_.fi-input-wrapper_*]:!text-green-900 dark:[&_.fi-input-wrapper_*]:!text-green-100'];
                        }),
                    Select::make($name . '_metric')
                        ->label(__('Metric'))
                        ->options(fn (Get $get) => static::getMetricOptionsForChannel($get($name . '_channel'), $get('granularity')))
                        ->visible(fn (Get $get) => $get($name . '_source_type') === 'channel'),
                    Select::make($name . '_dm_id')
                        ->label(__('Derived Metric'))
                        ->options(fn () => static::getDerivedMetricOptions())
                        ->visible(fn (Get $get) => $get($name . '_source_type') === 'derived_metric')
                        ->live(),
                    Select::make($name . '_asset_group')
                        ->label(__('Asset Group (keep empty for runtime)'))
                        ->options(fn () => static::getAssetGroupOptions())
                        ->disabled(fn (Get $get) => filled($get($name . '_asset_filter')))
                        ->visible(fn (Get $get) => $get($name . '_source_type') === 'channel')
                        ->live(),
                    Select::make($name . '_asset_filter')
                        ->label(__('Asset Filter (keep empty for runtime)'))
                        ->multiple()
                        ->options(fn (Get $get) => static::getAssetOptionsForChannel($get($name . '_channel')))
                        ->disabled(fn (Get $get) => filled($get($name . '_asset_group')))
                        ->visible(fn (Get $get) => $get($name . '_source_type') === 'channel')
                        ->live(),
                ])->columns(1),
        ];
    }

    public static function getSchema(bool $isEdit = false): array
    {
        $forwardAction = function (Set $set, Get $get, string $nextStep) {
            $history = json_decode($get('_step_history') ?? '[]', true) ?: [];
            $history[] = $get('_builder_step');
            $set('_step_history', json_encode($history));
            $set('_builder_step', $nextStep);
        };

        $backAction = function (Set $set, Get $get) {
            $history = json_decode($get('_step_history') ?? '[]', true) ?: [];
            $prevStep = array_pop($history) ?? '1_intent';
            $set('_step_history', json_encode($history));
            $set('_builder_step', $prevStep);
        };

        return [
            Hidden::make('_builder_step')->default('1_intent'),
            Hidden::make('_step_history')->default('[]'),

            Section::make(__('KPI Configuration'))
                ->schema([
                    // Step 1: Intent
                    Section::make(__('1. Choose Build Method'))
                        ->schema([
                            Radio::make('_intent')
                                ->label(__('Do you want to build a KPI from scratch or use a predefined template?'))
                                ->options([
                                    'template' => __('Use a predefined template'),
                                    'scratch' => __('Build from scratch'),
                                ])
                                ->descriptions([
                                    'template' => __('Select a predefined marketing theory scenario.'),
                                    'scratch' => __('Start with a blank canvas to explore your own hypotheses.'),
                                ])
                                ->view('filament.forms.components.playbook-radio')
                                ->live(),
                            Actions::make([
                                Actions\Action::make('next_intent')
                                    ->label(__('Next'))
                                    ->action(function (Set $set, Get $get) use ($forwardAction) {
                                        if ($get('_intent') === 'template') {
                                            $forwardAction($set, $get, '1a1_asset_group');
                                        } elseif ($get('_intent') === 'scratch') {
                                            $forwardAction($set, $get, '21_calculation');
                                        }
                                    })
                                    ->disabled(fn (Get $get) => empty($get('_intent'))),
                            ]),
                        ])
                        ->visible(fn (Get $get) => $get('_builder_step') === '1_intent'),

                    // Step 1.A.1: Asset Group Focus
                    Section::make(__('1.A.1. Focus on specific assets?'))
                        ->schema([
                            Radio::make('_focus_assets')
                                ->label(__('Do you want to focus in a specific group of assets?'))
                                ->options([
                                    'group' => __('Select an asset group'),
                                    'all' => __('All assets'),
                                ])
                                ->descriptions([
                                    'group' => __('Analyze a specific segment or campaign group.'),
                                    'all' => __('Analyze across the entire organization.'),
                                ])
                                ->view('filament.forms.components.playbook-radio')
                                ->live()
                                ->afterStateUpdated(function (Set $set, $state) {
                                    if ($state === 'all') {
                                        $set('global_asset_group', null);
                                    }
                                    $set('category_filter', []);
                                    $set('template', null);
                                }),
                            Select::make('global_asset_group')
                                ->label(__('Global Asset Group (optional)'))
                                ->options(fn () => static::getAssetGroupOptions())
                                ->visible(fn (Get $get) => $get('_focus_assets') === 'group')
                                ->live()
                                ->afterStateUpdated(function (Set $set) {
                                    $set('category_filter', []);
                                    $set('template', null);
                                }),
                            Actions::make([
                                Actions\Action::make('back_focus')
                                    ->label(__('Back'))
                                    ->color('gray')
                                    ->action(fn (Set $set, Get $get) => $backAction($set, $get)),
                                Actions\Action::make('next_focus')
                                    ->label(__('Assign & Next'))
                                    ->action(function (Set $set, Get $get) use ($forwardAction) {
                                        $forwardAction($set, $get, '1a2_template');
                                    })
                                    ->disabled(fn (Get $get) => empty($get('_focus_assets')) || ($get('_focus_assets') === 'group' && empty($get('global_asset_group')))),
                            ]),
                        ])
                        ->visible(fn (Get $get) => $get('_builder_step') === '1a1_asset_group'),

                    // Step 1.A.2: Template Selection
                    Section::make(__('1.A.2. Select Template'))
                        ->schema([
                            Grid::make(2)
                                ->schema([
                                    Group::make([
                                        Select::make('category_filter')
                                            ->label(__('Filter by category'))
                                            ->multiple()
                                            ->options(fn (Get $get) => self::getCategoryOptions($get('global_asset_group')))
                                            ->live(),

                                        Select::make('template')
                                            ->label(__('Quick Start Template'))
                                            ->allowHtml()
                                            ->searchable()
                                            ->options(fn (Get $get) => self::getTemplateOptions($get('category_filter') ?? [], $get('global_asset_group')))
                                            ->live()
                                            ->afterStateUpdated(function (\Filament\Forms\Set $set, \Filament\Forms\Get $get, $state) {
                                                if (! $state) {
                                                    return;
                                                }
                                                $kpi = PredefinedKpiRegistry::getPredefinedKpis()[$state] ?? null;
                                                if (! $kpi) {
                                                    return;
                                                }

                                                if (empty($get('name'))) {
                                                    $set('name', $kpi['name'] ?? '');
                                                }
                                                if (empty($get('description'))) {
                                                    $set('description', $kpi['description'] ?? '');
                                                }
                                                $set('calculation_type', $kpi['calculation_type']);
                                                if (!empty($kpi['default_granularity'])) {
                                                    $set('granularity', $kpi['default_granularity']);
                                                }
                                                if (!empty($kpi['default_zero_handling'])) {
                                                    $set('zero_handling', $kpi['default_zero_handling']);
                                                }
                                                if (!empty($kpi['default_edge_case_handling']['weighted'])) {
                                                    $set('edge_case_weighted', $kpi['default_edge_case_handling']['weighted']);
                                                }
                                                if (!empty($kpi['default_edge_case_handling']['grouping'])) {
                                                    $set('edge_case_grouping', $kpi['default_edge_case_handling']['grouping']);
                                                }
                                                if (isset($kpi['default_max_ratio'])) {
                                                    $set('max_ratio', $kpi['default_max_ratio']);
                                                }

                                                $activeChannels = array_keys(self::getActiveChannels());
                                                $registryTags = ChannelCapabilityRegistry::getTags();
                                                $globalGroup = $get('global_asset_group');

                                                $resolveChannel = function ($placeholder) use ($activeChannels, $registryTags, $globalGroup) {
                                                    preg_match('/__([A-Z_]+)_CHANNEL_(\d+)__/', $placeholder, $matches);
                                                    if (empty($matches[1])) {
                                                        return null;
                                                    }

                                                    $requiredTag = strtolower($matches[1]);
                                                    $index = (int)($matches[2] ?? 1) - 1;

                                                    $allowedChannels = $activeChannels;
                                                    if ($globalGroup) {
                                                        $group = \App\Models\AssetGroup::find($globalGroup);
                                                        if ($group) {
                                                            $allowedChannels = array_intersect($allowedChannels, $group->active_items->pluck('channel')->unique()->toArray());
                                                        }
                                                    }

                                                    $matchingChannels = [];
                                                    foreach ($allowedChannels as $channel) {
                                                        $tags = $registryTags[$channel] ?? [];
                                                        if (in_array($requiredTag, $tags)) {
                                                            $matchingChannels[] = $channel;
                                                        }
                                                    }

                                                    return $matchingChannels[$index] ?? null;
                                                };

                                                $extractTag = function ($placeholder) {
                                                    if (! $placeholder) {
                                                        return null;
                                                    }
                                                    preg_match('/__([A-Z_]+)_CHANNEL_\d+__/', $placeholder, $matches);

                                                    return isset($matches[1]) ? strtolower($matches[1]) : null;
                                                };

                                                $ast = $kpi['template']['ast'] ?? [];

                                                $isUnivariateAst = ($ast['type'] ?? '') === 'metric';
                                                if (in_array($kpi['calculation_type'], ['calculate_autocorrelation', 'calculate_anomaly']) || $isUnivariateAst) {
                                                    if (isset($ast['channel'])) {
                                                        $set('dependent_channel', $resolveChannel($ast['channel']));
                                                        $set('dependent_metric', $ast['metric'] ?? '');
                                                        $set('_required_tag', $extractTag($ast['channel']));
                                                        if ($globalGroup) {
                                                            $set('dependent_asset_group', $globalGroup);
                                                            $set('dependent_asset_filter', null);
                                                        }
                                                    }
                                                    $set('independent_variables', []);

                                                    return;
                                                }

                                                if (isset($ast['left']['channel'])) {
                                                    $set('dependent_channel', $resolveChannel($ast['left']['channel']));
                                                    $set('dependent_metric', $ast['left']['metric'] ?? '');
                                                    $set('_required_tag', $extractTag($ast['left']['channel']));
                                                    if ($globalGroup) {
                                                        $set('dependent_asset_group', $globalGroup);
                                                        $set('dependent_asset_filter', null);
                                                    }
                                                    $set('dependent_additional_variables', []);
                                                } elseif (($ast['left']['type'] ?? '') === 'operator' && ($ast['left']['operator'] ?? '') === '+') {
                                                    $numeratorVars = [];
                                                    $unpackNumerator = function ($node) use (&$unpackNumerator, &$numeratorVars, $resolveChannel) {
                                                        if (($node['type'] ?? '') === 'metric') {
                                                            $channel = $resolveChannel($node['channel']);
                                                            if ($channel) {
                                                                $numeratorVars[] = [
                                                                    'dependent_channel' => $channel,
                                                                    'dependent_metric' => $node['metric'] ?? '',
                                                                ];
                                                            }
                                                        } elseif (($node['type'] ?? '') === 'operator' && ($node['operator'] ?? '') === '+') {
                                                            $unpackNumerator($node['left']);
                                                            $unpackNumerator($node['right']);
                                                        }
                                                    };
                                                    $unpackNumerator($ast['left']);
                                                    if (!empty($numeratorVars)) {
                                                        $first = array_shift($numeratorVars);
                                                        $set('dependent_channel', $first['dependent_channel']);
                                                        $set('dependent_metric', $first['dependent_metric']);
                                                        $findFirstChannel = function ($node) use (&$findFirstChannel) {
                                                            if (isset($node['channel'])) {
                                                                return $node['channel'];
                                                            }
                                                            if (isset($node['left'])) {
                                                                return $findFirstChannel($node['left']);
                                                            }
                                                            return null;
                                                        };
                                                        $set('_required_tag', $extractTag($findFirstChannel($ast['left'])));
                                                        if ($globalGroup) {
                                                            $set('dependent_asset_group', $globalGroup);
                                                            $set('dependent_asset_filter', null);
                                                        }
                                                        $set('dependent_additional_variables', array_values($numeratorVars));
                                                    }
                                                }

                                                if (isset($ast['right'])) {
                                                    $independents = [];

                                                    $unpackIndependents = function ($node) use (&$unpackIndependents, $resolveChannel, $extractTag, &$independents, $globalGroup) {
                                                        if (($node['type'] ?? '') === 'metric') {
                                                            $independents[] = [
                                                                'independent_channel' => $resolveChannel($node['channel']),
                                                                'independent_metric' => $node['metric'] ?? '',
                                                                '_required_tag' => $extractTag($node['channel']),
                                                                'independent_asset_group' => $globalGroup,
                                                                'independent_asset_filter' => null,
                                                            ];
                                                        } elseif (($node['type'] ?? '') === 'operator' && $node['operator'] === '+') {
                                                            $unpackIndependents($node['left']);
                                                            $unpackIndependents($node['right']);
                                                        }
                                                    };

                                                    $unpackIndependents($ast['right']);

                                                    if (! empty($independents)) {
                                                        $repeaterData = [];
                                                        foreach ($independents as $idx => $ind) {
                                                            $repeaterData[\Illuminate\Support\Str::uuid()->toString()] = $ind;
                                                        }
                                                        $set('independent_variables', $repeaterData);
                                                    }
                                                }
                                            }),
                            ])->columnSpan(1)->extraAttributes(['class' => __('relative z-50')]),

                            Group::make([
                                            \Filament\Forms\Components\Placeholder::make('template_details')
                                                ->hiddenLabel()
                                                ->content(function (Get $get) {
                                                    $templateId = $get('template');
                                                    if (! $templateId) {
                                                        return new \Illuminate\Support\HtmlString('<div class="h-full flex items-center justify-center p-6 text-gray-500 italic bg-white dark:bg-gray-900 ring-1 ring-gray-950/5 dark:ring-white/10 rounded-xl">Select a template to view its details.</div>');
                                                    }

                                                    $kpis = \App\Services\Analytics\PredefinedKpiRegistry::getPredefinedKpis();
                                                    $kpi = $kpis[$templateId] ?? null;

                                                    if (! $kpi) {
                                                        return new \Illuminate\Support\HtmlString('<div class="text-danger-600 dark:text-danger-400 p-4 bg-danger-50 dark:bg-danger-400/10 rounded-xl ring-1 ring-danger-600/10 dark:ring-danger-400/20">Template details not found.</div>');
                                                    }

                                                    $reference = app(\App\Filament\App\Pages\KpiReference::class);
                                                    $guidance = $reference->getGuidance($templateId);

                                                    $html = '<div class="space-y-4 p-6 bg-white dark:bg-gray-900 ring-1 ring-gray-950/5 dark:ring-white/10 rounded-xl shadow-sm h-full">';
                                                    $html .= '<div><h3 class="text-lg font-semibold text-gray-950 dark:text-white">' . e($kpi['name']) . '</h3>';
                                                    $html .= '<span class="inline-block mt-1 px-2 py-1 text-xs font-medium text-primary-600 bg-primary-50 dark:text-primary-400 dark:bg-primary-400/10 rounded-full">' . e($guidance['type_label']) . '</span></div>';

                                                    if (! empty($guidance['explanation'])) {
                                                        $html .= '<div><h4 class="text-sm font-semibold text-gray-950 dark:text-white mb-1">What it does</h4><p class="text-sm text-gray-500 dark:text-gray-400 leading-relaxed">' . nl2br(e($guidance['explanation'])) . '</p></div>';
                                                    }

                                                    if (! empty($guidance['use_case'])) {
                                                        $html .= '<div><h4 class="text-sm font-semibold text-gray-950 dark:text-white mb-1">Golden use case</h4><p class="text-sm text-gray-500 dark:text-gray-400 leading-relaxed">' . nl2br(e($guidance['use_case'])) . '</p></div>';
                                                    }

                                                    if (! empty($guidance['interpretation'])) {
                                                        $html .= '<div><h4 class="text-sm font-semibold text-gray-950 dark:text-white mb-1">Reading the result</h4><p class="text-sm text-gray-500 dark:text-gray-400 leading-relaxed">' . nl2br(e($guidance['interpretation'])) . '</p></div>';
                                                    }

                                                    $html .= '</div>';

                                                    return new \Illuminate\Support\HtmlString($html);
                                                }),
                            ])->columnSpan(1),
                        ]),
                        Toggle::make('keep_template_guidance')
                            ->label(__('Automatically show template guidance in dashboard tooltips'))
                            ->helperText(__('When enabled, widgets using this KPI will display the template\'s theoretical guidance in their tooltips. You can change this later.')),
                        Actions::make([
                                Actions\Action::make('back_template')
                                                ->label(__('Back'))
                                                ->color('gray')
                                                ->action(fn (Set $set, Get $get) => $backAction($set, $get)),
                                Actions\Action::make('next_template')
                                                ->label(__('Next'))
                                                ->action(fn (Set $set, Get $get) => $forwardAction($set, $get, '22_series'))
                                                ->disabled(fn (Get $get) => empty($get('template'))),
                            ]),
                        ])
                        ->visible(fn (Get $get) => $get('_builder_step') === '1a2_template'),

                    // Step 2.1: Calculation Type
                    Section::make(__('2.1. Choose your calculation type'))
                        ->schema([
                            Select::make('calculation_type')
                                ->label(__('Calculation Type'))
                                ->options([
                                                'calculate_regression' => __('Multiple Linear Regression'),
                                                'calculate_elasticity' => __('Elasticity'),
                                                'calculate_autocorrelation' => __('Autocorrelation'),
                                                'calculate_granger' => __('Granger Causality'),
                                                'calculate_macd' => __('MACD Momentum'),
                                                'calculate_anomaly' => __('Anomaly Detection'),
                                                'calculate_trend_linear' => __('Linear Trend'),
                                                'calculate_trend_holt_winters' => __('Holt-Winters (Seasonality)'),
                                                'calculate_trend_logarithmic' => __('Logarithmic Trend'),
                                                'calculate_trend_ema' => __('EMA Crossover'),
                                ])
                                ->required()
                                ->live(),
                            Actions::make([
                                Actions\Action::make('back_calc')
                                                ->label(__('Back'))
                                                ->color('gray')
                                                ->action(fn (Set $set, Get $get) => $backAction($set, $get)),
                                Actions\Action::make('next_calc')
                                                ->label(__('Next'))
                                                ->action(fn (Set $set, Get $get) => $forwardAction($set, $get, '22_series'))
                                                ->disabled(fn (Get $get) => empty($get('calculation_type'))),
                            ]),
                        ])
                        ->visible(fn (Get $get) => $get('_builder_step') === '21_calculation'),

                    // Step 2.2: Configure Series (Horizontal layout)
                    Section::make(__('2.2. Configure Series'))
                        ->schema([
                            Grid::make(3) // Ensure max 3 cols logic if we need it
                                ->schema([
                                                Group::make(self::getNodeSchema('dependent', 'Dependent Variable (Y - Explained)'))
                                                    ->columnSpan(1),

                                                Repeater::make('independent_variables')
                                                    ->label(__('Independent Variables (X - Explanatory)'))
                                                    ->schema(self::getNodeSchema('independent', 'Variable'))
                                                    ->grid(2)
                                                    ->columnSpan(2)
                                                    ->defaultItems(1)
                                                    ->minItems(0)
                                                    ->visible(fn (Get $get) => in_array($get('calculation_type'), ['calculate_regression', 'calculate_elasticity', 'calculate_granger', 'calculate_macd'])),
                                ])
                                ->columns([
                                                'sm' => 1,
                                                'md' => 3,
                                ]),
                            Actions::make(array_filter([
                                ! $isEdit ? Actions\Action::make('back_series')
                                                ->label(__('Back'))
                                                ->color('gray')
                                                ->action(fn (Set $set, Get $get) => $backAction($set, $get)) : null,
                                Actions\Action::make('next_series')
                                                ->label(__('Next'))
                                                ->action(fn (Set $set, Get $get) => $forwardAction($set, $get, '23_scope'))
                                                ->modalHidden(function (Get $get) {
                                                    $isBivariate = in_array($get('calculation_type'), ['calculate_regression', 'calculate_elasticity', 'calculate_granger', 'calculate_macd']);
                                                    if (! $isBivariate) {
                                                        return true; // hide modal
                                                    }

                                                    $depSourceType = $get('dependent_source_type') ?? 'channel';
                                                    if ($depSourceType === 'derived_metric') {
                                                        return true; // hide modal — DMs have no asset groups
                                                    }

                                                    $depGroup = $get('dependent_asset_group');
                                                    $independents = $get('independent_variables') ?? [];

                                                    $depStr = is_array($depGroup) ? implode(',', $depGroup) : (string) ($depGroup ?? '');
                                                    $hasAnyGroup = $depStr !== '';

                                                    foreach ($independents as $item) {
                                                        $indSourceType = $item['independent_source_type'] ?? 'channel';
                                                        if ($indSourceType === 'derived_metric') {
                                                            continue; // skip DM variables
                                                        }
                                                        $indVal = $item['independent_asset_group'] ?? '';
                                                        $indStr = is_array($indVal) ? implode(',', $indVal) : (string) $indVal;
                                                        if ($indStr !== '') {
                                                            $hasAnyGroup = true;
                                                        }
                                                    }

                                                    if (! $hasAnyGroup) {
                                                        return true; // hide modal
                                                    }

                                                    foreach ($independents as $item) {
                                                        $indSourceType = $item['independent_source_type'] ?? 'channel';
                                                        if ($indSourceType === 'derived_metric') {
                                                            continue; // skip DM variables
                                                        }
                                                        $indVal = $item['independent_asset_group'] ?? '';
                                                        $indStr = is_array($indVal) ? implode(',', $indVal) : (string) $indVal;

                                                        if ($indStr !== $depStr) {
                                                            return false; // mismatch found, do NOT hide modal!
                                                        }
                                                    }

                                                    return true; // all match, hide modal
                                                })
                                                ->requiresConfirmation()
                                                ->modalHeading(__('Asset Groups Mismatch'))
                                                ->modalDescription(__('You have selected different asset groups (or left some unassigned) across your series. This might lead to mismatched comparative data if the underlying assets are fundamentally different. Are you sure you want to proceed?'))
                                                ->modalSubmitActionLabel(__('Yes, proceed')),
                            ])),
                        ])
                        ->visible(fn (Get $get) => $get('_builder_step') === '22_series'),

                    // Step 2.3: Scope / Filters
                    Section::make(__('2.3. Scope & Filters'))
                        ->schema([
                            Select::make('account_type')
                                ->label(__('Social / Asset Scope (Account Type)'))
                                ->options(function (Get $get) {
                                    $ch = $get('dependent_channel');
                                    return $ch ? \App\Services\Analytics\ChannelGranularityRegistry::getDependenciesForChannel($ch) : [];
                                })
                                ->visible(fn (Get $get) => ($get('dependent_channel') ?? '') === 'facebook_organic')
                                ->placeholder(__('Auto-detect from metric or dashboard tab'))
                                ->helperText(__('Explicitly target Instagram Account or Facebook Page.')),
                            DatePicker::make('start_date')->label(__('Start Date')),
                            DatePicker::make('end_date')->label(__('End Date')),
                            Select::make('granularity')
                                ->label(__('Granularity'))
                                ->options([
                                                'daily' => __('Daily'),
                                                'weekly' => __('Weekly'),
                                                'monthly' => __('Monthly'),
                                                'query' => __('Query / Keyword'),
                                                'dimensions.page' => __('Page / URL'),
                                                'campaign' => __('Campaign'),
                                                'adset' => __('Ad Set / Ad Group'),
                                                'ad' => __('Ad / Creative'),
                                                'post' => __('Post / Media'),
                                                'country' => __('Country / Geo'),
                                                'device' => __('Device'),
                                ])
                                ->default('daily'),
                            Select::make('zero_handling')
                                ->label(__('Zero Handling'))
                                ->options([
                                                'remove' => __('Remove Zeroes'),
                                                'trim' => __('Trim Leading/Trailing Zeroes'),
                                                'keep' => __('Keep Zeroes'),
                                ])
                                ->default('trim')
                                ->helperText('How to treat zero values in the time series before analysis.'),
                            Toggle::make('edge_case_weighted')
                                ->label(__('Weighted regression (WLS)'))
                                ->default(true)
                                ->helperText('Weight each dimension value by its volume (e.g. clicks), so items with more data influence the regression line proportionally more. Keeps the math robust even with noisy low-volume items.'),
                            Select::make('edge_case_grouping')
                                ->label(__('Group low-frequency values'))
                                ->options([
                                    'none' => __('No grouping'),
                                    'histogram' => __('Auto histogram-elbow'),
                                    'percentile' => __('Bottom percentile'),
                                ])
                                ->default('none')
                                ->helperText('Groups sparse dimension values (e.g. queries with very few clicks) into a single "others" centroid to improve chart readability.'),
                            TextInput::make('max_ratio')
                                ->label(__('Max ratio cap'))
                                ->numeric()
                                ->minValue(0)
                                ->step(0.01)
                                ->placeholder('e.g. 1.0')
                                ->helperText('Caps the Y-axis ratio at this value (e.g. 1.0 = 100%). Scatter points where the computed ratio exceeds the cap are filtered. Leave empty for no cap.'),
                            Actions::make([
                                Actions\Action::make('back_scope')
                                                ->label(__('Back'))
                                                ->color('gray')
                                                ->action(fn (Set $set, Get $get) => $backAction($set, $get)),
                                Actions\Action::make('next_scope')
                                                ->label(__('Next'))
                                                ->action(fn (Set $set, Get $get) => $forwardAction($set, $get, '24_info')),
                            ]),
                        ])
                        ->visible(fn (Get $get) => $get('_builder_step') === '23_scope'),

                    // Step 2.4: General Information
                    Section::make(__('2.4. General Information'))
                        ->schema([
                            TextInput::make('name')
                                ->label(__('KPI Name'))
                                ->required()
                                ->maxLength(255)
                                ->live(debounce: 500),
                            Textarea::make('description')
                                ->label(__('Description'))
                                ->maxLength(65535)
                                ->columnSpanFull(),
                            Toggle::make('is_active')
                                ->label(__('Active'))
                                ->default(true),
                            Actions::make([
                                Actions\Action::make('back_info')
                                                ->label(__('Back'))
                                                ->color('gray')
                                                ->action(fn (Set $set, Get $get) => $backAction($set, $get)),
                                Actions\Action::make('next_info')
                                                ->label(__('Next'))
                                                ->action(fn (Set $set, Get $get) => $forwardAction($set, $get, '25_summary'))
                                                ->disabled(fn (Get $get) => empty($get('name'))),
                            ])->columnSpanFull(),
                        ])->columns(2)
                        ->visible(fn (Get $get) => $get('_builder_step') === '24_info'),

                    // Step 2.5: Summary & Create
                    Section::make(__('2.5. Review & Create'))
                        ->schema([
                            Placeholder::make('kpi_summary')
                                ->hiddenLabel()
                                ->content(function (Get $get) {
                                    return self::generateSummaryHtml(function ($key) use ($get) {
                                        return $get($key);
                                    });
                                }),
                            Toggle::make('keep_template_guidance')
                                ->label(__('Show template guidance in dashboard tooltips'))
                                ->helperText(__('When enabled, widgets using this KPI will display the template\'s theoretical guidance in their tooltips.'))
                                ->visible(fn (Get $get) => !empty($get('template'))),
                            Actions::make(array_filter([
                                Actions\Action::make('back_summary')
                                                ->label(__('Back'))
                                                ->color('gray')
                                                ->action(fn (Set $set, Get $get) => $backAction($set, $get)),
                                Actions\Action::make('create_kpi')
                                                ->label($isEdit ? __('Save changes') : __('Create'))
                                                ->color('primary')
                                                ->submit($isEdit ? 'save' : 'create'),
                                ! $isEdit ? Actions\Action::make('create_another_kpi')
                                                ->label(__('Create & create another'))
                                                ->color('gray')
                                                ->submit('create')
                                                ->extraAttributes(['name' => __('createAnother'), 'value' => true]) : null,
                            ])),
                        ])
                        ->visible(fn (Get $get) => $get('_builder_step') === '25_summary'),
                ]),
        ];
    }

    public static function generateSummaryHtml(callable $get): HtmlString
    {
        $calcLabels = self::getCalculationTypeOptions();

        $name = e($get('name') ?: '—');
        $desc = e($get('description') ?: '—');
        $active = $get('is_active') ? '✅ Active' : '❌ Inactive';
        $calc = e($calcLabels[$get('calculation_type')] ?? ($get('calculation_type') ?: '—'));
        $granularity = e(ucfirst($get('granularity') ?: '—'));
        $start = e($get('start_date') ?: 'Not set');
        $end = e($get('end_date') ?: 'Not set');
        $zeroLabels = ['remove' => __('Remove Zeroes'), 'trim' => __('Trim Leading/Trailing'), 'keep' => __('Keep Zeroes')];
        $zero = e($zeroLabels[$get('zero_handling')] ?? ($get('zero_handling') ?: '—'));

        // Template info
        $templateKey = $get('template');
        $templateName = '—';
        if ($templateKey) {
            $kpis = PredefinedKpiRegistry::getPredefinedKpis();
            $templateName = e($kpis[$templateKey]['name'] ?? $templateKey);
        }

        // Dependent variable
        $depSourceType = $get('dependent_source_type') ?? 'channel';
        if ($depSourceType === 'derived_metric') {
            $depDmId = $get('dependent_dm_id');
            $depDm = $depDmId ? \App\Models\DerivedMetric::find($depDmId) : null;
            $depChannel = 'Derived Metric';
            $depMetric = e($depDm?->name ?? '—');
        } else {
            $depChannel = e(Str::headline($get('dependent_channel') ?: '—'));
            $depMetric = e(Str::headline($get('dependent_metric') ?: '—'));
        }

        $getAssetsText = function ($groupVal, $filterVal) {
            if ($groupVal) {
                $group = \App\Models\AssetGroup::find($groupVal);

                return $group ? '<span class="px-2 py-0.5 rounded text-xs font-medium bg-primary-50 text-primary-600 dark:bg-primary-900/20 dark:text-primary-400">Group: ' . e($group->name) . '</span>' : 'Group ID: ' . $groupVal;
            } elseif ($filterVal) {
                $count = is_array($filterVal) ? count($filterVal) : 1;

                return '<span class="px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600 dark:bg-white/10 dark:text-gray-300">' . $count . ' selected</span>';
            }

            return '<span class="px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-500 dark:bg-white/5 dark:text-gray-400 italic">Runtime</span>';
        };

        $depAssets = $getAssetsText($get('dependent_asset_group'), $get('dependent_asset_filter'));

        // Independent variables
        $independents = $get('independent_variables') ?? [];
        $indHtml = '';
        $idx = 1;
        foreach ($independents as $var) {
            $indSourceType = $var['independent_source_type'] ?? 'channel';
            if ($indSourceType === 'derived_metric') {
                $indDmId = $var['independent_dm_id'] ?? null;
                $indDm = $indDmId ? \App\Models\DerivedMetric::find($indDmId) : null;
                $ch = 'Derived Metric';
                $me = e($indDm?->name ?? '—');
                $ast = '—';
            } else {
                $ch = e(Str::headline($var['independent_channel'] ?? '—'));
                $me = e(Str::headline($var['independent_metric'] ?? '—'));
                $ast = $getAssetsText($var['independent_asset_group'] ?? null, $var['independent_asset_filter'] ?? null);
            }
            $indHtml .= "<tr><td class=\"px-3 py-2 text-sm text-gray-500 dark:text-gray-400\">X{$idx}</td><td class=\"px-3 py-2 text-sm text-gray-950 dark:text-white\">{$ch}</td><td class=\"px-3 py-2 text-sm text-gray-950 dark:text-white\">{$me}</td><td class=\"px-3 py-2 text-sm text-gray-950 dark:text-white\">{$ast}</td></tr>";
            $idx++;
        }

        $html = '<div class="space-y-6">';

        // General section
        $html .= '<div class="p-4 bg-white dark:bg-gray-900 ring-1 ring-gray-950/5 dark:ring-white/10 rounded-xl">';
        $html .= '<h4 class="text-sm font-semibold text-gray-950 dark:text-white mb-3">General</h4>';
        $html .= '<dl class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm">';
        $html .= "<dt class=\"text-gray-500 dark:text-gray-400\">Name</dt><dd class=\"text-gray-950 dark:text-white font-medium\">{$name}</dd>";
        $html .= "<dt class=\"text-gray-500 dark:text-gray-400\">Status</dt><dd>{$active}</dd>";
        $html .= "<dt class=\"text-gray-500 dark:text-gray-400 col-span-2\">Description</dt><dd class=\"text-gray-950 dark:text-white col-span-2\">{$desc}</dd>";
        $html .= '</dl></div>';

        // Configuration section
        $html .= '<div class="p-4 bg-white dark:bg-gray-900 ring-1 ring-gray-950/5 dark:ring-white/10 rounded-xl">';
        $html .= '<h4 class="text-sm font-semibold text-gray-950 dark:text-white mb-3">Configuration</h4>';
        $html .= '<dl class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm">';
        $html .= "<dt class=\"text-gray-500 dark:text-gray-400\">Calculation</dt><dd class=\"text-gray-950 dark:text-white font-medium\">{$calc}</dd>";
        if ($templateKey) {
            $html .= "<dt class=\"text-gray-500 dark:text-gray-400\">Template</dt><dd class=\"text-gray-950 dark:text-white\">{$templateName}</dd>";
        }
        if (($get('dependent_channel') ?? '') === 'facebook_organic') {
            $accountTypeVal = $get('account_type');
            $accountTypeLabels = ['instagram_account' => __('Instagram Account'), 'facebook_page' => __('Facebook Page')];
            $accountTypeText = e($accountTypeLabels[$accountTypeVal] ?? ($accountTypeVal ? ucfirst($accountTypeVal) : __('Auto-detect')));
            $html .= "<dt class=\"text-gray-500 dark:text-gray-400\">Account Scope</dt><dd class=\"text-gray-950 dark:text-white\">{$accountTypeText}</dd>";
        }
        $html .= "<dt class=\"text-gray-500 dark:text-gray-400\">Granularity</dt><dd class=\"text-gray-950 dark:text-white\">{$granularity}</dd>";
        $html .= "<dt class=\"text-gray-500 dark:text-gray-400\">Zero handling</dt><dd class=\"text-gray-950 dark:text-white\">{$zero}</dd>";
        $groupLabels = ['none' => 'No grouping', 'histogram' => 'Histogram-elbow', 'percentile' => 'Bottom percentile'];
        $wlsStatus = $get('edge_case_weighted') ? '✅ WLS' : '—';
        $groupMethod = e($groupLabels[$get('edge_case_grouping')] ?? ($get('edge_case_grouping') ?: '—'));
        $maxRatio = $get('max_ratio');
        $maxRatioText = $maxRatio !== null && $maxRatio !== '' ? 'Cap at ' . e($maxRatio) : 'No cap';
        $html .= "<dt class=\"text-gray-500 dark:text-gray-400\">Edge case handling</dt><dd class=\"text-gray-950 dark:text-white\">{$wlsStatus} / {$groupMethod} / {$maxRatioText}</dd>";
        $html .= "<dt class=\"text-gray-500 dark:text-gray-400\">Date range</dt><dd class=\"text-gray-950 dark:text-white\">{$start} → {$end}</dd>";
        $html .= '</dl></div>';

        // Series section
        $html .= '<div class="p-4 bg-white dark:bg-gray-900 ring-1 ring-gray-950/5 dark:ring-white/10 rounded-xl">';
        $html .= '<h4 class="text-sm font-semibold text-gray-950 dark:text-white mb-3">Series</h4>';
        $html .= '<table class="w-full text-left"><thead><tr class="border-b border-gray-200 dark:border-white/10"><th class="px-3 py-2 text-xs font-medium text-gray-500 dark:text-gray-400">Role</th><th class="px-3 py-2 text-xs font-medium text-gray-500 dark:text-gray-400">Channel</th><th class="px-3 py-2 text-xs font-medium text-gray-500 dark:text-gray-400">Metric</th><th class="px-3 py-2 text-xs font-medium text-gray-500 dark:text-gray-400">Assets</th></tr></thead><tbody>';
        $html .= "<tr class=\"border-b border-gray-100 dark:border-white/5\"><td class=\"px-3 py-2 text-sm text-gray-500 dark:text-gray-400\">Y (Dependent)</td><td class=\"px-3 py-2 text-sm text-gray-950 dark:text-white\">{$depChannel}</td><td class=\"px-3 py-2 text-sm text-gray-950 dark:text-white\">{$depMetric}</td><td class=\"px-3 py-2 text-sm text-gray-950 dark:text-white\">{$depAssets}</td></tr>";
        $html .= $indHtml;
        $html .= '</tbody></table></div>';

        $html .= '</div>';

        return new HtmlString($html);
    }

    public static function getTagToMetricMap(): array
    {
        return [
            'spendable' => ['spend' => __('Spend')],
            'clickable' => ['clicks' => __('Clicks')],
            'impressionable' => ['impressions' => __('Impressions')],
            'revenue_tracked' => ['revenue' => __('Revenue'), 'purchase_roas' => __('ROAS')],
            'conversion_tracked' => ['conversions' => __('Conversions'), 'results' => __('Results'), 'cost_per_result' => __('Cost per Result')],
            'traffic_tracked' => ['sessions' => __('Sessions'), 'pageviews' => __('Pageviews'), 'new_users' => __('New Users')],
            'behavior_tracked' => ['bounce_rate' => __('Bounce Rate'), 'average_session_duration' => __('Avg Session Duration')],
            'organic_social' => [
                'reach' => __('Reach'),
                'page_views_total' => __('Page Views'),
                'video_views' => __('Video Views'),
                'follows_and_unfollows' => __('Follows & Unfollows'),
                'total_interactions' => __('Total Interactions'),
                'likes' => __('Likes'),
                'comments' => __('Comments'),
                'views' => __('Views'),
                'profile_views' => __('Profile Views'),
                'website_clicks' => __('Website Clicks'),
                'profile_links_taps' => __('Profile Links Taps'),
                'saves' => __('Saves'),
                'shares' => __('Shares'),
                'replies' => __('Replies'),
                'accounts_engaged' => __('Accounts Engaged'),
                'content_views' => __('Content Views'),
                'engagements' => __('Engagements'),
                'followers' => __('Followers'),
                'engaged_users' => __('Engaged Users'),
            ],
            'reach_driven' => ['reach' => __('Reach')],
            'email_marketing' => ['sends' => __('Sends'), 'opens' => __('Opens'), 'clicks' => __('Email Clicks'), 'bounces' => __('Bounces')],
            'ecommerce' => ['orders' => __('Orders'), 'aov' => __('AOV'), 'revenue' => __('Revenue')],
            'seo' => ['clicks' => __('Search Clicks'), 'impressions' => __('Search Impressions'), 'position' => __('Average Position'), 'ctr' => __('CTR')],
            'paid_media' => ['spend' => __('Spend'), 'clicks' => __('Clicks'), 'impressions' => __('Impressions'), 'cpm' => __('CPM'), 'conversions' => __('Conversions'), 'cpc' => __('CPC'), 'purchase_roas' => __('ROAS'), 'cost_per_result' => __('Cost per Result'), 'results' => __('Results'), 'link_clicks' => __('Link Clicks')],
            'analytics' => ['sessions' => __('Sessions'), 'pageviews' => __('Pageviews'), 'bounce_rate' => __('Bounce Rate'), 'new_users' => __('New Users'), 'average_session_duration' => __('Avg Session Duration')],
        ];
    }

    public static function getAllMetricOptions(): array
    {
        $options = [];
        foreach (self::getTagToMetricMap() as $metrics) {
            foreach ($metrics as $key => $label) {
                $options[$key] = $label;
            }
        }
        return $options;
    }

    public static function getCalculationTypeOptions(): array
    {
        return [
            'calculate_regression' => __('Multiple Linear Regression'),
            'calculate_elasticity' => __('Elasticity'),
            'calculate_autocorrelation' => __('Autocorrelation'),
            'calculate_granger' => __('Granger Causality'),
            'calculate_macd' => __('MACD Momentum'),
            'calculate_anomaly' => __('Anomaly Detection'),
            'calculate_trend_linear' => __('Linear Trend'),
            'calculate_trend_holt_winters' => __('Holt-Winters (Seasonality)'),
            'calculate_trend_logarithmic' => __('Logarithmic Trend'),
            'calculate_trend_ema' => __('EMA Crossover'),
        ];
    }
}
