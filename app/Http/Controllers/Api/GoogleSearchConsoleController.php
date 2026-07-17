<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Services\RemoteEngineService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class GoogleSearchConsoleController extends Controller
{
    private function validateRequest(Request $request): array
    {
        return $request->validate([
            'tenant' => 'required|integer',
            'account' => 'required|string',
            'dateStart' => 'required|date',
            'dateEnd' => 'required|date',
            'activeTab' => 'nullable|string|in:queries,pages,countries,devices,appearances',
            'activeFilters' => 'nullable|array',
            'activeFilters.*' => 'nullable|array',
            'metrics' => 'nullable|array',
            'metrics.*' => 'string'
        ]);
    }

    private function getRequestedAggregations(Request $request): array
    {
        $availableAggs = ['clicks' => 'clicks', 'impressions' => 'impressions', 'ctr' => 'ctr', 'position' => 'position'];
        $requestedMetrics = $request->input('metrics');
        
        $aggregations = [];
        if (empty($requestedMetrics)) {
            return $availableAggs;
        }
        
        foreach ((array)$requestedMetrics as $m) {
            if (isset($availableAggs[$m])) {
                $aggregations[$m] = $availableAggs[$m];
            }
        }
        
        return empty($aggregations) ? $availableAggs : $aggregations;
    }

    private function applyDynamicFilters(array &$filters, ?array $activeFilters): void
    {
        // Search Appearance is incompatible with other dimensions, must always be standard
        $filters['dimensions.searchAppearance'] = 'standard';

        if (empty($activeFilters)) {
            return;
        }

        $dimensionMap = [
            'queries' => 'query',
            'pages' => 'dimensions.page',
            'countries' => 'country',
            'devices' => 'device',
        ];

        foreach ($dimensionMap as $tab => $dimKey) {
            if (!empty($activeFilters[$tab])) {
                if (count($activeFilters[$tab]) === 1) {
                    $filters[$dimKey] = $activeFilters[$tab][0];
                } else {
                    $filters[$dimKey] = ['operator' => 'in', 'value' => $activeFilters[$tab]];
                }
            }
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

            // The dashboard account selector sends channeled_account IDs, not page IDs.
            $baseFilters = ['channeledAccount' => (string)$validated['account']];
            $this->applyDynamicFilters($baseFilters, $validated['activeFilters'] ?? null);

            $aggs = $this->getRequestedAggregations($request);

            $payloads = [
                'summary' => [
                    'aggregations' => $aggs,
                    'groupBy' => [],
                    'filters' => $baseFilters,
                    'startDate' => $validated['dateStart'],
                    'endDate' => $validated['dateEnd']
                ],
                'previous' => [
                    'aggregations' => $aggs,
                    'groupBy' => [],
                    'filters' => $baseFilters,
                    'startDate' => $prevStart->format('Y-m-d'),
                    'endDate' => $prevEnd->format('Y-m-d')
                ],
            ];

            \Illuminate\Support\Facades\Log::info("GSC Summary Payload: ", $payloads);
            $results = $service->aggregateChanneledPool($tenant, 'google_search_console', 'metric', $payloads);
            \Illuminate\Support\Facades\Log::info("GSC Summary Results: ", $results);

            if (isset($results['summary']['status']) && $results['summary']['status'] === 'error') {
                \Illuminate\Support\Facades\Log::error("GSC Summary APIs Hub Error: " . json_encode($results['summary']));
            }

            return response()->json([
                'summary' => $results['summary']['data'][0] ?? [],
                'previous' => $results['previous']['data'][0] ?? [],
                'debug_results' => config('app.debug') ? $results : null
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("GSC Summary Error: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function chart(Request $request)
    {
        try {
            $validated = $this->validateRequest($request);
            $tenant = Project::findOrFail($validated['tenant']);
            $service = app(RemoteEngineService::class);

            // The dashboard account selector sends channeled_account IDs, not page IDs.
            $baseFilters = ['channeledAccount' => (string)$validated['account']];
            $this->applyDynamicFilters($baseFilters, $validated['activeFilters'] ?? null);

            $aggs = $this->getRequestedAggregations($request);

            $payloads = [
                'chart' => [
                    'aggregations' => $aggs,
                    'groupBy' => ['daily'], // or 'date' if daily fails
                    'filters' => $baseFilters,
                    'startDate' => $validated['dateStart'],
                    'endDate' => $validated['dateEnd'],
                    'limit' => 1000 // ensure all days are returned
                ]
            ];

            $results = $service->aggregateChanneledPool($tenant, 'google_search_console', 'metric', $payloads);

            if (isset($results['chart']['status']) && $results['chart']['status'] === 'error') {
                \Illuminate\Support\Facades\Log::error("GSC Chart APIs Hub Error: " . json_encode($results['chart']));
            }

            return response()->json([
                'chart' => $results['chart']['data'] ?? [],
                'debug_results' => config('app.debug') ? $results : null
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("GSC Chart Error: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function table(Request $request)
    {
        try {
            $validated = $this->validateRequest($request);
            $tenant = Project::findOrFail($validated['tenant']);
            $service = app(RemoteEngineService::class);

            $tab = $validated['activeTab'] ?? 'queries';
            $aggs = $this->getRequestedAggregations($request);

            $tabPayload = [
                'aggregations' => $aggs,
                'filters' => [
                    // The dashboard account selector sends channeled_account IDs, not page IDs.
                    'channeledAccount' => (string)$validated['account'],
                    'dimensions.searchAppearance' => 'standard'
                ],
                'startDate' => $validated['dateStart'],
                'endDate' => $validated['dateEnd'],
                'limit' => 5000 // reasonable limit for frontend rendering
            ];

            if ($tab === 'queries') $tabPayload['groupBy'] = ['query'];
            elseif ($tab === 'pages') $tabPayload['groupBy'] = ['dimensions.page'];
            elseif ($tab === 'countries') $tabPayload['groupBy'] = ['country'];
            elseif ($tab === 'devices') $tabPayload['groupBy'] = ['device'];
            elseif ($tab === 'appearances') {
                $tabPayload['groupBy'] = ['dimensions.searchAppearance'];
                $tabPayload['filters']['dimensions.searchAppearance'] = ['operator' => 'not_equal', 'value' => 'standard'];
            } else {
                // Metric widget custom dimension
                $tabPayload['groupBy'] = [$tab];
                if ($tab === 'dimensions.searchAppearance' || $tab === 'searchAppearance') {
                    $tabPayload['filters']['dimensions.searchAppearance'] = ['operator' => 'not_equal', 'value' => 'standard'];
                }
            }

            $payloads = ['table' => $tabPayload];
            $results = $service->aggregateChanneledPool($tenant, 'google_search_console', 'metric', $payloads);

            if (isset($results['table']['status']) && $results['table']['status'] === 'error') {
                \Illuminate\Support\Facades\Log::error("GSC Table APIs Hub Error: " . json_encode($results['table']));
            }

            $tableData = $results['table']['data'] ?? [];

            // Normalize ID for frontend
            $rawKey = $tabPayload['groupBy'][0] ?? 'id';
            $lookupFull = strtolower($rawKey);
            $lookupStripped = strtolower(str_replace('dimensions.', '', $rawKey));
            
            foreach ($tableData as &$row) {
                $rowLower = array_change_key_case($row, CASE_LOWER);
                $val = $rowLower[$lookupFull] ?? $rowLower[$lookupStripped] ?? $rowLower['id'] ?? 'Unknown';
                if (!$val || $val === 'null' || $val === '(not set)') $val = 'Unknown';
                $row['id'] = $val;
                $row['name'] = $val;
            }

            return response()->json([
                'table' => $tableData,
                'debug_results' => config('app.debug') ? $results : null
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("GSC Table Error: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    public function trend(Request $request)
    {
        try {
            $validated = $request->validate([
'tenant' => 'required|integer',
                'metric' => 'required|string',
                'series' => 'required|array',
                'series.dates' => 'required|array',
                'series.values' => 'required|array',
            ]);

            $tenant = Project::findOrFail($validated['tenant']);
            $service = app(RemoteEngineService::class);

            $payload = [
                'metric' => $validated['metric'],
                'series' => $validated['series']
            ];

            // For GSC we need Linear regression + 28-day SMA
            $linearResult = $service->getTrend('linear', $payload);
            
            $smaPayload = array_merge($payload, ['window' => 28]);
            $smaResult = $service->getTrend('sma', $smaPayload);

            $trendData = [];
            
            if (isset($linearResult['success']) && $linearResult['success']) {
                $trendData['trend_linear'] = $linearResult['trend'] ?? [];
            }
            
            if (isset($smaResult['success']) && $smaResult['success']) {
                $trendData['trend_sma'] = $smaResult['trend'] ?? [];
            }

            if (empty($trendData)) {
                return response()->json(['success' => false, 'error' => 'No trend calculated']);
            }

            return response()->json(['trend' => array_merge(['success' => true], $trendData)]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("GSC Trend Proxy Error: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
