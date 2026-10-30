# Mailchimp analytics catalog

Audited 2026-10-30. Runtime: `ChannelCapabilityRegistry::getAnalyticsTree('mailchimp')`.
The cache derives metrics from recipient events and connected-store orders. This is
not a direct copy of Mailchimp's campaign report summary.

## Audience → campaign / store → daily event aggregates

| Canonical metric | Cached name | Definition / reduction |
|---|---|---|
| sends | sends | Count send records |
| opens | opens_total | Count open events, including repeats |
| opens_standard | opens_standard | Open events with false proxy flag; **not verified human-only** |
| opens_proxy | opens_proxy | Open events explicitly marked proxy |
| clicks | clicks_total | Count click events, including repeats |
| clicks_unique | clicks_unique | Distinct recipient identity **per day and slice** |
| bounces | bounces_total | Count bounce events |
| bounces_hard | bounces_hard | Hard bounce events |
| bounces_soft | bounces_soft | Soft bounce events |
| unsubscribes | unsubscribes | Unsubscribe records |
| orders | orders_count | Distinct order IDs per store/day |
| revenue | revenue | Sum order total_amount per store/day; store currency |
| open_rate | derived | opens_total / sends; event rate, not unique-recipient open rate |
| click_rate | derived | clicks_total / sends; event rate, not unique-recipient click rate |

Counts sum only over disjoint observations within a compatible scope. Unique clicks
cannot be summed across dates or URLs to reconstruct window/campaign unique people.
Current event rates can exceed 100%; labels should distinguish them from provider
recipient-based rates. No FX conversion or accounting-revenue equivalence is implied.

`campaign_engagement` is partitioned by campaign ID; URL slices are produced for
click metrics in addition to baseline campaign totals. Never combine both. Tabs are
campaigns, automations, audiences and URLs; selecting an automation tab is not proof
that every historical automation activity is available through the current importer.
`store_conversions` is a separate order stream, partitioned by store. A configured
30-day attribution window does not establish actual provider campaign attribution.
These rule names are not stored as a generic `dimensions.scope` filter.

Filters: audience/account, campaign, campaign type, clicked URL and tenant-discovered
entity attributes. URLs apply to clicks, not sends, opens or arbitrary order metrics.
Current Explorer requests select one audience; audience discovery and source-selection
coverage should be checked before enabling multi-audience reporting.

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
