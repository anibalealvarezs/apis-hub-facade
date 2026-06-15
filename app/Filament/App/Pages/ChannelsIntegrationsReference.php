<?php

namespace App\Filament\App\Pages;

use Filament\Pages\Page;

class ChannelsIntegrationsReference extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-puzzle-piece';
    protected static string $view = 'filament.app.pages.channels-integrations-reference';

    public static function getNavigationLabel(): string
    {
        return __('Channels & Integrations');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Knowledge Base');
    }

    public function getTitle(): string
    {
        return __('Channels & Integrations Status');
    }

    public static function canAccess(): bool
    {
        return true;
    }
}
