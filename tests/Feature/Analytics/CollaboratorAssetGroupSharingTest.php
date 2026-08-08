<?php

use App\Http\Controllers\Api\DashboardWidgetDataController;
use App\Models\AssetGroup;
use App\Models\AssetGroupItem;
use App\Models\CustomKpi;
use App\Models\Dashboard;
use App\Models\DashboardWidget;
use App\Models\DerivedMetric;
use App\Models\Project;
use App\Models\ProjectUserAssetGroup;
use App\Models\User;
use App\Services\CollaboratorAssetAccessService;
use App\Services\RemoteEngineService;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class TestableCollaboratorController extends DashboardWidgetDataController
{
    public function publicHandleKpiSource(Project $project, DashboardWidget $widget, array $controls): array
    {
        return $this->handleKpiSource($project, $widget, $controls);
    }

    public function publicExtractAssetFilter(array $controls, Project $project, string $channel, ?array $seriesAssetFilter = null): array|string|null
    {
        return $this->extractAssetFilter($controls, $project, $channel, $seriesAssetFilter);
    }
}

function agRole(string $name): Role
{
    return Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
}

function agProject(array $syncConfig = []): Project
{
    $project = Project::factory()->create([
        'subdomain' => 'ag-' . uniqid(),
        'sync_config' => $syncConfig,
    ]);

    test()->project = $project;

    return $project;
}

function agFbmSyncConfig(): array
{
    return [
        'facebook_marketing' => [
            'ad_accounts' => [
                ['id' => 'act_111', 'enabled' => true, 'lost_access' => false],
                ['id' => 'act_222', 'enabled' => true, 'lost_access' => false],
                ['id' => 'act_333', 'enabled' => false, 'lost_access' => false],
            ],
        ],
    ];
}

function agGroup(Project $project, array $items = []): AssetGroup
{
    $group = AssetGroup::create([
        'project_id' => $project->id,
        'name' => 'AG Group ' . uniqid(),
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

function agAddCollaborator(User $user, Project $project, string $role = 'project_user', bool $unrestricted = false, array $groupIds = []): void
{
    DB::table('project_user')->insertOrIgnore([
        'project_id' => $project->id,
        'user_id' => $user->id,
        'asset_access_unrestricted' => $unrestricted,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('model_has_roles')->insertOrIgnore([
        'role_id' => agRole($role)->id,
        'model_type' => User::class,
        'model_id' => $user->id,
        'project_id' => $project->id,
    ]);

    foreach ($groupIds as $groupId) {
        ProjectUserAssetGroup::firstOrCreate([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'asset_group_id' => $groupId,
        ]);
    }
}

function agWidget(Project $project, array $overrides = [], ?int $dashboardUserId = null): DashboardWidget
{
    $dashboard = Dashboard::create([
        'project_id' => $project->id,
        'user_id' => $dashboardUserId ?? $project->user_id,
        'name' => 'AG Dashboard',
        'is_public' => false,
        'is_default' => false,
    ]);

    return DashboardWidget::create(array_merge([
        'dashboard_id' => $dashboard->id,
        'name' => 'AG Widget',
        'source_type' => 'metric',
        'widget_type' => 'tile',
        'grid_x' => 0,
        'grid_y' => 0,
        'grid_w' => 3,
        'grid_h' => 2,
    ], $overrides));
}

function agKpi(Project $project, array $overrides = []): CustomKpi
{
    return CustomKpi::create(array_merge([
        'project_id' => $project->id,
        'name' => 'AG KPI',
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

function agDm(Project $project, array $overrides = []): DerivedMetric
{
    return DerivedMetric::create(array_merge([
        'project_id' => $project->id,
        'name' => 'AG DM',
        'ast' => ['type' => 'addition', 'values' => ['a']],
        'source_series' => [
            ['key' => 'a', 'channel' => 'facebook_marketing', 'metric' => 'spend'],
        ],
        'output_granularity' => 'daily',
        'is_active' => true,
    ], $overrides));
}

function agBindEngine(array $stubs = [], ?array $listData = null, ?callable $listChanneledCallback = null): stdClass
{
    $captured = new stdClass();
    $captured->aggregateChanneledPoolCalls = [];
    $captured->computeKpiCalls = [];
    $captured->listChanneledCalls = [];

    $mock = Mockery::mock(RemoteEngineService::class);
    if ($listChanneledCallback !== null) {
        $mock->shouldReceive('listChanneled')->andReturnUsing($listChanneledCallback);
    } else {
        $mock->shouldReceive('listChanneled')
            ->withArgs(function (...$args) use ($captured) {
                $captured->listChanneledCalls[] = $args;

                return true;
            })
            ->andReturn($listData ?? ['data' => []]);
    }

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
    $this->project = agProject(agFbmSyncConfig());
});

// ─── isUnrestricted ───

it('treats a user with no collaborator record as unrestricted', function () {
    $user = User::factory()->create();
    $service = app(CollaboratorAssetAccessService::class);

    expect($service->isUnrestricted($this->project, $user->id))->toBeTrue();
});

it('treats owners and editors as unrestricted even when the collaborator flag is off', function () {
    $service = app(CollaboratorAssetAccessService::class);

    $owner = User::factory()->create();
    agAddCollaborator($owner, $this->project, 'project_owner', false);
    expect($service->isUnrestricted($this->project, $owner->id))->toBeTrue();

    $editor = User::factory()->create();
    agAddCollaborator($editor, $this->project, 'project_editor', false);
    expect($service->isUnrestricted($this->project, $editor->id))->toBeTrue();
});

it('respects the unrestricted flag for a regular collaborator', function () {
    $service = app(CollaboratorAssetAccessService::class);

    $restricted = User::factory()->create();
    agAddCollaborator($restricted, $this->project, 'project_user', false);
    expect($service->isUnrestricted($this->project, $restricted->id))->toBeFalse();

    $unrestricted = User::factory()->create();
    agAddCollaborator($unrestricted, $this->project, 'project_user', true);
    expect($service->isUnrestricted($this->project, $unrestricted->id))->toBeTrue();
});

// ─── getAllowedAssetIdsForChannel / filterAllowedAssets ───

it('blocks every asset for a restricted collaborator with no shared groups', function () {
    $user = User::factory()->create();
    agAddCollaborator($user, $this->project, 'project_user', false);
    $service = app(CollaboratorAssetAccessService::class);

    expect($service->getAllowedAssetIdsForChannel($this->project, $user->id, 'facebook_marketing'))->toBe([]);
    expect($service->filterAllowedAssets($this->project, $user->id, 'facebook_marketing', ['act_111', 'act_222']))->toBe([]);
});

it('only allows assets present in the shared groups for a restricted collaborator', function () {
    $group = agGroup($this->project, ['facebook_marketing' => ['act_111']]);
    $user = User::factory()->create();
    agAddCollaborator($user, $this->project, 'project_user', false, [$group->id]);
    $service = app(CollaboratorAssetAccessService::class);

    expect($service->getAllowedAssetIdsForChannel($this->project, $user->id, 'facebook_marketing'))->toBe(['act_111']);
    expect($service->filterAllowedAssets($this->project, $user->id, 'facebook_marketing', ['act_111', 'act_222']))->toBe(['act_111']);
});

it('ignores shared group assets that are disabled in sync_config', function () {
    $group = agGroup($this->project, ['facebook_marketing' => ['act_111', 'act_333']]);
    $user = User::factory()->create();
    agAddCollaborator($user, $this->project, 'project_user', false, [$group->id]);
    $service = app(CollaboratorAssetAccessService::class);

    expect($service->getAllowedAssetIdsForChannel($this->project, $user->id, 'facebook_marketing'))->toBe(['act_111']);
});

it('lets an unrestricted collaborator see every enabled asset', function () {
    $user = User::factory()->create();
    agAddCollaborator($user, $this->project, 'project_user', true);
    $service = app(CollaboratorAssetAccessService::class);

    expect($service->getAllowedAssetIdsForChannel($this->project, $user->id, 'facebook_marketing'))
        ->toBe(['act_111', 'act_222']);
    expect($service->filterAllowedAssets($this->project, $user->id, 'facebook_marketing', ['act_111', 'act_222']))
        ->toBe(['act_111', 'act_222']);
});

// ─── getAllowedAssetGroupQuery / canAccessGroup / User::sharedAssetGroups ───

it('scopes asset group listings for restricted collaborators', function () {
    $shared = agGroup($this->project, ['facebook_marketing' => ['act_111']]);
    $hidden = agGroup($this->project, ['facebook_marketing' => ['act_222']]);
    $user = User::factory()->create();
    agAddCollaborator($user, $this->project, 'project_user', false, [$shared->id]);
    $service = app(CollaboratorAssetAccessService::class);

    $ids = $service->getAllowedAssetGroupQuery($this->project, $user->id)->pluck('id')->all();

    expect($ids)->toBe([$shared->id]);
    expect($service->canAccessGroup($this->project, $user->id, $shared->id))->toBeTrue();
    expect($service->canAccessGroup($this->project, $user->id, $hidden->id))->toBeFalse();
    expect($user->sharedAssetGroups($this->project)->pluck('asset_groups.id')->all())->toBe([$shared->id]);
});

it('does not scope asset group listings for unrestricted collaborators or owners', function () {
    $groupA = agGroup($this->project, ['facebook_marketing' => ['act_111']]);
    $groupB = agGroup($this->project, ['facebook_marketing' => ['act_222']]);
    $service = app(CollaboratorAssetAccessService::class);

    $unrestricted = User::factory()->create();
    agAddCollaborator($unrestricted, $this->project, 'project_user', true);
    expect($service->getAllowedAssetGroupQuery($this->project, $unrestricted->id)->pluck('id')->sort()->values()->all())
        ->toBe(collect([$groupA->id, $groupB->id])->sort()->values()->all());

    $owner = User::factory()->create();
    agAddCollaborator($owner, $this->project, 'project_owner', false);
    expect($service->canAccessGroup($this->project, $owner->id, $groupB->id))->toBeTrue();
});

// ─── extractAssetFilter enforcement ───

it('constrains a no-scope asset request to the shared assets for a restricted collaborator', function () {
    $group = agGroup($this->project, ['facebook_marketing' => ['act_111']]);
    $user = User::factory()->create();
    agAddCollaborator($user, $this->project, 'project_user', false, [$group->id]);
    $this->actingAs($user);
    $controller = app(TestableCollaboratorController::class);

    $filter = $controller->publicExtractAssetFilter([], $this->project, 'facebook_marketing');

    expect($filter)->toBe(['act_111']);
});

it('constrains a DM series asset_filter to the shared assets for a restricted collaborator', function () {
    $group = agGroup($this->project, ['facebook_marketing' => ['act_111']]);
    $user = User::factory()->create();
    agAddCollaborator($user, $this->project, 'project_user', false, [$group->id]);
    $this->actingAs($user);
    $controller = app(TestableCollaboratorController::class);

    $filter = $controller->publicExtractAssetFilter([], $this->project, 'facebook_marketing', ['act_222']);

    expect($filter)->toBe('___EMPTY_GROUP___');
});

it('intersects series_assets with the shared assets for a restricted collaborator', function () {
    $group = agGroup($this->project, ['facebook_marketing' => ['act_111']]);
    $user = User::factory()->create();
    agAddCollaborator($user, $this->project, 'project_user', false, [$group->id]);
    $this->actingAs($user);
    $controller = app(TestableCollaboratorController::class);

    $filter = $controller->publicExtractAssetFilter(
        ['series_assets' => ['dependent' => ['act_111', 'act_222']]],
        $this->project,
        'facebook_marketing'
    );

    expect($filter)->toBe(['act_111']);
});

// ─── HTTP show() enforcement ───

it('returns access_restricted when a restricted collaborator requests an unshared asset', function () {
    $group = agGroup($this->project, ['facebook_marketing' => ['act_111']]);
    $user = User::factory()->create();
    agAddCollaborator($user, $this->project, 'project_user', false, [$group->id]);
    $widget = agWidget($this->project, [
        'source_config' => ['channel' => 'facebook_marketing', 'metrics' => ['spend']],
    ], $user->id);
    agBindEngine(['kpi' => false]);

    $response = $this->actingAs($user)->postJson('/api/dashboard/widget/' . $widget->id . '/data', [
        'tenant' => $this->project->subdomain,
        'controls' => [
            'channel' => 'facebook_marketing',
            'metrics' => ['spend'],
            'assets' => ['act_222'],
            'date_start' => '2026-07-01',
            'date_end' => '2026-07-07',
            'granularity' => 'daily',
        ],
    ]);

    $response->assertStatus(403);
    $response->assertJson(['error' => 'access_restricted']);
});

it('returns access_restricted when a restricted collaborator has no shared groups', function () {
    $user = User::factory()->create();
    agAddCollaborator($user, $this->project, 'project_user', false);
    $widget = agWidget($this->project, [
        'source_config' => ['channel' => 'facebook_marketing', 'metrics' => ['spend']],
    ], $user->id);
    agBindEngine(['kpi' => false]);

    $response = $this->actingAs($user)->postJson('/api/dashboard/widget/' . $widget->id . '/data', [
        'tenant' => $this->project->subdomain,
        'controls' => [
            'channel' => 'facebook_marketing',
            'metrics' => ['spend'],
            'date_start' => '2026-07-01',
            'date_end' => '2026-07-07',
            'granularity' => 'daily',
        ],
    ]);

    $response->assertStatus(403);
    $response->assertJson(['error' => 'access_restricted']);
});

it('narrows a partial asset list to the shared assets before reaching the engine', function () {
    $group = agGroup($this->project, ['facebook_marketing' => ['act_111']]);
    $user = User::factory()->create();
    agAddCollaborator($user, $this->project, 'project_user', false, [$group->id]);
    $widget = agWidget($this->project, [
        'source_config' => ['channel' => 'facebook_marketing', 'metrics' => ['spend']],
    ], $user->id);
    $captured = agBindEngine(['kpi' => false]);

    $response = $this->actingAs($user)->postJson('/api/dashboard/widget/' . $widget->id . '/data', [
        'tenant' => $this->project->subdomain,
        'controls' => [
            'channel' => 'facebook_marketing',
            'metrics' => ['spend'],
            'assets' => ['act_111', 'act_222'],
            'date_start' => '2026-07-01',
            'date_end' => '2026-07-07',
            'granularity' => 'daily',
        ],
    ]);

    $response->assertOk();
    expect($captured->aggregateChanneledPoolCalls)->toHaveCount(1);
    expect($captured->aggregateChanneledPoolCalls[0][3]['chart']['filters']['channeledAccount'])
        ->toBe('act_111');
});

it('constrains an unscoped widget to the shared assets for a restricted collaborator', function () {
    $group = agGroup($this->project, ['facebook_marketing' => ['act_111']]);
    $user = User::factory()->create();
    agAddCollaborator($user, $this->project, 'project_user', false, [$group->id]);
    $widget = agWidget($this->project, [
        'source_config' => ['channel' => 'facebook_marketing', 'metrics' => ['spend']],
    ], $user->id);
    $captured = agBindEngine(['kpi' => false]);

    $response = $this->actingAs($user)->postJson('/api/dashboard/widget/' . $widget->id . '/data', [
        'tenant' => $this->project->subdomain,
        'controls' => [
            'channel' => 'facebook_marketing',
            'metrics' => ['spend'],
            'date_start' => '2026-07-01',
            'date_end' => '2026-07-07',
            'granularity' => 'daily',
        ],
    ]);

    $response->assertOk();
    expect($captured->aggregateChanneledPoolCalls)->toHaveCount(1);
    expect($captured->aggregateChanneledPoolCalls[0][3]['chart']['filters']['channeledAccount'])
        ->toBe('act_111');
});

// ─── KPI enforcement ───

it('constrains an unscoped KPI to the shared assets for a restricted collaborator', function () {
    $group = agGroup($this->project, ['facebook_marketing' => ['act_111']]);
    $user = User::factory()->create();
    agAddCollaborator($user, $this->project, 'project_user', false, [$group->id]);
    $this->actingAs($user);

    $kpi = agKpi($this->project);
    $widget = agWidget($this->project, [
        'custom_kpi_id' => $kpi->id,
        'source_type' => 'kpi',
        'widget_type' => 'line_chart',
    ]);
    $captured = agBindEngine(['chart' => false]);
    $controller = app(TestableCollaboratorController::class);

    $controller->publicHandleKpiSource($this->project, $widget, [
        'channel' => 'facebook_marketing',
        'metrics' => ['spend'],
        'date_start' => '2026-07-01',
        'date_end' => '2026-07-07',
        'granularity' => 'daily',
        'zero_handling' => 'remove',
    ]);

    expect($captured->computeKpiCalls)->toHaveCount(1);
    expect($captured->computeKpiCalls[0][1]['ast']['filters']['asset_platform_id'])->toBe(['act_111']);
});

it('short-circuits a KPI whose stored asset filter references unshared assets', function () {
    $group = agGroup($this->project, ['facebook_marketing' => ['act_111']]);
    $user = User::factory()->create();
    agAddCollaborator($user, $this->project, 'project_user', false, [$group->id]);
    $this->actingAs($user);

    $kpi = agKpi($this->project, [
        'filters' => [
            '_ui_state' => [
                'dependent_channel' => 'facebook_marketing',
                'dependent_metric' => 'spend',
                'granularity' => 'daily',
                'dependent_asset_filter' => ['act_222'],
            ],
        ],
    ]);
    $widget = agWidget($this->project, [
        'custom_kpi_id' => $kpi->id,
        'source_type' => 'kpi',
        'widget_type' => 'line_chart',
    ]);
    $captured = agBindEngine(['chart' => false, 'kpi' => false]);
    $controller = app(TestableCollaboratorController::class);

    $data = $controller->publicHandleKpiSource($this->project, $widget, [
        'channel' => 'facebook_marketing',
        'metrics' => ['spend'],
        'date_start' => '2026-07-01',
        'date_end' => '2026-07-07',
        'granularity' => 'daily',
        'zero_handling' => 'remove',
    ]);

    expect($captured->computeKpiCalls)->toBe([]);
    expect($data['labels'])->toBe([]);
});

// ─── Request-plane guard: channel endpoints ───

function agFboSyncConfig(): array
{
    return [
        'facebook_organic' => [
            'pages' => [
                ['id' => '111', 'enabled' => true, 'lost_access' => false],
                ['id' => '222', 'enabled' => true, 'lost_access' => false],
            ],
        ],
    ];
}

function agGscSyncConfig(): array
{
    return [
        'google_search_console' => [
            'assets' => [
                'sites' => [
                    ['url' => 'https://example.com/', 'enabled' => true, 'lost_access' => false],
                    ['url' => 'https://elsewhere.com/', 'enabled' => true, 'lost_access' => false],
                ],
            ],
        ],
    ];
}

function agGa4SyncConfig(): array
{
    return [
        'google_analytics' => [
            'properties' => [
                ['id' => '123456789', 'enabled' => true, 'lost_access' => false],
                ['id' => '987654321', 'enabled' => true, 'lost_access' => false],
            ],
        ],
    ];
}

it('blocks a restricted collaborator from an unshared FBM engine account', function () {
    $group = agGroup($this->project, ['facebook_marketing' => ['act_111']]);
    $user = User::factory()->create();
    agAddCollaborator($user, $this->project, 'project_user', false, [$group->id]);
    agBindEngine(['chart' => false, 'kpi' => false], [
        'data' => [
            ['id' => '101', 'platformId' => 'act_111', 'name' => 'Act 111'],
            ['id' => '202', 'platformId' => 'act_222', 'name' => 'Act 222'],
        ],
    ]);

    $response = $this->actingAs($user)->postJson('/api/fbm/chart', [
        'tenant' => $this->project->id,
        'account' => ['202'],
        'dateStart' => '2026-07-01',
        'dateEnd' => '2026-07-07',
    ]);

    $response->assertStatus(403);
    $response->assertJson(['error' => 'access_restricted']);
});

it('lets a restricted collaborator fetch a shared FBM engine account and narrows partial lists', function () {
    $group = agGroup($this->project, ['facebook_marketing' => ['act_111']]);
    $user = User::factory()->create();
    agAddCollaborator($user, $this->project, 'project_user', false, [$group->id]);
    $captured = agBindEngine(['kpi' => false], [
        'data' => [
            ['id' => '101', 'platformId' => 'act_111', 'name' => 'Act 111'],
            ['id' => '202', 'platformId' => 'act_222', 'name' => 'Act 222'],
        ],
    ]);

    $response = $this->actingAs($user)->postJson('/api/fbm/chart', [
        'tenant' => $this->project->id,
        'account' => ['101', '202'],
        'dateStart' => '2026-07-01',
        'dateEnd' => '2026-07-07',
    ]);

    $response->assertOk();
    expect($captured->aggregateChanneledPoolCalls)->toHaveCount(1);
    expect($captured->aggregateChanneledPoolCalls[0][3]['chart']['filters']['channeledAccount'])->toBe('101');
});

it('never calls the engine when a restricted collaborator with no shared assets requests channel data', function () {
    $user = User::factory()->create();
    agAddCollaborator($user, $this->project, 'project_user', false);
    agBindEngine(['chart' => false, 'kpi' => false], [
        'data' => [
            ['id' => '101', 'platformId' => 'act_111', 'name' => 'Act 111'],
        ],
    ]);

    $response = $this->actingAs($user)->postJson('/api/fbm/chart', [
        'tenant' => $this->project->id,
        'account' => ['101'],
        'dateStart' => '2026-07-01',
        'dateEnd' => '2026-07-07',
    ]);

    $response->assertStatus(403);
    $response->assertJson(['error' => 'access_restricted']);
});

it('blocks a non-member from fetching channel data for a project', function () {
    $user = User::factory()->create();
    agBindEngine(['chart' => false, 'kpi' => false]);

    $response = $this->actingAs($user)->postJson('/api/fbm/chart', [
        'tenant' => $this->project->id,
        'account' => ['101'],
        'dateStart' => '2026-07-01',
        'dateEnd' => '2026-07-07',
    ]);

    $response->assertStatus(403);
    $response->assertJson(['error' => 'access_denied']);
});

it('does not restrict owners and editors on channel endpoints', function () {
    $owner = User::factory()->create();
    agAddCollaborator($owner, $this->project, 'project_owner', false);
    $captured = agBindEngine(['kpi' => false], [
        'data' => [
            ['id' => '101', 'platformId' => 'act_111', 'name' => 'Act 111'],
        ],
    ]);

    $response = $this->actingAs($owner)->postJson('/api/fbm/chart', [
        'tenant' => $this->project->id,
        'account' => ['101'],
        'dateStart' => '2026-07-01',
        'dateEnd' => '2026-07-07',
    ]);

    $response->assertOk();
    expect($captured->aggregateChanneledPoolCalls)->toHaveCount(1);
    expect($captured->aggregateChanneledPoolCalls[0][3]['chart']['filters']['channeledAccount'])->toBe('101');
});

it('blocks a restricted collaborator from an unshared GSC engine account', function () {
    $this->project = agProject(agGscSyncConfig());
    $group = agGroup($this->project, ['google_search_console' => ['https://example.com/']]);
    $user = User::factory()->create();
    agAddCollaborator($user, $this->project, 'project_user', false, [$group->id]);
    agBindEngine(['chart' => false, 'kpi' => false], [
        'data' => [
            ['id' => 'g1', 'platformId' => md5('https://example.com/'), 'name' => 'Example'],
            ['id' => 'g2', 'platformId' => md5('https://elsewhere.com/'), 'name' => 'Elsewhere'],
        ],
    ]);

    $response = $this->actingAs($user)->postJson('/api/gsc/summary', [
        'tenant' => $this->project->id,
        'account' => 'g2',
        'dateStart' => '2026-07-01',
        'dateEnd' => '2026-07-07',
    ]);

    $response->assertStatus(403);
    $response->assertJson(['error' => 'access_restricted']);
});

it('allows a restricted collaborator to fetch a shared GSC engine account', function () {
    $this->project = agProject(agGscSyncConfig());
    $group = agGroup($this->project, ['google_search_console' => ['https://example.com/']]);
    $user = User::factory()->create();
    agAddCollaborator($user, $this->project, 'project_user', false, [$group->id]);
    $captured = agBindEngine(['kpi' => false], [
        'data' => [
            ['id' => 'g1', 'platformId' => md5('https://example.com/'), 'name' => 'Example'],
            ['id' => 'g2', 'platformId' => md5('https://elsewhere.com/'), 'name' => 'Elsewhere'],
        ],
    ]);

    $response = $this->actingAs($user)->postJson('/api/gsc/summary', [
        'tenant' => $this->project->id,
        'account' => 'g1',
        'dateStart' => '2026-07-01',
        'dateEnd' => '2026-07-07',
    ]);

    $response->assertOk();
    expect($captured->aggregateChanneledPoolCalls)->toHaveCount(1);
    expect($captured->aggregateChanneledPoolCalls[0][3]['summary']['filters']['channeledAccount'])->toBe('g1');
});

it('blocks a restricted collaborator from an unshared GA4 engine account', function () {
    $this->project = agProject(agGa4SyncConfig());
    $group = agGroup($this->project, ['google_analytics' => ['123456789']]);
    $user = User::factory()->create();
    agAddCollaborator($user, $this->project, 'project_user', false, [$group->id]);
    agBindEngine(['chart' => false, 'kpi' => false], [
        'data' => [
            ['id' => '55', 'platformId' => '123456789', 'name' => 'Prop A'],
            ['id' => '66', 'platformId' => '987654321', 'name' => 'Prop B'],
        ],
    ]);

    $response = $this->actingAs($user)->postJson('/api/ga4/summary', [
        'tenant' => $this->project->id,
        'account' => '66',
        'dateStart' => '2026-07-01',
        'dateEnd' => '2026-07-07',
    ]);

    $response->assertStatus(403);
    $response->assertJson(['error' => 'access_restricted']);
});

it('allows a restricted collaborator to fetch a shared GA4 engine account', function () {
    $this->project = agProject(agGa4SyncConfig());
    $group = agGroup($this->project, ['google_analytics' => ['123456789']]);
    $user = User::factory()->create();
    agAddCollaborator($user, $this->project, 'project_user', false, [$group->id]);
    $captured = agBindEngine(['kpi' => false], [
        'data' => [
            ['id' => '55', 'platformId' => '123456789', 'name' => 'Prop A'],
            ['id' => '66', 'platformId' => '987654321', 'name' => 'Prop B'],
        ],
    ]);

    $response = $this->actingAs($user)->postJson('/api/ga4/summary', [
        'tenant' => $this->project->id,
        'account' => '55',
        'dateStart' => '2026-07-01',
        'dateEnd' => '2026-07-07',
    ]);

    $response->assertOk();
    expect($captured->aggregateChanneledPoolCalls)->toHaveCount(1);
    expect($captured->aggregateChanneledPoolCalls[0][3]['summary_traffic_matrix']['filters']['channeledAccount'])->toBe('55');
});

it('allows an FBO composite whose page is shared and blocks one that is not', function () {
    $this->project = agProject(agFboSyncConfig());
    $group = agGroup($this->project, ['facebook_organic' => ['111']]);
    $user = User::factory()->create();
    agAddCollaborator($user, $this->project, 'project_user', false, [$group->id]);
    $captured = agBindEngine(['kpi' => false], [
        'data' => [
            ['id' => '301', 'platformId' => '111', 'name' => 'Page 111'],
            ['id' => '302', 'platformId' => 'ig999', 'name' => 'IG 999'],
            ['id' => '401', 'platformId' => '222', 'name' => 'Page 222'],
        ],
    ]);

    $blocked = $this->actingAs($user)->postJson('/api/fbo/summary', [
        'tenant' => $this->project->id,
        'account' => ['401|NONE|222|p888'],
        'dateStart' => '2026-07-01',
        'dateEnd' => '2026-07-07',
        'activeTab' => 'facebook',
    ]);
    $blocked->assertStatus(403);
    $blocked->assertJson(['error' => 'access_restricted']);

    $allowed = $this->actingAs($user)->postJson('/api/fbo/summary', [
        'tenant' => $this->project->id,
        'account' => ['301|302|111|p777'],
        'dateStart' => '2026-07-01',
        'dateEnd' => '2026-07-07',
        'activeTab' => 'facebook',
    ]);
    $allowed->assertOk();
    expect($captured->aggregateChanneledPoolCalls)->toHaveCount(1);
});

it('blocks a restricted collaborator from fetching an FBO post that is not shared', function () {
    $this->project = agProject(agFboSyncConfig());
    $group = agGroup($this->project, ['facebook_organic' => ['111']]);
    $user = User::factory()->create();
    agAddCollaborator($user, $this->project, 'project_user', false, [$group->id]);

    agBindEngine(['chart' => false, 'kpi' => false], null, function (...$args) {
        $entity = $args[2];
        if ($entity === 'post') {
            return ['data' => [['postId' => 'x1', 'page_platform_id' => '222', 'page_id' => 'p888']]];
        }

        return ['data' => [
            ['id' => '301', 'platformId' => '111', 'name' => 'Page 111'],
            ['id' => '401', 'platformId' => '222', 'name' => 'Page 222'],
        ]];
    });

    $response = $this->actingAs($user)->postJson('/api/fbo/post', [
        'tenant' => $this->project->id,
        'postId' => 'x1',
    ]);

    $response->assertStatus(403);
    $response->assertJson(['error' => 'access_restricted']);
});

it('allows a restricted collaborator to fetch an FBO post belonging to a shared page', function () {
    $this->project = agProject(agFboSyncConfig());
    $group = agGroup($this->project, ['facebook_organic' => ['111']]);
    $user = User::factory()->create();
    agAddCollaborator($user, $this->project, 'project_user', false, [$group->id]);

    agBindEngine(['chart' => false, 'kpi' => false], null, function (...$args) {
        $entity = $args[2];
        if ($entity === 'post') {
            return ['data' => [['postId' => 'x2', 'page_platform_id' => '111', 'page_id' => 'p777']]];
        }

        return ['data' => [
            ['id' => '301', 'platformId' => '111', 'name' => 'Page 111'],
        ]];
    });

    $response = $this->actingAs($user)->postJson('/api/fbo/post', [
        'tenant' => $this->project->id,
        'postId' => 'x2',
    ]);

    $response->assertOk();
    $response->assertJsonPath('post.postId', 'x2');
});

it('filters GA4 listProperties to shared properties for a restricted collaborator', function () {
    $this->project = agProject(agGa4SyncConfig());
    $group = agGroup($this->project, ['google_analytics' => ['123456789']]);
    $user = User::factory()->create();
    agAddCollaborator($user, $this->project, 'project_user', false, [$group->id]);

    agBindEngine(['chart' => false, 'kpi' => false], [
        'data' => [
            ['id' => '55', 'platformId' => '123456789', 'name' => 'Prop A'],
            ['id' => '66', 'platformId' => '987654321', 'name' => 'Prop B'],
        ],
    ]);

    $response = $this->actingAs($user)->postJson('/api/ga4/list-properties', [
        'tenant' => $this->project->id,
    ]);

    $response->assertOk();
    expect($response->json('properties'))->toHaveCount(1);
    expect($response->json('properties.0.platformId'))->toBe('123456789');
});

it('returns every enabled property in listProperties for unrestricted collaborators', function () {
    $this->project = agProject(agGa4SyncConfig());
    $owner = User::factory()->create();
    agAddCollaborator($owner, $this->project, 'project_owner', false);

    agBindEngine(['chart' => false, 'kpi' => false], [
        'data' => [
            ['id' => '55', 'platformId' => '123456789', 'name' => 'Prop A'],
            ['id' => '66', 'platformId' => '987654321', 'name' => 'Prop B'],
        ],
    ]);

    $response = $this->actingAs($owner)->postJson('/api/ga4/list-properties', [
        'tenant' => $this->project->id,
    ]);

    $response->assertOk();
    expect($response->json('properties'))->toHaveCount(2);
});

// ─── Derived metric preview enforcement ───

it('short-circuits derived metric preview for a restricted collaborator with unshared assets', function () {
    $group = agGroup($this->project, ['facebook_marketing' => ['act_111']]);
    $user = User::factory()->create();
    agAddCollaborator($user, $this->project, 'project_user', false, [$group->id]);
    $captured = agBindEngine(['chart' => false, 'kpi' => false], [
        'data' => [
            ['id' => '101', 'platformId' => 'act_111', 'name' => 'Act 111'],
        ],
    ]);

    $response = $this->actingAs($user)->postJson('/api/derived-metrics/preview', [
        'project_id' => $this->project->id,
        'ast' => ['operator' => 'add', 'operands' => [['series' => 'a'], ['series' => 'b']]],
        'source_series' => [
            ['key' => 'a', 'channel' => 'facebook_marketing', 'metric' => 'spend', 'asset_filter' => ['act_222']],
        ],
    ]);

    $response->assertOk();
    expect($response->json('success'))->toBeTrue();
    expect($response->json('data.dates'))->toBe([]);
    expect($captured->aggregateChanneledPoolCalls)->toBe([]);
    expect($captured->computeKpiCalls)->toBe([]);
});

it('keeps derived metric preview working for restricted collaborators with shared assets', function () {
    $group = agGroup($this->project, ['facebook_marketing' => ['act_111']]);
    $user = User::factory()->create();
    agAddCollaborator($user, $this->project, 'project_user', false, [$group->id]);
    $captured = agBindEngine(['kpi' => true], [
        'data' => [
            ['id' => '101', 'platformId' => 'act_111', 'name' => 'Act 111'],
        ],
    ]);

    $response = $this->actingAs($user)->postJson('/api/derived-metrics/preview', [
        'project_id' => $this->project->id,
        'ast' => ['operator' => 'add', 'operands' => [['series' => 'a'], ['series' => 'b']]],
        'source_series' => [
            ['key' => 'a', 'channel' => 'facebook_marketing', 'metric' => 'spend', 'asset_filter' => ['act_111']],
        ],
    ]);

    $response->assertOk();
    expect($response->json('success'))->toBeTrue();
    expect($captured->aggregateChanneledPoolCalls)->toHaveCount(1);
    expect($captured->computeKpiCalls)->toHaveCount(1);
});

// ─── Missing-assets marker (show) ───

it('flags a metric widget as missing assets when its group has no channel assets', function () {
    $group = agGroup($this->project, ['mailchimp' => ['x']]);
    $owner = User::factory()->create();
    agAddCollaborator($owner, $this->project, 'project_owner', false);
    $widget = agWidget($this->project, [
        'widget_type' => 'line_chart',
        'source_config' => ['channel' => 'facebook_marketing', 'metrics' => ['spend']],
    ]);
    $captured = agBindEngine(['chart' => false, 'kpi' => false]);

    $response = $this->actingAs($owner)->postJson('/api/dashboard/widget/' . $widget->id . '/data', [
        'tenant' => $this->project->subdomain,
        'controls' => [
            'channel' => 'facebook_marketing',
            'metrics' => ['spend'],
            'asset_group' => $group->id,
            'date_start' => '2026-07-01',
            'date_end' => '2026-07-07',
            'granularity' => 'daily',
        ],
    ]);

    $response->assertOk();
    $response->assertJsonPath('missing_assets', true);
    expect($captured->aggregateChanneledPoolCalls)->toBe([]);
    expect($captured->computeKpiCalls)->toBe([]);
});

it('flags a KPI widget as missing assets without calling the engine', function () {
    $group = agGroup($this->project, ['mailchimp' => ['x']]);
    $owner = User::factory()->create();
    agAddCollaborator($owner, $this->project, 'project_owner', false);
    $kpi = agKpi($this->project);
    $widget = agWidget($this->project, [
        'custom_kpi_id' => $kpi->id,
        'source_type' => 'kpi',
        'widget_type' => 'line_chart',
    ]);
    $captured = agBindEngine(['chart' => false, 'kpi' => false]);

    $response = $this->actingAs($owner)->postJson('/api/dashboard/widget/' . $widget->id . '/data', [
        'tenant' => $this->project->subdomain,
        'controls' => [
            'channel' => 'facebook_marketing',
            'metrics' => ['spend'],
            'asset_group' => $group->id,
            'date_start' => '2026-07-01',
            'date_end' => '2026-07-07',
            'granularity' => 'daily',
            'zero_handling' => 'remove',
        ],
    ]);

    $response->assertOk();
    $response->assertJsonPath('missing_assets', true);
    expect($captured->computeKpiCalls)->toBe([]);
});

it('flags a derived metric widget as missing assets while still running the compute request', function () {
    $group = agGroup($this->project, ['mailchimp' => ['x']]);
    $owner = User::factory()->create();
    agAddCollaborator($owner, $this->project, 'project_owner', false);
    $dm = agDm($this->project);
    $widget = agWidget($this->project, [
        'derived_metric_id' => $dm->id,
        'source_type' => 'derived_metric',
        'widget_type' => 'line_chart',
    ]);
    $captured = agBindEngine(['chart' => false]);

    $response = $this->actingAs($owner)->postJson('/api/dashboard/widget/' . $widget->id . '/data', [
        'tenant' => $this->project->subdomain,
        'controls' => [
            'asset_group' => $group->id,
            'date_start' => '2026-07-01',
            'date_end' => '2026-07-07',
            'granularity' => 'daily',
        ],
    ]);

    $response->assertOk();
    $response->assertJsonPath('missing_assets', true);
    expect($captured->aggregateChanneledPoolCalls)->toBe([]);
    expect($captured->computeKpiCalls)->toHaveCount(1);
});
