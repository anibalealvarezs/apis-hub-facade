<?php

use App\Filament\App\Pages\McpAccessReference;
use App\Models\AssetGroup;
use App\Models\AssetGroupItem;
use App\Models\BillingProfile;
use App\Models\Project;
use App\Models\ProjectUserAssetGroup;
use App\Models\User;
use App\Notifications\ApiKeyRotatedNotification;
use App\Notifications\UserApiKeyRotatedNotification;
use App\Services\CollaboratorAssetAccessService;
use App\Services\DeployerService;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->owner = User::factory()->create(['name' => 'Owner Alice', 'email' => 'owner@example.com']);
    $this->editor = User::factory()->create(['name' => 'Editor Bob', 'email' => 'editor@example.com']);
    $this->viewer = User::factory()->create(['name' => 'Viewer Charlie', 'email' => 'viewer@example.com']);
    $this->outsider = User::factory()->create(['name' => 'Outsider Dave', 'email' => 'outsider@example.com']);

    $this->billingProfile = BillingProfile::create([
        'user_id' => $this->owner->id,
        'name' => 'Enterprise Billing Profile',
        'type' => 'company',
        'tier' => \App\Enums\UserTier::ULTRA,
        'status' => 'active',
        'is_default' => true,
    ]);

    $this->project = Project::factory()->create([
        'user_id' => $this->owner->id,
        'billing_profile_id' => $this->billingProfile->id,
        'subdomain' => 'scoped-test-node',
        'is_active' => true,
        'public_api_key' => 'master_tenant_api_key_initial_12345678',
        'sync_config' => [
            'facebook_marketing' => [
                'ad_accounts' => [
                    ['id' => 'act_101', 'name' => 'US Retail Ads', 'enabled' => true],
                    ['id' => 'act_202', 'name' => 'EU Apparel Ads', 'enabled' => true],
                    ['id' => 'act_303', 'name' => 'Global Brand Ads', 'enabled' => true],
                ],
            ],
            'google_search_console' => [
                'sites' => [
                    ['id' => 'https://example.com/us/', 'enabled' => true],
                    ['id' => 'https://example.com/eu/', 'enabled' => true],
                ],
            ],
        ],
    ]);

    // Attach project_user pivot with unrestricted flags
    DB::table('project_user')->insert([
        [
            'project_id' => $this->project->id,
            'user_id' => $this->owner->id,
            'asset_access_unrestricted' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'project_id' => $this->project->id,
            'user_id' => $this->editor->id,
            'asset_access_unrestricted' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'project_id' => $this->project->id,
            'user_id' => $this->viewer->id,
            'asset_access_unrestricted' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ],
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

    // Create 2 Asset Groups: Group 1 (US Only) and Group 2 (EU Only)
    $this->groupUs = AssetGroup::create([
        'project_id' => $this->project->id,
        'name' => 'US Assets',
    ]);
    AssetGroupItem::create([
        'asset_group_id' => $this->groupUs->id,
        'channel' => 'facebook_marketing',
        'asset_type' => 'ad_account',
        'asset_id' => 'act_101',
    ]);
    AssetGroupItem::create([
        'asset_group_id' => $this->groupUs->id,
        'channel' => 'google_search_console',
        'asset_type' => 'site',
        'asset_id' => 'https://example.com/us/',
    ]);

    $this->groupEu = AssetGroup::create([
        'project_id' => $this->project->id,
        'name' => 'EU Assets',
    ]);
    AssetGroupItem::create([
        'asset_group_id' => $this->groupEu->id,
        'channel' => 'facebook_marketing',
        'asset_type' => 'ad_account',
        'asset_id' => 'act_202',
    ]);

    // Assign ONLY Group US to Viewer Charlie
    ProjectUserAssetGroup::create([
        'project_id' => $this->project->id,
        'user_id' => $this->viewer->id,
        'asset_group_id' => $this->groupUs->id,
    ]);
});

it('segregates master vs user-scoped API key visibility based on project role', function () {
    // 1. Owner sees Master Key
    actingAs($this->owner);
    Filament::setTenant($this->project);

    $page = Livewire::test(McpAccessReference::class)
        ->assertOk()
        ->assertSee('Tenant Public API Key (Master Key)')
        ->assertDontSee('Personal Scoped API Key')
        ->assertSee('Viewer & Collaborator Scoped API Keys');

    expect($page->get('isEditorOrOwner'))->toBeTrue();

    // 2. Editor sees Master Key and collaborator table
    actingAs($this->editor);
    Filament::setTenant($this->project);

    $editorPage = Livewire::test(McpAccessReference::class)
        ->assertOk()
        ->assertSee('Tenant Public API Key (Master Key)')
        ->assertSee('Viewer & Collaborator Scoped API Keys');

    expect($editorPage->get('isEditorOrOwner'))->toBeTrue();

    // 3. Viewer does NOT see Master Key label or collaborator table, sees Personal Scoped Key label
    actingAs($this->viewer);
    Filament::setTenant($this->project);

    $viewerPage = Livewire::test(McpAccessReference::class)
        ->assertOk()
        ->assertSee('Personal Scoped API Key')
        ->assertDontSee('Tenant Public API Key (Master Key)')
        ->assertDontSee('Viewer & Collaborator Scoped API Keys');

    expect($viewerPage->get('isEditorOrOwner'))->toBeFalse();
});

it('correctly isolates asset groups and channel items for scoped viewer user', function () {
    $service = app(CollaboratorAssetAccessService::class);

    // 1. Owner has unrestricted access (null return or full query)
    $ownerAllowedQuery = $service->getAllowedAssetGroupQuery($this->project, $this->owner->id);
    expect($ownerAllowedQuery->count())->toBe(2);

    // 2. Viewer only has access to assigned Group US (1 group)
    $viewerAllowedQuery = $service->getAllowedAssetGroupQuery($this->project, $this->viewer->id);
    expect($viewerAllowedQuery->pluck('id')->toArray())->toEqual([$this->groupUs->id]);

    $sharedGroupIds = $service->getSharedAssetGroupIds($this->project, $this->viewer->id);
    expect($sharedGroupIds)->toBe([$this->groupUs->id]);

    // Check specific channel asset access
    $allowedAssets = $service->getAllowedAssetIdsForChannel($this->project, $this->viewer->id, 'facebook_marketing');
    expect($allowedAssets)->toContain('act_101');
    expect($allowedAssets)->not->toContain('act_202');
    expect($allowedAssets)->not->toContain('act_303');

    // Outsider has 0 asset groups
    expect($service->getSharedAssetGroupIds($this->project, $this->outsider->id))->toBe([]);
});

it('allows owners and editors to force-rotate a viewer API key and dispatches dual notifications', function () {
    Notification::fake();

    actingAs($this->owner);
    Filament::setTenant($this->project);

    Livewire::test(McpAccessReference::class)
        ->call('forceRotateCollaboratorKey', $this->viewer->id);

    // Notification must be sent to the viewer whose key was force rotated
    Notification::assertSentTo(
        [$this->viewer],
        UserApiKeyRotatedNotification::class,
        function (UserApiKeyRotatedNotification $notification) {
            $channels = $notification->via($this->viewer);
            expect($channels)->toContain('mail');
            expect($channels)->toContain('database');
            expect($notification->isForced)->toBeTrue();
            expect($notification->project->id)->toBe($this->project->id);
            expect($notification->rotatedBy->id)->toBe($this->owner->id);

            // Test mail contents
            $mail = $notification->toMail($this->viewer);
            expect($mail->subject)->toContain('Security Alert');
            expect($mail->greeting)->toContain('Viewer Charlie');
            expect($mail->introLines[0])->toContain('Owner Alice');

            // Test in-app database payload
            $dbMsg = $notification->toDatabase($this->viewer);
            expect($dbMsg['title'])->toBe('Personal API Key Rotated');
            expect($dbMsg['body'])->toContain('Owner Alice');

            return true;
        }
    );

    // Outsider and editor must NOT receive this viewer's notification
    Notification::assertNotSentTo([$this->outsider, $this->editor], UserApiKeyRotatedNotification::class);
});

it('allows viewers to self-rotate their personal API key with self-rotation notification', function () {
    Notification::fake();

    actingAs($this->viewer);
    Filament::setTenant($this->project);

    Livewire::test(McpAccessReference::class)
        ->callFormComponentAction('app_api_key', 'rotateKey');

    // Self-rotation dispatches notification with isForced = false
    Notification::assertSentTo(
        [$this->viewer],
        UserApiKeyRotatedNotification::class,
        function (UserApiKeyRotatedNotification $notification) {
            expect($notification->isForced)->toBeFalse();
            expect($notification->rotatedBy->id)->toBe($this->viewer->id);

            $mail = $notification->toMail($this->viewer);
            expect($mail->introLines[0])->toContain('rotated successfully');

            return true;
        }
    );

    // Owner and editor are NOT alerted for individual viewer's self-rotation
    Notification::assertNotSentTo([$this->owner, $this->editor], UserApiKeyRotatedNotification::class);
    Notification::assertNotSentTo([$this->owner, $this->editor], ApiKeyRotatedNotification::class);
});

it('ensures project helper isEditorOrOwner matches permissions accurately', function () {
    expect($this->project->isEditorOrOwner($this->owner))->toBeTrue();
    expect($this->project->isEditorOrOwner($this->editor))->toBeTrue();
    expect($this->project->isEditorOrOwner($this->viewer))->toBeFalse();
    expect($this->project->isEditorOrOwner($this->outsider))->toBeFalse();
    expect($this->project->isEditorOrOwner(null))->toBeFalse();
});

it('synchronizes user_keys.json to the tenant node when a user API key is rotated', function () {
    $mockDeployer = Mockery::mock(\App\Services\DeployerService::class);
    $mockDeployer->shouldReceive('syncUserApiKeys')
        ->once()
        ->with(Mockery::on(fn ($p) => $p->id === $this->project->id))
        ->andReturn(true);

    $this->app->instance(\App\Services\DeployerService::class, $mockDeployer);

    actingAs($this->owner);
    Filament::setTenant($this->project);

    Livewire::test(McpAccessReference::class)
        ->call('forceRotateCollaboratorKey', $this->viewer->id);
});
