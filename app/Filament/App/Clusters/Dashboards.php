<?php

namespace App\Filament\App\Clusters;

use Filament\Clusters\Cluster;

class Dashboards extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';
    protected static ?int $navigationSort = 2;

    public static function getNavigationLabel(): string
    {
        return __('Dashboards');
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Exploration & Telemetry';
    }
}
