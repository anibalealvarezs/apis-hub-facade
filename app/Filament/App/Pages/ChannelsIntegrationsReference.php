<?php

namespace App\Filament\App\Pages;

use App\Filament\App\Clusters\KnowledgeBase\Integrations;
use Filament\Pages\Page;

class ChannelsIntegrationsReference extends Page
{
    protected static ?string $cluster = Integrations::class;
    protected static ?string $navigationIcon = 'heroicon-o-puzzle-piece';
    protected static ?int $navigationSort = 2;
    protected static string $view = 'filament.app.pages.channels-integrations-reference';

    public static function getNavigationLabel(): string
    {
        return __('Channels & Integrations');
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
