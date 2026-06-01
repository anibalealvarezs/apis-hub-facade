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
        return $request->validate([
            'tenant' => 'required|string',
            'account' => 'required|array',
            'account.*' => 'string|numeric',
            'dateStart' => 'required|date',
            'dateEnd' => 'required|date',
            'activeTab' => 'nullable|string|in:campaigns,adsets,ads,age_gender,gender',
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
            'age_gender' => 'age', // just an example, if they filter by age in age_gender tab
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
                'total_spend' => 'spend',
                'total_clicks' => 'clicks',
                'total_impressions' => 'impressions',
                'average_ctr' => 'ctr',
                'average_cpc' => 'cpc',
                'average_purchase_roas' => 'purchase_roas',
                'total_results' => 'results'
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

            \Illuminate\Support\Facades\Log::info("FBM Summary Payload: ", $payloads);
            // $results = $service->aggregateChanneledPool($tenant, 'facebook_marketing', 'metric', $payloads);
            // \Illuminate\Support\Facades\Log::info("FBM Summary Results: ", $results);
            
            // if (isset($results['summary']['status']) && $results['summary']['status'] === 'error') {
            //     \Illuminate\Support\Facades\Log::error("FBM Summary APIs Hub Error: " . json_encode($results['summary']));
            // }

            return response()->json([
                'summary' => [],
                'previous' => [],
                'debug_results' => ['payloads' => $payloads]
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

            // $results = $service->aggregateChanneledPool($tenant, 'facebook_marketing', 'metric', $payloads);

            // if (isset($results['chart']['status']) && $results['chart']['status'] === 'error') {
            //     \Illuminate\Support\Facades\Log::error("FBM Chart APIs Hub Error: " . json_encode($results['chart']));
            // }

            return response()->json([
                'chart' => [],
                'debug_results' => ['payloads' => $payloads]
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
                'total_spend' => 'spend',
                'total_clicks' => 'clicks',
                'total_impressions' => 'impressions',
                'average_ctr' => 'ctr',
                'average_cpc' => 'cpc',
                'total_results' => 'results',
                'average_purchase_roas' => 'purchase_roas',
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

            if ($validated['activeTab'] === 'campaigns') {
                $tabPayload['groupBy'] = ['channeledCampaign'];
            } elseif ($validated['activeTab'] === 'adsets') {
                $tabPayload['groupBy'] = ['adGroup_id', 'adGroup'];
            } elseif ($validated['activeTab'] === 'ads') {
                $tabPayload['groupBy'] = ['ad_id', 'ad']; // Assuming 'ad_id', 'ad'
            } elseif ($validated['activeTab'] === 'age_gender') {
                $tabPayload['groupBy'] = ['age', 'gender'];
            } elseif ($validated['activeTab'] === 'gender') {
                $tabPayload['groupBy'] = ['gender'];
            }

            $payloads = ['table' => $tabPayload];
            // $results = $service->aggregateChanneledPool($tenant, 'facebook_marketing', 'metric', $payloads);

            // if (isset($results['table']['status']) && $results['table']['status'] === 'error') {
            //     \Illuminate\Support\Facades\Log::error("FBM Table APIs Hub Error: " . json_encode($results['table']));
            // }

            $tableData = [];

            // Normalize ID and Name for frontend table rendering
            foreach ($tableData as &$row) {
                if (isset($row['channeledCampaign'])) {
                    $row['id'] = $row['channeledCampaign'];
                    $row['name'] = $row['channeledCampaign']; // We might need to map to actual name if returned
                } elseif (isset($row['adGroup_id'])) {
                    $row['id'] = $row['adGroup_id'];
                    $row['name'] = $row['adgroup'] ?? $row['adGroup'] ?? $row['adGroup_id'];
                } elseif (isset($row['ad_id'])) {
                    $row['id'] = $row['ad_id'];
                    $row['name'] = $row['ad'] ?? $row['ad_id'];
                } elseif (isset($row['age']) && isset($row['gender'])) {
                    $row['id'] = $row['age'] . '_' . $row['gender'];
                    $row['name'] = ucfirst($row['gender']) . ' / ' . $row['age'];
                } elseif (isset($row['gender'])) {
                    $row['id'] = $row['gender'];
                    $row['name'] = ucfirst($row['gender']);
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
