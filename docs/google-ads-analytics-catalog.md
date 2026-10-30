# Google Ads analytics catalog

Audited 2026-10-30. Runtime: `ChannelCapabilityRegistry::getAnalyticsTree('google_ads')`.
This is an inventory of the current implementation, not an expansion of ingestion.
See the [audit and proposed changes](analytics-availability-audit.md).

## Customer → hierarchy scope → device → metric

| Scope | Entity / dependency | Cached grain | Selection |
|---|---|---|---|
| account | Customer | Date, device | Explicit account toggle or fallback when no lower level selected |
| campaign | Campaign / campaign_level | Date, campaign, device; campaign type converter field | Campaign toggle |
| ad_group | Ad group / adgroup_level | Date, ad group, device | Ad group toggle |
| ad | Ad / ad_level | Date, ad, device; campaign type converter field | Ad toggle |
| query | Search term / search_term_level | Date, query, device, search-term status | Opt-in search-term toggle |

These are overlapping hierarchy reports. Choose a hierarchy aggregation profile and
entity dependency; do not sum account, campaign, ad group, ad and query observations
together. Converter `level` context is not proof of a `dimensions.level` filter.
Search terms can have incomplete coverage and must not replace account totals.
Resource/segment/metric combinations require compatibility checks before expansion:
[Google Ads segmentation](https://developers.google.com/google-ads/api/docs/reporting/segmentation).

| Metric | Source / calculation | Cache availability | Required reduction |
|---|---|---|---|
| Spend | metrics.cost_micros / 1,000,000 | Default | Sum within one scope and currency |
| Impressions | metrics.impressions | Default | Sum |
| Clicks | metrics.clicks | Default | Sum |
| Conversions | metrics.conversions | Default; may be fractional | Sum |
| Conversion value / revenue | metrics.conversions_value | **Known omission**, ADS-01 | Sum |
| ROAS | Conversion value / spend | **Incorrect cached value**, ADS-01 | Ratio of sums |
| CTR | Clicks / impressions | Derived | Ratio of sums |
| CPC | Spend / clicks | Derived; average_cpc requested but not in default converter whitelist | Ratio of sums |
| CPM | 1,000 × spend / impressions | Derived | Ratio of sums |
| Cost per conversion | Spend / conversions | Derived; source value also metadata | Ratio of sums |
| Conversion rate | Conversions / clicks | Derived click-based definition | Ratio of sums; not necessarily provider interaction-based rate |
| Video views | metrics.video_views | Dictionary entry; **not default ingestion**, ADS-03 | Sum after implementation |

Zero denominators should produce an unavailable result, not an infinite value.
Account currency is retained; there is no implied FX conversion across accounts.
Revenue aliases refer to advertising conversion value, not accounting revenue.
`search_term` in the driver dictionary is a dimension, not a numeric measure.

Filters include authorized internal customer IDs, campaign, ad group, ad, device,
campaign type and query semantic classifications (intent, brand relation, business
relevance). Entity attribute filters use tenant-discovered stored fields, not arbitrary
provider JSON. Their presence does not add historical metric breakdowns.

Source evidence: Google driver `GoogleAdsDriver::{getCanonicalMetricDictionary,
getMetricProfiles,getAggregationProfiles,syncMetrics,resolveLevelsToFetch}` and
`GoogleAdsMetricConvert::{customerMetrics,campaignMetrics,adGroupMetrics,adMetrics,
searchTermMetrics,preprocessRows}`; Facade `GoogleAdsController`, breakdown and
granularity registries; tenant `CanonicalMetricSqlResolver`.

Expansion candidates: corrected conversion value/ROAS first; then explicit video,
interaction and conversion-action capabilities by resource. Advertise only metrics
that survive request selection, conversion, persistence and aggregation end to end.
