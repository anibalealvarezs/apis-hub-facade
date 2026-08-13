<?php

namespace App\Filament\App\Clusters\KnowledgeBase;

use Filament\Clusters\Cluster;

class Data extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-circle-stack';
    protected static ?int $navigationSort = 2;

    public static function getNavigationLabel(): string
    {
        return __('Data');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Knowledge Base');
    }
}
