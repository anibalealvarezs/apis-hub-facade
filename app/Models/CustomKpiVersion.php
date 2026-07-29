<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomKpiVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'custom_kpi_id',
        'project_id',
        'user_id',
        'version_number',
        'name',
        'description',
        'calculation_type',
        'ast',
        'filters',
        'is_active',
        'change_summary',
    ];

    protected $casts = [
        'ast' => 'array',
        'filters' => 'array',
        'is_active' => 'boolean',
    ];

    public function customKpi(): BelongsTo
    {
        return $this->belongsTo(CustomKpi::class);
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
