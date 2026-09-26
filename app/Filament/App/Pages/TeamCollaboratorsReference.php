<?php

declare(strict_types=1);

namespace App\Filament\App\Pages;

use App\Filament\App\Clusters\KnowledgeBase\Administration;
use Filament\Pages\Page;

class TeamCollaboratorsReference extends Page
{
    protected static ?string $cluster = Administration::class;
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?int $navigationSort = 3;
    protected static string $view = 'filament.app.pages.team-collaborators-reference';

    public static function getNavigationLabel(): string
    {
        return __('Team & Collaborators');
    }

    public function getTitle(): string
    {
        return __('Team, Collaborators & Asset Access Reference');
    }

    public static function canAccess(): bool
    {
        return true;
    }
}
