<?php

namespace App\Models;

use App\Traits\TracksVersions;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DerivedMetric extends Model
{
    use HasFactory;
    use TracksVersions;

    protected $fillable = [
        'project_id',
        'name',
        'description',
        'calculation_type',
        'format',
        'ast',
        'source_series',
        'output_granularity',
        'is_active',
    ];

    protected $casts = [
        'ast' => 'array',
        'source_series' => 'array',
        'is_active' => 'boolean',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function widgets(): HasMany
    {
        return $this->hasMany(DashboardWidget::class);
    }

    protected function getVersionModelClass(): string
    {
        return \App\Models\DerivedMetricVersion::class;
    }

    public function getTrackableFields(): array
    {
        return ['name', 'description', 'calculation_type', 'ast', 'source_series', 'output_granularity', 'is_active'];
    }

    protected function getVersionForeignKey(): string
    {
        return 'derived_metric_id';
    }
}
