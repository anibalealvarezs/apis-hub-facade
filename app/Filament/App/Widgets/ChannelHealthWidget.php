<?php

namespace App\Filament\App\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Facades\Filament;

class ChannelHealthWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $tenant = Filament::getTenant();
        $metrics = $tenant->health_metrics ?? [];
        $channels = $metrics['channels'] ?? ['facebook' => false, 'google' => false];

        return [
            Stat::make('Facebook Connectivity', $channels['facebook'] ? 'ONLINE' : 'OFFLINE')
                ->description($channels['facebook'] ? 'Graph API is authorized' : 'Re-authorization required')
                ->descriptionIcon($channels['facebook'] ? 'heroicon-m-check-circle' : 'heroicon-m-x-circle')
                ->chart([1, 1, 1, 1, 1, 1, $channels['facebook'] ? 1 : 0])
                ->color($channels['facebook'] ? 'success' : 'danger'),

            Stat::make('Google Connectivity', $channels['google'] ? 'ONLINE' : 'OFFLINE')
                ->description($channels['google'] ? 'Search Console is authorized' : 'Re-authorization required')
                ->descriptionIcon($channels['google'] ? 'heroicon-m-check-circle' : 'heroicon-m-x-circle')
                ->chart([1, 1, 1, 1, 1, 1, $channels['google'] ? 1 : 0])
                ->color($channels['google'] ? 'success' : 'danger'),
        ];
    }
}
