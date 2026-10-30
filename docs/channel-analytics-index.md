# Channel analytics reference index

Track source availability, implemented ingestion, cache identities, scope isolation,
metric semantics, breakdowns, filters, version requirements and consumers together.
Source-supported and currently cached capabilities must be distinguished.

- **Google Ads:** [Expansion and search-terms plan](plans/google_ads_expansion_and_search_terms_plan.md),
  existing ChannelCapabilityRegistry / ChannelBreakdownRegistry /
  ChannelGranularityRegistry entries, and the Google driver's aggregation profiles.
- **Google Search Console:** [Complete analytics tree](gsc-analytics-catalog.md);
  runtime `ChannelCapabilityRegistry::getAnalyticsTree('google_search_console')`.
  Includes all six search types and the independent standard/appearance scopes.
