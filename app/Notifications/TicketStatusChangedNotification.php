<?php

namespace App\Notifications;

use App\Models\SupportTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Filament\Notifications\Notification as FilamentNotification;

class TicketStatusChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public SupportTicket $ticket,
        public string $oldStatus,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject("Ticket #{$this->ticket->id} Status Updated - APIs Hub")
            ->greeting('Hello ' . $notifiable->name . ',');

        if ($this->ticket->status === 'waiting_on_user') {
            $mail->line("The status of ticket #{$this->ticket->id} has been changed to \"Waiting on User\".")
                 ->line("An administrator is awaiting your response. Please check your ticket and reply at your earliest convenience.");
        } elseif ($this->ticket->status === 'closed') {
            $mail->line("The status of ticket #{$this->ticket->id} has been changed to \"Closed\".")
                 ->line("If you have further questions, please feel free to open a new ticket.");
        } else {
            $mail->line("The status of ticket #{$this->ticket->id} has been updated from \"" . str_replace('_', ' ', $this->oldStatus) . "\" to \"" . str_replace('_', ' ', $this->ticket->status) . "\".");
        }

        $mail->action('View Ticket', url("/account/support-tickets/{$this->ticket->id}"))
             ->line('Thank you for using APIs Hub.');

        return $mail;
    }

    public function toDatabase(object $notifiable): array
    {
        $title = "Ticket #{$this->ticket->id} Status Updated";
        $body = "Status changed from \"" . str_replace('_', ' ', $this->oldStatus) . "\" to \"" . str_replace('_', ' ', $this->ticket->status) . "\".";

        $notification = FilamentNotification::make()
            ->title($title)
            ->body($body);

        if ($this->ticket->status === 'waiting_on_user') {
            $notification->warning();
        } elseif ($this->ticket->status === 'closed') {
            $notification->success();
        } else {
            $notification->info();
        }

        $notification->actions([
            \Filament\Notifications\Actions\Action::make('view')
                ->button()
                ->url(url("/account/support-tickets/{$this->ticket->id}"))
                ->label('View Ticket'),
        ]);

        return $notification->getDatabaseMessage();
    }
}
