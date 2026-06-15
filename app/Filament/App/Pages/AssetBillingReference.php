<?php

namespace App\Filament\App\Pages;

use Filament\Pages\Page;

class AssetBillingReference extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static string $view = 'filament.app.pages.asset-billing-reference';

    public static function getNavigationLabel(): string
    {
        return __('Billing & Quotas');
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Knowledge Base';
    }

    public function getTitle(): string
    {
        return __('Asset Billing & Quotas Reference');
    }

    public static function canAccess(): bool
    {
        return true;
    }
}
