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
        'timezone',
        'subdomain',
        'server_id',
        'user_id',
        'billing_profile_id',
        'db_name',
        'db_user',
        'db_password',
        'monitoring_token',
        'remote_admin_api_key',
        'remote_app_api_key',
        'last_deployed_at',
        'deploy_started_at',
        'last_sync_started_at',
        'git_repo',
        'git_branch',
        'is_active',
        'health_status',
        'health_metrics',
        'sync_config',
        'last_heartbeat_at',
        'error_count',
        'public_api_key',
        'billing_status',
        'past_due_at',
        'apis_hub_release_id',
        'google_profile_id',
        'facebook_profile_id',
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
     * Relationship: Billing profile paying for this project.
     */
    public function billingProfile(): BelongsTo
    {
        return $this->belongsTo(BillingProfile::class);
    }

    /**
     * Check if a user has administrative control over this project.
     * (Technical owner OR the owner of the assigned billing profile).
     */
    public function hasAdminAccess(User $user): bool
    {
        return $this->user_id === $user->id 
            || $this->billingProfile?->user_id === $user->id;
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

            // Auto-assign the user's default billing profile if not explicitly set
            if (empty($project->billing_profile_id) && !empty($project->user_id)) {
                $user = \App\Models\User::find($project->user_id);
                $defaultProfile = $user?->billingProfiles()->where('is_default', true)->first();
                if ($defaultProfile) {
                    $project->billing_profile_id = $defaultProfile->id;
                } else {
                    $project->billing_profile_id = $user?->billingProfiles()->first()?->id;
                }
            }
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
        'deploy_started_at' => 'datetime',
        'last_sync_started_at' => 'datetime',
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
     * Relationship: The Google Channel Profile associated with this project.
     */
    public function googleProfile(): BelongsTo
    {
        return $this->belongsTo(ChannelProfile::class, 'google_profile_id');
    }

    /**
     * Relationship: The Facebook Channel Profile associated with this project.
     */
    public function facebookProfile(): BelongsTo
    {
        return $this->belongsTo(ChannelProfile::class, 'facebook_profile_id');
    }

    /**
     * Transparent proxy for provider credentials to ensure backward compatibility.
     */
    public function getAttribute($key)
    {
        $proxies = [
            'facebook_user_token' => ['facebookProfile', 'access_token'],
            'facebook_user_id' => ['facebookProfile', 'provider_account_id'],
            'google_refresh_token' => ['googleProfile', 'refresh_token'],
            'google_user_id' => ['googleProfile', 'provider_account_id'],
        ];

        if (array_key_exists($key, $proxies)) {
            [$relation, $attribute] = $proxies[$key];

            return $this->{$relation}?->{$attribute};
        }

        return parent::getAttribute($key);
    }

    /**
     * Determine if a proxy attribute exists.
     */
    public function __isset($key)
    {
        $proxies = [
            'facebook_user_token' => ['facebookProfile', 'access_token'],
            'facebook_user_id' => ['facebookProfile', 'provider_account_id'],
            'google_refresh_token' => ['googleProfile', 'refresh_token'],
            'google_user_id' => ['googleProfile', 'provider_account_id'],
        ];

        if (array_key_exists($key, $proxies)) {
            [$relation, $attribute] = $proxies[$key];
            return $this->{$relation} !== null && $this->{$relation}->{$attribute} !== null;
        }

        return parent::__isset($key);
    }

    /**
     * Transparent proxy for setting provider credentials.
     * With the new ChannelProfile architecture, credentials should ideally be managed via the Profile entity.
     * This maintains basic backwards compatibility.
     */
    public function setAttribute($key, $value)
    {
        $proxies = [
            'facebook_user_token' => ['facebookProfile', 'access_token', 'facebook'],
            'facebook_user_id' => ['facebookProfile', 'provider_account_id', 'facebook'],
            'google_refresh_token' => ['googleProfile', 'refresh_token', 'google'],
            'google_user_id' => ['googleProfile', 'provider_account_id', 'google'],
        ];

        if (array_key_exists($key, $proxies)) {
            [$relation, $attribute, $provider] = $proxies[$key];
            
            // If the profile already exists, just update the attribute
            if ($this->{$relation}) {
                $this->{$relation}->update([$attribute => $value]);
            } else {
                // For backwards compatibility, create a new ChannelProfile owned by the project's trueOwner
                // This is a fallback. The UI should assign existing profiles.
                $profile = ChannelProfile::create([
                    'user_id' => $this->user_id,
                    'provider' => $provider,
                    $attribute => $value,
                ]);
                $foreignKey = "{$provider}_profile_id";
                $this->attributes[$foreignKey] = $profile->id;
                $this->setRelation($relation, $profile);
            }

            return $this;
        }

        return parent::setAttribute($key, $value);
    }

    /**
     * Check if the project has at least one channel asset enabled.
     */
    public function hasConfiguredAssets(): bool
    {
        $syncConfig = $this->sync_config ?? [];
        if (!is_array($syncConfig) || empty($syncConfig)) {
            return false;
        }

        foreach ($syncConfig as $channelKey => $channelConfig) {
            if (!is_array($channelConfig)) continue;
            if (empty($channelConfig['enabled'])) continue;

            if ($this->findEnabledAsset($channelConfig)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Recursively search any depth of a channel config array for an item
     * that has enabled=true. This handles both flat asset lists
     * (e.g. facebook_marketing.ad_accounts[]) and double-nested structures
     * (e.g. google_search_console.assets.sites[]).
     */
    private function findEnabledAsset(array $config, int $depth = 0): bool
    {
        if ($depth > 5) {
            return false; // Guard against unexpected deep structures
        }

        foreach ($config as $key => $value) {
            if (!is_array($value)) continue;

            // If any item in this array has an 'enabled' key, treat it as an asset list
            foreach ($value as $item) {
                if (is_array($item) && !empty($item['enabled'])) {
                    return true;
                }
            }

            // Otherwise recurse one level deeper (handles nested objects like assets.sites)
            if ($this->findEnabledAsset($value, $depth + 1)) {
                return true;
            }
        }

        return false;
    }
}
