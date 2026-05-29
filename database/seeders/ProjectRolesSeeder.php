<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class ProjectRolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Define base permissions
        $permissions = [
            'delete_project',
            'transfer_project',
            'deploy_project',
            'manage_channels',
            'edit_preferences',
            'manage_billing',
            'manage_collaborators',
            'view_data',
            'view_settings',
            'manage_public_views',
            'view_public_views',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Project User
        $userRole = Role::firstOrCreate(['name' => 'project_user', 'guard_name' => 'web']);
        $userRole->syncPermissions([
            'view_public_views',
        ]);

        // Project Viewer
        $viewerRole = Role::firstOrCreate(['name' => 'project_viewer', 'guard_name' => 'web']);
        $viewerRole->syncPermissions([
            'view_data',
            'view_settings',
            'view_public_views',
        ]);

        // Project Editor
        $editorRole = Role::firstOrCreate(['name' => 'project_editor', 'guard_name' => 'web']);
        $editorRole->syncPermissions([
            'deploy_project',
            'manage_channels',
            'edit_preferences',
            'manage_billing',
            'manage_collaborators',
            'view_data',
            'view_settings',
            'manage_public_views',
            'view_public_views',
        ]);

        // Project Owner
        $ownerRole = Role::firstOrCreate(['name' => 'project_owner', 'guard_name' => 'web']);
        $ownerRole->syncPermissions([
            'delete_project',
            'transfer_project',
            'deploy_project',
            'manage_channels',
            'edit_preferences',
            'manage_billing',
            'manage_collaborators',
            'view_data',
            'view_settings',
            'manage_public_views',
            'view_public_views',
        ]);
    }
}
