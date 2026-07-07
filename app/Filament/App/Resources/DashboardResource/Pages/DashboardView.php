<?php

namespace App\Filament\App\Resources\DashboardResource\Pages;

use App\Filament\App\Resources\DashboardResource;
use App\Models\Dashboard;
use Filament\Actions;
use Filament\Resources\Pages\Page;

class DashboardView extends Page
{
    protected static string $resource = DashboardResource::class;

    protected static string $view = 'filament.app.pages.dashboard-view';

    public Dashboard $dashboard;

    public array $resolvedControls = [];

    public array $widgets = [];

    public function mount(Dashboard $record): void
    {
        $this->dashboard = $record;

        $this->widgets = $this->dashboard->widgets()
            ->orderBy('grid_y')
            ->orderBy('grid_x')
            ->get()
            ->toArray();

        $service = app(\App\Services\WidgetDataService::class);
        foreach ($this->widgets as &$widgetArray) {
            $widgetModel = (new \App\Models\DashboardWidget())->forceFill($widgetArray);
            $resolved = $service->resolveControls($this->dashboard, $widgetModel);
            $widgetArray['resolved_controls'] = $resolved;
            $widgetArray['series_assets_options'] = [];

            $uiState = [];
            $kpiAssetMode = 'multiple';
            if ($widgetModel->source_type === 'kpi' && ! empty($widgetModel->source_config['custom_kpi_id'])) {
                $kpi = \App\Models\CustomKpi::find($widgetModel->source_config['custom_kpi_id']);
                if ($kpi) {
                    $uiState = $kpi->filters['_ui_state'] ?? [];
                    $templateKey = $uiState['template_key'] ?? null;
                    if ($templateKey) {
                        $predefined = \App\Services\Analytics\PredefinedKpiRegistry::getPredefinedKpis();
                        if (isset($predefined[$templateKey]['asset_selection_mode'])) {
                            $kpiAssetMode = $predefined[$templateKey]['asset_selection_mode'];
                        } elseif (isset($predefined[$templateKey]['scope']) && $predefined[$templateKey]['scope'] === 'asset') {
                            $kpiAssetMode = 'single';
                        }
                    }
                }
            }

            $user = auth()->user();
            $isAdmin = $user && ($user->role === 'admin' || $user->role === 'owner');

            $getAssetsForChannel = function ($channel) use ($isAdmin, $user, $service) {
                if (empty($channel)) {
                    return [];
                }
                $allAssets = \App\Services\Analytics\KpiFormBuilder::getAssetOptionsForChannel($channel);
                if ($isAdmin) {
                    return $allAssets;
                }
                $allowed = $service->filterAllowedAssets(\Filament\Facades\Filament::getTenant(), $user->id, $channel, array_keys($allAssets));
                $filtered = [];
                foreach ($allowed as $id) {
                    if (isset($allAssets[$id])) {
                        $filtered[$id] = $allAssets[$id];
                    }
                }

                return $filtered;
            };

            // Always provide asset filter options when a channel with assets is available
            $provideAssetFilters = function (string $channel, string $key, ?string $label = null, ?array $allowedIds = null) use (&$widgetArray, $getAssetsForChannel, $kpiAssetMode) {
                $assets = $getAssetsForChannel($channel);
                if (! empty($allowedIds)) {
                    $filtered = [];
                    foreach ($allowedIds as $id) {
                        if (isset($assets[$id])) {
                            $filtered[$id] = $assets[$id];
                        }
                    }
                    $assets = $filtered;
                }
                if (empty($assets)) {
                    $assets = []; // ensure it's an array
                }
                $widgetArray['series_assets_options'][$key] = [
                    'label' => $label ?? \Illuminate\Support\Str::headline($channel),
                    'options' => (object) $assets,
                    'mode' => $kpiAssetMode,
                ];
            };

            if (! empty($uiState['dependent_channel'])) {
                $depAssetIds = null;
                if (! empty($uiState['dependent_asset_filter'])) {
                    $depAssetIds = is_array($uiState['dependent_asset_filter'])
                        ? $uiState['dependent_asset_filter']
                        : [$uiState['dependent_asset_filter']];
                }
                if (!empty($resolved['series_assets']['dependent'])) {
                    if ($depAssetIds === null) {
                        $depAssetIds = $resolved['series_assets']['dependent'];
                    } else {
                        $depAssetIds = array_intersect($depAssetIds, $resolved['series_assets']['dependent']);
                    }
                }
                $provideAssetFilters($uiState['dependent_channel'], 'dependent', 'Dep (' . \Illuminate\Support\Str::headline($uiState['dependent_channel']) . ')', $depAssetIds);
            }

            if (isset($uiState['independent_variables']) && is_array($uiState['independent_variables'])) {
                foreach ($uiState['independent_variables'] as $key => $var) {
                    if (! empty($var['independent_channel'])) {
                        $indAssetIds = null;
                        if (! empty($var['independent_asset_filter'])) {
                            $indAssetIds = is_array($var['independent_asset_filter'])
                                ? $var['independent_asset_filter']
                                : [$var['independent_asset_filter']];
                        }
                        $idxKey = 'independent_' . $key;
                        if (!empty($resolved['series_assets'][$idxKey])) {
                            if ($indAssetIds === null) {
                                $indAssetIds = $resolved['series_assets'][$idxKey];
                            } else {
                                $indAssetIds = array_intersect($indAssetIds, $resolved['series_assets'][$idxKey]);
                            }
                        }
                        $provideAssetFilters($var['independent_channel'], $idxKey, 'Ind ' . $key . ' (' . \Illuminate\Support\Str::headline($var['independent_channel']) . ')', $indAssetIds);
                    }
                }
            }

            // Fallback for non-KPI widgets with a channel
            if (empty($widgetArray['series_assets_options'])) {
                if (!empty($resolved['series_channels'])) {
                    foreach ($resolved['series_channels'] as $idx => $chan) {
                        $rawAssetIds = null;
                        if (!empty($resolved['raw_series'][$idx]['assets'])) {
                            $rawAssetIds = $resolved['raw_series'][$idx]['assets'];
                        } elseif (!empty($resolved['series_assets'][$idx])) {
                            $rawAssetIds = $resolved['series_assets'][$idx];
                        }
                        $provideAssetFilters($chan, strval($idx), null, $rawAssetIds);
                    }
                } elseif (!empty($resolved['channel'])) {
                    $rawAssetIds = null;
                    if (!empty($resolved['assets'])) {
                        $rawAssetIds = $resolved['assets'];
                    } elseif (!empty($resolved['series_assets']['0'])) {
                        $rawAssetIds = $resolved['series_assets']['0'];
                    }
                    $provideAssetFilters($resolved['channel'], '0', null, $rawAssetIds);
                }
            }



            // Expose per-variable metric options for on-the-go selection (regression KPIs)
            $variables = [];
            $varIndex = 0;

            if ($widgetArray['source_type'] === 'kpi') {
                $depChannel = $uiState['dependent_channel'] ?? $resolved['channel'] ?? '';
                $depMetrics = ! empty($depChannel)
                    ? \App\Services\Analytics\KpiFormBuilder::getMetricOptionsForChannel($depChannel)
                    : [];
                $variables['dependent'] = [
                    'index' => $varIndex++,
                    'channel' => $depChannel,
                    'channel_name' => !empty($depChannel) ? \App\Services\Analytics\KpiFormBuilder::getChannelDisplayName($depChannel) : '',
                    'metrics' => $depMetrics,
                    'selected_metric' => $uiState['dependent_metric'] ?? '',
                ];

                if (isset($uiState['independent_variables']) && is_array($uiState['independent_variables'])) {
                    foreach ($uiState['independent_variables'] as $key => $var) {
                        $indChannel = $var['independent_channel'] ?? '';
                        $indMetrics = ! empty($indChannel)
                            ? \App\Services\Analytics\KpiFormBuilder::getMetricOptionsForChannel($indChannel)
                            : [];
                        $variables['independent_' . $key] = [
                            'index' => $varIndex++,
                            'channel' => $indChannel,
                            'channel_name' => !empty($indChannel) ? \App\Services\Analytics\KpiFormBuilder::getChannelDisplayName($indChannel) : '',
                            'metrics' => $indMetrics,
                            'selected_metric' => $var['independent_metric'] ?? '',
                        ];
                    }
                }
            } else {
                // Raw metrics variables mapping
                if (!empty($resolved['series_channels'])) {
                    foreach ($resolved['series_channels'] as $idx => $chan) {
                        $metrics = !empty($chan) ? \App\Services\Analytics\KpiFormBuilder::getMetricOptionsForChannel($chan) : [];
                        $variables[strval($idx)] = [
                            'index' => $varIndex++,
                            'channel' => $chan,
                            'channel_name' => !empty($chan) ? \App\Services\Analytics\KpiFormBuilder::getChannelDisplayName($chan) : '',
                            'metrics' => $metrics,
                        ];
                    }
                } elseif (!empty($resolved['channel'])) {
                    $metrics = !empty($resolved['channel']) ? \App\Services\Analytics\KpiFormBuilder::getMetricOptionsForChannel($resolved['channel']) : [];
                    $variables['0'] = [
                        'index' => $varIndex++,
                        'channel' => $resolved['channel'],
                        'channel_name' => \App\Services\Analytics\KpiFormBuilder::getChannelDisplayName($resolved['channel']),
                        'metrics' => $metrics,
                    ];
                }
            }

            $widgetArray['variables'] = (object) $variables;
            // Keep flat metric_options for backward compatibility
            $widgetArray['metric_options'] = isset($depMetrics) ? (object) $depMetrics : new \stdClass();
            $widgetArray['series_assets_options'] = (object) $widgetArray['series_assets_options'];
        }

    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('edit')
                ->label(__('Edit Dashboard'))
                ->icon('heroicon-o-pencil-square')
                ->url(DashboardResource::getUrl('builder', ['record' => $this->dashboard]))
                ->visible(fn () => auth()->user()->can('edit_preferences')),
            Actions\Action::make('back')
                ->label(__('Back to Dashboards'))
                ->icon('heroicon-o-arrow-left')
                ->url(DashboardResource::getUrl('index')),
        ];
    }
}
