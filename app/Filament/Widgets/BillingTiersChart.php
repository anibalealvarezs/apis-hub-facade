<?php

namespace App\Filament\Widgets;

use App\Models\BillingProfile;
use Filament\Widgets\ChartWidget;

class BillingTiersChart extends ChartWidget
{
    protected static ?string $heading = 'Billing Profiles by Tier';
    
    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 1;

    protected static ?string $maxHeight = '150px';

    protected function getData(): array
    {
        $profiles = BillingProfile::selectRaw('tier, count(*) as count')
            ->where('status', 'active')
            ->groupBy('tier')
            ->get();

        $labels = [];
        $data = [];
        $colors = [];

        // Predefined colors for tiers to keep it visually consistent
        $tierColors = [
            'free' => '#9ca3af', // Gray
            'pro' => '#3b82f6', // Blue
            'ultra' => '#10b981', // Green
            'founder' => '#eab308', // Yellow
            'enterprise' => '#ef4444', // Red
            'suspended' => '#4b5563', // Dark Gray
        ];

        foreach ($profiles as $profile) {
            $tierName = $profile->tier instanceof \App\Enums\UserTier ? $profile->tier->value : $profile->tier;
            
            $labels[] = ucfirst($tierName);
            $data[] = $profile->count;
            $colors[] = $tierColors[$tierName] ?? '#6366f1';
        }

        return [
            'datasets' => [
                [
                    'label' => 'Profiles',
                    'data' => $data,
                    'backgroundColor' => $colors,
                    'borderColor' => '#1f2937', // Dark border to match admin panel
                    'borderWidth' => 1,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
