<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Filament\Notifications\Notification as FilamentNotification;

class ProjectsSuspendedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $suspendedCount;

    public function __construct($suspendedCount = null)
    {
        $this->suspendedCount = $suspendedCount;
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $msg = (new MailMessage)
                    ->error()
                    ->subject('Projects Suspended - APIs Hub')
                    ->greeting('Hello ' . $notifiable->name . ',')
                    ->line('Your billing grace period has expired.');
                    
        if ($this->suspendedCount) {
            $msg->line("We have suspended {$this->suspendedCount} of your projects that exceed the Free tier limits.");
        } else {
            $msg->line('Your projects that exceed the Free tier limits have been suspended.');
        }

        return $msg->line('To reactivate your projects, please renew your subscription.')
                   ->action('Renew Subscription', url('/account/account-subscription'))
                   ->line('If you believe this is an error, please contact support.');
    }

    public function toDatabase(object $notifiable): array
    {
        $body = $this->suspendedCount 
            ? "Your grace period expired. {$this->suspendedCount} projects have been suspended." 
            : "Your grace period expired. Projects exceeding limits have been suspended.";

        return FilamentNotification::make()
            ->title('Projects Suspended')
            ->body($body)
            ->danger()
            ->actions([
                \Filament\Notifications\Actions\Action::make('renew')
                    ->button()
                    ->url(url('/account/account-subscription'))
                    ->label('Renew Plan')
            ])
            ->getDatabaseMessage();
    }
}
