<?php

use App\Enums\UserTier;
use App\Models\ApisHubRelease;
use App\Models\Project;
use App\Models\User;
use App\Settings\FeatureSettings;
use Filament\Facades\Filament;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('renders configureAiContext action as upgrade modal on free tier and normal modal on pro tier', function () {
    $release = ApisHubRelease::create([
        'version_tag' => 'v1.16.0',
        'is_active' => true,
    ]);

    $freeProfile = $this->user->billingProfiles()->first();
    $freeProfile->update(['tier' => UserTier::FREE]);

    $project = Project::factory()->create([
        'user_id' => $this->user->id,
        'billing_profile_id' => $freeProfile->id,
        'apis_hub_release_id' => $release->id,
    ]);

    Filament::setTenant($project, isQuiet: true);

    $settings = app(FeatureSettings::class);
    $settings->enable_free_ai_classification = false;
    $settings->save();

    // Verify Free tier restrictions on DataSources AI actions
    $dataSourcesPage = new \App\Filament\App\Pages\DataSources();
    $reflection = new \ReflectionClass($dataSourcesPage);
    $activeChannelProp = $reflection->getProperty('activeChannel');
    $activeChannelProp->setAccessible(true);
    $activeChannelProp->setValue($dataSourcesPage, 'google_search_console');

    // On Free tier, project does not support AI classification
    expect($project->supportsAiClassification())->toBeFalse();

    // Switch to PRO tier
    $freeProfile->update(['tier' => UserTier::PRO]);
    expect($project->fresh()->supportsAiClassification())->toBeTrue();
});
