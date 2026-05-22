<?php

namespace App\Filament\App\Resources\BillingProfileResource\Pages;

use App\Filament\App\Resources\BillingProfileResource;
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
