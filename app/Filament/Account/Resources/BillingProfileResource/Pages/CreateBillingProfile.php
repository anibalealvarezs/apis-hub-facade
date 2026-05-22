<?php

namespace App\Filament\Account\Resources\BillingProfileResource\Pages;

use App\Filament\Account\Resources\BillingProfileResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateBillingProfile extends CreateRecord
{
    protected static string $resource = BillingProfileResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();
        return $data;
    }
}
