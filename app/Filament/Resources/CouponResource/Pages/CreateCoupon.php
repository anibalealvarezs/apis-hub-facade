<?php

namespace App\Filament\Resources\CouponResource\Pages;

use App\Filament\Resources\CouponResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateCoupon extends CreateRecord
{
    protected static string $resource = CouponResource::class;
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['code'] = strtoupper($data['code']);
        return $data;
    }
}
