<?php

namespace App\Notifications;

use App\Models\TicketMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Filament\Notifications\Notification as FilamentNotification;

class TicketUserReplyNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public TicketMessage $message,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $ticket = $this->message->ticket;
        $panel = ($notifiable instanceof \App\Models\User && $notifiable->isAdmin()) ? 'admin' : 'account';

        return (new MailMessage)
            ->subject("New Reply on Ticket #{$ticket->id} - APIs Hub")
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line("{$this->message->user?->name} replied to ticket #{$ticket->id}.")
            ->line("Message: " . str($this->message->message)->limit(200))
            ->action('View Ticket', url("/{$panel}/support-tickets/{$ticket->id}"))
            ->line('Thank you for using APIs Hub.');
    }

    public function toDatabase(object $notifiable): array
    {
        $ticket = $this->message->ticket;
        $panel = ($notifiable instanceof \App\Models\User && $notifiable->isAdmin()) ? 'admin' : 'account';
        $title = "New Reply on Ticket #{$ticket->id}";
        $body = str($this->message->message)->limit(100);

        return FilamentNotification::make()
            ->title($title)
            ->body($body)
            ->info()
            ->actions([
                \Filament\Notifications\Actions\Action::make('view')
                    ->button()
                    ->url(url("/{$panel}/support-tickets/{$ticket->id}"))
                    ->label('View Ticket'),
            ])
            ->getDatabaseMessage();
    }
}
