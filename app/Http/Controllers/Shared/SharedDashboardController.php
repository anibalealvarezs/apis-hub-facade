<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Models\CustomKpi;
use App\Models\Dashboard;
use App\Models\DerivedMetric;
use App\Models\Project;
use App\Services\Analytics\ChannelGranularityRegistry;
use App\Services\Analytics\KpiFormBuilder;
use App\Services\Analytics\PredefinedKpiRegistry;
use App\Services\WidgetDataService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SharedDashboardController extends Controller
{
    public function show(Request $request, $subdomain, Dashboard $dashboard)
    {
        $project = Project::where('subdomain', $subdomain)->firstOrFail();

        if ($dashboard->project_id !== $project->id) {
            abort(404);
        }

        if (!$dashboard->is_public) {
            abort(404);
        }

        $widgets = $dashboard->widgets()
            ->orderBy('grid_y')
            ->orderBy('grid_x')
            ->get();

        $service = app(WidgetDataService::class);
        foreach ($widgets as $widget) {
            $resolved = $service->resolveControls($dashboard, $widget);
            $widget->series_assets_options = [];

            $uiState = [];
            $kpiAssetMode = 'multiple';
            if ($widget->source_type === 'kpi' && !empty($widget->source_config['custom_kpi_id'])) {
                $kpi = CustomKpi::find($widget->source_config['custom_kpi_id']);
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

            $getAssetsForChannel = function (string $channel) {
                return empty($channel) ? [] : KpiFormBuilder::getAssetOptionsForChannel($channel);
            };

            // Mirrors LoadsDashboardViewData::$provideAssetFilters for the public (unauthenticated) view.
            $provideAssetFilters = function (string $channel, string $key, ?string $label = null, ?array $allowedIds = null) use (&$widget, &$resolved, $getAssetsForChannel, $kpiAssetMode) {
                $allAssets = $getAssetsForChannel($channel);
                if (empty($allAssets)) {
                    $allAssets = [];
                }

                // Only default the selection when nothing is stored for this key yet —
                // never clobber the dashboard author's configured selections.
                if (!isset($resolved['series_assets'][$key])) {
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
                        $resolved['series_assets'][$key] = [strval(key($defaultAssets))];
                    } else {
                        $resolved['series_assets'][$key] = [];
                    }
                }

                $computedMode = $kpiAssetMode;
                if (!ChannelGranularityRegistry::allowsMultipleAssets($channel)) {
                    $computedMode = 'single';
                }

                $widget->series_assets_options[$key] = [
                    'label' => $label ?? Str::headline($channel),
                    'options' => (object) $allAssets,
                    'mode' => $computedMode,
                ];
            };

            if ($widget->source_type === 'kpi') {
                // Dependent variable (channel-based)
                if (!empty($uiState['dependent_channel'])) {
                    $depAssetIds = !empty($uiState['dependent_asset_filter'])
                        ? (is_array($uiState['dependent_asset_filter']) ? $uiState['dependent_asset_filter'] : [$uiState['dependent_asset_filter']])
                        : null;
                    $provideAssetFilters(
                        $uiState['dependent_channel'],
                        'dependent',
                        'Dep (' . Str::headline($uiState['dependent_channel']) . ')',
                        $depAssetIds
                    );
                }

                // Independent variables (channel-based)
                if (isset($uiState['independent_variables']) && is_array($uiState['independent_variables'])) {
                    foreach ($uiState['independent_variables'] as $key => $var) {
                        if (!empty($var['independent_channel'])) {
                            $indAssetIds = !empty($var['independent_asset_filter'])
                                ? (is_array($var['independent_asset_filter']) ? $var['independent_asset_filter'] : [$var['independent_asset_filter']])
                                : null;
                            $provideAssetFilters(
                                $var['independent_channel'],
                                'independent_' . $key,
                                'Ind ' . $key . ' (' . Str::headline($var['independent_channel']) . ')',
                                $indAssetIds
                            );
                        }
                    }
                }

                // Dependent DM source series (per-series asset filters)
                if (!empty($uiState['dependent_dm_id'])) {
                    $depDm = DerivedMetric::find($uiState['dependent_dm_id']);
                    if ($depDm) {
                        foreach (array_values($depDm->source_series ?? []) as $sIdx => $series) {
                            $channel = $series['channel'] ?? '';
                            if (!empty($channel)) {
                                $provideAssetFilters(
                                    $channel,
                                    'dep_dm_' . $sIdx,
                                    $series['label'] ?? ('Series ' . chr(97 + $sIdx)),
                                    $series['asset_filter'] ?? null
                                );
                            }
                        }
                    }
                }

                // Independent DM source series (per-series asset filters)
                if (isset($uiState['independent_variables']) && is_array($uiState['independent_variables'])) {
                    foreach ($uiState['independent_variables'] as $key => $var) {
                        if (!empty($var['independent_dm_id'])) {
                            $indDm = DerivedMetric::find($var['independent_dm_id']);
                            if ($indDm) {
                                foreach (array_values($indDm->source_series ?? []) as $sIdx => $series) {
                                    $channel = $series['channel'] ?? '';
                                    if (!empty($channel)) {
                                        $provideAssetFilters(
                                            $channel,
                                            'ind_' . $key . '_dm_' . $sIdx,
                                            $series['label'] ?? ('Series ' . chr(97 + $sIdx)),
                                            $series['asset_filter'] ?? null
                                        );
                                    }
                                }
                            }
                        }
                    }
                }
            } elseif ($widget->source_type === 'derived_metric') {
                $dmId = $widget->source_config['derived_metric_id'] ?? null;
                if ($dmId) {
                    $dm = DerivedMetric::find($dmId);
                    if ($dm) {
                        foreach (array_values($dm->source_series ?? []) as $sIdx => $series) {
                            $channel = $series['channel'] ?? '';
                            if (!empty($channel)) {
                                $provideAssetFilters(
                                    $channel,
                                    'dm_' . $sIdx,
                                    $series['label'] ?? ('Series ' . chr(97 + $sIdx)),
                                    $series['asset_filter'] ?? null
                                );
                            }
                        }
                    }
                }
            }

            // Fallback for widgets without explicit series options (non-KPI raw metrics)
            if (empty($widget->series_assets_options)) {
                $primaryChannel = $resolved['channel'] ?? null;
                if (empty($primaryChannel) && !empty($resolved['series_channels'])) {
                    $primaryChannel = reset($resolved['series_channels']);
                }
                if (!empty($primaryChannel)) {
                    $rawAssetIds = !empty($resolved['assets']) && is_array($resolved['assets'])
                        ? $resolved['assets']
                        : null;
                    $provideAssetFilters($primaryChannel, '0', Str::headline($primaryChannel), $rawAssetIds);
                }
            }

            $widget->resolved_controls = $resolved;
        }

        return view('shared.public-dashboard', [
            'dashboard' => $dashboard,
            'project' => $project,
            'widgets' => $widgets,
        ]);
    }
}
