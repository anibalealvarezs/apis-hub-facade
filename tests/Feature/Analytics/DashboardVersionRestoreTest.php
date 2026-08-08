<?php

use App\Models\Dashboard;
use App\Models\DashboardWidget;
use App\Models\Project;
use App\Models\User;
use App\Services\DashboardService;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->project = Project::factory()->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user);

    $this->service = app(DashboardService::class);
});

function makeDashboard(): Dashboard
{
    return Dashboard::create([
        'project_id' => test()->project->id,
        'user_id' => test()->user->id,
        'name' => 'Test Dashboard',
        'is_public' => false,
        'is_default' => false,
    ]);
}

function makeWidget(Dashboard $dashboard, array $overrides = []): DashboardWidget
{
    return DashboardWidget::create(array_merge([
        'dashboard_id' => $dashboard->id,
        'name' => 'Test Widget',
        'source_type' => 'kpi',
        'widget_type' => 'counter',
        'grid_x' => 0,
        'grid_y' => 0,
        'grid_w' => 3,
        'grid_h' => 2,
    ], $overrides));
}

// ─── DASHV-001: Dashboard version snapshots widgets + their versions ───

it('captures widget_ids and widget_version_ids when a dashboard version is saved', function () {
    $dashboard = makeDashboard();
    $w1 = makeWidget($dashboard, ['name' => 'W1']);
    $w2 = makeWidget($dashboard, ['name' => 'W2']);

    $dashboard->createVersion('Snapshot 1');

    $version = $dashboard->versions()->latest('version_number')->first();
    expect($version->widget_ids)->toBe([$w1->id, $w2->id]);
    expect($version->widget_version_ids)->toHaveKeys([$w1->id, $w2->id]);
});

// ─── DASHV-008: widget without a version gets an on-the-fly Initial snapshot ───

it('creates an Initial snapshot version for widgets that have none during dashboard version save', function () {
    $dashboard = makeDashboard();
    $w1 = makeWidget($dashboard, ['name' => 'W1']);

    $w1->versions()->delete();

    $dashboard->createVersion('Snapshot 1');

    expect($w1->versions()->count())->toBe(1);
    expect($w1->versions()->first()->change_summary)->toBe('Initial snapshot');

    $version = $dashboard->versions()->latest('version_number')->first();
    expect($version->widget_version_ids)->toHaveKey($w1->id);
});

// ─── WID-008: saveLayout persists grid and creates a widget version ───

it('persists grid layout and auto-creates a widget version on saveLayout', function () {
    $dashboard = makeDashboard();
    $w1 = makeWidget($dashboard, ['name' => 'W1']);

    expect($w1->versions()->count())->toBe(1);

    $this->service->saveLayout($dashboard, [
        ['id' => $w1->id, 'x' => 2, 'y' => 3, 'w' => 4, 'h' => 1],
    ]);

    $w1->refresh();
    expect($w1->grid_x)->toBe(2);
    expect($w1->grid_y)->toBe(3);
    expect($w1->grid_w)->toBe(4);
    expect($w1->grid_h)->toBe(1);

    expect($w1->versions()->count())->toBe(2);
    expect($w1->versions()->latest('version_number')->first()->grid_x)->toBe(2);
    expect($w1->versions()->latest('version_number')->first()->grid_w)->toBe(4);

    expect($dashboard->fresh()->grid_layout)->toBe([
        ['id' => $w1->id, 'x' => 2, 'y' => 3, 'w' => 4, 'h' => 1],
    ]);
});

// ─── DASHV-003/004: full restore reconciles extra / missing / changed widgets ───

it('restores a full dashboard version: deletes extra, restores trashed, reverts changed widgets', function () {
    $dashboard = makeDashboard();
    $w1 = makeWidget($dashboard, ['name' => 'W1', 'grid_x' => 0, 'grid_y' => 0, 'grid_w' => 3, 'grid_h' => 2]);
    $w2 = makeWidget($dashboard, ['name' => 'W2']);
    $w3 = makeWidget($dashboard, ['name' => 'W3']);

    $dashboard->createVersion('Snapshot 1');

    $snapshotVersion = $dashboard->versions()->latest('version_number')->first();

    // Mutate state after snapshot 1
    $w4 = makeWidget($dashboard, ['name' => 'W4']);
    $w1->delete();
    $w2->update(['grid_x' => 6, 'grid_y' => 4, 'grid_w' => 5, 'grid_h' => 3]);
    $dashboard->update(['name' => 'Changed Name']);

    $dashboard->createVersion('Snapshot 2');

    $dashboard->restoreFullVersion($snapshotVersion);

    $dashboard->refresh();

    // Dashboard fields reverted
    expect($dashboard->name)->toBe('Test Dashboard');

    // Extra widget soft-deleted
    $w4Fresh = DashboardWidget::withTrashed()->find($w4->id);
    expect($w4Fresh)->not->toBeNull();
    expect($w4Fresh->deleted_at)->not->toBeNull();

    // Missing widget restored from trash
    $w1Fresh = DashboardWidget::withTrashed()->find($w1->id);
    expect($w1Fresh->deleted_at)->toBeNull();

    // Changed widget reverted to its snapshot version
    $w2Fresh = DashboardWidget::find($w2->id);
    expect($w2Fresh->grid_x)->toBe(0);
    expect($w2Fresh->grid_y)->toBe(0);
    expect($w2Fresh->grid_w)->toBe(3);
    expect($w2Fresh->grid_h)->toBe(2);

    // Unchanged widget untouched
    $w3Fresh = DashboardWidget::find($w3->id);
    expect($w3Fresh->deleted_at)->toBeNull();
});

// ─── DASHV-005: restore does not auto-create a version ───

it('does not create a new dashboard or widget version during restore', function () {
    $dashboard = makeDashboard();
    $w1 = makeWidget($dashboard, ['name' => 'W1']);

    $dashboard->createVersion('Snapshot 1');
    $snapshotVersion = $dashboard->versions()->latest('version_number')->first();

    $dashboard->update(['name' => 'Changed']);
    $widgetVersionsBefore = $w1->versions()->count();

    $dashboard->createVersion('Snapshot 2');

    $dashboard->restoreFullVersion($snapshotVersion);

    expect($dashboard->versions()->count())->toBe(3); // Created + Snapshot 1 + Snapshot 2
    expect($w1->versions()->count())->toBe($widgetVersionsBefore);
});

// ─── DASHV-002: hasUnsavedChanges reflects divergence after restore ───

it('reports unsaved changes after a full restore to an older version', function () {
    $dashboard = makeDashboard();
    makeWidget($dashboard, ['name' => 'W1']);

    $dashboard->createVersion('Snapshot 1');
    $snapshotVersion = $dashboard->versions()->latest('version_number')->first();

    $dashboard->update(['name' => 'Changed Name']);
    makeWidget($dashboard, ['name' => 'W2']);
    $dashboard->createVersion('Snapshot 2');

    $dashboard->restoreFullVersion($snapshotVersion);
    $dashboard->refresh();

    // Latest version is Snapshot 2 which differs from current restored state
    expect($dashboard->hasUnsavedChanges())->toBeTrue();
});

it('reports no unsaved changes when state matches the latest version', function () {
    $dashboard = makeDashboard();
    makeWidget($dashboard, ['name' => 'W1']);

    $dashboard->createVersion('Snapshot 1');

    $dashboard->refresh();
    expect($dashboard->hasUnsavedChanges())->toBeFalse();
});

// ─── Restore skips widget reconciliation when widget_ids is null ───

it('skips widget reconciliation when the version has null widget_ids', function () {
    $dashboard = makeDashboard();

    $legacyVersion = $dashboard->versions()->first();
    $legacyVersion->update(['widget_ids' => null]);

    $w1 = makeWidget($dashboard, ['name' => 'W1']);

    $dashboard->restoreFullVersion($legacyVersion);

    expect(DashboardWidget::find($w1->id))->not->toBeNull();
    expect($w1->fresh()->deleted_at)->toBeNull();
});

// ─── Duplication via service (DUP-004 / DUP-005) ───

it('clones the current dashboard with fresh widgets and a (Copy) suffix', function () {
    $dashboard = makeDashboard();
    makeWidget($dashboard, ['name' => 'W1']);
    makeWidget($dashboard, ['name' => 'W2']);

    $clone = $this->service->cloneDashboard($dashboard);

    expect($clone->id)->not->toBe($dashboard->id);
    expect($clone->name)->toBe('Test Dashboard (Copy)');
    expect($clone->is_default)->toBeFalse();
    expect($clone->widgets->count())->toBe(2);

    $cloneWidgetIds = $clone->widgets->pluck('id')->toArray();
    $origWidgetIds = $dashboard->widgets->pluck('id')->toArray();
    expect(array_intersect($cloneWidgetIds, $origWidgetIds))->toBe([]);
});

it('clones a dashboard from a historic version using widget version snapshots', function () {
    $dashboard = makeDashboard();
    $w1 = makeWidget($dashboard, ['name' => 'W1', 'grid_w' => 3]);
    $w2 = makeWidget($dashboard, ['name' => 'W2', 'grid_w' => 3]);

    $dashboard->createVersion('Snapshot 1');
    $snapshotVersion = $dashboard->versions()->latest('version_number')->first();

    $clone = $this->service->cloneDashboardFromVersion($dashboard, $snapshotVersion);

    expect($clone->id)->not->toBe($dashboard->id);
    expect($clone->name)->toBe('Test Dashboard (From v2)');
    expect($clone->is_default)->toBeFalse();
    expect($clone->widgets->count())->toBe(2);

    $cloneWidgetNames = $clone->widgets->pluck('name')->sort()->values()->toArray();
    expect($cloneWidgetNames)->toBe(['W1', 'W2']);
});

it('duplicates a single widget with a (Copy) suffix', function () {
    $dashboard = makeDashboard();
    $w1 = makeWidget($dashboard, ['name' => 'W1', 'grid_x' => 0, 'grid_y' => 0]);

    $dup = $this->service->duplicateWidget($w1);

    expect($dup->id)->not->toBe($w1->id);
    expect($dup->name)->toBe('W1 (Copy)');
    expect($dup->dashboard_id)->toBe($dashboard->id);
    expect($dup->grid_x)->toBe(1);
    expect($dup->grid_y)->toBe(1);
});
