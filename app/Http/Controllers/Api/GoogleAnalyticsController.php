<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Services\RemoteEngineService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class GoogleAnalyticsController extends Controller
{
    private function validateRequest(Request $request): array
    {
        return $request->validate([
            'tenant' => 'required|string',
            'account' => 'required|string',
            'dateStart' => 'required|date',
            'dateEnd' => 'required|date',
            'activeTab' => 'nullable|string|in:campaigns,adgroups,channels,sources,traffic_pages,traffic_countries,traffic_devices,acquisition_channels,events,adtouchpoints_adgroups,adtouchpoints_terms,adtouchpoints_content',
            'activeFilters' => 'nullable|array',
            'activeFilters.*' => 'nullable|array',
        ]);
    }

    private function scopeForTab(string $tab): ?string
    {
        return match ($tab) {
            'acquisition_channels' => 'acquisition_matrix',
            'events' => 'event_matrix',
            'adtouchpoints_adgroups', 'adtouchpoints_terms', 'adtouchpoints_content' => 'ad_touchpoint_matrix',
            default => 'traffic_matrix',
        };
    }

    private function metricsForScope(string $scope, bool $includeBounceRate = false): array
    {
        $base = match ($scope) {
            'traffic_matrix' => ['sessions', 'screenPageViews', 'conversions', 'averageSessionDuration'],
            'acquisition_matrix' => ['newUsers', 'activeUsers', 'totalUsers'],
            'event_matrix' => ['eventCount', 'conversions'],
            'ad_touchpoint_matrix' => ['sessions', 'conversions'],
            default => ['sessions', 'activeUsers'],
        };
        if ($scope === 'traffic_matrix' && $includeBounceRate) {
            $base[] = 'bounceRate';
        }
        return $base;
    }

    private function buildAggregations(array $metrics): array
    {
        $agg = [];
        foreach ($metrics as $m) {
            $agg[$m] = $m;
        }
        return $agg;
    }

    /**
     * Tabs that merge traffic_matrix + acquisition_matrix with entity groupBy.
     */
    private function isDualScopeTab(string $tab): bool
    {
        return in_array($tab, ['campaigns', 'adgroups', 'channels', 'sources']);
    }

    /**
     * For dual-scope tabs, returns [trafficQuery, acquisitionQuery].
     */
    private function dualScopeQueries(string $tab): array
    {
        $trafficMetrics = $this->metricsForScope('traffic_matrix');
        $acqMetrics = $this->metricsForScope('acquisition_matrix');

        switch ($tab) {
            case 'campaigns':
                return [
                    ['scope' => 'traffic_matrix', 'metrics' => $trafficMetrics, 'groupBy' => ['channeledCampaign']],
                    ['scope' => 'acquisition_matrix', 'metrics' => $acqMetrics, 'groupBy' => ['channeledCampaign']],
                ];
            case 'adgroups':
                return [
                    ['scope' => 'traffic_matrix', 'metrics' => $trafficMetrics, 'groupBy' => ['channeledAdGroup']],
                    ['scope' => 'acquisition_matrix', 'metrics' => $acqMetrics, 'groupBy' => ['channeledAdGroup']],
                ];
            case 'channels':
                return [
                    ['scope' => 'traffic_matrix', 'metrics' => $trafficMetrics, 'groupBy' => ['dimensions.sessionDefaultChannelGroup']],
                    ['scope' => 'acquisition_matrix', 'metrics' => $acqMetrics, 'groupBy' => ['dimensions.firstUserDefaultChannelGroup']],
                ];
            case 'sources':
                return [
                    ['scope' => 'traffic_matrix', 'metrics' => $trafficMetrics, 'groupBy' => ['dimensions.sessionSourceMedium']],
                    ['scope' => 'acquisition_matrix', 'metrics' => $acqMetrics, 'groupBy' => ['dimensions.firstUserSourceMedium']],
                ];
            default:
                return [];
        }
    }

    private function singleScopeQuery(string $tab): array
    {
        $scope = $this->scopeForTab($tab);
        $includeBounceRate = in_array($tab, ['traffic_pages', 'traffic_countries', 'traffic_devices']);
        $metrics = $this->metricsForScope($scope, $includeBounceRate);

        $groupBy = match ($tab) {
            'traffic_pages' => ['dimensions.landing_page'],
            'traffic_countries' => ['country'],
            'traffic_devices' => ['device'],
            'acquisition_channels' => ['dimensions.firstUserDefaultChannelGroup'],
            'events' => ['event'],
            'adtouchpoints_adgroups', 'adtouchpoints_terms' => ['channeledAdGroup'],
            'adtouchpoints_content' => ['channeledAd'],
            default => [],
        };

        return ['scope' => $scope, 'metrics' => $metrics, 'groupBy' => $groupBy];
    }

    private function mergeMatrixResults(array $results, array $groupByKeys): array
    {
        $map = [];

        foreach ($results as $idx => $rows) {
            $dimKey = $groupByKeys[$idx] ?? null;
            if (!$dimKey || !$rows) continue;

            $lookupFull = strtolower($dimKey);
            $lookupStripped = strtolower(str_replace('dimensions.', '', $dimKey));

            foreach ($rows as $row) {
                $rowLower = array_change_key_case($row, CASE_LOWER);
                $dimValue = $rowLower[$lookupFull] ?? $rowLower[$lookupStripped] ?? null;
                if (!$dimValue || $dimValue === 'null' || $dimValue === '(not set)') {
                    $dimValue = 'Unknown';
                }

                $key = strtolower((string) $dimValue);
                if (!isset($map[$key])) {
                    $map[$key] = ['_dimKey' => $dimKey, '_dimValue' => $dimValue];
                }

                $existing = $map[$key];
                foreach ($row as $k => $v) {
                    $kLower = strtolower($k);
                    if ($kLower === $lookupFull || $kLower === $lookupStripped) continue;
                    $num = is_numeric($v) ? (float) $v : $v;
                    if (is_float($num)) {
                        $existing[$k] = ($existing[$k] ?? 0) + $num;
                    } elseif (!isset($existing[$k])) {
                        $existing[$k] = $v;
                    }
                }
                $map[$key] = $existing;
            }
        }

        return array_values(array_map(function ($item) {
            $item['id'] = $item['_dimValue'];
            $item['name'] = $item['_dimValue'];
            unset($item['_dimKey'], $item['_dimValue']);
            return $item;
        }, $map));
    }

    public function listProperties(Request $request)
    {
        try {
            $validated = $request->validate([
                'tenant' => 'required|string',
            ]);

            $tenant = Project::findOrFail($validated['tenant']);
            $service = app(RemoteEngineService::class);
            $response = $service->listChanneled($tenant, 'google_analytics', 'channeled_account', ['limit' => 1000, 'enabled' => 1]);

            $properties = [];
            if (isset($response['data']) && is_array($response['data'])) {
                foreach ($response['data'] as $prop) {
                    $properties[] = [
                        'id' => $prop['id'],
                        'name' => $prop['name'] ?? $prop['platformId'] ?? $prop['platform_id'] ?? $prop['id'],
                        'platformId' => $prop['platformId'] ?? $prop['platform_id'] ?? null,
                    ];
                }
            }

            return response()->json(['properties' => $properties]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("GA4 List Properties Error: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function summary(Request $request)
    {
        try {
            $validated = $this->validateRequest($request);
            $tenant = Project::findOrFail($validated['tenant']);
            $service = app(RemoteEngineService::class);

            $start = Carbon::parse($validated['dateStart']);
            $end = Carbon::parse($validated['dateEnd']);
            $diff = $start->diffInDays($end) + 1;
            $prevEnd = $start->copy()->subDay();
            $prevStart = $prevEnd->copy()->subDays($diff - 1);

            $baseFilters = ['channeledAccount' => (string) $validated['account'], 'channel' => 'google_analytics'];

            $trafficMetrics = $this->metricsForScope('traffic_matrix');
            $acqMetrics = $this->metricsForScope('acquisition_matrix');

            $payloads = [
                'summary_traffic' => [
                    'aggregations' => $this->buildAggregations($trafficMetrics),
                    'groupBy' => [],
                    'filters' => array_merge($baseFilters, ['dimensions.scope' => 'traffic_matrix']),
                    'startDate' => $validated['dateStart'],
                    'endDate' => $validated['dateEnd'],
                ],
                'summary_acquisition' => [
                    'aggregations' => $this->buildAggregations($acqMetrics),
                    'groupBy' => [],
                    'filters' => array_merge($baseFilters, ['dimensions.scope' => 'acquisition_matrix']),
                    'startDate' => $validated['dateStart'],
                    'endDate' => $validated['dateEnd'],
                ],
                'previous_traffic' => [
                    'aggregations' => $this->buildAggregations($trafficMetrics),
                    'groupBy' => [],
                    'filters' => array_merge($baseFilters, ['dimensions.scope' => 'traffic_matrix']),
                    'startDate' => $prevStart->format('Y-m-d'),
                    'endDate' => $prevEnd->format('Y-m-d'),
                ],
                'previous_acquisition' => [
                    'aggregations' => $this->buildAggregations($acqMetrics),
                    'groupBy' => [],
                    'filters' => array_merge($baseFilters, ['dimensions.scope' => 'acquisition_matrix']),
                    'startDate' => $prevStart->format('Y-m-d'),
                    'endDate' => $prevEnd->format('Y-m-d'),
                ],
            ];

            $results = $service->aggregateChanneledPool($tenant, 'google_analytics', 'metric', $payloads);

            $summary = [];
            $previous = [];

            foreach (['summary_traffic', 'summary_acquisition'] as $key) {
                if (isset($results[$key]['data'][0])) {
                    $summary = array_merge($summary, $results[$key]['data'][0]);
                }
            }
            foreach (['previous_traffic', 'previous_acquisition'] as $key) {
                if (isset($results[$key]['data'][0])) {
                    $previous = array_merge($previous, $results[$key]['data'][0]);
                }
            }

            return response()->json([
                'summary' => $summary,
                'previous' => $previous,
                'debug_results' => config('app.debug') ? $results : null,
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("GA4 Summary Error: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function chart(Request $request)
    {
        try {
            $validated = $this->validateRequest($request);
            $tenant = Project::findOrFail($validated['tenant']);
            $service = app(RemoteEngineService::class);

            $baseFilters = ['channeledAccount' => (string) $validated['account'], 'channel' => 'google_analytics'];

            $trafficMetrics = $this->metricsForScope('traffic_matrix');
            $acqMetrics = $this->metricsForScope('acquisition_matrix');

            $payloads = [
                'chart_traffic' => [
                    'aggregations' => $this->buildAggregations($trafficMetrics),
                    'groupBy' => ['daily'],
                    'filters' => array_merge($baseFilters, ['dimensions.scope' => 'traffic_matrix']),
                    'startDate' => $validated['dateStart'],
                    'endDate' => $validated['dateEnd'],
                    'limit' => 1000,
                ],
                'chart_acquisition' => [
                    'aggregations' => $this->buildAggregations($acqMetrics),
                    'groupBy' => ['daily'],
                    'filters' => array_merge($baseFilters, ['dimensions.scope' => 'acquisition_matrix']),
                    'startDate' => $validated['dateStart'],
                    'endDate' => $validated['dateEnd'],
                    'limit' => 1000,
                ],
            ];

            $results = $service->aggregateChanneledPool($tenant, 'google_analytics', 'metric', $payloads);

            $mergedByDate = [];
            foreach (['chart_traffic', 'chart_acquisition'] as $key) {
                $rows = $results[$key]['data'] ?? [];
                foreach ($rows as $row) {
                    $dateKey = $row['daily'] ?? $row['date'] ?? $row['metric_date'] ?? null;
                    if (!$dateKey) continue;
                    if (!isset($mergedByDate[$dateKey])) {
                        $mergedByDate[$dateKey] = ['daily' => $dateKey];
                    }
                    foreach ($row as $k => $v) {
                        if (!in_array($k, ['daily', 'date', 'metric_date'])) {
                            $mergedByDate[$dateKey][$k] = ($mergedByDate[$dateKey][$k] ?? 0) + (float) $v;
                        }
                    }
                }
            }

            return response()->json([
                'chart' => array_values($mergedByDate),
                'debug_results' => config('app.debug') ? $results : null,
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("GA4 Chart Error: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function table(Request $request)
    {
        try {
            $validated = $this->validateRequest($request);
            $tenant = Project::findOrFail($validated['tenant']);
            $service = app(RemoteEngineService::class);

            $tab = $validated['activeTab'] ?? 'campaigns';
            $baseFilters = ['channeledAccount' => (string) $validated['account'], 'channel' => 'google_analytics'];

            if ($this->isDualScopeTab($tab)) {
                $queries = $this->dualScopeQueries($tab);
                $payloads = [];
                foreach ($queries as $idx => $q) {
                    $payloads["q{$idx}"] = [
                        'aggregations' => $this->buildAggregations($q['metrics']),
                        'groupBy' => $q['groupBy'],
                        'filters' => array_merge($baseFilters, ['dimensions.scope' => $q['scope']]),
                        'startDate' => $validated['dateStart'],
                        'endDate' => $validated['dateEnd'],
                        'limit' => 5000,
                    ];
                }

                $results = $service->aggregateChanneledPool($tenant, 'google_analytics', 'metric', $payloads);

                $dataSets = [];
                $groupByKeys = [];
                foreach ($queries as $idx => $q) {
                    $dataSets[] = $results["q{$idx}"]['data'] ?? [];
                    $groupByKeys[] = $q['groupBy'][0];
                }

                $tableData = $this->mergeMatrixResults($dataSets, $groupByKeys);
            } else {
                $q = $this->singleScopeQuery($tab);
                $payload = [
                    'aggregations' => $this->buildAggregations($q['metrics']),
                    'groupBy' => $q['groupBy'],
                    'filters' => array_merge($baseFilters, ['dimensions.scope' => $q['scope']]),
                    'startDate' => $validated['dateStart'],
                    'endDate' => $validated['dateEnd'],
                    'limit' => 5000,
                ];

                $results = $service->aggregateChanneledPool($tenant, 'google_analytics', 'metric', ['table' => $payload]);
                $tableData = $results['table']['data'] ?? [];

                $rawKey = !empty($q['groupBy']) ? $q['groupBy'][0] : 'id';
                $lookupFull = strtolower($rawKey);
                $lookupStripped = strtolower(str_replace('dimensions.', '', $rawKey));
                foreach ($tableData as &$row) {
                    $rowLower = array_change_key_case($row, CASE_LOWER);
                    $val = $rowLower[$lookupFull] ?? $rowLower[$lookupStripped] ?? $rowLower['id'] ?? 'Unknown';
                    if (!$val || $val === 'null' || $val === '(not set)') $val = 'Unknown';
                    $row['id'] = $val;
                    $row['name'] = $val;
                }
            }

            return response()->json([
                'table' => $tableData,
                'debug_results' => config('app.debug') ? $results : null,
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("GA4 Table Error: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
