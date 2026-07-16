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

        \Illuminate\Support\Facades\Log::info('[STEP show] Widget ' . $widget->id . ' after resolveControls', [
            'widget_id' => $widget->id,
            'metrics' => $resolvedControls['metrics'] ?? '__NOT_SET__',
            'channel' => $resolvedControls['channel'] ?? '__NOT_SET__',
            'granularity' => $resolvedControls['granularity'] ?? '__NOT_SET__',
            'series_assets' => $resolvedControls['series_assets'] ?? '__NOT_SET__',
            'assets' => $resolvedControls['assets'] ?? '__NOT_SET__',
            'has_request_controls' => isset($validated['controls']),
        ]);

        // Merge runtime overrides from the request (on-the-go UX)
        if (isset($validated['controls'])) {
            if (array_key_exists('granularity', $validated['controls']) && empty($validated['controls']['granularity'])) {
                unset($validated['controls']['granularity']);
            }
            $resolvedControls = array_merge($resolvedControls, $validated['controls']);
        }

        \Illuminate\Support\Facades\Log::info('[STEP show] Widget ' . $widget->id . ' after merge with request controls', [
            'widget_id' => $widget->id,
            'metrics' => $resolvedControls['metrics'] ?? '__NOT_SET__',
            'request_metrics' => $validated['controls']['metrics'] ?? '__NOT_SET__',
            'channel' => $resolvedControls['channel'] ?? '__NOT_SET__',
            'granularity' => $resolvedControls['granularity'] ?? '__NOT_SET__',
            'series_assets' => $resolvedControls['series_assets'] ?? '__NOT_SET__',
        ]);

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

                \Illuminate\Support\Facades\Log::info('[STEP show] Post-process controls metrics check', [
                    'widget_id' => $widget->id,
                    'runtimeMetrics_entry' => $runtimeMetrics,
                    'kpi_dependent_metric' => $kpiUiState['dependent_metric'] ?? '__NONE__',
                    'kpi_independent_vars' => collect($kpiUiState['independent_variables'] ?? [])->map(fn($v) => ['metric' => $v['independent_metric'] ?? '__EMPTY__', 'channel' => $v['independent_channel'] ?? '__EMPTY__'])->values()->toArray(),
                    'need_dependent_fallback' => empty($runtimeMetrics[0]) && !empty($kpiUiState['dependent_metric']),
                    'need_independent_fallback' => empty($runtimeMetrics[1]) && isset($kpiUiState['independent_variables']),
                ]);

                if (empty($runtimeMetrics[0]) && !empty($kpiUiState['dependent_metric'])) {
                    $resolvedControls['metrics'][0] = $kpiUiState['dependent_metric'];
                }
                if (empty($runtimeMetrics[1]) && isset($kpiUiState['independent_variables'])) {
                    $firstIvar = reset($kpiUiState['independent_variables']);
                    if (!empty($firstIvar['independent_metric'])) {
                        $resolvedControls['metrics'][1] = $firstIvar['independent_metric'];
                    }
                }

                \Illuminate\Support\Facades\Log::info('[STEP show] Post-process controls metrics after fallback', [
                    'widget_id' => $widget->id,
                    'runtimeMetrics_original' => $runtimeMetrics,
                    'resolvedControls_metrics' => $resolvedControls['metrics'] ?? [],
                ]);
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

                \Illuminate\Support\Facades\Log::info('Scatter data from Python', [
                    'n' => $n,
                    'x_sample' => array_slice($rawX, 0, 5),
                    'y_sample' => array_slice($rawY, 0, 5),
                    'labels' => isset($scatter['labels']) ? array_slice($scatter['labels'], 0, 10) : null,
                    'all_labels' => $scatter['labels'] ?? null,
                ]);

                $maxRatio = $resolvedControls['max_ratio'] ?? null;
                $modelType = $data['model_type'] ?? 'linear';

                // Remove data points where position > 30 (poor-ranking noise)
                // Applied before regression so the trend line also excludes them.
                // The histogram cluster centroid (label === "others") is kept
                // regardless so [[[others]]] stays visible on the chart.
                // Also filter labels to keep indices aligned with filtered x/y arrays.
                if ($resolvedControls['metrics'][0] === 'position') {
                    $filteredX = []; $filteredY = []; $filteredLabels = [];
                    foreach ($rawX as $i => $x) {
                        $isCluster = isset($scatter['labels'][$i]) && $scatter['labels'][$i] === 'others';
                        if ($isCluster || $rawY[$i] <= 30) {
                            $filteredX[] = $x; $filteredY[] = $rawY[$i];
                            if (isset($scatter['labels'][$i])) $filteredLabels[] = $scatter['labels'][$i];
                        }
                    }
                    $rawX = $filteredX; $rawY = $filteredY; $n = count($rawX);
                    if (!empty($filteredLabels)) $scatter['labels'] = $filteredLabels;
                } elseif ($resolvedControls['metrics'][1] === 'position') {
                    $filteredX = []; $filteredY = []; $filteredLabels = [];
                    foreach ($rawX as $i => $x) {
                        $isCluster = isset($scatter['labels'][$i]) && $scatter['labels'][$i] === 'others';
                        if ($isCluster || $x <= 30) {
                            $filteredX[] = $x; $filteredY[] = $rawY[$i];
                            if (isset($scatter['labels'][$i])) $filteredLabels[] = $scatter['labels'][$i];
                        }
                    }
                    $rawX = $filteredX; $rawY = $filteredY; $n = count($rawX);
                    if (!empty($filteredLabels)) $scatter['labels'] = $filteredLabels;
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

                \Illuminate\Support\Facades\Log::info('PHP regression result', [
                    'n' => $n,
                    'slope_m' => $m,
                    'intercept_b' => $b,
                    'r_squared' => $rSquared,
                    'minX' => min($rawX),
                    'maxX' => max($rawX),
                    'sumX' => $sumX ?? null,
                    'sumY' => $sumY ?? null,
                ]);

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

                \Illuminate\Support\Facades\Log::info('Volume filter config', [
                    'xMetric' => $xMetric,
                    'isVolumeMetric' => $isVolumeMetric,
                    'hardFloor' => $hardFloor,
                    'xThreshold' => $xThreshold,
                    'totalN' => $totalN,
                    'maxRatio' => $maxRatio,
                ]);

                // Identify the histogram cluster point (labeled "others" by Python's
                // _histogram_elbow_grouping) and relabel to [[[others]]] for the frontend.
                // Python handles the grouping logic; we just need to detect which point
                // is the centroid so the frontend can split it into a toggleable dataset.
                $clusterIndex = null;
                $isHistogram = ($resolvedControls['edge_case_grouping'] ?? null) === 'histogram';
                if ($isHistogram && isset($scatter['labels'])) {
                    foreach ($scatter['labels'] as $i => $label) {
                        if ($label === 'others') {
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

                // Top percentile cap for chart readability — keep only the top N%
                // of points by x value (highest impressions, best positions).
                $displayPercentile = (float) ($resolvedControls['display_percentile'] ?? 0.15);
                $pointsBeforeCap = count($points);
                if ($displayPercentile < 1.0 && $pointsBeforeCap > 10) {
                    $nKeep = max(2, (int) ceil($pointsBeforeCap * $displayPercentile));
                    $isPosition = ($resolvedControls['metrics'][1] ?? '') === 'position';

                    $alwaysKeep = [];
                    $filterable = [];
                    $removeUnknown = !empty($resolvedControls['remove_unknown']);
                    foreach ($points as $p) {
                        $isUnknown = isset($p['label']) && $p['label'] === 'unknown';
                        if ($removeUnknown && $isUnknown) {
                            continue;
                        }
                        if (!empty($p['_isCluster']) || $isUnknown) {
                            $alwaysKeep[] = $p;
                        } else {
                            $filterable[] = $p;
                        }
                    }

                    if (count($filterable) > $nKeep) {
                        if ($isPosition) {
                            usort($filterable, fn($a, $b) => $a['x'] <=> $b['x']);
                        } else {
                            usort($filterable, fn($a, $b) => $b['x'] <=> $a['x']);
                        }
                        $filterable = array_slice($filterable, 0, $nKeep);
                    }

                    $points = array_merge($alwaysKeep, $filterable);
                    usort($points, fn($a, $b) => $a['x'] <=> $b['x']);

                    \Illuminate\Support\Facades\Log::info('Scatter percentile cap applied', [
                        'percentile' => $displayPercentile,
                        'n_before' => $pointsBeforeCap,
                        'n_after' => count($points),
                        'always_kept' => count($alwaysKeep),
                    ]);
                }

                \Illuminate\Support\Facades\Log::info('Scatter points after filtering', [
                    'total_points' => count($points),
                    'cluster_index' => $clusterIndex,
                    'points_sample' => array_map(fn($p) => [
                        'label' => $p['label'] ?? 'no-label',
                        'x' => $p['x'],
                        'y' => $p['y'],
                        'isCluster' => !empty($p['_isCluster']),
                    ], array_slice($points, 0, 10)),
                    'all_labels' => array_map(fn($p) => $p['label'] ?? 'no-label', $points),
                ]);

                if (!empty($points)) {
                    $maxXPoint = $points[array_search(max(array_column($points, 'x')), array_column($points, 'x'))];
                    \Illuminate\Support\Facades\Log::info('Max X point after filtering', [
                        'label' => $maxXPoint['label'] ?? 'no-label',
                        'x' => $maxXPoint['x'],
                        'y' => $maxXPoint['y'],
                        'isCluster' => !empty($maxXPoint['_isCluster']),
                    ]);
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
                    'x_label' => null,
                    'y_label' => null,
                    'r_squared' => $rSquared,
                    'coefficients' => [$m],
                    'baseline_intercept' => $b,
                ];
            } elseif ($effectiveWidgetType === 'table' && isset($data['scatter_data'])) {
                $scatter = $data['scatter_data'];
                $x = $scatter['x'] ?? [];
                $y = $scatter['y'] ?? [];
                $labels = $scatter['labels'] ?? [];
                $n = count($x);

                $rows = [];
                for ($i = 0; $i < $n; $i++) {
                    $rows[] = [
                        'label' => $labels[$i] ?? "#{$i}",
                        'x' => $x[$i] ?? 0,
                        'y' => $y[$i] ?? 0,
                    ];
                }

                $data = [
                    'columns' => [
                        ['key' => 'label', 'label' => 'Name'],
                        ['key' => 'x', 'label' => $scatter['x_label'] ?? 'X', 'format' => 'number'],
                        ['key' => 'y', 'label' => $scatter['y_label'] ?? 'Value', 'format' => 'number'],
                    ],
                    'rows' => $rows,
                    'total' => $n,
                ];
            } elseif ($effectiveWidgetType === 'table' && isset($data['summary']) && is_array($data['summary'])) {
                $summary = $data['summary'];
                $previous = $data['previous'] ?? [];
                $metrics = $resolvedControls['metrics'] ?? array_keys($summary);

                $metricLabels = \App\Services\Analytics\KpiFormBuilder::getAllMetricOptions();
                $ratioMetrics = ['ctr', 'bounce_rate', 'result_rate'];

                $rows = [];
                foreach ($metrics as $metric) {
                    if (!isset($summary[$metric])) {
                        continue;
                    }
                    $current = is_numeric($summary[$metric]) ? (float) $summary[$metric] : $summary[$metric];
                    $prev = isset($previous[$metric]) && is_numeric($previous[$metric]) ? (float) $previous[$metric] : null;

                    $change = null;
                    if ($prev !== null && $prev != 0) {
                        $change = round((($current - $prev) / abs($prev)) * 100, 2);
                    }

                    $isRatio = in_array($metric, $ratioMetrics);
                    if ($isRatio) {
                        $current = is_numeric($current) ? round((float) $current * 100, 4) : $current;
                        $prev = ($prev !== null && is_numeric($prev)) ? round((float) $prev * 100, 4) : $prev;
                    }

                    $rows[] = [
                        'metric' => $metricLabels[$metric] ?? ucfirst($metric),
                        'current' => $current,
                        'previous' => $prev,
                        'change' => $change,
                        'current_format' => $isRatio ? 'percent' : 'number',
                        'previous_format' => $isRatio ? 'percent' : 'number',
                    ];
                }

                $data = [
                    'columns' => [
                        ['key' => 'metric', 'label' => 'Metric'],
                        ['key' => 'current', 'label' => 'Current Period', 'format' => 'number'],
                        ['key' => 'previous', 'label' => 'Previous Period', 'format' => 'number'],
                        ['key' => 'change', 'label' => 'Change', 'format' => 'percent'],
                    ],
                    'rows' => $rows,
                ];
            } elseif ($effectiveWidgetType === 'table' && isset($data['chart']) && is_array($data['chart']) && !empty($data['chart'])) {
                $chartData = $data['chart'];
                $metricLabels = \App\Services\Analytics\KpiFormBuilder::getAllMetricOptions();
                $ratioMetrics = ['ctr', 'bounce_rate', 'result_rate'];

                $dateKey = isset($chartData[0]['daily']) ? 'daily' : 'date';

                $columns = [['key' => 'date', 'label' => 'Date']];
                foreach ($chartData[0] as $key => $val) {
                    if ($key === $dateKey) continue;
                    $cleanKey = preg_replace('/^trend_(?:total|average)_/', '', $key);
                    $isRatio = in_array($cleanKey, $ratioMetrics);
                    $columns[] = [
                        'key' => $key,
                        'label' => $metricLabels[$cleanKey] ?? ucfirst($cleanKey),
                        'format' => $isRatio ? 'percent' : 'number',
                    ];
                }

                $rows = [];
                foreach ($chartData as $row) {
                    $row['date'] = $row[$dateKey] ?? '';
                    unset($row[$dateKey]);
                    foreach ($row as $k => $v) {
                        if ($k === 'date') continue;
                        $ck = preg_replace('/^trend_(?:total|average)_/', '', $k);
                        if (in_array($ck, $ratioMetrics) && is_numeric($v)) {
                            $row[$k] = round((float) $v * 100, 4);
                        }
                    }
                    $rows[] = $row;
                }

                $data = [
                    'columns' => $columns,
                    'rows' => $rows,
                ];
            } elseif ($effectiveWidgetType === 'sparkline' && isset($data['trend'])) {
                $data['values'] = array_column($data['trend'], 'value');
            } elseif (in_array($effectiveWidgetType, ['line_chart', 'bar_chart', 'combo_chart']) && isset($data['trend'])) {
                $trend = $data['trend'];
                $slope = $data['slope'] ?? null;
                $intercept = $data['intercept'] ?? null;
                $labels = [];
                $values = [];
                foreach ($trend as $point) {
                    $labels[] = $point['date'] ?? $point['label'] ?? '';
                    $values[] = $point['value'] ?? $point['y'] ?? 0;
                }
                $data = [
                    'labels' => $labels,
                    'datasets' => [
                        [
                            'label' => $widget->name ?: 'Metric',
                            'data' => $values,
                            'borderColor' => '#3b82f6',
                            'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                            'fill' => true,
                            'tension' => 0.3,
                        ],
                    ],
                ];
                if ($slope !== null) {
                    $data['coefficients'] = [$slope];
                }
                if ($intercept !== null) {
                    $data['baseline_intercept'] = $intercept;
                }
            }

            // If tile or gauge and data is still in series format, aggregate to a single value
            if (in_array($effectiveWidgetType, ['tile', 'gauge']) && !isset($data['value'])) {
                if (isset($data['series']['values'])) {
                    $primaryValues = $data['series']['values'];
                    if (is_array($primaryValues) && count($primaryValues) > 0) {
                        $avgValue = (float) (array_sum($primaryValues) / count($primaryValues));
                        $data['value'] = $avgValue;
                        $data['current'] = $avgValue;
                        $data['previous'] = null;
                    }
                }
            }

            \Illuminate\Support\Facades\Log::info('[STEP show] Response data', [
                'widget_id' => $widget->id,
                'widget_type' => $effectiveWidgetType,
                'source_type' => $widget->source_type,
                'has_scatter_data' => isset($data['scatter_data']) || isset($data['datasets']),
                'data_keys' => is_array($data) ? array_keys($data) : gettype($data),
                'controls_metrics' => $resolvedControls['metrics'] ?? [],
            ]);

            return response()->json([
                'success' => true,
                'widget_type' => $effectiveWidgetType,
                'source_type' => $widget->source_type,
                'data' => $data,
                'controls' => $resolvedControls,
            ], 200, [], JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('[STEP show] Exception', [
                'widget_id' => $widget->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
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

        \Illuminate\Support\Facades\Log::info('[STEP handleKpiSource] Enter', [
            'widget_id' => $widget->id,
            'kpi_id' => $kpi->id,
            'calculation_type' => $kpi->calculation_type,
            'controls_metrics' => $controls['metrics'] ?? [],
            'controls_channel' => $controls['channel'] ?? '__NONE__',
            'controls_granularity' => $controls['granularity'] ?? '__NONE__',
            'controls_series_assets' => $controls['series_assets'] ?? [],
            'controls_date_start' => $controls['date_start'] ?? null,
            'controls_date_end' => $controls['date_end'] ?? null,
        ]);

        $controlsHash = $this->widgetDataService->computeControlsHash($controls);
        $cached = $this->widgetDataService->getCachedResult($kpi->id, $controlsHash);

        \Illuminate\Support\Facades\Log::info('[STEP handleKpiSource] Cache check', [
            'widget_id' => $widget->id,
            'kpi_id' => $kpi->id,
            'controls_hash' => $controlsHash,
            'cache_hit' => $cached ? true : false,
        ]);

        if ($cached) {
            $cachedDataKeys = !empty($cached->result['data']) ? array_keys($cached->result['data']) : [];
            \Illuminate\Support\Facades\Log::info('[STEP handleKpiSource] Cache HIT, returning cached', [
                'widget_id' => $widget->id,
                'kpi_id' => $kpi->id,
                'controls_hash' => $controlsHash,
                'data_keys' => $cachedDataKeys,
                'has_scatter' => isset($cached->result['data']['scatter_data']),
                '_debug' => $cached->result['data']['_debug'] ?? null,
            ]);
            return $cached->result;
        }

        $uiState = $kpi->filters['_ui_state'] ?? [];
        \Illuminate\Support\Facades\Log::info('[STEP handleKpiSource] Raw _ui_state from KPI DB', [
            'widget_id' => $widget->id,
            'kpi_id' => $kpi->id,
            'dependent_metric' => $uiState['dependent_metric'] ?? '__EMPTY__',
            'independent_variables' => collect($uiState['independent_variables'] ?? [])->map(fn($v) => [
                'independent_metric' => $v['independent_metric'] ?? '__EMPTY__',
                'independent_channel' => $v['independent_channel'] ?? '__EMPTY__',
                'independent_asset_filter' => $v['independent_asset_filter'] ?? '__EMPTY__',
            ])->values()->toArray(),
            'granularity' => $uiState['granularity'] ?? '__EMPTY__',
        ]);
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

        if (isset($controls['remove_unknown'])) {
            $controlsToMerge['remove_unknown'] = filter_var($controls['remove_unknown'], FILTER_VALIDATE_BOOLEAN);
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

        \Illuminate\Support\Facades\Log::info('[STEP handleKpiSource] Runtime metric override', [
            'widget_id' => $widget->id,
            'runtimeMetrics_raw' => $runtimeMetrics,
            'dependent_before' => $uiState['dependent_metric'] ?? '__EMPTY__',
            'independent_vars_before' => collect($uiState['independent_variables'] ?? [])->map(fn($v) => [
                'metric' => $v['independent_metric'] ?? '__EMPTY__',
                'channel' => $v['independent_channel'] ?? '__EMPTY__',
            ])->values()->toArray(),
        ]);

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

        \Illuminate\Support\Facades\Log::info('[STEP handleKpiSource] After runtime metric override', [
            'widget_id' => $widget->id,
            'dependent_after' => $uiState['dependent_metric'] ?? '__EMPTY__',
            'independent_vars_after' => collect($uiState['independent_variables'] ?? [])->map(fn($v) => [
                'metric' => $v['independent_metric'] ?? '__EMPTY__',
                'channel' => $v['independent_channel'] ?? '__EMPTY__',
            ])->values()->toArray(),
        ]);

        // Auto-resolve missing independent variable metrics
        if (isset($uiState['independent_variables']) && is_array($uiState['independent_variables'])) {
            foreach ($uiState['independent_variables'] as $key => $var) {
                if (empty($var['independent_metric']) && !empty($var['independent_channel'])) {
                    $channelMetrics = \App\Services\Analytics\KpiFormBuilder::getMetricOptionsForChannel($var['independent_channel']);
                    \Illuminate\Support\Facades\Log::info('[STEP handleKpiSource] Auto-resolving missing metric', [
                        'widget_id' => $widget->id,
                        'var_key' => $key,
                        'channel' => $var['independent_channel'],
                        'available_metrics' => $channelMetrics ? array_keys($channelMetrics) : [],
                    ]);
                    if (!empty($channelMetrics)) {
                        $metricKeys = array_keys($channelMetrics);
                        $uiState['independent_variables'][$key]['independent_metric'] = $metricKeys[0];
                    }
                } else {
                    \Illuminate\Support\Facades\Log::info('[STEP handleKpiSource] Skipping auto-resolve', [
                        'widget_id' => $widget->id,
                        'var_key' => $key,
                        'metric_already_set' => !empty($var['independent_metric']),
                        'has_channel' => !empty($var['independent_channel']),
                        'current_metric' => $var['independent_metric'] ?? '__EMPTY__',
                        'current_channel' => $var['independent_channel'] ?? '__EMPTY__',
                    ]);
                }
            }
        }

        \Illuminate\Support\Facades\Log::info('[STEP handleKpiSource] After auto-resolve', [
            'widget_id' => $widget->id,
            'dependent_metric' => $uiState['dependent_metric'] ?? '__EMPTY__',
            'independent_vars' => collect($uiState['independent_variables'] ?? [])->map(fn($v) => [
                'metric' => $v['independent_metric'] ?? '__EMPTY__',
                'channel' => $v['independent_channel'] ?? '__EMPTY__',
                'asset_filter' => $v['independent_asset_filter'] ?? '__EMPTY__',
            ])->values()->toArray(),
        ]);

        $mergedState = array_merge($uiState, $controlsToMerge);

        \Illuminate\Support\Facades\Log::info('Merged state after auto-resolution (enriched)', [
            'dependent_metric' => $mergedState['dependent_metric'] ?? null,
            'dependent_channel' => $mergedState['dependent_channel'] ?? null,
            'independent_vars' => collect($mergedState['independent_variables'] ?? [])->map(fn($v) => [
                'channel' => $v['independent_channel'] ?? null,
                'metric' => $v['independent_metric'] ?? null,
                'group' => $v['independent_asset_group'] ?? null,
                'asset_filter' => $v['independent_asset_filter'] ?? null,
            ])->values()->toArray(),
            'granularity' => $mergedState['granularity'] ?? null,
            'zero_handling' => $mergedState['zero_handling'] ?? null,
            'grouping' => $mergedState['edge_case_grouping'] ?? null,
            'weighted' => $mergedState['edge_case_weighted'] ?? null,
            'series_assets' => $controls['series_assets'] ?? [],
            'request_metrics' => $controls['metrics'] ?? [],
            'widget_id' => $widget->id,
        ]);

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

        \Illuminate\Support\Facades\Log::info('KPI payload built', [
            'calculation_type' => $kpi->calculation_type,
            'ast' => $payload['ast'] ?? null,
            'filters' => $payload['filters'] ?? null,
            'zero_handling' => $payload['zero_handling'] ?? null,
            'edge_case_handling' => $payload['edge_case_handling'] ?? null,
            'max_ratio' => $payload['max_ratio'] ?? null,
        ]);

        \Illuminate\Support\Facades\Log::info('[STEP handleKpiSource] About to call engine', [
            'widget_id' => $widget->id,
            'kpi_id' => $kpi->id,
            'calculation_type' => $kpi->calculation_type,
            'payload_ast' => $payload['ast'] ?? null,
            'payload_filters' => $payload['filters'] ?? null,
            'payload_calc' => $payload['calculate_regression'] ?? $payload['calculate_elasticity'] ?? null,
        ]);

        $result = $this->remoteEngineService->computeKpi($project, $payload);

        \Illuminate\Support\Facades\Log::info('Raw result from remote node', [
            'has_data' => isset($result['data']),
            'scatter_data_keys' => isset($result['data']['scatter_data']) ? array_keys($result['data']['scatter_data']) : null,
            'scatter_n' => isset($result['data']['scatter_data']['x']) ? count($result['data']['scatter_data']['x']) : null,
            'scatter_max_x' => isset($result['data']['scatter_data']['x']) ? max($result['data']['scatter_data']['x']) : null,
            'scatter_max_x_label' => isset($result['data']['scatter_data']['x']) && isset($result['data']['scatter_data']['labels'])
                ? ($result['data']['scatter_data']['labels'][array_search(max($result['data']['scatter_data']['x']), $result['data']['scatter_data']['x'])] ?? 'unknown')
                : null,
            'granularity' => $controls['granularity'] ?? null,
            'widget_id' => $widget->id,
            'controls_metrics' => $controls['metrics'] ?? [],
            'controls_hash' => $controlsHash,
            '_debug' => $result['data']['_debug'] ?? null,
        ]);

        // Trace all occurrences of "unknown" in the scatter data (value could be duplicated)
        if (isset($result['data']['scatter_data']['labels']) && isset($result['data']['scatter_data']['x'])) {
            $unknownIndices = [];
            foreach ($result['data']['scatter_data']['labels'] as $idx => $label) {
                if (strtolower($label) === 'unknown') {
                    $unknownIndices[] = $idx;
                }
            }
            if (!empty($unknownIndices)) {
                $unknownData = [];
                foreach ($unknownIndices as $idx) {
                    $unknownData[] = [
                        'index' => $idx,
                        'x' => $result['data']['scatter_data']['x'][$idx] ?? null,
                        'y' => $result['data']['scatter_data']['y'][$idx] ?? null,
                    ];
                }
                \Illuminate\Support\Facades\Log::info('unknown_trace: entries found', [
                    'count' => count($unknownIndices),
                    'entries' => $unknownData,
                    'total_x_sum' => array_sum(array_column($unknownData, 'x')),
                ]);
            } else {
                \Illuminate\Support\Facades\Log::info('unknown_trace: no entries found in scatter_data');
            }
        }

        if (!($result['success'] ?? false)) {
            throw new \RuntimeException($result['message'] ?? 'KPI computation failed');
        }

        $resultData = $result['data'] ?? [];

        \Illuminate\Support\Facades\Log::info('[STEP handleKpiSource] Caching result', [
            'widget_id' => $widget->id,
            'kpi_id' => $kpi->id,
            'controls_hash' => $controlsHash,
            'has_scatter' => isset($resultData['scatter_data']),
            'data_keys' => array_keys($resultData),
            '_debug' => $resultData['_debug'] ?? null,
        ]);

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

        $assetFilter = $this->extractAssetFilter($controls);
        $assetFilter = $this->resolveChanneledAccountId($project, $channel, $assetFilter);

        $dateStart = $controls['date_start'] ?? now()->subDays(30)->format('Y-m-d');
        $dateEnd = $controls['date_end'] ?? now()->format('Y-m-d');

        $granularity = $controls['granularity'] ?? 'daily';

        $payload = [
            'tenant' => $project->id,
            'account' => $assetFilter,
            'dateStart' => $dateStart,
            'dateEnd' => $dateEnd,
            'granularity' => $granularity,
            'metrics' => $metrics,
        ];

        $action = in_array($granularity, ['daily', 'weekly', 'monthly']) ? 'chart' : 'summary';

        return $this->forwardToChannelEndpoint($channel, $action, $payload);
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

        $assetFilter = $this->extractAssetFilter($controls);
        $assetFilter = $this->resolveChanneledAccountId($project, $channel, $assetFilter);

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

    protected function extractAssetFilter(array $controls): ?string
    {
        if (!empty($controls['asset'])) {
            return $controls['asset'];
        }

        if (!empty($controls['assets'][0])) {
            return $controls['assets'][0];
        }

        $seriesAssets = $controls['series_assets'] ?? null;
        if (is_array($seriesAssets)) {
            if (!empty($seriesAssets['dependent'][0])) {
                return $seriesAssets['dependent'][0];
            }
            if (!empty($seriesAssets[0])) {
                return is_array($seriesAssets[0])
                    ? ($seriesAssets[0][0] ?? null)
                    : $seriesAssets[0];
            }
        }

        return null;
    }

    protected function resolveChanneledAccountId(Project $project, string $channel, ?string $asset): ?string
    {
        if ($asset === null || is_numeric($asset)) {
            return $asset;
        }

        if ($channel !== 'google_search_console') {
            return $asset;
        }

        $config = $project->sync_config['google_search_console']['assets']['sites']
            ?? $project->sync_config['google_search_console']['sites']
            ?? [];

        $siteUrl = null;
        foreach ($config as $site) {
            $candidate = $site['url'] ?? $site['id'] ?? null;
            if ($candidate === $asset) {
                $siteUrl = $candidate;
                break;
            }
        }

        if (!$siteUrl) {
            return $asset;
        }

        $hash = md5($siteUrl);

        try {
            $service = app(\App\Services\RemoteEngineService::class);
            $response = $service->listChanneled($project, 'google_search_console', 'channeled_account', ['limit' => 1000, 'enabled' => 1]);

            if (isset($response['data']) && is_array($response['data'])) {
                foreach ($response['data'] as $page) {
                    $platformId = rtrim((string) ($page['platformId'] ?? $page['platform_id'] ?? ''), '/');
                    if ($platformId === $hash) {
                        return (string) $page['id'];
                    }
                }
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('[resolveChanneledAccountId] Failed to resolve', [
                'channel' => $channel,
                'asset' => $asset,
                'error' => $e->getMessage(),
            ]);
        }

        return $asset;
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
