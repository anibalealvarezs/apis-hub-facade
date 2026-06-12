<?php

namespace App\Filament\Account\Resources\SupportTicketResource\Pages;

use App\Filament\Account\Resources\SupportTicketResource;
use App\Models\TicketMessage;
use Filament\Resources\Pages\CreateRecord;

class CreateSupportTicket extends CreateRecord
{
    protected static string $resource = SupportTicketResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();
        $data['status'] = 'started';
        return $data;
    }

    protected function afterCreate(): void
    {
        TicketMessage::create([
            'support_ticket_id' => $this->record->id,
            'user_id' => auth()->id(),
            'message' => $this->record->description,
        ]);
    }
}
