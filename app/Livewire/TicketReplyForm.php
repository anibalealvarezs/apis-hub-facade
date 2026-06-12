<?php

namespace App\Livewire;

use App\Models\SupportTicket;
use App\Models\TicketMessage;
use App\Models\User;
use App\Notifications\TicketStatusChangedNotification;
use App\Notifications\TicketUserReplyNotification;
use Filament\Notifications\Notification;
use Livewire\Component;

class TicketReplyForm extends Component
{
    public SupportTicket $ticket;

    public string $newMessage = '';

    public bool $changeStatusToWaitingOnUser = false;

    public bool $showStatusPrompt = false;

    public function mount(SupportTicket $ticket, bool $showStatusPrompt = false): void
    {
        $this->ticket = $ticket;
        $this->showStatusPrompt = $showStatusPrompt;
    }

    public function reply(): void
    {
        $this->validate([
            'newMessage' => 'required|string|max:5000',
        ]);

        if ($this->ticket->status === 'closed') {
            Notification::make()
                ->title('This ticket is closed.')
                ->warning()
                ->send();
            return;
        }

        $message = TicketMessage::create([
            'support_ticket_id' => $this->ticket->id,
            'user_id' => auth()->id(),
            'message' => $this->newMessage,
        ]);

        $isAdmin = auth()->user()->isAdmin();
        $shouldChangeStatus = $isAdmin && $this->changeStatusToWaitingOnUser;

        if ($isAdmin) {
            $this->ticket->load('user');

            if ($shouldChangeStatus) {
                $oldStatus = $this->ticket->status;
                $this->ticket->update([
                    'status' => 'waiting_on_user',
                ]);

                if ($this->ticket->user) {
                    $this->ticket->user->notify(new TicketStatusChangedNotification($this->ticket, $oldStatus));
                }
            }

            if ($this->ticket->user) {
                $this->ticket->user->notify(new TicketUserReplyNotification($message));
            }
        } else {
            $admins = User::role('super_admin')->get();
            foreach ($admins as $admin) {
                $admin->notify(new TicketUserReplyNotification($message));
            }
        }

        $this->newMessage = '';
        $this->changeStatusToWaitingOnUser = false;

        $this->dispatch('ticket-reply-added', ticketId: $this->ticket->id);

        if ($shouldChangeStatus) {
            Notification::make()
                ->title('Reply sent and status changed to Waiting on User')
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Reply sent')
                ->success()
                ->send();
        }
    }

    public function render()
    {
        return view('livewire.ticket-reply-form');
    }
}
