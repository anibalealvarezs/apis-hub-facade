<?php

namespace App\Notifications;

use App\Models\BillingProfile;
use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Filament\Notifications\Notification as FilamentNotification;

class BillingProfileAssignmentProcessedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public BillingProfile $billingProfile,
        public Project $project,
        public string $status,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $statusText = $this->status === 'approved' ? 'approved' : 'rejected';

        $mail = (new MailMessage)
            ->subject('Billing Profile Request ' . ucfirst($statusText) . ' - APIs Hub')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line("Your request to use the billing profile \"{$this->billingProfile->name}\" for project \"{$this->project->name}\" has been {$statusText}.");

        if ($this->status === 'approved') {
            $mail->line('Your project is now active and using the assigned billing profile.');
        } else {
            $mail->line('Your project has been suspended. Please assign a different billing profile to continue.');
        }

        return $mail
            ->action('View Project', url("/app/{$this->project->id}/billing"))
            ->line('If you have any questions, please contact our support team.');
    }

    public function toDatabase(object $notifiable): array
    {
        $statusText = $this->status === 'approved' ? 'approved' : 'rejected';

        $body = $this->status === 'approved'
            ? "Your request to use the billing profile \"{$this->billingProfile->name}\" for project \"{$this->project->name}\" has been approved. Your project is now active."
            : "Your request to use the billing profile \"{$this->billingProfile->name}\" for project \"{$this->project->name}\" has been rejected. Please assign a different billing profile.";

        return FilamentNotification::make()
            ->title('Billing Profile Request ' . ucfirst($statusText))
            ->body($body)
            ->actions([
                \Filament\Notifications\Actions\Action::make('view')
                    ->button()
                    ->url(url("/app/{$this->project->id}/billing"))
                    ->label('View Project'),
            ])
            ->getDatabaseMessage();
    }
}
