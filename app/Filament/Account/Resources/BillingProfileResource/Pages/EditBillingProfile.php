<?php

namespace App\Filament\Account\Resources\BillingProfileResource\Pages;

use App\Filament\Account\Resources\BillingProfileResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBillingProfile extends EditRecord
{
    protected static string $resource = BillingProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
