<?php

namespace App\Filament\Account\Pages;

use Filament\Pages\Page;

class OnboardingSettings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static string $view = 'filament.account.pages.onboarding-settings';

    protected static ?int $navigationSort = 50;

    public static function getNavigationGroup(): ?string
    {
        return __('Preferences');
    }

    public function getTitle(): string
    {
        return __('Onboarding & Guided Tours');
    }

    public static function getNavigationLabel(): string
    {
        return __('Onboarding & Tours');
    }

    public function getViewData(): array
    {
        $user = auth()->user();
        $primaryProject = $user?->projects()->first();

        return [
            'tenantSubdomain' => $primaryProject?->subdomain,
        ];
    }
}
