<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DashboardWidget extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'dashboard_id',
        'custom_kpi_id',
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
    ];

    protected $casts = [
        'source_config' => 'array',
        'controls' => 'array',
    ];

    public function dashboard(): BelongsTo
    {
        return $this->belongsTo(Dashboard::class);
    }

    public function customKpi(): BelongsTo
    {
        return $this->belongsTo(CustomKpi::class);
    }
}
