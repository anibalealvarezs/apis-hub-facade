<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DashboardVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'dashboard_id',
        'project_id',
        'user_id',
        'version_number',
        'name',
        'description',
        'grid_layout',
        'controls',
        'is_public',
        'is_default',
        'change_summary',
    ];

    protected $casts = [
        'grid_layout' => 'array',
        'controls' => 'array',
        'is_public' => 'boolean',
        'is_default' => 'boolean',
    ];

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
