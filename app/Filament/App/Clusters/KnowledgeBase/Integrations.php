<?php

namespace App\Filament\App\Clusters\KnowledgeBase;

use Filament\Clusters\Cluster;

class Integrations extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-puzzle-piece';
    protected static ?int $navigationSort = 3;

    public static function getNavigationLabel(): string
    {
        return __('Integrations');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Knowledge Base');
    }
}
