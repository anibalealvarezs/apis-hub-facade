<?php

    namespace App\Filament\Widgets;

    use App\Models\Project;
    use Filament\Widgets\ChartWidget;

    class ProjectsPerTierChart extends ChartWidget
    {
        protected static ?string $heading = 'Projects by Billing Tier';

        protected static ?int $sort = 4;

        protected int|string|array $columnSpan = '1/4';

        protected static ?string $maxHeight = '200px';

        protected function getData(): array
        {
            $projects = Project::with('billingProfile')
                ->where('is_active', true)
                ->get();

            $tierCounts = [
                'free'       => 0,
                'pro'        => 0,
                'ultra'      => 0,
                'founder'    => 0,
                'enterprise' => 0,
                'suspended'  => 0,
                'none'       => 0,
            ];

            foreach ($projects as $project) {
                if ($project->billingProfile) {
                    $tierName = $project->billingProfile->tier instanceof \App\Enums\UserTier
                        ? $project->billingProfile->tier->value
                        : $project->billingProfile->tier;

                    if (isset($tierCounts[$tierName])) {
                        $tierCounts[$tierName]++;
                    } else {
                        $tierCounts[$tierName] = 1;
                    }
                } else {
                    $tierCounts['none']++;
                }
            }

            $labels = [];
            $data = [];
            $colors = [];

            $tierColors = [
                'free'       => '#9ca3af', // Gray
                'pro'        => '#3b82f6', // Blue
                'ultra'      => '#10b981', // Green
                'founder'    => '#eab308', // Yellow
                'enterprise' => '#ef4444', // Red
                'suspended'  => '#4b5563', // Dark Gray
                'none'       => '#ef4444', // Red (Warning state)
            ];

            foreach ($tierCounts as $tier => $count) {
                if ($count > 0) {
                    $labels[] = ucfirst($tier);
                    $data[] = $count;
                    $colors[] = $tierColors[$tier] ?? '#6366f1';
                }
            }

            return [
                'datasets' => [
                    [
                        'label'           => 'Projects',
                        'data'            => $data,
                        'backgroundColor' => $colors,
                        'borderColor'     => '#1f2937',
                        'borderWidth'     => 1,
                    ],
                ],
                'labels'   => $labels,
            ];
        }

        protected function getType(): string
        {
            return 'doughnut';
        }
    }
