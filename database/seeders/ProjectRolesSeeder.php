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
            'view_project',
            'update_project_settings',
            'delete_project',
            'manage_project_users',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Project Viewer
        $viewerRole = Role::firstOrCreate(['name' => 'project_viewer', 'guard_name' => 'web']);
        $viewerRole->givePermissionTo('view_project');

        // Project Editor
        $editorRole = Role::firstOrCreate(['name' => 'project_editor', 'guard_name' => 'web']);
        $editorRole->givePermissionTo([
            'view_project',
            'update_project_settings',
        ]);

        // Project Owner
        $ownerRole = Role::firstOrCreate(['name' => 'project_owner', 'guard_name' => 'web']);
        $ownerRole->givePermissionTo([
            'view_project',
            'update_project_settings',
            'delete_project',
            'manage_project_users',
        ]);
    }
}
