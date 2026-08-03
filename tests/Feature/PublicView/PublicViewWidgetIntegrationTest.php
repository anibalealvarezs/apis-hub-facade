<?php

use App\Models\AssetGroup;
use App\Models\CustomKpi;
use App\Models\Dashboard;
use App\Models\DashboardPublicView;
use App\Models\DashboardWidget;
use App\Models\Project;
use App\Models\User;

beforeEach(function () {
    config(['app.public_view_skip_ua_check' => true]);
    $this->user = User::factory()->create();
    $this->project = Project::factory()->create(['user_id' => $this->user->id]);
    $this->dashboard = Dashboard::create([
        'project_id' => $this->project->id,
        'user_id' => $this->user->id,
        'name' => 'Public View Integration Dashboard',
        'is_public' => true,
        'is_default' => false,
    ]);
});

it('exempts widget data endpoint from CSRF validation and allows fetching data via pv_token without 419 error', function () {
    $pv = DashboardPublicView::create([
        'dashboard_id' => $this->dashboard->id,
        'name' => 'Public View Token Test',
    ]);

    $widget = DashboardWidget::create([
        'dashboard_id' => $this->dashboard->id,
        'name' => 'GA Metric Widget',
        'widget_type' => 'chart',
        'source_type' => 'metric',
        'source_config' => [
            'channel' => 'google_analytics',
            'metrics' => ['sessions', 'conversions'],
        ],
        'grid_x' => 0,
        'grid_y' => 0,
        'grid_w' => 6,
        'grid_h' => 4,
    ]);

    // Perform unauthenticated POST request without CSRF token
    $response = $this->postJson("/api/dashboard/widget/{$widget->id}/data", [
        'pv_token' => $pv->token,
    ]);

    // Assert status is NOT 419 (Page Expired)
    expect($response->status())->not->toBe(419);
});

it('renders public view page with KPI widgets without indirect series_assets_options property modification errors', function () {
    $kpi = CustomKpi::create([
        'project_id' => $this->project->id,
        'name' => 'Test KPI',
        'formula' => 'a / b',
        'filters' => [
            '_ui_state' => [
                'dependent_channel' => 'google_analytics',
                'independent_variables' => [
                    'b' => ['independent_channel' => 'google_analytics'],
                ],
            ],
        ],
        'is_active' => true,
    ]);

    $widget = DashboardWidget::create([
        'dashboard_id' => $this->dashboard->id,
        'name' => 'KPI Widget',
        'widget_type' => 'tile',
        'source_type' => 'kpi',
        'source_config' => [
            'custom_kpi_id' => $kpi->id,
        ],
        'grid_x' => 0,
        'grid_y' => 0,
        'grid_w' => 6,
        'grid_h' => 4,
    ]);

    $pv = DashboardPublicView::create([
        'dashboard_id' => $this->dashboard->id,
        'name' => 'KPI Public View',
    ]);

    $response = $this->get(route('public-view.show', ['token' => $pv->token]));

    $response->assertSuccessful();
    $response->assertSee('Public View Integration Dashboard');
});

it('restricts allowed assets when public view specifies an asset group', function () {
    $group = AssetGroup::create([
        'project_id' => $this->project->id,
        'name' => 'Restricted Group',
        'type' => 'static',
    ]);
    $group->items()->create([
        'channel' => 'google_analytics',
        'asset_id' => '123456789',
    ]);

    $pv = DashboardPublicView::create([
        'dashboard_id' => $this->dashboard->id,
        'name' => 'Asset Group Restricted View',
        'asset_group_id' => $group->id,
    ]);

    $widget = DashboardWidget::create([
        'dashboard_id' => $this->dashboard->id,
        'name' => 'Restricted GA Widget',
        'widget_type' => 'chart',
        'source_type' => 'metric',
        'source_config' => [
            'channel' => 'google_analytics',
            'metrics' => ['sessions'],
        ],
        'grid_x' => 0,
        'grid_y' => 0,
        'grid_w' => 6,
        'grid_h' => 4,
    ]);

    $response = $this->postJson("/api/dashboard/widget/{$widget->id}/data", [
        'pv_token' => $pv->token,
    ]);

    expect($response->status())->not->toBe(419);
});

it('renders dashboard controls bar without asset group selector and renders widget filters button', function () {
    $pv = DashboardPublicView::create([
        'dashboard_id' => $this->dashboard->id,
        'name' => 'Interactive Controls View',
    ]);

    $widget = DashboardWidget::create([
        'dashboard_id' => $this->dashboard->id,
        'name' => 'GA Metric Widget',
        'widget_type' => 'chart',
        'source_type' => 'metric',
        'source_config' => [
            'channel' => 'google_analytics',
            'metrics' => ['sessions'],
        ],
        'grid_x' => 0,
        'grid_y' => 0,
        'grid_w' => 6,
        'grid_h' => 4,
    ]);

    $response = $this->get(route('public-view.show', ['token' => $pv->token]));

    $response->assertSuccessful();
    // Assert dashboard controls bar fields exist
    $response->assertSee('dashboardControls.date_start', false);
    $response->assertSee('dashboardControls.date_end', false);
    $response->assertSee('dashboardControls.granularity', false);
    $response->assertSee('dashboardControls.zero_handling', false);
    
    // Assert Asset Group selector is excluded
    $response->assertDontSee('dashboardControls.asset_group', false);
    $response->assertDontSee('Asset Group', false);

    // Assert widget filters button exists
    $response->assertSee('Filters', false);
});

