<?php

namespace App\Filament\App\Pages;

use Filament\Pages\Page;

class SubscriptionFeatures extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-star';
    protected static string $view = 'filament.app.pages.subscription-features';

    public static function getNavigationLabel(): string
    {
        return __('Features & Tiers');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Knowledge Base');
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
