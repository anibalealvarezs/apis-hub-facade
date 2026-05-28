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
            'tenant' => 'required|string',
            'account' => 'required|string',
            'dateStart' => 'required|date',
            'dateEnd' => 'required|date',
            'activeTab' => 'nullable|string|in:queries,pages,countries,devices,appearances',
        ]);
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

            $payloads = [
                'summary' => [
                    'aggregations' => ['clicks' => 'clicks', 'impressions' => 'impressions', 'ctr' => 'ctr', 'position' => 'position'],
                    'groupBy' => [],
                    'filters' => [
                        // The original code passed 'page' filter, we maintain it if it was required, but GSC aggregates property-wide if no page filter. 
                        // Wait, if $validated['account'] is an ID, passing it to 'page' filter is wrong. We'll omit the page filter for property-wide stats.
                    ],
                    'startDate' => $validated['dateStart'],
                    'endDate' => $validated['dateEnd']
                ],
                'previous' => [
                    'aggregations' => ['clicks' => 'clicks', 'impressions' => 'impressions', 'ctr' => 'ctr', 'position' => 'position'],
                    'groupBy' => [],
                    'filters' => [],
                    'startDate' => $prevStart->format('Y-m-d'), 
                    'endDate' => $prevEnd->format('Y-m-d')
                ],
            ];

            $results = $service->aggregateChanneledPool($tenant, 'google_search_console', (int)$validated['account'], $payloads);

            return response()->json([
                'summary' => $results['summary']['data'][0] ?? [],
                'previous' => $results['previous']['data'][0] ?? [],
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

            $payloads = [
                'chart' => [
                    'aggregations' => ['clicks' => 'clicks', 'impressions' => 'impressions', 'ctr' => 'ctr', 'position' => 'position'],
                    'groupBy' => ['daily'], // or 'date' if daily fails
                    'filters' => [],
                    'startDate' => $validated['dateStart'],
                    'endDate' => $validated['dateEnd'],
                    'limit' => 1000 // ensure all days are returned
                ]
            ];

            $results = $service->aggregateChanneledPool($tenant, 'google_search_console', (int)$validated['account'], $payloads);

            return response()->json([
                'chart' => $results['chart']['data'] ?? [],
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

            $tabPayload = [
                'aggregations' => ['clicks' => 'clicks', 'impressions' => 'impressions', 'ctr' => 'ctr', 'position' => 'position'],
                'filters' => [],
                'startDate' => $validated['dateStart'],
                'endDate' => $validated['dateEnd'],
                'limit' => 5000 // reasonable limit for frontend rendering
            ];

            if ($validated['activeTab'] === 'queries') $tabPayload['groupBy'] = ['query'];
            elseif ($validated['activeTab'] === 'pages') $tabPayload['groupBy'] = ['dimensions.page'];
            elseif ($validated['activeTab'] === 'countries') $tabPayload['groupBy'] = ['country'];
            elseif ($validated['activeTab'] === 'devices') $tabPayload['groupBy'] = ['device'];
            elseif ($validated['activeTab'] === 'appearances') {
                $tabPayload['groupBy'] = ['dimensions.searchAppearance'];
                $tabPayload['filters']['dimensions.searchAppearance'] = ['operator' => 'not_equal', 'value' => 'standard'];
            }

            $payloads = ['table' => $tabPayload];
            $results = $service->aggregateChanneledPool($tenant, 'google_search_console', (int)$validated['account'], $payloads);

            $tableData = $results['table']['data'] ?? [];

            // Normalize ID for frontend
            foreach ($tableData as &$row) {
                if (isset($row['query'])) { $row['id'] = $row['query']; }
                elseif (isset($row['dimensions.page'])) { $row['id'] = $row['dimensions.page']; }
                elseif (isset($row['page'])) { $row['id'] = $row['page']; }
                elseif (isset($row['country'])) { $row['id'] = $row['country']; }
                elseif (isset($row['device'])) { $row['id'] = $row['device']; }
                elseif (isset($row['dimensions.searchAppearance'])) { $row['id'] = $row['dimensions.searchAppearance']; }
                elseif (isset($row['searchAppearance'])) { $row['id'] = $row['searchAppearance']; }
                else { $row['id'] = 'Unknown'; }
            }

            return response()->json([
                'table' => $tableData,
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("GSC Table Error: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
