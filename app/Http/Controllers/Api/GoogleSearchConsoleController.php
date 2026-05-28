<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Services\RemoteEngineService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class GoogleSearchConsoleController extends Controller
{
    public function report(Request $request)
    {
        try {
            $validated = $request->validate([
                'tenant' => 'required|string',
                'account' => 'required|string',
                'dateStart' => 'required|date',
                'dateEnd' => 'required|date',
                'activeTab' => 'required|string|in:queries,pages,countries,devices,appearances',
            ]);

            $tenant = Project::findOrFail($validated['tenant']);
            $service = app(RemoteEngineService::class);

            $tabPayload = [
                'aggregations' => ['clicks' => 'clicks', 'impressions' => 'impressions', 'ctr' => 'ctr', 'position' => 'position'],
                'filters' => [
                    'page' => (string)$validated['account'],
                    'dimensions.searchAppearance' => 'standard'
                ],
                'startDate' => $validated['dateStart'],
                'endDate' => $validated['dateEnd']
            ];

            if ($validated['activeTab'] === 'queries') $tabPayload['groupBy'] = ['query'];
            elseif ($validated['activeTab'] === 'pages') $tabPayload['groupBy'] = ['page'];
            elseif ($validated['activeTab'] === 'countries') $tabPayload['groupBy'] = ['country'];
            elseif ($validated['activeTab'] === 'devices') $tabPayload['groupBy'] = ['device'];
            elseif ($validated['activeTab'] === 'appearances') {
                $tabPayload['groupBy'] = ['dimensions.searchAppearance'];
                $tabPayload['filters']['dimensions.searchAppearance'] = ['operator' => 'not_equal', 'value' => 'standard'];
            }

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
                        'page' => (string)$validated['account'],
                        'dimensions.searchAppearance' => 'standard'
                    ],
                    'startDate' => $validated['dateStart'],
                    'endDate' => $validated['dateEnd']
                ],
                'previous' => [
                    'aggregations' => ['clicks' => 'clicks', 'impressions' => 'impressions', 'ctr' => 'ctr', 'position' => 'position'],
                    'groupBy' => [],
                    'filters' => [
                        'page' => (string)$validated['account'],
                        'dimensions.searchAppearance' => 'standard'
                    ],
                    'startDate' => $prevStart->format('Y-m-d'), 
                    'endDate' => $prevEnd->format('Y-m-d')
                ],
                'chart' => [
                    'aggregations' => ['clicks' => 'clicks', 'impressions' => 'impressions', 'ctr' => 'ctr', 'position' => 'position'],
                    'groupBy' => ['daily'],
                    'filters' => [
                        'page' => (string)$validated['account'],
                        'dimensions.searchAppearance' => 'standard'
                    ],
                    'startDate' => $validated['dateStart'],
                    'endDate' => $validated['dateEnd']
                ],
                'table' => $tabPayload
            ];

            $results = $service->aggregateChanneledPool($tenant, 'google_search_console', 'metric', $payloads);

            return response()->json([
                'summary' => $results['summary']['data'][0] ?? [],
                'previous' => $results['previous']['data'][0] ?? [],
                'chart' => $results['chart']['data'] ?? [],
                'table' => $results['table']['data'] ?? [],
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("GSC Controller Error: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
