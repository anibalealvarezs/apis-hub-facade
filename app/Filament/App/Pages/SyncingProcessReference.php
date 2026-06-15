<?php

namespace App\Filament\App\Pages;

use Filament\Pages\Page;

class SyncingProcessReference extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-arrow-path-rounded-square';
    protected static string $view = 'filament.app.pages.syncing-process-reference';

    public static function getNavigationLabel(): string
    {
        return __('Syncing Engine & Telemetry');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Knowledge Base');
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
