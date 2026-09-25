<?php

namespace App\Notifications;

use App\Models\Project;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Filament\Notifications\Notification as FilamentNotification;

class ApiKeyRotatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Project $project,
        public ?User $rotatedBy = null
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $actorName = $this->rotatedBy?->name ?? __('A team member');

        return (new MailMessage)
            ->subject(__('Security Alert: API Key Rotated for :project - APIs Hub', ['project' => $this->project->name]))
            ->greeting(__('Hello :name,', ['name' => $notifiable->name]))
            ->line(__('The public API key for project ":project" has been rotated by :actor.', [
                'project' => $this->project->name,
                'actor' => $actorName,
            ]))
            ->line(__('The previous key has been immediately invalidated and the new key has been deployed to your dedicated node.'))
            ->line(__('Please make sure to update any external reporting platforms (PowerBI, Looker Studio), automated scripts, and third-party integrations with the new key to prevent synchronization disruption.'))
            ->action(__('View Integration Guide & API Key', ['project' => $this->project->name]), url("/app/{$this->project->subdomain}/integrations/api-access-reference"))
            ->line(__('If you did not expect this change, please contact your project administrator immediately.'));
    }

    public function toDatabase(object $notifiable): array
    {
        $actorName = $this->rotatedBy?->name ?? __('A team member');

        return FilamentNotification::make()
            ->title(__('API Key Rotated'))
            ->body(__('The API key for :project was rotated by :actor. External integrations must be updated.', [
                'project' => $this->project->name,
                'actor' => $actorName,
            ]))
            ->warning()
            ->actions([
                \Filament\Notifications\Actions\Action::make('view_guide')
                    ->button()
                    ->url(url("/app/{$this->project->subdomain}/integrations/api-access-reference"))
                    ->label(__('View API Key')),
            ])
            ->getDatabaseMessage();
    }
}
