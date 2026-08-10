<?php

namespace App\Notifications;

use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Filament\Notifications\Notification as FilamentNotification;

class IntegrationDisconnectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Project $project,
        public string $provider
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $providerName = ucfirst($this->provider);
        
        return (new MailMessage)
            ->subject("Action Required: {$providerName} Integration Disconnected - APIs Hub")
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line("The authentication token for your {$providerName} integration on project '{$this->project->name}' has expired or was revoked.")
            ->line("To prevent synchronization failures, please reconnect your account as soon as possible.")
            ->action('Reconnect Account', url("/app/{$this->project->subdomain}/data-sources"))
            ->line('Thank you for using APIs Hub.');
    }

    public function toDatabase(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title('Integration Disconnected')
            ->body("The authentication token for {$this->provider} on project '{$this->project->name}' has expired or was revoked. Please reconnect your account.")
            ->danger()
            ->actions([
                \Filament\Notifications\Actions\Action::make('reconnect')
                    ->button()
                    ->url(url("/app/{$this->project->subdomain}/data-sources"))
                    ->label('Reconnect'),
            ])
            ->getDatabaseMessage();
    }
}
