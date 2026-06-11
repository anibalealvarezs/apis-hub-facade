<?php

namespace App\Notifications;

use App\Models\Project;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Filament\Notifications\Notification as FilamentNotification;

class InvitationAccepted extends Notification implements ShouldQueue
{
    use Queueable;

    protected Project $project;
    protected User $joinedUser;

    public function __construct(Project $project, User $joinedUser)
    {
        $this->project = $project;
        $this->joinedUser = $joinedUser;
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__(':name joined :project', [
                'name' => $this->joinedUser->name,
                'project' => $this->project->name,
            ]))
            ->greeting(__('Hello :name,', ['name' => $notifiable->name]))
            ->line(__(':userName has accepted the invitation and joined :project as a collaborator.', [
                'userName' => $this->joinedUser->name,
                'project' => $this->project->name,
            ]))
            ->line(__('You can manage their access in the project settings.'));
    }

    public function toDatabase(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title(__(':name joined the project', ['name' => $this->joinedUser->name]))
            ->body(__(':userName has accepted the invitation to :project.', [
                'userName' => $this->joinedUser->name,
                'project' => $this->project->name,
            ]))
            ->success()
            ->getDatabaseMessage();
    }
}
