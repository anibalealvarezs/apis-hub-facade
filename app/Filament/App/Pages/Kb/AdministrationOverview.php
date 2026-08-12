<?php

namespace App\Filament\App\Pages\Kb;

use App\Filament\App\Clusters\KnowledgeBase\Administration as AdministrationCluster;
use App\Filament\App\Pages\ProjectRoles;
use Filament\Pages\Page;

class AdministrationOverview extends Page
{
    protected static ?string $cluster = AdministrationCluster::class;
    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static ?int $navigationSort = 1;
    protected static string $view = 'filament.app.pages.kb.administration-overview';
    protected static ?string $slug = 'overview';

    public static function getNavigationLabel(): string
    {
        return __('Overview');
    }

    public function getTitle(): string
    {
        return __('Administration');
    }

    protected function getViewData(): array
    {
        return [
            'intro' => __('Reference material for team management, roles, and permissions.'),
            'links' => [
                [
                    'url' => ProjectRoles::getUrl(),
                    'icon' => 'heroicon-o-document-text',
                    'title' => __('Roles & Permissions'),
                    'description' => __('Learn which roles exist and what each one can do.'),
                ],
            ],
        ];
    }
}
