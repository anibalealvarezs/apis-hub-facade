# Google Analytics (GA4) analytics catalog

Audited 2026-10-30. Runtime: `ChannelCapabilityRegistry::getAnalyticsTree('google_analytics')`.
The tree uses raw cached metric names, with canonical aliases separately recorded.

## Property → matrix → entities / dimensions → metrics

| Scope | Default requested metrics | Cached breakdowns |
|---|---|---|
| account | screenPageViews, sessions, bounceRate, totalRevenue | Date only; **no scope dimension** |
| traffic_matrix | screenPageViews, sessions, bounceRate, averageSessionDuration, totalRevenue, conversions | Channel group, source, medium, landing page, campaign, ad group, country, device |
| event_matrix | eventCount, conversions | Event, page, channel group, source, medium, campaign/ad-group/manual content mappings |
| acquisition_matrix | newUsers, activeUsers, totalUsers | First-user channel group, first-user source/medium, campaign/ad-group/manual content mappings |
| ad_touchpoint_matrix | sessions, conversions, totalRevenue | Session campaign/ad-group/manual term/content mappings |

All matrices include date. Matrix requests with `all` execute all five datasets;
they overlap. Filter `dimensions.scope` to one matrix, or select unscoped baseline
records for account totals. Traffic attribution and first-user attribution answer
different questions; do not merge them to fill apparent gaps.

| Metric | Unit / meaning | Required reduction |
|---|---|---|
| screenPageViews | Page/screen views; canonical impressions alias | Sum within one compatible matrix |
| sessions | Sessions | Sum only within one compatible matrix/grain |
| eventCount | Event occurrences | Sum |
| conversions | Legacy requested source name | Sum; validate current property compatibility before changing to keyEvents |
| totalRevenue | Property currency; canonical revenue | Sum within one currency |
| bounceRate | Ratio | Session-weighted average, not sum or unweighted average |
| averageSessionDuration | Seconds | Session-weighted average |
| newUsers, activeUsers, totalUsers | Users | Source distinct count for the requested window; daily/slice sums cannot reproduce window uniqueness |

Canonical aliases: `reach → activeUsers`, `impressions → screenPageViews`,
`new_users → newUsers`, `revenue → totalRevenue`, `bounce_rate → bounceRate`,
`average_session_duration → averageSessionDuration`, `total_users → totalUsers`,
`event_count → eventCount`. These aliases must retain their GA4 meaning in cross-channel
widgets. **The current `spend → totalRevenue` dictionary mapping is a defect, GA-01.**
It is deliberately not declared a valid cost capability in this catalog.

Landing pages are correctly normalized from `landingPagePlusQueryString` to
`dimensions.landing_page`. Session source/medium is split into `dimensions.source`
and `dimensions.medium`; the existing UI `dimensions.sessionSourceMedium` path is
not emitted by the converter (GA-03). First-user source/medium is retained combined.
Do not infer missing revenue in acquisition means zero: that matrix does not request it.
Mapped campaign names/manual terms are not stable provider campaign/ad identifiers.

The source has many more metrics/dimensions than this cache. Proposed additions must
use per-property [metadata and compatibility checks](https://developers.google.com/analytics/devguides/reporting/data/v1/rest/v1beta/properties/checkCompatibility),
not a universal Cartesian product. The current
[source schema](https://developers.google.com/analytics/devguides/reporting/data/v1/api-schema)
uses key-event terminology and defines user/rate semantics. This audit did not make
authenticated property requests and does not claim legacy conversions fail everywhere.
Preserve [response quality metadata](https://developers.google.com/analytics/devguides/reporting/data/v1/rest/v1beta/ResponseMetaData)
when adding capabilities, including thresholding/sampling and currency/timezone context.

Filters: authorized property IDs, selected matrix, compatible dimensions/entities and
tenant-discovered stored entity attributes. Dates use the property's reporting context;
multi-property currency/timezone differences require explicit reconciliation.

Evidence: Google driver `GoogleAnalyticsDriver::{syncMetrics,getCanonicalMetricDictionary,
getMetricProfiles,getAggregationProfiles}`, `GoogleAnalyticsMetricConvert` matrix
methods and `preprocessRows`; Facade `GoogleAnalyticsController::metricsForScope`,
breakdown/granularity registries; tenant `CanonicalMetricSqlResolver`.
See [audit](analytics-availability-audit.md) for repair and migration proposals.
