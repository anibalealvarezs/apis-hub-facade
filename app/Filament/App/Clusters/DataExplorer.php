<?php

namespace App\Filament\App\Clusters;

use Filament\Clusters\Cluster;

class DataExplorer extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-pie';
    protected static ?int $navigationSort = 3;

    public static function getNavigationLabel(): string
    {
        return __('Data Explorer');
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Exploration & Telemetry';
    }
}
