<?php

namespace App\Notifications;

use App\Models\SupportTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Filament\Notifications\Notification as FilamentNotification;

class TicketCreatedForUserNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public SupportTicket $ticket,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Support Ticket #{$this->ticket->id} Created For You - APIs Hub")
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line("A support ticket has been created on your behalf.")
            ->line("Type: " . ucfirst(str_replace('_', ' ', $this->ticket->type)))
            ->line("Description: " . str($this->ticket->description)->limit(200))
            ->action('View Ticket', url("/account/support-tickets/{$this->ticket->id}"))
            ->line('Thank you for using APIs Hub.');
    }

    public function toDatabase(object $notifiable): array
    {
        $title = "Ticket #{$this->ticket->id} Created For You";
        $body = "A " . str_replace('_', ' ', $this->ticket->type) . " ticket has been opened on your behalf.";

        return FilamentNotification::make()
            ->title($title)
            ->body($body)
            ->info()
            ->actions([
                \Filament\Notifications\Actions\Action::make('view')
                    ->button()
                    ->url(url("/account/support-tickets/{$this->ticket->id}"))
                    ->label('View Ticket'),
            ])
            ->getDatabaseMessage();
    }
}
