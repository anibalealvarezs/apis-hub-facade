<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Filament\Notifications\Notification as FilamentNotification;

class BillingAccessRevokedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $billingProfileName,
        public array $reassignedProjects,
        public array $suspendedProjects,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Billing Profile Access Revoked - APIs Hub')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line("Your access to the billing profile \"{$this->billingProfileName}\" has been revoked by the owner.");

        if (!empty($this->reassignedProjects)) {
            $names = collect($this->reassignedProjects)->pluck('name')->implode(', ');
            $mail->line("The following projects have been reassigned to your default billing profile: {$names}.");
        }

        if (!empty($this->suspendedProjects)) {
            $names = collect($this->suspendedProjects)->pluck('name')->implode(', ');
            $mail->line("The following projects could not be reassigned due to quota limits and have been suspended: {$names}. Please assign a different billing profile to reactivate them.");
        }

        return $mail
            ->action('View Projects', url('/app'))
            ->line('If you have any questions, please contact our support team.');
    }

    public function toDatabase(object $notifiable): array
    {
        $body = "Your access to the billing profile \"{$this->billingProfileName}\" has been revoked.";

        $parts = [];
        if (!empty($this->reassignedProjects)) {
            $names = collect($this->reassignedProjects)->pluck('name')->implode(', ');
            $parts[] = "Reassigned: {$names}";
        }
        if (!empty($this->suspendedProjects)) {
            $names = collect($this->suspendedProjects)->pluck('name')->implode(', ');
            $parts[] = "Suspended: {$names}";
        }
        if (!empty($parts)) {
            $body .= ' ' . implode('. ', $parts) . '.';
        }

        return FilamentNotification::make()
            ->title('Billing Profile Access Revoked')
            ->body($body)
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
