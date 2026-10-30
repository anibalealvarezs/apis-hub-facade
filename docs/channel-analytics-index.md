# Channel analytics reference index

Track source availability, implemented ingestion, cache identities, scope isolation,
metric semantics, breakdowns, filters, version requirements and consumers together.
Source-supported and currently cached capabilities must be distinguished.

- **Google Ads:** [Analytics catalog](google-ads-analytics-catalog.md), plus the
  [expansion and search-terms plan](plans/google_ads_expansion_and_search_terms_plan.md).
- **Google Search Console:** [Complete analytics tree](gsc-analytics-catalog.md);
  runtime `ChannelCapabilityRegistry::getAnalyticsTree('google_search_console')`.
  Includes all six search types and the independent standard/appearance scopes.
- **Meta Organic:** [Analytics catalog](meta-organic-analytics-catalog.md), runtime
  `getAnalyticsTree('facebook_organic')` (alias `meta_organic`).
- **Google Analytics:** [GA4 catalog](google-analytics-catalog.md), runtime
  `getAnalyticsTree('google_analytics')` (alias `google_analytics_4`).
- **Mailchimp:** [Analytics catalog](mailchimp-analytics-catalog.md), runtime
  `getAnalyticsTree('mailchimp')`.
- **Meta Ads:** existing [driver surface manifest](../../meta-hub-driver/docs/surface-manifest.md),
  breakdown options and Facade registries. No new Meta Ads runtime tree is introduced here.

The four new trees are maintained in `ChannelAnalyticsCatalog`, exposed by
`ChannelCapabilityRegistry`, and distinguish cached, derived, conditional,
source-only and known-issue metrics. They document required reducers and scope
isolation; catalog membership does not itself expand ingestion. GA4 scope metrics now
feed its Explorer/controller and KPI choices; the other three new trees remain an
inventory. Driver query contracts remain authoritative for tenant execution.
Temporal granularities reuse the existing registry. Entity attributes remain tenant
metadata, so no provider-specific attribute list is hardcoded into aggregation.

See the [availability audit and prioritized proposals](analytics-availability-audit.md)
for confirmed converter findings, source verification limits, repair tests and backfills.

Implementation sequence: [Progressive analytics correctness plan](plans/analytics-correctness-remediation-plan.md),
with release boundaries, acceptance gates and historical repair safeguards.

GA4 coverage expansion: [Scoped analytics expansion plan](../../apis-hub/docs/google-analytics-expansion-plan.md).
Extends purpose-driven caching under the nine-dimension limit, with measurement-scope
and additivity rules to avoid duplicated totals. Every implementation phase must update
the GA4 catalog and consumer capabilities together.
