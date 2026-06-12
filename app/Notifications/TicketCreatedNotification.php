<?php

namespace App\Notifications;

use App\Models\SupportTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Filament\Notifications\Notification as FilamentNotification;

class TicketCreatedNotification extends Notification implements ShouldQueue
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
            ->subject("New Support Ticket #{$this->ticket->id} - APIs Hub")
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line("A new support ticket has been created by {$this->ticket->user?->name} ({$this->ticket->user?->email}).")
            ->line("Type: " . ucfirst(str_replace('_', ' ', $this->ticket->type)))
            ->line("Description: " . str($this->ticket->description)->limit(200))
            ->action('View Ticket', url("/admin/support-tickets/{$this->ticket->id}"))
            ->line('Thank you for using APIs Hub.');
    }

    public function toDatabase(object $notifiable): array
    {
        $title = "New Ticket #{$this->ticket->id}";
        $body = "{$this->ticket->user?->name} created a " . str_replace('_', ' ', $this->ticket->type) . " ticket.";

        return FilamentNotification::make()
            ->title($title)
            ->body($body)
            ->info()
            ->actions([
                \Filament\Notifications\Actions\Action::make('view')
                    ->button()
                    ->url(url("/admin/support-tickets/{$this->ticket->id}"))
                    ->label('View Ticket'),
            ])
            ->getDatabaseMessage();
    }
}
