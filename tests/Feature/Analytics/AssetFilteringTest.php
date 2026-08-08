<?php

use App\Http\Controllers\Api\DashboardWidgetDataController;
use App\Models\AssetGroup;
use App\Models\AssetGroupItem;
use App\Models\CustomKpi;
use App\Models\Dashboard;
use App\Models\DashboardWidget;
use App\Models\DerivedMetric;
use App\Models\Project;
use App\Models\User;
use App\Services\RemoteEngineService;

class TestableDashboardWidgetDataController extends DashboardWidgetDataController
{
    public function publicHandleKpiSource(Project $project, DashboardWidget $widget, array $controls): array
    {
        return $this->handleKpiSource($project, $widget, $controls);
    }

    public function publicHandleMetricSource(Project $project, DashboardWidget $widget, array $controls): array
    {
        return $this->handleMetricSource($project, $widget, $controls);
    }

    public function publicHandleDerivedMetricSource(Project $project, DashboardWidget $widget, array $controls): array
    {
        return $this->handleDerivedMetricSource($project, $widget, $controls);
    }

    public function publicExtractAssetFilter(array $controls, Project $project, string $channel, ?array $seriesAssetFilter = null): array|string|null
    {
        return $this->extractAssetFilter($controls, $project, $channel, $seriesAssetFilter);
    }
}

function makeAfProject(array $syncConfig = []): Project
{
    $project = Project::factory()->create([
        'subdomain' => 'af-' . uniqid(),
        'sync_config' => $syncConfig,
    ]);

    test()->project = $project;

    return $project;
}

function afFbmSyncConfig(): array
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

function makeAfGroup(Project $project, array $items = []): AssetGroup
{
    $group = AssetGroup::create([
        'project_id' => $project->id,
        'name' => 'AF Group ' . uniqid(),
    ]);

    foreach ($items as $channel => $assetIds) {
        foreach ($assetIds as $assetId) {
            AssetGroupItem::create([
                'asset_group_id' => $group->id,
                'channel' => $channel,
                'asset_id' => $assetId,
            ]);
        }
    }

    return $group;
}

function makeAfWidget(Project $project, array $overrides = []): DashboardWidget
{
    $dashboard = Dashboard::create([
        'project_id' => $project->id,
        'user_id' => $project->user_id,
        'name' => 'AF Dashboard',
        'is_public' => false,
        'is_default' => false,
    ]);

    return DashboardWidget::create(array_merge([
        'dashboard_id' => $dashboard->id,
        'name' => 'AF Widget',
        'source_type' => 'metric',
        'widget_type' => 'tile',
        'grid_x' => 0,
        'grid_y' => 0,
        'grid_w' => 3,
        'grid_h' => 2,
    ], $overrides));
}

function makeAfKpi(Project $project, array $overrides = []): CustomKpi
{
    return CustomKpi::create(array_merge([
        'project_id' => $project->id,
        'name' => 'AF KPI',
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

function makeAfDm(Project $project, array $overrides = []): DerivedMetric
{
    return DerivedMetric::create(array_merge([
        'project_id' => $project->id,
        'name' => 'AF DM',
        'ast' => ['type' => 'addition', 'values' => ['a']],
        'source_series' => [
            ['key' => 'a', 'channel' => 'facebook_marketing', 'metric' => 'spend'],
        ],
        'output_granularity' => 'daily',
        'is_active' => true,
    ], $overrides));
}

/**
 * Binds a Mockery RemoteEngineService into the container and records every
 * call to aggregateChanneledPool / computeKpi so tests can assert which asset
 * filters actually reached the engine.
 */
function bindAfEngine(array $stubs = []): stdClass
{
    $captured = new stdClass();
    $captured->aggregateChanneledPoolCalls = [];
    $captured->computeKpiCalls = [];

    $mock = Mockery::mock(RemoteEngineService::class);
    $mock->shouldReceive('listChanneled')->andReturn(['data' => []]);

    if ($stubs['chart'] ?? true) {
        $mock->shouldReceive('aggregateChanneledPool')
            ->withArgs(function (...$args) use ($captured) {
                $captured->aggregateChanneledPoolCalls[] = $args;

                return true;
            })
            ->andReturn([
                'chart' => [
                    'status' => 'success',
                    'data' => [
                        ['daily' => '2026-07-01', 'spend' => 10.0],
                        ['daily' => '2026-07-02', 'spend' => 20.0],
                    ],
                ],
            ]);
    } else {
        $mock->shouldReceive('aggregateChanneledPool')->never();
    }

    if ($stubs['kpi'] ?? true) {
        $mock->shouldReceive('computeKpi')
            ->withArgs(function (...$args) use ($captured) {
                $captured->computeKpiCalls[] = $args;

                return true;
            })
            ->andReturn(['success' => true, 'data' => ['dates' => ['2026-07-01'], 'values' => [1.0]]]);
    } else {
        $mock->shouldReceive('computeKpi')->never();
    }

    app()->instance(RemoteEngineService::class, $mock);

    return $captured;
}

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
    $this->project = makeAfProject(afFbmSyncConfig());
});

// ─── REQ-009 / HIER-004: asset group resolution in extractAssetFilter ───

it('resolves asset_group items to the enabled channel assets for multi-asset channels', function () {
    $group = makeAfGroup($this->project, ['facebook_marketing' => ['act_111', 'act_222']]);
    $controller = app(TestableDashboardWidgetDataController::class);

    $filter = $controller->publicExtractAssetFilter(['asset_group' => $group->id], $this->project, 'facebook_marketing');

    expect($filter)->toBe(['act_111', 'act_222']);
});

it('returns a single asset id for channels that do not allow multiple assets', function () {
    $this->project->update(['sync_config' => [
        'google_search_console' => [
            'sites' => [['url' => 'https://example.com', 'enabled' => true, 'lost_access' => false]],
        ],
    ]]);
    $group = makeAfGroup($this->project, ['google_search_console' => ['https://example.com']]);
    $controller = app(TestableDashboardWidgetDataController::class);

    $filter = $controller->publicExtractAssetFilter(['asset_group' => $group->id], $this->project, 'google_search_console');

    expect($filter)->toBe('https://example.com');
});

it('returns ___EMPTY_GROUP___ when the group has no items for the channel', function () {
    $group = makeAfGroup($this->project, ['facebook_marketing' => ['act_111']]);
    $controller = app(TestableDashboardWidgetDataController::class);

    $filter = $controller->publicExtractAssetFilter(['asset_group' => $group->id], $this->project, 'google_search_console');

    expect($filter)->toBe('___EMPTY_GROUP___');
});

it('returns ___EMPTY_GROUP___ when every group asset is disabled in sync_config', function () {
    $group = makeAfGroup($this->project, ['facebook_marketing' => ['act_333']]);
    $controller = app(TestableDashboardWidgetDataController::class);

    $filter = $controller->publicExtractAssetFilter(['asset_group' => $group->id], $this->project, 'facebook_marketing');

    expect($filter)->toBe('___EMPTY_GROUP___');
});

// ─── REQ-009: asset group cannot be bypassed with a foreign / missing group ───

it('does not leak assets when the asset_group belongs to another project', function () {
    $otherProject = Project::factory()->create(['sync_config' => afFbmSyncConfig()]);
    $foreignGroup = makeAfGroup($otherProject, ['facebook_marketing' => ['act_111']]);
    $controller = app(TestableDashboardWidgetDataController::class);

    $filter = $controller->publicExtractAssetFilter(['asset_group' => $foreignGroup->id], $this->project, 'facebook_marketing');

    expect($filter)->toBe('___EMPTY_GROUP___');
});

it('returns ___EMPTY_GROUP___ for a non-existent asset_group reference', function () {
    $controller = app(TestableDashboardWidgetDataController::class);

    $filter = $controller->publicExtractAssetFilter(['asset_group' => 999999], $this->project, 'facebook_marketing');

    expect($filter)->toBe('___EMPTY_GROUP___');
});

it('returns ___EMPTY_GROUP___ when controls reference assets not enabled in sync_config', function () {
    $controller = app(TestableDashboardWidgetDataController::class);

    $filter = $controller->publicExtractAssetFilter(['assets' => ['act_999']], $this->project, 'facebook_marketing');

    expect($filter)->toBe('___EMPTY_GROUP___');
});

it('intersects a series asset filter with the valid channel assets', function () {
    $controller = app(TestableDashboardWidgetDataController::class);

    $filter = $controller->publicExtractAssetFilter(
        ['assets' => ['act_111', 'act_222']],
        $this->project,
        'facebook_marketing',
        ['act_222', 'act_999']
    );

    expect($filter)->toBe(['act_222']);
});

// ─── HIER-005 / D2-VIEW: metric widgets forward only the group assets to the engine ───

it('sends only the configured asset group assets to the engine for metric widgets', function () {
    $group = makeAfGroup($this->project, ['facebook_marketing' => ['act_111', 'act_222']]);
    $widget = makeAfWidget($this->project, [
        'source_type' => 'metric',
        'source_config' => ['channel' => 'facebook_marketing', 'metrics' => ['spend']],
    ]);
    $captured = bindAfEngine(['kpi' => false]);

    $controller = app(TestableDashboardWidgetDataController::class);
    $data = $controller->publicHandleMetricSource($this->project, $widget, [
        'channel' => 'facebook_marketing',
        'metrics' => ['spend'],
        'asset_group' => $group->id,
        'date_start' => '2026-07-01',
        'date_end' => '2026-07-07',
        'granularity' => 'daily',
    ]);

    expect($data['chart'])->toHaveCount(2);
    expect($captured->aggregateChanneledPoolCalls)->toHaveCount(1);
    expect($captured->aggregateChanneledPoolCalls[0][3]['chart']['filters']['channeledAccount'])
        ->toBe(['operator' => 'in', 'value' => ['act_111', 'act_222']]);
});

it('returns an empty result without calling the engine when a metric widget group has no assets', function () {
    $group = makeAfGroup($this->project, ['mailchimp' => ['x']]);
    $widget = makeAfWidget($this->project, ['source_type' => 'metric']);
    $captured = bindAfEngine(['chart' => false, 'kpi' => false]);

    $controller = app(TestableDashboardWidgetDataController::class);
    $data = $controller->publicHandleMetricSource($this->project, $widget, [
        'channel' => 'facebook_marketing',
        'metrics' => ['spend'],
        'asset_group' => $group->id,
        'date_start' => '2026-07-01',
        'date_end' => '2026-07-07',
        'granularity' => 'daily',
    ]);

    expect($data)->toBe(['columns' => [], 'rows' => [], 'chart' => [], 'summary' => []]);
    expect($captured->aggregateChanneledPoolCalls)->toBe([]);
});

// ─── HIER-002 / HIER-003: derived metric source series inherit the asset group ───

it('passes the asset group through to the engine for every derived metric source series', function () {
    $group = makeAfGroup($this->project, ['facebook_marketing' => ['act_111', 'act_222']]);
    $dm = makeAfDm($this->project);
    $widget = makeAfWidget($this->project, [
        'derived_metric_id' => $dm->id,
        'source_type' => 'derived_metric',
        'widget_type' => 'line_chart',
    ]);
    $captured = bindAfEngine();

    $controller = app(TestableDashboardWidgetDataController::class);
    $data = $controller->publicHandleDerivedMetricSource($this->project, $widget, [
        'date_start' => '2026-07-01',
        'date_end' => '2026-07-07',
        'granularity' => 'daily',
        'asset_group' => $group->id,
    ]);

    expect($data['labels'])->toBe(['2026-07-01']);
    expect($captured->aggregateChanneledPoolCalls)->toHaveCount(1);
    expect($captured->aggregateChanneledPoolCalls[0][3]['chart']['filters']['channeledAccount'])
        ->toBe(['operator' => 'in', 'value' => ['act_111', 'act_222']]);
});

it('enforces the DM source series asset_filter when no group is configured', function () {
    $dm = makeAfDm($this->project, [
        'source_series' => [
            ['key' => 'a', 'channel' => 'facebook_marketing', 'metric' => 'spend', 'asset_filter' => ['act_222']],
        ],
    ]);
    $widget = makeAfWidget($this->project, [
        'derived_metric_id' => $dm->id,
        'source_type' => 'derived_metric',
        'widget_type' => 'line_chart',
    ]);
    $captured = bindAfEngine();

    $controller = app(TestableDashboardWidgetDataController::class);
    $controller->publicHandleDerivedMetricSource($this->project, $widget, [
        'date_start' => '2026-07-01',
        'date_end' => '2026-07-07',
        'granularity' => 'daily',
    ]);

    expect($captured->aggregateChanneledPoolCalls[0][3]['chart']['filters']['channeledAccount'])
        ->toBe('act_222');
});

it('lets the view series_assets dm_N override narrow the DM series filter', function () {
    $dm = makeAfDm($this->project, [
        'source_series' => [
            ['key' => 'a', 'channel' => 'facebook_marketing', 'metric' => 'spend', 'asset_filter' => ['act_222', 'act_111']],
        ],
    ]);
    $widget = makeAfWidget($this->project, [
        'derived_metric_id' => $dm->id,
        'source_type' => 'derived_metric',
        'widget_type' => 'line_chart',
    ]);
    $captured = bindAfEngine();

    $controller = app(TestableDashboardWidgetDataController::class);
    $controller->publicHandleDerivedMetricSource($this->project, $widget, [
        'date_start' => '2026-07-01',
        'date_end' => '2026-07-07',
        'granularity' => 'daily',
        'series_assets' => ['dm_0' => ['act_111']],
    ]);

    expect($captured->aggregateChanneledPoolCalls[0][3]['chart']['filters']['channeledAccount'])
        ->toBe('act_111');
});

it('never fetches series data when the derived metric group yields no assets for its channel', function () {
    $group = makeAfGroup($this->project, ['mailchimp' => ['x']]);
    $dm = makeAfDm($this->project);
    $widget = makeAfWidget($this->project, [
        'derived_metric_id' => $dm->id,
        'source_type' => 'derived_metric',
        'widget_type' => 'line_chart',
    ]);
    $captured = bindAfEngine(['chart' => false]);

    $controller = app(TestableDashboardWidgetDataController::class);
    $data = $controller->publicHandleDerivedMetricSource($this->project, $widget, [
        'date_start' => '2026-07-01',
        'date_end' => '2026-07-07',
        'granularity' => 'daily',
        'asset_group' => $group->id,
    ]);

    expect($captured->aggregateChanneledPoolCalls)->toBe([]);
    expect($captured->computeKpiCalls[0][1]['series_data']['a'])->toBe([]);
    expect($data['labels'])->toBe(['2026-07-01']);
});

// ─── REQ-009: a foreign group must not leak all assets through a derived metric ───

it('does not leak all assets when a derived metric references a foreign asset group', function () {
    $otherProject = Project::factory()->create(['sync_config' => afFbmSyncConfig()]);
    $foreignGroup = makeAfGroup($otherProject, ['facebook_marketing' => ['act_111']]);
    $dm = makeAfDm($this->project);
    $widget = makeAfWidget($this->project, [
        'derived_metric_id' => $dm->id,
        'source_type' => 'derived_metric',
        'widget_type' => 'line_chart',
    ]);
    $captured = bindAfEngine(['chart' => false]);

    $controller = app(TestableDashboardWidgetDataController::class);
    $controller->publicHandleDerivedMetricSource($this->project, $widget, [
        'date_start' => '2026-07-01',
        'date_end' => '2026-07-07',
        'granularity' => 'daily',
        'asset_group' => $foreignGroup->id,
    ]);

    expect($captured->aggregateChanneledPoolCalls)->toBe([]);
    expect($captured->computeKpiCalls[0][1]['series_data']['a'])->toBe([]);
});

// ─── HIER-004: KPI dependent / independent asset groups reach the payload ───

it('applies the dependent asset group to the KPI payload', function () {
    $group = makeAfGroup($this->project, ['facebook_marketing' => ['act_111', 'act_222']]);
    $kpi = makeAfKpi($this->project);
    $widget = makeAfWidget($this->project, [
        'custom_kpi_id' => $kpi->id,
        'source_type' => 'kpi',
        'widget_type' => 'line_chart',
    ]);
    $captured = bindAfEngine(['chart' => false]);

    $controller = app(TestableDashboardWidgetDataController::class);
    $controller->publicHandleKpiSource($this->project, $widget, [
        'channel' => 'facebook_marketing',
        'metrics' => ['spend'],
        'asset_group' => $group->id,
        'date_start' => '2026-07-01',
        'date_end' => '2026-07-07',
        'granularity' => 'daily',
        'zero_handling' => 'remove',
    ]);

    expect($captured->computeKpiCalls)->toHaveCount(1);
    $ast = $captured->computeKpiCalls[0][1]['ast'];
    expect($ast['type'])->toBe('metric');
    expect($ast['filters']['asset_platform_id'])->toBe(['act_111', 'act_222']);
});

it('applies an independent variable asset group to the KPI payload', function () {
    $group = makeAfGroup($this->project, ['facebook_marketing' => ['act_222']]);
    $kpi = makeAfKpi($this->project, [
        'calculation_type' => 'calculate_regression',
        'filters' => [
            '_ui_state' => [
                'dependent_channel' => 'facebook_marketing',
                'dependent_metric' => 'spend',
                'granularity' => 'daily',
                'independent_variables' => [
                    ['key' => 'x', 'independent_channel' => 'facebook_marketing', 'independent_metric' => 'clicks'],
                ],
            ],
        ],
    ]);
    $widget = makeAfWidget($this->project, [
        'custom_kpi_id' => $kpi->id,
        'source_type' => 'kpi',
        'widget_type' => 'line_chart',
    ]);
    $captured = bindAfEngine(['chart' => false]);

    $controller = app(TestableDashboardWidgetDataController::class);
    $controller->publicHandleKpiSource($this->project, $widget, [
        'channel' => 'facebook_marketing',
        'metrics' => ['spend', 'clicks'],
        'series_asset_groups' => ['independent_0' => $group->id],
        'date_start' => '2026-07-01',
        'date_end' => '2026-07-07',
        'granularity' => 'daily',
        'zero_handling' => 'remove',
    ]);

    expect($captured->computeKpiCalls)->toHaveCount(1);
    $ast = $captured->computeKpiCalls[0][1]['ast'];
    expect($ast['type'])->toBe('operator');
    expect($ast['right']['filters']['asset_platform_id'])->toBe(['act_222']);
});

it('short-circuits without calling the engine when a KPI group yields no assets', function () {
    $group = makeAfGroup($this->project, ['mailchimp' => ['x']]);
    $kpi = makeAfKpi($this->project);
    $widget = makeAfWidget($this->project, [
        'custom_kpi_id' => $kpi->id,
        'source_type' => 'kpi',
        'widget_type' => 'line_chart',
    ]);
    $captured = bindAfEngine(['chart' => false, 'kpi' => false]);

    $controller = app(TestableDashboardWidgetDataController::class);
    $data = $controller->publicHandleKpiSource($this->project, $widget, [
        'channel' => 'facebook_marketing',
        'metrics' => ['spend'],
        'asset_group' => $group->id,
        'date_start' => '2026-07-01',
        'date_end' => '2026-07-07',
        'granularity' => 'daily',
        'zero_handling' => 'remove',
    ]);

    expect($captured->computeKpiCalls)->toBe([]);
    expect($data['labels'])->toBe([]);
    expect($data['_debug'])->toContain('No available assets');
});
