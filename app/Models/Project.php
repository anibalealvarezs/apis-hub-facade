<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Model representing a dedicated APIs Hub Project / Deployment instance.
 *
 * @property int $id
 * @property string $name
 * @property string $subdomain
 * @property string $remote_admin_api_key
 * @property string $remote_app_api_key
 * @property string $monitoring_token
 * @property string $facebook_user_token
 * @property string $google_refresh_token
 * @property string $public_api_key
 * @property string $db_password
 */
class Project extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'subdomain',
        'server_id',
        'user_id',
        'db_name',
        'db_user',
        'db_password',
        'monitoring_token',
        'remote_admin_api_key',
        'remote_app_api_key',
        'last_deployed_at',
        'git_repo',
        'git_branch',
        'is_active',
        'health_status',
        'health_metrics',
        'last_heartbeat_at',
        'error_count',
        'facebook_user_token',
        'google_refresh_token',
        'public_api_key',
    ];

    /**
     * Relationship: Each project instance belongs to a specific User account (Owner).

     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relationship: Multiple users can have access to a single project instance.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    /**
     * Boot logic for automatically generating monitoring tokens.
     */
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($project) {
            $project->monitoring_token = \Illuminate\Support\Str::uuid();
        });
    }

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'last_deployed_at' => 'datetime',
        'last_heartbeat_at' => 'datetime',
        'health_metrics' => 'array',
        'sync_config' => 'array',
        'error_count' => 'integer',
        'db_password' => 'encrypted',
        'facebook_user_token' => 'encrypted',
        'google_refresh_token' => 'encrypted',
        'app_api_key' => 'encrypted',
        'remote_admin_api_key' => 'encrypted',
        'remote_app_api_key' => 'encrypted',
        'public_api_key' => 'encrypted',
    ];

    /**
     * Get the server that hosts this project.
     */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }
}
