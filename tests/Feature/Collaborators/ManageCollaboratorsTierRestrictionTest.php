<?php

use App\Enums\UserTier;
use App\Models\BillingProfile;
use App\Models\Project;
use App\Models\User;
use App\Services\FeatureGateService;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $this->gate = app(FeatureGateService::class);
    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    // Grant manage_collaborators permission to user for testing actions visibility
    $permission = Permission::firstOrCreate(['name' => 'manage_collaborators']);
    $this->user->givePermissionTo($permission);
});

it('shows upgradeCollaborators action instead of invite action when on free tier', function () {
    $freeProfile = $this->user->billingProfiles()->first();
    $freeProfile->update(['tier' => UserTier::FREE]);

    $project = Project::factory()->create([
        'user_id' => $this->user->id,
        'billing_profile_id' => $freeProfile->id,
    ]);

    \Filament\Facades\Filament::setTenant($project, isQuiet: true);

    $page = new \App\Filament\App\Pages\ManageCollaborators();
    $table = $page->table(new \Filament\Tables\Table($page));
    $headerActions = $table->getHeaderActions();

    $inviteAction = collect($headerActions)->first(fn ($a) => $a->getName() === 'invite');
    $upgradeAction = collect($headerActions)->first(fn ($a) => $a->getName() === 'upgradeCollaborators');

    expect($inviteAction)->toBeNull();
    expect($upgradeAction)->not->toBeNull();
    expect($upgradeAction->getColor())->toBe('warning');
});

it('shows upgradeCollaborators action instead of invite action when on pro tier', function () {
    $proProfile = $this->user->billingProfiles()->first();
    $proProfile->update(['tier' => UserTier::PRO]);

    $project = Project::factory()->create([
        'user_id' => $this->user->id,
        'billing_profile_id' => $proProfile->id,
    ]);

    \Filament\Facades\Filament::setTenant($project, isQuiet: true);

    $page = new \App\Filament\App\Pages\ManageCollaborators();
    $table = $page->table(new \Filament\Tables\Table($page));
    $headerActions = $table->getHeaderActions();

    $inviteAction = collect($headerActions)->first(fn ($a) => $a->getName() === 'invite');
    $upgradeAction = collect($headerActions)->first(fn ($a) => $a->getName() === 'upgradeCollaborators');

    expect($inviteAction)->toBeNull();
    expect($upgradeAction)->not->toBeNull();
    expect($upgradeAction->getColor())->toBe('warning');
});

it('shows regular invite action when on ultra or enterprise tier', function () {
    $ultraProfile = $this->user->billingProfiles()->first();
    $ultraProfile->update(['tier' => UserTier::ULTRA]);

    $project = Project::factory()->create([
        'user_id' => $this->user->id,
        'billing_profile_id' => $ultraProfile->id,
    ]);

    \Filament\Facades\Filament::setTenant($project, isQuiet: true);

    $page = new \App\Filament\App\Pages\ManageCollaborators();
    $table = $page->table(new \Filament\Tables\Table($page));
    $headerActions = $table->getHeaderActions();

    $inviteAction = collect($headerActions)->first(fn ($a) => $a->getName() === 'invite');
    $upgradeAction = collect($headerActions)->first(fn ($a) => $a->getName() === 'upgradeCollaborators');

    expect($inviteAction)->not->toBeNull();
    expect($upgradeAction)->toBeNull();
});
