<?php

use App\Models\User;
use App\Models\Project;
use App\Models\BillingProfile;
use App\Enums\UserTier;
use App\Jobs\SuspendProjectDomainJob;
use App\Jobs\StopProjectContainersJob;
use App\Services\ProjectTransferService;
use Illuminate\Support\Facades\Queue;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

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

it('prevents creating multiple FREE billing profiles for a single user', function () {
    // The user already has a FREE billing profile created by default (in our test beforeEach,
    // we updated that to PRO, so let's first create a new FREE one).
    $freeProfile1 = BillingProfile::create([
        'user_id' => $this->user->id,
        'name' => 'Free Profile 1',
        'type' => 'company',
        'tier' => UserTier::FREE,
        'country_code' => 'US',
        'is_default' => false,
    ]);

    expect($freeProfile1->tier)->toBe(UserTier::FREE);

    // Trying to create a second FREE billing profile should throw exception
    expect(fn () => BillingProfile::create([
        'user_id' => $this->user->id,
        'name' => 'Free Profile 2',
        'type' => 'company',
        'tier' => UserTier::FREE,
        'country_code' => 'US',
        'is_default' => false,
    ]))->toThrow(InvalidArgumentException::class, 'Ya tienes un perfil de facturación de plan gratuito ("free"). Por favor, sube de nivel tu perfil actual.');
});

it('suspends billing profile and projects on downgrade to FREE if another FREE profile already exists', function () {
    Queue::fake();

    // 1. Create a FREE profile for the user
    $freeProfile1 = BillingProfile::create([
        'user_id' => $this->user->id,
        'name' => 'Free Profile 1',
        'type' => 'company',
        'tier' => UserTier::FREE,
        'country_code' => 'US',
        'is_default' => false,
    ]);

    // 2. We already have $this->billingProfile set to PRO with 3 active projects in beforeEach.
    // Let's attempt to downgrade this PRO profile to FREE.
    // Since another FREE profile ($freeProfile1) exists, this should trigger SUSPENDED tier instead!
    $this->billingProfile->update(['tier' => UserTier::FREE]);

    $this->billingProfile->refresh();
    expect($this->billingProfile->tier)->toBe(UserTier::SUSPENDED);
    expect($this->billingProfile->status)->toBe('suspended');

    // And all projects of this profile should be suspended!
    foreach ($this->projects as $project) {
        $project->refresh();
        expect($project->billing_status)->toBe('suspended');
        expect($project->is_active)->toBeFalse();
    }
});

it('displays the custom pre-downgrade warning modal description if another free profile exists', function () {
    // 1. Create a FREE profile for the user
    $freeProfile1 = BillingProfile::create([
        'user_id' => $this->user->id,
        'name' => 'Free Profile 1',
        'type' => 'company',
        'tier' => UserTier::FREE,
        'country_code' => 'US',
        'is_default' => false,
    ]);

    // Authenticate the user
    actingAs($this->user);

    $page = new \App\Filament\Account\Pages\AccountSubscription();
    $page->selectedProfileId = $this->billingProfile->id;

    $reflection = new \ReflectionClass($page);
    $method = $reflection->getMethod('getHeaderActions');
    $method->setAccessible(true);
    $actions = $method->invoke($page);

    $action = collect($actions)->firstWhere(fn ($a) => $a->getName() === 'downgradeToFree');
    $description = value($action->getModalDescription());
    
    expect($description)->toContain('Ya tienes otro perfil de facturación gratuito.');
    expect($description)->toContain('SUSPENDIDO');
});

it('throws the polite invitation error message when a free tier user attempts to accept a project invitation beyond their quota', function () {
    // Attach user to the projects
    foreach ($this->projects as $project) {
        $project->users()->attach($this->user->id);
    }

    // Set user's billing profile back to FREE
    $this->billingProfile->update(['tier' => UserTier::FREE]);

    // 1. Authenticate user
    actingAs($this->user);
    
    // Total accessible projects count is currently 3 (since we created 3 in beforeEach).
    // So the user definitely has >= 1 projects on FREE tier.
    
    // Create a project invitation for this user
    $project = Project::factory()->create(['user_id' => User::factory()->create()->id]); // Belongs to someone else
    $invitation = \App\Models\ProjectInvitation::create([
        'project_id' => $project->id,
        'email' => $this->user->email,
        'role' => 'collaborator',
        'token' => 'test-invitation-token',
        'status' => 'pending',
        'expires_at' => now()->addDays(7),
    ]);
    
    // Try to accept the invitation
    $response = get('/app/invitations/test-invitation-token/accept');
    
    // Should redirect to app with our polite error message
    expect($response->headers->get('Location'))->toContain('/app');
    $response->assertSessionHasErrors(['invitation']);
    
    $errors = session('errors')->getBag('default')->get('invitation');
    expect($errors[0])->toBe('Si solo se cuenta con un perfil propio free tier, solo se puede acceder a un único proyecto. Para poder acceder a un proyecto como colaborador, debe eliminar el proyecto de su perfil free tier.');
});
