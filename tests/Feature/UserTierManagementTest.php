<?php

use App\Models\User;
use App\Models\Project;
use App\Models\BillingProfile;
use App\Enums\UserTier;
use App\Jobs\SuspendProjectDomainJob;
use App\Jobs\StopProjectContainersJob;
use App\Services\ProjectTransferService;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->user = User::factory()->create();

    // The UserObserver automatically created a default FREE billing profile.
    // Let's fetch it and update it to PRO tier for our test baseline.
    $this->billingProfile = $this->user->billingProfiles()->first();
    $this->billingProfile->update([
        'tier' => UserTier::PRO,
    ]);

    // Create 3 active projects associated with this billing profile
    $this->projects = collect(range(1, 3))->map(fn ($i) => Project::factory()->create([
        'user_id' => $this->user->id,
        'billing_profile_id' => $this->billingProfile->id,
        'is_active' => true,
        'billing_status' => 'active',
        'subdomain' => "test-project-{$i}",
    ]));
});

it('prevents Enterprise profiles from being downgraded', function () {
    $enterpriseProfile = BillingProfile::create([
        'user_id' => $this->user->id,
        'name' => 'Enterprise Profile',
        'type' => 'company',
        'tier' => UserTier::ENTERPRISE,
        'country_code' => 'US',
        'is_default' => false,
    ]);

    expect(fn () => $enterpriseProfile->update(['tier' => UserTier::PRO]))
        ->toThrow(InvalidArgumentException::class, 'Enterprise users cannot be downgraded to protect system-level tracking.');
});

it('cascades suspension to projects when a billing profile status is updated to suspended', function () {
    Queue::fake();

    // SUSPENDED tier has limit of 0 projects, so all 3 projects should be suspended
    $this->billingProfile->update(['tier' => UserTier::SUSPENDED]);

    foreach ($this->projects as $project) {
        $project->refresh();
        expect($project->billing_status)->toBe('suspended');
        expect($project->is_active)->toBeFalse();
    }

    Queue::assertPushed(SuspendProjectDomainJob::class, 3);
    Queue::assertPushed(StopProjectContainersJob::class, 3);
});

it('cascades suspension to excess projects when a billing profile is downgraded to FREE', function () {
    Queue::fake();

    // FREE tier has limit of 1 project.
    // The profile had 3 active projects.
    // The service suspends the newest 2 (the ones created last, i.e., index 1 and 2 of projects collection if sorted by created_at desc).
    $this->billingProfile->update(['tier' => UserTier::FREE]);

    // Sort projects by created_at desc to know which ones are suspended
    $sortedProjects = $this->projects->sortByDesc('created_at')->values();

    // The first two sorted (newest) should be suspended
    expect($sortedProjects[0]->refresh()->billing_status)->toBe('suspended');
    expect($sortedProjects[0]->is_active)->toBeFalse();

    expect($sortedProjects[1]->refresh()->billing_status)->toBe('suspended');
    expect($sortedProjects[1]->is_active)->toBeFalse();

    // The oldest project (index 2) should remain active
    expect($sortedProjects[2]->refresh()->billing_status)->toBe('active');
    expect($sortedProjects[2]->is_active)->toBeTrue();

    Queue::assertPushed(SuspendProjectDomainJob::class, 2);
    Queue::assertPushed(StopProjectContainersJob::class, 2);
});

it('requires third-party billing profile approval upon transfer', function () {
    $recipient = User::factory()->create();
    $recipientProfile = $recipient->billingProfiles()->first();
    $recipientProfile->update(['tier' => UserTier::PRO]);

    // An external third-party user A (padre) owns this billing profile
    $padre = User::factory()->create();
    $padreProfile = $padre->billingProfiles()->first();
    $padreProfile->update(['tier' => UserTier::PRO]);

    // Padre shares their billing profile with the recipient
    $padreProfile->sharedWithUsers()->attach($recipient->id, ['role' => 'shared_payer']);

    $projectToTransfer = $this->projects->first();

    $transferService = app(ProjectTransferService::class);

    // Recipient accepts the transfer and assigns Padre's billing profile
    $transferService->executeTransfer($projectToTransfer, $recipient, $padreProfile, false);

    $projectToTransfer->refresh();
    // Since Padre's profile is third-party, it must be pending_approval and inactive!
    expect($projectToTransfer->billing_status)->toBe('pending_approval');
    expect($projectToTransfer->is_active)->toBeFalse();
    expect($projectToTransfer->user_id)->toBe($recipient->id); // Technical owner is the recipient B
    expect($projectToTransfer->billing_profile_id)->toBe($padreProfile->id); // Financial billing profile belongs to Padre A
});

it('allows immediate active transfer when recipient assigns their own profile', function () {
    $recipient = User::factory()->create();
    $recipientProfile = $recipient->billingProfiles()->first();
    $recipientProfile->update(['tier' => UserTier::PRO]);

    $projectToTransfer = $this->projects->first();

    $transferService = app(ProjectTransferService::class);

    // Recipient accepts and assigns their own billing profile
    $transferService->executeTransfer($projectToTransfer, $recipient, $recipientProfile, false);

    $projectToTransfer->refresh();
    // Automatically active!
    expect($projectToTransfer->billing_status)->toBe('active');
    expect($projectToTransfer->is_active)->toBeTrue();
    expect($projectToTransfer->user_id)->toBe($recipient->id);
    expect($projectToTransfer->billing_profile_id)->toBe($recipientProfile->id);
});
