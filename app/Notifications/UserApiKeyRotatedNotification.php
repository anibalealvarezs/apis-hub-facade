<?php

namespace App\Notifications;

use App\Models\Project;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Filament\Notifications\Notification as FilamentNotification;

class UserApiKeyRotatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Project $project,
        public ?User $rotatedBy = null,
        public bool $isForced = false
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $actorName = $this->rotatedBy?->name ?? __('A project administrator');

        $message = (new MailMessage)
            ->subject(__('Security Alert: Your API Access Key Rotated for :project - APIs Hub', ['project' => $this->project->name]))
            ->greeting(__('Hello :name,', ['name' => $notifiable->name]));

        if ($this->isForced) {
            $message->line(__('Your scoped API key for project ":project" was rotated by :actor.', [
                'project' => $this->project->name,
                'actor' => $actorName,
            ]))
            ->line(__('This action was initiated by a project owner/editor. Your previous key has been immediately invalidated to maintain security.'));
        } else {
            $message->line(__('Your scoped API key for project ":project" has been rotated successfully.', [
                'project' => $this->project->name,
            ]));
        }

        return $message
            ->line(__('Please update your MCP clients (Google Antigravity, Claude Desktop, Cursor), external reporting scripts, or dashboards with your new key.'))
            ->action(__('View My API Key & MCP Guide', ['project' => $this->project->name]), url("/app/{$this->project->subdomain}/integrations/mcp-access-reference"))
            ->line(__('If you did not authorize or expect this change, please contact your project administrator immediately.'));
    }

    public function toDatabase(object $notifiable): array
    {
        $actorName = $this->rotatedBy?->name ?? __('A project administrator');

        $body = $this->isForced
            ? __('Your scoped API key for :project was rotated by :actor. External integrations must be updated.', [
                'project' => $this->project->name,
                'actor' => $actorName,
            ])
            : __('Your scoped API key for :project was rotated successfully.', [
                'project' => $this->project->name,
            ]);

        return FilamentNotification::make()
            ->title(__('Personal API Key Rotated'))
            ->body($body)
            ->warning()
            ->actions([
                \Filament\Notifications\Actions\Action::make('view_key')
                    ->button()
                    ->url(url("/app/{$this->project->subdomain}/integrations/mcp-access-reference"))
                    ->label(__('View New Key')),
            ])
            ->getDatabaseMessage();
    }
}
