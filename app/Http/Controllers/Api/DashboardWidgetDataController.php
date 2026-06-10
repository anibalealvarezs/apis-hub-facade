<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Dashboard;
use App\Models\DashboardWidget;
use App\Models\Project;
use App\Services\WidgetDataService;
use App\Services\Analytics\KpiPayloadBuilder;
use App\Services\RemoteEngineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardWidgetDataController extends Controller
{
    public function __construct(
        protected WidgetDataService $widgetDataService,
        protected RemoteEngineService $remoteEngineService,
    ) {}

    public function show(Request $request, DashboardWidget $widget): JsonResponse
    {
        $validated = $request->validate([
            'tenant' => 'required|string',
        ]);

        $project = Project::findOrFail($validated['tenant']);
        $dashboard = $widget->dashboard;

        if ($dashboard->project_id !== $project->id) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $user = $request->user();

        if (!$user || $user->cannot('view', $dashboard)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $dashboard = $widget->dashboard;

        $resolvedControls = $this->widgetDataService->resolveControls($dashboard, $widget);

        try {
            $data = match ($widget->source_type) {
                'kpi' => $this->handleKpiSource($project, $widget, $resolvedControls),
                'metric' => $this->handleMetricSource($project, $widget, $resolvedControls),
                'entity' => $this->handleEntitySource($project, $widget, $resolvedControls),
                default => throw new \InvalidArgumentException('Unknown source type: ' . $widget->source_type),
            };

            return response()->json([
                'success' => true,
                'widget_type' => $widget->widget_type,
                'source_type' => $widget->source_type,
                'data' => $data,
                'controls' => $resolvedControls,
            ]);
        } catch (\Throwable $e) {
            report($e);
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    protected function handleKpiSource(Project $project, DashboardWidget $widget, array $controls): array
    {
        $kpi = $widget->customKpi;
        if (!$kpi) {
            throw new \RuntimeException('Widget has no associated KPI');
        }

        $controlsHash = $this->widgetDataService->computeControlsHash($controls);
        $cached = $this->widgetDataService->getCachedResult($kpi->id, $controlsHash);
        if ($cached) {
            return $cached->result;
        }

        $payload = KpiPayloadBuilder::build(
            $kpi->calculation_type,
            array_merge($kpi->filters ?? [], [
                'dependent_channel' => $controls['channel'] ?? null,
                'dependent_asset_filter' => $controls['asset'] ?? null,
                'dependent_series' => [],
                'start_date' => $controls['date_start'] ?? null,
                'end_date' => $controls['date_end'] ?? null,
                'granularity' => $controls['granularity'] ?? 'daily',
            ]),
            [
                'start_date' => $controls['date_start'] ?? null,
                'end_date' => $controls['date_end'] ?? null,
                'granularity' => $controls['granularity'] ?? 'daily',
                'zero_handling' => $controls['zero_handling'] ?? 'remove',
            ]
        );

        $result = $this->remoteEngineService->computeKpi($project, $payload);

        if (!($result['success'] ?? false)) {
            throw new \RuntimeException($result['message'] ?? 'KPI computation failed');
        }

        $resultData = $result['data'] ?? [];

        $this->widgetDataService->cacheResult(
            $kpi->id, $project->id, $controlsHash, $resultData
        );

        return $resultData;
    }

    protected function handleMetricSource(Project $project, DashboardWidget $widget, array $controls): array
    {
        $config = $widget->source_config ?? [];
        $channel = $controls['channel'] ?? $config['channel'] ?? '';
        $metrics = $config['metrics'] ?? [];

        if (!$channel) {
            throw new \RuntimeException('No channel configured for metric widget');
        }

        $assetFilter = $controls['asset'] ?? $controls['assets'][0] ?? null;

        $dateStart = $controls['date_start'] ?? now()->subDays(30)->format('Y-m-d');
        $dateEnd = $controls['date_end'] ?? now()->format('Y-m-d');

        $payload = [
            'tenant' => $project->id,
            'account' => $assetFilter,
            'dateStart' => $dateStart,
            'dateEnd' => $dateEnd,
            'granularity' => $controls['granularity'] ?? 'daily',
            'metrics' => $metrics,
        ];

        return $this->forwardToChannelEndpoint($channel, 'summary', $payload);
    }

    protected function handleEntitySource(Project $project, DashboardWidget $widget, array $controls): array
    {
        $config = $widget->source_config ?? [];
        $channel = $controls['channel'] ?? $config['channel'] ?? '';
        $entityType = $config['entity_type'] ?? 'campaigns';
        $limit = $config['limit'] ?? 50;

        if (!$channel) {
            throw new \RuntimeException('No channel configured for entity widget');
        }

        $assetFilter = $controls['asset'] ?? $controls['assets'][0] ?? null;

        $dateStart = $controls['date_start'] ?? now()->subDays(30)->format('Y-m-d');
        $dateEnd = $controls['date_end'] ?? now()->format('Y-m-d');

        $payload = [
            'tenant' => $project->id,
            'account' => $assetFilter,
            'dateStart' => $dateStart,
            'dateEnd' => $dateEnd,
            'activeTab' => $entityType,
            'limit' => $limit,
        ];

        return $this->forwardToChannelEndpoint($channel, 'table', $payload);
    }

    protected function forwardToChannelEndpoint(string $channel, string $action, array $payload): array
    {
        $endpoints = [
            'facebook_marketing' => \App\Http\Controllers\Api\FacebookMarketingController::class,
            'facebook_organic' => \App\Http\Controllers\Api\FacebookOrganicController::class,
            'google_search_console' => \App\Http\Controllers\Api\GoogleSearchConsoleController::class,
        ];

        $controllerClass = $endpoints[$channel] ?? null;
        if (!$controllerClass) {
            throw new \RuntimeException("Unknown channel: {$channel}");
        }

        $controller = app($controllerClass);

        if (!method_exists($controller, $action)) {
            throw new \RuntimeException("Channel {$channel} does not support action {$action}");
        }

        $request = new Request($payload);
        $request->setUserResolver(fn () => auth()->user());

        $response = $controller->$action($request);

        if ($response instanceof \Illuminate\Http\JsonResponse) {
            $data = $response->getData(true);
            return $data['data'] ?? $data;
        }

        if (is_array($response)) {
            return $response;
        }

        return json_decode(json_encode($response), true);
    }
}
