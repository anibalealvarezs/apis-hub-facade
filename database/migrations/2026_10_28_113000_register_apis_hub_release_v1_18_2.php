<?php

use App\Domain\ChannelProfiles\ChannelProfileRegistry;
use App\Models\ApisHubRelease;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $registry = app(ChannelProfileRegistry::class);
        $schema = [];
        foreach ($registry->all() as $profile) {
            $schema[$profile->getChannelKey()] = $profile->getSchemaDefinition();
        }
        $supportedChannels = array_keys($schema);

        $rel181 = ApisHubRelease::where('version_tag', 'v1.18.1')->first();

        $release = ApisHubRelease::updateOrCreate(
            ['version_tag' => 'v1.18.2'],
            [
                'is_active' => true,
                'is_default' => true,
                'description' => 'Modernizes Meta Ads with configurable breakdown passes, expanded conversion metrics, and optional metrics wipe on nuclear resync.',
                'changelog' => "## v1.18.2\n\n- Meta Ads Modernization: Configurable multi-pass breakdowns, top-level null breakdowns, and expanded conversion metrics.\n- Nuclear Resync: Added optional `--wipe-metrics` flag to purge historical metrics for targeted channels before resynchronizing.\n- Dependencies: Updated `anibalealvarezs/meta-hub-driver` to `^1.2.0` and `anibalealvarezs/facebook-graph-api` to `^1.2.0`.",
                'supported_channels' => $rel181?->supported_channels ?? $supportedChannels,
                'config_schemas' => $rel181?->config_schemas ?? $schema,
                'upgrade_commands' => [],
            ]
        );

        // Unset previous defaults so v1.18.2 is the single default release
        ApisHubRelease::where('id', '!=', $release->id)->where('is_default', true)->update(['is_default' => false]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        ApisHubRelease::where('version_tag', 'v1.18.2')->delete();

        // Restore v1.18.1 as default if present
        $rel181 = ApisHubRelease::where('version_tag', 'v1.18.1')->first();
        if ($rel181) {
            $rel181->update(['is_default' => true]);
        }
    }
};
