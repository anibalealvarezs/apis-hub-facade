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
        return [
            Actions\CreateAction::make(),
        ];
    }
}
