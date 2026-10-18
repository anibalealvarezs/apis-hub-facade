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
            'activeTab' => 'nullable|string|in:campaigns,adgroups,ads,devices',
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
            'campaigns' => 'channeledCampaign',
            'adgroups'  => 'adGroup',
            'ads'       => 'ad',
            'devices'   => 'device',
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
                'adgroups' => 'adGroup',
                'ads'      => 'ad',
                'devices'  => 'device',
                default    => 'channeledCampaign',
            };

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
                    'groupBy'      => [$groupByDimension],
                    'filters'      => $baseFilters,
                    'startDate'    => $validated['dateStart'],
                    'endDate'      => $validated['dateEnd']
                ]
            ];

            $results = $service->aggregateChanneledPool($tenant, 'google_ads', 'metric', $payloads);

            return response()->json([
                'data' => $results['table']['data'] ?? [],
                'retryable' => !empty($results['table']['retryable'])
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Google Ads Table Error: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
