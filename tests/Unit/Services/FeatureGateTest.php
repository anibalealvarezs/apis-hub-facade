<?php

use App\Enums\UserTier;
use App\Models\BillingProfile;
use App\Models\Project;
use App\Models\User;
use App\Services\FeatureGateService;
use App\Settings\FeatureSettings;

beforeEach(function () {
    $this->gate = app(FeatureGateService::class);
});

it('returns correct minimum tiers for features', function () {
    expect($this->gate->getMinimumTierForFeature('public_dashboards'))->toBe(UserTier::PRO);
    expect($this->gate->getMinimumTierForFeature('ai_classification'))->toBe(UserTier::PRO);
    expect($this->gate->getMinimumTierForFeature('invite_collaborators'))->toBe(UserTier::ULTRA);
    expect($this->gate->getMinimumTierForFeature('share_billing_profiles'))->toBe(UserTier::ENTERPRISE);
});

it('restricts public dashboards on free tier and allows on pro or higher', function () {
    $user = User::factory()->make(['id' => 1]);
    $freeProfile = new BillingProfile(['tier' => UserTier::FREE, 'user_id' => 1]);
    $proProfile = new BillingProfile(['tier' => UserTier::PRO, 'user_id' => 1]);

    $freeProject = new Project(['id' => 10]);
    $freeProject->setRelation('billingProfile', $freeProfile);

    $proProject = new Project(['id' => 20]);
    $proProject->setRelation('billingProfile', $proProfile);

    expect($this->gate->canAccess('public_dashboards', $freeProject))->toBeFalse();
    expect($this->gate->canAccess('public_dashboards', $proProject))->toBeTrue();
});

it('generates correct upgrade context for billing profile owner vs collaborator', function () {
    $owner = User::factory()->make(['id' => 1, 'name' => 'Owner Alice']);
    $collaborator = User::factory()->make(['id' => 2, 'name' => 'Collaborator Bob']);

    $profile = new BillingProfile([
        'tier' => UserTier::FREE,
        'user_id' => 1,
    ]);
    $profile->id = 55;
    $profile->setRelation('user', $owner);

    $project = new Project(['id' => 10, 'user_id' => 1]);
    $project->setRelation('billingProfile', $profile);

    // As Owner
    $ownerCtx = $this->gate->getUpgradeContext('public_dashboards', $project, $owner);
    expect($ownerCtx['is_profile_owner'])->toBeTrue()
        ->and($ownerCtx['required_tier'])->toBe(UserTier::PRO)
        ->and($ownerCtx['current_tier'])->toBe(UserTier::FREE)
        ->and($ownerCtx['upgrade_url'])->toContain('profile=55');

    // As Collaborator (non-owner)
    $collabCtx = $this->gate->getUpgradeContext('public_dashboards', $project, $collaborator);
    expect($collabCtx['is_profile_owner'])->toBeFalse()
        ->and($collabCtx['profile_owner_name'])->toBe('Owner Alice')
        ->and($collabCtx['upgrade_url'])->toBeNull();
});
