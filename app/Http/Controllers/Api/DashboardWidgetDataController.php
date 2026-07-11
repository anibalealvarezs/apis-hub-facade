<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Dashboard;
use App\Models\DashboardWidget;
use App\Models\Project;
use App\Services\WidgetDataService;
use App\Services\Analytics\KpiPayloadBuilder;
use App\Services\RemoteEngineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardWidgetDataController extends Controller
{
    public function __construct(
        protected WidgetDataService $widgetDataService,
        protected RemoteEngineService $remoteEngineService,
    ) {}

    public function show(Request $request, DashboardWidget $widget): JsonResponse
    {
        $validated = $request->validate([
            'tenant' => 'required|string',
            'controls' => 'nullable|array',
        ]);

        $project = Project::where('subdomain', $validated['tenant'])->firstOrFail();
        $dashboard = $widget->dashboard;

        if ($dashboard->project_id !== $project->id) {
            return response()->json(['error' => 'Forbidden'], 403, [], JSON_UNESCAPED_UNICODE);
        }

        $user = $request->user();

        if (!$dashboard->is_public) {
            if (!$user || $user->cannot('view', $dashboard)) {
                return response()->json(['error' => 'Unauthorized'], 403, [], JSON_UNESCAPED_UNICODE);
            }
        }

        $dashboard = $widget->dashboard;

        $resolvedControls = $this->widgetDataService->resolveControls($dashboard, $widget);

        // Merge runtime overrides from the request (on-the-go UX)
        if (isset($validated['controls'])) {
            if (array_key_exists('granularity', $validated['controls']) && empty($validated['controls']['granularity'])) {
                unset($validated['controls']['granularity']);
            }
            $resolvedControls = array_merge($resolvedControls, $validated['controls']);
        }

        if (!$dashboard->is_public && $user && !empty($resolvedControls['channel'])) {
            $isProjectUser = \Illuminate\Support\Facades\DB::table('model_has_roles')
                ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                ->where('model_has_roles.model_id', $user->id)
                ->where('model_has_roles.project_id', $project->id)
                ->where('roles.name', 'project_user')
                ->exists();

            if ($isProjectUser) {
                $assetList = $this->widgetDataService->getResolvedAssetList($widget, $resolvedControls);
                $allowedAssets = $this->widgetDataService->filterAllowedAssets(
                    $project, $user->id, $resolvedControls['channel'], $assetList
                );

                if (empty($allowedAssets)) {
                    return response()->json([
                        'success' => false,
                        'error' => 'access_restricted',
                        'message' => 'You do not have access to the selected asset for this dashboard.',
                    ], 403, [], JSON_UNESCAPED_UNICODE);
                }
            }
        }

        try {
            $data = match ($widget->source_type) {
                'kpi' => $this->handleKpiSource($project, $widget, $resolvedControls),
                'metric' => $this->handleMetricSource($project, $widget, $resolvedControls),
                'entity' => $this->handleEntitySource($project, $widget, $resolvedControls),
                default => throw new \InvalidArgumentException('Unknown source type: ' . $widget->source_type),
            };

            // Ensure controls.metrics reflects the actual resolved metrics
            if ($widget->source_type === 'kpi' && $widget->customKpi) {
                $kpiUiState = $widget->customKpi->filters['_ui_state'] ?? [];
                $runtimeMetrics = $resolvedControls['metrics'] ?? [];
                if (empty($runtimeMetrics[0]) && !empty($kpiUiState['dependent_metric'])) {
                    $resolvedControls['metrics'][0] = $kpiUiState['dependent_metric'];
                }
                if (empty($runtimeMetrics[1]) && isset($kpiUiState['independent_variables'])) {
                    $firstIvar = reset($kpiUiState['independent_variables']);
                    if (!empty($firstIvar['independent_metric'])) {
                        $resolvedControls['metrics'][1] = $firstIvar['independent_metric'];
                    }
                }
            }

            $effectiveWidgetType = $widget->widget_type;

            if (isset($data['anomaly_detected'])) {
                $chartTypes = ['line_chart', 'bar_chart', 'sparkline', 'anomaly_chart'];
                $series = $data['series'] ?? ['dates' => [], 'values' => []];
                $anomalyDates = array_flip($data['anomaly_dates'] ?? []);

                if (in_array($effectiveWidgetType, $chartTypes)) {
                    $effectiveWidgetType = 'anomaly_chart';
                    if (!empty($series['dates'])) {
                        $pointRadius = array_map(
                            fn($d) => isset($anomalyDates[$d]) ? 7 : 2,
                            $series['dates']
                        );
                        $pointBg = array_map(
                            fn($d) => isset($anomalyDates[$d]) ? '#ef4444' : 'transparent',
                            $series['dates']
                        );
                        $pointBorder = array_map(
                            fn($d) => isset($anomalyDates[$d]) ? '#ef4444' : 'transparent',
                            $series['dates']
                        );
                        $pointBorderWidth = array_map(
                            fn($d) => isset($anomalyDates[$d]) ? 3 : 0,
                            $series['dates']
                        );

                        $data = [
                            'labels' => $series['dates'],
                            'datasets' => [
                                [
                                    'label' => $widget->name ?: 'Metric',
                                    'data' => $series['values'],
                                    'borderColor' => '#3b82f6',
                                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                                    'fill' => true,
                                    'tension' => 0.3,
                                    'pointRadius' => $pointRadius,
                                    'pointBackgroundColor' => $pointBg,
                                    'pointBorderColor' => $pointBorder,
                                    'pointBorderWidth' => $pointBorderWidth,
                                ],
                            ],
                            'anomaly_dates' => array_keys($anomalyDates),
                        ];
                    } else {
                        $data = [
                            'labels' => [],
                            'datasets' => [],
                            'anomaly_dates' => [],
                        ];
                    }
                } elseif ($effectiveWidgetType === 'table') {
                    $rows = [];
                    foreach ($series['dates'] ?? [] as $i => $date) {
                        $rows[] = [
                            'date' => $date,
                            'value' => $series['values'][$i] ?? 0,
                            'anomaly' => isset($anomalyDates[$date]) ? 'Yes' : 'No',
                        ];
                    }
                    $data = [
                        'columns' => [
                            ['key' => 'date', 'label' => 'Date'],
                            ['key' => 'value', 'label' => 'Value', 'format' => 'number'],
                            ['key' => 'anomaly', 'label' => 'Anomaly'],
                        ],
                        'rows' => $rows,
                        'anomaly_detected' => $data['anomaly_detected'],
                        'anomaly_dates' => $data['anomaly_dates'],
                    ];
                } elseif ($effectiveWidgetType === 'tile') {
                    $anomalyCount = count($data['anomaly_dates'] ?? []);
                    $totalPoints = $data['data_points'] ?? count($series['dates'] ?? []);
                    $data = [
                        'value' => $anomalyCount,
                        'label' => 'Anomalies Detected',
                        'previous' => null,
                        'suffix' => $totalPoints > 0 ? " / {$totalPoints} points" : '',
                    ];
                } elseif ($effectiveWidgetType === 'gauge') {
                    $anomalyCount = count($data['anomaly_dates'] ?? []);
                    $totalPoints = $data['data_points'] ?? count($series['dates'] ?? []);
                    $pct = $totalPoints > 0 ? round(($anomalyCount / $totalPoints) * 100) : 0;
                    $data = [
                        'value' => $pct,
                        'min' => 0,
                        'max' => 100,
                        'label' => 'Anomaly Rate',
                    ];
                }
            } elseif ($effectiveWidgetType === 'combo_chart' && isset($data['series']['macd_line'])) {
                $series = $data['series'];
                $data = [
                    'labels' => $series['dates'] ?? [],
                    'datasets' => [
                        [
                            'type' => 'line',
                            'label' => 'MACD Line',
                            'data' => $series['macd_line'],
                            'borderColor' => '#3b82f6',
                            'borderWidth' => 2,
                            'fill' => false,
                            'pointRadius' => 0
                        ],
                        [
                            'type' => 'line',
                            'label' => 'Signal Line',
                            'data' => $series['signal_line'],
                            'borderColor' => '#f59e0b',
                            'borderWidth' => 2,
                            'fill' => false,
                            'pointRadius' => 0
                        ],
                        [
                            'type' => 'bar',
                            'label' => 'Histogram',
                            'data' => $series['histogram'],
                            'backgroundColor' => array_map(fn($v) => $v >= 0 ? 'rgba(34, 197, 94, 0.5)' : 'rgba(239, 68, 68, 0.5)', $series['histogram'] ?? [])
                        ]
                    ]
                ];
            } elseif ($effectiveWidgetType === 'scatter_plot' && isset($data['scatter_data'])) {
                $scatter = $data['scatter_data'];
                $rawX = $scatter['x'];
                $rawY = $scatter['y'];
                $n = count($rawX);

                $maxRatio = $resolvedControls['max_ratio'] ?? null;
                $modelType = $data['model_type'] ?? 'linear';

                // Remove data points where position > 30 (poor-ranking noise)
                // Applied before regression so the trend line also excludes them.
                if ($resolvedControls['metrics'][0] === 'position') {
                    $filteredX = []; $filteredY = [];
                    foreach ($rawX as $i => $x) {
                        if ($rawY[$i] <= 30) { $filteredX[] = $x; $filteredY[] = $rawY[$i]; }
                    }
                    $rawX = $filteredX; $rawY = $filteredY; $n = count($rawX);
                } elseif ($resolvedControls['metrics'][1] === 'position') {
                    $filteredX = []; $filteredY = [];
                    foreach ($rawX as $i => $x) {
                        if ($x <= 30) { $filteredX[] = $x; $filteredY[] = $rawY[$i]; }
                    }
                    $rawX = $filteredX; $rawY = $filteredY; $n = count($rawX);
                }

                // Build regression from filtered data
                $b = 0;
                $m = 0;
                $rSquared = null;
                if ($n >= 2) {
                    if ($modelType === 'log-log') {
                        $logX = array_map(fn($v) => $v > 0 ? log($v) : null, $rawX);
                        $logY = array_map(fn($v) => $v > 0 ? log($v) : null, $rawY);
                        $valid = [];
                        foreach ($logX as $i => $lx) {
                            if ($lx !== null && $logY[$i] !== null) {
                                $valid[] = true;
                            }
                        }
                        $validN = count($valid);
                        if ($validN >= 2) {
                            $validLogX = [];
                            $validLogY = [];
                            foreach ($logX as $i => $lx) {
                                if ($lx !== null && $logY[$i] !== null) {
                                    $validLogX[] = $lx;
                                    $validLogY[] = $logY[$i];
                                }
                            }
                            $sumX = array_sum($validLogX);
                            $sumY = array_sum($validLogY);
                            $sumXY = array_sum(array_map(fn($a, $b) => $a * $b, $validLogX, $validLogY));
                            $sumX2 = array_sum(array_map(fn($v) => $v * $v, $validLogX));
                            $m = ($validN * $sumXY - $sumX * $sumY) / ($validN * $sumX2 - $sumX * $sumX);
                            $b = ($sumY - $m * $sumX) / $validN;
                            $ssRes = array_sum(array_map(fn($lx, $ly) => ($ly - ($m * $lx + $b)) ** 2, $validLogX, $validLogY));
                            $ssTot = array_sum(array_map(fn($ly) => ($ly - $sumY / $validN) ** 2, $validLogY));
                            $rSquared = $ssTot > 0 ? 1 - $ssRes / $ssTot : null;
                        }
                    } else {
                        $sumX = array_sum($rawX);
                        $sumY = array_sum($rawY);
                        $sumXY = array_sum(array_map(fn($a, $b) => $a * $b, $rawX, $rawY));
                        $sumX2 = array_sum(array_map(fn($v) => $v * $v, $rawX));
                        $denom = $n * $sumX2 - $sumX * $sumX;
                        if ($denom != 0) {
                            $m = ($n * $sumXY - $sumX * $sumY) / $denom;
                            $b = ($sumY - $m * $sumX) / $n;
                            $ssRes = array_sum(array_map(fn($xi, $yi) => ($yi - ($m * $xi + $b)) ** 2, $rawX, $rawY));
                            $ssTot = array_sum(array_map(fn($yi) => ($yi - $sumY / $n) ** 2, $rawY));
                            $rSquared = $ssTot > 0 ? 1 - $ssRes / $ssTot : null;
                        }
                    }
                }

                $minX = $n > 0 ? min($rawX) : 0;
                $maxX = $n > 0 ? max($rawX) : 0;

                $trendLineData = [];
                if ($modelType === 'log-log') {
                    $steps = 20;
                    if ($maxX > $minX) {
                        $stepSize = ($maxX - $minX) / $steps;
                        for ($i = 0; $i <= $steps; $i++) {
                            $currX = $minX + ($i * $stepSize);
                            if ($currX > 0) {
                                $trendLineData[] = ['x' => $currX, 'y' => exp($b) * pow($currX, $m)];
                            }
                        }
                    }
                } else {
                    if ($maxX > $minX) {
                        $trendLineData = [
                            ['x' => $minX, 'y' => $m * $minX + $b],
                            ['x' => $maxX, 'y' => $m * $maxX + $b],
                        ];
                    }
                }

                // Filter points for display only; regression stays based on all data
                // Two-layer filter for volume metrics (impressions, clicks, etc.):
                //   1. Hard floor — never display X below this threshold.
                //   2. Dynamic percentile — for datasets large enough, cut the bottom 20%.
                // For bounded metrics (position, ratio), use a conservative 2nd percentile.
                $volumeMetrics = ['impressions', 'clicks', 'reach', 'engaged_users', 'sessions', 'new_users', 'pageviews', 'link_clicks', 'followers'];
                $xMetric = $resolvedControls['metrics'][1] ?? '';
                $isVolumeMetric = in_array($xMetric, $volumeMetrics);
                $hardFloor = null;
                $xThreshold = null;
                $totalN = count($rawX);

                if ($isVolumeMetric) {
                    if ($xMetric === 'clicks') {
                        $hardFloor = 3;
                    } else {
                        $hardFloor = 5;
                    }
                    $minPoints = 8;
                    if ($totalN >= $minPoints) {
                        $sortedX = $rawX;
                        sort($sortedX);
                        $pctIdx = max(1, (int) floor($totalN * 0.20));
                        $xThreshold = $sortedX[$pctIdx];
                    }
                } elseif ($totalN >= 20) {
                    $sortedX = $rawX;
                    sort($sortedX);
                    $pctIdx = max(1, (int) floor($totalN * 0.02));
                    $xThreshold = $sortedX[$pctIdx];
                }

                // Identify the histogram cluster point (lowest X) and relabel to [[[others]]]
                $clusterIndex = null;
                $isHistogram = ($resolvedControls['edge_case_grouping'] ?? null) === 'histogram';
                if ($isHistogram && $totalN > 0) {
                    $minX = min($rawX);
                    foreach ($rawX as $i => $x) {
                        if ($x === $minX) {
                            $clusterIndex = $i;
                            break;
                        }
                    }
                }

                $points = [];
                foreach ($rawX as $i => $x) {
                    $y = $rawY[$i];
                    $isCluster = ($clusterIndex !== null && $i === $clusterIndex);

                    // Apply volume filter only to non-cluster points
                    if (!$isCluster) {
                        if ($hardFloor !== null && $x < $hardFloor) {
                            continue;
                        }
                        if ($xThreshold !== null && $x <= $xThreshold) {
                            continue;
                        }
                    }
                    if ($maxRatio !== null && ($y > $maxRatio || $y < 0)) {
                        continue;
                    }
                    $point = ['x' => $x, 'y' => $y];
                    if (isset($scatter['labels'][$i])) {
                        $point['label'] = $isCluster ? '[[[others]]]' : $scatter['labels'][$i];
                    }
                    if ($isCluster) {
                        $point['_isCluster'] = true;
                    }
                    $points[] = $point;
                }

                $data = [
                    'labels' => [],
                    'datasets' => [
                        [
                            'type' => 'scatter',
                            'label' => 'Data Points',
                            'data' => $points,
                            'backgroundColor' => 'rgba(59, 130, 246, 0.6)',
                            'pointRadius' => 4,
                        ],
                        [
                            'type' => 'line',
                            'label' => 'Trend Line',
                            'data' => $trendLineData,
                            'borderColor' => '#3b82f6',
                            'borderWidth' => 2,
                            'fill' => false,
                            'pointRadius' => 0,
                        ],
                    ],
                    'x_label' => $scatter['x_label'] ?? 'Independent Variable',
                    'y_label' => $scatter['y_label'] ?? null,
                    'r_squared' => $rSquared,
                    'coefficients' => [$m],
                    'baseline_intercept' => $b,
                ];
            }

            return response()->json([
                'success' => true,
                'widget_type' => $effectiveWidgetType,
                'source_type' => $widget->source_type,
                'data' => $data,
                'controls' => $resolvedControls,
            ], 200, [], JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            report($e);
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }

    protected function handleKpiSource(Project $project, DashboardWidget $widget, array $controls): array
    {
        $kpi = $widget->customKpi;
        if (!$kpi) {
            throw new \RuntimeException('Widget has no associated KPI');
        }

        $controlsHash = $this->widgetDataService->computeControlsHash($controls);
        $cached = $this->widgetDataService->getCachedResult($kpi->id, $controlsHash);
        if ($cached) {
            return $cached->result;
        }

        $uiState = $kpi->filters['_ui_state'] ?? [];
        \Illuminate\Support\Facades\Log::info('UI State:', $uiState);
        $controlsToMerge = [];
        if (!empty($controls['channel'])) $controlsToMerge['dependent_channel'] = $controls['channel'];
        if (!empty($controls['assets'])) $controlsToMerge['dependent_asset_filter'] = $controls['assets'];
        
        if (isset($controls['series_assets']['dependent'])) {
            $controlsToMerge['dependent_asset_filter'] = $controls['series_assets']['dependent'];
            $controlsToMerge['dependent_asset_group'] = null;
        }
        if (!empty($controls['series_asset_groups']['dependent'])) {
            $controlsToMerge['dependent_asset_group'] = $controls['series_asset_groups']['dependent'];
            $controlsToMerge['dependent_asset_filter'] = null;
        }

        if (!empty($controls['date_start'])) $controlsToMerge['start_date'] = $controls['date_start'];
        if (!empty($controls['date_end'])) $controlsToMerge['end_date'] = $controls['date_end'];
        if (!empty($controls['granularity'])) {
            $fixedGranularity = $uiState['granularity'] ?? null;
            if (!$fixedGranularity || in_array($fixedGranularity, ['daily', 'weekly', 'monthly', 'quarterly', 'yearly'])) {
                $controlsToMerge['granularity'] = $controls['granularity'];
            }
        }

        if (isset($controls['edge_case_weighted'])) {
            $controlsToMerge['edge_case_weighted'] = filter_var($controls['edge_case_weighted'], FILTER_VALIDATE_BOOLEAN);
        }
        if (isset($controls['edge_case_grouping'])) {
            $controlsToMerge['edge_case_grouping'] = $controls['edge_case_grouping'];
        }

        if (isset($controls['max_ratio'])) {
            $controlsToMerge['max_ratio'] = $controls['max_ratio'] !== null ? (float) $controls['max_ratio'] : null;
        }

        // If independent variables are present and missing channels/assets, override them with controls
        if (isset($uiState['independent_variables']) && is_array($uiState['independent_variables'])) {
            foreach ($uiState['independent_variables'] as $key => $var) {
                if (empty($var['independent_channel']) && !empty($controls['channel'])) {
                    $uiState['independent_variables'][$key]['independent_channel'] = $controls['channel'];
                }
                if (empty($var['independent_asset_filter']) && !empty($controls['assets'])) {
                    $uiState['independent_variables'][$key]['independent_asset_filter'] = $controls['assets'];
                }
                if (isset($controls['series_assets']["independent_{$key}"])) {
                    $uiState['independent_variables'][$key]['independent_asset_filter'] = $controls['series_assets']["independent_{$key}"];
                    $uiState['independent_variables'][$key]['independent_asset_group'] = null;
                }
                if (!empty($controls['series_asset_groups']["independent_{$key}"])) {
                    $uiState['independent_variables'][$key]['independent_asset_group'] = $controls['series_asset_groups']["independent_{$key}"];
                    $uiState['independent_variables'][$key]['independent_asset_filter'] = null;
                }
            }
        }

        // Hydrate/override metrics from runtime controls (on-the-go / widget-level override)
        $runtimeMetrics = $controls['metrics'] ?? [];
        $metricIndex = 0;

        if (!empty($runtimeMetrics[$metricIndex])) {
            $uiState['dependent_metric'] = $runtimeMetrics[$metricIndex];
        }
        $metricIndex++;

        if (isset($uiState['independent_variables']) && is_array($uiState['independent_variables'])) {
            foreach ($uiState['independent_variables'] as $key => $var) {
                if (!empty($runtimeMetrics[$metricIndex])) {
                    $uiState['independent_variables'][$key]['independent_metric'] = $runtimeMetrics[$metricIndex];
                }
                $metricIndex++;
            }
        }

        // Auto-resolve missing independent variable metrics
        if (isset($uiState['independent_variables']) && is_array($uiState['independent_variables'])) {
            foreach ($uiState['independent_variables'] as $key => $var) {
                if (empty($var['independent_metric']) && !empty($var['independent_channel'])) {
                    $channelMetrics = \App\Services\Analytics\KpiFormBuilder::getMetricOptionsForChannel($var['independent_channel']);
                    if (!empty($channelMetrics)) {
                        $metricKeys = array_keys($channelMetrics);
                        $uiState['independent_variables'][$key]['independent_metric'] = $metricKeys[0];
                    }
                }
            }
        }

        $mergedState = array_merge($uiState, $controlsToMerge);

        if (empty($mergedState['dependent_metric'])) {
            $channelMetrics = !empty($uiState['dependent_channel'])
                ? \App\Services\Analytics\KpiFormBuilder::getMetricOptionsForChannel($uiState['dependent_channel'])
                : [];

            if (!empty($channelMetrics)) {
                $metricKeys = array_keys($channelMetrics);
                $mergedState['dependent_metric'] = $metricKeys[0];
            } else {
                throw new \RuntimeException("This KPI is incomplete. It requires a 'Dependent Metric', but none was configured, no runtime metric was provided, and no default metrics are available for the channel.");
            }
        }

        $payload = KpiPayloadBuilder::build(
            $kpi->calculation_type,
            $mergedState,
            [
                'start_date' => !empty($controls['date_start']) ? $controls['date_start'] : null,
                'end_date' => !empty($controls['date_end']) ? $controls['date_end'] : null,
                'granularity' => !empty($controls['granularity']) ? $controls['granularity'] : null,
                'zero_handling' => !empty($controls['zero_handling']) ? $controls['zero_handling'] : null,
            ]
        );

        $result = $this->remoteEngineService->computeKpi($project, $payload);

        if (!($result['success'] ?? false)) {
            throw new \RuntimeException($result['message'] ?? 'KPI computation failed');
        }

        $resultData = $result['data'] ?? [];

        $this->widgetDataService->cacheResult(
            $kpi->id, $project->id, $controlsHash, $resultData
        );

        return $resultData;
    }

    protected function handleMetricSource(Project $project, DashboardWidget $widget, array $controls): array
    {
        $config = $widget->source_config ?? [];
        $channel = $controls['channel'] ?? $config['channel'] ?? '';
        $metrics = $controls['metrics'] ?? $config['metrics'] ?? [];

        if (!$channel) {
            throw new \RuntimeException('No channel configured for metric widget');
        }

        $assetFilter = $controls['asset'] ?? $controls['assets'][0] ?? $controls['series_assets']['dependent'][0] ?? null;

        $dateStart = $controls['date_start'] ?? now()->subDays(30)->format('Y-m-d');
        $dateEnd = $controls['date_end'] ?? now()->format('Y-m-d');

        $payload = [
            'tenant' => $project->id,
            'account' => $assetFilter,
            'dateStart' => $dateStart,
            'dateEnd' => $dateEnd,
            'granularity' => $controls['granularity'] ?? 'daily',
            'metrics' => $metrics,
        ];

        return $this->forwardToChannelEndpoint($channel, 'summary', $payload);
    }

    protected function handleEntitySource(Project $project, DashboardWidget $widget, array $controls): array
    {
        $config = $widget->source_config ?? [];
        $channel = $controls['channel'] ?? $config['channel'] ?? '';
        $entityType = $controls['entity_type'] ?? $config['entity_type'] ?? 'campaigns';
        $limit = $config['limit'] ?? 50;

        if (!$channel) {
            throw new \RuntimeException('No channel configured for entity widget');
        }

        $assetFilter = $controls['asset'] ?? $controls['assets'][0] ?? $controls['series_assets']['dependent'][0] ?? null;

        $dateStart = $controls['date_start'] ?? now()->subDays(30)->format('Y-m-d');
        $dateEnd = $controls['date_end'] ?? now()->format('Y-m-d');

        $payload = [
            'tenant' => $project->id,
            'account' => $assetFilter,
            'dateStart' => $dateStart,
            'dateEnd' => $dateEnd,
            'activeTab' => $entityType,
            'limit' => $limit,
        ];

        return $this->forwardToChannelEndpoint($channel, 'table', $payload);
    }

    protected function forwardToChannelEndpoint(string $channel, string $action, array $payload): array
    {
        $endpoints = [
            'facebook_marketing' => \App\Http\Controllers\Api\FacebookMarketingController::class,
            'facebook_organic' => \App\Http\Controllers\Api\FacebookOrganicController::class,
            'google_search_console' => \App\Http\Controllers\Api\GoogleSearchConsoleController::class,
            'google_analytics' => \App\Http\Controllers\Api\GoogleAnalyticsController::class,
        ];

        $controllerClass = $endpoints[$channel] ?? null;
        if (!$controllerClass) {
            throw new \RuntimeException("Unknown channel: {$channel}");
        }

        $controller = app($controllerClass);

        if (!method_exists($controller, $action)) {
            throw new \RuntimeException("Channel {$channel} does not support action {$action}");
        }

        $request = new Request($payload);
        $request->setUserResolver(fn () => auth()->user());

        $response = $controller->$action($request);

        if ($response instanceof \Illuminate\Http\JsonResponse) {
            $data = $response->getData(true);
            return $data['data'] ?? $data;
        }

        if (is_array($response)) {
            return $response;
        }

        return json_decode(json_encode($response, JSON_UNESCAPED_UNICODE), true);
    }
}
