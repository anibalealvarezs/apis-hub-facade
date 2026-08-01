<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectUserAssetGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'user_id',
        'asset_group_id',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assetGroup(): BelongsTo
    {
        return $this->belongsTo(AssetGroup::class);
    }
}
