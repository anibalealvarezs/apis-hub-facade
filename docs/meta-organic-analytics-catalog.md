# Meta Organic analytics catalog

Audited 2026-10-30. Runtime: `ChannelCapabilityRegistry::getAnalyticsTree('facebook_organic')`
(alias `meta_organic`). This covers Facebook Pages and Instagram professional-account
analytics. Conditional dictionary support is not proof that every account/media/API
version returns a metric. No authenticated Meta coverage test was performed.

## Account → surface → reporting period → metric

| Surface | Cached behavior | Conditional metric families |
|---|---|---|
| facebook_page | Daily Page insights; linked-page flow profiles | Reactions mapped to likes, comments, reach, views, page views, video views, follows, interactions |
| instagram_account | Five daily total_value passes | Reach, views, content views, likes, comments, saves, shares, interactions, profile views/link taps/website clicks, follows/unfollows, engaged accounts, replies |
| fb_post | Post/media snapshots; some `_daily` series | Reactions, comments, reach/views, clicks, video views/watch time, interactions, shares |
| ig_post | Media-dependent lifetime snapshots | Likes, comments, reach/views/plays, saves, shares, replies, interactions, Reels watch time, profile activity/visits, reposts, follows |

These are conditional families, not a guarantee of every row in every surface. API
permissions, media type and version govern availability. The current SDK enums and
capability helpers remain the concrete request source. Provider deprecation dates
were not verified: official Meta reference pages were unavailable during this audit.

The runtime catalog indexes all **24 canonical keys** and their dictionary aliases:

| Family | Canonical metrics | Reduction |
|---|---|---|
| Interaction | likes, comments, saves, shares, total_interactions, replies, post_clicks, reposts | Sum daily disjoint flows; latest per post for lifetime snapshots |
| Exposure | reach, views, content_views, page_views_total, video_views, accounts_engaged | Views/counts can sum within compatible daily grain; reach/engaged accounts require distinct-window semantics |
| Profile | profile_views, website_clicks, profile_links_taps, profile_activity, profile_visits | Daily sum or latest snapshot according to surface |
| Following | follows_and_unfollows, follows | Preserve follow/unfollow direction; gross activity is not net audience growth |
| Watch time | ig_reels_avg_watch_time, post_video_avg_time_watched, ig_reels_video_view_total_time | Weighted averages for averages; sum totals; verify source units before formatting |

`likes` includes reaction-type totals, not just the LIKE reaction (ORG-02). Different
engagement aliases can have different source definitions; avoid presenting one generic
cross-provider total without explaining its meaning. Watch-time units are deliberately
marked `source_time_unit` until verified for the pinned API version.

## Dimensions and filters

Instagram account passes request media-product type, follow type and contact-button
type depending on metric family; Facebook reaction insights expose reaction type.
Baseline totals and breakdown slices overlap. Choose one representation, never sum
baseline plus slices. Not all metrics support every breakdown.

Entity views are account/page and post/media. Use the dependency and existing
`facebook_organic_page_flow`, `facebook_organic_post_snapshot`, or linked-page
aggregation profile; no universal `dimensions.scope` field is asserted. Attribute
filters inspect stored page/post/account fields discovered from the tenant.

Lifetime values fetched on successive days must use latest snapshot **per entity**;
they are not daily increments. Reach is also not additive across posts or dates.
The driver clamps Instagram account history to roughly two years; this is a local
request policy, not a promise of provider retention.

## Coverage caveats

- ORG-01: the SDK fabricates zero for requested Instagram metrics missing from a
  response; some optional pass errors only warn. Missing/unsupported/partial and
  measured zero therefore cannot reliably be distinguished.
- ORG-02: reaction, following and interaction aliases need semantic separation.
- ORG-03: snapshot, distinct-user and overlapping-breakdown rules must survive all
  widget/Explorer aggregation paths.
- ORG-04: maintain a version/media/permission capability matrix and explicit units;
  validate using synthetic fixtures plus a controlled source check before rollout.

Evidence: Meta driver `FacebookOrganicDriver::{getCanonicalMetricDictionary,
getAggregationProfiles,syncMetrics}`; `FacebookOrganicMetricConvert` account/post
converters; Facebook SDK `FacebookGraphApi::getDailyInstagramAccountTotalValueInsights`,
organic metric enums and media capability helpers; Facade organic registries.
See the [prioritized audit](analytics-availability-audit.md).
