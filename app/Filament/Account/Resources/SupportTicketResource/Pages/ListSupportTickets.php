<?php

namespace App\Filament\Account\Resources\SupportTicketResource\Pages;

use App\Filament\Account\Resources\SupportTicketResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSupportTickets extends ListRecords
{
    protected static string $resource = SupportTicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('New Ticket'),
        ];
    }
}
