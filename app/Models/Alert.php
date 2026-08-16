<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class Alert extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'project_id',
        'user_id',
        'name',
        'description',
        'source_type',
        'source_config',
        'ast',
        'filters',
        'aggregation_method',
        'upper_limit',
        'lower_limit',
        'unit',
        'notify_ui',
        'notify_email',
        'schedule_type',
        'schedule_config',
        'next_evaluation_at',
        'last_evaluated_at',
        'last_triggered_at',
        'is_active',
    ];

    protected $casts = [
        'source_config' => 'array',
        'ast' => 'array',
        'filters' => 'array',
        'schedule_config' => 'array',
        'upper_limit' => 'decimal:6',
        'lower_limit' => 'decimal:6',
        'is_active' => 'boolean',
        'notify_ui' => 'boolean',
        'notify_email' => 'boolean',
        'next_evaluation_at' => 'datetime',
        'last_evaluated_at' => 'datetime',
        'last_triggered_at' => 'datetime',
    ];

    // ── Relations ──────────────────────────────────────────────────

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function calculationLines(): HasMany
    {
        return $this->hasMany(AlertCalculationLine::class)->orderBy('sort_order');
    }

    public function alertLogs(): HasMany
    {
        return $this->hasMany(AlertLog::class);
    }

    // ── Scopes ─────────────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeDue(Builder $query): Builder
    {
        return $query->where('next_evaluation_at', '<=', now());
    }

    // ── Scheduling ─────────────────────────────────────────────────

    /**
     * Compute the next evaluation timestamp based on schedule config and project timezone.
     * Returns null for 'once' type that has already run.
     */
    public function computeNextEvaluationAt(): ?Carbon
    {
        $config = $this->schedule_config ?? [];
        $time = $config['time'] ?? '08:00';
        $projectTz = $this->project?->timezone ?? 'UTC';

        $now = Carbon::now($projectTz);

        // Parse the scheduled time components
        [$hour, $minute] = array_map('intval', explode(':', $time));

        switch ($this->schedule_type) {
            case 'daily':
                $next = $now->copy()->setTime($hour, $minute, 0);
                if ($next->lte($now)) {
                    $next->addDay();
                }
                return $next->utc();

            case 'weekly':
                $dayOfWeek = (int) ($config['day_of_week'] ?? 1); // 0=Sun, 1=Mon, ...
                $next = $now->copy()->next($dayOfWeek)->setTime($hour, $minute, 0);
                // If today IS that day and the time hasn't passed, use today
                if ($now->dayOfWeek === $dayOfWeek) {
                    $today = $now->copy()->setTime($hour, $minute, 0);
                    if ($today->gt($now)) {
                        $next = $today;
                    }
                }
                return $next->utc();

            case 'biweekly':
                $dayOfWeek = (int) ($config['day_of_week'] ?? 1);
                $next = $now->copy()->next($dayOfWeek)->setTime($hour, $minute, 0);
                if ($now->dayOfWeek === $dayOfWeek) {
                    $today = $now->copy()->setTime($hour, $minute, 0);
                    if ($today->gt($now)) {
                        $next = $today;
                    }
                }
                // If last evaluation was less than 13 days ago, skip to next occurrence
                if ($this->last_evaluated_at) {
                    $daysSinceLastEval = $this->last_evaluated_at->diffInDays($next);
                    if ($daysSinceLastEval < 13) {
                        $next->addWeek();
                    }
                }
                return $next->utc();

            case 'monthly':
                $daysOfMonth = $config['days_of_month'] ?? [1];
                sort($daysOfMonth);

                $candidates = [];
                foreach ($daysOfMonth as $day) {
                    $candidate = $now->copy()->day(min($day, $now->daysInMonth))->setTime($hour, $minute, 0);
                    if ($candidate->gt($now)) {
                        $candidates[] = $candidate;
                    }
                }

                if (empty($candidates)) {
                    // All days this month have passed, use the first day of next month
                    $nextMonth = $now->copy()->addMonth()->startOfMonth();
                    $firstDay = min($daysOfMonth[0], $nextMonth->daysInMonth);
                    $candidates[] = $nextMonth->day($firstDay)->setTime($hour, $minute, 0);
                }

                return collect($candidates)->min()->utc();

            case 'once':
                if ($this->last_evaluated_at) {
                    return null; // Already ran
                }
                $date = $config['date'] ?? null;
                if (!$date) {
                    return null;
                }
                $next = Carbon::parse($date, $projectTz)->setTime($hour, $minute, 0);
                return $next->utc();

            default:
                return null;
        }
    }

    /**
     * Get the total count of calculation lines for billing purposes.
     */
    public function getTotalCalculationLines(): int
    {
        return $this->calculationLines()->count();
    }
}
