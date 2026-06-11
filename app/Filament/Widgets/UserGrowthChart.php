<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class UserGrowthChart extends ChartWidget
{
    protected static ?string $heading = 'User Registrations (Last 30 Days)';
    
    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $startDate = now()->subDays(30)->startOfDay();
        $endDate = now()->endOfDay();

        $users = User::whereBetween('created_at', [$startDate, $endDate])
            ->get()
            ->groupBy(function ($user) {
                return Carbon::parse($user->created_at)->format('Y-m-d');
            });

        $labels = [];
        $data = [];

        // Fill in missing days
        for ($i = 30; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $labels[] = Carbon::parse($date)->format('M d');
            $data[] = isset($users[$date]) ? $users[$date]->count() : 0;
        }

        return [
            'datasets' => [
                [
                    'label' => 'New Users',
                    'data' => $data,
                    'fill' => 'start',
                    'borderColor' => '#00a7f9',
                    'backgroundColor' => 'rgba(0, 167, 249, 0.1)',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
