<?php

namespace App\Filament\App\Resources\DashboardResource\Traits;

use App\Models\Dashboard;
use App\Models\DashboardWidget;
use App\Models\CustomKpi;
use App\Models\AssetGroup;
use App\Services\WidgetDataService;
use App\Services\Analytics\PredefinedKpiRegistry;
use App\Services\Analytics\KpiFormBuilder;
use Filament\Facades\Filament;
use Illuminate\Support\Str;
use stdClass;

trait LoadsDashboardViewData
{
    public Dashboard $dashboard;
    public array $resolvedControls = [];
    public array $widgets = [];
    public array $allChannels = [];

    public function loadDashboardViewData(Dashboard $record): void
    {
        $this->dashboard = $record;
        $this->allChannels = \App\Services\Analytics\KpiFormBuilder::getActiveChannels();

        $this->widgets = $this->dashboard->widgets()
            ->orderBy('grid_y')
            ->orderBy('grid_x')
            ->get()
            ->toArray();

        $service = app(WidgetDataService::class);
        foreach ($this->widgets as &$widgetArray) {
            $widgetModel = (new DashboardWidget())->forceFill($widgetArray);
            $resolved = $service->resolveControls($this->dashboard, $widgetModel);
            $widgetArray['resolved_controls'] = $resolved;
            $widgetArray['series_assets_options'] = [];

            $uiState = [];
            $kpiAssetMode = 'multiple';
            if ($widgetModel->source_type === 'kpi' && ! empty($widgetModel->source_config['custom_kpi_id'])) {
                $kpi = CustomKpi::find($widgetModel->source_config['custom_kpi_id']);
                if ($kpi) {
                    $uiState = $kpi->filters['_ui_state'] ?? [];
                    $templateKey = $uiState['template_key'] ?? null;
                    if ($templateKey) {
                        $predefined = PredefinedKpiRegistry::getPredefinedKpis();
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
                $allAssets = KpiFormBuilder::getAssetOptionsForChannel($channel);
                if ($isAdmin) {
                    return $allAssets;
                }
                $allowed = $service->filterAllowedAssets(Filament::getTenant(), $user->id, $channel, array_keys($allAssets));
                $filtered = [];
                foreach ($allowed as $id) {
                    if (isset($allAssets[$id])) {
                        $filtered[$id] = $allAssets[$id];
                    }
                }

                return $filtered;
            };

            // Always provide asset filter options when a channel with assets is available
            $provideAssetFilters = function (string $channel, string $key, ?string $label = null, ?array $allowedIds = null) use (&$widgetArray, $getAssetsForChannel, $kpiAssetMode, &$resolved) {
                $assets = $getAssetsForChannel($channel);
                if (! empty($allowedIds)) {
                    $filtered = [];
                    foreach ($allowedIds as $id) {
                        if (isset($assets[$id])) {
                            $filtered[$id] = $assets[$id];
                        }
                    }
                    if (empty($filtered)) {
                        // If all provided IDs were invalid (e.g. channel changed but old assets remained),
                        // reset to all available channel assets so the user can make a new selection.
                        $assets = $getAssetsForChannel($channel);
                    } else {
                        $assets = $filtered;
                    }
                }
                if (empty($assets)) {
                    $assets = []; // ensure it's an array
                }
                
                // The view must not select all assets per series by default, but just a single asset per series.
                if (!empty($assets)) {
                    reset($assets);
                    $resolved['series_assets'][$key] = [strval(key($assets))];
                } else {
                    $resolved['series_assets'][$key] = [];
                }

                $widgetArray['series_assets_options'][$key] = [
                    'label' => $label ?? Str::headline($channel),
                    'options' => (object) $assets,
                    'mode' => $kpiAssetMode,
                ];
            };

            if (! empty($uiState['dependent_channel'])) {
                $depAssetIds = null;
                $configuredGroup = $uiState['global_asset_group'] ?? $uiState['dependent_asset_group'] ?? $resolved['series_asset_groups']['dependent'] ?? null;
                if (!empty($configuredGroup)) {
                    $group = AssetGroup::find($configuredGroup);
                    if ($group) {
                        $activeAssetIds = $group->active_items->pluck('asset_id')->toArray();
                        if (!empty($activeAssetIds)) {
                            $depAssetIds = $activeAssetIds;
                        }
                    }
                } elseif (! empty($uiState['dependent_asset_filter'])) {
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
                $provideAssetFilters($uiState['dependent_channel'], 'dependent', 'Dep (' . Str::headline($uiState['dependent_channel']) . ')', $depAssetIds);
            }

            if (isset($uiState['independent_variables']) && is_array($uiState['independent_variables'])) {
                foreach ($uiState['independent_variables'] as $key => $var) {
                    if (! empty($var['independent_channel'])) {
                        $idxKey = 'independent_' . $key;
                        $indAssetIds = null;
                        $configuredGroup = $uiState['global_asset_group'] ?? $var['independent_asset_group'] ?? $resolved['series_asset_groups'][$idxKey] ?? null;
                        if (!empty($configuredGroup)) {
                            $group = AssetGroup::find($configuredGroup);
                            if ($group) {
                                $activeAssetIds = $group->active_items->pluck('asset_id')->toArray();
                                if (!empty($activeAssetIds)) {
                                    $indAssetIds = $activeAssetIds;
                                }
                            }
                        } elseif (! empty($var['independent_asset_filter'])) {
                            $indAssetIds = is_array($var['independent_asset_filter'])
                                ? $var['independent_asset_filter']
                                : [$var['independent_asset_filter']];
                        }
                        if (!empty($resolved['series_assets'][$idxKey])) {
                            if ($indAssetIds === null) {
                                $indAssetIds = $resolved['series_assets'][$idxKey];
                            } else {
                                $indAssetIds = array_intersect($indAssetIds, $resolved['series_assets'][$idxKey]);
                            }
                        }
                        $provideAssetFilters($var['independent_channel'], $idxKey, 'Ind ' . $key . ' (' . Str::headline($var['independent_channel']) . ')', $indAssetIds);
                    }
                }
            }

            // Fallback for non-KPI widgets with a channel
            if (empty($widgetArray['series_assets_options'])) {
                if (empty($resolved['series_channels']) && empty($resolved['channel'])) {
                    $firstChannel = !empty($this->allChannels) ? array_key_first($this->allChannels) : null;
                    if ($firstChannel) {
                        $resolved['channel'] = $firstChannel;
                    }
                }
                
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

            // Expose edge case handling defaults from the KPI's _ui_state
            if ($widgetArray['source_type'] === 'kpi') {
                $resolved['edge_case_weighted'] = $uiState['edge_case_weighted'] ?? true;
                if (isset($uiState['edge_case_grouping'])) {
                    $resolved['edge_case_grouping'] = $uiState['edge_case_grouping'];
                } else {
                    // Smart default: dimension-based KPIs default to histogram grouping
                    $dimGranularities = ['page', 'query', 'post', 'device', 'country'];
                    $kpiGranularity = $uiState['granularity'] ?? $resolved['granularity'] ?? null;
                    $resolved['edge_case_grouping'] = in_array($kpiGranularity, $dimGranularities, true) ? 'histogram' : 'none';
                }
            }

            // Expose per-variable metric options for on-the-go selection (regression KPIs)
            $variables = [];
            $varIndex = 0;

            if ($widgetArray['source_type'] === 'kpi') {
                $depChannel = $uiState['dependent_channel'] ?? $resolved['channel'] ?? '';
                $depMetrics = ! empty($depChannel)
                    ? KpiFormBuilder::getMetricOptionsForChannel($depChannel)
                    : [];
                $variables['dependent'] = [
                    'index' => $varIndex++,
                    'channel' => $depChannel,
                    'channel_name' => !empty($depChannel) ? KpiFormBuilder::getChannelDisplayName($depChannel) : '',
                    'metrics' => $depMetrics,
                    'selected_metric' => $uiState['dependent_metric'] ?? '',
                ];

                if (isset($uiState['independent_variables']) && is_array($uiState['independent_variables'])) {
                    foreach ($uiState['independent_variables'] as $key => $var) {
                        $indChannel = $var['independent_channel'] ?? '';
                        $indMetrics = ! empty($indChannel)
                            ? KpiFormBuilder::getMetricOptionsForChannel($indChannel)
                            : [];
                        $variables['independent_' . $key] = [
                            'index' => $varIndex++,
                            'channel' => $indChannel,
                            'channel_name' => !empty($indChannel) ? KpiFormBuilder::getChannelDisplayName($indChannel) : '',
                            'metrics' => $indMetrics,
                            'selected_metric' => $var['independent_metric'] ?? '',
                        ];
                    }
                }
            } else {
                // Raw metrics variables mapping
                if (!empty($resolved['series_channels'])) {
                    foreach ($resolved['series_channels'] as $idx => $chan) {
                        $metrics = !empty($chan) ? KpiFormBuilder::getMetricOptionsForChannel($chan) : [];
                        $variables[strval($idx)] = [
                            'index' => $varIndex++,
                            'channel' => $chan,
                            'channel_name' => !empty($chan) ? KpiFormBuilder::getChannelDisplayName($chan) : '',
                            'metrics' => $metrics,
                        ];
                    }
                } elseif (!empty($resolved['channel'])) {
                    $metrics = !empty($resolved['channel']) ? KpiFormBuilder::getMetricOptionsForChannel($resolved['channel']) : [];
                    $variables['0'] = [
                        'index' => $varIndex++,
                        'channel' => $resolved['channel'],
                        'channel_name' => KpiFormBuilder::getChannelDisplayName($resolved['channel']),
                        'metrics' => $metrics,
                    ];
                }
            }

            if (!isset($resolved['metrics']) || !is_array($resolved['metrics'])) {
                $resolved['metrics'] = [];
            }
            // First pad the array with empty strings up to the number of variables
            for ($i = 0; $i < count($variables); $i++) {
                if (!isset($resolved['metrics'][$i])) {
                    $resolved['metrics'][$i] = '';
                }
            }
            foreach ($variables as $vConfig) {
                $idx = $vConfig['index'];
                $hasMetricInControl = !empty($resolved['metrics'][$idx]);
                $hasMetricInKpi = !empty($vConfig['selected_metric']) && $widgetArray['source_type'] === 'kpi';
                
                if (!$hasMetricInControl && !$hasMetricInKpi) {
                    if (!empty($vConfig['metrics'])) {
                        $resolved['metrics'][$idx] = array_key_first($vConfig['metrics']);
                    }
                }
            }
            ksort($resolved['metrics']);

            $widgetArray['variables'] = (object) $variables;
            // Keep flat metric_options for backward compatibility
            $widgetArray['metric_options'] = isset($depMetrics) ? (object) $depMetrics : new stdClass();
            $widgetArray['series_assets_options'] = (object) $widgetArray['series_assets_options'];
            $widgetArray['resolved_controls'] = $resolved;
        }
    }
}
