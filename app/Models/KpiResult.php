<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KpiResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'custom_kpi_id',
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

    public function customKpi(): BelongsTo
    {
        return $this->belongsTo(CustomKpi::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
