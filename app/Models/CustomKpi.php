<?php

namespace App\Models;

use App\Traits\TracksVersions;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomKpi extends Model
{
    use HasFactory;
    use TracksVersions;

    protected $fillable = [
        'project_id',
        'name',
        'description',
        'calculation_type',
        'filters',
        'ast',
        'is_active',
    ];

    protected $casts = [
        'ast' => 'array',
        'filters' => 'array',
        'is_active' => 'boolean',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function dashboards(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Dashboard::class, 'dashboard_widgets', 'custom_kpi_id', 'dashboard_id');
    }

    protected function getVersionModelClass(): string
    {
        return \App\Models\CustomKpiVersion::class;
    }

    protected function getTrackableFields(): array
    {
        return ['name', 'description', 'calculation_type', 'ast', 'filters', 'is_active'];
    }

    protected function getVersionForeignKey(): string
    {
        return 'custom_kpi_id';
    }
}
