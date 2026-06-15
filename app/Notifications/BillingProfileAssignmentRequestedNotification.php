<?php

namespace App\Notifications;

use App\Models\BillingProfile;
use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Filament\Notifications\Notification as FilamentNotification;

class BillingProfileAssignmentRequestedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public BillingProfile $billingProfile,
        public Project $project,
        public string $requesterName,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Billing Profile Assignment Request - APIs Hub')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line("{$this->requesterName} has requested to assign your billing profile \"{$this->billingProfile->name}\" to the project \"{$this->project->name}\".")
            ->line('Please review and approve or reject this request in your Account panel.')
            ->action('Review Request', url('/account/billing-profiles'))
            ->line('If you have any questions, please contact our support team.');
    }

    public function toDatabase(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title('Billing Profile Assignment Request')
            ->body("{$this->requesterName} requested to use your billing profile \"{$this->billingProfile->name}\" for project \"{$this->project->name}\".")
            ->actions([
                \Filament\Notifications\Actions\Action::make('view')
                    ->button()
                    ->url(url('/account/billing-profiles'))
                    ->label('Review Request'),
            ])
            ->getDatabaseMessage();
    }
}
