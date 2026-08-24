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
    public static function getTenant()
    {
        return Filament::getTenant() ?? (app()->bound('current_public_project') ? app('current_public_project') : null);
    }

    public static function getActiveChannels(): array
    {
        $tenant = static::getTenant();
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
        $tenant = static::getTenant();
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

        $project = static::getTenant();
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

        $project = static::getTenant();
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
        $tenant = static::getTenant();
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
        $project = static::getTenant();
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
                    Select::make($name . '_dependency')
                        ->label(__('Data Scope / Matrix'))
                        ->options(fn (Get $get) => filled($get($name . '_channel'))
                            ? \App\Services\Analytics\ChannelGranularityRegistry::getDependenciesForChannel($get($name . '_channel'))
                            : []
                        )
                        ->placeholder(__('Default Scope'))
                        ->live()
                        ->visible(fn (Get $get) => $get($name . '_source_type') === 'channel' && filled($get($name . '_channel')) && ! empty(\App\Services\Analytics\ChannelGranularityRegistry::getDependenciesForChannel($get($name . '_channel')))),
                    Select::make($name . '_metric')
                        ->label(__('Metric'))
                        ->options(fn (Get $get) => static::getMetricOptionsForChannel(
                            $get($name . '_channel'),
                            $get('granularity'),
                            $get($name . '_dependency') ?? $get('channel_dependency')
                        ))
                        ->visible(fn (Get $get) => $get($name . '_source_type') === 'channel'),
                    Select::make($name . '_dm_id')
                        ->label(__('Derived Metric'))
                        ->options(fn () => static::getDerivedMetricOptions())
                        ->visible(fn (Get $get) => $get($name . '_source_type') === 'derived_metric')
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

            Placeholder::make('_wizard_header')
                ->hiddenLabel()
                ->columnSpanFull()
                ->content(function (Get $get) {
                    $step = $get('_builder_step') ?? '1_intent';

                    $currentStepNum = match (true) {
                        in_array($step, ['1_intent', '1a1_asset_group', '1a2_template', '21_calculation']) => 1,
                        $step === '22_series' => 2,
                        $step === '23_scope' => 3,
                        in_array($step, ['3_summary', '4_save']) => 4,
                        default => 1,
                    };

                    $steps = [
                        1 => __('1. Method & Template'),
                        2 => __('2. Configure Series'),
                        3 => __('3. Scope & Filters'),
                        4 => __('4. Summary & Save'),
                    ];

                    $html = '<div class="fi-fo-wizard fi-contained rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 mb-6 overflow-hidden">';
                    $html .= '<ol role="list" class="fi-fo-wizard-header grid divide-y divide-gray-200 dark:divide-white/5 md:grid-flow-col md:divide-y-0 md:overflow-x-auto border-b border-gray-200 dark:border-white/10">';

                    $stepsCount = count($steps);
                    $i = 0;
                    foreach ($steps as $num => $title) {
                        $i++;
                        $isActive = $num === $currentStepNum;
                        $isPast = $num < $currentStepNum;
                        $isLast = $i === $stepsCount;

                        $stepNumStr = str_pad($num, 2, '0', STR_PAD_LEFT);

                        $iconCtnClasses = $isPast
                            ? 'bg-primary-600 dark:bg-primary-500'
                            : ($isActive ? 'border-2 border-primary-600 dark:border-primary-500' : 'border-2 border-gray-300 dark:border-gray-600');

                        $indicatorClasses = $isActive
                            ? 'text-primary-600 dark:text-primary-500 font-bold'
                            : 'text-gray-500 dark:text-gray-400 font-medium';

                        $labelClasses = $isActive
                            ? 'text-primary-600 dark:text-primary-400 font-medium'
                            : ($isPast ? 'text-gray-950 dark:text-white font-medium' : 'text-gray-500 dark:text-gray-400 font-medium');

                        $html .= '<li class="fi-fo-wizard-header-step relative flex ' . ($isActive ? 'fi-active' : ($isPast ? 'fi-completed' : '')) . '">';
                        $html .= '<div class="fi-fo-wizard-header-step-button flex h-full items-center gap-x-4 px-6 py-4 text-start w-full">';
                        
                        $html .= '<div class="fi-fo-wizard-header-step-icon-ctn flex h-10 w-10 shrink-0 items-center justify-center rounded-full ' . $iconCtnClasses . '">';
                        if ($isPast) {
                            $html .= '<svg class="fi-fo-wizard-header-step-icon h-6 w-6 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>';
                        } else {
                            $html .= '<span class="fi-fo-wizard-header-step-indicator text-sm ' . $indicatorClasses . '">' . $stepNumStr . '</span>';
                        }
                        $html .= '</div>';

                        $html .= '<div class="grid justify-items-start md:w-max md:max-w-60">';
                        $html .= '<span class="fi-fo-wizard-header-step-label text-sm ' . $labelClasses . '">' . e($title) . '</span>';
                        $html .= '</div>';

                        $html .= '</div>';

                        if (! $isLast) {
                            $html .= '<div aria-hidden="true" class="fi-fo-wizard-header-step-separator absolute end-0 hidden h-full w-5 md:block"><svg fill="none" preserveAspectRatio="none" viewBox="0 0 22 80" class="h-full w-full text-gray-200 dark:text-white/5 rtl:rotate-180"><path d="M0 -2L20 40L0 82" stroke-linejoin="round" stroke="currentcolor" vector-effect="non-scaling-stroke" /></svg></div>';
                        }

                        $html .= '</li>';
                    }

                    $html .= '</ol></div>';

                    return new \Illuminate\Support\HtmlString($html);
                }),

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
                                            $forwardAction($set, $get, '1a2_template');
                                        } elseif ($get('_intent') === 'scratch') {
                                            $forwardAction($set, $get, '21_calculation');
                                        }
                                    })
                                    ->disabled(fn (Get $get) => empty($get('_intent'))),
                            ]),
                        ])
                        ->visible(fn (Get $get) => $get('_builder_step') === '1_intent'),

                    // Step 1.A.2: Template Selection
                    Section::make(__('1.A.2. Select Template'))
                        ->schema([
                            Grid::make(2)
                                ->schema([
                                    Group::make([
                                        Select::make('category_filter')
                                            ->label(__('Filter by category'))
                                            ->multiple()
                                            ->options(fn (Get $get) => self::getCategoryOptions())
                                            ->live(),

                                        Select::make('template')
                                            ->label(__('Quick Start Template'))
                                            ->allowHtml()
                                            ->searchable()
                                            ->options(fn (Get $get) => self::getTemplateOptions($get('category_filter') ?? []))
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

                                                $resolveChannel = function ($channelPlaceholder) use ($get) {
                                                    if (empty($channelPlaceholder)) {
                                                        return null;
                                                    }
                                                    if (! str_starts_with($channelPlaceholder, '__')) {
                                                        return $channelPlaceholder;
                                                    }

                                                    preg_match('/__([A-Z_]+)_CHANNEL_\d+__/', $channelPlaceholder, $matches);
                                                    $tag = isset($matches[1]) ? strtolower($matches[1]) : null;
                                                    if (! $tag) {
                                                        return null;
                                                    }

                                                    $activeChannels = self::getActiveChannels();
                                                    $registryTags = \App\Services\Analytics\ChannelCapabilityRegistry::getTags();
                                                    foreach ($activeChannels as $key => $label) {
                                                        if (in_array($tag, $registryTags[$key] ?? [])) {
                                                            return $key;
                                                        }
                                                    }

                                                    return null;
                                                };

                                                $extractTag = function ($placeholder) {
                                                    if (empty($placeholder) || ! str_starts_with($placeholder, '__')) {
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
                                                        $set('dependent_dependency', $ast['dependency'] ?? null);
                                                        $set('dependent_metric', $ast['metric'] ?? '');
                                                        $set('_required_tag', $extractTag($ast['channel']));
                                                    }
                                                    $set('independent_variables', []);

                                                    return;
                                                }

                                                if (isset($ast['left']['channel'])) {
                                                    $set('dependent_channel', $resolveChannel($ast['left']['channel']));
                                                    $set('dependent_dependency', $ast['left']['dependency'] ?? null);
                                                    $set('dependent_metric', $ast['left']['metric'] ?? '');
                                                    $set('_required_tag', $extractTag($ast['left']['channel']));
                                                    $set('dependent_additional_variables', []);
                                                } elseif (($ast['left']['type'] ?? '') === 'operator' && ($ast['left']['operator'] ?? '') === '+') {
                                                    $numeratorVars = [];
                                                    $unpackNumerator = function ($node) use (&$unpackNumerator, &$numeratorVars, $resolveChannel) {
                                                        if (($node['type'] ?? '') === 'metric') {
                                                            $channel = $resolveChannel($node['channel']);
                                                            if ($channel) {
                                                                $numeratorVars[] = [
                                                                    'dependent_channel' => $channel,
                                                                    'dependent_dependency' => $node['dependency'] ?? null,
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
                                                        $set('dependent_dependency', $first['dependent_dependency'] ?? null);
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
                                                        $set('dependent_additional_variables', array_values($numeratorVars));
                                                    }
                                                }

                                                if (isset($ast['right'])) {
                                                    $independents = [];

                                                    $unpackIndependents = function ($node) use (&$unpackIndependents, $resolveChannel, $extractTag, &$independents) {
                                                        if (($node['type'] ?? '') === 'metric') {
                                                            $independents[] = [
                                                                'independent_channel' => $resolveChannel($node['channel']),
                                                                'independent_dependency' => $node['dependency'] ?? null,
                                                                'independent_metric' => $node['metric'] ?? '',
                                                                '_required_tag' => $extractTag($node['channel']),
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
                                                            $repeaterData[] = [
                                                                'independent_channel' => $ind['independent_channel'] ?? null,
                                                                'independent_dependency' => $ind['independent_dependency'] ?? null,
                                                                'independent_metric' => $ind['independent_metric'] ?? null,
                                                                '_required_tag' => $ind['_required_tag'] ?? null,
                                                            ];
                                                        }
                                                        $set('independent_variables', $repeaterData);
                                                    }
                                                }
                                            }),
                                    ]),

                                    // Template Preview Card
                                    Group::make([
                                        Placeholder::make('_template_preview')
                                            ->hiddenLabel()
                                            ->content(function (Get $get) {
                                                $templateKey = $get('template');
                                                if (! $templateKey) {
                                                    return new HtmlString('<div class="p-6 bg-gray-50 dark:bg-white/5 rounded-xl border border-dashed border-gray-200 dark:border-white/10 text-center text-sm text-gray-500">Select a template to view details</div>');
                                                }

                                                $kpi = PredefinedKpiRegistry::getPredefinedKpis()[$templateKey] ?? null;
                                                if (! $kpi) {
                                                    return new HtmlString('');
                                                }

                                                $calcLabels = [
                                                    'calculate_regression' => 'Multiple Linear Regression',
                                                    'calculate_elasticity' => 'Elasticity',
                                                    'calculate_autocorrelation' => 'Autocorrelation',
                                                    'calculate_granger' => 'Granger Causality',
                                                    'calculate_macd' => 'MACD Momentum',
                                                    'calculate_anomaly' => 'Anomaly Detection',
                                                    'calculate_trend_linear' => 'Linear Trend',
                                                    'calculate_trend_holt_winters' => 'Holt-Winters (Seasonality)',
                                                    'calculate_trend_logarithmic' => 'Logarithmic Trend',
                                                    'calculate_trend_ema' => 'EMA Crossover',
                                                ];

                                                $calcName = $calcLabels[$kpi['calculation_type']] ?? $kpi['calculation_type'];

                                                return new HtmlString(
                                                    '<div class="space-y-4 p-6 bg-white dark:bg-gray-900 ring-1 ring-gray-950/5 dark:ring-white/10 rounded-xl shadow-sm h-full">'
                                                    . '<div><h3 class="text-lg font-semibold text-gray-950 dark:text-white">' . e($kpi['name']) . '</h3></div>'
                                                    . '<div><p class="text-sm text-gray-500 dark:text-gray-400 leading-relaxed">' . e($kpi['description'] ?? '') . '</p></div>'
                                                    . '<div class="flex flex-wrap gap-2">'
                                                    . '<span class="px-2 py-1 text-xs font-medium rounded-full bg-primary-50 dark:bg-primary-900/20 text-primary-600 dark:text-primary-400">' . e($calcName) . '</span>'
                                                    . '<span class="px-2 py-1 text-xs font-medium rounded-full bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400">' . ucfirst($kpi['category']) . '</span>'
                                                    . '</div>'
                                                    . '</div>'
                                                );
                                            }),
                                    ]),
                                ])->columns(2),
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
                            Grid::make(3)
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
                                    ->action(fn (Set $set, Get $get) => $forwardAction($set, $get, '23_scope')),
                            ])),
                        ])
                        ->visible(fn (Get $get) => $get('_builder_step') === '22_series'),

                    // Step 2.3: Scope / Filters
                    Section::make(__('2.3. Scope & Filters'))
                        ->schema([
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
            } else {
                $ch = e(Str::headline($var['independent_channel'] ?? '—'));
                $me = e(Str::headline($var['independent_metric'] ?? '—'));
            }
            $indHtml .= "<tr><td class=\"px-3 py-2 text-sm text-gray-500 dark:text-gray-400\">X{$idx}</td><td class=\"px-3 py-2 text-sm text-gray-950 dark:text-white\">{$ch}</td><td class=\"px-3 py-2 text-sm text-gray-950 dark:text-white\">{$me}</td></tr>";
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
        $html .= '<table class="w-full text-left"><thead><tr class="border-b border-gray-200 dark:border-white/10"><th class="px-3 py-2 text-xs font-medium text-gray-500 dark:text-gray-400">Role</th><th class="px-3 py-2 text-xs font-medium text-gray-500 dark:text-gray-400">Channel</th><th class="px-3 py-2 text-xs font-medium text-gray-500 dark:text-gray-400">Metric</th></tr></thead><tbody>';
        $html .= "<tr class=\"border-b border-gray-100 dark:border-white/5\"><td class=\"px-3 py-2 text-sm text-gray-500 dark:text-gray-400\">Y (Dependent)</td><td class=\"px-3 py-2 text-sm text-gray-950 dark:text-white\">{$depChannel}</td><td class=\"px-3 py-2 text-sm text-gray-950 dark:text-white\">{$depMetric}</td></tr>";
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
