<?php

use App\Enums\UserTier;
use App\Models\ApisHubRelease;
use App\Models\BillingProfile;
use App\Models\Project;
use App\Models\User;
use App\Services\FeatureGateService;
use App\Settings\FeatureSettings;

beforeEach(function () {
    $this->gate = app(FeatureGateService::class);
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('hides ai classification section on releases older than v1.16.0 regardless of tier', function () {
    $releaseOld = ApisHubRelease::create([
        'version_tag' => 'v1.15.2',
        'is_active' => true,
    ]);

    $proProfile = $this->user->billingProfiles()->first();
    $proProfile->update(['tier' => UserTier::PRO]);

    $project = Project::factory()->create([
        'user_id' => $this->user->id,
        'billing_profile_id' => $proProfile->id,
        'apis_hub_release_id' => $releaseOld->id,
    ]);

    expect($project->releaseSupportsAiClassification())->toBeFalse();
    expect($project->supportsAiClassification())->toBeFalse();
});

it('detects tier restriction when release >= v1.16.0 but tier is FREE and promo is off', function () {
    $releaseNew = ApisHubRelease::create([
        'version_tag' => 'v1.16.0',
        'is_active' => true,
    ]);

    $freeProfile = $this->user->billingProfiles()->first();
    $freeProfile->update(['tier' => UserTier::FREE]);

    $project = Project::factory()->create([
        'user_id' => $this->user->id,
        'billing_profile_id' => $freeProfile->id,
        'apis_hub_release_id' => $releaseNew->id,
    ]);

    $settings = app(FeatureSettings::class);
    $settings->enable_free_ai_classification = false;
    $settings->save();

    expect($project->releaseSupportsAiClassification())->toBeTrue();
    expect($project->supportsAiClassification())->toBeFalse();
    expect($this->gate->canAccess('ai_classification', $project))->toBeFalse();
});

it('allows ai classification when release >= v1.16.0 and promo is enabled for FREE tier', function () {
    $releaseNew = ApisHubRelease::create([
        'version_tag' => 'v1.16.0',
        'is_active' => true,
    ]);

    $freeProfile = $this->user->billingProfiles()->first();
    $freeProfile->update(['tier' => UserTier::FREE]);

    $project = Project::factory()->create([
        'user_id' => $this->user->id,
        'billing_profile_id' => $freeProfile->id,
        'apis_hub_release_id' => $releaseNew->id,
    ]);

    $settings = app(FeatureSettings::class);
    $settings->enable_free_ai_classification = true;
    $settings->save();

    expect($project->releaseSupportsAiClassification())->toBeTrue();
    expect($project->supportsAiClassification())->toBeTrue();
    expect($this->gate->canAccess('ai_classification', $project))->toBeTrue();
});

it('allows ai classification when release >= v1.16.0 and tier is PRO', function () {
    $releaseNew = ApisHubRelease::create([
        'version_tag' => 'v1.16.0',
        'is_active' => true,
    ]);

    $proProfile = $this->user->billingProfiles()->first();
    $proProfile->update(['tier' => UserTier::PRO]);

    $project = Project::factory()->create([
        'user_id' => $this->user->id,
        'billing_profile_id' => $proProfile->id,
        'apis_hub_release_id' => $releaseNew->id,
    ]);

    $settings = app(FeatureSettings::class);
    $settings->enable_free_ai_classification = false;
    $settings->save();

    expect($project->releaseSupportsAiClassification())->toBeTrue();
    expect($project->supportsAiClassification())->toBeTrue();
    expect($this->gate->canAccess('ai_classification', $project))->toBeTrue();
});

it('prevents saving typesafe_api_key in project settings when project is tier restricted', function () {
    $releaseNew = ApisHubRelease::create([
        'version_tag' => 'v1.16.0',
        'is_active' => true,
    ]);

    $freeProfile = $this->user->billingProfiles()->first();
    $freeProfile->update(['tier' => UserTier::FREE]);

    $project = Project::factory()->create([
        'user_id' => $this->user->id,
        'billing_profile_id' => $freeProfile->id,
        'apis_hub_release_id' => $releaseNew->id,
        'typesafe_api_key' => null,
    ]);

    $settings = app(FeatureSettings::class);
    $settings->enable_free_ai_classification = false;
    $settings->save();

    \Filament\Facades\Filament::setTenant($project, isQuiet: true);

    $page = new \App\Filament\App\Pages\ProjectSettings();
    $reflection = new \ReflectionClass($page);
    $actionsMethod = $reflection->getMethod('getHeaderActions');
    $actionsMethod->setAccessible(true);
    $actions = $actionsMethod->invoke($page);

    $editAction = collect($actions)->first(fn ($a) => $a->getName() === 'edit_settings');
    expect($editAction)->not->toBeNull();

    // Call the action with test data attempting to submit an API key
    $actionClosure = $editAction->getActionFunction();
    expect($actionClosure)->not->toBeNull();

    $actionClosure([
        'timezone' => 'UTC',
        'supported_locales' => ['en'],
        'typesafe_api_key' => 'unauthorized_free_key_attempt',
    ]);

    // Key must NOT be saved because the project does not support AI classification (tier restricted)
    expect($project->fresh()->typesafe_api_key)->toBeNull();
});

it('allows saving typesafe_api_key in project settings when project is on PRO tier', function () {
    $releaseNew = ApisHubRelease::create([
        'version_tag' => 'v1.16.0',
        'is_active' => true,
    ]);

    $proProfile = $this->user->billingProfiles()->first();
    $proProfile->update(['tier' => UserTier::PRO]);

    $project = Project::factory()->create([
        'user_id' => $this->user->id,
        'billing_profile_id' => $proProfile->id,
        'apis_hub_release_id' => $releaseNew->id,
        'typesafe_api_key' => null,
    ]);

    \Filament\Facades\Filament::setTenant($project, isQuiet: true);

    expect($project->releaseSupportsAiClassification())->toBeTrue();
    expect($project->supportsAiClassification())->toBeTrue();

    $page = new \App\Filament\App\Pages\ProjectSettings();
    $reflection = new \ReflectionClass($page);
    $getProjMethod = $reflection->getMethod('getProject');
    $getProjMethod->setAccessible(true);
    $activeProj = $getProjMethod->invoke($page);
    expect($activeProj->id)->toBe($project->id);

    $actionsMethod = $reflection->getMethod('getHeaderActions');
    $actionsMethod->setAccessible(true);
    $actions = $actionsMethod->invoke($page);

    $editAction = collect($actions)->first(fn ($a) => $a->getName() === 'edit_settings');
    expect($editAction)->not->toBeNull();

    // Directly call the action closure to inspect execution
    $actionClosure = $editAction->getActionFunction();
    expect($actionClosure)->not->toBeNull();

    $actionClosure([
        'timezone' => 'UTC',
        'supported_locales' => ['en'],
        'typesafe_api_key' => 'valid_pro_key_12345',
    ]);

    expect($project->fresh()->typesafe_api_key)->toBe('valid_pro_key_12345');
});
