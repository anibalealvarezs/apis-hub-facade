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
        'is_default',
        'description',
        'changelog',
        'supported_channels',
        'config_schemas',
        'upgrade_commands',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'supported_channels' => 'array',
        'config_schemas' => 'array',
        'upgrade_commands' => 'array',
    ];

    /**
     * Get the release configuration schemas with a graceful fallback to ChannelProfileRegistry
     * if the release's database record has missing or incomplete channel schemas.
     */
    public function getConfigSchemasAttribute($value): array
    {
        $schemas = is_string($value) ? json_decode($value, true) : $value;
        if (!is_array($schemas)) {
            $schemas = [];
        }

        try {
            $registry = app(\App\Domain\ChannelProfiles\ChannelProfileRegistry::class);
            foreach ($registry->all() as $profile) {
                $key = $profile->getChannelKey();
                if (empty($schemas[$key]['fields'])) {
                    $schemas[$key] = $profile->getSchemaDefinition();
                }
            }
        } catch (\Throwable $e) {
            // Safe fallback: keep raw schemas if registry is not bootable
        }

        return $schemas;
    }

    /**
     * Get the release supported channels with a graceful fallback to ChannelProfileRegistry
     * if the release's database record has no channels explicitly listed.
     */
    public function getSupportedChannelsAttribute($value): array
    {
        $channels = is_string($value) ? json_decode($value, true) : $value;
        if (is_array($channels) && !empty($channels)) {
            return $channels;
        }

        try {
            $registry = app(\App\Domain\ChannelProfiles\ChannelProfileRegistry::class);
            return array_keys($registry->all());
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Get the projects using this release.
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    /**
     * Get all releases that are newer than this one.
     */
    public function scopeNewerThan($query, string $versionTag)
    {
        $stripped = ltrim($versionTag, 'v');

        return $query->whereRaw("
            CASE
                WHEN version_tag LIKE 'v%' THEN SUBSTRING(version_tag, 2)
                ELSE version_tag
            END > ?
        ", [$stripped]);
    }

    /**
     * Compare two version tags (e.g. v1.13.3 vs v1.14.0).
     * Strips the leading 'v' and uses version_compare.
     */
    public static function isNewerThan(string $tagA, string $tagB): bool
    {
        return version_compare(ltrim($tagA, 'v'), ltrim($tagB, 'v'), '>');
    }

    /**
     * Get the available upgrades for a given version tag.
     */
    public static function availableUpgradesFor(string $versionTag): \Illuminate\Support\Collection
    {
        return static::where('is_active', true)
            ->get()
            ->filter(fn (ApisHubRelease $release) => static::isNewerThan($release->version_tag, $versionTag))
            ->values();
    }
}
