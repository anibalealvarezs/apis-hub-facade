<?php

namespace App\Filament\App\Pages;

use App\Filament\App\Clusters\KnowledgeBase\Integrations;
use Filament\Pages\Page;

class SyncingProcessReference extends Page
{
    protected static ?string $cluster = Integrations::class;
    protected static ?string $navigationIcon = 'heroicon-o-arrow-path-rounded-square';
    protected static ?int $navigationSort = 3;
    protected static string $view = 'filament.app.pages.syncing-process-reference';

    public static function getNavigationLabel(): string
    {
        return __('Syncing Engine & Telemetry');
    }

    public function getTitle(): string
    {
        return __('Syncing Engine & Telemetry Reference');
    }

    public static function canAccess(): bool
    {
        return true;
    }
}
