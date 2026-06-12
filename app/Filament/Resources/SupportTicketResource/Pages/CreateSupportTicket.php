<?php

namespace App\Filament\Resources\SupportTicketResource\Pages;

use App\Filament\Resources\SupportTicketResource;
use App\Models\TicketMessage;
use Filament\Resources\Pages\CreateRecord;

class CreateSupportTicket extends CreateRecord
{
    protected static string $resource = SupportTicketResource::class;

    protected function afterCreate(): void
    {
        TicketMessage::create([
            'support_ticket_id' => $this->record->id,
            'user_id' => $this->record->user_id,
            'message' => $this->record->description,
        ]);
    }
}
