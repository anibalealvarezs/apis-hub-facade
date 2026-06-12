<?php

namespace App\Filament\Account\Resources\SupportTicketResource\Pages;

use App\Filament\Account\Resources\SupportTicketResource;
use App\Models\TicketMessage;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewSupportTicket extends ViewRecord
{
    protected static string $resource = SupportTicketResource::class;

    protected static string $view = 'filament.account.pages.view-support-ticket';

    public $newMessage = '';

    public function getTitle(): string
    {
        return "Ticket #{$this->record->id}";
    }

    public function reply()
    {
        if (!$this->record->isReplyAllowed(auth()->user())) {
            Notification::make()
                ->title('You do not have permission to reply to this ticket.')
                ->danger()
                ->send();
            return;
        }

        if ($this->record->status === 'closed') {
            Notification::make()
                ->title('This ticket is closed.')
                ->warning()
                ->send();
            return;
        }

        $this->validate([
            'newMessage' => 'required|string|max:5000',
        ]);

        TicketMessage::create([
            'support_ticket_id' => $this->record->id,
            'user_id' => auth()->id(),
            'message' => $this->newMessage,
        ]);

        $this->newMessage = '';
        $this->record->refresh();
    }

    public function getMessages()
    {
        return $this->record->messages()->with('user')->orderBy('created_at')->get();
    }
}
