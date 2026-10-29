<?php

namespace Database\Seeders;

use App\Domain\ChannelProfiles\ChannelProfileRegistry;
use App\Models\ApisHubRelease;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ApisHubReleaseV1183Seeder extends Seeder
{
    public function run(): void
    {
        $schema = [];
        foreach (app(ChannelProfileRegistry::class)->all() as $profile) {
            $schema[$profile->getChannelKey()] = $profile->getSchemaDefinition();
        }

        DB::transaction(function () use ($schema) {
            $previous = ApisHubRelease::where('version_tag', 'v1.18.2')->first();
            $release = ApisHubRelease::updateOrCreate(
                ['version_tag' => 'v1.18.3'],
                [
                    'is_active' => true,
                    'is_default' => true,
                    'description' => 'Expands Google Search Console synchronization to all six search types and safely labels existing cached data as Web.',
                    'changelog' => <<<'MARKDOWN'
## v1.18.3

- Google Search Console: Synchronizes Web, Image, Video, News, Discover, and Google News separately. The optional `search_types` configuration limits synchronization to selected types.
- Metric identity: Stores `search_type` as a dimension so each type has distinct configuration signatures and metric hashes. Existing reports continue to default to Web.
- Feed compatibility: Discover and Google News collect page/country clicks, impressions, and CTR without fabricating unsupported query, device, or position data.
- Tenant upgrade: Labels existing untyped GSC metrics as `web` and atomically updates configuration signatures, metric dimension hashes, and channeled dimension links. Duplicate identities follow the existing signature-recalculation policy without summing observations; unique dates and channel links are preserved. Other channels and non-colliding typed records are unchanged.
- Historical coverage: Additional search types require a GSC-only historical replay. Use `app:nuclear-resync --channel=google_search_console` without `--wipe-metrics` during rollout with workers stopped; no global resync is required.
- Dependencies: Updates `anibalealvarezs/google-hub-driver` to `^1.3.0`.

Upgrade with synchronization workers stopped and a pre-upgrade database backup. The search-type identity migration is forward-only; downgrading requires restoring that backup.
MARKDOWN,
                    'supported_channels' => $previous?->supported_channels ?? array_keys($schema),
                    'config_schemas' => $previous?->config_schemas ?? $schema,
                    // Tenant version migrations run through the standard upgrade process.
                    // Registration must not trigger a resync or dispatch tenant upgrades.
                    'upgrade_commands' => [],
                ]
            );

            ApisHubRelease::where('id', '!=', $release->id)
                ->where('is_default', true)->update(['is_default' => false]);
        });

        $this->command?->info('APIs Hub v1.18.3 registered as the default release.');
    }
}
