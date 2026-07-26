<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DerivedMetricResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'derived_metric_id',
        'project_id',
        'controls_hash',
        'result',
        'cached_at',
        'expires_at',
    ];

    protected $casts = [
        'result' => 'array',
        'cached_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function derivedMetric(): BelongsTo
    {
        return $this->belongsTo(DerivedMetric::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
