# Analytics availability audit — 2026-10-30

Scope: Meta Organic, Google Ads, GA4 and Mailchimp, the four channels beyond the
previous GSC/Meta Ads work. Deliverables are reference catalogs plus a runtime
inventory reachable through `ChannelCapabilityRegistry::getAnalyticsTree`.
Existing GSC behavior is preserved. These new entries are metadata: Explorer/widget
pickers and tenant query execution are not switched over or repaired by this change.

## Method and confidence

Traced provider SDK calls → driver defaults → conversion whitelist → cached keys /
dimensions → tenant reducers → Facade controllers and selector registries. Three
offline converter probes used only synthetic accounts, IDs and example.test URLs.
No production sync, credentialed provider request, data migration or backfill ran.
Source/code observations below are distinct from source/API compatibility checks.
Official Google and Mailchimp references were checked; Meta reference access failed,
so exact current Meta version/media availability remains unverified.

Catalog statuses: `cached` means a default ingestion path emits the measure;
`conditional` means source/account/media-dependent; `preaggregated` means derived
from atomic records; `derived` is a formula; `source_only` is not reliably ingested;
`known_issue` is a confirmed unsafe/omitted capability. Required reducers describe
correct semantics, **not an assertion that all current engine paths implement them**.
Scope metric lists are applicable inventory, not an enablement whitelist.

## Priority findings and proposals

| ID | Priority | Finding / evidence | Proposed correction and acceptance check |
|---|---|---|---|
| GA-01 | P1 | `GoogleAnalyticsDriver::getCanonicalMetricDictionary` maps spend to totalRevenue. Revenue-only GA data can be resolved as cost by canonical aggregation. | Remove the cost alias; exclude GA4 from spend capabilities until a real cost source exists. A revenue-only fixture must never contribute to cross-channel spend. Review saved KPI/widget definitions before rollout. |
| ADS-01 | P1 | `GoogleAdsMetricConvert::preprocessRows` assigns conversion value to roas_purchase, but emits plural conversions_value while the default whitelist requests singular conversion_value. The offline default converter emits false ROAS and no conversion_value. Generic ROAS AVG does not fix it. | Normalize conversion value consistently; derive ROAS from value/spend totals, not cached ratios. Test REST camelCase and snake_case, zero spend and unequal daily spend. Rebuild affected stored metrics; changing future ingestion alone leaves history wrong. |
| MC-01 | P1 | `MailchimpConvert::events` hashes campaign, recipient, action and timestamp, omitting URL. Two different URLs at the same instant get the same platform ID. | Use provider event ID where available or a versioned stable identity including URL/type. Replay/reconcile historical events and rebuild aggregates without retaining both old and new copies. Exact repeated identical events may still be indistinguishable without source IDs. |
| GA-02 | P1 | GA user counts are daily/slice distincts; generic SUM cannot deduplicate users over a window. Rate/duration fields also encounter generic SUM or AVG paths. | Define supported reducer per metric/profile; use source window aggregates for exact distincts, session-weighted rates and durations. Where the cache cannot reproduce source semantics, label approximation or reject that combination. Test repeated users across days/slices and unequal session volumes. |
| MC-02 | P1 | `AgnosticPreAggregationEngine` computes count_distinct by day/scope and optional URL; canonical clicks_unique uses SUM. | Compute requested-window distincts over atomic identities, or expose a separate explicitly daily-distinct-sum measure. Test one recipient on two dates/two URLs: campaign window unique must remain one. |
| ORG-01 | P1 | SDK `getDailyInstagramAccountTotalValueInsights` inserts zero for missing requested metrics; driver optional-pass failures can warn and continue. | Persist supported/missing/failed/zero coverage states; do not manufacture observations. Test absent metric, explicit zero, failed pass and partial response separately. Historic fabricated zeros cannot be safely identified from value alone; re-fetch affected windows if source retention permits. |
| GA-03 | P2 | Traffic/event converters persist source + medium, while `ChannelBreakdownRegistry` offers dimensions.sessionSourceMedium. Synthetic conversion confirmed split dimensions; landing_page works. | Select source/medium separately or version a combined dimension and reconcile existing grain. Test filters from Explorer, raw widgets and KPI sources against cached rows. Avoid naive replay that adds overlapping configurations. |
| ADS-03 | P2 | `GoogleAdsDriver::syncMetrics` does not pass requested metrics to the converter whitelist. A dictionary video_views entry therefore overstates availability. | Introduce explicit resource-specific request/conversion capabilities. Test a requested non-default metric through persistence and reporting, not just dictionary lookup. |
| ORG-02 | P2 | Likes includes all reactions; following and engagement aliases conflate distinct definitions. | Separate likes/reactions and gross/net following, retaining source meanings. Version canonical aliases and review saved widgets before changing historic interpretation. |
| ORG-03 | P2 | Organic lifetime post snapshots, daily flows, unique reach and baseline + breakdown slices have different reducers. Existing snapshot profiles help but are not proof every generic widget path uses them. | Enforce one compatible surface/grain at query validation and test latest-per-post, repeated snapshots, mixed posts and overlapping slices. Do not sum reach across posts/days as exact people. |
| MC-03 | P2 | Missing proxy flags become false in converter. Current activity path does not prove human-only classification. | Preserve unknown; validate bulk source bot/proxy fields/filter support, separate raw and filtered metrics. Compare with source report totals under identical bot settings. |
| MC-04 | P2 | sentToEvents uses member last_changed; importer selects campaigns by campaign dates and lacks a local activity end-date bound. | Use actual send timing/evidence and independent incremental event watermarks; bound both ends and reconcile late activity. Test an old campaign with a new click/unsubscribe. |
| MC-06 | P2 | `MailchimpController` computes open/click rates from total events divided by sends. Repeated opens/clicks can exceed 100%. | Relabel as event ratios and add unique-recipient source-compatible rates; do not silently reinterpret existing metrics. |
| ADS-02 | P2 | Ratio aggregation and click-based conversion-rate semantics can diverge from source interaction-based rates. | Declare numerator/denominator and unit per metric; retain interactions where required. Test weighted multi-day ratios and fractional conversions. |
| GA-04 / GA-05 | P2 | Legacy conversions requests and dropped quality metadata lack per-property compatibility/quality guarantees. | Check metadata/compatibility, migrate key-event naming with explicit versioned aliases, retain thresholding/sampling/currency/timezone context. Do not treat unsupported as measured zero. |
| MC-05 | P2 | Store orders are separate from campaign activity; configured attribution lookback does not prove attributed revenue. All-order fetches need incremental bounds. | Validate attribution end to end, scope revenue by store/currency, persist incremental watermarks. Test unrelated orders cannot inflate a campaign widget. |
| ORG-04 | P2 | Current live Meta metric/media/permission availability and watch-time units were not verified. | Maintain a pinned-version capability matrix and controlled fixtures/live reconciliation before adding UI choices. Local two-year request clamp is not a provider retention guarantee. |

P1 means incorrect or missing reported data with a demonstrated code path; P2 includes
scope-dependent correctness and coverage gaps. No user-specific dashboard result was
verified. Fixing these is proposed follow-up implementation, not part of this inventory.

## Reproducible offline observations

Google default customer converter input:

```json
{"customer":{"id":"123"},"segments":{"date":"2026-10-01","device":"MOBILE"},"metrics":{"costMicros":"2000000","conversionsValue":"10","conversions":"1","clicks":"4","impressions":"100"}}
```

`GoogleAdsMetricConvert::customerMetrics` emits spend=2, impressions=100, clicks=4,
conversions=1, roas_purchase=10, **no conversion_value**. Correct ROAS for these
inputs is 5. This demonstrates the converter defect, not every possible UI result.

GA traffic converter input: date=20261001, sessionSourceMedium=`google / organic`,
landingPagePlusQueryString=`/home?q=1`, sessions=2. Output dimensions:
landing_page=/home?q=1, medium=organic, scope=traffic_matrix, source=google.
No sessionSourceMedium dimension was emitted. Landing-page caching is implemented.

Mailchimp events: synthetic recipient, same campaign, action=click,
timestamp=2026-10-01T12:00:00Z, URLs `https://example.test/a` and
`https://example.test/b`. Both convert to platform ID
`cd6bda4f1ae7b7aae33564d6341b454c`; both have is_proxy=false when flags are absent.

## Delivery sequence

1. Repair GA cost aliases and Ads conversion value/ROAS with historical reconciliation.
2. Repair event identity and window distinct semantics, then affected Mailchimp history.
3. Add coverage/quality states and rate/snapshot policies; expose partial data honestly.
4. Align selectors with cached dimensions and adopt the catalog incrementally in
   Explorer/widgets. The runtime inventory currently does not replace their registries.
5. Expand metrics through source-specific compatibility checks, volume budgets and
   tests across request → conversion → persistence → aggregate → UI. Document any
   versioned backfill and do not run upgrades during heavy sync/activity periods.

Further validation candidates: Mailchimp asset-list pagination beyond the first
100 results and enforcement of selected audience boundaries; GA name-based entity
collisions/manual-term mappings; Ads resource-specific campaign-type coverage.
These require targeted tests before being described as confirmed reporting defects.

## Primary source references

- [Google Ads segmentation](https://developers.google.com/google-ads/api/docs/reporting/segmentation): resource/metric/segment compatibility and report growth.
- [GA4 schema](https://developers.google.com/analytics/devguides/reporting/data/v1/api-schema), [compatibility](https://developers.google.com/analytics/devguides/reporting/data/v1/rest/v1beta/properties/checkCompatibility), [response metadata](https://developers.google.com/analytics/devguides/reporting/data/v1/rest/v1beta/ResponseMetaData).
- [Mailchimp subscriber email activity](https://mailchimp.com/developer/marketing/api/reports/email-activity/get-email-activity): activity shape and source filtering. This is the single-subscriber reference; bulk endpoint completeness must be tested separately.
- [Instagram user insights](https://developers.facebook.com/docs/instagram-platform/reference/instagram-user/insights/) and [media insights](https://developers.facebook.com/docs/instagram-platform/reference/instagram-media/insights/): references only; access failed during this audit, so no current deprecation claim is made.
