<?php

use App\Filament\App\Pages\ApiAccessReference;
use App\Filament\App\Pages\SyncSettings;
use App\Models\Project;
use App\Models\User;
use App\Notifications\ApiKeyRotatedNotification;
use App\Services\DeployerService;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->owner = User::factory()->create(['name' => 'Project Owner']);
    $this->editor = User::factory()->create(['name' => 'Project Editor']);
    $this->viewer = User::factory()->create(['name' => 'Project Viewer']);
    $this->outsider = User::factory()->create(['name' => 'Outsider User']);

    \Spatie\Permission\Models\Permission::findOrCreate('edit_preferences', 'web');
    $this->owner->givePermissionTo('edit_preferences');
    $this->editor->givePermissionTo('edit_preferences');

    $this->billingProfile = \App\Models\BillingProfile::create([
        'user_id' => $this->owner->id,
        'name' => 'Owner Billing Profile',
        'type' => 'personal',
        'tier' => \App\Enums\UserTier::ULTRA,
        'status' => 'active',
        'is_default' => true,
    ]);

    $this->project = Project::factory()->create([
        'user_id' => $this->owner->id,
        'billing_profile_id' => $this->billingProfile->id,
        'subdomain' => 'test-api-node',
        'is_active' => true,
        'public_api_key' => 'initial_api_key_1234567890abcdef',
    ]);

    $this->project->users()->attach([
        $this->owner->id,
        $this->editor->id,
        $this->viewer->id,
    ]);

    $ownerRole = Role::findOrCreate('project_owner', 'web');
    $editorRole = Role::findOrCreate('project_editor', 'web');
    $viewerRole = Role::findOrCreate('project_viewer', 'web');

    DB::table('model_has_roles')->insert([
        [
            'role_id' => $ownerRole->id,
            'model_type' => User::class,
            'model_id' => $this->owner->id,
            'project_id' => $this->project->id,
        ],
        [
            'role_id' => $editorRole->id,
            'model_type' => User::class,
            'model_id' => $this->editor->id,
            'project_id' => $this->project->id,
        ],
        [
            'role_id' => $viewerRole->id,
            'model_type' => User::class,
            'model_id' => $this->viewer->id,
            'project_id' => $this->project->id,
        ],
    ]);
});

it('rotates API key from ApiAccessReference page and pushes to remote node', function () {
    Notification::fake();

    $mockDeployer = Mockery::mock(DeployerService::class);
    $mockDeployer->shouldReceive('updateCredentials')
        ->once()
        ->withArgs(function ($project, $creds) {
            return $project->id === $this->project->id
                && isset($creds['APP_API_KEY'])
                && isset($creds['TOKEN_AUTHORITY_BEARER'])
                && $creds['APP_API_KEY'] === $creds['TOKEN_AUTHORITY_BEARER']
                && strlen($creds['APP_API_KEY']) === 64
                && $creds['APP_API_KEY'] !== 'initial_api_key_1234567890abcdef';
        })
        ->andReturn(['success' => true]);

    app()->instance(DeployerService::class, $mockDeployer);

    actingAs($this->owner);
    Filament::setTenant($this->project);

    $initialKey = $this->project->fresh()->public_api_key;

    Livewire::test(ApiAccessReference::class)
        ->assertFormSet(['app_api_key' => $initialKey])
        ->callFormComponentAction('app_api_key', 'rotateKey')
        ->assertHasNoFormErrors();

    // 1. Verify Project in database has the new rotated key
    $freshProject = $this->project->fresh();
    expect($freshProject->public_api_key)->not->toBe($initialKey);
    expect(strlen($freshProject->public_api_key))->toBe(64);

    // 2. Verify notifications sent to all editors and owners (but not viewers or outsiders)
    Notification::assertSentTo([$this->owner, $this->editor], ApiKeyRotatedNotification::class);
    Notification::assertNotSentTo([$this->viewer, $this->outsider], ApiKeyRotatedNotification::class);
});

it('rotates API key from SyncSettings page and dispatches notifications', function () {
    Notification::fake();

    $mockDeployer = Mockery::mock(DeployerService::class);
    $mockDeployer->shouldReceive('updateCredentials')
        ->once()
        ->withArgs(function ($project, $creds) {
            return $project->id === $this->project->id
                && !empty($creds['APP_API_KEY'])
                && $creds['APP_API_KEY'] === $creds['TOKEN_AUTHORITY_BEARER'];
        })
        ->andReturn(['success' => true]);

    app()->instance(DeployerService::class, $mockDeployer);

    actingAs($this->editor);
    Filament::setTenant($this->project);

    $initialKey = $this->project->fresh()->public_api_key;

    Livewire::test(SyncSettings::class)
        ->assertFormSet(['app_api_key' => $initialKey])
        ->callFormComponentAction('app_api_key', 'rotateKey')
        ->assertHasNoFormErrors();

    // 1. Database updated
    $freshProject = $this->project->fresh();
    expect($freshProject->public_api_key)->not->toBe($initialKey);

    // 2. Notifications verified
    Notification::assertSentTo([$this->owner, $this->editor], ApiKeyRotatedNotification::class);
    Notification::assertNotSentTo([$this->viewer], ApiKeyRotatedNotification::class);
});

it('correctly resolves getEditorsAndOwners for a project', function () {
    $recipients = $this->project->getEditorsAndOwners();

    expect($recipients->pluck('id')->all())->toContain($this->owner->id);
    expect($recipients->pluck('id')->all())->toContain($this->editor->id);
    expect($recipients->pluck('id')->all())->not->toContain($this->viewer->id);
    expect($recipients->pluck('id')->all())->not->toContain($this->outsider->id);
});

it('serves the public API documentation placeholder page', function () {
    $response = $this->get(route('docs.api'));
    $response->assertStatus(200);
    $response->assertSee('APIs Hub Developer Reference');
    $response->assertSee('POST /{channel}/metric/aggregate');

    $responseEs = $this->get(route('docs.api.es'));
    $responseEs->assertStatus(200);
    $responseEs->assertSee('Documentación de API y Especificación OpenAPI');
});

it('disables API access and rotation when project or billing profile is downgraded or suspended', function () {
    // 1. Initial State: Ultra tier and active -> API credentials section visible, rotateKey action exists
    actingAs($this->editor);
    Filament::setTenant($this->project);

    Livewire::test(SyncSettings::class)
        ->assertFormFieldExists('app_api_key')
        ->assertFormComponentActionExists('app_api_key', 'rotateKey');

    Livewire::test(ApiAccessReference::class)
        ->assertFormComponentActionEnabled('app_api_key', 'rotateKey');

    // 2. Downgrade to PRO: API credentials hidden on SyncSettings, upgrade required placeholder shown
    $this->billingProfile->update(['tier' => \App\Enums\UserTier::PRO]);

    Livewire::test(SyncSettings::class)
        ->assertFormFieldDoesNotExist('app_api_key')
        ->assertSee('Upgrade Required');

    // 3. Upgrade back to ENTERPRISE: API credentials re-enabled
    $this->billingProfile->update(['tier' => \App\Enums\UserTier::ENTERPRISE]);

    Livewire::test(SyncSettings::class)
        ->assertFormFieldExists('app_api_key')
        ->assertFormComponentActionExists('app_api_key', 'rotateKey')
        ->assertDontSee('Upgrade Required');

    // 4. Suspend project: rotateKey action becomes disabled on both pages
    $this->project->update([
        'billing_status' => 'suspended',
        'is_active' => false,
    ]);

    Livewire::test(ApiAccessReference::class)
        ->assertFormComponentActionDisabled('app_api_key', 'rotateKey');

    Livewire::test(SyncSettings::class)
        ->assertFormComponentActionDisabled('app_api_key', 'rotateKey');

    // 5. Unsuspend project: rotateKey action becomes enabled again
    $this->project->update([
        'billing_status' => 'active',
        'is_active' => true,
    ]);

    Livewire::test(ApiAccessReference::class)
        ->assertFormComponentActionEnabled('app_api_key', 'rotateKey');

    Livewire::test(SyncSettings::class)
        ->assertFormComponentActionEnabled('app_api_key', 'rotateKey');
});

it('updates remote rate limit and routing when BillingLifecycleService enforces tier changes and suspension', function () {
    $service = app(\App\Services\BillingLifecycleService::class);

    // Initial state: Ultra tier -> 500 req/min
    expect($service->canAccessApi(\App\Enums\UserTier::ULTRA))->toBeTrue();
    expect($service->getApiRateLimitForTier(\App\Enums\UserTier::ULTRA))->toBe(500);

    // Downgrade to Pro -> 0 req/min, canAccessApi false
    expect($service->canAccessApi(\App\Enums\UserTier::PRO))->toBeFalse();
    expect($service->getApiRateLimitForTier(\App\Enums\UserTier::PRO))->toBe(0);

    // Suspended -> 0 req/min, canAccessApi false
    expect($service->canAccessApi(\App\Enums\UserTier::SUSPENDED))->toBeFalse();
    expect($service->getApiRateLimitForTier(\App\Enums\UserTier::SUSPENDED))->toBe(0);

    // Verify DeployerService pushes updated rate limit on enforceTierLimits
    $mockDeployer = Mockery::mock(DeployerService::class);
    $mockDeployer->shouldReceive('updateCredentials')
        ->once()
        ->withArgs(function ($project, $creds) {
            return $project->id === $this->project->id
                && isset($creds['API_RATE_LIMIT_PER_MINUTE'])
                && $creds['API_RATE_LIMIT_PER_MINUTE'] === 1000;
        })
        ->andReturn(['success' => true]);

    app()->instance(DeployerService::class, $mockDeployer);

    // Enforce Enterprise tier upgrade
    $service->enforceTierLimits($this->billingProfile, \App\Enums\UserTier::ENTERPRISE);

    expect($this->billingProfile->fresh()->tier)->toBe(\App\Enums\UserTier::ENTERPRISE);
    expect($service->getApiRateLimitForTier($this->billingProfile->fresh()->tier))->toBe(1000);
});
