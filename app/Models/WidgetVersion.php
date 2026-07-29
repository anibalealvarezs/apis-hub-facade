<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WidgetVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'dashboard_widget_id',
        'dashboard_id',
        'project_id',
        'user_id',
        'version_number',
        'custom_kpi_id',
        'derived_metric_id',
        'name',
        'title',
        'description',
        'source_type',
        'source_config',
        'widget_type',
        'controls',
        'grid_x',
        'grid_y',
        'grid_w',
        'grid_h',
        'change_summary',
    ];

    protected $casts = [
        'source_config' => 'array',
        'controls' => 'array',
    ];

    public function dashboardWidget(): BelongsTo
    {
        return $this->belongsTo(DashboardWidget::class);
    }

    public function dashboard(): BelongsTo
    {
        return $this->belongsTo(Dashboard::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
