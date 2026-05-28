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

            $tabPayload = [];
            if ($validated['activeTab'] === 'queries') $tabPayload['groupBy'] = ['query'];
            elseif ($validated['activeTab'] === 'pages') $tabPayload['groupBy'] = ['page'];
            elseif ($validated['activeTab'] === 'countries') $tabPayload['groupBy'] = ['country'];
            elseif ($validated['activeTab'] === 'devices') $tabPayload['groupBy'] = ['device'];
            elseif ($validated['activeTab'] === 'appearances') $tabPayload['groupBy'] = ['searchAppearance'];

            $tabPayload['startDate'] = $validated['dateStart'];
            $tabPayload['endDate'] = $validated['dateEnd'];

            $payloads = [
                'summary' => ['startDate' => $validated['dateStart'], 'endDate' => $validated['dateEnd']],
                'previous' => [
                    'startDate' => Carbon::parse($validated['dateStart'])->subDays(28)->format('Y-m-d'), 
                    'endDate' => Carbon::parse($validated['dateEnd'])->subDays(28)->format('Y-m-d')
                ],
                'chart' => ['startDate' => $validated['dateStart'], 'endDate' => $validated['dateEnd'], 'groupBy' => ['date']],
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
