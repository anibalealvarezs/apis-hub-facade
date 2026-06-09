<?php

namespace App\Notifications;

use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Filament\Notifications\Notification as FilamentNotification;

class MemberExpelledFromProject extends Notification implements ShouldQueue
{
    use Queueable;

    protected Project $project;

    public function __construct(Project $project)
    {
        $this->project = $project;
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('You have been removed from :project', ['project' => $this->project->name]))
            ->greeting(__('Hello :name,', ['name' => $notifiable->name]))
            ->line(__('You have been removed from the project :project.', ['project' => $this->project->name]))
            ->line(__('If you believe this is an error, please contact the project owner.'));
    }

    public function toDatabase(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title(__('Removed from :project', ['project' => $this->project->name]))
            ->body(__('You have been removed from the project.'))
            ->danger()
            ->getDatabaseMessage();
    }
}
