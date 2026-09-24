<?php

use App\Enums\UserTier;
use App\Models\BillingProfile;
use App\Models\Dashboard;
use App\Models\Project;
use App\Models\User;
use App\Services\FeatureGateService;

beforeEach(function () {
    $this->gate = app(FeatureGateService::class);
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('prevents free tier project from setting dashboard is_public to true during creation and editing', function () {
    // User already has an auto-provisioned Free billing profile from UserObserver
    $freeProfile = $this->user->billingProfiles()->first();

    $project = Project::factory()->create([
        'user_id' => $this->user->id,
        'billing_profile_id' => $freeProfile->id,
    ]);

    \Filament\Facades\Filament::setTenant($project, isQuiet: true);

    // Test form mutation logic in CreateDashboard
    $createPage = new \App\Filament\App\Resources\DashboardResource\Pages\CreateDashboard();
    $reflection = new \ReflectionClass($createPage);
    $mutateMethod = $reflection->getMethod('mutateFormDataBeforeCreate');
    $mutateMethod->setAccessible(true);

    $sanitized = $mutateMethod->invoke($createPage, [
        'name' => ['en' => 'Test Free Dashboard'],
        'is_public' => true,
    ]);

    // is_public must be forced to false on Free tier
    expect($sanitized['is_public'])->toBeFalse();

    // Test form mutation logic in EditDashboard
    $editPage = new \App\Filament\App\Resources\DashboardResource\Pages\EditDashboard();
    $reflectionEdit = new \ReflectionClass($editPage);
    $mutateEditMethod = $reflectionEdit->getMethod('mutateFormDataBeforeSave');
    $mutateEditMethod->setAccessible(true);

    $sanitizedEdit = $mutateEditMethod->invoke($editPage, [
        'name' => ['en' => 'Updated Dashboard'],
        'is_public' => true,
    ]);

    expect($sanitizedEdit['is_public'])->toBeFalse();
});

it('allows pro tier project to set dashboard is_public to true', function () {
    // Upgrade user's profile to PRO
    $profile = $this->user->billingProfiles()->first();
    $profile->update(['tier' => UserTier::PRO]);

    $project = Project::factory()->create([
        'user_id' => $this->user->id,
        'billing_profile_id' => $profile->id,
    ]);

    \Filament\Facades\Filament::setTenant($project, isQuiet: true);

    $createPage = new \App\Filament\App\Resources\DashboardResource\Pages\CreateDashboard();
    $reflection = new \ReflectionClass($createPage);
    $mutateMethod = $reflection->getMethod('mutateFormDataBeforeCreate');
    $mutateMethod->setAccessible(true);

    $sanitized = $mutateMethod->invoke($createPage, [
        'name' => ['en' => 'Test Pro Dashboard'],
        'is_public' => true,
    ]);

    expect($sanitized['is_public'])->toBeTrue();
});

it('shows upgrade action instead of create action when dashboard quota is reached on free tier', function () {
    $freeProfile = $this->user->billingProfiles()->first();
    $project = Project::factory()->create([
        'user_id' => $this->user->id,
        'billing_profile_id' => $freeProfile->id,
    ]);

    \Filament\Facades\Filament::setTenant($project, isQuiet: true);

    // Free tier allows max 1 private dashboard. Create 1 dashboard to fill the quota.
    Dashboard::create([
        'name' => ['en' => 'Existing Free Dashboard'],
        'project_id' => $project->id,
        'user_id' => $this->user->id,
        'is_public' => false,
    ]);

    $listPage = new \App\Filament\App\Resources\DashboardResource\Pages\ListDashboards();
    $reflection = new \ReflectionClass($listPage);
    $actionsMethod = $reflection->getMethod('getHeaderActions');
    $actionsMethod->setAccessible(true);
    $actions = $actionsMethod->invoke($listPage);

    // CreateAction should NOT be present; upgradeDashboardQuota should be present instead
    $createAction = collect($actions)->first(fn ($a) => $a instanceof \Filament\Actions\CreateAction);
    $upgradeAction = collect($actions)->first(fn ($a) => $a->getName() === 'upgradeDashboardQuota');

    expect($createAction)->toBeNull();
    expect($upgradeAction)->not->toBeNull();
    expect($upgradeAction->getColor())->toBe('warning');
});

it('shows create action when dashboard quota is not yet reached', function () {
    $proProfile = $this->user->billingProfiles()->first();
    $proProfile->update(['tier' => UserTier::PRO]);

    $project = Project::factory()->create([
        'user_id' => $this->user->id,
        'billing_profile_id' => $proProfile->id,
    ]);

    \Filament\Facades\Filament::setTenant($project, isQuiet: true);

    // Pro tier allows 5 dashboards. With 1 created, quota is not reached.
    Dashboard::create([
        'name' => ['en' => 'Dashboard 1'],
        'project_id' => $project->id,
        'user_id' => $this->user->id,
        'is_public' => false,
    ]);

    $listPage = new \App\Filament\App\Resources\DashboardResource\Pages\ListDashboards();
    $reflection = new \ReflectionClass($listPage);
    $actionsMethod = $reflection->getMethod('getHeaderActions');
    $actionsMethod->setAccessible(true);
    $actions = $actionsMethod->invoke($listPage);

    $createAction = collect($actions)->first(fn ($a) => $a instanceof \Filament\Actions\CreateAction);
    $upgradeAction = collect($actions)->first(fn ($a) => $a->getName() === 'upgradeDashboardQuota');

    expect($createAction)->not->toBeNull();
    expect($upgradeAction)->toBeNull();
});
