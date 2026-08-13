<?php

namespace App\Filament\App\Clusters\KnowledgeBase;

use Filament\Clusters\Cluster;

class Analytics extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return __('Analytics');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Knowledge Base');
    }
}
