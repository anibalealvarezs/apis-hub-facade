<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Services\RemoteEngineService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class FacebookMarketingController extends Controller
{
    private function validateRequest(Request $request): array
    {
        \Illuminate\Support\Facades\Log::info("FBM Raw Request: ", $request->all());
        return $request->validate([
            'tenant' => 'required|integer',
            'account' => 'required|array',
            'account.*' => 'nullable', // Temporarily remove strict type check to bypass 422
            'dateStart' => 'required|date',
            'dateEnd' => 'required|date',
            'activeTab' => 'nullable|string|in:campaigns,adsets,ads,age,gender',
            'activeFilters' => 'nullable|array',
            'activeFilters.*' => 'nullable|array',
            'metrics' => 'nullable|array',
            'metrics.*' => 'nullable|string',
        ]);
    }

    private function applyDynamicFilters(array &$filters, ?array $activeFilters): void
    {
        if (empty($activeFilters)) {
            return;
        }

        $dimensionMap = [
            'campaigns' => 'channeledCampaign',
            'adsets' => 'adGroup',
            'ads' => 'ad',
            'gender' => 'gender',
            'age' => 'age',
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

            $baseFilters = ['channel' => 'facebook_marketing'];
            if (!empty($validated['account'])) {
                if (count($validated['account']) === 1) {
                    $baseFilters['channeledAccount'] = $validated['account'][0];
                } else {
                    $baseFilters['channeledAccount'] = ['operator' => 'in', 'value' => $validated['account']];
                }
            }

            $this->applyDynamicFilters($baseFilters, $validated['activeFilters'] ?? null);

            $aggregations = [
                'total_spend' => 'spend',
                'total_clicks' => 'clicks',
                'total_impressions' => 'impressions',
                'total_reach' => 'reach',
                'average_frequency' => 'frequency',
                'average_cpm' => 'cpm',
                'average_ctr' => 'ctr',
                'average_cpc' => 'cpc',
                'average_purchase_roas' => 'purchase_roas',
                'total_results' => 'results',
                'average_cost_per_result' => 'cost_per_result',
                'average_result_rate' => 'result_rate'
            ];

            $payloads = [
                'summary' => [
                    'aggregations' => $aggregations,
                    'groupBy' => [],
                    'filters' => $baseFilters,
                    'startDate' => $validated['dateStart'],
                    'endDate' => $validated['dateEnd']
                ],
                'previous' => [
                    'aggregations' => $aggregations,
                    'groupBy' => [],
                    'filters' => $baseFilters,
                    'startDate' => $prevStart->format('Y-m-d'), 
                    'endDate' => $prevEnd->format('Y-m-d')
                ],
            ];

            $results = $service->aggregateChanneledPool($tenant, 'facebook_marketing', 'metric', $payloads);
            \Illuminate\Support\Facades\Log::info("FBM Summary Results: ", $results);
            
            if (isset($results['summary']['status']) && $results['summary']['status'] === 'error') {
                \Illuminate\Support\Facades\Log::error("FBM Summary APIs Hub Error: " . json_encode($results['summary']));
            }

            $summaryData = $results['summary']['data'][0] ?? [];
            $previousData = $results['previous']['data'][0] ?? [];

            $mapPrefixes = function (&$data) {
                if (empty($data)) return;
                // Ensure all keys are lowercase to match the mappings
                $data = array_change_key_case($data, CASE_LOWER);
                $mappings = [
                    'total_spend' => 'spend',
                    'total_clicks' => 'clicks',
                    'total_impressions' => 'impressions',
                    'total_reach' => 'reach',
                    'average_frequency' => 'frequency',
                    'average_cpm' => 'cpm',
                    'average_ctr' => 'ctr',
                    'average_cpc' => 'cpc',
                    'average_purchase_roas' => 'purchase_roas',
                    'total_results' => 'results',
                    'average_cost_per_result' => 'cost_per_result',
                    'average_result_rate' => 'result_rate'
                ];
                foreach ($mappings as $prefixed => $standard) {
                    if (isset($data[$prefixed])) {
                        $data[$standard] = $data[$prefixed];
                        unset($data[$prefixed]);
                    }
                }
            };

            $mapPrefixes($summaryData);
            $mapPrefixes($previousData);

            return response()->json([
                'summary' => empty($summaryData) ? new \stdClass() : $summaryData,
                'previous' => empty($previousData) ? new \stdClass() : $previousData,
                'debug_results' => ['payloads' => $payloads, 'meta' => $results['summary']['meta'] ?? null]
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("FBM Summary Error: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function chart(Request $request)
    {
        try {
            $validated = $this->validateRequest($request);
            $tenant = Project::findOrFail($validated['tenant']);
            $service = app(RemoteEngineService::class);

            $baseFilters = ['channel' => 'facebook_marketing'];
            if (!empty($validated['account'])) {
                if (count($validated['account']) === 1) {
                    $baseFilters['channeledAccount'] = $validated['account'][0];
                } else {
                    $baseFilters['channeledAccount'] = ['operator' => 'in', 'value' => $validated['account']];
                }
            }

            $this->applyDynamicFilters($baseFilters, $validated['activeFilters'] ?? null);

            $defaultAggregations = [
                'spend' => 'spend',
                'clicks' => 'clicks',
                'impressions' => 'impressions',
                'reach' => 'reach',
                'frequency' => 'frequency',
                'cpm' => 'cpm',
                'ctr' => 'ctr',
                'cpc' => 'cpc',
                'results' => 'results',
                'purchase_roas' => 'purchase_roas',
                'cost_per_result' => 'cost_per_result',
                'result_rate' => 'result_rate'
            ];

            $aggregations = $defaultAggregations;
            if (!empty($validated['metrics']) && is_array($validated['metrics'])) {
                $requestedMetrics = array_flip($validated['metrics']);
                $filteredAggregations = [];
                foreach ($defaultAggregations as $alias => $rawMetric) {
                    if (isset($requestedMetrics[$rawMetric]) || isset($requestedMetrics[$alias])) {
                        $filteredAggregations[$alias] = $rawMetric;
                    }
                }
                if (!empty($filteredAggregations)) {
                    $aggregations = $filteredAggregations;
                }
            }

            $payloads = [
                'chart' => [
                    'aggregations' => $aggregations,
                    'groupBy' => ['daily'],
                    'filters' => $baseFilters,
                    'startDate' => $validated['dateStart'],
                    'endDate' => $validated['dateEnd'],
                    'limit' => 1000
                ]
            ];

            $results = $service->aggregateChanneledPool($tenant, 'facebook_marketing', 'metric', $payloads);

            \Illuminate\Support\Facades\Log::error('[FACADE FBM DEBUG] chart method results:', [
                'status' => $results['chart']['status'] ?? null,
                'error' => $results['chart']['error'] ?? null,
                'data_count' => isset($results['chart']['data']) && is_array($results['chart']['data']) ? count($results['chart']['data']) : 0,
                'payloads' => $payloads,
            ]);

            if (isset($results['chart']['status']) && $results['chart']['status'] === 'error') {
                \Illuminate\Support\Facades\Log::error("FBM Chart APIs Hub Error: " . json_encode($results['chart']));
            }

            return response()->json([
                'chart' => $results['chart']['data'] ?? [],
                'debug_results' => ['payloads' => $payloads, 'meta' => $results['chart']['meta'] ?? null]
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("FBM Chart Error: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function trend(Request $request)
    {
        try {
            $validated = $request->validate([
'tenant' => 'required|integer',
                'series' => 'required|array',
                'series.dates' => 'required|array',
                'series.values' => 'required|array',
                'metric' => 'required|string'
            ]);

            $service = app(RemoteEngineService::class);
            
            // For Paid Media, we use EMA 7 vs 14 as per specs
            $payload = [
                'series' => $validated['series'],
                'metric' => $validated['metric'],
                'short_window' => 7,
                'long_window' => 14
            ];

            $result = $service->getTrend('ema', $payload);

            return response()->json([
                'trend' => $result,
                'metric' => $validated['metric']
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("FBM Trend Error: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function table(Request $request)
    {
        try {
            $validated = $this->validateRequest($request);
            $tenant = Project::findOrFail($validated['tenant']);
            $service = app(RemoteEngineService::class);

            $aggregations = [
                'total_spend' => 'spend',
                'total_clicks' => 'clicks',
                'total_impressions' => 'impressions',
                'total_reach' => 'reach',
                'average_frequency' => 'frequency',
                'average_cpm' => 'cpm',
                'average_ctr' => 'ctr',
                'average_cpc' => 'cpc',
                'total_results' => 'results',
                'average_purchase_roas' => 'purchase_roas',
                'average_cost_per_result' => 'cost_per_result',
                'average_result_rate' => 'result_rate'
            ];

            $accountFilter = ['channel' => 'facebook_marketing'];
            if (!empty($validated['account'])) {
                if (count($validated['account']) === 1) {
                    $accountFilter['channeledAccount'] = $validated['account'][0];
                } else {
                    $accountFilter['channeledAccount'] = ['operator' => 'in', 'value' => $validated['account']];
                }
            }

            $tabPayload = [
                'aggregations' => $aggregations,
                'filters' => $accountFilter,
                'startDate' => $validated['dateStart'],
                'endDate' => $validated['dateEnd'],
                'limit' => 5000
            ];
            
            $this->applyDynamicFilters($tabPayload['filters'], $validated['activeFilters'] ?? null);

            \Illuminate\Support\Facades\Log::info("FBM Table Incoming Request ActiveFilters: ", $validated['activeFilters'] ?? []);
            \Illuminate\Support\Facades\Log::info("FBM Table Outgoing Payload: ", $tabPayload);

            $tab = $validated['activeTab'] ?? 'campaigns';
            if (in_array($tab, ['campaigns', 'campaign_level'])) {
                $tabPayload['groupBy'] = ['channeledCampaign', 'campaign_status'];
            } elseif (in_array($tab, ['adsets', 'adset_level'])) {
                $tabPayload['groupBy'] = ['adGroup', 'adset_status'];
            } elseif (in_array($tab, ['ads', 'ad_level'])) {
                $tabPayload['groupBy'] = ['ad', 'ad_status'];
            } elseif ($tab === 'age') {
                $tabPayload['groupBy'] = ['age'];
            } elseif ($tab === 'gender') {
                $tabPayload['groupBy'] = ['gender'];
            }

            $payloads = ['table' => $tabPayload];
            $results = $service->aggregateChanneledPool($tenant, 'facebook_marketing', 'metric', $payloads);

            if (isset($results['table']['status']) && $results['table']['status'] === 'error') {
                \Illuminate\Support\Facades\Log::error("FBM Table APIs Hub Error: " . json_encode($results['table']));
            }

            $tableData = $results['table']['data'] ?? [];

            if (!empty($tableData)) {
                \Illuminate\Support\Facades\Log::info("FBM Raw Table Row: ", $tableData[0]);
            }

            // Normalize ID and Name for frontend table rendering
            foreach ($tableData as &$row) {
                // DBAL might return lowercased keys depending on the PDO configuration
                $rowLower = array_change_key_case($row, CASE_LOWER);
                
                if (isset($rowLower['channeledcampaign'])) {
                    $row['id'] = $rowLower['channeledcampaign_id'] ?? $rowLower['channeledcampaign'];
                    $row['name'] = $rowLower['channeledcampaign'];
                } elseif (isset($rowLower['adgroup_id'])) {
                    $row['id'] = $rowLower['adgroup_id'];
                    $row['name'] = $rowLower['adgroup'] ?? $rowLower['adgroup_id'];
                } elseif (isset($rowLower['ad_id'])) {
                    $row['id'] = $rowLower['ad_id'];
                    $row['name'] = $rowLower['ad'] ?? $rowLower['ad_id'];
                } elseif (isset($rowLower['age']) && isset($rowLower['gender'])) {
                    $row['id'] = $rowLower['age'] . '_' . $rowLower['gender'];
                    $row['name'] = ucfirst($rowLower['gender']) . ' / ' . $rowLower['age'];
                } elseif (isset($rowLower['age'])) {
                    $row['id'] = $rowLower['age'];
                    $row['name'] = $rowLower['age'];
                } elseif (isset($rowLower['gender'])) {
                    $row['id'] = $rowLower['gender'];
                    $row['name'] = ucfirst($rowLower['gender']);
                } else {
                    $row['id'] = 'Unknown';
                    $row['name'] = 'Unknown';
                }

                $mappings = [
                    'total_spend' => 'spend',
                    'total_clicks' => 'clicks',
                    'total_impressions' => 'impressions',
                    'total_reach' => 'reach',
                    'average_frequency' => 'frequency',
                    'average_cpm' => 'cpm',
                    'average_ctr' => 'ctr',
                    'average_cpc' => 'cpc',
                    'average_purchase_roas' => 'purchase_roas',
                    'total_results' => 'results',
                    'average_cost_per_result' => 'cost_per_result',
                    'average_result_rate' => 'result_rate'
                ];

                foreach ($mappings as $prefixed => $standard) {
                    if (isset($rowLower[$prefixed])) {
                        $row[$standard] = $rowLower[$prefixed];
                        foreach ($row as $key => $value) {
                            if (is_string($key) && strcasecmp($key, $prefixed) === 0) {
                                unset($row[$key]);
                            }
                        }
                    }
                }
            }

            return response()->json([
                'table' => $tableData,
                'debug_results' => ['payloads' => $payloads]
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("FBM Table Error: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
