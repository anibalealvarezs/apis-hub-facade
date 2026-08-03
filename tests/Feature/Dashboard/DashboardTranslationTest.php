<?php

use App\Models\Dashboard;
use App\Models\DashboardWidget;
use App\Models\Project;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->project = Project::factory()->create(['user_id' => $this->user->id]);
});

it('auto duplicates dashboard name and description across locales on creation', function () {
    app()->setLocale('en');

    $dashboard = Dashboard::create([
        'project_id' => $this->project->id,
        'user_id' => $this->user->id,
        'name' => 'Marketing Overview',
        'description' => 'Key marketing metrics',
        'is_public' => true,
        'is_default' => false,
    ]);

    // English locale
    app()->setLocale('en');
    expect($dashboard->name)->toBe('Marketing Overview');
    expect($dashboard->description)->toBe('Key marketing metrics');

    // Spanish locale - should return duplicated content automatically
    app()->setLocale('es');
    expect($dashboard->name)->toBe('Marketing Overview');
    expect($dashboard->description)->toBe('Key marketing metrics');

    // Manually setting custom Spanish translation
    $dashboard->setTranslation('name', 'es', 'Resumen de Marketing');
    $dashboard->save();

    expect($dashboard->name)->toBe('Resumen de Marketing');

    app()->setLocale('en');
    expect($dashboard->name)->toBe('Marketing Overview');
});

it('auto duplicates widget name, title, and description across locales on creation', function () {
    app()->setLocale('en');

    $dashboard = Dashboard::create([
        'project_id' => $this->project->id,
        'user_id' => $this->user->id,
        'name' => 'Widget Translation Dashboard',
        'is_public' => true,
        'is_default' => false,
    ]);

    $widget = DashboardWidget::create([
        'dashboard_id' => $dashboard->id,
        'name' => 'GA Sessions',
        'title' => 'Total Traffic',
        'description' => 'Website visitor count',
        'widget_type' => 'chart',
        'source_type' => 'metric',
        'grid_x' => 0,
        'grid_y' => 0,
        'grid_w' => 6,
        'grid_h' => 4,
    ]);

    // English locale
    app()->setLocale('en');
    expect($widget->name)->toBe('GA Sessions');
    expect($widget->title)->toBe('Total Traffic');
    expect($widget->description)->toBe('Website visitor count');

    // Spanish locale - auto-duplicated default
    app()->setLocale('es');
    expect($widget->name)->toBe('GA Sessions');
    expect($widget->title)->toBe('Total Traffic');
    expect($widget->description)->toBe('Website visitor count');

    // Customize Spanish translation
    $widget->setTranslation('title', 'es', 'Tráfico Total');
    $widget->setTranslation('description', 'es', 'Número de visitantes');
    $widget->save();

    expect($widget->title)->toBe('Tráfico Total');
    expect($widget->description)->toBe('Número de visitantes');

    app()->setLocale('en');
    expect($widget->title)->toBe('Total Traffic');
});

it('updates widget controls with per-language title and description maps cleanly', function () {
    app()->setLocale('en');

    $dashboard = Dashboard::create([
        'project_id' => $this->project->id,
        'user_id' => $this->user->id,
        'name' => 'Widget Controls Test Dashboard',
        'is_public' => true,
        'is_default' => false,
    ]);

    $widget = DashboardWidget::create([
        'dashboard_id' => $dashboard->id,
        'name' => 'Ad Spend',
        'title' => 'Total Ad Spend',
        'description' => 'Ad spend across channels',
        'widget_type' => 'chart',
        'source_type' => 'metric',
        'grid_x' => 0,
        'grid_y' => 0,
        'grid_w' => 6,
        'grid_h' => 4,
    ]);

    $builder = new \App\Filament\App\Resources\DashboardResource\Pages\DashboardBuilder();
    $builder->dashboard = $dashboard;

    $builder->saveWidgetControls(
        $widget->id,
        ['date_start' => '2026-01-01'],
        null,
        null,
        ['en' => 'Total Paid Spend', 'es' => 'Gasto Publicitario Total'],
        ['en' => 'Track total ad spend', 'es' => 'Rastrea el gasto publicitario total']
    );

    $widget->refresh();

    app()->setLocale('en');
    expect($widget->title)->toBe('Total Paid Spend');
    expect($widget->description)->toBe('Track total ad spend');

    app()->setLocale('es');
    expect($widget->title)->toBe('Gasto Publicitario Total');
    expect($widget->description)->toBe('Rastrea el gasto publicitario total');
});

