<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Filament\Notifications\Notification as FilamentNotification;

class BillingPaymentFailedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct()
    {
        //
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
                    ->error()
                    ->subject('Payment Action Required - APIs Hub')
                    ->greeting('Hello ' . $notifiable->name . ',')
                    ->line('We were unable to process your recent subscription payment.')
                    ->line('Your account has entered a 7-day grace period. Please update your payment method to avoid interruption of your active projects.')
                    ->action('Update Payment Method', url('/account/account-subscription'))
                    ->line('If you need help, please contact our support team.');
    }

    public function toDatabase(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title('Payment Failed')
            ->body('We could not process your payment. You have a 7-day grace period before your projects are suspended.')
            ->danger()
            ->actions([
                \Filament\Notifications\Actions\Action::make('view')
                    ->button()
                    ->url(url('/account/account-subscription'))
                    ->label('Fix Payment')
            ])
            ->getDatabaseMessage();
    }
}
