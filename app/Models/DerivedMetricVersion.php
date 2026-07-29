<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DerivedMetricVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'derived_metric_id',
        'project_id',
        'user_id',
        'version_number',
        'name',
        'description',
        'calculation_type',
        'ast',
        'source_series',
        'output_granularity',
        'is_active',
        'change_summary',
    ];

    protected $casts = [
        'ast' => 'array',
        'source_series' => 'array',
        'is_active' => 'boolean',
    ];

    public function derivedMetric(): BelongsTo
    {
        return $this->belongsTo(DerivedMetric::class);
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
