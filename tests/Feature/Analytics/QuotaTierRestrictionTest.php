<?php

use App\Enums\UserTier;
use App\Models\CustomKpi;
use App\Models\DerivedMetric;
use App\Models\Project;
use App\Models\User;
use App\Services\FeatureGateService;

beforeEach(function () {
    $this->gate = app(FeatureGateService::class);
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('shows upgrade action instead of create action when custom kpi quota is reached on free tier', function () {
    $freeProfile = $this->user->billingProfiles()->first();
    $freeProfile->update(['tier' => UserTier::FREE]);

    $project = Project::factory()->create([
        'user_id' => $this->user->id,
        'billing_profile_id' => $freeProfile->id,
    ]);

    \Filament\Facades\Filament::setTenant($project, isQuiet: true);

    // Free tier allows 10 Custom KPIs. Create 10 to hit quota limit.
    for ($i = 1; $i <= 10; $i++) {
        CustomKpi::create([
            'name' => "Custom KPI {$i}",
            'project_id' => $project->id,
            'calculation_type' => 'simple',
            'is_active' => true,
        ]);
    }

    $listPage = new \App\Filament\App\Resources\CustomKpiResource\Pages\ListCustomKpis();
    $reflection = new \ReflectionClass($listPage);
    $actionsMethod = $reflection->getMethod('getHeaderActions');
    $actionsMethod->setAccessible(true);
    $actions = $actionsMethod->invoke($listPage);

    $createAction = collect($actions)->first(fn ($a) => $a instanceof \Filament\Actions\CreateAction);
    $upgradeAction = collect($actions)->first(fn ($a) => $a->getName() === 'upgradeCustomKpiQuota');

    expect($createAction)->toBeNull();
    expect($upgradeAction)->not->toBeNull();
    expect($upgradeAction->getColor())->toBe('warning');
});

it('shows create action when custom kpi quota is not yet reached', function () {
    $freeProfile = $this->user->billingProfiles()->first();
    $freeProfile->update(['tier' => UserTier::FREE]);

    $project = Project::factory()->create([
        'user_id' => $this->user->id,
        'billing_profile_id' => $freeProfile->id,
    ]);

    \Filament\Facades\Filament::setTenant($project, isQuiet: true);

    // Only 2 created, quota is 10
    CustomKpi::create([
        'name' => 'Custom KPI 1',
        'project_id' => $project->id,
        'calculation_type' => 'simple',
        'is_active' => true,
    ]);

    $listPage = new \App\Filament\App\Resources\CustomKpiResource\Pages\ListCustomKpis();
    $reflection = new \ReflectionClass($listPage);
    $actionsMethod = $reflection->getMethod('getHeaderActions');
    $actionsMethod->setAccessible(true);
    $actions = $actionsMethod->invoke($listPage);

    $createAction = collect($actions)->first(fn ($a) => $a instanceof \Filament\Actions\CreateAction);
    $upgradeAction = collect($actions)->first(fn ($a) => $a->getName() === 'upgradeCustomKpiQuota');

    expect($createAction)->not->toBeNull();
    expect($upgradeAction)->toBeNull();
});

it('shows upgrade action instead of create action when derived metric quota is reached on free tier', function () {
    $freeProfile = $this->user->billingProfiles()->first();
    $freeProfile->update(['tier' => UserTier::FREE]);

    $project = Project::factory()->create([
        'user_id' => $this->user->id,
        'billing_profile_id' => $freeProfile->id,
    ]);

    \Filament\Facades\Filament::setTenant($project, isQuiet: true);

    // Free tier allows 10 Derived Metrics. Create 10 to hit quota limit.
    for ($i = 1; $i <= 10; $i++) {
        DerivedMetric::create([
            'name' => "Derived Metric {$i}",
            'project_id' => $project->id,
            'metric_key' => "metric_{$i}",
            'formula' => 'a + 1',
            'source_series' => [],
            'is_active' => true,
        ]);
    }

    $listPage = new \App\Filament\App\Resources\DerivedMetricResource\Pages\ListDerivedMetrics();
    $reflection = new \ReflectionClass($listPage);
    $actionsMethod = $reflection->getMethod('getHeaderActions');
    $actionsMethod->setAccessible(true);
    $actions = $actionsMethod->invoke($listPage);

    $createAction = collect($actions)->first(fn ($a) => $a instanceof \Filament\Actions\CreateAction);
    $upgradeAction = collect($actions)->first(fn ($a) => $a->getName() === 'upgradeDerivedMetricQuota');

    expect($createAction)->toBeNull();
    expect($upgradeAction)->not->toBeNull();
    expect($upgradeAction->getColor())->toBe('warning');
});

it('shows create action when derived metric quota is not yet reached', function () {
    $freeProfile = $this->user->billingProfiles()->first();
    $freeProfile->update(['tier' => UserTier::FREE]);

    $project = Project::factory()->create([
        'user_id' => $this->user->id,
        'billing_profile_id' => $freeProfile->id,
    ]);

    \Filament\Facades\Filament::setTenant($project, isQuiet: true);

    DerivedMetric::create([
        'name' => 'Derived Metric 1',
        'project_id' => $project->id,
        'metric_key' => 'metric_1',
        'formula' => 'a + 1',
        'source_series' => [],
        'is_active' => true,
    ]);

    $listPage = new \App\Filament\App\Resources\DerivedMetricResource\Pages\ListDerivedMetrics();
    $reflection = new \ReflectionClass($listPage);
    $actionsMethod = $reflection->getMethod('getHeaderActions');
    $actionsMethod->setAccessible(true);
    $actions = $actionsMethod->invoke($listPage);

    $createAction = collect($actions)->first(fn ($a) => $a instanceof \Filament\Actions\CreateAction);
    $upgradeAction = collect($actions)->first(fn ($a) => $a->getName() === 'upgradeDerivedMetricQuota');

    expect($createAction)->not->toBeNull();
    expect($upgradeAction)->toBeNull();
});
