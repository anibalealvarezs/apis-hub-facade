<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Models\Project;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class LastWeekPerformanceChart extends ChartWidget
{
    protected static ?string $heading = 'Performance & Momentum (Last 7 Days)';
    
    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 1;

    protected function getData(): array
    {
        $startDate = now()->subDays(7)->startOfDay();
        $endDate = now()->endOfDay();

        $users = User::whereBetween('created_at', [$startDate, $endDate])
            ->get()
            ->groupBy(function ($user) {
                return Carbon::parse($user->created_at)->format('Y-m-d');
            });
            
        $projects = Project::whereBetween('created_at', [$startDate, $endDate])
            ->get()
            ->groupBy(function ($project) {
                return Carbon::parse($project->created_at)->format('Y-m-d');
            });

        $labels = [];
        $userData = [];
        $projectData = [];

        // Fill in missing days
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $labels[] = Carbon::parse($date)->format('D, M d');
            $userData[] = isset($users[$date]) ? $users[$date]->count() : 0;
            $projectData[] = isset($projects[$date]) ? $projects[$date]->count() : 0;
        }

        return [
            'datasets' => [
                [
                    'label' => 'New Users',
                    'data' => $userData,
                    'backgroundColor' => '#3b82f6', // Blue
                ],
                [
                    'label' => 'New Projects',
                    'data' => $projectData,
                    'backgroundColor' => '#10b981', // Green
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
