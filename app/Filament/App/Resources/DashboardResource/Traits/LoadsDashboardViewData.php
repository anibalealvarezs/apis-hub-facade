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
    public array $availableLanguages = [];

    public function getAllAssetGroups(): array
    {
        $project = Filament::getTenant() ?? ($this->project ?? null) ?? ($this->dashboard->project ?? null);
        if (!$project) return [];

        $groups = app(\App\Services\CollaboratorAssetAccessService::class)
            ->getAllowedAssetGroupQuery($project, auth()->user()?->getAuthIdentifier())
            ->get();

        $result = [];
        foreach ($groups as $group) {
            $result[$group->id] = $group->name;
        }
        return $result;
    }

    public function getChannelAssetGroupMap(): array
    {
        $project = Filament::getTenant() ?? ($this->project ?? null) ?? ($this->dashboard->project ?? null);
        if (!$project) return [];

        $groups = app(\App\Services\CollaboratorAssetAccessService::class)
            ->getAllowedAssetGroupQuery($project, auth()->user()?->getAuthIdentifier())
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
        $t0 = microtime(true);
        \Illuminate\Support\Facades\Log::debug('[DM_DEBUG] loadDashboardViewData ENTER', ['dashboard_id' => $record->id]);
        $this->dashboard = $record;
        $this->availableLanguages = $this->dashboard->getAvailableLanguages();
        $this->allChannels = \App\Services\Analytics\KpiFormBuilder::getActiveChannels();
        \Illuminate\Support\Facades\Log::debug('[DM_DEBUG] loadDashboardViewData getActiveChannels done', ['ms' => round((microtime(true) - $t0) * 1000, 1)]);

        $t1 = microtime(true);
        $this->widgets = $this->dashboard->widgets()
            ->orderBy('grid_y')
            ->orderBy('grid_x')
            ->get()
            ->toArray();
        \Illuminate\Support\Facades\Log::debug('[DM_DEBUG] loadDashboardViewData widgets loaded', ['count' => count($this->widgets), 'ms' => round((microtime(true) - $t1) * 1000, 1)]);

        $service = app(WidgetDataService::class);
        $wi = 0;
        $locale = app()->getLocale();
        foreach ($this->widgets as &$widgetArray) {
            $wi++;
            $widgetArray['name'] = \App\Filament\App\Resources\DashboardResource\Pages\DashboardBuilder::parseLocalizedValue($widgetArray['name'] ?? null, $locale);
            $widgetArray['title'] = \App\Filament\App\Resources\DashboardResource\Pages\DashboardBuilder::parseLocalizedValue($widgetArray['title'] ?? null, $locale);
            $widgetArray['description'] = \App\Filament\App\Resources\DashboardResource\Pages\DashboardBuilder::parseLocalizedValue($widgetArray['description'] ?? null, $locale);

            $tw = microtime(true);
            \Illuminate\Support\Facades\Log::debug("[DM_DEBUG] loadDashboardViewData processing widget {$wi}", ['id' => $widgetArray['id'] ?? '?', 'source_type' => $widgetArray['source_type'] ?? '?']);
            $widgetModel = (new DashboardWidget())->forceFill($widgetArray);
            $widgetArray['titles'] = $widgetModel->getTranslations('title');
            $widgetArray['descriptions'] = $widgetModel->getTranslations('description');
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
            $access = app(\App\Services\CollaboratorAssetAccessService::class);
            $project = Filament::getTenant() ?? ($this->project ?? null) ?? ($this->dashboard->project ?? null);

            $getAssetsForChannel = function ($channel) use ($user, $access, $project) {
                if (empty($channel)) {
                    return [];
                }
                $allAssets = KpiFormBuilder::getAssetOptionsForChannel($channel);
                if (! $user || $access->isUnrestricted($project, $user->id)) {
                    return $allAssets;
                }
                $allowed = $access->getAllowedAssetIdsForChannel($project, $user->id, $channel);
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

                $computedMode = $kpiAssetMode;
                if (!\App\Services\Analytics\ChannelGranularityRegistry::allowsMultipleAssets($channel)) {
                    $computedMode = 'single';
                }

                $widgetArray['series_assets_options'][$key] = [
                    'label' => $label ?? Str::headline($channel),
                    'options' => (object) $allAssets,
                    'mode' => $computedMode,
                ];
            };

            if (! empty($uiState['dependent_channel'])) {
                $depAssetIds = null;
                $configuredGroup = $uiState['global_asset_group'] ?? $uiState['dependent_asset_group'] ?? $resolved['series_asset_groups']['dependent'] ?? null;
                if (!empty($configuredGroup)) {
                    $group = $access->getAllowedAssetGroupQuery($project, $user?->getAuthIdentifier())->find($configuredGroup);
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
                            $group = $access->getAllowedAssetGroupQuery($project, $user?->getAuthIdentifier())->find($configuredGroup);
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

            // KPI DM series info + per-source-series asset options
            $widgetArray['dm_kpi_series'] = [];
            if ($widgetArray['source_type'] === 'kpi') {
                // Dependent DM
                if (! empty($uiState['dependent_source_type']) && $uiState['dependent_source_type'] === 'derived_metric' && ! empty($uiState['dependent_dm_id'])) {
                    $depDm = \App\Models\DerivedMetric::find($uiState['dependent_dm_id']);
                    if ($depDm) {
                        $depSourceSeries = array_values($depDm->source_series ?? []);
                        $widgetArray['dm_kpi_series']['dependent'] = [
                            'dm_id' => $depDm->id,
                            'dm_name' => $depDm->name,
                            'source_series' => $depSourceSeries,
                        ];
                        foreach ($depSourceSeries as $sIdx => $series) {
                            $channel = $series['channel'] ?? '';
                            if (! empty($channel)) {
                                $key = 'dep_dm_' . $sIdx;
                                $alreadySaved = isset($resolved['series_assets'][$key]);
                                $allAssets = $getAssetsForChannel($channel);
                                $computedMode = $kpiAssetMode;
                                if (! \App\Services\Analytics\ChannelGranularityRegistry::allowsMultipleAssets($channel)) {
                                    $computedMode = 'single';
                                }
                                $widgetArray['series_assets_options'][$key] = [
                                    'label' => $series['label'] ?? ('Series ' . chr(97 + $sIdx)),
                                    'options' => (object) ($allAssets ?: []),
                                    'mode' => $computedMode,
                                ];
                                if (! $alreadySaved) {
                                    $allowedIds = $series['asset_filter'] ?? null;
                                    $defaultAssets = $allAssets;
                                    if (is_array($allowedIds) && ! empty($allowedIds)) {
                                        $filtered = [];
                                        foreach ($allowedIds as $id) {
                                            if (isset($allAssets[$id])) {
                                                $filtered[$id] = $allAssets[$id];
                                            }
                                        }
                                        if (! empty($filtered)) {
                                            $defaultAssets = $filtered;
                                        }
                                    }
                                    if (! empty($defaultAssets)) {
                                        reset($defaultAssets);
                                        $resolved['series_assets'][$key] = [strval(key($defaultAssets))];
                                    } else {
                                        $resolved['series_assets'][$key] = [];
                                    }
                                }
                            }
                        }
                    }
                }
                // Independent DMs
                if (isset($uiState['independent_variables']) && is_array($uiState['independent_variables'])) {
                    foreach ($uiState['independent_variables'] as $key => $var) {
                        if (! empty($var['independent_source_type']) && $var['independent_source_type'] === 'derived_metric' && ! empty($var['independent_dm_id'])) {
                            $indDm = \App\Models\DerivedMetric::find($var['independent_dm_id']);
                            if ($indDm) {
                                $indSourceSeries = array_values($indDm->source_series ?? []);
                                $widgetArray['dm_kpi_series']['independent_' . $key] = [
                                    'dm_id' => $indDm->id,
                                    'dm_name' => $indDm->name,
                                    'source_series' => $indSourceSeries,
                                ];
                                foreach ($indSourceSeries as $sIdx => $series) {
                                    $channel = $series['channel'] ?? '';
                                    if (! empty($channel)) {
                                        $assetKey = 'ind_' . $key . '_dm_' . $sIdx;
                                        $alreadySaved = isset($resolved['series_assets'][$assetKey]);
                                        $allAssets = $getAssetsForChannel($channel);
                                        $computedMode = $kpiAssetMode;
                                        if (! \App\Services\Analytics\ChannelGranularityRegistry::allowsMultipleAssets($channel)) {
                                            $computedMode = 'single';
                                        }
                                        $widgetArray['series_assets_options'][$assetKey] = [
                                            'label' => $series['label'] ?? ('Series ' . chr(97 + $sIdx)),
                                            'options' => (object) ($allAssets ?: []),
                                            'mode' => $computedMode,
                                        ];
                                        if (! $alreadySaved) {
                                            $allowedIds = $series['asset_filter'] ?? null;
                                            $defaultAssets = $allAssets;
                                            if (is_array($allowedIds) && ! empty($allowedIds)) {
                                                $filtered = [];
                                                foreach ($allowedIds as $id) {
                                                    if (isset($allAssets[$id])) {
                                                        $filtered[$id] = $allAssets[$id];
                                                    }
                                                }
                                                if (! empty($filtered)) {
                                                    $defaultAssets = $filtered;
                                                }
                                            }
                                            if (! empty($defaultAssets)) {
                                                reset($defaultAssets);
                                                $resolved['series_assets'][$assetKey] = [strval(key($defaultAssets))];
                                            } else {
                                                $resolved['series_assets'][$assetKey] = [];
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }

            // DM source series asset options
            if ($widgetArray['source_type'] === 'derived_metric') {
                $dmId = $widgetArray['source_config']['derived_metric_id'] ?? null;
                if ($dmId) {
                    $dm = \App\Models\DerivedMetric::find($dmId);
                    if ($dm) {
                        $sourceSeries = array_values($dm->source_series ?? []);
                        $widgetArray['dm_source_series'] = $sourceSeries;
                        foreach ($sourceSeries as $sIdx => $series) {
                            $channel = $series['channel'] ?? '';
                            if (!empty($channel)) {
                                $dmAssetKey = 'dm_' . $sIdx;
                                $alreadySaved = isset($resolved['series_assets'][$dmAssetKey]);
                                // Set up series_assets_options needed by the settings modal
                                $allAssets = $getAssetsForChannel($channel);
                                $computedMode = $kpiAssetMode;
                                if (!\App\Services\Analytics\ChannelGranularityRegistry::allowsMultipleAssets($channel)) {
                                    $computedMode = 'single';
                                }
                                $widgetArray['series_assets_options'][$dmAssetKey] = [
                                    'label' => $series['label'] ?? ('Series ' . chr(97 + $sIdx)),
                                    'options' => (object) ($allAssets ?: []),
                                    'mode' => $computedMode,
                                ];
                                // Preserve view-saved selection; fallback to builder dm_assets or definition asset_filter
                                if (!$alreadySaved) {
                                    $allowedIds = $resolved['dm_assets'][$sIdx] ?? $series['asset_filter'] ?? null;
                                    $defaultAssets = $allAssets;
                                    if (is_array($allowedIds) && !empty($allowedIds)) {
                                        $filtered = [];
                                        foreach ($allowedIds as $id) {
                                            if (isset($allAssets[$id])) {
                                                $filtered[$id] = $allAssets[$id];
                                            }
                                        }
                                        if (!empty($filtered)) {
                                            $defaultAssets = $filtered;
                                        }
                                    }
                                    if (!empty($defaultAssets)) {
                                        reset($defaultAssets);
                                        $resolved['series_assets'][$dmAssetKey] = [strval(key($defaultAssets))];
                                    } else {
                                        $resolved['series_assets'][$dmAssetKey] = [];
                                    }
                                }
                            }
                        }
                    }
                }
            }

            // Fallback for non-KPI widgets with a channel
            if (empty($widgetArray['series_assets_options'])) {
                $primaryChannel = $resolved['channel'] ?? null;
                if (empty($primaryChannel) && !empty($resolved['series_channels'])) {
                    $primaryChannel = reset($resolved['series_channels']);
                }
                if (empty($primaryChannel) && !empty($this->allChannels)) {
                    $primaryChannel = array_key_first($this->allChannels);
                }

                if (!empty($primaryChannel)) {
                    $rawAssetIds = null;
                    if (!empty($resolved['assets'])) {
                        $rawAssetIds = $resolved['assets'];
                    } elseif (!empty($resolved['series_assets']['0'])) {
                        $rawAssetIds = $resolved['series_assets']['0'];
                    } elseif (!empty($resolved['raw_series'][0]['assets'])) {
                        $rawAssetIds = $resolved['raw_series'][0]['assets'];
                    }
                    $provideAssetFilters($primaryChannel, '0', null, $rawAssetIds);
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
                            } elseif (str_starts_with($assetKey, 'dep_dm_')) {
                                $sIdx = (int) substr($assetKey, 7);
                                $channel = ($widgetArray['dm_kpi_series']['dependent']['source_series'][$sIdx] ?? [])['channel'] ?? null;
                            } elseif (preg_match('/^ind_(\d+)_dm_(\d+)$/', $assetKey, $m)) {
                                $vKey = 'independent_' . $m[1];
                                $sIdx = (int) $m[2];
                                $channel = ($widgetArray['dm_kpi_series'][$vKey]['source_series'][$sIdx] ?? [])['channel'] ?? null;
                            }
                        } elseif ($widgetArray['source_type'] === 'derived_metric') {
                            if (str_starts_with($assetKey, 'dm_')) {
                                $sIdx = (int) substr($assetKey, 3);
                                $dmSeries = $widgetArray['dm_source_series'] ?? [];
                                $channel = $dmSeries[$sIdx]['channel'] ?? null;
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
                // Add DM source series variables for KPI DM variables (view settings modal)
                foreach ($widgetArray['dm_kpi_series'] ?? [] as $varKey => $dmInfo) {
                    foreach ($dmInfo['source_series'] ?? [] as $sIdx => $series) {
                        $channel = $series['channel'] ?? '';
                        if (! empty($channel)) {
                            // Match the asset key scheme used in series_assets_options and builder
                            $prefix = $varKey === 'dependent' ? 'dep' : 'ind';
                            $entryKey = ($varKey === 'dependent' ? '' : str_replace('independent_', '', $varKey) . '_');
                            $entryKey = ($varKey === 'dependent' ? 'dep' : 'ind_' . str_replace('independent_', '', $varKey)) . '_dm_' . $sIdx;
                            $variables[$entryKey] = [
                                'index' => $varIndex++,
                                'channel' => $channel,
                                'channel_name' => KpiFormBuilder::getChannelDisplayName($channel),
                                'metrics' => [$series['metric'] => $series['metric']],
                                'selected_metric' => $series['metric'],
                                'dm_source_label' => $series['label'] ?? ('Source ' . chr(97 + $sIdx)),
                                'dm_name' => $dmInfo['dm_name'] ?? '',
                            ];
                        }
                    }
                }
            } elseif ($widgetArray['source_type'] === 'derived_metric') {
                $dmSeries = $widgetArray['dm_source_series'] ?? [];
                foreach ($dmSeries as $sIdx => $series) {
                    $channel = $series['channel'] ?? '';
                    if (!empty($channel)) {
                        $variables['dm_' . $sIdx] = [
                            'index' => $varIndex++,
                            'channel' => $channel,
                            'channel_name' => KpiFormBuilder::getChannelDisplayName($channel),
                            'metrics' => [$series['metric'] => $series['metric']],
                            'selected_metric' => $series['metric'],
                        ];
                    }
                }
            } else {
                // Raw metrics variables mapping - enforce single series for non-KPI widgets
                $primaryChannel = $resolved['channel'] ?? null;
                if (empty($primaryChannel) && !empty($resolved['series_channels'])) {
                    $primaryChannel = reset($resolved['series_channels']);
                }
                
                if (!empty($primaryChannel)) {
                    $granularity = $resolved['granularity'] ?? null;
                    $dependency  = $resolved['dependency'] ?? null;
                    $metrics     = KpiFormBuilder::getMetricOptionsForChannel($primaryChannel, $granularity, $dependency);
                    $variables['0'] = [
                        'index' => $varIndex++,
                        'channel' => $primaryChannel,
                        'channel_name' => KpiFormBuilder::getChannelDisplayName($primaryChannel),
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
                $widgetControls = is_array($widgetArray['controls'] ?? null)
                    ? $widgetArray['controls']
                    : (json_decode($widgetArray['controls'] ?? '[]', true) ?: []);
                $keepGuidance = $widgetControls['keep_template_guidance'] ?? null;

                // If user explicitly opted out, skip entirely
                if ($keepGuidance === false) {
                    continue;
                }

                $templateKey = $widgetControls['template_key']
                    ?? (isset($kpi) ? ($kpi->filters['_ui_state']['template_key'] ?? null) : null);

                // Fallback for existing KPIs: try to match by name in the predefined registry
                if (!$templateKey && isset($kpi) && !empty($kpi->name)) {
                    $rawKpiName = is_array($kpi->name) ? ($kpi->name['en'] ?? reset($kpi->name)) : $kpi->name;
                    $predefinedAll = PredefinedKpiRegistry::getPredefinedKpis();
                    foreach ($predefinedAll as $key => $def) {
                        if (($def['name'] ?? '') === $rawKpiName || ($def['name'] ?? '') === $kpi->name) {
                            $templateKey = $key;
                            break;
                        }
                    }
                }

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

            \Illuminate\Support\Facades\Log::debug("[DM_DEBUG] loadDashboardViewData widget {$wi} DONE", ['id' => $widgetArray['id'] ?? '?', 'ms' => round((microtime(true) - $tw) * 1000, 1)]);
        }
        \Illuminate\Support\Facades\Log::debug('[DM_DEBUG] loadDashboardViewData EXIT', ['total_widgets' => $wi, 'total_ms' => round((microtime(true) - $t0) * 1000, 1)]);
    }

    public function saveWidgetControls(int $widgetId, array $controls, ?string $title = null, ?string $description = null, array $titles = [], array $descriptions = []): void
    {
        $widget = DashboardWidget::where('dashboard_id', $this->dashboard->id)
            ->findOrFail($widgetId);

        $widget->controls = $controls;
        if (! empty($titles)) {
            $widget->setTranslations('title', array_filter($titles, fn ($v) => $v !== null && $v !== ''));
        } elseif ($title !== null) {
            $widget->title = $title;
        }
        if (! empty($descriptions)) {
            $widget->setTranslations('description', array_filter($descriptions, fn ($v) => $v !== null && $v !== ''));
        } elseif ($description !== null) {
            $widget->description = $description;
        }
        $widget->save();

        if (property_exists($this, 'unsavedChanges')) {
            $this->unsavedChanges = true;
        }

        \Filament\Notifications\Notification::make()
            ->title(__('Widget controls saved'))
            ->success()
            ->send();
    }
}
