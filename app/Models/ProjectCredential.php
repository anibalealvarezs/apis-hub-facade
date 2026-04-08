<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model representing a provider-specific credential for an APIs Hub project.
 *
 * @property int $id
 * @property int $project_id
 * @property string $provider
 * @property string $token
 * @property string $refresh_token
 * @property string $external_user_id
 * @property array $scopes
 * @property array $meta
 */
class ProjectCredential extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'project_id',
        'provider',
        'token',
        'refresh_token',
        'external_user_id',
        'scopes',
        'meta',
        'expires_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'token' => 'encrypted',
        'refresh_token' => 'encrypted',
        'scopes' => 'json',
        'meta' => 'json',
        'expires_at' => 'datetime',
    ];

    /**
     * Relationship: Each credential belongs to a specific project.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
