<?php

declare(strict_types=1);

namespace App\Filament\App\Pages;

use App\Filament\App\Clusters\KnowledgeBase\Integrations;
use Filament\Pages\Page;

class ApiAccessReference extends Page
{
    protected static ?string $cluster = Integrations::class;
    protected static ?string $navigationIcon = 'heroicon-o-code-bracket';
    protected static ?int $navigationSort = 4;
    protected static string $view = 'filament.app.pages.api-access-reference';

    public static function getNavigationLabel(): string
    {
        return __('API Access & Integration');
    }

    public function getTitle(): string
    {
        return __('API Access & Integration Guide');
    }

    public static function canAccess(): bool
    {
        return true;
    }
}
