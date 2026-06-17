<?php

namespace App\Filament\Account\Resources\SupportTicketResource\Pages;

use App\Filament\Account\Resources\SupportTicketResource;
use App\Models\TicketMessage;
use App\Models\User;
use App\Notifications\TicketCreatedNotification;
use Filament\Resources\Pages\CreateRecord;

class CreateSupportTicket extends CreateRecord
{
    protected static string $resource = SupportTicketResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();
        $data['status'] = 'started';
        unset($data['association_type']);
        return $data;
    }

    protected function afterCreate(): void
    {
        TicketMessage::create([
            'support_ticket_id' => $this->record->id,
            'user_id' => auth()->id(),
            'message' => $this->record->description,
        ]);

        $admins = User::role('super_admin')->get();
        foreach ($admins as $admin) {
            $admin->notify(new TicketCreatedNotification($this->record));
        }
    }
}
