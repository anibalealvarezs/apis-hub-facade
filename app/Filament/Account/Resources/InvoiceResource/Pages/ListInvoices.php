<?php

namespace App\Filament\Account\Resources\InvoiceResource\Pages;

use App\Filament\Account\Resources\InvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInvoices extends ListRecords
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No create action for invoices
        ];
    }
}
