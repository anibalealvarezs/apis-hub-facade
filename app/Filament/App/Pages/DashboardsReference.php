<?php

namespace App\Filament\App\Pages;

use App\Filament\App\Clusters\KnowledgeBase\Analytics;
use Filament\Pages\Page;

class DashboardsReference extends Page
{
    protected static ?string $cluster = Analytics::class;
    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';
    protected static ?int $navigationSort = 3;
    protected static string $view = 'filament.app.pages.dashboards-reference';

    public static function getNavigationLabel(): string
    {
        return __('Dashboards');
    }

    public function getTitle(): string
    {
        return __('Dashboards Reference');
    }

    public static function canAccess(): bool
    {
        return true;
    }
}
