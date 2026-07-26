<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DerivedMetricPreviewController extends Controller
{
    public function preview(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'project_id' => 'required|integer',
            'ast' => 'required|array',
            'source_series' => 'required|array|min:1',
            'source_series.*.key' => 'required|string',
            'source_series.*.channel' => 'required|string',
            'source_series.*.metric' => 'required|string',
            'source_series.*.asset_filter' => 'nullable|array',
            'granularity' => 'nullable|string',
            'date_start' => 'nullable|date',
            'date_end' => 'nullable|date',
        ]);

        $project = Project::findOrFail($validated['project_id']);

        $dateStart = $validated['date_start'] ?? now()->subDays(30)->format('Y-m-d');
        $dateEnd = $validated['date_end'] ?? now()->format('Y-m-d');
        $granularity = $validated['granularity'] ?? 'daily';

        $fetchedSeries = [];

        foreach ($validated['source_series'] as $series) {
            $key = $series['key'];
            $channel = $series['channel'];
            $metric = $series['metric'];
            $assetFilter = $series['asset_filter'] ?? null;

            $controller = new DashboardWidgetDataController(
                app(\App\Services\WidgetDataService::class),
                app(\App\Services\RemoteEngineService::class)
            );

            $extractedAssets = null;
            if ($assetFilter !== null && ! empty($assetFilter)) {
                $validForChannel = $controller->getValidAssetsForChannel($project, $channel);
                $filtered = array_intersect($assetFilter, $validForChannel);
                $extractedAssets = ! empty($filtered) ? array_values($filtered) : null;
            }

            $payload = [
                'tenant' => $project->id,
                'account' => $extractedAssets,
                'dateStart' => $dateStart,
                'dateEnd' => $dateEnd,
                'granularity' => $series['granularity'] ?? $granularity,
                'metrics' => [$metric],
            ];

            try {
                $channelResponse = $controller->forwardToChannelEndpoint($channel, 'chart', $payload);
                $seriesData = $controller->extractTimeSeriesFromResponse($channelResponse, $metric);
                $fetchedSeries[$key] = $seriesData;
            } catch (\Throwable $e) {
                $fetchedSeries[$key] = [];
            }
        }

        $context = new \Services\Analytics\VirtualMetricEngine\EvaluationContext($fetchedSeries);

        $parser = new \Services\Analytics\VirtualMetricEngine\AstParser();
        $node = $parser->parse($validated['ast']);
        $result = $node->evaluate($context);

        if (is_array($result) && ! isset($result['dates'])) {
            $dates = array_keys($result);
            $values = array_values($result);
            sort($dates);
            $sortedValues = array_map(fn ($d) => $result[$d], $dates);
            $result = ['dates' => $dates, 'values' => $sortedValues];
        }

        $preview = $result;
        if (isset($preview['dates']) && count($preview['dates']) > 30) {
            $preview['dates'] = array_slice($preview['dates'], 0, 30);
            $preview['values'] = array_slice($preview['values'], 0, 30);
        }

        return response()->json([
            'success' => true,
            'data' => $preview,
        ]);
    }
}
