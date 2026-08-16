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

        $unit = $alert?->unit ?? ($payload['unit'] ?? 'number');

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
            'unit' => $unit,
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
     * Format a metric value according to the configured unit (number, percentage, currency).
     * For floats with non-zero decimals, retains up to 4 decimal places trimming trailing zeroes
     * (e.g. 1.1000 -> 1.1, 1.1001 -> 1.1001).
     */
    public function formatMetricValue(float|int|string|null $value, ?string $unit = 'number'): string
    {
        if ($value === null || $value === '') {
            return 'N/A';
        }

        $numericVal = (float) $value;

        switch ($unit) {
            case 'percentage':
            case '%':
                $pct = $numericVal * 100;
                $formatted = sprintf('%.4f', $pct);
                if (str_contains($formatted, '.')) {
                    $formatted = rtrim(rtrim($formatted, '0'), '.');
                }
                return $formatted . '%';

            case 'currency':
            case '$':
                $formatted = sprintf('%.4f', $numericVal);
                if (str_contains($formatted, '.')) {
                    $formatted = rtrim(rtrim($formatted, '0'), '.');
                }
                $parts = explode('.', $formatted);
                $parts[0] = number_format((float) $parts[0]);
                return '$' . implode('.', $parts);

            default:
                $formatted = sprintf('%.4f', $numericVal);
                if (str_contains($formatted, '.')) {
                    $formatted = rtrim(rtrim($formatted, '0'), '.');
                }
                $parts = explode('.', $formatted);
                $parts[0] = number_format((float) $parts[0]);
                return implode('.', $parts);
        }
    }

    /**
     * Determine if a metric is "higher is better" (e.g. CTR, ROAS, Revenue)
     * versus "lower is better" (e.g. Spend, Cost, CPC, CPA, Bounce Rate).
     */
    public function isHigherBetter(?Alert $alert, ?string $metricName = null): bool
    {
        $target = $metricName;
        if (!$target && $alert) {
            $sourceConfig = $alert->source_config ?? [];
            $target = ($sourceConfig['metric'] ?? '') . ' ' . ($alert->name ?? '') . ' ' . ($alert->source_summary ?? '');
        }

        $lowerTarget = strtolower($target ?? '');

        $lowerIsBetterKeywords = [
            'cost', 'spend', 'cpc', 'cpa', 'cpm', 'cpp', 'cost_per',
            'bounce', 'unsub', 'spam', 'refund', 'return',
            'cancel', 'error', 'churn', 'dispute', 'drop', 'failure', 'complaint',
        ];

        foreach ($lowerIsBetterKeywords as $keyword) {
            if (str_contains($lowerTarget, $keyword)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Compute visual styling indicators (arrow direction, badge emoji, color, status).
     *
     * Rules:
     * - Higher is better (e.g. CTR):
     *   - Upper limit exceeded -> ⬆️ Up, 🟢 Green (Good / Positive Surge)
     *   - Lower limit breached -> ⬇️ Down, 🔴 Red (Bad / Critical Drop)
     * - Lower is better (e.g. Spend / Cost):
     *   - Upper limit exceeded -> ⬆️ Up, 🔴 Red (Bad / High Spend Warning)
     *   - Lower limit breached -> ⬇️ Down, 🟢 Green (Good / Cost Savings)
     */
    public function getAlertVisualIndicators(
        ?Alert $alert,
        ?string $thresholdType,
        ?string $unit,
        float|int|string|null $evaluatedValue,
        float|int|string|null $thresholdValue = null
    ): array {
        $higherIsBetter = $this->isHigherBetter($alert);
        $isUpper = ($thresholdType === 'upper');

        $direction = $isUpper ? 'up' : 'down';
        $arrow = $isUpper ? '▲' : '▼';

        $isGood = $isUpper ? $higherIsBetter : !$higherIsBetter;
        $color = $isGood ? '#10b981' : '#ef4444'; // Emerald green vs Red
        $colorName = $isGood ? 'green' : 'red';
        $filamentStatus = $isGood ? 'success' : 'danger';

        $resolvedUnit = $unit ?? $alert?->unit ?? 'number';

        $formattedEvaluated = $this->formatMetricValue($evaluatedValue, $resolvedUnit);
        $formattedThreshold = $thresholdValue !== null ? $this->formatMetricValue($thresholdValue, $resolvedUnit) : null;

        $arrowBadge = $arrow;
        $iconHtml = "<span style='color: {$color}; font-weight: bold;'>{$arrow}</span>";

        return [
            'is_good' => $isGood,
            'higher_is_better' => $higherIsBetter,
            'direction' => $direction,
            'arrow' => $arrow,
            'arrow_badge' => $arrowBadge,
            'color' => $color,
            'color_name' => $colorName,
            'filament_status' => $filamentStatus,
            'unit' => $resolvedUnit,
            'formatted_evaluated' => $formattedEvaluated,
            'formatted_threshold' => $formattedThreshold,
            'icon_html' => $iconHtml,
            'title_prefix' => $arrowBadge,
        ];
    }

    /**
     * Compute baseline statistics (min, max, avg, stdDev, current) and dry-run triggers from a data series.
     */
    public function computeBaselineStats(array $dataPoints): array
    {
        $values = array_filter(array_map(fn($v) => is_numeric($v) ? (float) $v : null, array_values($dataPoints)), fn($v) => $v !== null);

        if (empty($values)) {
            return [
                'count' => 0,
                'min' => 0.0,
                'max' => 0.0,
                'avg' => 0.0,
                'std_dev' => 0.0,
                'current' => 0.0,
                'points' => [],
            ];
        }

        $count = count($values);
        $min = min($values);
        $max = max($values);
        $avg = array_sum($values) / $count;
        $current = end($values);

        // Standard deviation
        $variance = 0.0;
        foreach ($values as $v) {
            $variance += pow($v - $avg, 2);
        }
        $stdDev = sqrt($variance / max(1, $count));

        return [
            'count' => $count,
            'min' => round($min, 4),
            'max' => round($max, 4),
            'avg' => round($avg, 4),
            'std_dev' => round($stdDev, 4),
            'current' => round($current, 4),
            'points' => array_values($dataPoints),
        ];
    }

    /**
     * Simulate how many times an upper or lower limit would have triggered over a historical dataset.
     */
    public function simulateThresholdTriggers(
        array $dataPoints,
        ?float $upperLimit = null,
        ?float $lowerLimit = null,
        string $unit = 'number'
    ): array {
        $triggersUpper = 0;
        $triggersLower = 0;
        $totalEvaluated = 0;
        $triggeredIndices = [];

        // If unit is percentage, scale normalized float or percentage
        foreach ($dataPoints as $index => $point) {
            $val = is_numeric($point) ? (float) $point : (isset($point['value']) ? (float) $point['value'] : null);
            if ($val === null) {
                continue;
            }
            $totalEvaluated++;

            $isTriggered = false;
            if ($upperLimit !== null && $val > $upperLimit) {
                $triggersUpper++;
                $isTriggered = true;
            } elseif ($lowerLimit !== null && $val < $lowerLimit) {
                $triggersLower++;
                $isTriggered = true;
            }

            if ($isTriggered) {
                $triggeredIndices[] = $index;
            }
        }

        $totalTriggers = $triggersUpper + $triggersLower;

        // Health / Sensitivity assessment
        $sensitivity = 'balanced';
        $warning = null;

        if ($totalEvaluated > 0) {
            $triggerRate = $totalTriggers / $totalEvaluated;
            if ($triggerRate > 0.40) {
                $sensitivity = 'very_high';
                $warning = __('Thresholds may be too tight: this alert would have triggered in :percent% of recent evaluations.', [
                    'percent' => round($triggerRate * 100, 1),
                ]);
            } elseif ($triggerRate > 0.20) {
                $sensitivity = 'high';
            } elseif ($totalTriggers === 0 && ($upperLimit !== null || $lowerLimit !== null)) {
                $sensitivity = 'conservative';
            }
        }

        return [
            'total_evaluated' => $totalEvaluated,
            'total_triggers' => $totalTriggers,
            'triggers_upper' => $triggersUpper,
            'triggers_lower' => $triggersLower,
            'triggered_indices' => $triggeredIndices,
            'sensitivity' => $sensitivity,
            'warning' => $warning,
        ];
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

