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
            'tenant' => 'required|integer',
            'account' => 'required|string',
            'dateStart' => 'required|date',
            'dateEnd' => 'required|date',
            'activeTab' => 'nullable|string|in:campaigns,adgroups,channels,sources,traffic_pages,traffic_countries,traffic_devices,acquisition_channels,events,adtouchpoints_adgroups,adtouchpoints_terms,adtouchpoints_content',
            'activeFilters' => 'nullable|array',
            'activeFilters.*' => 'nullable|array',
            'metrics' => 'nullable|array',
            'metrics.*' => 'string',
            'dependency' => 'nullable|string',
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
            'traffic_matrix' => ['sessions', 'screenPageViews', 'conversions', 'averageSessionDuration', 'totalRevenue'],
            'acquisition_matrix' => ['newUsers', 'activeUsers', 'totalUsers', 'totalRevenue'],
            'event_matrix' => ['eventCount', 'conversions'],
            'ad_touchpoint_matrix' => ['sessions', 'conversions', 'totalRevenue'],
            default => ['sessions', 'activeUsers'],
        };
        if ($scope === 'traffic_matrix' && $includeBounceRate) {
            $base[] = 'bounceRate';
        }
        return $base;
    }

    private function mapToGa4(string $metric): string
    {
        return match ($metric) {
            'pageviews' => 'screenPageViews',
            'bounce_rate' => 'bounceRate',
            'new_users' => 'newUsers',
            'average_session_duration' => 'averageSessionDuration',
            'revenue' => 'totalRevenue',
            'events' => 'eventCount',
            'active_users' => 'activeUsers',
            'total_users' => 'totalUsers',
            default => $metric,
        };
    }

    private function mapFromGa4(string $metric): string
    {
        return match ($metric) {
            'screenPageViews' => 'screenPageViews',
            'bounceRate' => 'bounceRate',
            'newUsers' => 'newUsers',
            'averageSessionDuration' => 'averageSessionDuration',
            'totalRevenue' => 'revenue',
            'eventCount' => 'eventCount',
            'activeUsers' => 'activeUsers',
            'totalUsers' => 'totalUsers',
            default => $metric,
        };
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
    private function dualScopeQueries(string $tab, array $requestedMetrics = []): array
    {
        $trafficMetrics = $this->metricsForScope('traffic_matrix');
        if (!empty($requestedMetrics)) {
            $trafficMetrics = array_values(array_intersect($trafficMetrics, $requestedMetrics));
        }

        $acqMetrics = $this->metricsForScope('acquisition_matrix');
        if (!empty($requestedMetrics)) {
            $acqMetrics = array_values(array_intersect($acqMetrics, $requestedMetrics));
        }

        $queries = [];

        switch ($tab) {
            case 'campaigns':
                if (!empty($trafficMetrics)) $queries[] = ['scope' => 'traffic_matrix', 'metrics' => $trafficMetrics, 'groupBy' => ['channeledCampaign']];
                if (!empty($acqMetrics)) $queries[] = ['scope' => 'acquisition_matrix', 'metrics' => $acqMetrics, 'groupBy' => ['channeledCampaign']];
                break;
            case 'adgroups':
                if (!empty($trafficMetrics)) $queries[] = ['scope' => 'traffic_matrix', 'metrics' => $trafficMetrics, 'groupBy' => ['channeledAdGroup']];
                if (!empty($acqMetrics)) $queries[] = ['scope' => 'acquisition_matrix', 'metrics' => $acqMetrics, 'groupBy' => ['channeledAdGroup']];
                break;
            case 'channels':
                if (!empty($trafficMetrics)) $queries[] = ['scope' => 'traffic_matrix', 'metrics' => $trafficMetrics, 'groupBy' => ['dimensions.sessionDefaultChannelGroup']];
                if (!empty($acqMetrics)) $queries[] = ['scope' => 'acquisition_matrix', 'metrics' => $acqMetrics, 'groupBy' => ['dimensions.firstUserDefaultChannelGroup']];
                break;
            case 'sources':
                if (!empty($trafficMetrics)) $queries[] = ['scope' => 'traffic_matrix', 'metrics' => $trafficMetrics, 'groupBy' => ['dimensions.sessionSourceMedium']];
                if (!empty($acqMetrics)) $queries[] = ['scope' => 'acquisition_matrix', 'metrics' => $acqMetrics, 'groupBy' => ['dimensions.firstUserSourceMedium']];
                break;
        }

        return $queries;
    }

    private function singleScopeQuery(string $tab, array $requestedMetrics = []): array
    {
        $scope = $this->scopeForTab($tab);
        $includeBounceRate = in_array($tab, ['traffic_pages', 'traffic_countries', 'traffic_devices']);
        $metrics = $this->metricsForScope($scope, $includeBounceRate);

        if (!empty($requestedMetrics)) {
            $metrics = array_values(array_intersect($metrics, $requestedMetrics));
        }

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
        $dimensionKeysToIgnore = [
            'channeledcampaign', 'channeledcampaign_id',
            'channeledadgroup', 'channeledadgroup_id',
            'channeledad', 'channeledad_id',
            'dimensions.sessiondefaultchannelgroup', 'dimensions.firstuserdefaultchannelgroup',
            'sessiondefaultchannelgroup', 'firstuserdefaultchannelgroup',
            'dimensions.sessionsourcemedium', 'dimensions.firstusersourcemedium',
            'sessionsourcemedium', 'firstusersourcemedium',
            'dimensions.landing_page', 'landing_page', 'country', 'device', 'event'
        ];

        foreach ($results as $idx => $rows) {
            $dimKey = $groupByKeys[$idx] ?? null;
            if (!$dimKey || !$rows) continue;

            $lookupFull = strtolower($dimKey);
            $lookupStripped = strtolower(str_replace('dimensions.', '', $dimKey));

            foreach ($rows as $row) {
                $rowLower = array_change_key_case($row, CASE_LOWER);

                // Check for explicit entity ID key (e.g. channeledCampaign_id)
                $idKey = $lookupStripped . '_id';
                $entityId = $rowLower[$idKey] ?? null;

                $dimValue = $rowLower[$lookupFull] ?? $rowLower[$lookupStripped] ?? null;
                if (!$dimValue || $dimValue === 'null' || $dimValue === '(not set)') {
                    $dimValue = 'Unknown';
                }

                // If entity ID exists, map by entity ID to prevent grouping distinct items into "n/a"
                $key = ($entityId !== null && $entityId !== '') ? 'id_' . $entityId : strtolower((string) $dimValue);

                $displayName = $dimValue;
                if ($displayName === 'Unknown' || $displayName === 'N/A') {
                    if ($entityId) {
                        $displayName = 'ID #' . $entityId;
                    }
                }

                if (!isset($map[$key])) {
                    $map[$key] = ['_dimKey' => $dimKey, '_dimValue' => $displayName];
                }

                $existing = $map[$key];
                foreach ($row as $k => $v) {
                    $kLower = strtolower($k);
                    if (in_array($kLower, $dimensionKeysToIgnore, true) || $kLower === $lookupFull || $kLower === $lookupStripped || $kLower === $idKey) {
                        continue;
                    }
                    $num = is_numeric($v) ? (float) $v : $v;
                    if (is_float($num)) {
                        if (in_array($kLower, ['bouncerate', 'bounce_rate', 'averagesessionduration', 'average_session_duration'], true)) {
                            $cntKey = '_cnt_' . $k;
                            $currentSum = ($existing[$k] ?? 0) * ($existing[$cntKey] ?? (isset($existing[$k]) ? 1 : 0));
                            $newCount = ($existing[$cntKey] ?? (isset($existing[$k]) ? 1 : 0)) + 1;
                            $avg = ($currentSum + $num) / $newCount;
                            $existing[$k] = in_array($kLower, ['bouncerate', 'bounce_rate'], true) ? min(1.0, $avg) : $avg;
                            $existing[$cntKey] = $newCount;
                        } else {
                            $existing[$k] = ($existing[$k] ?? 0) + $num;
                        }
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
            foreach (array_keys($item) as $k) {
                if (str_starts_with($k, '_cnt_')) {
                    unset($item[$k]);
                }
            }
            return $item;
        }, $map));
    }

    public function listProperties(Request $request)
    {
        try {
            $validated = $request->validate([
'tenant' => 'required|integer',
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

            $requestedMetrics = $validated['metrics'] ?? [];
            $requestedMetrics = array_map([$this, 'mapToGa4'], $requestedMetrics);
            $dependency = $validated['dependency'] ?? null;

            if (empty($requestedMetrics)) {
                if ($dependency) {
                    $requestedMetrics = $this->metricsForScope($dependency, $dependency === 'traffic_matrix');
                } else {
                    $requestedMetrics = array_values(array_unique(array_merge(
                        $this->metricsForScope('traffic_matrix', true),
                        $this->metricsForScope('acquisition_matrix'),
                        $this->metricsForScope('event_matrix'),
                        $this->metricsForScope('ad_touchpoint_matrix')
                    )));
                }
            } else {
                $validForScope = $dependency ? $this->metricsForScope($dependency, $dependency === 'traffic_matrix') : array_unique(array_merge(
                    $this->metricsForScope('traffic_matrix', true),
                    $this->metricsForScope('acquisition_matrix'),
                    $this->metricsForScope('event_matrix'),
                    $this->metricsForScope('ad_touchpoint_matrix')
                ));
                
                $intersect = array_intersect($requestedMetrics, $validForScope);
                $requestedMetrics = !empty($intersect) ? array_values($intersect) : $validForScope;
            }

            \Illuminate\Support\Facades\Log::error("GA4 Summary TRACE [Input]:", [
                'metrics_raw' => $validated['metrics'] ?? null,
                'dependency' => $dependency,
                'requestedMetrics' => $requestedMetrics,
            ]);

            $scopesToQuery = $dependency ? [$dependency] : ['traffic_matrix', 'acquisition_matrix', 'event_matrix', 'ad_touchpoint_matrix'];
            $payloads = [];
            $unassignedMetrics = $requestedMetrics;

            $allKnownMetrics = array_unique(array_merge(
                $this->metricsForScope('traffic_matrix', true),
                $this->metricsForScope('acquisition_matrix'),
                $this->metricsForScope('event_matrix'),
                $this->metricsForScope('ad_touchpoint_matrix')
            ));

            foreach ($scopesToQuery as $scope) {
                if (empty($unassignedMetrics)) break;

                if ($dependency) {
                    $intersect = $unassignedMetrics;
                } else {
                    $scopeMetrics = $this->metricsForScope($scope, $scope === 'traffic_matrix');
                    $intersect = array_values(array_intersect($scopeMetrics, $unassignedMetrics));
                    
                    if ($scope === 'traffic_matrix') {
                        $customMetrics = array_diff($unassignedMetrics, $allKnownMetrics);
                        $intersect = array_merge($intersect, $customMetrics);
                    }
                }

                if (!empty($intersect)) {
                    $payloads["summary_{$scope}"] = [
                        'aggregations' => $this->buildAggregations($intersect),
                        'groupBy' => [],
                        'filters' => array_merge($baseFilters, ['dimensions.scope' => $scope]),
                        'startDate' => $validated['dateStart'],
                        'endDate' => $validated['dateEnd'],
                    ];
                    $payloads["previous_{$scope}"] = [
                        'aggregations' => $this->buildAggregations($intersect),
                        'groupBy' => [],
                        'filters' => array_merge($baseFilters, ['dimensions.scope' => $scope]),
                        'startDate' => $prevStart->format('Y-m-d'),
                        'endDate' => $prevEnd->format('Y-m-d'),
                    ];
                    $unassignedMetrics = array_diff($unassignedMetrics, $intersect);
                }
            }

            \Illuminate\Support\Facades\Log::error("GA4 Summary TRACE [Payloads]:", $payloads);
            $results = empty($payloads) ? [] : $service->aggregateChanneledPool($tenant, 'google_analytics', 'metric', $payloads);
            \Illuminate\Support\Facades\Log::error("GA4 Summary TRACE [Engine Results]:", $results);

            $summary = [];
            $previous = [];

            foreach ($scopesToQuery as $scope) {
                if (isset($results["summary_{$scope}"]['data'][0])) {
                    foreach ($results["summary_{$scope}"]['data'][0] as $k => $v) {
                        $summary[$this->mapFromGa4($k)] = $v;
                    }
                }
                if (isset($results["previous_{$scope}"]['data'][0])) {
                    foreach ($results["previous_{$scope}"]['data'][0] as $k => $v) {
                        $previous[$this->mapFromGa4($k)] = $v;
                    }
                }
            }

            \Illuminate\Support\Facades\Log::error("GA4 Summary TRACE [Final Output]:", [
                'summary' => $summary,
                'previous' => $previous
            ]);

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

            $requestedMetrics = $validated['metrics'] ?? [];
            \Illuminate\Support\Facades\Log::error("GA4 Chart: Input metrics from validated request:", ['metrics' => $requestedMetrics]);

            $requestedMetrics = array_map([$this, 'mapToGa4'], $requestedMetrics);
            \Illuminate\Support\Facades\Log::error("GA4 Chart: Metrics after mapToGa4:", ['metrics' => $requestedMetrics]);

            $dependency = $validated['dependency'] ?? null;
            \Illuminate\Support\Facades\Log::error("GA4 Chart: Resolved dependency:", ['dependency' => $dependency]);
            
            if (empty($requestedMetrics)) {
                if ($dependency) {
                    $requestedMetrics = $this->metricsForScope($dependency, $dependency === 'traffic_matrix');
                } else {
                    $requestedMetrics = array_merge(
                        $this->metricsForScope('traffic_matrix', true),
                        $this->metricsForScope('acquisition_matrix')
                    );
                }
            } else {
                $validForScope = $dependency ? $this->metricsForScope($dependency, $dependency === 'traffic_matrix') : array_merge(
                    $this->metricsForScope('traffic_matrix', true),
                    $this->metricsForScope('acquisition_matrix'),
                    $this->metricsForScope('event_matrix'),
                    $this->metricsForScope('ad_touchpoint_matrix')
                );
                
                $intersect = array_intersect($requestedMetrics, $validForScope);
                $requestedMetrics = !empty($intersect) ? array_values($intersect) : $validForScope;
            }
            $scopesToQuery = $dependency ? [$dependency] : ['traffic_matrix', 'acquisition_matrix', 'event_matrix', 'ad_touchpoint_matrix'];
            $payloads = [];
            $unassignedMetrics = $requestedMetrics;

            $allKnownMetrics = array_unique(array_merge(
                $this->metricsForScope('traffic_matrix', true),
                $this->metricsForScope('acquisition_matrix'),
                $this->metricsForScope('event_matrix'),
                $this->metricsForScope('ad_touchpoint_matrix')
            ));

            foreach ($scopesToQuery as $scope) {
                if (empty($unassignedMetrics)) break;

                if ($dependency) {
                    $intersect = $unassignedMetrics;
                } else {
                    $scopeMetrics = $this->metricsForScope($scope, $scope === 'traffic_matrix');
                    $intersect = array_values(array_intersect($scopeMetrics, $unassignedMetrics));
                    
                    if ($scope === 'traffic_matrix') {
                        $customMetrics = array_diff($unassignedMetrics, $allKnownMetrics);
                        $intersect = array_merge($intersect, $customMetrics);
                    }
                }

                if (!empty($intersect)) {
                    $payloads["chart_{$scope}"] = [
                        'aggregations' => $this->buildAggregations($intersect),
                        'groupBy' => ['daily'],
                        'filters' => array_merge($baseFilters, ['dimensions.scope' => $scope]),
                        'startDate' => $validated['dateStart'],
                        'endDate' => $validated['dateEnd'],
                        'limit' => 1000,
                    ];
                    $unassignedMetrics = array_diff($unassignedMetrics, $intersect);
                }
            }

            \Illuminate\Support\Facades\Log::error("GA4 Chart Payload:", $payloads);
            $results = empty($payloads) ? [] : $service->aggregateChanneledPool($tenant, 'google_analytics', 'metric', $payloads);
            \Illuminate\Support\Facades\Log::error("GA4 Chart Results:", $results);

            $mergedByDate = [];
            foreach ($scopesToQuery as $scope) {
                $rows = $results["chart_{$scope}"]['data'] ?? [];
                foreach ($rows as $row) {
                    $dateKey = $row['daily'] ?? $row['date'] ?? $row['metric_date'] ?? null;
                    if (!$dateKey) continue;
                    if (!isset($mergedByDate[$dateKey])) {
                        $mergedByDate[$dateKey] = ['daily' => $dateKey];
                    }
                    foreach ($row as $k => $v) {
                        if (!in_array($k, ['daily', 'date', 'metric_date'])) {
                            $mappedKey = $this->mapFromGa4($k);
                            $mergedByDate[$dateKey][$mappedKey] = ($mergedByDate[$dateKey][$mappedKey] ?? 0) + (float) $v;
                        }
                    }
                }
            }

            \Illuminate\Support\Facades\Log::error("GA4 Chart: Final mapped chart result:", array_values($mergedByDate));

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

            $dependency = $validated['dependency'] ?? null;
            $requestedMetrics = $validated['metrics'] ?? [];
            \Illuminate\Support\Facades\Log::error("GA4 Table TRACE [Request]:", [
                'tab' => $tab,
                'account' => $validated['account'],
                'metrics_raw' => $validated['metrics'] ?? null,
                'dependency' => $dependency
            ]);
            
            $requestedMetrics = array_map([$this, 'mapToGa4'], $requestedMetrics);
            $queries = [];

            $isLegacyTab = in_array($tab, [
                'campaigns', 'adgroups', 'channels', 'sources',
                'traffic_pages', 'traffic_countries', 'traffic_devices', 'acquisition_channels',
                'events', 'adtouchpoints_adgroups', 'adtouchpoints_terms', 'adtouchpoints_content'
            ]);

            if ($isLegacyTab) {
                if ($this->isDualScopeTab($tab)) {
                    $queries = $this->dualScopeQueries($tab, $requestedMetrics);
                } else {
                    $q = $this->singleScopeQuery($tab, $requestedMetrics);
                    if (!empty($q['metrics'])) {
                        $queries[] = $q;
                    }
                }
            } else {
                $scopesToQuery = $dependency ? [$dependency] : ['traffic_matrix', 'acquisition_matrix', 'event_matrix', 'ad_touchpoint_matrix'];
                $unassignedMetrics = $requestedMetrics;

                if (empty($unassignedMetrics)) {
                    if ($dependency) {
                        $unassignedMetrics = $this->metricsForScope($dependency, $dependency === 'traffic_matrix');
                    } else {
                        $unassignedMetrics = array_merge(
                            $this->metricsForScope('traffic_matrix', true),
                            $this->metricsForScope('acquisition_matrix')
                        );
                    }
                } else {
                    $validForScope = $dependency ? $this->metricsForScope($dependency, $dependency === 'traffic_matrix') : array_merge(
                        $this->metricsForScope('traffic_matrix', true),
                        $this->metricsForScope('acquisition_matrix'),
                        $this->metricsForScope('event_matrix'),
                        $this->metricsForScope('ad_touchpoint_matrix')
                    );
                    
                    $intersect = array_intersect($unassignedMetrics, $validForScope);
                    $unassignedMetrics = !empty($intersect) ? array_values($intersect) : $validForScope;
                }

                $allKnownMetrics = array_unique(array_merge(
                    $this->metricsForScope('traffic_matrix', true),
                    $this->metricsForScope('acquisition_matrix'),
                    $this->metricsForScope('event_matrix'),
                    $this->metricsForScope('ad_touchpoint_matrix')
                ));

                foreach ($scopesToQuery as $scope) {
                    if (empty($unassignedMetrics)) break;

                    if ($dependency) {
                        $intersect = $unassignedMetrics;
                    } else {
                        $scopeMetrics = $this->metricsForScope($scope);
                        $intersect = array_values(array_intersect($scopeMetrics, $unassignedMetrics));
                        
                        if ($scope === 'traffic_matrix') {
                            $customMetrics = array_diff($unassignedMetrics, $allKnownMetrics);
                            $intersect = array_merge($intersect, $customMetrics);
                        }
                    }
                    
                    if (!empty($intersect)) {
                        $queries[] = [
                            'scope' => $scope,
                            'metrics' => $intersect,
                            'groupBy' => [$tab],
                        ];
                        $unassignedMetrics = array_diff($unassignedMetrics, $intersect);
                    }
                }
            }

            \Illuminate\Support\Facades\Log::error("GA4 Table TRACE [Queries Built]:", ['queries' => $queries]);

            if (empty($queries)) {
                return response()->json(['table' => [], 'debug_results' => null]);
            }

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

            \Illuminate\Support\Facades\Log::error("GA4 Table TRACE [Engine Payloads]:", ['payloads' => $payloads]);
            $results = $service->aggregateChanneledPool($tenant, 'google_analytics', 'metric', $payloads);
            \Illuminate\Support\Facades\Log::error("GA4 Table TRACE [Engine Raw Response]:", ['results' => $results]);

            $dataSets = [];
            $groupByKeys = [];
            foreach ($queries as $idx => $q) {
                $dataSets[] = $results["q{$idx}"]['data'] ?? [];
                $groupByKeys[] = $q['groupBy'][0];
            }

            $tableData = empty($dataSets) ? [] : $this->mergeMatrixResults($dataSets, $groupByKeys);
            
            $mappedTableData = [];
            foreach ($tableData as $row) {
                $mappedRow = [];
                foreach ($row as $k => $v) {
                    $mappedRow[$this->mapFromGa4($k)] = $v;
                }
                $mappedTableData[] = $mappedRow;
            }

            \Illuminate\Support\Facades\Log::error("GA4 Table TRACE [Final Mapped Output]:", [
                'count' => count($mappedTableData),
                'first_row' => $mappedTableData[0] ?? null
            ]);

            return response()->json([
                'table' => $mappedTableData,
                'debug_results' => config('app.debug') ? $results : null,
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("GA4 Table Error: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
