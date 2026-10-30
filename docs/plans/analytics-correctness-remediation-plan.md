# Progressive analytics correctness plan

Created: 2026-10-30. Status: GA4 corrections implemented locally; rollout pending.
Other channel remediation remains proposed.

Further GA4 coverage is planned in the [scoped analytics expansion plan](../../../apis-hub/docs/google-analytics-expansion-plan.md).
It retains purpose-driven scopes, explicitly accounts for the nine-dimension source
limit and makes catalog updates mandatory with every expansion increment.
Baseline: [four-channel availability audit](../analytics-availability-audit.md).

## GA4 execution update — 2026-10-30

GA4 implementation now covers the phase 0/1 cost regression and the GA4 portions of
phases 3/4: driver-owned query policy, same-grain weighted rates, explicit reported
user sums, scope/breakdown guards, virtual Source / Medium, metadata-selected key
events, compatibility preflight and retained quality metadata. Facade selectors,
widget errors/notices and result-cache versions are aligned. Failed matrix ingestion
no longer claims success. Multi-property monetary totals require matching known
currencies. Other channels have not been changed by this implementation.

Phase 5 operational acceptance remains pending: representative PostgreSQL performance,
controlled source reconciliation, dependency releases, tenant canary and any necessary
history replay. Exact window uniques and summarized source-quality UI badges remain
follow-up work; user figures are explicitly reported sums in this increment.
See [GA4 rollout](../../../apis-hub/docs/ga4-correctness-rollout.md). No production
upgrade, Composer pin update, database migration or source replay ran locally.

## Objectives and boundaries

Correct misleading numbers first, prevent further loss of information, then improve
coverage and expand capabilities. Keep Explorer, raw widgets, KPI widgets and public
dashboards consistent. Preserve GSC's standard/appearance isolation and Meta Ads'
optional no-breakdown behavior throughout.

The tenant aggregation engine should consume provider-neutral metric contracts:
units, scope/grain, reducer, dependencies, distinctness, coverage and supported
filters. Drivers declare source-specific meanings and compatibility. Facade uses
the effective contract to present choices and validate requests; it must not be the
only authority for semantics. Tenant validation must protect direct API consumers too.

Do not turn the current Facade inventory into a second independent ingestion schema.
Introduce a small versioned driver/core contract incrementally, and adapt existing
profiles before considering broader registry consolidation.

## Delivery order

| Phase | Outcome | Audit issues | Principal packages | Depends on |
|---|---|---|---|---|
| 0 | Reproducible baseline and migration safeguards | All | Affected drivers, apis-hub, Facade | Nothing |
| 1 | Correct cost, conversion value and ROAS | GA-01, ADS-01; ratio portion of ADS-02 | google-hub-driver, apis-hub, Facade | 0 |
| 2 | Stop event loss and distinguish missing from zero | MC-01, MC-03, MC-04, ORG-01 | mailchimp-hub-driver, facebook-graph-api, meta-hub-driver, api-driver-core, apis-hub | 0; rollout after 1 |
| 3 | Correct window, ratio, snapshot and attribution semantics | GA-02, MC-02, MC-05, MC-06, ORG-03, remaining ADS-02 | api-driver-core, apis-hub, affected drivers, Facade | 1; new-record coverage from 2 |
| 4 | Align filters, source names and capabilities across consumers | GA-03, GA-04, GA-05, ADS-03, ORG-02, ORG-04 | Drivers, relevant SDKs, apis-hub-api if needed, Facade | Stable contracts from 2–3 |
| 5 | Validate historical repairs and expand safely | Remaining coverage candidates | Relevant drivers, apis-hub, Facade | Each prerequisite repair |

Phases are release boundaries, not one large upgrade. Historical repair can accompany
each phase once safe; it must not wait for every channel to be finished. Preventing
new bad data takes precedence over completing old backfills.

## Phase 0 — Baseline and repair infrastructure

- Turn confirmed audit probes into regression fixtures in their owning packages.
  Cover camelCase/snake_case Ads responses, repeated people across days, Mailchimp
  same-second different-URL clicks, absent versus explicit-zero Meta metrics and
  repeated organic lifetime snapshots.
- Inventory affected cached names, configuration signatures, saved widgets/KPIs,
  tenant versions and source retention. Use counts/proxies to estimate repair volume;
  do not promise an exact completion time from configuration count alone.
- Define an idempotent repair pattern using the existing upgrade/rollback machinery:
  dry-run summary, bounded batches, durable checkpoints where compatible with the
  transaction strategy, progress stages, post-repair invariants and safe resume.
- Separate short schema/code upgrades from large historical recomputations where
  feasible. Define per repair whether affected reporting is unavailable, remains on
  the old version, or shows explicitly incomplete data until reconciliation finishes.

**Exit gate:** fixtures reproduce confirmed defects; each planned repair states its
source of truth, eligibility, duplicate policy, rollback boundary and validation query.
Test interruption/resume and rollback on a representative PostgreSQL copy before
using any repair on a large tenant.

## Phase 1 — Financial correctness

Deliver as two independently reviewable changes:

1. Remove GA4 revenue-as-spend resolution. Review channel capability tags, canonical
   dictionary, generic SQL resolution and saved KPI/widget sources. Invalid old
   configurations should surface an actionable unavailable metric, not silently
   become zero or continue reporting revenue as cost.
2. Normalize Ads conversion value consistently and derive ROAS as total conversion
   value divided by total spend. Review generic and hierarchy aggregation paths.
   Distinguish advertising conversion value from purchase-only revenue: do not label
   all conversion value as purchase ROAS unless the source is restricted accordingly.

Historical repair: determine whether retained source metadata is sufficient to rebuild
conversion value accurately at the original grain. Otherwise re-fetch eligible windows.
Do not blindly reinterpret every legacy ROAS row as conversion value. Remove/replace
affected derived rows consistently and invalidate report caches after validation.

**Exit gate:** spend=2 and conversion value=10 produce ROAS=5; unequal-spend days
produce ratio-of-sums; zero spend is unavailable; fractional conversions survive.
Revenue-only GA4 data never contributes to spend. Explorer, custom KPI/raw widgets
and public dashboards agree for the same scope and date range.

## Phase 2 — Preserve trustworthy source observations

1. Version Mailchimp event identity. Prefer a stable provider ID where actually
   available; otherwise include relevant event distinctions such as URL/type. Keep
   the unavoidable ambiguity of identical events explicit. Reconcile old/new IDs
   together so replay cannot double-count retained events.
2. Correct event windowing: independent event watermarks, bounded date intervals and
   late activity on old campaigns. Stop treating list-member last_changed as verified
   send time; select a source-supported timestamp/aggregate strategy and record its
   precision. Verify audience boundaries and pagination in the same importer tests.
3. Preserve proxy/bot classification as unknown when absent. Validate the bulk
   endpoint before promising source-equivalent human-only metrics.
4. Stop manufacturing Meta zeros. Represent measured zero, missing, unsupported and
   failed/partial passes distinctly. Define how failed re-fetches affect existing
   successful observations; they must not overwrite good data with synthetic zero.

Historical repair: re-fetch only where source retention and permissions allow. Existing
collapsed clicks and synthetic zeros may be unrecoverable without the source; expose
an explicit incomplete-history state instead of claiming complete reconstruction.

**Exit gate:** replay is idempotent; distinct URLs retain distinct events; late events
are captured once; absent flags stay unknown; failed Meta passes retain partial
coverage and do not masquerade as complete success.

## Phase 3 — Aggregation semantics

- Introduce the minimum shared metric-contract support needed for additive measures,
  ratios with explicit components, weighted averages, entity snapshots and distinct
  counts. Validate compatible scopes/grains and currency before aggregation.
- GA4: compute rates/duration using valid component totals. Check whether the cached
  weights and grain permit exact reconstruction. Daily distinct user aggregates
  cannot yield exact arbitrary-window users; choose source window queries/materialized
  supported windows or mark/reject unsupported combinations. Do not obtain user-level
  data simply to force distincts into the existing cache.
- Mailchimp: compute requested-window distinct recipients from eligible atomic events
  without exposing identities. Separate daily-distinct sums from window uniques.
  Distinguish event ratios from recipient-based rates. Keep store revenue separate
  from campaign attribution until attribution is demonstrated end to end.
- Organic: enforce latest snapshot per entity for lifetime measures, isolate daily
  flows and overlapping baseline/breakdown passes, and avoid presenting summed reach
  as exact unique people.
- Preserve division-by-zero, missing-data and mixed-currency behavior consistently
  across direct aggregation, KPI calculations and cross-channel widgets.

**Exit gate:** one recipient on two dates/two URLs remains one window-unique person;
unequal weights produce correct rates; two snapshots of one post do not double its
lifetime count; baseline plus slices cannot double-count; unrelated store orders do
not become campaign revenue. Unsupported exactness is visible and consistent.

## Phase 4 — Catalog-driven capabilities and UI alignment

1. Expose effective tenant capabilities with contract/version information. Merge static
   driver definitions with enabled ingestion settings and observed coverage, without
   confusing permission failures or an empty date window with permanent unsupportedness.
2. Align GA4 Source / Medium filters with existing split cached fields first. Preserve
   old saved-filter intent through a tested compatibility adapter; do not blindly
   reinterpret substring or exclusion operators. A combined cached dimension is
   optional and needs versioned grain reconciliation if introduced.
3. Validate GA4 metadata/compatibility and key-event naming, preserving versioned
   aliases and response quality context. Make Ads requested metrics pass explicit
   resource-specific request/conversion validation before exposing video or other extras.
4. Separate Organic reactions/likes and following definitions; verify watch-time units,
   media types, API versions and permissions through controlled source checks.
5. Move Explorer, widget builder, KPI dependencies and server validation onto the same
   contract incrementally. Keep friendly labels, tenant-discovered entity attributes,
   context-specific filters, debounce and request cleanup. Existing widgets need clear
   compatibility handling rather than silent semantic changes.

**Exit gate:** every selectable metric/dimension combination has an ingestion and
aggregation path, appropriate tenant-version support and a usable label. Negative
tests reject incompatible requests. Existing GSC and Meta Ads behavior stays covered.

## Phase 5 — Controlled expansion and operational acceptance

- Add capabilities individually after correctness gates pass: Ads video/interactions,
  GA4 additional compatible metrics, Mailchimp source-equivalent recipient metrics,
  and verified Organic media capabilities. Do not enable a dictionary entry alone.
- Compare representative source totals with tenant output under matching dates,
  timezone, currency, attribution, bot settings and scope. Document tolerances and
  provider-specific reasons for differences; do not use one universal tolerance.
- Exercise large-tenant repair duration and load. Retain four concurrent dashboard
  widget requests within shared tenant budgets; historical jobs and source-window
  queries must not bypass backpressure or overload normal dashboard reads.

**Exit gate:** agreed correctness and performance checks pass, repaired/unrecoverable
windows are recorded, progress is understandable and operators can safely stop or
resume repair work according to its documented transaction boundary.

## Release and rollout checklist for each increment

1. Review code, contract changes, compatibility adapters and synthetic regression tests.
2. Publish/pin dependencies in order: SDK/core → driver → tenant/API → Facade consumer.
   Do not advertise capabilities to tenants that lack the required implementation.
3. Test migration and recovery on a representative PostgreSQL copy; document backup,
   downgrade constraints, expected workload and cache invalidation.
4. Pilot on a controlled tenant during low activity and outside sync hours. Use the
   existing durable upgrade lifecycle, action locking, polling suppression and readable
   progress. Do not run uncontrolled source calls during an upgrade.
5. Verify API readiness, version/contract, data invariants, sample reports and dashboard
   latency before widening rollout. A completed process alone is not data acceptance.
6. Roll out by affected channel/data volume; monitor partial coverage, rejected queries,
   duplicate handling and repair progress. Pause expansion if correctness diverges.

Production rollout and historical repairs are separate execution steps from this plan.
Avoid a single all-channel migration or an automatic full resync as the default remedy.

## Immediate next implementation

Start Phase 0 fixtures for GA-01/ADS-01, then deliver Phase 1 financial corrections
and their historical repair design. In parallel planning, define the event-identity
and coverage contract for Phase 2 so new information loss can be stopped promptly.
No dates are assigned until tenant volume, retained evidence and provider access are
known. Track completion by the exit gates above, not by code merged alone.
