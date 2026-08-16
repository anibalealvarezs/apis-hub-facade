<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlertLog extends Model
{
    use HasFactory;
    use Prunable;

    /**
     * Alert logs only have created_at (no updated_at — logs are immutable).
     */
    const UPDATED_AT = null;

    protected $fillable = [
        'project_id',
        'alert_id',
        'alert_calculation_line_id',
        'alert_name',
        'alert_description',
        'source_type',
        'source_summary',
        'asset_summary',
        'ast_snapshot',
        'asset_filter_snapshot',
        'evaluated_value',
        'threshold_type',
        'threshold_value',
        'unit',
        'aggregation_method',
        'evaluation_window',
        'status',
        'warning_message',
        'notified_ui',
        'notified_email',
        'triggered_at',
    ];

    protected $casts = [
        'ast_snapshot' => 'array',
        'asset_filter_snapshot' => 'array',
        'evaluation_window' => 'array',
        'evaluated_value' => 'decimal:6',
        'threshold_value' => 'decimal:6',
        'notified_ui' => 'boolean',
        'notified_email' => 'boolean',
        'triggered_at' => 'datetime',
    ];

    // ── Pruning ────────────────────────────────────────────────────

    /**
     * Automatically hard-delete log records older than 30 days.
     */
    public function prunable(): Builder
    {
        return static::where('created_at', '<', now()->subDays(30));
    }

    // ── Relations ──────────────────────────────────────────────────

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function alert(): BelongsTo
    {
        return $this->belongsTo(Alert::class);
    }

    public function calculationLine(): BelongsTo
    {
        return $this->belongsTo(AlertCalculationLine::class, 'alert_calculation_line_id');
    }
}
