<?php

namespace App\Filament\App\Clusters\KnowledgeBase;

use Filament\Clusters\Cluster;

class AccountProjectsBilling extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static ?int $navigationSort = 5;

    public static function getNavigationLabel(): string
    {
        return __('Account, Projects & Billing');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Knowledge Base');
    }
}
