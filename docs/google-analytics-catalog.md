# Google Analytics (GA4) analytics catalog

Audited 2026-10-30. Runtime: `ChannelCapabilityRegistry::getAnalyticsTree('google_analytics')`.
The tree uses raw cached metric names, with canonical aliases separately recorded.

## Planned expansion and current event caveat

The [scoped expansion plan](../../apis-hub/docs/google-analytics-expansion-plan.md)
extends the current purpose-driven matrices under the nine-dimension API limit.
It explicitly separates user/session/event/item measurement scopes, attribution,
grain and additivity. Do not sum overlapping recipes or assume displayed breakdown
rows must add up to a unique-user total. Catalog updates are required in every phase.

The updated driver discovers observed event names with `eventCount`, independently
of key-event designation, through paginated reports with metadata/compatibility
preflight. Raw names remain identities; marketing classification is enrichment.
The tenant resolves channeled events by property/account and resolves discovery
accounts by channel. Existing Explorer and custom-widget event breakdowns consume
these stored event identities; no separate custom-event metric is required.

This requires the updated SDK, driver and tenant; it is not a claim about already
installed releases or historical cache coverage. New syncs discover events in their
requested window. Historical missing/wrong links require a controlled audit and
replay; this increment does not migrate historical observations or launch a replay.
Registered custom parameters/dimensions/metrics are not automatically cached.

Driver report recipes now declare exact source dimensions, metric allowlists,
measurement context and attribution. The event recipe uses all nine dimension
slots, including date. Property recipe metadata reports source availability while
explicitly leaving compatibility unchecked and cache coverage unknown; it does not
yet provide a persisted per-property catalog or a new UI capability endpoint.
Offline fixtures cover discovery, conversion, pagination, cancellation and account
isolation. Authenticated source reconciliation and PostgreSQL/performance acceptance
remain rollout gates.

## Correctness implementation update

The accompanying GA4 contract v1 implementation removes revenue-as-cost, enforces
one scope, uses cached same-grain session weights for rates, composes Source / Medium
virtually, validates source fields and retains new response-quality metadata.
Explorer and KPI options now consume the catalog's scope metric lists. Acquisition
does not advertise revenue. User metrics remain **reported count sums**, with visible
notices; exact window uniques require a separate source-window cache. Unsupported
cost requests fail, and old pooled errors cannot masquerade as empty totals.

Historical observations do not require a dimension/signature rewrite. Missing weights
produce unavailable rates; KPI/raw widgets reject unavailable inputs rather than
coercing them to zero. Multi-property revenue needs matching known currencies;
old windows may need metadata replay or single-property reporting. Calendar grains
are supported by the new contract; rolling windows are currently rejected.
These changes require the matching SDK/driver/tenant deployment, not Facade alone.
See [rollout and remaining acceptance checks](../../apis-hub/docs/ga4-correctness-rollout.md).

Runtime `implementation` entries distinguish corrections from remaining semantic limits.

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
| conversions | Stable cached identity; source keyEvents/legacy conversions selected from property metadata | Sum within one compatible matrix |
| totalRevenue | Property currency; canonical revenue | Sum within one currency |
| bounceRate | Ratio | Session-weighted average, not sum or unweighted average |
| averageSessionDuration | Seconds | Session-weighted average |
| newUsers, activeUsers, totalUsers | Users | Source distinct count for the requested window; daily/slice sums cannot reproduce window uniqueness |

Canonical aliases: `reach → activeUsers`, `impressions → screenPageViews`,
`new_users → newUsers`, `revenue → totalRevenue`, `bounce_rate → bounceRate`,
`average_session_duration → averageSessionDuration`, `total_users → totalUsers`,
`event_count → eventCount`. These aliases must retain their GA4 meaning in cross-channel
widgets. **The former `spend → totalRevenue` mapping was removed (GA-01).**
Spend is unsupported and is not declared a valid cost capability.

Landing pages are correctly normalized from `landingPagePlusQueryString` to
`dimensions.landing_page`. Session source/medium is split into `dimensions.source`
and `dimensions.medium`; `dimensions.sessionSourceMedium` is now composed virtually
for grouping/filtering (GA-03), without adding a cached dimension. First-user source/medium is retained combined.
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


User-count explanations appear as click-toggle warning icons beside widget heading
actions, not permanent canvas content. They apply only to GA4 active/new/total user
metrics (including aliases and dependent inputs), not all widgets containing GA4.
Explorer uses the same tooltip in user metric labels/column headings and the chart
heading when a user metric is selected. Public/shared and fullscreen widgets use
the same heading component.
