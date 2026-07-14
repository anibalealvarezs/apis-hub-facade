<?php

namespace App\Filament\App\Resources\DashboardResource\Traits;

use App\Models\Dashboard;
use App\Models\DashboardWidget;
use App\Models\CustomKpi;
use App\Models\AssetGroup;
use App\Services\WidgetDataService;
use App\Services\Analytics\PredefinedKpiRegistry;
use App\Services\Analytics\KpiFormBuilder;
use App\Filament\App\Pages\KpiReference;
use Filament\Facades\Filament;
use Illuminate\Support\Str;
use stdClass;

trait LoadsDashboardViewData
{
    public Dashboard $dashboard;
    public array $resolvedControls = [];
    public array $widgets = [];
    public array $allChannels = [];

    public function getAllAssetGroups(): array
    {
        $project = Filament::getTenant();
        if (!$project) return [];

        $groups = \App\Models\AssetGroup::where('project_id', $project->id)->get();
        $result = [];
        foreach ($groups as $group) {
            $result[$group->id] = $group->name;
        }
        return $result;
    }

    public function getChannelAssetGroupMap(): array
    {
        $project = Filament::getTenant();
        if (!$project) return [];

        $groups = \App\Models\AssetGroup::where('project_id', $project->id)
            ->with('items')
            ->get();

        $map = [];
        foreach ($groups as $group) {
            $activeAssets = $group->active_items;
            foreach ($activeAssets->groupBy('channel') as $channel => $items) {
                if (!isset($map[$channel])) {
                    $map[$channel] = [];
                }
                $map[$channel][(string) $group->id] = $items->pluck('asset_id')->map(fn ($v) => (string) $v)->values()->toArray();
            }
        }
        return $map;
    }

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
                $allAssets = $getAssetsForChannel($channel);
                if (empty($allAssets)) {
                    $allAssets = [];
                }

                // Determine default selection from allowed IDs (KPI group / widget config).
                // Options list always shows ALL channel assets — group filtering happens
                // client-side via isViewAssetInGroup so switching asset groups works.
                $defaultAssets = $allAssets;
                if (!empty($allowedIds)) {
                    $filtered = [];
                    foreach ($allowedIds as $id) {
                        if (isset($allAssets[$id])) {
                            $filtered[$id] = $allAssets[$id];
                        }
                    }
                    if (!empty($filtered)) {
                        $defaultAssets = $filtered;
                    }
                    // If all filtered out (e.g. channel changed), keep default as all assets
                }

                // Default selection: single asset
                if (!empty($defaultAssets)) {
                    reset($defaultAssets);
                    $resolved['series_assets'][$key] = [strval(key($defaultAssets))];
                } else {
                    $resolved['series_assets'][$key] = [];
                }

                $widgetArray['series_assets_options'][$key] = [
                    'label' => $label ?? Str::headline($channel),
                    'options' => (object) $allAssets,
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

            // Apply dashboard-level asset group filter to resolved series assets.
            // This prevents JS applyAssetGroup() from detecting changes and
            // triggering an unnecessary widget reload on page init.
            $dashboardAssetGroup = $this->dashboard->controls['asset_group'] ?? null;
            if ($dashboardAssetGroup && method_exists($this, 'getChannelAssetGroupMap')) {
                $channelGroupMap = $this->getChannelAssetGroupMap();
                if (!empty($channelGroupMap)) {
                    foreach ($resolved['series_assets'] ?? [] as $assetKey => $assetIds) {
                        $channel = null;
                        if ($widgetArray['source_type'] === 'kpi') {
                            if ($assetKey === 'dependent') {
                                $channel = $uiState['dependent_channel'] ?? $resolved['channel'] ?? null;
                            } elseif (str_starts_with($assetKey, 'independent_')) {
                                $idx = substr($assetKey, strlen('independent_'));
                                $channel = $uiState['independent_variables'][$idx]['independent_channel'] ?? null;
                            }
                        } else {
                            if (is_numeric($assetKey)) {
                                $idx = (int) $assetKey;
                                $channel = $resolved['series_channels'][$idx] ?? $resolved['channel'] ?? null;
                            }
                        }
                        if ($channel && isset($channelGroupMap[$channel][$dashboardAssetGroup])) {
                            $allowedAssets = $channelGroupMap[$channel][$dashboardAssetGroup];
                            $validAssets = array_intersect($assetIds, $allowedAssets);
                            if (!empty($validAssets)) {
                                $resolved['series_assets'][$assetKey] = array_values($validAssets);
                            } elseif (!empty($allowedAssets)) {
                                $resolved['series_assets'][$assetKey] = [reset($allowedAssets)];
                            }
                        }
                    }
                }
            }

            // Expose KPI-level defaults (from _ui_state) before dashboard-level controls
            if ($widgetArray['source_type'] === 'kpi') {
                $widgetControls = $widgetModel->controls ?? [];

                // Dates: widget → KPI config → dashboard → hardcoded default
                // Note: KPI uses start_date/end_date, dashboard/widget uses date_start/date_end
                if (!isset($widgetControls['date_start']) && !empty($uiState['start_date'])) {
                    $resolved['date_start'] = $uiState['start_date'];
                }
                if (!isset($widgetControls['date_end']) && !empty($uiState['end_date'])) {
                    $resolved['date_end'] = $uiState['end_date'];
                }

                // Zero handling: widget → KPI config → dashboard → 'remove'
                if (!isset($widgetControls['zero_handling']) && isset($uiState['zero_handling'])) {
                    $resolved['zero_handling'] = $uiState['zero_handling'];
                }

                // Granularity: widget → KPI config → dashboard → 'daily'
                if (!isset($widgetControls['granularity']) && isset($uiState['granularity'])) {
                    $resolved['granularity'] = $uiState['granularity'];
                }

                // Max ratio: widget → KPI config → null (no cap)
                if (!isset($widgetControls['max_ratio']) && isset($uiState['max_ratio'])) {
                    $resolved['max_ratio'] = $uiState['max_ratio'];
                }

                // Edge cases: widget → KPI config → dashboard → smart default
                if (!isset($widgetControls['edge_case_weighted'])) {
                    $resolved['edge_case_weighted'] = $uiState['edge_case_weighted']
                        ?? $resolved['edge_case_weighted']
                        ?? true;
                }
                if (!isset($widgetControls['edge_case_grouping'])) {
                    $resolved['edge_case_grouping'] = $uiState['edge_case_grouping']
                        ?? $resolved['edge_case_grouping']
                        ?? null;
                    if ($resolved['edge_case_grouping'] === null) {
                        $dimGranularities = ['page', 'query', 'post', 'device', 'country'];
                        $kpiGranularity = $uiState['granularity'] ?? $resolved['granularity'] ?? null;
                        $resolved['edge_case_grouping'] = in_array($kpiGranularity, $dimGranularities, true) ? 'histogram' : 'none';
                    }
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
            \Illuminate\Support\Facades\Log::debug('[LoadsDashboardViewData] BEFORE padding | Widget: ' . ($widgetArray['id'] ?? '?') . ' | variables count: ' . count($variables) . ' | resolved metrics: ' . json_encode($resolved['metrics']));
            // First pad the array with empty strings up to the number of variables
            for ($i = 0; $i < count($variables); $i++) {
                if (!isset($resolved['metrics'][$i])) {
                    $resolved['metrics'][$i] = '';
                }
            }
            \Illuminate\Support\Facades\Log::debug('[LoadsDashboardViewData] AFTER padding | Widget: ' . ($widgetArray['id'] ?? '?') . ' | resolved metrics: ' . json_encode($resolved['metrics']));
            foreach ($variables as $vConfig) {
                \Illuminate\Support\Facades\Log::debug('[LoadsDashboardViewData] Variable loop | Widget: ' . ($widgetArray['id'] ?? '?') . ' | index: ' . ($vConfig['index'] ?? '?') . ' | selected_metric: ' . ($vConfig['selected_metric'] ?? '__NONE__') . ' | metrics keys: ' . json_encode(array_keys($vConfig['metrics'] ?? [])));
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

            $widgetArray['kpi_theory'] = null;
            if ($widgetArray['source_type'] === 'kpi') {
                $templateKey = $uiState['template_key'] ?? null;
                if ($templateKey) {
                    $guidance = KpiReference::getGuidance($templateKey);
                    if (!empty($guidance['type_label']) || !empty($guidance['explanation'])) {
                        $predefined = PredefinedKpiRegistry::getPredefinedKpis();
                        $kpiName = $predefined[$templateKey]['name']
                            ?? $widgetArray['title']
                            ?? $widgetArray['name']
                            ?? '';
                        $widgetArray['kpi_theory'] = [
                            'name' => $kpiName,
                            'type_label' => $guidance['type_label'] ?? '',
                            'explanation' => $guidance['explanation'] ?? '',
                            'use_case' => $guidance['use_case'] ?? '',
                            'interpretation' => $guidance['interpretation'] ?? '',
                        ];
                    }
                }
            }
        }
    }
}
