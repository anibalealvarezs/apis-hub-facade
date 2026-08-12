<?php

namespace App\Filament\App\Pages;

use App\Filament\App\Clusters\KnowledgeBase\AccountProjectsBilling;
use Filament\Pages\Page;

class AssetBillingReference extends Page
{
    protected static ?string $cluster = AccountProjectsBilling::class;
    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static ?int $navigationSort = 3;
    protected static string $view = 'filament.app.pages.asset-billing-reference';

    public static function getNavigationLabel(): string
    {
        return __('Billing & Quotas');
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
