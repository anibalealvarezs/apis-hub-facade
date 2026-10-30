<?php

namespace Database\Seeders;

use App\Domain\ChannelProfiles\ChannelProfileRegistry;
use App\Models\ApisHubRelease;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ApisHubReleaseV1184Seeder extends Seeder
{
    public function run(): void
    {
        $schema = [];
        foreach (app(ChannelProfileRegistry::class)->all() as $profile) {
            $schema[$profile->getChannelKey()] = $profile->getSchemaDefinition();
        }

        // This release has no large history migration; skipped earlier releases retain theirs.
        $impact = Schema::hasColumn('apis_hub_releases', 'upgrade_impact') ? ['upgrade_impact' => []] : [];
        DB::transaction(function () use ($schema, $impact) {
            $previous = ApisHubRelease::where('version_tag', 'v1.18.3')->first();
            $release = ApisHubRelease::updateOrCreate(
                ['version_tag' => 'v1.18.4'],
                [
                    'is_active' => true,
                    'is_default' => true,
                    'description' => 'Corrects GA4 reporting, validates compatible metrics and updates Google, Meta and Mailchimp drivers without an automatic history rewrite.',
                    'changelog' => <<<'MARKDOWN'
## v1.18.4

- GA4 correctness: Revenue is no longer interpreted as advertising spend. Metrics and breakdowns are restricted to their supported reporting scope.
- Rates and missing data: Bounce rate and average session duration use matching session counts as weights. Missing inputs remain unavailable instead of becoming false zeros; incompatible or incomplete reports fail clearly.
- User counts: User figures are explicitly reported sums of daily and breakdown counts, not unique people for the selected period. Exact arbitrary-window unique-user reporting is not introduced.
- Filters: Source / Medium grouping and filters work with existing cached dimensions without changing metric identities.
- Source compatibility: GA4 validates requested fields against property metadata, selects supported key-event names while preserving the existing cached conversions identity, and records quality/currency/timezone metadata on new observations. Failed matrix requests no longer report complete synchronization.
- Currency: Combined revenue across properties requires matching known currencies. Historical windows without currency metadata may require a targeted replay or single-property reporting.
- Meta Ads: Supports the baseline-only synchronization option without adding optional audience breakdowns.
- Dependencies: Google driver ^1.3.1, driver core ^1.1.4, Google SDK ^1.1.1, Meta driver ^1.2.1 and Mailchimp driver ^1.1.1. Facebook SDK remains ^1.2.0.
- Upgrade from v1.18.3: Checks required runtime capabilities. No schema rewrite, metric-signature recalculation, automatic resync or data wipe is performed. Missing historical weights/metadata can be repaired separately after reviewing the affected windows.
- Upgrades from earlier versions still execute their intervening migrations. In particular, projects upgrading from before v1.18.3 may still require substantial time for the Google Search Console history migration.

Schedule the upgrade outside synchronization hours and during low activity. Use the normal backup and durable upgrade process. Controlled source reconciliation and representative PostgreSQL performance checks remain rollout requirements; registration alone does not upgrade a tenant or schedule a replay.
MARKDOWN,
                    'supported_channels' => $previous?->supported_channels ?? array_keys($schema),
                    'config_schemas' => $previous?->config_schemas ?? $schema,
                    'upgrade_commands' => [],
                    ...$impact,
                ]
            );
            ApisHubRelease::where('id', '!=', $release->id)
                ->where('is_default', true)->update(['is_default' => false]);
        });
        $this->command?->info('APIs Hub v1.18.4 registered as the default release.');
    }
}
