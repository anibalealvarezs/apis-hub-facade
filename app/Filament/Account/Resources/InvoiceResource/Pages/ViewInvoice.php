<?php

namespace App\Filament\Account\Resources\InvoiceResource\Pages;

use App\Filament\Account\Resources\InvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewInvoice extends ViewRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('download')
                ->label('Download PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->url(fn (\App\Models\Invoice $record) => route('invoices.download', $record))
                ->openUrlInNewTab()
                ->visible(fn (\App\Models\Invoice $record) => $record->fiscal_status === 'reconciled'),
        ];
    }
}
