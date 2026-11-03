# GA4 expanded analytics surfaces

Data Explorer's Property reports workspace reads the tenant's property metadata,
shows registered custom fields and compiles bounded source reports. Choose exact
window users, a purpose-driven recipe, custom field combination, or cohort. Funnel
choices appear only when the tenant explicitly enables its validated alpha path.
Fields have source display names; units, property timezone/currency and quality flags
remain available with the result. Changing properties invalidates pending UI results.

Reports can be saved into an editable dashboard as a table, or as metric cards when
there are no breakdowns. Their definition is frozen. Private views recheck account
access, public dashboards use the saved widget authorization context, and source data
is queued/cached by the tenant instead of fetched from Google per visitor. Changed
field definitions, timezone or currency require a reviewed new saved revision.
Pending/failed responses never become successful browser cache entries; stale source
results remain separate. Source totals are not sums of potentially overlapping rows.

The common channel catalog now includes engagement_matrix, ecommerce_matrix and
product_matrix with their measurements, raw source breakdowns, revision1 filters and
units/reducers. Existing raw/KPI widget choices use these scope definitions. Data
Sources offers optional scheduled caching for these three recipes; enabling one does
not wipe old metrics. New recipe history must be scheduled separately as needed.

Standard daily user metrics remain reported sums with a heading tooltip. The new
whole-window user cards/tables use one source-window result, never the sum of daily
unique users. Session-weighted cached rates are grouped in the tenant, including
end-anchored trailing buckets; Facade must not rebucket those weighted averages.
Revenue uses property currency, with distinct transaction/item/refund measures.

## Deployment and acceptance

Deploy the updated Google SDK/driver and tenant scoped analytics worker/endpoints
before enabling this UI. Regenerate the release configuration schema for the new
Data Sources choices. Run the normal Facade asset build. No dependency pins, release
seeder, tag or production replay is changed here. The tenant must persist private
storage/analytics-reports and install its new master cron entry, or reports stay queued.

Local tests cover Explorer request/result/widget definitions, context invalidation,
source totals, user notices, static catalog consistency and weighted aggregation.
A real-property canary, PostgreSQL volume tests and alpha funnel validation remain
release acceptance work. See the tenant's docs/ga4-scoped-reports-rollout.md for the
budgets, operational limits, canary matrix and historical cache policy. Warehouse
analysis remains a separate connector, not an advertised capability of this cache.
