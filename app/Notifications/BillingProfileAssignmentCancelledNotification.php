<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Filament\Notifications\Notification as FilamentNotification;

class BillingProfileAssignmentCancelledNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $billingProfileName,
        public string $projectName,
        public string $reason,
        public ?string $actorName = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        if ($this->reason === 'expired') {
            return (new MailMessage)
                ->subject('Billing Profile Request Expired - APIs Hub')
                ->greeting('Hello ' . $notifiable->name . ',')
                ->line("The request to use your billing profile \"{$this->billingProfileName}\" for project \"{$this->projectName}\" has expired due to a lack of response.")
                ->line('No further action is required on your part. If the user still needs access, they will need to submit a new request.')
                ->line('Thank you for using APIs Hub.');
        }

        return (new MailMessage)
            ->subject('Billing Profile Request Cancelled - APIs Hub')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line("{$this->actorName} has cancelled their request to use your billing profile \"{$this->billingProfileName}\" for project \"{$this->projectName}\".")
            ->line('No further action is required on your part.')
            ->line('Thank you for using APIs Hub.');
    }

    public function toDatabase(object $notifiable): array
    {
        if ($this->reason === 'expired') {
            return FilamentNotification::make()
                ->title('Billing Profile Request Expired')
                ->body("The request to use your billing profile \"{$this->billingProfileName}\" for project \"{$this->projectName}\" has expired.")
                ->info()
                ->getDatabaseMessage();
        }

        return FilamentNotification::make()
            ->title('Billing Profile Request Cancelled')
            ->body("{$this->actorName} has cancelled their request to use your billing profile \"{$this->billingProfileName}\".")
            ->info()
            ->getDatabaseMessage();
    }
}
