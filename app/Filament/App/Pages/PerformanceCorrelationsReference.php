<?php

namespace App\Filament\App\Pages;

use Filament\Pages\Page;

class PerformanceCorrelationsReference extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $cluster = \App\Filament\App\Clusters\KnowledgeBase::class;
    protected static ?string $navigationGroup = 'Data Analysis';
    protected static ?int $navigationSort = 4;

    public static function getNavigationLabel(): string
    {
        return __('Performance Correlations Guide');
    }

    public function getTitle(): string
    {
        return __('Performance Correlations: A Marketer\'s Guide');
    }

    protected static string $view = 'filament.app.pages.performance-correlations-reference';
}
