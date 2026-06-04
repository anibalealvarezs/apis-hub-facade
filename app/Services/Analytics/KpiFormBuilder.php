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
        
        $active = [];
        foreach ($tenant->sync_config as $channel => $data) {
            // Very simplified check, real check can use DataSources logic
            $active[$channel] = \Illuminate\Support\Str::headline($channel);
        }
        return $active;
    }

    public static function getTemplateOptions(): array
    {
        $activeChannels = array_keys(self::getActiveChannels());
        $kpis = PredefinedKpiRegistry::getAvailableKpis($activeChannels);
        
        $options = [];
        foreach ($kpis as $key => $kpi) {
            $options[$key] = $kpi['name'];
        }
        return $options;
    }

    public static function getNodeSchema(string $name, string $label): array
    {
        return [
            Fieldset::make($label)
                ->schema([
                    Select::make($name . '_channel')
                        ->label('Channel')
                        ->options(fn () => self::getActiveChannels())
                        ->live(),
                    Select::make($name . '_metric')
                        ->label('Metric')
                        ->options(function (Get $get) use ($name) {
                            $channel = $get($name . '_channel');
                            if (!$channel) return [];
                            // Hardcoded for now, ideally dynamically fetched from release config schemas
                            if (str_contains($channel, 'facebook_marketing')) return ['spend' => 'Spend', 'clicks' => 'Clicks', 'impressions' => 'Impressions'];
                            if (str_contains($channel, 'google_search_console')) return ['clicks' => 'Clicks', 'impressions' => 'Impressions', 'ctr' => 'CTR'];
                            if (str_contains($channel, 'facebook_organic')) return ['reach' => 'Reach', 'likes' => 'Likes'];
                            return ['metric_1' => 'Metric 1', 'metric_2' => 'Metric 2'];
                        }),
                    Select::make($name . '_asset_filter')
                        ->label('Asset Filter (Optional)')
                        ->options(function (Get $get) use ($name) {
                            $channel = $get($name . '_channel');
                            if (!$channel) return [];
                            $tenant = Filament::getTenant();
                            $config = $tenant->sync_config[$channel] ?? [];
                            // Extract assets dynamically based on channel
                            $assets = [];
                            // Quick simplified extraction
                            foreach ($config as $key => $items) {
                                if (is_array($items)) {
                                    foreach ($items as $item) {
                                        if (isset($item['id']) || isset($item['url'])) {
                                            $id = $item['id'] ?? $item['url'];
                                            $nameStr = $item['name'] ?? $item['url'] ?? $id;
                                            $assets[$id] = $nameStr;
                                        }
                                    }
                                }
                            }
                            return $assets;
                        })
                ])->columns(3)
        ];
    }
    
    public static function getSchema(): array
    {
        return [
            Section::make('KPI Configuration')
                ->schema([
                    Select::make('template')
                        ->label('Quick Start Template')
                        ->options(fn () => self::getTemplateOptions())
                        ->live()
                        ->afterStateUpdated(function (\Filament\Forms\Set $set, $state) {
                            if (!$state) return;
                            $kpi = PredefinedKpiRegistry::getPredefinedKpis()[$state] ?? null;
                            if ($kpi) {
                                $set('calculation_type', $kpi['calculation_type']);
                                // We will populate the ast fields here eventually based on template
                            }
                        }),

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
                                ->minItems(1)
                        ]),
                    
                    Fieldset::make('Scope / Filters')
                        ->schema([
                            DatePicker::make('start_date')->label('Start Date')->required(),
                            DatePicker::make('end_date')->label('End Date')->required(),
                            Select::make('granularity')
                                ->label('Granularity')
                                ->options([
                                    'daily' => 'Daily',
                                    'weekly' => 'Weekly',
                                    'monthly' => 'Monthly'
                                ])
                                ->default('daily')
                                ->required(),
                        ])->columns(3),
                ])->columns(1)
        ];
    }
}
