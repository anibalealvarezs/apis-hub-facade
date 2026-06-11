<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Enums\UserTier;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class FunnelMetricsWidget extends BaseWidget
{
    protected static ?int $sort = 2; // Right below StatsOverview

    protected function getStats(): array
    {
        $totalUsers = User::count();
        $unverifiedEmails = User::whereNull('email_verified_at')->count();
        
        $verifiedNoProject = User::whereNotNull('email_verified_at')
            ->whereDoesntHave('projects')
            ->whereDoesntHave('ownedProjects')
            ->count();
            
        $noProjectAny = User::whereDoesntHave('projects')
            ->whereDoesntHave('ownedProjects')
            ->count();
            
        $onlyFreeTier = User::whereHas('billingProfiles', function ($query) {
                $query->where('tier', UserTier::FREE);
            })
            ->whereDoesntHave('billingProfiles', function ($query) {
                $query->where('tier', '!=', UserTier::FREE);
            })
            ->count();

        return [
            Stat::make('1. Total Accounts', $totalUsers)
                ->description('All registered users')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),

            Stat::make('2. Unverified Emails', $unverifiedEmails)
                ->description('Drop-off at signup')
                ->descriptionIcon('heroicon-m-envelope-open')
                ->color('warning'),

            Stat::make('3. Verified, No Project', $verifiedNoProject)
                ->description('Drop-off at onboarding')
                ->descriptionIcon('heroicon-m-rectangle-stack')
                ->color('danger'),

            Stat::make('4. No Access to Any Project', $noProjectAny)
                ->description('Total inactive accounts')
                ->descriptionIcon('heroicon-m-eye-slash')
                ->color('gray'),

            Stat::make('5. Only Free Tier', $onlyFreeTier)
                ->description('Accounts that haven\'t upgraded')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('info'),
        ];
    }
}
