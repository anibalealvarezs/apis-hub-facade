<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DashboardDraft extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'dashboard_id',
        'draft_token',
        'name',
        'description',
        'target_audience',
        'account_mode',
        'widgets',
        'grid_layout',
        'version',
        'expires_at',
    ];

    protected $casts = [
        'widgets' => 'array',
        'grid_layout' => 'array',
        'version' => 'integer',
        'expires_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function dashboard(): BelongsTo
    {
        return $this->belongsTo(Dashboard::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
}
