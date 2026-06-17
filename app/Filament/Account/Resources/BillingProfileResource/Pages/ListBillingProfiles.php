<?php

namespace App\Filament\Account\Resources\BillingProfileResource\Pages;

use App\Filament\Account\Resources\BillingProfileResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBillingProfiles extends ListRecords
{
    protected static string $resource = BillingProfileResource::class;

    protected function getHeaderActions(): array
    {
        $hasFreeProfile = \App\Models\BillingProfile::where('user_id', auth()->id())
            ->where('tier', 'free')
            ->exists();

        $actions = [];

        if ($hasFreeProfile) {
            $actions[] = Actions\Action::make('free_tier_notice')
                ->label(__('Limit: 1 Free Profile'))
                ->icon('heroicon-o-information-circle')
                ->color('warning')
                ->modalHeading(__('Free Tier Limitation'))
                ->modalDescription(__('To prevent platform abuse, accounts are limited to a single Free Tier billing profile. You must either delete your existing free profile or upgrade it to a paid tier before you can create a new one.'))
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Understood');
        }

        // The CreateAction will automatically hide itself due to BillingProfileResource::canCreate() returning false
        $actions[] = Actions\CreateAction::make();

        return $actions;
    }
    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Account\Widgets\BillingRequestsWidget::class,
        ];
    }
}
