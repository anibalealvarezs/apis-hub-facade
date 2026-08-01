<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Services\CollaboratorAssetAccessService;
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
            'format' => 'nullable|string|in:decimal,percentage,currency',
            'date_start' => 'nullable|date',
            'date_end' => 'nullable|date',
        ]);

        $project = Project::findOrFail($validated['project_id']);

        $access = app(CollaboratorAssetAccessService::class);
        $restricted = false;
        $userId = null;
        $user = $request->user();
        if ($user) {
            $userId = (int) $user->getAuthIdentifier();
            if (! $access->isProjectMember($project, $userId)) {
                return response()->json([
                    'success' => false,
                    'error' => 'access_denied',
                    'message' => 'You do not have access to this project.',
                ], 403);
            }
            $restricted = ! $access->isUnrestricted($project, $userId);
        }

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

            $validForChannel = $controller->getValidAssetsForChannel($project, $channel);

            if ($restricted) {
                $allowed = $access->getAllowedAssetIdsForChannel($project, $userId, $channel);
                $validForChannel = array_values(array_intersect($validForChannel, $allowed));
            }

            $extractedAssets = null;
            if ($assetFilter !== null && ! empty($assetFilter)) {
                $filtered = array_intersect($assetFilter, $validForChannel);
                $extractedAssets = ! empty($filtered) ? array_values($filtered) : null;
            } elseif (! empty($validForChannel)) {
                $extractedAssets = $validForChannel[0];
            }

            if ($restricted && $extractedAssets === null) {
                $fetchedSeries[$key] = [];
                continue;
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

        if ($restricted && empty(array_filter($fetchedSeries, fn ($s) => ! empty($s)))) {
            return response()->json([
                'success' => true,
                'data' => ['dates' => [], 'values' => []],
            ]);
        }

        $remoteEngineService = app(\App\Services\RemoteEngineService::class);
        $granularity = $validated['granularity'] ?? 'daily';

        $computePayload = [
            'ast' => $validated['ast'],
            'filters' => [
                'startDate' => $dateStart,
                'endDate' => $dateEnd,
                'period' => $granularity,
                'groupBy' => [$granularity],
            ],
            'series_data' => $fetchedSeries,
            'derived_metrics' => [],
        ];

        $result = $remoteEngineService->computeKpi($project, $computePayload);

        $data = $result['data'] ?? $result;

        if (is_array($data) && ! isset($data['dates'])) {
            if (isset($data['chart'])) {
                $chartData = $data['chart'];
                $dates = [];
                $values = [];
                foreach ($chartData as $point) {
                    $date = $point['date'] ?? $point['label'] ?? null;
                    $value = $point['value'] ?? $point['y'] ?? null;
                    if ($date !== null && is_numeric($value)) {
                        $dates[] = $date;
                        $values[] = (float) $value;
                    }
                }
                $data = ['dates' => $dates, 'values' => $values];
            } else {
                $dates = array_keys($data);
                $values = array_values($data);
                sort($dates);
                $sortedValues = array_map(fn ($d) => $data[$d], $dates);
                $data = ['dates' => $dates, 'values' => $sortedValues];
            }
        }

        $preview = $data;

        $previewFormat = $validated['format'] ?? null;
        if ($previewFormat === 'percentage' && isset($preview['values'])) {
            $preview['values'] = array_map(fn ($v) => is_numeric($v) ? (float) $v * 100 : $v, $preview['values']);
        }

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
