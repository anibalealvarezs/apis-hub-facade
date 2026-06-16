<?php

namespace App\Filament\App\Pages;

use Filament\Pages\Page;

class PerformanceCorrelationsReference extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?int $navigationSort = 4;

    public static function getNavigationLabel(): string
    {
        return __('Performance Correlations Guide');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Knowledge Base');
    }
    {
        return __('Performance Correlations Guide');
    }

    public function getTitle(): string
    {
        return __('Performance Correlations: A Marketer\'s Guide');
    }

    protected static string $view = 'filament.app.pages.performance-correlations-reference';
}
