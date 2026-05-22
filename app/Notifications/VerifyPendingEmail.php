<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Carbon;

class VerifyPendingEmail extends Notification implements ShouldQueue
{
    use Queueable;

    public string $pendingEmail;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $pendingEmail)
    {
        $this->pendingEmail = $pendingEmail;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $verificationUrl = URL::temporarySignedRoute(
            'profile.verify-pending-email',
            Carbon::now()->addMinutes(config('auth.verification.expire', 60)),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($this->pendingEmail),
            ]
        );

        return (new MailMessage)
            ->subject('Verify Your New Email Address')
            ->line('You recently requested to change your email address.')
            ->line('Please click the button below to verify this new email address.')
            ->action('Verify Email Address', $verificationUrl)
            ->line('If you did not request an email change, no further action is required.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
