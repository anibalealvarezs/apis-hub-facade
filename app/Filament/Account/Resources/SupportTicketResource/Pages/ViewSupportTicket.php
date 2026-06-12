<?php

namespace App\Filament\Account\Resources\SupportTicketResource\Pages;

use App\Filament\Account\Resources\SupportTicketResource;
use Filament\Resources\Pages\ViewRecord;

class ViewSupportTicket extends ViewRecord
{
    protected static string $resource = SupportTicketResource::class;

    protected static string $view = 'filament.account.pages.view-support-ticket';

    public function getTitle(): string
    {
        return "Ticket #{$this->record->id}";
    }

    protected function getListeners(): array
    {
        return [
            'ticket-reply-added' => 'refreshRecord',
        ];
    }

    public function refreshRecord(): void
    {
        $this->record->refresh();
    }

    public function getMessages()
    {
        return $this->record->messages()->with('user')->orderBy('created_at')->get();
    }
}
