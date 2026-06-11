<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Filament\Notifications\Notification as FilamentNotification;

class TierUpgradedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $tierName;

    public function __construct($tierName)
    {
        $this->tierName = $tierName;
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
                    ->subject('Subscription Upgraded - APIs Hub')
                    ->greeting('Hello ' . $notifiable->name . ',')
                    ->line("Your subscription has been successfully upgraded to the {$this->tierName} plan!")
                    ->line('You now have access to expanded limits and new features.')
                    ->action('Go to Dashboard', url('/app'))
                    ->line('Thank you for trusting APIs Hub!');
    }

    public function toDatabase(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title('Subscription Upgraded')
            ->body("Welcome to the {$this->tierName} plan! Your limits have been expanded.")
            ->success()
            ->getDatabaseMessage();
    }
}
