# Mailchimp analytics catalog

Audited 2026-10-30, commerce integration updated 2026-11-06. Runtime: `ChannelCapabilityRegistry::getAnalyticsTree('mailchimp')`.
The cache derives metrics from recipient events and connected-store orders. This is
not a direct copy of Mailchimp's campaign report summary.

## Audience → campaign / store → daily event aggregates

| Canonical metric | Cached name | Definition / reduction |
|---|---|---|
| sends | sends | Count send records |
| opens | opens_total | Count open events, including repeats |
| opens_unique_daily | opens_unique_daily | Distinct recipients with an observed open per campaign and day, including proxy opens |
| opens_standard | opens_standard | Open events with false proxy flag; **not verified human-only** |
| opens_proxy | opens_proxy | Open events explicitly marked proxy |
| clicks | clicks_total | Count click events, including repeats |
| clicks_unique | clicks_unique | Distinct recipient identity **per day and slice** |
| bounces | bounces_total | Count bounce events |
| bounces_hard | bounces_hard | Hard bounce events |
| bounces_soft | bounces_soft | Soft bounce events |
| unsubscribes | unsubscribes | Unsubscribe records |
| orders | orders_count | Distinct eligible scoped order IDs per store/day/currency |
| revenue | revenue | Sum source totals from the same eligible orders per store/day/currency |
| open_rate | derived | opens_total / sends; event rate, not unique-recipient open rate |
| click_rate | derived | clicks_total / sends; event rate, not unique-recipient click rate |

Counts sum only over disjoint observations within a compatible scope. Unique clicks
cannot be summed across dates or URLs to reconstruct window/campaign unique people.
Current event rates can exceed 100%; labels should distinguish them from provider
recipient-based rates. No FX conversion or accounting-revenue equivalence is implied.

`campaign_engagement` is partitioned by campaign ID; URL slices are produced for
click metrics in addition to baseline campaign totals. Never combine both. Tabs are
campaigns, automations, audiences, URLs and connected stores; selecting an automation tab is not proof
that every historical automation activity is available through the current importer.
`store_conversions` is a separate order stream, partitioned by store. A configured
30-day attribution window does not establish actual provider campaign attribution.
These rule names are not stored as a generic `dimensions.scope` filter.

Filters: audience/account, campaign, campaign type, clicked URL and tenant-discovered
entity attributes. URLs apply to clicks, not sends, opens or arbitrary order metrics.
Current Explorer requests select one audience; audience discovery and source-selection
coverage should be checked before enabling multi-audience reporting.

The local driver adds `opens_unique_daily` through the existing campaign
pre-aggregation. One recipient opening repeatedly on a campaign-day counts
once. The same recipient can count again on a different day or campaign.
Data Preview shows the metric in a summary tile, the daily chart, and the
campaign, automation, and audience breakdowns. Tiles and table rows sum the
cached campaign-day counts. The chart sums campaigns for each day. Opens
have no URL attribution. Missing observations display as dashes or chart gaps. Widget Builder exposes daily unique opens in campaign engagement. Its coverage starts with windows
processed by the updated driver; old cached windows may have no observation.

## Gaps and expansion

- MC-01: event IDs omit clicked URL, collapsing distinct same-second URL clicks.
- MC-02: retain/query atomic identities for window distincts, or explicitly label
  daily distinct sums. Per-URL uniqueness also overlaps.
- MC-03: absence of proxy flags currently means false. The documented
  [subscriber activity response](https://mailchimp.com/developer/marketing/api/reports/email-activity/get-email-activity)
  does not establish that every activity includes such flags. Validate the bulk
  endpoint and its bot-filter support independently before changing the importer.
- MC-04: sends use list-member last_changed as a timestamp; campaign-date selection
  and missing local event end bounds can distort historical windows. Reconcile late
  events independently of campaign send dates.
- MC-05: store order fetches need incremental bounds and explicit attribution checks.
- MC-06: add unique opens and recipient-based rates using source aggregates or
  window-distinct identities, with separate bot/proxy/unknown classifications.

Evidence: Mailchimp driver `MailchimpDriver::{getCanonicalMetricDictionary,
getPreAggregationRules,syncMetrics,fetchAvailableAssets}`, `MailchimpConvert::{events,
sentToEvents,unsubscribeEvents}`; tenant `AgnosticPreAggregationEngine`; Facade
`MailchimpController`. See [audit](analytics-availability-audit.md) for validation and
backfill implications. Exact activity completeness still requires an authenticated
reconciliation against campaign report totals; this audit used no live credentials.

Connected Stores is available in Data Preview with order and revenue tiles,
a daily chart, and a table grouped by store and currency. The Widget Builder
offers orders and revenue under Connected store orders, with store, currency,
and time breakdowns. Audience access restrictions apply to every request.

Store metric identities contain audience, store ID, and currency using dimension
sets. Order IDs are scoped to the Mailchimp account and store before persistence.
Order dates use processed_at_foreign, falling back to a supplied created_at.
Orders missing a source date or audience linkage are excluded. Currency comes
from the order or its store. There is no USD default.

The expanded rule also requires a verified persisted store relationship, scoped
order identity and known numeric total. Missing totals remain unknown, while a
known zero remains eligible. All reported statuses participate. Revenue is a source
order-total sum and does not implement paid-order, net-revenue or refund policies.

Catalog commerce capabilities are read from the tenant before fetching records.
Upgraded tenants expose stores, orders, products, variants, promotions, promo codes
and optional cart snapshots. Commerce routes default to Mailchimp and retain the
authorized audience/Asset Group scope. Customer pages reuse the canonical customer
Catalog and expose only source memberships authorized for that audience scope.
Lifecycle timestamps describe cached milestones, without claiming a full event log.

Connected Stores resolves store names/domains from cached entities in batches of
at most 100 source IDs. Profile links retain the current Asset Group scope. Older
tenants can retain ID-based metric rows until entity capabilities are available.
No additional product, promotion or cart performance measures are advertised.

Revenue sums source order totals. Mixed-currency summaries and chart series
return null; tables retain separate currency rows. Currency filters allow a
single-currency series. Untyped legacy aggregates are excluded. Historical order
records must be refreshed with the updated driver before rebuilding.
See the tenant [store rollout](../../apis-hub/docs/mailchimp-store-rollout.md).
