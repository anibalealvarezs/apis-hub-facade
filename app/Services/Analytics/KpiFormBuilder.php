<?php

namespace App\Services\Analytics;

use App\Models\CustomKpi;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Group;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Facades\Filament;
use Illuminate\Support\Str;

class KpiFormBuilder
{
    public static function getActiveChannels(): array
    {
        $tenant = Filament::getTenant();
        if (!$tenant) {
            return [];
        }
        $config = $tenant->sync_config ?? [];
        $active = [];

        foreach ($config as $channelKey => $channelData) {
            if (isset($channelData['is_active']) && $channelData['is_active']) {
                $active[$channelKey] = self::getChannelDisplayName($channelKey);
            }
        }
        return $active;
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
            ]
        ];
    }

    private static function getChannelCategories(?string $globalAssetGroup = null): array
    {
        $channels = \App\Services\Analytics\ChannelCapabilityRegistry::getTags();
        $cats = [];
        
        $validChannels = array_keys($channels);
        if ($globalAssetGroup) {
            $group = \App\Models\AssetGroup::find($globalAssetGroup);
            if ($group) {
                $validChannels = $group->active_items->pluck('channel')->unique()->toArray();
                $validChannels = array_unique(array_merge($validChannels, array_keys(self::getActiveChannels())));
            }
        }
        
        foreach (array_keys($channels) as $channel) {
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
                    if (!in_array($reqTag, $availableTags)) {
                        $isValid = false;
                        break;
                    }
                }
                if (!$isValid) continue;
            }
            
            // Inyectar dinámicamente las categorías de canales
            foreach ($channelTags as $channel => $tags) {
                if (count(array_intersect($requiredTags, $tags)) > 0) {
                    $kpiCats[] = 'ch_' . $channel;
                }
            }
            $kpiCats = array_unique($kpiCats);

            if (!empty($categoryFilter)) {
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
                $extractMetrics = function($node) use (&$extractMetrics, &$metrics) {
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
        if (!$project) {
            return [];
        }

        $syncConfig = $project->sync_config ?? [];
        if (!isset($syncConfig[$channel]['is_active']) || !$syncConfig[$channel]['is_active']) {
            return [];
        }

        $options = [];
        $channelTags = \App\Services\Analytics\ChannelCapabilityRegistry::getTags()[$channel] ?? [];

        foreach ($channelTags as $tag) {
            $options["{$channel}_{$tag}_metric_placeholder"] = self::getChannelDisplayName($tag) . ' (Simulated Metric)';
        }

        $configFields = \App\Services\Analytics\ChannelCapabilityRegistry::getConfigFields()[$channel] ?? [];
        foreach ($configFields as $key => $field) {
            if ($field['type'] === 'metric' || $field['type'] === 'dimension') {
                $options[$key] = $field['label'];
            }
        }

        $metrics = \App\Services\Analytics\PredefinedMetricRegistry::getMetricsForChannel($channel);
        foreach ($metrics as $key => $config) {
            $options[$key] = $config['label'];
        }

        return $options;
    }

    public static function getAllAssetsForChannel(?string $channel): array
    {
        if (empty($channel)) {
            return [];
        }

        $project = Filament::getTenant();
        if (!$project) {
            return [];
        }

        $syncConfig = $project->sync_config ?? [];
        if (empty($syncConfig[$channel]['is_active'])) {
            return [];
        }

        $items = \App\Models\AssetGroupItem::whereHas('group', function($q) use ($project) {
            $q->where('project_id', $project->id);
        })
        ->where('channel', $channel)
        ->get();

        $assets = [];
        foreach ($items as $item) {
            $assets[$item->asset_id] = [
                'name' => $item->asset_name ?? $item->asset_id,
                'enabled' => true,
            ];
        }

        foreach (['facebook', 'google', 'meta', 'klaviyo', 'shopify', 'netsuite', 'amazon', 'bigcommerce', 'pinterest', 'linkedin', 'tiktok', 'x', 'triple_whale'] as $provider) {
            if (strpos($channel, $provider) !== false && !empty($syncConfig[$channel]['accounts'])) {
                foreach ($syncConfig[$channel]['accounts'] as $acc) {
                    if (is_array($acc) && !empty($acc['enabled']) && empty($acc['lost_access'])) {
                        $id = $acc['id'] ?? $acc['url'] ?? '';
                        $name = $acc['name'] ?? $acc['url'] ?? $id;
                        if ($id) {
                            $assets[$id] = [
                                'name' => $name,
                                'enabled' => true,
                            ];
                        }
                    }
                }
            }
        }

        if (strpos($channel, 'google') !== false && !empty($syncConfig[$channel]['properties'])) {
            foreach ($syncConfig[$channel]['properties'] as $prop) {
                if (is_array($prop) && !empty($prop['enabled']) && empty($prop['lost_access'])) {
                    $id = $prop['id'] ?? $prop['url'] ?? '';
                    $name = $prop['name'] ?? $prop['url'] ?? $id;
                    if ($id) {
                        $assets[$id] = [
                            'name' => $name,
                            'enabled' => true,
                        ];
                    }
                }
            }
        }

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
        if (!$tenant) return [];
        return \App\Models\AssetGroup::where('project_id', $tenant->id)->pluck('name', 'id')->toArray();
    }

    public static function getNodeSchema(string $name, string $label): array
    {
        return [
            Section::make($label)
                ->schema([
                    Select::make($name . '_channel')
                        ->label('Channel (keep empty for runtime)')
                        ->options(fn () => self::getActiveChannels())
                        ->live(),
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
                        ->live()
                ])->columns(1)
        ];
    }
    
    public static function getSchema(): array
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
                                    ->disabled(fn (Get $get) => empty($get('_intent')))
                            ])
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
                                    ->disabled(fn (Get $get) => empty($get('_focus_assets')) || ($get('_focus_assets') === 'group' && empty($get('global_asset_group'))))
                            ])
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
                                    if (!$state) return;
                                    $kpi = PredefinedKpiRegistry::getPredefinedKpis()[$state] ?? null;
                                    if (!$kpi) return;

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

                                    $resolveChannel = function($placeholder) use ($activeChannels, $registryTags, $globalGroup) {
                                        preg_match('/__([A-Z_]+)_CHANNEL_\d+__/', $placeholder, $matches);
                                        if (empty($matches[1])) return null;

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

                                    $ast = $kpi['template']['ast'] ?? [];

                                    $isUnivariateAst = ($ast['type'] ?? '') === 'metric';
                                    if (in_array($kpi['calculation_type'], ['calculate_autocorrelation', 'calculate_anomaly']) || $isUnivariateAst) {
                                        if (isset($ast['channel'])) {
                                            $set('dependent_channel', $resolveChannel($ast['channel']));
                                            $set('dependent_metric', $ast['metric'] ?? '');
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
                                        if ($globalGroup) {
                                            $set('dependent_asset_group', $globalGroup);
                                            $set('dependent_asset_filter', null);
                                        }
                                    }

                                    if (isset($ast['right'])) {
                                        $independents = [];

                                        $unpackIndependents = function($node) use (&$unpackIndependents, $resolveChannel, &$independents, $globalGroup) {
                                            if (($node['type'] ?? '') === 'metric') {
                                                $independents[] = [
                                                    'independent_channel' => $resolveChannel($node['channel']),
                                                    'independent_metric' => $node['metric'] ?? '',
                                                    'independent_asset_group' => $globalGroup,
                                                    'independent_asset_filter' => null,
                                                ];
                                            } elseif (($node['type'] ?? '') === 'operator' && $node['operator'] === '+') {
                                                $unpackIndependents($node['left']);
                                                $unpackIndependents($node['right']);
                                            }
                                        };

                                        $unpackIndependents($ast['right']);

                                        if (!empty($independents)) {
                                            $repeaterData = [];
                                            foreach ($independents as $idx => $ind) {
                                                $repeaterData[\Illuminate\Support\Str::uuid()->toString()] = $ind;
                                            }
                                            $set('independent_variables', $repeaterData);
                                        }
                                    }
                                })
                            ])->columnSpan(1)->extraAttributes(['class' => 'relative z-50']),

                            Group::make([
                                \Filament\Forms\Components\Placeholder::make('template_details')
                                    ->hiddenLabel()
                                    ->content(function (Get $get) {
                                        $templateId = $get('template');
                                        if (!$templateId) {
                                            return new \Illuminate\Support\HtmlString('<div class="h-full flex items-center justify-center p-6 text-gray-500 italic bg-white dark:bg-gray-900 ring-1 ring-gray-950/5 dark:ring-white/10 rounded-xl">Select a template to view its details.</div>');
                                        }
                                        
                                        $kpis = \App\Services\Analytics\PredefinedKpiRegistry::getPredefinedKpis();
                                        $kpi = $kpis[$templateId] ?? null;
                                        
                                        if (!$kpi) {
                                            return new \Illuminate\Support\HtmlString('<div class="text-danger-600 dark:text-danger-400 p-4 bg-danger-50 dark:bg-danger-400/10 rounded-xl ring-1 ring-danger-600/10 dark:ring-danger-400/20">Template details not found.</div>');
                                        }
                                        
                                        $reference = app(\App\Filament\App\Pages\KpiReference::class);
                                        $guidance = $reference->getGuidance($templateId);
                                        
                                        $html = '<div class="space-y-4 p-5 bg-white dark:bg-gray-900 ring-1 ring-gray-950/5 dark:ring-white/10 rounded-xl shadow-sm h-full">';
                                        $html .= '<div><h3 class="text-lg font-semibold text-gray-950 dark:text-white">' . e($kpi['name']) . '</h3>';
                                        $html .= '<span class="inline-block mt-1 px-2 py-1 text-xs font-medium text-primary-600 bg-primary-50 dark:text-primary-400 dark:bg-primary-400/10 rounded-full">' . e($guidance['type_label']) . '</span></div>';
                                        
                                        if (!empty($guidance['explanation'])) {
                                            $html .= '<div><h4 class="text-sm font-semibold text-gray-950 dark:text-white mb-1">What it does</h4><p class="text-sm text-gray-500 dark:text-gray-400 leading-relaxed">' . nl2br(e($guidance['explanation'])) . '</p></div>';
                                        }
                                        
                                        if (!empty($guidance['use_case'])) {
                                            $html .= '<div><h4 class="text-sm font-semibold text-gray-950 dark:text-white mb-1">Golden use case</h4><p class="text-sm text-gray-500 dark:text-gray-400 leading-relaxed">' . nl2br(e($guidance['use_case'])) . '</p></div>';
                                        }
                                        
                                        if (!empty($guidance['interpretation'])) {
                                            $html .= '<div><h4 class="text-sm font-semibold text-gray-950 dark:text-white mb-1">Reading the result</h4><p class="text-sm text-gray-500 dark:text-gray-400 leading-relaxed">' . nl2br(e($guidance['interpretation'])) . '</p></div>';
                                        }
                                        
                                        $html .= '</div>';
                                        
                                        return new \Illuminate\Support\HtmlString($html);
                                    })
                            ])->columnSpan(1)
                        ]),
                        Actions::make([
                                Actions\Action::make('back_template')
                                    ->label('Back')
                                    ->color('gray')
                                    ->action(fn (Set $set, Get $get) => $backAction($set, $get)),
                                Actions\Action::make('next_template')
                                    ->label('Next')
                                    ->action(fn (Set $set, Get $get) => $forwardAction($set, $get, '23_scope'))
                                    ->disabled(fn (Get $get) => empty($get('template')))
                            ])
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
                                    ->disabled(fn (Get $get) => empty($get('calculation_type')))
                            ])
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
                                        ->visible(fn (Get $get) => in_array($get('calculation_type'), ['calculate_regression', 'calculate_elasticity', 'calculate_granger', 'calculate_macd']))
                                ])
                                ->columns([
                                    'sm' => 1,
                                    'md' => 3,
                                ]),
                            Actions::make([
                                Actions\Action::make('back_series')
                                    ->label('Back')
                                    ->color('gray')
                                    ->action(fn (Set $set, Get $get) => $backAction($set, $get)),
                                Actions\Action::make('next_series')
                                    ->label('Next')
                                    ->action(fn (Set $set, Get $get) => $forwardAction($set, $get, '23_scope'))
                            ])
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
                                    'monthly' => 'Monthly'
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
                            ])
                        ])
                        ->visible(fn (Get $get) => $get('_builder_step') === '23_scope'),
                ])
        ];
    }
}
