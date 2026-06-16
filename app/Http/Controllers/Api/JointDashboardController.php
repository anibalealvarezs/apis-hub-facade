<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Services\RemoteEngineService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class JointDashboardController extends Controller
{
    protected RemoteEngineService $engine;

    public function __construct(RemoteEngineService $engine)
    {
        $this->engine = $engine;
    }

    public function fetchData(Request $request)
    {
        $tenantId = $request->header('X-Tenant');
        if (!$tenantId) {
            return response()->json(['error' => 'Missing tenant ID'], 400);
        }

        $tenant = Project::find($tenantId);
        if (!$tenant) {
            return response()->json(['error' => 'Tenant not found'], 404);
        }

        $curveA = $request->input('curveA');
        $curveB = $request->input('curveB');
        $dateStart = $request->input('dateStart');
        $dateEnd = $request->input('dateEnd');

        if (!$curveA || !$curveB || !$dateStart || !$dateEnd) {
            return response()->json(['error' => 'Missing required parameters'], 400);
        }

        $seriesA = $this->fetchSeries($tenant, $curveA, $dateStart, $dateEnd);
        $seriesB = $this->fetchSeries($tenant, $curveB, $dateStart, $dateEnd);

        // Calculate Correlation
        $correlation = null;
        if (!empty($seriesA['dates']) && !empty($seriesB['dates'])) {
            $correlationResponse = $this->engine->getCorrelation([
                'series_x' => [
                    'dates' => $seriesA['dates'],
                    'values' => $seriesA['values']
                ],
                'series_y' => [
                    'dates' => $seriesB['dates'],
                    'values' => $seriesB['values']
                ]
            ]);

            if (isset($correlationResponse['correlation_coefficient'])) {
                $correlation = $correlationResponse;
            }
        }

        return response()->json([
            'curveA' => $seriesA,
            'curveB' => $seriesB,
            'correlation' => $correlation
        ]);
    }

    protected function fetchSeries(Project $tenant, array $config, string $dateStart, string $dateEnd)
    {
        $channel = $config['channel'];
        $asset = $config['asset'];
        $metric = $config['metric'];

        // Build Payload
        $payload = [
            'type' => 'timeseries',
            'period' => 'daily',
            'dateRange' => [
                'start' => Carbon::parse($dateStart)->startOfDay()->toIso8601String(),
                'end' => Carbon::parse($dateEnd)->endOfDay()->toIso8601String()
            ],
            'metrics' => [$metric],
            'filters' => []
        ];

        // Apply specific entity filters based on channel
        $entity = 'channeled_account';
        
        if ($channel === 'facebook_marketing') {
            $payload['filters'][] = ['field' => 'account_id', 'operator' => '=', 'value' => $asset];
            $entity = 'campaign'; // Assuming metric is aggregated across campaigns for the account
        } else if ($channel === 'facebook_organic') {
            $payload['filters'][] = ['field' => 'channeledAccount', 'operator' => '=', 'value' => $asset];
            $entity = 'social_organic_post_snapshot'; // or lifetime
        } else if ($channel === 'google_search_console') {
            $payload['filters'][] = ['field' => 'channeledAccount', 'operator' => '=', 'value' => $asset];
            $entity = 'search_console_page_performance';
        }

        // Send to remote engine
        $response = $this->engine->aggregateChanneled($tenant, $channel, $entity, $payload);

        // Normalize response to { dates: [], values: [] }
        $dates = [];
        $values = [];

        // Generate full date range
        $periodStart = Carbon::parse($dateStart);
        $periodEnd = Carbon::parse($dateEnd);
        $periodDates = [];
        for ($date = $periodStart->copy(); $date->lte($periodEnd); $date->addDay()) {
            $periodDates[] = $date->format('Y-m-d');
        }

        if (isset($response['data']) && is_array($response['data'])) {
            $dataMap = [];
            foreach ($response['data'] as $row) {
                // Determine date key
                $d = null;
                if (isset($row['date_start'])) $d = substr($row['date_start'], 0, 10);
                elseif (isset($row['date'])) $d = substr($row['date'], 0, 10);
                elseif (isset($row['snapshot_date'])) $d = substr($row['snapshot_date'], 0, 10);

                if ($d) {
                    $val = floatval($row[$metric] ?? 0);
                    if (!isset($dataMap[$d])) {
                        $dataMap[$d] = $val;
                    } else {
                        $dataMap[$d] += $val;
                    }
                }
            }

            foreach ($periodDates as $d) {
                $dates[] = $d;
                $values[] = $dataMap[$d] ?? null;
            }
        } else {
            foreach ($periodDates as $d) {
                $dates[] = $d;
                $values[] = null;
            }
        }

        return [
            'name' => "{$config['channelName']} - {$metric}",
            'metric' => $metric,
            'dates' => $dates,
            'values' => $values
        ];
    }
}
