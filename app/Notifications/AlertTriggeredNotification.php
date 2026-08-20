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

        $indicators = app(\App\Services\AlertService::class)->getAlertVisualIndicators(
            $this->alert,
            $this->alertLog->threshold_type,
            $this->alertLog->unit,
            $this->alertLog->evaluated_value,
            $this->alertLog->threshold_value
        );

        $arrow = $indicators['arrow'];

        return (new MailMessage)
            ->subject("{$arrow} Alert Triggered: {$this->alert->name} [{$projectName}]")
            ->markdown('emails.alert-triggered', [
                'user' => $notifiable,
                'alert' => $this->alert,
                'alertLog' => $this->alertLog,
                'projectName' => $projectName,
                'indicators' => $indicators,
                'evaluatedVal' => $indicators['formatted_evaluated'],
                'thresholdVal' => $indicators['formatted_threshold'],
                'thresholdType' => $this->alertLog->threshold_type,
                'alertUrl' => url("/app/{$subdomain}/alerts/{$this->alert->id}"),
            ]);
    }

    public function toDatabase(object $notifiable): array
    {
        $subdomain = $this->alert->project?->subdomain ?? '';

        $indicators = app(\App\Services\AlertService::class)->getAlertVisualIndicators(
            $this->alert,
            $this->alertLog->threshold_type,
            $this->alertLog->unit,
            $this->alertLog->evaluated_value,
            $this->alertLog->threshold_value
        );

        $thresholdLabel = $this->alertLog->threshold_type === 'upper' ? 'Upper Limit Exceeded' : 'Lower Limit Breached';
        $color = $indicators['color'];
        $arrow = $indicators['arrow'];
        $titleHtml = new \Illuminate\Support\HtmlString("<span style='color: {$color}; font-weight: 800; font-size: 1.1em; margin-right: 4px;'>{$arrow}</span> " . e("Alert Triggered: {$this->alert->name}"));

        $notification = FilamentNotification::make()
            ->title($titleHtml)
            ->body("Value **{$indicators['formatted_evaluated']}** {$thresholdLabel} (threshold: {$indicators['formatted_threshold']}) for {$this->alertLog->asset_summary}.");

        if ($indicators['is_good']) {
            $notification->success();
        } else {
            $notification->danger();
        }

        return $notification
            ->actions([
                Action::make('view')
                    ->button()
                    ->url(url("/app/{$subdomain}/alerts/{$this->alert->id}"))
                    ->label('View Alert'),
            ])
            ->getDatabaseMessage();
    }
}
