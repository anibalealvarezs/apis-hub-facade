<?php

namespace App\Filament\App\Pages;

use Filament\Pages\Page;

class AccountStructureReference extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-group';
    protected static string $view = 'filament.app.pages.account-structure-reference';

    public static function getNavigationLabel(): string
    {
        return __('Account & Projects Structure');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Knowledge Base');
    }

    public function getTitle(): string
    {
        return __('Account & Projects Structure Reference');
    }

    public static function canAccess(): bool
    {
        return true;
    }
}
