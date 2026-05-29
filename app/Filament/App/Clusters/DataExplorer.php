<?php

namespace App\Filament\App\Clusters;

use Filament\Clusters\Cluster;

class DataExplorer extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-pie';
    protected static ?string $navigationGroup = 'Exploration & Telemetry';
    protected static ?string $navigationLabel = 'Data Explorer';
    protected static ?int $navigationSort = 3;
}
