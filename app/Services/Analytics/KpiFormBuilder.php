<?php

namespace App\Services\Analytics;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\DatePicker;
use Filament\Facades\Filament;
use Filament\Forms\Get;

class KpiFormBuilder
{
    public static function getActiveChannels(): array
    {
        $tenant = Filament::getTenant();
        if (!$tenant || empty($tenant->sync_config)) {
            return [];
        }
        
        $validChannels = array_keys(ChannelCapabilityRegistry::getTags());
        $active = [];
        foreach ($tenant->sync_config as $channel => $data) {
            if (in_array($channel, $validChannels)) {
                $active[$channel] = \Illuminate\Support\Str::headline($channel);
            }
        }
        return $active;
    }

    public static function getCategoryOptions(): array
    {
        return [
            'performance' => __('Performance'),
            'cost' => __('Cost'),
            'results' => __('Results'),
            'clicks' => __('Clicks'),
            'impressions' => __('Impressions'),
            'seasonality' => __('Seasonality'),
            'trends' => __('Trends'),
            'scalability' => __('Scalability'),
            'cross-channel' => __('Cross-Channel'),
            'alerts' => __('Alerts'),
            'seo' => __('SEO'),
            'organic' => __('Organic'),
            'agency' => __('Agency Performance'),
            'scope_global' => __('Global'),
            'scope_channel' => __('Channel'),
            'scope_asset' => __('Asset'),
        ];
    }

    public static function getTemplateOptions(array $categoryFilter = []): array
    {
        $activeChannels = array_keys(self::getActiveChannels());
        $kpis = PredefinedKpiRegistry::getAvailableKpis($activeChannels);

        $options = [];
        foreach ($kpis as $key => $kpi) {
            if (!empty($categoryFilter)) {
                $kpiCats = $kpi['categories'] ?? [];
                $intersection = array_intersect($categoryFilter, $kpiCats);
                if (count($intersection) !== count($categoryFilter)) {
                    continue;
                }
            }
            $name = htmlspecialchars($kpi['name'], ENT_QUOTES, 'UTF-8');
            $desc = htmlspecialchars($kpi['description'], ENT_QUOTES, 'UTF-8');
            $options[] = [
                'key' => $key,
                'name' => $name,
                'html' => "<span class=\"font-semibold\">{$name}</span> <span class=\"text-gray-400\">— {$desc}</span>",
            ];
        }

        usort($options, fn ($a, $b) => strcasecmp($a['name'], $b['name']));

        $sorted = [];
        foreach ($options as $item) {
            $sorted[$item['key']] = $item['html'];
        }
        return $sorted;
    }

    public static function getMetricOptionsForChannel(?string $channel): array
    {
        if (!$channel) return [];
        if (str_contains($channel, 'facebook_marketing')) return [
            'spend' => 'Spend', 'clicks' => 'Clicks', 'impressions' => 'Impressions',
            'results' => 'Results', 'result_rate' => 'Result Rate', 'cpc' => 'CPC',
            'purchase_roas' => 'ROAS (Purchase)',
        ];
        if (str_contains($channel, 'google_search_console')) return [
            'clicks' => 'Clicks', 'impressions' => 'Impressions', 'ctr' => 'CTR', 'position' => 'Position',
        ];
        if (str_contains($channel, 'facebook_organic')) return [
            'reach' => 'Reach', 'impressions' => 'Impressions', 'engaged_users' => 'Engaged Users', 'likes' => 'Likes',
        ];
        return ['metric_1' => 'Metric 1', 'metric_2' => 'Metric 2'];
    }

    public static function getAssetOptionsForChannel(?string $channel): array
    {
        if (!$channel) return [];
        $tenant = Filament::getTenant();
        if (!$tenant) return [];
        $config = $tenant->sync_config[$channel] ?? [];
        $assets = [];

        $assetKeys = ['sites', 'ad_accounts', 'pages', 'locations', 'profiles', 'accounts', 'shops'];

        foreach ($assetKeys as $assetKey) {
            if (!empty($config[$assetKey]) && is_array($config[$assetKey])) {
                foreach ($config[$assetKey] as $item) {
                    if (is_array($item) && !empty($item['enabled']) && empty($item['lost_access']) && (isset($item['id']) || isset($item['url']))) {
                        $id = $item['id'] ?? $item['url'];
                        $nameStr = $item['name'] ?? $item['url'] ?? $id;
                        $assets[$id] = $nameStr;
                    }
                }
            }
        }

        if (!empty($config['assets']) && is_array($config['assets'])) {
            foreach ($assetKeys as $assetKey) {
                if (!empty($config['assets'][$assetKey]) && is_array($config['assets'][$assetKey])) {
                    foreach ($config['assets'][$assetKey] as $item) {
                        if (is_array($item) && !empty($item['enabled']) && empty($item['lost_access']) && (isset($item['id']) || isset($item['url']))) {
                            $id = $item['id'] ?? $item['url'];
                            $nameStr = $item['name'] ?? $item['url'] ?? $id;
                            $assets[$id] = $nameStr;
                        }
                    }
                }
            }
        }

        return $assets;
    }

    public static function getNodeSchema(string $name, string $label): array
    {
        return [
            Fieldset::make($label)
                ->schema([
                    Select::make($name . '_channel')
                        ->label('Channel (keep empty for runtime)')
                        ->options(fn () => self::getActiveChannels())
                        ->live(),
                    Select::make($name . '_metric')
                        ->label('Metric (keep empty for runtime)')
                        ->options(fn (Get $get) => static::getMetricOptionsForChannel($get($name . '_channel'))),
                    Select::make($name . '_asset_filter')
                        ->label('Asset Filter (keep empty for runtime)')
                        ->options(fn (Get $get) => static::getAssetOptionsForChannel($get($name . '_channel')))
                ])->columns(3)
        ];
    }
    
    public static function getSchema(): array
    {
        return [
            Section::make('KPI Configuration')
                ->schema([
                    Section::make('Quick Start')
                        ->description('Browse and pick a predefined template to auto-fill the configuration below.')
                        ->collapsible()
                        ->compact()
                        ->extraAttributes(['class' => 'bg-gray-50 dark:bg-white/5 rounded-lg'])
                        ->schema([
                            Select::make('category_filter')
                                ->label('Filter by category')
                                ->multiple()
                                ->options(fn () => self::getCategoryOptions())
                                ->live(),

                            Select::make('template')
                                ->label('Quick Start Template')
                                ->allowHtml()
                                ->searchable()
                                ->options(fn (Get $get) => self::getTemplateOptions($get('category_filter') ?? []))
                                ->live()
                                ->afterStateUpdated(function (\Filament\Forms\Set $set, $state) {
                                    if (!$state) return;
                                    $kpi = PredefinedKpiRegistry::getPredefinedKpis()[$state] ?? null;
                                    if (!$kpi) return;

                                    $set('calculation_type', $kpi['calculation_type']);

                                    $activeChannels = array_keys(self::getActiveChannels());
                                    $registryTags = ChannelCapabilityRegistry::getTags();

                                    $resolveChannel = function($placeholder) use ($activeChannels, $registryTags) {
                                        preg_match('/__([A-Z_]+)_CHANNEL_\d+__/', $placeholder, $matches);
                                        if (empty($matches[1])) return null;

                                        $requiredTag = strtolower($matches[1]);

                                        foreach ($activeChannels as $channel) {
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
                                        }
                                        $set('independent_variables', []);
                                        return;
                                    }

                                    if (isset($ast['left']['channel'])) {
                                        $set('dependent_channel', $resolveChannel($ast['left']['channel']));
                                        $set('dependent_metric', $ast['left']['metric'] ?? '');
                                    }

                                    if (isset($ast['right'])) {
                                        $independents = [];

                                        $unpackIndependents = function($node) use (&$unpackIndependents, $resolveChannel, &$independents) {
                                            if (($node['type'] ?? '') === 'metric') {
                                                $independents[] = [
                                                    'independent_channel' => $resolveChannel($node['channel']),
                                                    'independent_metric' => $node['metric'] ?? '',
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
                                }),

                    ]),

                    Select::make('calculation_type')
                        ->label('Calculation Type')
                        ->options([
                            'calculate_regression' => 'Multiple Linear Regression',
                            'calculate_elasticity' => 'Elasticity',
                            'calculate_autocorrelation' => 'Autocorrelation',
                            'calculate_granger' => 'Granger Causality',
                            'calculate_macd' => 'MACD Momentum',
                            'calculate_anomaly' => 'Anomaly Detection',
                        ])
                        ->required()
                        ->live(),

                    // Dependent Variable (Y) - Used in everything
                    ...self::getNodeSchema('dependent', 'Dependent Variable (Y - Explained)'),

                    // Independent Variables (X) - Used in Bivariate
                    Fieldset::make('Independent Variables (X - Explanatory)')
                        ->visible(fn (Get $get) => in_array($get('calculation_type'), ['calculate_regression', 'calculate_elasticity', 'calculate_granger', 'calculate_macd']))
                        ->schema([
                            Repeater::make('independent_variables')
                                ->label('')
                                ->schema(self::getNodeSchema('independent', 'Variable'))
                                ->defaultItems(1)
                                ->minItems(0)
                        ]),
                    
                    Fieldset::make('Scope / Filters')
                        ->schema([
                            DatePicker::make('start_date')->label('Start Date'),
                            DatePicker::make('end_date')->label('End Date'),
                            Select::make('granularity')
                                ->label('Granularity')
                                ->options([
                                    'daily' => 'Daily',
                                    'weekly' => 'Weekly',
                                    'monthly' => 'Monthly'
                                ]),
                            Select::make('zero_handling')
                                ->label('Zero Handling')
                                ->options([
                                    'remove' => 'Remove Zeroes',
                                    'trim' => 'Trim Leading/Trailing Zeroes',
                                    'keep' => 'Keep Zeroes',
                                ])
                                ->default('remove')
                                ->helperText('How to treat zero values in the time series before analysis.'),
                        ])->columns(3),
                ])->columns(1)
        ];
    }
}
