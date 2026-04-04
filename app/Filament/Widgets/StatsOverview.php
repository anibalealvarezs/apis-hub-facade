<?php

namespace App\Filament\Widgets;

use App\Models\Project;
use App\Models\Server;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Active Projects', Project::where('is_active', true)->count())
                ->description('Total instances managed')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('success'),
            
            Stat::make('Ready Servers', Server::where('is_ready', true)->count())
                ->description('Servers available for deployment')
                ->descriptionIcon('heroicon-m-server-stack')
                ->color('info'),

            Stat::make('Critical Errors', Project::where('health_status', 'error')->count())
                ->description('Instances requiring attention')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger'),
        ];
    }
}
