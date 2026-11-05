<?php

namespace Database\Seeders;

use App\Domain\ChannelProfiles\ChannelProfileRegistry;
use App\Models\ApisHubRelease;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ApisHubReleaseV1190Seeder extends Seeder
{
    public function run(): void
    {
        $schema = [];
        foreach (app(ChannelProfileRegistry::class)->all() as $profile) {
            $schema[$profile->getChannelKey()] = $profile->getSchemaDefinition();
        }

        // v1.19.0 introduces read-only Catalog endpoints and metric signature validation fixes;
        // no destructive history rewrite or automatic replay is triggered by this release.
        $impact = Schema::hasColumn('apis_hub_releases', 'upgrade_impact') ? ['upgrade_impact' => []] : [];

        DB::transaction(function () use ($schema, $impact) {
            $previous = ApisHubRelease::where('version_tag', 'v1.18.4')->first()
                ?? ApisHubRelease::where('version_tag', 'v1.18.3')->first();

            $release = ApisHubRelease::updateOrCreate(
                ['version_tag' => 'v1.19.0'],
                [
                    'is_active' => true,
                    'is_default' => true,
                    'description' => 'Introduces the Catalog Read Foundation and Marketing Entity endpoints across advertising, organic, search, analytics, email, and customer channels, plus metric signature and channel catalog aggregation refinements.',
                    'changelog' => <<<'MARKDOWN'
## v1.19.0

- Catalog Read Foundation & Entity APIs: Adds read-only tenant endpoints (`/api/catalog/capabilities`, `/api/catalog/list`, `/api/catalog/profile`, `/api/catalog/activity`) powering Facade Catalog entity pages, Data Explorer deep links, and the cross-channel Marketing Overview.
- Supported Catalog entities: Supports `accounts`, `campaigns`, `ad-sets` (Meta Ads), `ad-groups` (Google Ads), `ads`, `pages`, `posts`, `queries` (scoped by property and `search_type`), `events` (GA4 & Mailchimp event definitions), and `customers` across `facebook_marketing`, `google_ads`, `facebook_organic`, `google_search_console`, `google_analytics`, `mailchimp`, and `facebook_leads`. Commerce `orders` and `products` remain deferred until production coverage is verified.
- Authorization & PII protection: Enforces project and Asset Group `authorized_scope` across every catalog list, profile, hierarchy link, metric series, and activity timeline. Customer profiles and unredacted recipient identities require explicit `can_view_pii` authorization.
- Metric signature & aggregation refinements: Ensures persisted entity relationships are resolved before generating `MetricConfig` signatures during sync, supports synthetic ROAS calculation for Google Ads, and aligns universal/channel aggregations with the updated channel metrics catalog.
- Dependencies: Google driver `^1.3.2`, Meta driver `^1.2.2`, Mailchimp driver `^1.1.2`, Amazon driver `^0.1.1`, driver core `^1.1.4`, Google SDK `^1.1.1`, and Facebook SDK `^1.2.0`.
- Upgrade behavior: Standard in-place tenant upgrade from `v1.18.4` with no destructive schema rewrite, automatic historical replay, or metrics wipe. Projects upgrading from earlier releases still execute their intervening version migrations.
MARKDOWN,
                    'supported_channels' => $previous?->supported_channels ?? array_keys($schema),
                    'config_schemas' => $previous?->config_schemas ?? $schema,
                    'upgrade_commands' => [],
                    ...$impact,
                ]
            );

            ApisHubRelease::where('id', '!=', $release->id)
                ->where('is_default', true)
                ->update(['is_default' => false]);
        });

        $this->command?->info('APIs Hub v1.19.0 registered as the default release.');
    }
}
