<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
        'public_api_key',
        'billing_status',
        'past_due_at',
        'apis_hub_release_id',
    ];

    /**
     * Relationship: Each project instance belongs to a specific User account (Owner).

     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relationship: Alias for user() to explicitly denote the single true owner of the project.
     */
    public function trueOwner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relationship: The deployment logs for this project.
     */
    public function deploymentLogs(): HasMany
    {
        return $this->hasMany(ProjectDeploymentLog::class);
    }

    /**
     * Relationship: Historical status changes (activations/suspensions).
     */
    public function statusLogs(): HasMany
    {
        return $this->hasMany(ProjectStatusLog::class);
    }

    /**
     * Relationship: Dynamic provider-based credentials.
     */
    public function credentials(): HasMany
    {
        return $this->hasMany(ProjectCredential::class);
    }

    /**
     * Relationship: Multiple users can have access to a single project instance.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->using(ProjectUser::class);
    }

    /**
     * Relationship: Billing profiles authorized to pay for this project.
     */
    public function authorizedBillingProfiles(): BelongsToMany
    {
        return $this->belongsToMany(BillingProfile::class, 'billing_profile_project')
            ->withPivot(['is_primary', 'status', 'assigned_by_user_id'])
            ->withTimestamps();
    }

    /**
     * Relationship: Invoices generated specifically for this project.
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Relationship: Pending invitations for this project.
     */
    public function pendingInvitations(): HasMany
    {
        return $this->hasMany(Invitation::class)->where('status', 'pending');
    }

    /**
     * Get the APIs Hub Release associated with the project.
     */
    public function apisHubRelease(): BelongsTo
    {
        return $this->belongsTo(ApisHubRelease::class);
    }

    /**
     * Boot logic for automatically generating monitoring tokens.
     */
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($project) {
            $project->monitoring_token = \Illuminate\Support\Str::uuid();
            $project->public_api_key = bin2hex(random_bytes(32));
            $project->remote_admin_api_key = bin2hex(random_bytes(32));
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
        'past_due_at' => 'datetime',
        'health_metrics' => 'array',
        'sync_config' => 'array',
        'error_count' => 'integer',
        'db_password' => 'encrypted',
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

    /**
     * Transparent proxy for provider credentials to ensure backward compatibility.
     * Intercepts legacy column calls and redirects them to the project_credentials table.
     */
    public function getAttribute($key)
    {
        $proxies = [
            'facebook_user_token' => ['facebook', 'token'],
            'facebook_user_id' => ['facebook', 'external_user_id'],
            'google_refresh_token' => ['google', 'refresh_token'],
            'google_user_id' => ['google', 'external_user_id'],
        ];

        if (array_key_exists($key, $proxies)) {
            [$provider, $attribute] = $proxies[$key];

            return $this->credentials()->where('provider', $provider)->first()?->{$attribute};
        }

        return parent::getAttribute($key);
    }

    /**
     * Transparent proxy for setting provider credentials.
     */
    public function setAttribute($key, $value)
    {
        $proxies = [
            'facebook_user_token' => ['facebook', 'token'],
            'facebook_user_id' => ['facebook', 'external_user_id'],
            'google_refresh_token' => ['google', 'refresh_token'],
            'google_user_id' => ['google', 'external_user_id'],
        ];

        if (array_key_exists($key, $proxies)) {
            [$provider, $attribute] = $proxies[$key];
            // We postpone actual database update to when the model is saved.
            // For now, we'll ensure the relation exists.
            $this->credentials()->updateOrCreate(
                ['provider' => $provider],
                [$attribute => $value]
            );

            return $this;
        }

        return parent::setAttribute($key, $value);
    }
}
