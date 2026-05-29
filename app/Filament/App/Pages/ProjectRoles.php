<?php

namespace App\Filament\App\Pages;

use Filament\Pages\Page;
use Spatie\Permission\Models\Role;

class ProjectRoles extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.app.pages.project-roles';

    protected static ?string $navigationGroup = 'Knowledge Base';

    public static function getNavigationLabel(): string
    {
        return 'Roles & Permissions';
    }

    public function getTitle(): string
    {
        return 'Roles & Permissions';
    }

    public static function canAccess(): bool
    {
        // Todos los colaboradores del proyecto pueden ver qué roles existen.
        return true;
    }

    protected function getViewData(): array
    {
        // Obtenemos los roles del proyecto excluyendo roles del sistema global (como super_admin)
        $roles = Role::whereIn('name', ['project_owner', 'project_editor', 'project_viewer', 'project_user'])
            ->with('permissions')
            ->get();

        return [
            'roles' => $roles,
        ];
    }
}
