<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Services\RemoteEngineService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class GoogleAdsController extends Controller
{
    private function validateRequest(Request $request): array
    {
        return $request->validate([
            'tenant' => 'required|integer',
            'account' => 'required|array',
            'account.*' => 'nullable',
            'dateStart' => 'required|date',
            'dateEnd' => 'required|date',
            'activeTab' => 'nullable|string|in:campaigns,adgroups,ads,devices,search_terms,queries',
            'activeFilters' => 'nullable|array',
            'activeFilters.*' => 'nullable',
            'filters' => 'nullable|array',
            'breakdown' => 'nullable|string',
            'groupBy' => 'nullable|array',
            'metrics' => 'nullable|array',
            'metrics.*' => 'nullable|string',
        ]);
    }

    private function applyDynamicFilters(array &$filters, ?array $activeFilters, ?array $explicitFilters = null): void
    {
        if (!empty($explicitFilters)) {
            foreach ($explicitFilters as $k => $v) {
                if ($v !== null && $v !== '') {
                    $filters[$k] = $v;
                }
            }
        }

        if (empty($activeFilters)) {
            return;
        }

        $dimensionMap = [
            'campaigns'       => 'channeledCampaign',
            'adgroups'        => 'adGroup',
            'ads'             => 'ad',
            'devices'         => 'device',
            'search_terms'    => 'query',
            'queries'         => 'query',
            'campaign_types'  => 'campaignType',
            'intents'         => 'dimensions.intent',
        ];

        foreach ($dimensionMap as $tab => $dimKey) {
            if (!empty($activeFilters[$tab])) {
                $values = is_array($activeFilters[$tab]) ? array_values(array_filter($activeFilters[$tab])) : [$activeFilters[$tab]];
                if (empty($values)) {
                    continue;
                }
                if (count($values) === 1) {
                    $filters[$dimKey] = $values[0];
                } else {
                    $filters[$dimKey] = ['operator' => 'in', 'value' => $values];
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

            $baseFilters = ['channel' => 'google_ads'];
            if (!empty($validated['account'])) {
                if (count($validated['account']) === 1) {
                    $baseFilters['channeledAccount'] = $validated['account'][0];
                } else {
                    $baseFilters['channeledAccount'] = ['operator' => 'in', 'value' => $validated['account']];
                }
            }

            $this->applyDynamicFilters($baseFilters, $validated['activeFilters'] ?? null);

            $aggregations = [
                'total_spend'                 => 'spend',
                'total_clicks'                => 'clicks',
                'total_impressions'           => 'impressions',
                'total_conversions'           => 'conversions',
                'total_conversion_value'      => 'total_revenue',
                'average_ctr'                 => 'ctr',
                'average_cpc'                 => 'cpc',
                'average_roas_purchase'       => 'roas_purchase',
                'average_cost_per_conversion' => 'cost_per_conversion',
                'average_conversion_rate'     => 'conversion_rate',
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

            $results = $service->aggregateChanneledPool($tenant, 'google_ads', 'metric', $payloads);

            $summaryData = $results['summary']['data'][0] ?? [];
            $previousData = $results['previous']['data'][0] ?? [];

            $mapPrefixes = function (&$data) {
                if (empty($data)) return;
                $data = array_change_key_case($data, CASE_LOWER);
                $mappings = [
                    'total_spend'                 => 'spend',
                    'total_clicks'                => 'clicks',
                    'total_impressions'           => 'impressions',
                    'total_conversions'           => 'conversions',
                    'total_conversion_value'      => 'conversion_value',
                    'average_ctr'                 => 'ctr',
                    'average_cpc'                 => 'cpc',
                    'average_roas_purchase'       => 'roas_purchase',
                    'average_cost_per_conversion' => 'cost_per_conversion',
                    'average_conversion_rate'     => 'conversion_rate',
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
                'debug_results' => ['payloads' => $payloads, 'meta' => $results['summary']['meta'] ?? null],
                'retryable' => !empty($results['summary']['retryable'])
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Google Ads Summary Error: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function chart(Request $request)
    {
        try {
            $validated = $this->validateRequest($request);
            $tenant = Project::findOrFail($validated['tenant']);
            $service = app(RemoteEngineService::class);

            $baseFilters = ['channel' => 'google_ads'];
            if (!empty($validated['account'])) {
                if (count($validated['account']) === 1) {
                    $baseFilters['channeledAccount'] = $validated['account'][0];
                } else {
                    $baseFilters['channeledAccount'] = ['operator' => 'in', 'value' => $validated['account']];
                }
            }

            $this->applyDynamicFilters($baseFilters, $validated['activeFilters'] ?? null, $validated['filters'] ?? null);

            $defaultAggregations = [
                'spend'               => 'spend',
                'clicks'              => 'clicks',
                'impressions'         => 'impressions',
                'conversions'         => 'conversions',
                'conversion_value'    => 'total_revenue',
                'ctr'                 => 'ctr',
                'cpc'                 => 'cpc',
                'roas_purchase'       => 'roas_purchase',
                'cost_per_conversion' => 'cost_per_conversion',
                'conversion_rate'     => 'conversion_rate',
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

            $groupBy = ['daily'];
            if (!empty($validated['groupBy'])) {
                $groupBy = $validated['groupBy'];
            } elseif (!empty($validated['breakdown'])) {
                $groupBy = ['daily', $validated['breakdown']];
            }

            $payloads = [
                'chart' => [
                    'aggregations' => $aggregations,
                    'groupBy'      => $groupBy,
                    'filters'      => $baseFilters,
                    'startDate'    => $validated['dateStart'],
                    'endDate'      => $validated['dateEnd']
                ]
            ];

            $results = $service->aggregateChanneledPool($tenant, 'google_ads', 'metric', $payloads);

            return response()->json([
                'chart' => $results['chart']['data'] ?? [],
                'retryable' => !empty($results['chart']['retryable'])
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Google Ads Chart Error: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function table(Request $request)
    {
        try {
            $validated = $this->validateRequest($request);
            $tenant = Project::findOrFail($validated['tenant']);
            $service = app(RemoteEngineService::class);

            $baseFilters = ['channel' => 'google_ads'];
            if (!empty($validated['account'])) {
                if (count($validated['account']) === 1) {
                    $baseFilters['channeledAccount'] = $validated['account'][0];
                } else {
                    $baseFilters['channeledAccount'] = ['operator' => 'in', 'value' => $validated['account']];
                }
            }

            $this->applyDynamicFilters($baseFilters, $validated['activeFilters'] ?? null, $validated['filters'] ?? null);

            $tab = $validated['activeTab'] ?? 'campaigns';
            $groupByDimension = match ($tab) {
                'adgroups'                     => 'adGroup',
                'ads'                          => 'ad',
                'devices'                      => 'device',
                'search_terms', 'queries'      => 'query',
                default                        => 'channeledCampaign',
            };

            $groupBy = [$groupByDimension];
            if ($tab === 'campaigns') {
                $groupBy[] = 'campaignType';
            } elseif (in_array($tab, ['search_terms', 'queries'], true)) {
                $groupBy[] = 'dimensions.intent';
            }

            $aggregations = [
                'spend'               => 'spend',
                'clicks'              => 'clicks',
                'impressions'         => 'impressions',
                'conversions'         => 'conversions',
                'conversion_value'    => 'conversion_value',
                'ctr'                 => 'ctr',
                'cpc'                 => 'cpc',
                'roas_purchase'       => 'roas_purchase',
                'cost_per_conversion' => 'cost_per_conversion',
                'conversion_rate'     => 'conversion_rate',
            ];

            $payloads = [
                'table' => [
                    'aggregations' => $aggregations,
                    'groupBy'      => $groupBy,
                    'filters'      => $baseFilters,
                    'startDate'    => $validated['dateStart'],
                    'endDate'      => $validated['dateEnd']
                ]
            ];

            $results = $service->aggregateChanneledPool($tenant, 'google_ads', 'metric', $payloads);

            $tableData = $results['table']['data'] ?? [];

            foreach ($tableData as &$row) {
                $rowLower = array_change_key_case($row, CASE_LOWER);

                if (isset($rowLower['channeledcampaign'])) {
                    $row['id'] = $rowLower['channeledcampaign_id'] ?? $rowLower['channeledcampaign'];
                    $row['name'] = $rowLower['channeledcampaign'];
                } elseif (isset($rowLower['adgroup'])) {
                    $row['id'] = $rowLower['adgroup_id'] ?? $rowLower['adgroup'];
                    $row['name'] = $rowLower['adgroup'];
                } elseif (isset($rowLower['ad'])) {
                    $row['id'] = $rowLower['ad_id'] ?? $rowLower['ad'];
                    $row['name'] = $rowLower['ad'];
                } elseif (isset($rowLower['device'])) {
                    $row['id'] = $rowLower['device_id'] ?? $rowLower['device'];
                    $row['name'] = ucfirst(strtolower((string)$rowLower['device']));
                } elseif (isset($rowLower['query'])) {
                    $row['id'] = $rowLower['query_id'] ?? $rowLower['query'];
                    $row['name'] = $rowLower['query'];
                } else {
                    $row['id'] = $row['id'] ?? 'Unknown';
                    $row['name'] = $row['name'] ?? 'Unknown';
                }

                if (isset($rowLower['campaigntype'])) {
                    $row['campaign_type'] = $rowLower['campaigntype'];
                }
                if (isset($rowLower['dimensions.intent']) || isset($rowLower['intent'])) {
                    $row['intent'] = $rowLower['dimensions.intent'] ?? $rowLower['intent'];
                }
            }

            return response()->json([
                'data' => $tableData,
                'retryable' => !empty($results['table']['retryable'])
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Google Ads Table Error: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
