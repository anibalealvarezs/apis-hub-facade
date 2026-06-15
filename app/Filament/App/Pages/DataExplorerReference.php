<?php

namespace App\Filament\App\Pages;

use Filament\Pages\Page;

class DataExplorerReference extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';
    protected static string $view = 'filament.app.pages.data-explorer-reference';

    public static function getNavigationLabel(): string
    {
        return __('Data Explorers');
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Knowledge Base';
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
