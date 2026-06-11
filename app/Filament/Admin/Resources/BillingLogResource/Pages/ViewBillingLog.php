<?php

namespace App\Filament\Admin\Resources\BillingLogResource\Pages;

use App\Filament\Admin\Resources\BillingLogResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewBillingLog extends ViewRecord
{
    protected static string $resource = BillingLogResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
