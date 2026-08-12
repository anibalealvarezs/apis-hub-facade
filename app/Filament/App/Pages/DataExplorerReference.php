<?php

namespace App\Filament\App\Pages;

use App\Filament\App\Clusters\KnowledgeBase\Data;
use Filament\Pages\Page;

class DataExplorerReference extends Page
{
    protected static ?string $cluster = Data::class;
    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';
    protected static ?int $navigationSort = 2;
    protected static string $view = 'filament.app.pages.data-explorer-reference';

    public static function getNavigationLabel(): string
    {
        return __('Data Explorers');
    }

    public function getTitle(): string
    {
        return __('Data Explorers Reference');
    }

    public static function canAccess(): bool
    {
        return true;
    }
}
