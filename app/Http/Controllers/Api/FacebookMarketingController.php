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
            'tenant' => 'required|string',
            'account' => 'required|array',
            'account.*' => 'nullable', // Temporarily remove strict type check to bypass 422
            'dateStart' => 'required|date',
            'dateEnd' => 'required|date',
            'activeTab' => 'nullable|string|in:campaigns,adsets,ads,age,gender',
            'activeFilters' => 'nullable|array',
            'activeFilters.*' => 'nullable|array',
        ]);
    }

    private function applyDynamicFilters(array &$filters, ?array $activeFilters): void
    {
        if (empty($activeFilters)) {
            return;
        }

        $dimensionMap = [
            'campaigns' => 'channeledCampaign',
            'adsets' => 'adGroup_id',
            'ads' => 'ad_id',
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

            $baseFilters = [];
            if (!empty($validated['account'])) {
                if (count($validated['account']) === 1) {
                    $baseFilters['channeledAccount'] = $validated['account'][0];
                } else {
                    $baseFilters['channeledAccount'] = ['operator' => 'in', 'value' => $validated['account']];
                }
            }

            $this->applyDynamicFilters($baseFilters, $validated['activeFilters'] ?? null);

            $aggregations = [
                'spend' => 'spend',
                'clicks' => 'clicks',
                'impressions' => 'impressions',
                'ctr' => 'ctr',
                'cpc' => 'cpc',
                'purchase_roas' => 'purchase_roas',
                'results' => 'results'
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

            return response()->json([
                'summary' => $results['summary']['data'][0] ?? new \stdClass(),
                'previous' => $results['previous']['data'][0] ?? new \stdClass(),
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

            $baseFilters = [];
            if (!empty($validated['account'])) {
                if (count($validated['account']) === 1) {
                    $baseFilters['channeledAccount'] = $validated['account'][0];
                } else {
                    $baseFilters['channeledAccount'] = ['operator' => 'in', 'value' => $validated['account']];
                }
            }

            $this->applyDynamicFilters($baseFilters, $validated['activeFilters'] ?? null);

            $aggregations = [
                'trend_total_spend' => 'spend',
                'trend_total_clicks' => 'clicks',
                'trend_total_impressions' => 'impressions',
                'trend_average_ctr' => 'ctr',
                'trend_average_cpc' => 'cpc',
                'trend_total_results' => 'results',
                'trend_average_purchase_roas' => 'purchase_roas'
            ];

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

    public function table(Request $request)
    {
        try {
            $validated = $this->validateRequest($request);
            $tenant = Project::findOrFail($validated['tenant']);
            $service = app(RemoteEngineService::class);

            $aggregations = [
                'campaign_status' => 'campaign_status', // Not all tabs will have status but APIs Hub will ignore it or return null
                'spend' => 'spend',
                'clicks' => 'clicks',
                'impressions' => 'impressions',
                'ctr' => 'ctr',
                'cpc' => 'cpc',
                'results' => 'results',
                'purchase_roas' => 'purchase_roas',
            ];

            $accountFilter = [];
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

            if ($validated['activeTab'] === 'campaigns') {
                $tabPayload['groupBy'] = ['channeledCampaign'];
            } elseif ($validated['activeTab'] === 'adsets') {
                $tabPayload['groupBy'] = ['adGroup'];
            } elseif ($validated['activeTab'] === 'ads') {
                $tabPayload['groupBy'] = ['ad'];
            } elseif ($validated['activeTab'] === 'age') {
                $tabPayload['groupBy'] = ['age'];
            } elseif ($validated['activeTab'] === 'gender') {
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
                } elseif (isset($rowLower['gender'])) {
                    $row['id'] = $rowLower['gender'];
                    $row['name'] = ucfirst($rowLower['gender']);
                } else {
                    $row['id'] = 'Unknown';
                    $row['name'] = 'Unknown';
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
