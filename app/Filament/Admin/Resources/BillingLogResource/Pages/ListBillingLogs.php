<?php

namespace App\Filament\Admin\Resources\BillingLogResource\Pages;

use App\Filament\Admin\Resources\BillingLogResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBillingLogs extends ListRecords
{
    protected static string $resource = BillingLogResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
