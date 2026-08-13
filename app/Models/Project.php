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
        'redeploy_pending',
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
        'supported_locales',
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
     * Relationship: Alias for user() used by Filament tables and other UI components.
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }



    /**
     * Relationship: Support tickets where this project is tagged internally (admin-only associations).
     */
    public function supportTicketInternalAssociations(): BelongsToMany
    {
        return $this->belongsToMany(SupportTicket::class, 'ticket_internal_projects');
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
        return $this->hasMany(ProjectInvitation::class);
    }

    /**
     * Relationship: Custom KPIs defined for this project.
     */
    public function customKpis(): HasMany
    {
        return $this->hasMany(CustomKpi::class);
    }

    /**
     * Relationship: Derived Metrics defined for this project.
     */
    public function derivedMetrics(): HasMany
    {
        return $this->hasMany(DerivedMetric::class);
    }

    /**
     * Relationship: Dashboards belonging to this project.
     */
    public function dashboards(): HasMany
    {
        return $this->hasMany(Dashboard::class);
    }

    /**
     * Relationship: Asset Groups belonging to this project.
     */
    public function assetGroups(): HasMany
    {
        return $this->hasMany(AssetGroup::class);
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

            // Auto-assign the default APIs Hub Release
            if (empty($project->apis_hub_release_id)) {
                $defaultRelease = \App\Models\ApisHubRelease::where('is_default', true)->first();
                if ($defaultRelease) {
                    $project->apis_hub_release_id = $defaultRelease->id;
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
        'redeploy_pending' => 'boolean',
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
        'supported_locales' => 'array',
    ];

    public static function getSupportedLanguageCatalog(): array
    {
        $configuredCatalog = config('languages.catalog');
        if (is_array($configuredCatalog) && !empty($configuredCatalog)) {
            return $configuredCatalog;
        }

        if (class_exists(\Symfony\Component\Intl\Languages::class)) {
            try {
                $names = \Symfony\Component\Intl\Languages::getNames(app()->getLocale());
                asort($names);
                return $names;
            } catch (\Throwable $e) {}
        }

        return [
            'en' => 'English',
            'es' => 'Español',
            'pt' => 'Português',
            'fr' => 'Français',
            'de' => 'Deutsch',
            'it' => 'Italiano',
        ];
    }

    public function getAvailableLanguages(): array
    {
        $catalog = static::getSupportedLanguageCatalog();
        $configured = $this->supported_locales ?? ['en', 'es'];
        if (empty($configured)) {
            $configured = ['en', 'es'];
        }

        $result = [];
        foreach ($configured as $loc) {
            if (isset($catalog[$loc])) {
                $result[$loc] = $catalog[$loc];
            }
        }

        return !empty($result) ? $result : ['en' => 'English', 'es' => 'Español'];
    }

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
            if (!is_array($channelConfig) || empty($channelConfig['enabled'])) {
                continue;
            }

            // We explicitly target the known asset list keys defined by the driver schemas
            $assetKeys = ['sites', 'ad_accounts', 'pages', 'locations', 'profiles', 'accounts', 'shops', 'properties'];

            // 1. Check direct asset lists (e.g., $channelConfig['pages'])
            foreach ($assetKeys as $assetKey) {
                if (!empty($channelConfig[$assetKey]) && is_array($channelConfig[$assetKey])) {
                    foreach ($channelConfig[$assetKey] as $asset) {
                        if (is_array($asset) && !empty($asset['enabled']) && empty($asset['lost_access'])) {
                            return true;
                        }
                    }
                }
            }

            // 2. Check nested asset lists under an 'assets' wrapper (e.g., $channelConfig['assets']['ad_accounts'])
            if (!empty($channelConfig['assets']) && is_array($channelConfig['assets'])) {
                foreach ($assetKeys as $assetKey) {
                    if (!empty($channelConfig['assets'][$assetKey]) && is_array($channelConfig['assets'][$assetKey])) {
                        foreach ($channelConfig['assets'][$assetKey] as $asset) {
                            if (is_array($asset) && !empty($asset['enabled']) && empty($asset['lost_access'])) {
                                return true;
                            }
                        }
                    }
                }
            }
        }

        return false;
    }

    /**
     * Determine if a specific channel is actively connected (has valid credentials).
     */
    public function isChannelConnected(string $channel): bool
    {
        if (str_starts_with($channel, 'facebook_')) {
            return !empty($this->facebook_user_token);
        }
        if (str_starts_with($channel, 'google_')) {
            return !empty($this->google_refresh_token);
        }
        
        // Fallback for other providers (shopify, klaviyo, etc.) using ProjectCredential
        $provider = explode('_', $channel)[0];
        return $this->credentials()->where('provider', $provider)->whereNotNull('token')->exists();
    }

    /**
     * Count the number of enabled channels.
     */
    public function countEnabledChannels(bool $checkConnection = false): int
    {
        $count = 0;
        foreach ($this->sync_config ?? [] as $channel => $channelConfig) {
            if (is_array($channelConfig) && !empty($channelConfig['enabled'])) {
                if ($checkConnection && !$this->isChannelConnected((string) $channel)) {
                    continue;
                }
                $count++;
            }
        }
        return $count;
    }

    /**
     * Count the number of enabled assets.
     * @param bool $onlyInEnabledChannels If true, ignores assets in disabled channels.
     */
    public function countEnabledAssets(bool $onlyInEnabledChannels = false): int
    {
        return $this->countAssetsByCondition(function ($asset) {
            return !empty($asset['enabled']) && empty($asset['lost_access']);
        }, $onlyInEnabledChannels);
    }

    /**
     * Count the number of locked assets.
     */
    public function countLockedAssets(): int
    {
        return $this->countAssetsByCondition(function ($asset) {
            return !empty($asset['locked']);
        }, false);
    }

    /**
     * Count the number of assets in grace period.
     */
    public function countGracePeriodAssets(): int
    {
        return $this->countAssetsByCondition(function ($asset) {
            return !empty($asset['in_grace_period']);
        }, false);
    }

    /**
     * Helper to count assets based on a condition closure.
     */
    protected function countAssetsByCondition(\Closure $condition, bool $onlyInEnabledChannels = false): int
    {
        $count = 0;
        foreach ($this->sync_config ?? [] as $channelConfig) {
            if (!is_array($channelConfig)) {
                continue;
            }
            if ($onlyInEnabledChannels && empty($channelConfig['enabled'])) {
                continue;
            }

            $assetKeys = ['sites', 'ad_accounts', 'pages', 'locations', 'profiles', 'accounts', 'shops', 'properties'];

            // 1. Check direct asset lists
            foreach ($assetKeys as $assetKey) {
                if (!empty($channelConfig[$assetKey]) && is_array($channelConfig[$assetKey])) {
                    foreach ($channelConfig[$assetKey] as $asset) {
                        if (is_array($asset) && $condition($asset)) {
                            $count++;
                        }
                    }
                }
            }

            // 2. Check nested asset lists
            if (!empty($channelConfig['assets']) && is_array($channelConfig['assets'])) {
                foreach ($assetKeys as $assetKey) {
                    if (!empty($channelConfig['assets'][$assetKey]) && is_array($channelConfig['assets'][$assetKey])) {
                        foreach ($channelConfig['assets'][$assetKey] as $asset) {
                            if (is_array($asset) && $condition($asset)) {
                                $count++;
                            }
                        }
                    }
                }
            }
        }
        return $count;
    }

    /**
     * Determine if the project has ever been deployed.
     */
    public function hasBeenDeployed(): bool
    {
        return $this->last_deployed_at !== null || $this->subdomain === 'alpha';
    }
}

