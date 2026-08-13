<?php

namespace App\Filament\App\Clusters\KnowledgeBase;

use Filament\Clusters\Cluster;

class Administration extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?int $navigationSort = 4;

    public static function getNavigationLabel(): string
    {
        return __('Administration');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Knowledge Base');
    }
}
