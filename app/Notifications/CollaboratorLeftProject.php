<?php

namespace App\Notifications;

use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Filament\Notifications\Notification as FilamentNotification;

class CollaboratorLeftProject extends Notification implements ShouldQueue
{
    use Queueable;

    protected Project $project;
    protected string $userName;

    public function __construct(Project $project, string $userName)
    {
        $this->project = $project;
        $this->userName = $userName;
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__(':name left :project', ['name' => $this->userName, 'project' => $this->project->name]))
            ->greeting(__('Hello :name,', ['name' => $notifiable->name]))
            ->line(__(':userName has abandoned the project :project.', [
                'userName' => $this->userName,
                'project' => $this->project->name,
            ]))
            ->line(__('They will no longer have access to the project\'s data and resources.'));
    }

    public function toDatabase(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title(__(':name left the project', ['name' => $this->userName]))
            ->body(__(':userName has abandoned :project.', [
                'userName' => $this->userName,
                'project' => $this->project->name,
            ]))
            ->warning()
            ->getDatabaseMessage();
    }
}
