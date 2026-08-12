<?php

namespace App\Filament\App\Pages;

use App\Filament\App\Clusters\KnowledgeBase\AccountProjectsBilling;
use Filament\Pages\Page;

class AccountStructureReference extends Page
{
    protected static ?string $cluster = AccountProjectsBilling::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-group';
    protected static ?int $navigationSort = 2;
    protected static string $view = 'filament.app.pages.account-structure-reference';

    public static function getNavigationLabel(): string
    {
        return __('Account & Projects Structure');
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
