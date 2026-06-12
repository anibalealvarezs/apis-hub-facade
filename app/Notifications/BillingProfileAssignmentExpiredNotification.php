<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Filament\Notifications\Notification as FilamentNotification;

class BillingProfileAssignmentExpiredNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $billingProfileName,
        public string $projectName,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Billing Profile Request Expired - APIs Hub')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line("Your request to use the billing profile \"{$this->billingProfileName}\" for project \"{$this->projectName}\" has expired because the owner did not respond in time.")
            ->line('Your project has been reassigned to your default billing profile and should now be active.')
            ->line('If you still need access to that profile, please submit a new request.')
            ->action('View Project', url("/app/{$this->projectName}"))
            ->line('Thank you for using APIs Hub.');
    }

    public function toDatabase(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title('Billing Profile Request Expired')
            ->body("Your request to use the billing profile \"{$this->billingProfileName}\" for project \"{$this->projectName}\" has expired. The project has been reassigned to your default billing profile.")
            ->warning()
            ->actions([
                \Filament\Notifications\Actions\Action::make('view')
                    ->button()
                    ->url(url('/app'))
                    ->label('View Projects'),
            ])
            ->getDatabaseMessage();
    }
}
