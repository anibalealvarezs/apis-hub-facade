<?php

use App\Http\Controllers\Api\DashboardWidgetDataController;
use App\Models\CustomKpi;
use App\Models\Dashboard;
use App\Models\DashboardWidget;
use App\Models\DerivedMetric;
use App\Models\Project;
use App\Models\User;
use App\Services\RemoteEngineService;
use App\Services\WidgetTypeRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WidgetIntegrityTestableController extends DashboardWidgetDataController
{
    public function publicHandleKpiSource(Project $project, DashboardWidget $widget, array $controls): array
    {
        return $this->handleKpiSource($project, $widget, $controls);
    }

    public function publicHandleMetricSource(Project $project, DashboardWidget $widget, array $controls): array
    {
        return $this->handleMetricSource($project, $widget, $controls);
    }

    public function publicHandleEntitySource(Project $project, DashboardWidget $widget, array $controls): array
    {
        return $this->handleEntitySource($project, $widget, $controls);
    }

    public function publicHandleDerivedMetricSource(Project $project, DashboardWidget $widget, array $controls): array
    {
        return $this->handleDerivedMetricSource($project, $widget, $controls);
    }
}

function makeWidProject(array $syncConfig = []): Project
{
    $project = Project::factory()->create([
        'subdomain' => 'wid-' . uniqid(),
        'sync_config' => $syncConfig,
    ]);

    test()->project = $project;

    return $project;
}

function widFbmSyncConfig(): array
{
    return [
        'facebook_marketing' => [
            'ad_accounts' => [
                ['id' => 'act_111', 'enabled' => true, 'lost_access' => false],
                ['id' => 'act_222', 'enabled' => true, 'lost_access' => false],
            ],
        ],
    ];
}

function widAllChannelsSyncConfig(): array
{
    return [
        'facebook_marketing' => [
            'ad_accounts' => [
                ['id' => 'act_111', 'enabled' => true, 'lost_access' => false],
            ],
        ],
        'facebook_organic' => [
            'pages' => [
                [
                    'platformId' => 'fb_1',
                    'pageId' => 'p1',
                    'fbAccountId' => 'acc1',
                    'igAccountId' => 'ig1',
                    'enabled' => true,
                    'lost_access' => false,
                ],
            ],
        ],
        'google_search_console' => [
            'sites' => [
                ['url' => 'https://example.com', 'enabled' => true, 'lost_access' => false],
            ],
        ],
        'google_analytics' => [
            'properties' => [
                ['id' => '123456', 'enabled' => true, 'lost_access' => false],
            ],
        ],
    ];
}

function makeWidWidget(Project $project, array $overrides = []): DashboardWidget
{
    $dashboard = Dashboard::create([
        'project_id' => $project->id,
        'user_id' => $project->user_id,
        'name' => 'WID Dashboard',
        'is_public' => true,
        'is_default' => false,
    ]);

    return DashboardWidget::create(array_merge([
        'dashboard_id' => $dashboard->id,
        'name' => 'WID Widget',
        'source_type' => 'metric',
        'widget_type' => 'tile',
        'grid_x' => 0,
        'grid_y' => 0,
        'grid_w' => 3,
        'grid_h' => 2,
    ], $overrides));
}

function makeWidKpi(Project $project, array $overrides = []): CustomKpi
{
    return CustomKpi::create(array_merge([
        'project_id' => $project->id,
        'name' => 'WID KPI',
        'calculation_type' => 'calculate_anomaly',
        'filters' => [
            '_ui_state' => [
                'dependent_channel' => 'facebook_marketing',
                'dependent_metric' => 'spend',
                'granularity' => 'daily',
            ],
        ],
        'ast' => [],
        'is_active' => true,
    ], $overrides));
}

function makeWidDm(Project $project, array $overrides = []): DerivedMetric
{
    return DerivedMetric::create(array_merge([
        'project_id' => $project->id,
        'name' => 'WID DM',
        'ast' => ['type' => 'addition', 'values' => ['a']],
        'source_series' => [
            ['key' => 'a', 'channel' => 'facebook_marketing', 'metric' => 'spend'],
        ],
        'output_granularity' => 'daily',
        'is_active' => true,
    ], $overrides));
}

/**
 * Binds a Mockery RemoteEngineService into the container and records every SDK
 * call so tests can assert the exact request objects and SDK methods used.
 *
 * The aggregateChanneledPool stub mimics the per-endpoint map the engine
 * returns: each key (chart / table / chart_<scope>) holds a
 * ['status' => ..., 'data' => [...]] envelope.
 */
function bindWidEngine(array $stubs = []): stdClass
{
    $captured = new stdClass();
    $captured->aggregateChanneledPoolCalls = [];
    $captured->aggregateChanneledCalls = [];
    $captured->computeKpiCalls = [];
    $captured->listChanneledCalls = [];

    $mock = Mockery::mock(RemoteEngineService::class);
    $mock->shouldReceive('listChanneled')
        ->withArgs(function (...$args) use ($captured) {
            $captured->listChanneledCalls[] = $args;

            return true;
        })
        ->andReturn(['data' => []]);

    $mock->shouldReceive('aggregateChanneled')
        ->withArgs(function (...$args) use ($captured) {
            $captured->aggregateChanneledCalls[] = $args;

            return true;
        })
        ->andReturn(['data' => []]);

    $poolShape = $stubs['pool'] ?? [
        'chart' => [
            'status' => 'success',
            'data' => [
                ['daily' => '2026-07-01', 'spend' => 10.0, 'clicks' => 100.0],
                ['daily' => '2026-07-02', 'spend' => 20.0, 'clicks' => 200.0],
            ],
        ],
        'table' => [
            'status' => 'success',
            'data' => [
                ['channeledCampaign' => 'Camp 1', 'channeledCampaign_id' => 'c1', 'spend' => 10.0],
                ['channeledCampaign' => 'Camp 2', 'channeledCampaign_id' => 'c2', 'spend' => 20.0],
            ],
        ],
    ];

    if ($stubs['chart'] ?? true) {
        $mock->shouldReceive('aggregateChanneledPool')
            ->withArgs(function (...$args) use ($captured) {
                $captured->aggregateChanneledPoolCalls[] = $args;

                return true;
            })
            ->andReturn($poolShape);
    } else {
        $mock->shouldReceive('aggregateChanneledPool')->never();
    }

    $kpiShape = $stubs['kpi_shape'] ?? [
        'success' => true,
        'data' => [
            'dates' => ['2026-07-01', '2026-07-02'],
            'values' => [1.0, 2.0],
        ],
    ];

    if ($stubs['kpi'] ?? true) {
        $mock->shouldReceive('computeKpi')
            ->withArgs(function (...$args) use ($captured) {
                $captured->computeKpiCalls[] = $args;

                return true;
            })
            ->andReturn($kpiShape);
    } else {
        $mock->shouldReceive('computeKpi')->never();
    }

    app()->instance(RemoteEngineService::class, $mock);

    return $captured;
}

function widShow(Project $project, DashboardWidget $widget, array $controls = []): JsonResponse
{
    $request = Request::create('/api/dashboard/widget/' . $widget->id . '/data', 'POST', [
        'tenant' => $project->subdomain,
        'controls' => $controls,
    ]);

    return app(WidgetIntegrityTestableController::class)->show($request, $widget);
}

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->project = makeWidProject(widFbmSyncConfig());
});

// ─── SDK METHOD SELECTION ──────────────────────────────────────────────

it('builds a KPI compute request and calls computeKpi only', function () {
    $kpi = makeWidKpi($this->project);
    $widget = makeWidWidget($this->project, [
        'custom_kpi_id' => $kpi->id,
        'source_type' => 'kpi',
        'widget_type' => 'line_chart',
    ]);
    $captured = bindWidEngine(['chart' => false]);

    $controller = app(WidgetIntegrityTestableController::class);
    $controller->publicHandleKpiSource($this->project, $widget, [
        'channel' => 'facebook_marketing',
        'metrics' => ['spend'],
        'date_start' => '2026-07-01',
        'date_end' => '2026-07-07',
        'granularity' => 'daily',
        'zero_handling' => 'remove',
    ]);

    expect($captured->computeKpiCalls)->toHaveCount(1);
    expect($captured->aggregateChanneledPoolCalls)->toBe([]);

    $payload = $captured->computeKpiCalls[0][1];
    expect($payload['ast']['type'])->toBe('metric');
    expect($payload['ast']['metric'])->toBe('facebook_marketing.spend');
    expect($payload['filters']['startDate'])->toBe('2026-07-01');
    expect($payload['filters']['endDate'])->toBe('2026-07-07');
    expect($payload['filters']['groupBy'])->toBe(['daily']);
    expect($payload['zero_handling'])->toBe('remove');
    expect($payload['calculate_anomaly'])->toBeTrue();
});

it('pre-fetches derived metric series data before the KPI compute request', function () {
    $dm = makeWidDm($this->project);
    $kpi = makeWidKpi($this->project, [
        'filters' => [
            '_ui_state' => [
                'dependent_source_type' => 'derived_metric',
                'dependent_dm_id' => $dm->id,
                'granularity' => 'daily',
            ],
        ],
    ]);
    $widget = makeWidWidget($this->project, [
        'custom_kpi_id' => $kpi->id,
        'source_type' => 'kpi',
        'widget_type' => 'line_chart',
    ]);
    $captured = bindWidEngine();

    $controller = app(WidgetIntegrityTestableController::class);
    $controller->publicHandleKpiSource($this->project, $widget, [
        'channel' => 'facebook_marketing',
        'assets' => ['act_111'],
        'activeTab' => 'campaigns',
        'date_start' => '2026-07-01',
        'date_end' => '2026-07-07',
        'granularity' => 'daily',
    ]);

    expect($captured->computeKpiCalls)->toHaveCount(2);
    expect($captured->aggregateChanneledPoolCalls)->toHaveCount(1);
    expect($captured->aggregateChanneledPoolCalls[0][1])->toBe('facebook_marketing');
    expect($captured->aggregateChanneledPoolCalls[0][2])->toBe('metric');

    $dmPayload = $captured->computeKpiCalls[0][1];
    expect($dmPayload['ast'])->toBeArray();
    expect($dmPayload['series_data']['a'])->toBe(['2026-07-01' => 10.0, '2026-07-02' => 20.0]);

    $kpiPayload = $captured->computeKpiCalls[1][1];
    expect($kpiPayload['ast']['metric'])->toBe('dm_' . $dm->id);
    expect($kpiPayload['series_data']['dm_' . $dm->id])->toBe(['2026-07-01' => 1.0, '2026-07-02' => 2.0]);
});

it('forwards metric widget requests through the channel chart endpoint', function () {
    $widget = makeWidWidget($this->project, [
        'source_type' => 'metric',
        'source_config' => ['channel' => 'facebook_marketing', 'metrics' => ['spend']],
    ]);
    $captured = bindWidEngine(['kpi' => false]);

    $controller = app(WidgetIntegrityTestableController::class);
    $data = $controller->publicHandleMetricSource($this->project, $widget, [
        'channel' => 'facebook_marketing',
        'metrics' => ['spend'],
        'assets' => ['act_111'],
        'date_start' => '2026-07-01',
        'date_end' => '2026-07-07',
        'granularity' => 'daily',
    ]);

    expect($data['chart'])->toHaveCount(2);
    expect($captured->computeKpiCalls)->toBe([]);
    expect($captured->aggregateChanneledPoolCalls)->toHaveCount(1);

    [, $channel, $entity, $payloads] = $captured->aggregateChanneledPoolCalls[0];
    expect($channel)->toBe('facebook_marketing');
    expect($entity)->toBe('metric');

    $chartPayload = $payloads['chart'];
    expect($chartPayload['filters']['channel'])->toBe('facebook_marketing');
    expect($chartPayload['filters']['channeledAccount'])->toBe('act_111');
    expect($chartPayload['groupBy'])->toBe(['daily']);
    expect($chartPayload['startDate'])->toBe('2026-07-01');
    expect($chartPayload['endDate'])->toBe('2026-07-07');
    expect($chartPayload['limit'])->toBe(1000);
});

it('forwards entity widget requests through the channel table endpoint', function () {
    $widget = makeWidWidget($this->project, [
        'source_type' => 'entity',
        'source_config' => ['channel' => 'facebook_marketing', 'entity_type' => 'campaigns'],
    ]);
    $captured = bindWidEngine(['kpi' => false]);

    $controller = app(WidgetIntegrityTestableController::class);
    $data = $controller->publicHandleEntitySource($this->project, $widget, [
        'channel' => 'facebook_marketing',
        'assets' => ['act_111'],
        'date_start' => '2026-07-01',
        'date_end' => '2026-07-07',
    ]);

    expect($data['table'])->toHaveCount(2);
    expect($captured->aggregateChanneledPoolCalls)->toHaveCount(1);

    [, $channel, $entity, $payloads] = $captured->aggregateChanneledPoolCalls[0];
    expect($channel)->toBe('facebook_marketing');
    expect($entity)->toBe('metric');
    expect($payloads['table']['groupBy'])->toBe(['channeledCampaign', 'campaign_status']);
    expect($payloads['table']['filters']['channeledAccount'])->toBe('act_111');
});

it('fetches each derived metric source series and then computes the formula', function () {
    $dm = makeWidDm($this->project);
    $widget = makeWidWidget($this->project, [
        'derived_metric_id' => $dm->id,
        'source_type' => 'derived_metric',
        'widget_type' => 'line_chart',
    ]);
    $captured = bindWidEngine();

    $controller = app(WidgetIntegrityTestableController::class);
    $data = $controller->publicHandleDerivedMetricSource($this->project, $widget, [
        'assets' => ['act_111'],
        'activeTab' => 'campaigns',
        'date_start' => '2026-07-01',
        'date_end' => '2026-07-07',
        'granularity' => 'daily',
    ]);

    expect($captured->aggregateChanneledPoolCalls)->toHaveCount(1);
    [, $channel, $entity, $payloads] = $captured->aggregateChanneledPoolCalls[0];
    expect($channel)->toBe('facebook_marketing');
    expect($entity)->toBe('metric');
    expect($payloads['chart']['groupBy'])->toBe(['daily']);
    expect($payloads['chart']['metrics'] ?? null)->toBeNull();

    expect($captured->computeKpiCalls)->toHaveCount(1);
    $computePayload = $captured->computeKpiCalls[0][1];
    expect($computePayload['ast'])->toBeArray();
    expect($computePayload['filters']['groupBy'])->toBe(['daily']);
    expect($computePayload['series_data']['a'])->toBe(['2026-07-01' => 10.0, '2026-07-02' => 20.0]);
    expect($computePayload['derived_metrics'])->toBe([]);

    expect($data['labels'])->toBe(['2026-07-01', '2026-07-02']);
    expect($data['datasets'][0]['label'])->toContain('(Result)');
});

// ─── CHANNEL DATA-EXPLORER ENDPOINTS ───────────────────────────────────

it('forwards metric requests to every channel endpoint with the channel account payload', function () {
    $project = makeWidProject(widAllChannelsSyncConfig());

    $channels = [
        [
            'channel' => 'facebook_marketing',
            'metrics' => ['spend'],
            'assets' => ['act_111'],
        ],
        [
            'channel' => 'facebook_organic',
            'metrics' => ['reach'],
            'assets' => ['fb_1'],
        ],
        [
            'channel' => 'google_search_console',
            'metrics' => ['clicks'],
            'assets' => ['https://example.com'],
        ],
        [
            'channel' => 'google_analytics',
            'metrics' => ['sessions'],
            'assets' => ['123456'],
            'pool' => [
                'chart_traffic_matrix' => [
                    'status' => 'success',
                    'data' => [['daily' => '2026-07-01', 'sessions' => 5.0]],
                ],
            ],
        ],
    ];

    foreach ($channels as $channelSpec) {
        $captured = bindWidEngine(['kpi' => false, 'pool' => $channelSpec['pool'] ?? null]);
        $widget = makeWidWidget($project, [
            'source_type' => 'metric',
            'source_config' => ['channel' => $channelSpec['channel'], 'metrics' => $channelSpec['metrics']],
        ]);

        $controller = app(WidgetIntegrityTestableController::class);
        $data = $controller->publicHandleMetricSource($project, $widget, [
            'channel' => $channelSpec['channel'],
            'metrics' => $channelSpec['metrics'],
            'assets' => $channelSpec['assets'],
            'date_start' => '2026-07-01',
            'date_end' => '2026-07-07',
            'granularity' => 'daily',
        ]);

        expect($captured->aggregateChanneledPoolCalls)->toHaveCount(1);
        [, $channel, $entity, $payloads] = $captured->aggregateChanneledPoolCalls[0];
        expect($channel)->toBe($channelSpec['channel']);
        expect($entity)->toBe('metric');
        expect($payloads)->not->toBeEmpty();
        expect($data)->toHaveKey('chart');
        expect($data['chart'])->toBeArray();
    }
});

it('normalizes Google Search Console table rows for the data explorer', function () {
    $project = makeWidProject(widAllChannelsSyncConfig());
    $widget = makeWidWidget($project, [
        'source_type' => 'metric',
        'widget_type' => 'line_chart',
        'source_config' => ['channel' => 'google_search_console', 'metrics' => ['clicks', 'impressions', 'ctr', 'position']],
    ]);
    $captured = bindWidEngine([
        'kpi' => false,
        'pool' => [
            'table' => [
                'status' => 'success',
                'data' => [
                    ['query' => 'keyword one', 'clicks' => 10, 'impressions' => 100, 'ctr' => 0.05, 'position' => 5],
                    ['query' => 'keyword two', 'clicks' => 20, 'impressions' => 200, 'ctr' => 0.03, 'position' => 3],
                ],
            ],
        ],
    ]);

    $controller = app(WidgetIntegrityTestableController::class);
    $data = $controller->publicHandleMetricSource($project, $widget, [
        'channel' => 'google_search_console',
        'metrics' => ['clicks', 'impressions', 'ctr', 'position'],
        'assets' => ['https://example.com'],
        'date_start' => '2026-07-01',
        'date_end' => '2026-07-07',
        'granularity' => 'queries',
    ]);

    expect($data['table'])->toHaveCount(2);
    expect($data['table'][0]['id'])->toBe('keyword one');
    expect($data['table'][0]['name'])->toBe('keyword one');
    expect($data['table'][0]['ctr'])->toBe(0.05);
    expect($data['table'][1]['id'])->toBe('keyword two');
});

it('merges Google Analytics chart rows by date across scopes', function () {
    $project = makeWidProject(widAllChannelsSyncConfig());
    $widget = makeWidWidget($project, [
        'source_type' => 'metric',
        'source_config' => ['channel' => 'google_analytics', 'metrics' => ['sessions']],
    ]);
    $captured = bindWidEngine([
        'kpi' => false,
        'pool' => [
            'chart_traffic_matrix' => [
                'status' => 'success',
                'data' => [
                    ['daily' => '2026-07-01', 'sessions' => 5.0],
                    ['daily' => '2026-07-02', 'sessions' => 9.0],
                ],
            ],
        ],
    ]);

    $controller = app(WidgetIntegrityTestableController::class);
    $data = $controller->publicHandleMetricSource($project, $widget, [
        'channel' => 'google_analytics',
        'metrics' => ['sessions'],
        'assets' => ['123456'],
        'date_start' => '2026-07-01',
        'date_end' => '2026-07-07',
        'granularity' => 'daily',
    ]);

    expect($data['chart'])->toHaveCount(2);
    expect($data['chart'][0]['daily'])->toBe('2026-07-01');
    expect($data['chart'][0]['sessions'])->toBe(5);
});

// ─── WIDGET CONFIG MATRIX + END-TO-END ────────────────────────────────

it('produces a successful request object for every widget source and type combination', function () {
    $sourceTypes = ['kpi', 'metric', 'derived_metric'];

    foreach ($sourceTypes as $sourceType) {
        foreach (WidgetTypeRegistry::getWidgetTypesForSource($sourceType) as $widgetType) {
            $kpi = $sourceType === 'kpi' ? makeWidKpi($this->project) : null;
            $dm = $sourceType === 'derived_metric' ? makeWidDm($this->project) : null;

            $widget = makeWidWidget($this->project, [
                'custom_kpi_id' => $kpi?->id,
                'derived_metric_id' => $dm?->id,
                'source_type' => $sourceType,
                'widget_type' => $widgetType,
                'source_config' => ['channel' => 'facebook_marketing', 'metrics' => ['spend']],
            ]);

            $captured = bindWidEngine([
                'chart' => $sourceType !== 'kpi',
                'kpi' => $sourceType !== 'metric',
            ]);

            $controls = [
                'channel' => 'facebook_marketing',
                'metrics' => ['spend'],
                'assets' => ['act_111'],
                'activeTab' => 'campaigns',
                'date_start' => '2026-07-01',
                'date_end' => '2026-07-07',
                'granularity' => 'daily',
            ];

            $controller = app(WidgetIntegrityTestableController::class);
            $data = match ($sourceType) {
                'kpi' => $controller->publicHandleKpiSource($this->project, $widget, $controls),
                'metric' => $controller->publicHandleMetricSource($this->project, $widget, $controls),
                'derived_metric' => $controller->publicHandleDerivedMetricSource($this->project, $widget, $controls),
            };

            expect($data)->toBeArray();

            $expectedSdkCalls = $sourceType === 'kpi' ? 'computeKpiCalls' : 'aggregateChanneledPoolCalls';
            expect($captured->{$expectedSdkCalls})->toHaveCount(1);
        }
    }
});

it('translates the engine response through the widget endpoint for a metric widget', function () {
    $widget = makeWidWidget($this->project, [
        'source_type' => 'metric',
        'widget_type' => 'tile',
        'source_config' => ['channel' => 'facebook_marketing', 'metrics' => ['spend']],
    ]);
    bindWidEngine(['kpi' => false]);

    $response = widShow($this->project, $widget, [
        'channel' => 'facebook_marketing',
        'metrics' => ['spend'],
        'assets' => ['act_111'],
        'date_start' => '2026-07-01',
        'date_end' => '2026-07-07',
        'granularity' => 'daily',
    ]);

    $payload = $response->getData(true);

    expect($response->getStatusCode())->toBe(200);
    expect($payload['success'])->toBeTrue();
    expect($payload['source_type'])->toBe('metric');
    expect($payload['widget_type'])->toBe('tile');
    expect($payload['data']['value'])->toBe(20);
    expect($payload['data']['previous'])->toBe(10);
});

it('returns a chart dataset for a derived metric widget through the widget endpoint', function () {
    $dm = makeWidDm($this->project);
    $widget = makeWidWidget($this->project, [
        'derived_metric_id' => $dm->id,
        'source_type' => 'derived_metric',
        'widget_type' => 'line_chart',
    ]);
    bindWidEngine();

    $response = widShow($this->project, $widget, [
        'channel' => 'facebook_marketing',
        'assets' => ['act_111'],
        'activeTab' => 'campaigns',
        'date_start' => '2026-07-01',
        'date_end' => '2026-07-07',
        'granularity' => 'daily',
    ]);

    $payload = $response->getData(true);

    expect($response->getStatusCode())->toBe(200);
    expect($payload['success'])->toBeTrue();
    expect($payload['source_type'])->toBe('derived_metric');
    expect($payload['widget_type'])->toBe('line_chart');
    expect($payload['data']['labels'])->toBe(['2026-07-01', '2026-07-02']);
    expect($payload['data']['datasets'][0]['data'])->toBe([1, 2]);
});

it('applies per-variable series_dependencies for KPI widgets and isolates scopes', function () {
    $kpi = makeWidKpi($this->project, [
        'filters' => [
            '_ui_state' => [
                'dependent_channel' => 'facebook_organic',
                'dependent_metric' => '',
                'granularity' => 'daily',
                'independent_variables' => [
                    [
                        'independent_channel' => 'facebook_organic',
                        'independent_metric' => '',
                    ],
                ],
            ],
        ],
    ]);

    $widget = makeWidWidget($this->project, [
        'custom_kpi_id' => $kpi->id,
        'source_type' => 'kpi',
        'widget_type' => 'scatter_plot',
    ]);

    $captured = bindWidEngine(['chart' => false]);

    $controller = app(WidgetIntegrityTestableController::class);
    $controller->publicHandleKpiSource($this->project, $widget, [
        'metrics' => ['reach', 'impressions'],
        'series_dependencies' => [
            'dependent' => 'instagram_account',
            'independent_0' => 'facebook_page',
        ],
    ]);

    expect($captured->computeKpiCalls)->toHaveCount(1);
    $kpiPayload = $captured->computeKpiCalls[0][1];

    expect($kpiPayload)->toHaveKey('ast');
    expect($kpiPayload['ast']['filters']['account_type'] ?? null)->toBe('instagram_account');
});
