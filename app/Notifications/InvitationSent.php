<?php

namespace App\Notifications;

use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Filament\Notifications\Notification as FilamentNotification;

class InvitationSent extends Notification implements ShouldQueue
{
    use Queueable;

    protected Project $project;
    protected string $invitedEmail;
    protected string $role;

    public function __construct(Project $project, string $invitedEmail, string $role)
    {
        $this->project = $project;
        $this->invitedEmail = $invitedEmail;
        $this->role = $role;
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('New invitation sent for :project', ['project' => $this->project->name]))
            ->greeting(__('Hello :name,', ['name' => $notifiable->name]))
            ->line(__('A new invitation has been sent to :email to join :project as :role.', [
                'email' => $this->invitedEmail,
                'project' => $this->project->name,
                'role' => $this->role,
            ]))
            ->line(__('They will be able to access the project once they accept the invitation.'));
    }

    public function toDatabase(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title(__('New invitation sent'))
            ->body(__(':email has been invited to :project as :role.', [
                'email' => $this->invitedEmail,
                'project' => $this->project->name,
                'role' => $this->role,
            ]))
            ->info()
            ->getDatabaseMessage();
    }
}
