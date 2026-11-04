<?php

namespace App\Filament\App\Clusters;

use App\Filament\App\Pages\JointDashboard;
use Filament\Clusters\Cluster;

class DataExplorer extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-pie';
    protected static ?int $navigationSort = 3;

    public static function getNavigationLabel(): string
    {
        return __('Data Preview');
    }

    public static function getClusteredComponents(): array
    {
        return array_values(array_diff(parent::getClusteredComponents(), [JointDashboard::class]));
    }

    public static function getNavigationItems(): array
    {
        [$item] = parent::getNavigationItems();

        return [$item->isActiveWhen(fn (): bool => request()->routeIs(static::getNavigationItemActiveRoutePattern())
            && ! request()->routeIs(JointDashboard::getRouteName()))];
    }

    public static function getClusterBreadcrumb(): ?string
    {
        return static::getNavigationLabel();
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Data Sources');
    }
}
