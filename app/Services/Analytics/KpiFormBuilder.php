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
            if (in_array($channel, $providers)) continue;
            
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

    private static function getChannelDisplayName(string $name): string
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
        $channels = \App\Services\Analytics\ChannelCapabilityRegistry::getTags();
        $cats = [];
        $providers = array_keys(self::getActiveChannels());

        $validChannels = array_keys($channels);
        if ($globalAssetGroup) {
            $group = \App\Models\AssetGroup::find($globalAssetGroup);
            if ($group) {
                $validChannels = $group->active_items->pluck('channel')->unique()->toArray();
            }
        } else {
            // No asset group — show only sub-channels that have enabled assets
            $validSubchannels = [];
            foreach ($validChannels as $channel) {
                if (in_array($channel, $providers)) {
                    continue;
                }
                if (self::channelHasEnabledAssets($channel)) {
                    $validSubchannels[] = $channel;
                }
            }
            $validChannels = $validSubchannels;
        }

        foreach (array_keys($channels) as $channel) {
            if (in_array($channel, $providers)) continue;
            
            if (in_array($channel, $validChannels)) {
                $cats['ch_' . $channel] = self::getChannelDisplayName($channel);
            }
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

        return $options;
    }

    public static function getMetricOptionsForChannel(?string $channel): array
    {
        if (empty($channel)) {
            return [];
        }

        $project = Filament::getTenant();
        if (! $project) {
            return [];
        }

        $activeChannels = array_keys(self::getActiveChannels());
        if (!in_array($channel, $activeChannels)) {
            return [];
        }

        $options = [];
        $channelTags = \App\Services\Analytics\ChannelCapabilityRegistry::getTags()[$channel] ?? [];

        foreach ($channelTags as $tag) {
            $options["{$channel}_{$tag}_metric_placeholder"] = self::getChannelDisplayName($tag) . ' (Simulated Metric)';
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
                && (array_key_exists('id', $data) || array_key_exists('url', $data) || array_key_exists('platformId', $data))
            ) {
                if (!empty($data['enabled']) && empty($data['lost_access'])) {
                    $id = $data['id'] ?? $data['url'] ?? $data['platformId'] ?? '';
                    $name = $data['name'] ?? $data['url'] ?? $data['platformId'] ?? $id;
                    if ($id) {
                        $assets[$id] = [
                            'name' => $name,
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

        return \App\Models\AssetGroup::where('project_id', $tenant->id)->pluck('name', 'id')->toArray();
    }

    public static function getNodeSchema(string $name, string $label): array
    {
        return [
            Section::make($label)
                ->schema([
                    Hidden::make('_required_tag'),
                    Select::make($name . '_channel')
                        ->label('Channel (keep empty for runtime)')
                        ->options(function (Get $get) {
                            $allChannels = self::getActiveChannels();
                            $requiredTag = $get('_required_tag');
                            if (empty($requiredTag)) return $allChannels;
                            
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
                        ->hint(fn (Get $get) => empty($get($name . '_channel')) ? 'ACTION REQUIRED: Please select a channel' : 'Channel Assigned')
                        ->hintIcon(fn (Get $get) => empty($get($name . '_channel')) ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle')
                        ->hintColor(fn (Get $get) => empty($get($name . '_channel')) ? 'danger' : 'success'),
                    Select::make($name . '_metric')
                        ->label('Metric')
                        ->options(fn (Get $get) => static::getMetricOptionsForChannel($get($name . '_channel')))
                        ->required(fn () => $name === 'dependent'),
                    Select::make($name . '_asset_group')
                        ->label('Asset Group (keep empty for runtime)')
                        ->options(fn () => static::getAssetGroupOptions())
                        ->disabled(fn (Get $get) => filled($get($name . '_asset_filter')))
                        ->live(),
                    Select::make($name . '_asset_filter')
                        ->label('Asset Filter (keep empty for runtime)')
                        ->options(fn (Get $get) => static::getAssetOptionsForChannel($get($name . '_channel')))
                        ->disabled(fn (Get $get) => filled($get($name . '_asset_group')))
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

            Section::make('KPI Configuration')
                ->schema([
                    // Step 1: Intent
                    Section::make('1. Choose Build Method')
                        ->schema([
                            Radio::make('_intent')
                                ->label('Do you want to build a KPI from scratch or use a predefined template?')
                                ->options([
                                    'template' => 'Use a predefined template',
                                    'scratch' => 'Build from scratch',
                                ])
                                ->live(),
                            Actions::make([
                                Actions\Action::make('next_intent')
                                    ->label('Next')
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
                    Section::make('1.A.1. Focus on specific assets?')
                        ->schema([
                            Radio::make('_focus_assets')
                                ->label('Do you want to focus in a specific group of assets?')
                                ->options([
                                    'group' => 'Select an asset group',
                                    'all' => 'All assets',
                                ])
                                ->live()
                                ->afterStateUpdated(function (Set $set, $state) {
                                    if ($state === 'all') {
                                        $set('global_asset_group', null);
                                    }
                                    $set('category_filter', []);
                                    $set('template', null);
                                }),
                            Select::make('global_asset_group')
                                ->label('Global Asset Group (optional)')
                                ->options(fn () => static::getAssetGroupOptions())
                                ->visible(fn (Get $get) => $get('_focus_assets') === 'group')
                                ->live()
                                ->afterStateUpdated(function (Set $set) {
                                    $set('category_filter', []);
                                    $set('template', null);
                                }),
                            Actions::make([
                                Actions\Action::make('back_focus')
                                    ->label('Back')
                                    ->color('gray')
                                    ->action(fn (Set $set, Get $get) => $backAction($set, $get)),
                                Actions\Action::make('next_focus')
                                    ->label('Assign & Next')
                                    ->action(function (Set $set, Get $get) use ($forwardAction) {
                                        $forwardAction($set, $get, '1a2_template');
                                    })
                                    ->disabled(fn (Get $get) => empty($get('_focus_assets')) || ($get('_focus_assets') === 'group' && empty($get('global_asset_group')))),
                            ]),
                        ])
                        ->visible(fn (Get $get) => $get('_builder_step') === '1a1_asset_group'),

                    // Step 1.A.2: Template Selection
                    Section::make('1.A.2. Select Template')
                        ->schema([
                            Grid::make(2)
                                ->schema([
                                    Group::make([
                                        Select::make('category_filter')
                                            ->label('Filter by category')
                                            ->multiple()
                                            ->options(fn (Get $get) => self::getCategoryOptions($get('global_asset_group')))
                                            ->live(),

                                        Select::make('template')
                                            ->label('Quick Start Template')
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

                                                $activeChannels = array_keys(self::getActiveChannels());
                                                $registryTags = ChannelCapabilityRegistry::getTags();
                                                $globalGroup = $get('global_asset_group');

                                                $resolveChannel = function ($placeholder) use ($activeChannels, $registryTags, $globalGroup) {
                                                    preg_match('/__([A-Z_]+)_CHANNEL_\d+__/', $placeholder, $matches);
                                                    if (empty($matches[1])) {
                                                        return null;
                                                    }

                                                    $requiredTag = strtolower($matches[1]);

                                                    $allowedChannels = $activeChannels;
                                                    if ($globalGroup) {
                                                        $group = \App\Models\AssetGroup::find($globalGroup);
                                                        if ($group) {
                                                            $allowedChannels = array_intersect($allowedChannels, $group->active_items->pluck('channel')->unique()->toArray());
                                                        }
                                                    }

                                                    foreach ($allowedChannels as $channel) {
                                                        $tags = $registryTags[$channel] ?? [];
                                                        if (in_array($requiredTag, $tags)) {
                                                            return $channel;
                                                        }
                                                    }

                                                    return null;
                                                };

                                                    $extractTag = function ($placeholder) {
                                                        if (!$placeholder) return null;
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
                            ])->columnSpan(1)->extraAttributes(['class' => 'relative z-50']),

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
                        Actions::make([
                                Actions\Action::make('back_template')
                                                ->label('Back')
                                                ->color('gray')
                                                ->action(fn (Set $set, Get $get) => $backAction($set, $get)),
                                Actions\Action::make('next_template')
                                                ->label('Next')
                                                ->action(fn (Set $set, Get $get) => $forwardAction($set, $get, '22_series'))
                                                ->disabled(fn (Get $get) => empty($get('template'))),
                            ]),
                        ])
                        ->visible(fn (Get $get) => $get('_builder_step') === '1a2_template'),

                    // Step 2.1: Calculation Type
                    Section::make('2.1. Choose your calculation type')
                        ->schema([
                            Select::make('calculation_type')
                                ->label('Calculation Type')
                                ->options([
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
                                ])
                                ->required()
                                ->live(),
                            Actions::make([
                                Actions\Action::make('back_calc')
                                                ->label('Back')
                                                ->color('gray')
                                                ->action(fn (Set $set, Get $get) => $backAction($set, $get)),
                                Actions\Action::make('next_calc')
                                                ->label('Next')
                                                ->action(fn (Set $set, Get $get) => $forwardAction($set, $get, '22_series'))
                                                ->disabled(fn (Get $get) => empty($get('calculation_type'))),
                            ]),
                        ])
                        ->visible(fn (Get $get) => $get('_builder_step') === '21_calculation'),

                    // Step 2.2: Configure Series (Horizontal layout)
                    Section::make('2.2. Configure Series')
                        ->schema([
                            Grid::make(3) // Ensure max 3 cols logic if we need it
                                ->schema([
                                                Group::make(self::getNodeSchema('dependent', 'Dependent Variable (Y - Explained)'))
                                                    ->columnSpan(1),

                                                Repeater::make('independent_variables')
                                                    ->label('Independent Variables (X - Explanatory)')
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
                                !$isEdit ? Actions\Action::make('back_series')
                                                ->label('Back')
                                                ->color('gray')
                                                ->action(fn (Set $set, Get $get) => $backAction($set, $get)) : null,
                                Actions\Action::make('next_series')
                                                ->label('Next')
                                                ->action(fn (Set $set, Get $get) => $forwardAction($set, $get, '23_scope')),
                            ])),
                        ])
                        ->visible(fn (Get $get) => $get('_builder_step') === '22_series'),

                    // Step 2.3: Scope / Filters
                    Section::make('2.3. Scope & Filters')
                        ->schema([
                            DatePicker::make('start_date')->label('Start Date'),
                            DatePicker::make('end_date')->label('End Date'),
                            Select::make('granularity')
                                ->label('Granularity')
                                ->options([
                                                'daily' => 'Daily',
                                                'weekly' => 'Weekly',
                                                'monthly' => 'Monthly',
                                ])
                                ->default('daily'),
                            Select::make('zero_handling')
                                ->label('Zero Handling')
                                ->options([
                                                'remove' => 'Remove Zeroes',
                                                'trim' => 'Trim Leading/Trailing Zeroes',
                                                'keep' => 'Keep Zeroes',
                                ])
                                ->default('trim')
                                ->helperText('How to treat zero values in the time series before analysis.'),
                            Actions::make([
                                Actions\Action::make('back_scope')
                                                ->label('Back')
                                                ->color('gray')
                                                ->action(fn (Set $set, Get $get) => $backAction($set, $get)),
                                Actions\Action::make('next_scope')
                                                ->label('Next')
                                                ->action(fn (Set $set, Get $get) => $forwardAction($set, $get, '24_info')),
                            ]),
                        ])
                        ->visible(fn (Get $get) => $get('_builder_step') === '23_scope'),

                    // Step 2.4: General Information
                    Section::make('2.4. General Information')
                        ->schema([
                            TextInput::make('name')
                                ->label('KPI Name')
                                ->required()
                                ->maxLength(255)
                                ->live(debounce: 500),
                            Textarea::make('description')
                                ->label('Description')
                                ->maxLength(65535)
                                ->columnSpanFull(),
                            Toggle::make('is_active')
                                ->label(__('Active'))
                                ->default(true),
                            Actions::make([
                                Actions\Action::make('back_info')
                                                ->label('Back')
                                                ->color('gray')
                                                ->action(fn (Set $set, Get $get) => $backAction($set, $get)),
                                Actions\Action::make('next_info')
                                                ->label('Next')
                                                ->action(fn (Set $set, Get $get) => $forwardAction($set, $get, '25_summary'))
                                                ->disabled(fn (Get $get) => empty($get('name'))),
                            ])->columnSpanFull(),
                        ])->columns(2)
                        ->visible(fn (Get $get) => $get('_builder_step') === '24_info'),

                    // Step 2.5: Summary & Create
                    Section::make('2.5. Review & Create')
                        ->schema([
                            Placeholder::make('kpi_summary')
                                ->hiddenLabel()
                                ->content(function (Get $get) {
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

                                    $name = e($get('name') ?: '—');
                                    $desc = e($get('description') ?: '—');
                                    $active = $get('is_active') ? '✅ Active' : '❌ Inactive';
                                    $calc = e($calcLabels[$get('calculation_type')] ?? ($get('calculation_type') ?: '—'));
                                    $granularity = e(ucfirst($get('granularity') ?: '—'));
                                    $start = e($get('start_date') ?: 'Not set');
                                    $end = e($get('end_date') ?: 'Not set');
                                    $zeroLabels = ['remove' => 'Remove Zeroes', 'trim' => 'Trim Leading/Trailing', 'keep' => 'Keep Zeroes'];
                                    $zero = e($zeroLabels[$get('zero_handling')] ?? ($get('zero_handling') ?: '—'));

                                    // Template info
                                    $templateKey = $get('template');
                                    $templateName = '—';
                                    if ($templateKey) {
                                        $kpis = PredefinedKpiRegistry::getPredefinedKpis();
                                        $templateName = e($kpis[$templateKey]['name'] ?? $templateKey);
                                    }

                                    // Dependent variable
                                    $depChannel = e(Str::headline($get('dependent_channel') ?: '—'));
                                    $depMetric = e(Str::headline($get('dependent_metric') ?: '—'));

                                    // Independent variables
                                    $independents = $get('independent_variables') ?? [];
                                    $indHtml = '';
                                    $idx = 1;
                                    foreach ($independents as $var) {
                                        $ch = e(Str::headline($var['independent_channel'] ?? '—'));
                                        $me = e(Str::headline($var['independent_metric'] ?? '—'));
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
                                }),
                            Actions::make(array_filter([
                                Actions\Action::make('back_summary')
                                                ->label('Back')
                                                ->color('gray')
                                                ->action(fn (Set $set, Get $get) => $backAction($set, $get)),
                                Actions\Action::make('create_kpi')
                                                ->label($isEdit ? 'Save changes' : 'Create')
                                                ->color('primary')
                                                ->submit($isEdit ? 'save' : 'create'),
                                !$isEdit ? Actions\Action::make('create_another_kpi')
                                                ->label('Create & create another')
                                                ->color('gray')
                                                ->submit('create')
                                                ->extraAttributes(['name' => 'createAnother', 'value' => true]) : null,
                            ])),
                        ])
                        ->visible(fn (Get $get) => $get('_builder_step') === '25_summary'),
                ]),
        ];
    }
}
