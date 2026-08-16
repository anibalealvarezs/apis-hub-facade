<?php

namespace App\Notifications;

use App\Models\Alert;
use App\Models\AlertLog;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AlertTriggeredNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Alert $alert,
        public AlertLog $alertLog
    ) {}

    public function via(object $notifiable): array
    {
        $channels = [];
        if ($this->alert->notify_ui) {
            $channels[] = 'database';
        }
        if ($this->alert->notify_email) {
            $channels[] = 'mail';
        }
        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $projectName = $this->alert->project?->name ?? 'APIs Hub';
        $subdomain = $this->alert->project?->subdomain ?? '';
        $evaluatedVal = number_format((float) $this->alertLog->evaluated_value, 2);
        $thresholdVal = number_format((float) $this->alertLog->threshold_value, 2);
        $thresholdType = $this->alertLog->threshold_type === 'upper' ? 'exceeded upper limit' : 'dropped below lower limit';

        return (new MailMessage)
            ->subject("Alert Triggered: {$this->alert->name} [{$projectName}]")
            ->markdown('emails.alert-triggered', [
                'user' => $notifiable,
                'alert' => $this->alert,
                'alertLog' => $this->alertLog,
                'projectName' => $projectName,
                'evaluatedVal' => $evaluatedVal,
                'thresholdVal' => $thresholdVal,
                'thresholdType' => $thresholdType,
                'alertUrl' => url("/app/{$subdomain}/alerts/{$this->alert->id}"),
            ]);
    }

    public function toDatabase(object $notifiable): array
    {
        $subdomain = $this->alert->project?->subdomain ?? '';
        $evaluatedVal = number_format((float) $this->alertLog->evaluated_value, 2);
        $thresholdVal = number_format((float) $this->alertLog->threshold_value, 2);
        $thresholdLabel = $this->alertLog->threshold_type === 'upper' ? 'Upper Limit Exceeded' : 'Lower Limit Breached';

        return FilamentNotification::make()
            ->title("Alert Triggered: {$this->alert->name}")
            ->body("Value **{$evaluatedVal}** {$thresholdLabel} (threshold: {$thresholdVal}) for {$this->alertLog->asset_summary}.")
            ->danger()
            ->actions([
                Action::make('view')
                    ->button()
                    ->url(url("/app/{$subdomain}/alerts/{$this->alert->id}"))
                    ->label('View Alert'),
            ])
            ->getDatabaseMessage();
    }
}
