<?php

use App\Filament\App\Pages\ManageCollaborators;
use App\Models\AssetGroup;
use App\Models\AssetGroupItem;
use App\Models\Project;
use App\Models\ProjectUserAssetGroup;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use function Pest\Laravel\actingAs;

function makeRole(string $name): Role
{
    return Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
}

function makeProject(): Project
{
    return Project::factory()->create([
        'subdomain' => 'asset-modal-' . uniqid(),
        'sync_config' => [
            'facebook_marketing' => [
                'ad_accounts' => [
                    ['id' => 'act_111', 'enabled' => true, 'lost_access' => false],
                ],
            ],
        ],
    ]);
}

function addMember(User $user, Project $project, string $role = 'project_user', bool $unrestricted = false, array $groupIds = []): void
{
    DB::table('project_user')->insertOrIgnore([
        'project_id' => $project->id,
        'user_id' => $user->id,
        'asset_access_unrestricted' => $unrestricted,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('model_has_roles')->insertOrIgnore([
        'role_id' => makeRole($role)->id,
        'model_type' => User::class,
        'model_id' => $user->id,
        'project_id' => $project->id,
    ]);

    foreach ($groupIds as $groupId) {
        ProjectUserAssetGroup::firstOrCreate([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'asset_group_id' => $groupId,
        ]);
    }
}

it('shows the custom list badge and opens the asset list modal on click', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();

    $permission = Permission::findOrCreate('view_settings', 'web');
    $owner->givePermissionTo($permission);

    $project = makeProject();
    $project->users()->attach($owner->id);

    $group = AssetGroup::create(['project_id' => $project->id, 'name' => 'Scratch Group']);
    AssetGroupItem::create(['asset_group_id' => $group->id, 'channel' => 'facebook_marketing', 'asset_id' => 'act_111']);

    addMember($member, $project, 'project_user', false, [$group->id]);

    actingAs($owner);

    Filament::setTenant($project);

    $component = Livewire::test(ManageCollaborators::class, ['tenant' => $project->subdomain]);

    $component->assertSee('Custom list');

    $component->call('mountTableAction', 'view_asset_list', (string) $member->id);

    $component->assertSet('mountedTableActions', ['view_asset_list']);
    $component->assertSee('act_111');
    $component->assertSee('Scratch Group');
});

it('does not open the asset list modal for unrestricted users', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();

    $permission = Permission::findOrCreate('view_settings', 'web');
    $owner->givePermissionTo($permission);

    $project = makeProject();
    $project->users()->attach($owner->id);

    addMember($member, $project, 'project_user', true, []);

    actingAs($owner);

    Filament::setTenant($project);

    $component = Livewire::test(ManageCollaborators::class, ['tenant' => $project->subdomain]);

    $component->call('mountTableAction', 'view_asset_list', (string) $member->id);

    $component->assertSet('mountedTableActions', []);
});
