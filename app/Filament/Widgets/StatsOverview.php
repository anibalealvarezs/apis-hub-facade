<?php

    namespace App\Filament\Widgets;

    use App\Models\Project;
    use App\Models\Server;
    use Filament\Widgets\StatsOverviewWidget as BaseWidget;
    use Filament\Widgets\StatsOverviewWidget\Stat;

    class StatsOverview extends BaseWidget
    {
        protected static ?int $sort = 1;

        protected int|string|array $columnSpan = 2;

        protected function getStats(): array
        {
            return [
                Stat::make('Total Users', \App\Models\User::count())
                    ->description(__('Registered accounts'))
                    ->descriptionIcon('heroicon-m-users')
                    ->color('primary'),

                Stat::make('Active Billing Profiles', \App\Models\BillingProfile::where('status', 'active')->count())
                    ->description(__('Paying and active subscriptions'))
                    ->descriptionIcon('heroicon-m-credit-card')
                    ->color('success'),

                Stat::make('Active Projects', Project::where('is_active', true)->count())
                    ->description(__('Total instances managed'))
                    ->descriptionIcon('heroicon-m-rectangle-stack')
                    ->color('success'),

                Stat::make('Ready Servers', Server::where('is_ready', true)->count())
                    ->description(__('Servers available for deployment'))
                    ->descriptionIcon('heroicon-m-server-stack')
                    ->color('info'),

                Stat::make('Critical Errors', Project::where('health_status', 'error')->count())
                    ->description(__('Instances requiring attention'))
                    ->descriptionIcon('heroicon-m-exclamation-triangle')
                    ->color('danger'),
            ];
        }
    }
