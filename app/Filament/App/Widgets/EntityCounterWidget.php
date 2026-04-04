<?php

namespace App\Filament\App\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Facades\Filament;

class EntityCounterWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $tenant = Filament::getTenant();
        $metrics = $tenant->health_metrics ?? [];
        $catalog = $metrics['catalog'] ?? [
            'pages' => 0,
            'campaigns' => 0,
            'posts' => 0,
            'queries' => 0,
        ];

        return [
            Stat::make('Ads Campaigns', number_format($catalog['campaigns']))
                ->description('Total campaigns cached')
                ->descriptionIcon('heroicon-m-megaphone')
                ->chart([7, 3, 4, 5, 6, 3, 5, 2, 3, 9])
                ->color('info'),

            Stat::make('Social Posts', number_format($catalog['posts']))
                ->description('Facebook/Instagram posts')
                ->descriptionIcon('heroicon-m-chat-bubble-left-right')
                ->chart([15, 4, 10, 2, 12, 4, 12, 10, 18, 15])
                ->color('success'),

            Stat::make('Search Queries', number_format($catalog['queries']))
                ->description('Google Search Console queries')
                ->descriptionIcon('heroicon-m-magnifying-glass')
                ->chart([2, 10, 5, 12, 8, 15, 12, 18, 14, 20])
                ->color('warning'),

            Stat::make('Managed Pages', number_format($catalog['pages']))
                ->description('Total linked sources')
                ->descriptionIcon('heroicon-m-globe-alt')
                ->chart([3, 5, 4, 4, 6, 5, 7, 6, 8, 10])
                ->color('primary'),
        ];
    }
}
