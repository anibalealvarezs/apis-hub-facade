<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetBillingLock extends Model
{
    protected $fillable = [
        'user_id',
        'project_id',
        'channel',
        'asset_identifier',
        'status',
        'staged_at',
        'locked_at',
        'disabled_at',
    ];

    protected $casts = [
        'staged_at' => 'datetime',
        'locked_at' => 'datetime',
        'disabled_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
