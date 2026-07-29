<?php

use App\Models\CustomKpi;
use App\Models\Dashboard;
use App\Models\DashboardWidget;
use App\Models\DerivedMetric;
use App\Models\Project;
use App\Models\User;
use App\Traits\TracksVersions;
use Illuminate\Database\Eloquent\Model;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->project = Project::factory()->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user);
});

// ─── CustomKpi Versioning ───

it('creates version v1 when CustomKpi is created', function () {
    $kpi = CustomKpi::create([
        'project_id' => $this->project->id,
        'name' => 'Test KPI',
        'calculation_type' => 'ratio',
        'ast' => ['type' => 'divide', 'left' => 'a', 'right' => 'b'],
        'filters' => ['channel' => 'google_ads'],
        'is_active' => true,
    ]);

    expect($kpi->versions()->count())->toBe(1);
    expect($kpi->versions()->first()->version_number)->toBe(1);
    expect($kpi->versions()->first()->change_summary)->toBe('Created');
});

it('creates a new version when trackable fields change on CustomKpi', function () {
    $kpi = CustomKpi::create([
        'project_id' => $this->project->id,
        'name' => 'Test KPI',
        'calculation_type' => 'ratio',
        'ast' => ['type' => 'divide', 'left' => 'a', 'right' => 'b'],
        'filters' => ['channel' => 'google_ads'],
        'is_active' => true,
    ]);

    $kpi->update(['name' => 'Updated KPI']);

    expect($kpi->versions()->count())->toBe(2);
    expect($kpi->versions()->latest('version_number')->first()->version_number)->toBe(2);
    expect($kpi->versions()->latest('version_number')->first()->name)->toBe('Updated KPI');
});

it('does not create a version when only updated_at changes on CustomKpi', function () {
    $kpi = CustomKpi::create([
        'project_id' => $this->project->id,
        'name' => 'Test KPI',
        'calculation_type' => 'ratio',
        'ast' => ['type' => 'divide', 'left' => 'a', 'right' => 'b'],
        'filters' => ['channel' => 'google_ads'],
        'is_active' => true,
    ]);

    $kpi->touch();

    expect($kpi->versions()->count())->toBe(1);
});

it('restores a previous version on CustomKpi', function () {
    $kpi = CustomKpi::create([
        'project_id' => $this->project->id,
        'name' => 'Original Name',
        'calculation_type' => 'ratio',
        'ast' => ['type' => 'divide', 'left' => 'a', 'right' => 'b'],
        'filters' => ['channel' => 'google_ads'],
        'is_active' => true,
    ]);

    $kpi->update(['name' => 'Changed Name']);

    $firstVersion = $kpi->versions()->oldest('version_number')->first();
    $kpi->restoreVersion($firstVersion->id);

    $kpi->refresh();
    expect($kpi->name)->toBe('Original Name');
    expect($kpi->versions()->count())->toBe(3);
});

it('tracks changes to description on CustomKpi', function () {
    $kpi = CustomKpi::create([
        'project_id' => $this->project->id,
        'name' => 'Test KPI',
        'description' => 'Original description',
        'calculation_type' => 'ratio',
        'ast' => ['type' => 'divide', 'left' => 'a', 'right' => 'b'],
        'filters' => ['channel' => 'google_ads'],
        'is_active' => true,
    ]);

    $kpi->update(['description' => 'Updated description']);

    expect($kpi->versions()->latest('version_number')->first()->description)->toBe('Updated description');
});

it('tracks changes to is_active on CustomKpi', function () {
    $kpi = CustomKpi::create([
        'project_id' => $this->project->id,
        'name' => 'Test KPI',
        'calculation_type' => 'ratio',
        'ast' => ['type' => 'divide', 'left' => 'a', 'right' => 'b'],
        'filters' => ['channel' => 'google_ads'],
        'is_active' => true,
    ]);

    $kpi->update(['is_active' => false]);

    expect($kpi->versions()->latest('version_number')->first()->is_active)->toBeFalse();
});

// ─── DerivedMetric Versioning ───

it('creates version v1 when DerivedMetric is created', function () {
    $dm = DerivedMetric::create([
        'project_id' => $this->project->id,
        'name' => 'Test DM',
        'ast' => ['type' => 'addition', 'values' => ['a', 'b']],
        'source_series' => [['key' => 'a', 'channel' => 'google_ads', 'metric' => 'impressions']],
        'is_active' => true,
    ]);

    expect($dm->versions()->count())->toBe(1);
    expect($dm->versions()->first()->version_number)->toBe(1);
});

it('creates a new version when trackable fields change on DerivedMetric', function () {
    $dm = DerivedMetric::create([
        'project_id' => $this->project->id,
        'name' => 'Test DM',
        'ast' => ['type' => 'addition', 'values' => ['a', 'b']],
        'source_series' => [['key' => 'a', 'channel' => 'google_ads', 'metric' => 'impressions']],
        'is_active' => true,
    ]);

    $dm->update(['name' => 'Updated DM']);

    expect($dm->versions()->count())->toBe(2);
    expect($dm->versions()->latest('version_number')->first()->name)->toBe('Updated DM');
});

it('restores a previous version on DerivedMetric', function () {
    $dm = DerivedMetric::create([
        'project_id' => $this->project->id,
        'name' => 'Original',
        'ast' => ['type' => 'addition', 'values' => ['a', 'b']],
        'source_series' => [['key' => 'a', 'channel' => 'google_ads', 'metric' => 'impressions']],
        'is_active' => true,
    ]);

    $dm->update(['name' => 'Changed']);

    $firstVersion = $dm->versions()->oldest('version_number')->first();
    $dm->restoreVersion($firstVersion->id);

    $dm->refresh();
    expect($dm->name)->toBe('Original');
});

it('tracks source_series changes on DerivedMetric', function () {
    $dm = DerivedMetric::create([
        'project_id' => $this->project->id,
        'name' => 'Test DM',
        'ast' => ['type' => 'addition', 'values' => ['a', 'b']],
        'source_series' => [['key' => 'a', 'channel' => 'google_ads', 'metric' => 'impressions']],
        'is_active' => true,
    ]);

    $newSeries = [['key' => 'a', 'channel' => 'google_ads', 'metric' => 'clicks']];
    $dm->update(['source_series' => $newSeries]);

    expect($dm->versions()->latest('version_number')->first()->source_series)->toBe($newSeries);
});

it('tracks output_granularity changes on DerivedMetric', function () {
    $dm = DerivedMetric::create([
        'project_id' => $this->project->id,
        'name' => 'Test DM',
        'ast' => ['type' => 'addition', 'values' => ['a', 'b']],
        'source_series' => [['key' => 'a', 'channel' => 'google_ads', 'metric' => 'impressions']],
        'output_granularity' => 'daily',
        'is_active' => true,
    ]);

    $dm->update(['output_granularity' => 'weekly']);

    expect($dm->versions()->latest('version_number')->first()->output_granularity)->toBe('weekly');
});

// ─── Dashboard Versioning ───

it('creates version v1 when Dashboard is created', function () {
    $dashboard = Dashboard::create([
        'project_id' => $this->project->id,
        'user_id' => $this->user->id,
        'name' => 'Test Dashboard',
        'is_public' => false,
        'is_default' => false,
    ]);

    expect($dashboard->versions()->count())->toBe(1);
    expect($dashboard->versions()->first()->version_number)->toBe(1);
});

it('creates a new version when trackable fields change on Dashboard', function () {
    $dashboard = Dashboard::create([
        'project_id' => $this->project->id,
        'user_id' => $this->user->id,
        'name' => 'Test Dashboard',
        'is_public' => false,
        'is_default' => false,
    ]);

    $dashboard->update(['name' => 'Updated Dashboard']);

    expect($dashboard->versions()->count())->toBe(2);
});

it('tracks is_public changes on Dashboard', function () {
    $dashboard = Dashboard::create([
        'project_id' => $this->project->id,
        'user_id' => $this->user->id,
        'name' => 'Test Dashboard',
        'is_public' => false,
        'is_default' => false,
    ]);

    $dashboard->update(['is_public' => true]);

    expect($dashboard->versions()->latest('version_number')->first()->is_public)->toBeTrue();
});

it('tracks grid_layout changes on Dashboard', function () {
    $dashboard = Dashboard::create([
        'project_id' => $this->project->id,
        'user_id' => $this->user->id,
        'name' => 'Test Dashboard',
        'is_public' => false,
        'is_default' => false,
    ]);

    $layout = [['x' => 0, 'y' => 0, 'w' => 6, 'h' => 4]];
    $dashboard->update(['grid_layout' => $layout]);

    expect($dashboard->versions()->latest('version_number')->first()->grid_layout)->toBe($layout);
});

it('restores a previous version on Dashboard', function () {
    $dashboard = Dashboard::create([
        'project_id' => $this->project->id,
        'user_id' => $this->user->id,
        'name' => 'Original',
        'is_public' => false,
        'is_default' => false,
    ]);

    $dashboard->update(['name' => 'Changed']);

    $firstVersion = $dashboard->versions()->oldest('version_number')->first();
    $dashboard->restoreVersion($firstVersion->id);

    $dashboard->refresh();
    expect($dashboard->name)->toBe('Original');
});

// ─── DashboardWidget Versioning ───

it('creates version v1 when DashboardWidget is created', function () {
    $dashboard = Dashboard::create([
        'project_id' => $this->project->id,
        'user_id' => $this->user->id,
        'name' => 'Test Dashboard',
        'is_public' => false,
        'is_default' => false,
    ]);

    $widget = DashboardWidget::create([
        'dashboard_id' => $dashboard->id,
        'name' => 'Test Widget',
        'source_type' => 'kpi',
        'widget_type' => 'counter',
        'grid_x' => 0,
        'grid_y' => 0,
        'grid_w' => 3,
        'grid_h' => 2,
    ]);

    expect($widget->versions()->count())->toBe(1);
    expect($widget->versions()->first()->version_number)->toBe(1);
});

it('creates a new version when widget position changes', function () {
    $dashboard = Dashboard::create([
        'project_id' => $this->project->id,
        'user_id' => $this->user->id,
        'name' => 'Test Dashboard',
        'is_public' => false,
        'is_default' => false,
    ]);

    $widget = DashboardWidget::create([
        'dashboard_id' => $dashboard->id,
        'name' => 'Test Widget',
        'source_type' => 'kpi',
        'widget_type' => 'counter',
        'grid_x' => 0,
        'grid_y' => 0,
        'grid_w' => 3,
        'grid_h' => 2,
    ]);

    $widget->update(['grid_x' => 3, 'grid_y' => 2]);

    expect($widget->versions()->count())->toBe(2);
    expect($widget->versions()->latest('version_number')->first()->grid_x)->toBe(3);
    expect($widget->versions()->latest('version_number')->first()->grid_y)->toBe(2);
});

it('restores a previous version on DashboardWidget', function () {
    $dashboard = Dashboard::create([
        'project_id' => $this->project->id,
        'user_id' => $this->user->id,
        'name' => 'Test Dashboard',
        'is_public' => false,
        'is_default' => false,
    ]);

    $widget = DashboardWidget::create([
        'dashboard_id' => $dashboard->id,
        'name' => 'Original',
        'source_type' => 'kpi',
        'widget_type' => 'counter',
        'grid_x' => 0,
        'grid_y' => 0,
        'grid_w' => 3,
        'grid_h' => 2,
    ]);

    $widget->update(['name' => 'Changed']);

    $firstVersion = $widget->versions()->oldest('version_number')->first();
    $widget->restoreVersion($firstVersion->id);

    $widget->refresh();
    expect($widget->name)->toBe('Original');
});

// ─── Version Number Sequence ───

it('increments version numbers sequentially for CustomKpi', function () {
    $kpi = CustomKpi::create([
        'project_id' => $this->project->id,
        'name' => 'Test KPI',
        'calculation_type' => 'ratio',
        'ast' => ['type' => 'divide', 'left' => 'a', 'right' => 'b'],
        'filters' => ['channel' => 'google_ads'],
        'is_active' => true,
    ]);

    $kpi->update(['name' => 'v2']);
    $kpi->update(['name' => 'v3']);
    $kpi->update(['name' => 'v4']);

    $numbers = $kpi->versions()->pluck('version_number')->sort()->values()->toArray();
    expect($numbers)->toBe([1, 2, 3, 4]);
});

// ─── Change Summary Detection ───

it('generates a change summary when trackable fields change', function () {
    $kpi = CustomKpi::create([
        'project_id' => $this->project->id,
        'name' => 'Test KPI',
        'calculation_type' => 'ratio',
        'ast' => ['type' => 'divide', 'left' => 'a', 'right' => 'b'],
        'filters' => ['channel' => 'google_ads'],
        'is_active' => true,
    ]);

    $kpi->update(['name' => 'Updated', 'description' => 'New description']);

    $latestVersion = $kpi->versions()->latest('version_number')->first();
    expect($latestVersion->change_summary)->toContain('name');
    expect($latestVersion->change_summary)->toContain('description');
});

// ─── Restore Creates Snapshot Before Reverting ───

it('creates a before-restore version snapshot', function () {
    $kpi = CustomKpi::create([
        'project_id' => $this->project->id,
        'name' => 'Original',
        'calculation_type' => 'ratio',
        'ast' => ['type' => 'divide', 'left' => 'a', 'right' => 'b'],
        'filters' => ['channel' => 'google_ads'],
        'is_active' => true,
    ]);

    $kpi->update(['name' => 'Intermediate']);
    $kpi->update(['name' => 'Current']);

    expect($kpi->versions()->count())->toBe(3);

    $firstVersion = $kpi->versions()->oldest('version_number')->first();
    $kpi->restoreVersion($firstVersion->id);

    expect($kpi->versions()->count())->toBe(4);
    expect($kpi->versions()->latest('version_number')->first()->change_summary)->toContain('Before restore');
});
