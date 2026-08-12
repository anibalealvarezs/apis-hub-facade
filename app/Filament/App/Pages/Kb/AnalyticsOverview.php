<?php

namespace App\Filament\App\Pages\Kb;

use App\Filament\App\Clusters\KnowledgeBase\Analytics as AnalyticsCluster;
use App\Filament\App\Pages\DashboardsReference;
use App\Filament\App\Pages\DerivedMetricsReference;
use App\Filament\App\Pages\KpiReference;
use Filament\Pages\Page;

class AnalyticsOverview extends Page
{
    protected static ?string $cluster = AnalyticsCluster::class;
    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static ?int $navigationSort = 1;
    protected static string $view = 'filament.app.pages.kb.analytics-overview';
    protected static ?string $slug = 'overview';

    public static function getNavigationLabel(): string
    {
        return __('Overview');
    }

    public function getTitle(): string
    {
        return __('Analytics');
    }

    protected function getViewData(): array
    {
        return [
            'intro' => __('Reference material for analytics, KPIs, dashboards, and derived metrics.'),
            'links' => [
                [
                    'url' => KpiReference::getUrl(),
                    'icon' => 'heroicon-o-academic-cap',
                    'title' => __('KPI Reference'),
                    'description' => __('Browse every predefined KPI template, its formula, and guidance on how to interpret it.'),
                ],
                [
                    'url' => DashboardsReference::getUrl(),
                    'icon' => 'heroicon-o-squares-2x2',
                    'title' => __('Dashboards'),
                    'description' => __('Learn how to build dashboards, add widgets, and share them with your team.'),
                ],
                [
                    'url' => DerivedMetricsReference::getUrl(),
                    'icon' => 'heroicon-o-calculator',
                    'title' => __('Derived Metrics'),
                    'description' => __('Learn how to create reusable, computed metrics on top of your synced data.'),
                ],
            ],
        ];
    }
}
