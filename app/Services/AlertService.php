<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\AlertCalculationLine;
use App\Models\AlertLog;
use App\Models\Project;
use App\Notifications\AlertTriggeredNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class AlertService
{
    /**
     * Handle a triggered alert callback from the tenant.
     * Creates an AlertLog with full snapshot and dispatches notifications.
     */
    public function handleTriggeredAlert(Project $project, array $payload): void
    {
        $alertId = $payload['alert_id'] ?? null;
        $lineId = $payload['calculation_line_id'] ?? null;
        $status = $payload['status'] ?? 'ok';
        $triggeredAt = $payload['triggered_at'] ?? now()->toIso8601String();

        $alert = $alertId ? Alert::where('id', $alertId)->where('project_id', $project->id)->first() : null;
        $line = $lineId ? AlertCalculationLine::find($lineId) : null;

        // Build self-contained snapshot
        $log = AlertLog::create([
            'project_id' => $project->id,
            'alert_id' => $alert?->id,
            'alert_calculation_line_id' => $line?->id,
            'alert_name' => $alert?->name ?? ($payload['alert_name'] ?? 'Unknown Alert'),
            'alert_description' => $alert?->description,
            'source_type' => $alert?->source_type ?? ($payload['source_type'] ?? 'unknown'),
            'source_summary' => $payload['source_summary'] ?? $this->buildAlertSummary($alert),
            'asset_summary' => $payload['asset_summary'] ?? ($line?->label ?? 'N/A'),
            'ast_snapshot' => $alert?->ast ?? ($payload['ast_snapshot'] ?? []),
            'asset_filter_snapshot' => $line?->asset_filter ?? ($payload['asset_filter_snapshot'] ?? []),
            'evaluated_value' => $payload['evaluated_value'] ?? null,
            'threshold_type' => $payload['threshold_type'] ?? null,
            'threshold_value' => $payload['threshold_value'] ?? null,
            'aggregation_method' => $alert?->aggregation_method ?? ($payload['aggregation_method'] ?? 'latest'),
            'evaluation_window' => $payload['evaluation_window'] ?? [],
            'status' => $status,
            'warning_message' => $payload['warning_message'] ?? null,
            'notified_ui' => false,
            'notified_email' => false,
            'triggered_at' => Carbon::parse($triggeredAt),
        ]);

        // Update alert timestamps
        if ($alert) {
            $updateData = ['last_evaluated_at' => now()];
            if ($status === 'triggered') {
                $updateData['last_triggered_at'] = now();
            }
            // Recompute next evaluation
            $alert->fill($updateData);
            $alert->next_evaluation_at = $alert->computeNextEvaluationAt();
            $alert->saveQuietly();
        }

        // Dispatch notifications only for 'triggered' status
        if ($status === 'triggered' && $alert) {
            $notifiedUi = false;
            $notifiedEmail = false;

            try {
                $notification = new AlertTriggeredNotification($alert, $log);

                if ($alert->notify_ui || $alert->notify_email) {
                    $alert->user->notify($notification);
                    $notifiedUi = $alert->notify_ui;
                    $notifiedEmail = $alert->notify_email;
                }
            } catch (\Exception $e) {
                Log::error("AlertService: Failed to dispatch notification for alert {$alert->id}: " . $e->getMessage());
            }

            $log->update([
                'notified_ui' => $notifiedUi,
                'notified_email' => $notifiedEmail,
            ]);
        }
    }

    /**
     * Generate a human-readable source summary for log snapshots.
     */
    public function buildAlertSummary(?Alert $alert): string
    {
        if (!$alert) {
            return 'Unknown';
        }

        $sourceConfig = $alert->source_config ?? [];

        return match ($alert->source_type) {
            'metric' => ($sourceConfig['channel'] ?? 'unknown') . '.' . ($sourceConfig['metric'] ?? 'unknown'),
            'kpi' => 'KPI: ' . ($sourceConfig['kpi_name'] ?? 'Custom KPI #' . ($sourceConfig['kpi_id'] ?? '?')),
            'derived_metric' => 'DM: ' . ($sourceConfig['dm_name'] ?? 'Derived Metric #' . ($sourceConfig['dm_id'] ?? '?')),
            default => $alert->source_type,
        };
    }

    /**
     * Batch-update next_evaluation_at for all active project alerts.
     */
    public function computeNextEvaluationForAll(Project $project): void
    {
        $alerts = $project->alerts()->active()->get();

        foreach ($alerts as $alert) {
            $next = $alert->computeNextEvaluationAt();
            if ($alert->next_evaluation_at?->equalTo($next) === false || $alert->next_evaluation_at === null) {
                $alert->update(['next_evaluation_at' => $next]);
            }
        }
    }

    /**
     * Count all calculation lines across all active alerts for a project.
     * Used for tier limit enforcement.
     */
    public function countTotalCalculationLines(Project $project): int
    {
        return AlertCalculationLine::whereHas('alert', function ($q) use ($project) {
            $q->where('project_id', $project->id)->where('is_active', true)->whereNull('deleted_at');
        })->count();
    }

    /**
     * Check if a scheduled time falls within a 2-hour window after any channel's daily sync.
     * Returns a warning message or null.
     */
    public function getSyncWindowWarning(Project $project, string $scheduledTime): ?string
    {
        $syncConfig = $project->sync_config ?? [];
        $projectTz = $project->timezone ?? 'UTC';

        // Collect all channel sync times
        $syncTimes = [];
        foreach ($syncConfig as $channel => $config) {
            if (isset($config['sync_time'])) {
                $syncTimes[$channel] = $config['sync_time'];
            }
        }

        if (empty($syncTimes)) {
            return null;
        }

        $scheduledMinutes = $this->timeToMinutes($scheduledTime);

        foreach ($syncTimes as $channel => $syncTime) {
            $syncMinutes = $this->timeToMinutes($syncTime);
            $windowEnd = $syncMinutes + 120; // 2-hour window

            // Handle midnight wraparound
            if ($windowEnd > 1440) {
                if ($scheduledMinutes >= $syncMinutes || $scheduledMinutes <= ($windowEnd - 1440)) {
                    $recommended = sprintf('%02d:%02d', intdiv($windowEnd % 1440, 60), $windowEnd % 1440 % 60);
                    return __('This schedule falls within 2 hours of your daily data sync (:sync_time for :channel). We recommend scheduling after :recommended_time to ensure all data is fully synced.', [
                        'sync_time' => $syncTime,
                        'channel' => $channel,
                        'recommended_time' => $recommended,
                    ]);
                }
            } elseif ($scheduledMinutes >= $syncMinutes && $scheduledMinutes <= $windowEnd) {
                $recommended = sprintf('%02d:%02d', intdiv($windowEnd, 60), $windowEnd % 60);
                return __('This schedule falls within 2 hours of your daily data sync (:sync_time for :channel). We recommend scheduling after :recommended_time to ensure all data is fully synced.', [
                    'sync_time' => $syncTime,
                    'channel' => $channel,
                    'recommended_time' => $recommended,
                ]);
            }
        }

        return null;
    }

    /**
     * Convert "HH:MM" to minutes since midnight.
     */
    protected function timeToMinutes(string $time): int
    {
        $parts = explode(':', $time);
        return ((int) ($parts[0] ?? 0)) * 60 + ((int) ($parts[1] ?? 0));
    }
}
