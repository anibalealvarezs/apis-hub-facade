<?php

use App\Filament\App\Resources\CustomKpiResource;
use App\Filament\App\Resources\DashboardResource;
use App\Filament\App\Resources\DashboardResource\Pages\DashboardBuilder;
use App\Filament\App\Resources\DerivedMetricResource;
use App\Models\CustomKpi;
use App\Models\Dashboard;
use App\Models\DashboardWidget;
use App\Models\DerivedMetric;
use App\Models\Project;
use App\Models\User;
use Livewire\Livewire;
use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->project = Project::factory()->create(['user_id' => $this->user->id]);
    $this->project->users()->attach($this->user->id);

    \Spatie\Permission\Models\Permission::findOrCreate('edit_preferences');
    \Spatie\Permission\Models\Permission::findOrCreate('deploy_project');
    $this->user->givePermissionTo(['edit_preferences', 'deploy_project']);

    actingAs($this->user);
    \Filament\Facades\Filament::setTenant($this->project);
});

// ─── Version History Visibility ───

it('shows version history on the CustomKpi edit page', function () {
    $kpi = CustomKpi::create([
        'project_id' => $this->project->id,
        'name' => 'Test KPI',
        'calculation_type' => 'ratio',
        'ast' => ['type' => 'divide', 'left' => 'a', 'right' => 'b'],
        'filters' => ['channel' => 'google_ads'],
        'is_active' => true,
    ]);

    $this->get(CustomKpiResource::getUrl('edit', ['record' => $kpi->id, 'tenant' => $this->project->subdomain]))
        ->assertSuccessful()
        ->assertSee('Version History');
});

it('shows version history on the DerivedMetric edit page', function () {
    $dm = DerivedMetric::create([
        'project_id' => $this->project->id,
        'name' => 'Test DM',
        'ast' => ['type' => 'addition', 'values' => ['a', 'b']],
        'source_series' => [['key' => 'a', 'channel' => 'google_ads', 'metric' => 'impressions']],
        'is_active' => true,
    ]);

    $this->get(DerivedMetricResource::getUrl('edit', ['record' => $dm->id, 'tenant' => $this->project->subdomain]))
        ->assertSuccessful()
        ->assertSee('Version History');
});

it('shows version history on the Dashboard edit page', function () {
    $dashboard = Dashboard::create([
        'project_id' => $this->project->id,
        'user_id' => $this->user->id,
        'name' => 'Test Dashboard',
        'is_public' => false,
        'is_default' => false,
    ]);

    $this->get(DashboardResource::getUrl('edit', ['record' => $dashboard->id, 'tenant' => $this->project->subdomain]))
        ->assertSuccessful()
        ->assertSee('Version History');
});

// ─── Version Data Display ───

it('displays version number, change summary, user, and date in version history', function () {
    $kpi = CustomKpi::create([
        'project_id' => $this->project->id,
        'name' => 'Test KPI',
        'calculation_type' => 'ratio',
        'ast' => ['type' => 'divide', 'left' => 'a', 'right' => 'b'],
        'filters' => ['channel' => 'google_ads'],
        'is_active' => true,
    ]);

    $kpi->update(['name' => 'Updated KPI']);

    $this->get(CustomKpiResource::getUrl('edit', ['record' => $kpi->id, 'tenant' => $this->project->subdomain]))
        ->assertSuccessful()
        ->assertSee('v1')
        ->assertSee('v2')
        ->assertSee('Updated');
});

// ─── Restore Version From UI ───

it('restores a previous version from the relation manager', function () {
    $kpi = CustomKpi::create([
        'project_id' => $this->project->id,
        'name' => 'Original',
        'calculation_type' => 'ratio',
        'ast' => ['type' => 'divide', 'left' => 'a', 'right' => 'b'],
        'filters' => ['channel' => 'google_ads'],
        'is_active' => true,
    ]);

    $kpi->update(['name' => 'Changed']);

    $firstVersion = $kpi->versions()->oldest('version_number')->first();

    Livewire::test(\App\Filament\App\Resources\CustomKpiResource\RelationManagers\VersionsRelationManager::class, [
        'ownerRecord' => $kpi,
        'pageClass' => \App\Filament\App\Resources\CustomKpiResource\Pages\EditCustomKpi::class,
    ])
        ->callTableAction('restore', $firstVersion)
        ->assertHasNoTableActionErrors();

    $kpi->refresh();
    expect($kpi->name)->toBe('Original');
});

// ─── Version Pruning ───

it('prunes old versions from CustomKpi', function () {
    $kpi = CustomKpi::create([
        'project_id' => $this->project->id,
        'name' => 'Test KPI',
        'calculation_type' => 'ratio',
        'ast' => ['type' => 'divide', 'left' => 'a', 'right' => 'b'],
        'filters' => ['channel' => 'google_ads'],
        'is_active' => true,
    ]);

    $kpi->fill(['name' => 'v2']);
    $kpi->createVersion();
    $kpi->fill(['name' => 'v3']);
    $kpi->createVersion();

    expect($kpi->versions()->count())->toBe(3);

    // Manually set created_at to simulate old versions
    $kpi->versions()->where('version_number', 2)->update(['created_at' => now()->subMonths(6)]);

    // Mirrors the pruneVersions bulk action closure on CustomKpiResource
    $cutoff = now()->subMonths(3);
    $kpi->getVersions()
        ->where('created_at', '<', $cutoff)
        ->where('version_number', '>', 1)
        ->delete();

    expect($kpi->versions()->count())->toBe(2); // v1 (always kept) + v3
});

it('always keeps version 1 after pruning', function () {
    $kpi = CustomKpi::create([
        'project_id' => $this->project->id,
        'name' => 'Test KPI',
        'calculation_type' => 'ratio',
        'ast' => ['type' => 'divide', 'left' => 'a', 'right' => 'b'],
        'filters' => ['channel' => 'google_ads'],
        'is_active' => true,
    ]);

    $kpi->fill(['name' => 'v2']);
    $kpi->createVersion();
    $kpi->versions()->where('version_number', 2)->update(['created_at' => now()->subMonths(12)]);

    // Mirrors the pruneVersions bulk action closure on CustomKpiResource
    $cutoff = now()->subMonths(3);
    $kpi->getVersions()
        ->where('created_at', '<', $cutoff)
        ->where('version_number', '>', 1)
        ->delete();

    expect($kpi->versions()->where('version_number', 1)->exists())->toBeTrue();
});

// ─── Dashboard Builder Version History ───

it('shows version history button in dashboard builder header', function () {
    $dashboard = Dashboard::create([
        'project_id' => $this->project->id,
        'user_id' => $this->user->id,
        'name' => 'Test Dashboard',
        'is_public' => false,
        'is_default' => false,
    ]);

    Livewire::test(DashboardBuilder::class, [
        'record' => $dashboard->id,
    ])
        ->assertSuccessful()
        ->assertSeeHtml('Version History');
});

it('restores a dashboard version from the builder', function () {
    $dashboard = Dashboard::create([
        'project_id' => $this->project->id,
        'user_id' => $this->user->id,
        'name' => 'Original',
        'is_public' => false,
        'is_default' => false,
    ]);

    $dashboard->update(['name' => 'Changed Name']);

    $firstVersion = $dashboard->versions()->oldest('version_number')->first();

    Livewire::test(DashboardBuilder::class, [
        'record' => $dashboard->id,
    ])
        ->call('restoreVersion', $firstVersion->id)
        ->assertHasNoErrors();

    $dashboard->refresh();
    expect($dashboard->name)->toBe('Original');
});

// ─── Widget Versioning UI ───

it('creates widget versions on grid position change in builder', function () {
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

    $widget->update(['grid_x' => 6, 'grid_y' => 4]);

    expect($widget->versions()->count())->toBe(2);
    expect($widget->versions()->latest('version_number')->first()->grid_x)->toBe(6);
});
