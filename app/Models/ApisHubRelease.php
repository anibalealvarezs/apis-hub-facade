<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApisHubRelease extends Model
{
    use HasFactory;

    protected $fillable = [
        'version_tag',
        'is_active',
        'supported_channels',
        'config_schemas',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'supported_channels' => 'array',
        'config_schemas' => 'array',
    ];

    /**
     * Get the projects using this release.
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }
}
