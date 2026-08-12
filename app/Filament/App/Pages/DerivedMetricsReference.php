<?php

namespace App\Filament\App\Pages;

use App\Filament\App\Clusters\KnowledgeBase\Analytics;
use Filament\Pages\Page;

class DerivedMetricsReference extends Page
{
    protected static ?string $cluster = Analytics::class;
    protected static ?string $navigationIcon = 'heroicon-o-calculator';
    protected static ?int $navigationSort = 4;
    protected static string $view = 'filament.app.pages.derived-metrics-reference';

    public static function getNavigationLabel(): string
    {
        return __('Derived Metrics');
    }

    public function getTitle(): string
    {
        return __('Derived Metrics Reference');
    }

    public static function canAccess(): bool
    {
        return true;
    }
}
