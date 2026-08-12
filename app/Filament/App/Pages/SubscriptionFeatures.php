<?php

namespace App\Filament\App\Pages;

use App\Filament\App\Clusters\KnowledgeBase\AccountProjectsBilling;
use Filament\Pages\Page;

class SubscriptionFeatures extends Page
{
    protected static ?string $cluster = AccountProjectsBilling::class;
    protected static ?string $navigationIcon = 'heroicon-o-star';
    protected static ?int $navigationSort = 4;
    protected static string $view = 'filament.app.pages.subscription-features';

    public static function getNavigationLabel(): string
    {
        return __('Features & Tiers');
    }

    public function getTitle(): string
    {
        return __('Features by Plan (Tiers)');
    }

    public static function canAccess(): bool
    {
        // Todos los colaboradores del proyecto pueden ver qué funcionalidades existen por plan.
        return true;
    }
}
