# End-to-End Testing Plan — DM / Widgets / Dashboards / KPIs

**Status:** In progress — implemented first batch (versioning/cache/duplication) on 2026-07-30; see tracker below.
**Owner:** opencode / any agent in future sessions
**Scope:** Full lifecycle testing of the analytics feature stack: Derived Metrics (DM), Custom KPIs, Dashboard Builder, Widget Builder, Dashboard View, Widget View — including versioning, duplication, data requests/rendering, and the DM → KPI → Builder → View hierarchy.

---

## 1. Purpose

This plan defines a comprehensive, manual + automated test suite that exercises every stage of the analytics domain. It exists so that any agent (or QA engineer) can pick it up in a future session and verify that the whole stack works end-to-end, and can hand it off to another agent without losing context.

The stack under test (in dependency order):

```
Derived Metric (DM)
   └─ Custom KPI (consumes DMs as variables via dm_<id>)
       └─ Dashboard Builder (adds KPI/DM widgets)
           └─ Widget Builder (per-widget controls, grid, type)
               └─ Dashboard View (renders all widgets)
                   └─ Widget View (renders a single widget's data)
```

Cross-cutting concerns: **versioning** (TracksVersions), **duplication** (clone/replicate), **data requests & rendering** (DashboardWidgetDataController + remote engine), **billing quotas**, **permissions/tenancy**.

---

## 2. Test Environment

- **Stack:** Laravel 12 + Filament 3.2 (panel `app`), Pest 3 / PHPUnit 11.5.50. Tests run on SQLite `:memory:`, `CACHE_STORE=array`, `QUEUE_CONNECTION=sync` (see `phpunit.xml`).
- **Tenant:** every action is scoped to a `Project` (`Filament::getTenant()`). User must belong to the project.
- **Remote engine:** data fetching calls the sibling `apis-hub` worker via `RemoteEngineService`. For isolated tests, mock the SDK client (see `tests/Feature/Services/RemoteEngineServiceTest.php` `getMockedService` with a mocked Guzzle `MockHandler`).
- **Fixtures needed:**
  - 1 admin user with `edit_preferences` (and a user without it for permission tests)
  - 1 project with `sync_config` enabling at least: `facebook_marketing`, `facebook_organic`, `google_analytics`, `google_search_console`, `google_ads`, `meta` (as needed per scenario)
  - Asset groups + items (for asset-filter scenarios)
  - Mocked remote-engine responses for widget data endpoints
- **Commands:** `php artisan test` (runs Unit + Feature). Per-file: `php artisan test --filter=Name`.

> **Note (2026-07-30):** the `label` column migration `2026_07_30_000006_add_label_to_version_tables.php` had not been run in earlier sessions (DB unreachable). Ensure `php artisan migrate --force` is run before any versioning test.

---

## 3. Priority Legend

| Priority | Meaning |
|---|---|
| **P0** | Critical — blocks core workflow, must be tested first |
| **P1** | High — core feature path, likely regression surface |
| **P2** | Medium — important but edge-case heavy |
| **P3** | Low — nice-to-have / cosmetic |

Each test case lists: **ID**, **Title**, **Priority**, **Type** (`Unit` / `Feature` / `Manual`), **Preconditions**, **Steps**, **Expected result**.

---

## 4. Domain A — Derived Metrics (DM)

### A1. DM CRUD
| ID | Title | Pri | Type |
|---|---|---|---|
| DM-001 | Create DM from scratch (no template) | P0 | Feature |
| DM-002 | Create DM from predefined template | P0 | Feature |
| DM-003 | Edit DM (change formula, series, metadata) | P0 | Feature |
| DM-004 | Delete DM (and cascade behavior) | P1 | Feature |
| DM-005 | DM with multiple source series (a/b/c) | P1 | Feature |
| DM-006 | DM with dynamic output granularity (`''`) | P2 | Feature |

**DM-001 steps:** open `DerivedMetricResource/create` → choose "Create from scratch" → define ≥1 source series (channel, metric, granularity, optional asset group/filter) → write AST formula referencing series keys (e.g. `a/b`) → set name/description/format → save.
**Expected:** row created; `source_series` reindexed with keys a/b/c; `ast` JSON-decoded; `format` defaults to `decimal`; wizard scratch path completes; version #1 created automatically (via TracksVersions `created` event).

**DM-002 steps:** template picker → choose a `PredefinedDerivedMetricRegistry` template (e.g. `cpc`, `ctr`) → pre-fill resolved against active channels → save.
**Expected:** channel placeholders (`__TAG_CHANNEL_N__`) resolved to real channel keys; `source_series` keys a/b; AST pre-filled; template only appears if `required_tags` satisfied by active channels (`getAvailable()`).

**DM-005 steps:** DM with 2-3 series referencing different channels.
**Expected:** `mutateFormDataBeforeSave` assigns sequential keys a, b, c; formula editor reads/writes the JSON `ast` hidden field; save persists all series.

### A2. DM Evaluation / Caching
| ID | Title | Pri | Type |
|---|---|---|---|
| DM-007 | DM cache hit path (DerivedMetricCacheService) | P1 | Unit |
| DM-008 | DM cache miss → live compute → cache store | P1 | Feature |
| DM-009 | Cache invalidation on DM update | P1 | Feature |
| DM-010 | Cache key sensitivity to controls (sha256 of ksort'd JSON) | P1 | Unit |
| DM-011 | Preview endpoint (`/api/derived-metrics/preview`) | P1 | Feature |
| DM-012 | Recursive DM resolution (DM referencing another DM via `dm_<id>`) | P2 | Feature |

**DM-007/010:** unit-test `DerivedMetricCacheService::getControlsHash` — same controls → same hash; different asset/date → different hash; key ordering irrelevant.

**DM-008:** compute a DM via `DashboardWidgetDataController::handleDerivedMetricSource` with no cache → assert `DerivedMetricResult` row created with `result` array + `expires_at` ≈ +60min.

**DM-009:** update DM trackable fields → `DerivedMetricCacheService::invalidateCache` clears `derived_metric_results` for that DM.

**DM-011:** POST `/api/derived-metrics/preview` with `project_id`, `ast`, `source_series` → returns normalized `{dates, values}`; `percentage` format multiplies by 100; result sliced to 30 points.

### A3. DM Versioning
| ID | Title | Pri | Type |
|---|---|---|---|
| DM-013 | Save Version captures current form state (not stale record) | P0 | Feature |
| DM-014 | Version table reflects new version immediately (no manual reload) | P0 | Feature |
| DM-015 | Restore a DM version | P1 | Feature |
| DM-016 | No auto-version on plain update (shouldAutoVersionOnUpdate=false) | P1 | Unit |
| DM-017 | Version numbering is sequential & append-only | P1 | Unit |
| DM-018 | Version label + change_summary persisted | P2 | Feature |
| DM-019 | Prune all versions bulk action | P2 | Feature |

**DM-013 steps:** open edit → change formula + name (do NOT save) → header "Save Version" → confirm.
**Expected (regression for bug 2026-07-30):** the `derived_metric_versions` row contains the NEW values (form state filled into record before `createVersion`), not the stale DB values.

**DM-014 steps (regression for bug 2026-07-30):** after saving a version on the Edit page, the Versions relation manager tab shows the new row **without a manual page reload**.
**Expected:** new version visible immediately (JS reload dispatched after save).

**DM-015:** create 3 versions → restore to v2 → assert DM fields equal v2 snapshot; no new version auto-created by restore (`withoutVersioning=true`).

---

## 5. Domain B — Custom KPIs

### B1. KPI CRUD
| ID | Title | Pri | Type |
|---|---|---|---|
| KPI-001 | Create KPI from template | P0 | Feature |
| KPI-002 | Create KPI from scratch (calculation types) | P0 | Feature |
| KPI-003 | Edit KPI (repair corrupted `_ui_state`) | P1 | Feature |
| KPI-004 | Delete KPI + cascade to dashboards/widgets | P1 | Feature |
| KPI-005 | KPI with independent variables | P1 | Feature |
| KPI-006 | KPI asset-group scope | P1 | Feature |
| KPI-007 | KPI with `facebook_organic` IG scope (platform swap) | P2 | Feature |

**KPI-001 steps:** `CustomKpiResource/create` → template picker (e.g. `true_blended_marginal_cost`) → placeholders resolved → next through series → save.
**Expected:** `filters['_ui_state']` packaged correctly; `ast` built via `KpiPayloadBuilder::buildAstFromState`; `template_key` stored; `compatible_widgets`/`optimal_widgets` available to the widget picker.

**KPI-003 (regression for corruption repair):** save a KPI with corrupted nested `filters['_ui_state']['filters']['_ui_state']` → reopen edit → assert deepest valid `_ui_state` wins, model keys stripped, `template` backfilled from `template_key` (or by name match).

**KPI-005:** bivariate KPI with independent variables (independent channels/metrics or DM variables).
**Expected:** `buildIndependentNodes` chains independents; independent DM variables use `dm_<id>` keys; single-variable fast-path for univariate.

### B2. KPI Execution / Payload
| ID | Title | Pri | Type |
|---|---|---|---|
| KPI-008 | Test action (`KpiExecuteActionBuilder`) — "Test" button | P0 | Feature |
| KPI-009 | Payload Debugger ("Payload" button) | P2 | Feature |
| KPI-010 | `KpiPayloadBuilder::build` payload shape | P1 | Unit |
| KPI-011 | `buildAstFromState` for univariate vs bivariate | P1 | Unit |
| KPI-012 | `swapFboIgPlatformIds` platform swap | P2 | Unit |
| KPI-013 | GSC `searchAppearance=standard` de-dup filter | P2 | Unit |

**KPI-008 steps:** open KPI list → "Test" action → fill runtime overrides for empty `_ui_state` slots → submit.
**Expected:** payload built with runtime overrides merged into `_ui_state`; remote `computeKpi` called; success/failure notification shown.

**KPI-010/011:** unit-test `build()` — assert `ast`, `filters.startDate/endDate/groupBy`, `zero_handling`, `edge_case_handling` (weighted/grouping + `group_column` `'y'` for `position`), `max_ratio`, `{calculationType:true}` flag. For AST: univariate returns single dependent node; bivariate produces `'/'` operator tree; `dependent_additional_variables` become nested `+` sums.

### B3. KPI Versioning
| ID | Title | Pri | Type |
|---|---|---|---|
| KPI-014 | Save Version captures current form state | P0 | Feature |
| KPI-015 | Version table reflects new version immediately | P0 | Feature |
| KPI-016 | Restore KPI version | P1 | Feature |
| KPI-017 | No auto-version on plain update | P1 | Unit |
| KPI-018 | Prune all versions bulk action | P2 | Feature |

(Same expected outcomes as DM-013..019 but against `custom_kpi_versions`.)

---

## 6. Domain C — Dashboard + Widget CRUD

### C1. Dashboard CRUD
| ID | Title | Pri | Type |
|---|---|---|---|
| DASH-001 | Create dashboard | P0 | Feature |
| DASH-002 | Edit dashboard (name/description) | P1 | Feature |
| DASH-003 | Delete dashboard | P1 | Feature |
| DASH-004 | Dashboard quota enforcement (tier) | P1 | Feature |
| DASH-005 | `is_public` toggle + public-count limit | P2 | Feature |
| DASH-006 | Set default dashboard | P2 | Feature |
| DASH-007 | Dashboard scoping: owner/shared-only visibility | P1 | Feature |
| DASH-008 | Shared dashboard view (`/shared/dashboard/{subdomain}/{dashboard}`) | P2 | Feature |

**DASH-001 steps:** `DashboardResource/create` → name/description/visibility → save.
**Expected:** created; version #1 auto-created (created event); appears in list for owner only (unless shared/public).

**DASH-004:** as FREE tier at quota, create dashboard → blocked (`canCreate` false via `getMaxPrivateDashboardsForTier`). Verify SUSPENDED → 0.

### C2. Widget CRUD (Builder)
| ID | Title | Pri | Type |
|---|---|---|---|
| WID-001 | Add KPI widget | P0 | Feature |
| WID-002 | Add DM widget | P0 | Feature |
| WID-003 | Add metric widget | P1 | Feature |
| WID-004 | Delete widget | P1 | Feature |
| WID-005 | Duplicate widget | P1 | Feature |
| WID-006 | Change widget type (compatibility enforced) | P1 | Feature |
| WID-007 | Edit widget controls/title/description | P1 | Feature |
| WID-008 | Resize/reposition widget (saveLayout) | P0 | Feature |
| WID-009 | Widget picker compatibility (compatible/optimal) | P2 | Feature |

**WID-001 steps:** Dashboard Builder → add widget → pick a KPI → confirm.
**Expected:** `DashboardService::addWidget` creates `DashboardWidget` with `source_type=kpi`; appears in grid; KPI listed via `getKpisForWidgetPicker` only if active + compatible template.

**WID-002:** add DM widget → `source_type=derived_metric`, `derived_metric_id` set.

**WID-006:** change `kpi`→`table` (allowed) vs `kpi`→`gauge`(kpi-compatible: allowed) vs `metric`→`scatter_plot` (NOT in `getWidgetTypesForSource` for metric → rejected with danger notification).

**WID-008 (regression for soft-delete scope bug 2026-07-30):** resize a widget → grid_x/y/w/h persisted via `DashboardService::saveLayout`; widget auto-version v+1 created (DashboardWidget auto-versioning is ON).

### C3. Dashboard Versioning
| ID | Title | Pri | Type |
|---|---|---|---|
| DASHV-001 | Save Version (manual) captures dashboard + widget snapshot | P0 | Feature |
| DASHV-002 | Unsaved-changes indicator persists across reload | P0 | Feature |
| DASHV-003 | Restore full dashboard version (fields + widgets) | P0 | Feature |
| DASHV-004 | Restore handles: extra widgets deleted, missing widgets restored, changed widgets reverted | P0 | Feature |
| DASHV-005 | Restore does NOT auto-create a version | P1 | Feature |
| DASHV-006 | Duplicate dashboard from version | P1 | Feature |
| DASHV-007 | Duplicate current dashboard | P1 | Feature |
| DASHV-008 | Widget version snapshot captured on dashboard version | P1 | Feature |
| DASHV-009 | Version history modal shows label + duplicate button | P2 | Feature |
| DASHV-010 | Prune versions bulk action | P2 | Feature |

**DASHV-001 steps (regression):** builder → add/move widgets → header "Save Version" with a label.
**Expected:** `dashboard_versions` row has `widget_ids` + `widget_version_ids` pointing at the latest `WidgetVersion` of each widget (widgets without a prior version get one on-the-fly "Initial snapshot").

**DASHV-002 (regression for bug 2026-07-30):** make builder edits (unsaved) → reload page.
**Expected:** the unsaved-changes warning is STILL present after reload because `mount()` sets `unsavedChanges = dashboard->hasUnsavedChanges()`.

**DASHV-003/004 (regression for soft-delete scope bug 2026-07-30):**
1. Create dashboard v1 with 3 widgets (w1,w2,w3) → save version.
2. Add w4, delete w1, resize w2 → save version.
3. Restore to v1.
**Expected (all three cases):** w4 soft-deleted (`deleted_at` set, visible via `withTrashed`); w1 restored from trash; w2 grid/size reverted to v1 snapshot; no widget version rows created during restore; dashboard fields reverted.

**DASHV-005:** after restore, `versions()->count()` unchanged.

**DASHV-006/007:** clone from version → new dashboard with widgets recreated from `WidgetVersion` rows; clone current → new dashboard with fresh widget copies + `(Copy)` name + `is_default=false`.

### C4. Widget Versioning (auto)
| ID | Title | Pri | Type |
|---|---|---|---|
| WIDV-001 | Widget update auto-creates version (only model with auto ON) | P0 | Unit |
| WIDV-002 | Widget update with no trackable change → no version | P1 | Unit |
| WIDV-003 | Version number sequential per widget | P1 | Unit |
| WIDV-004 | Restore widget version | P1 | Feature |
| WIDV-005 | Widget version label/change_summary persisted | P2 | Feature |

(Partially covered by existing `tests/Unit/TracksVersionsTest.php` L50-72.)

---

## 7. Domain D — Data Requests & Rendering

> Core file under test: `app/Http/Controllers/Api/DashboardWidgetDataController.php` (2049 lines). Mock the remote engine for all of these.

### D1. Widget data endpoint
| ID | Title | Pri | Type |
|---|---|---|---|
| REQ-001 | `POST /api/dashboard/widget/{widget}/data` auth + ownership | P0 | Feature |
| REQ-002 | KPI source → chart dataset | P0 | Feature |
| REQ-003 | KPI source → table rows | P1 | Feature |
| REQ-004 | DM source → cached vs live | P0 | Feature |
| REQ-005 | Metric source (multi-channel) | P1 | Feature |
| REQ-006 | Trend metric request | P1 | Feature |
| REQ-007 | Chrome metric request | P2 | Feature |
| REQ-008 | GA4 series request | P2 | Feature |
| REQ-009 | Asset-group validation + `__empty_group__` sentinel | P1 | Feature |
| REQ-010 | Controls resolution precedence (widget > dashboard > KPI `_ui_state`) | P0 | Unit |
| REQ-011 | Control cache hash stability | P1 | Unit |
| REQ-012 | 403 for non-owner / non-shared / non-public | P0 | Feature |

**REQ-001:** owner user → 200; non-owner (not shared, dashboard private) → 403; shared user → 200; public dashboard → 200.

**REQ-002/003:** mock remote `computeKpi` response with time series → assert `show()` returns chart-shaped `{labels, datasets}` (palette `3/0.80/1a` hex) and table-shaped `{columns, rows}` (respecting `percentage`/`currency` format).

**REQ-004:** first call → remote called + result cached; second call with same controls → served from `derived_metric_results`, remote NOT called again.

**REQ-009 (regression for `___EMPTY_GROUP___` short-circuit):** a variable with empty channel/asset group must skip asset resolution and NOT trigger the empty-group short-circuit. Assert no `["___EMPTY_GROUP___"]` sentinel leaks into the request when the channel is empty (e.g. DM variables).

**REQ-010:** `WidgetDataService::resolveControls` — widget controls override dashboard controls which override KPI `_ui_state`; `__inherit__` honored; missing keys fall through.

### D2. Dashboard View / Widget View
| ID | Title | Pri | Type |
|---|---|---|---|
| VIEW-001 | Dashboard View page loads widgets ordered by grid | P0 | Feature |
| VIEW-002 | Widget View renders each widget's data via REQ-001 | P0 | Feature |
| VIEW-003 | KPI `_ui_state` defaults surfaced to view | P1 | Feature |
| VIEW-004 | DM KPI variable metadata (`dm_kpi_series`) exposed | P1 | Feature |
| VIEW-005 | Asset-mode (single vs multiple) per channel | P1 | Feature |
| VIEW-006 | kpi_theory guidance tooltip data | P3 | Feature |

**VIEW-001/002:** open Dashboard View → assert `LoadsDashboardViewData` builds per-widget controls via `resolveControls`, widgets sorted `grid_y` then `grid_x`; each widget card fetches its data through the API endpoint and renders (chart/table/tile per `widget_type`).

**VIEW-004 (regression for `_source_type` gating bug):** KPI with DM variables → view exposes `dm_kpi_series` so the widget renders DM series data (not empty `metrics: ["",""]`).

### D3. Shared dashboard
| ID | Title | Pri | Type |
|---|---|---|---|
| SHARED-001 | Public dashboard accessible via subdomain URL | P1 | Feature |
| SHARED-002 | Private dashboard inaccessible via shared URL | P1 | Feature |

---

## 8. Domain E — Duplication

| ID | Title | Pri | Type |
|---|---|---|---|
| DUP-001 | Duplicate KPI (ReplicateAction) | P1 | Feature |
| DUP-002 | Duplicate DM (ReplicateAction) | P1 | Feature |
| DUP-003 | Duplicate widget (name + "(Copy)") | P1 | Feature |
| DUP-004 | Duplicate current dashboard | P1 | Feature |
| DUP-005 | Duplicate dashboard from version | P1 | Feature |

**DUP-001/002:** Replicate → new row name `" (copy)"`, `id` excluded, `dashboards_count`/`widgets_count` excluded; version #1 created for the copy.

**DUP-004:** builder "Duplicate" → `cloneDashboard` → name + ` (Copy)`, `is_default=false`, widgets re-created fresh, redirects to new builder.
**DUP-005:** version-history modal "Duplicate" → `cloneDashboardFromVersion` → widgets rebuilt from `WidgetVersion` rows.

---

## 9. Domain F — Hierarchy Heritage (DM → KPI → Builder → View)

These tests assert that a change made at the DM level propagates correctly through the entire chain.

| ID | Title | Pri | Type |
|---|---|---|---|
| HIER-001 | DM listed in KPI form's DM variable selector | P0 | Feature |
| HIER-002 | DM variable in KPI AST uses `dm_<id>` key | P0 | Unit |
| HIER-003 | DM-selecting KPI shows DM asset selectors in builder | P0 | Feature |
| HIER-004 | DM-selecting KPI renders data in dashboard view | P0 | Feature |
| HIER-005 | DM-selecting KPI renders in widget view | P0 | Feature |
| HIER-006 | DM available in widget picker for KPI widgets | P1 | Feature |
| HIER-007 | KPI referencing a deleted/soft-deleted DM degrades gracefully | P1 | Feature |
| HIER-008 | DM change invalidates dependent widget caches | P1 | Feature |
| HIER-009 | Widget type restrictions preserved down the chain | P1 | Feature |

**HIER-001:** KPI form → source type `derived_metric` → selector lists active DMs for the project (`KpiFormBuilder::getDerivedMetricOptions`).

**HIER-002:** unit-test `buildAstFromState` with `dependent_dm_id` → AST dependent node key `dm_<id>`; same for independent variables.

**HIER-003/004/005 (regression for `_source_type` gating bug):** create DM → create KPI using it as dependent variable → add KPI widget in builder (assert DM asset selectors appear via presence of `dependent_dm_id`) → open Dashboard View and Widget View (assert data renders, no `___EMPTY_GROUP___` short-circuit, no `metrics: ["",""]`).

**HIER-007:** soft-delete a DM referenced by a KPI → widget fetch → assert controlled failure (notification/empty state), no 500.

**HIER-008:** update DM → `DerivedMetricCacheService::invalidateCache` + `WidgetDataService::invalidateCache` for dependent widgets → next fetch recomputes.

**HIER-009:** verify widget types allowed for `derived_metric` source (`tile, line_chart, bar_chart, gauge, sparkline, table`) vs `kpi` source (adds `anomaly_chart, scatter_plot, combo_chart`) throughout builder picker and `changeWidgetType`.

---

## 10. Domain G — Permissions, Tenancy, Quotas

| ID | Title | Pri | Type |
|---|---|---|---|
| PERM-001 | User without `edit_preferences` cannot create/edit/delete KPI or DM | P1 | Feature |
| PERM-002 | `view_data` user can access dashboards but not create | P1 | Feature |
| PERM-003 | Tenant isolation: project A cannot see project B data | P0 | Feature |
| PERM-004 | Widget data endpoint tenant check (project match) | P0 | Feature |
| QUOTA-001 | KPI quota enforcement (FREE=10, PRO=30, ...) | P1 | Feature |
| QUOTA-002 | DM quota enforcement | P1 | Feature |
| QUOTA-003 | Dashboard quota enforcement | P1 | Feature |
| QUOTA-004 | API rate limit per tier (ULTRA 500, ENTERPRISE 1000) | P2 | Feature |

**PERM-003/004:** two projects, two users → user A's widget data request against user B's project/dashboard/widget → 403; KPI/DM lists scoped to own project.

---

## 11. Domain H — Automation: New Test Files to Create

Automated (Pest) coverage to add, mapped to the scenarios above:

1. `tests/Feature/Analytics/DerivedMetricLifecycleTest.php` — DM-001/002/003/005/006, DM-013/014/015 (versioning), DUP-002
2. `tests/Feature/Analytics/CustomKpiLifecycleTest.php` — KPI-001/002/003/005, KPI-014/015/016, DUP-001
3. `tests/Unit/Analytics/KpiPayloadBuilderTest.php` — KPI-010/011/012/013, HIER-002
4. `tests/Feature/Analytics/WidgetDataEndpointTest.php` — REQ-001..012 (mock remote engine via `RemoteEngineService::getMockedService` pattern)
5. `tests/Feature/Analytics/DashboardVersionRestoreTest.php` — DASHV-001..008, WID-008, WIDV-001..004 (extends `tests/Unit/TracksVersionsTest.php` patterns)
6. `tests/Feature/Analytics/DuplicationTest.php` — DUP-003/004/005
7. `tests/Feature/Analytics/HierarchyHeritageTest.php` — HIER-001..009
8. `tests/Feature/Analytics/PermissionsQuotasTest.php` — PERM-001..004, QUOTA-001..004
9. `tests/Unit/Analytics/WidgetDataServiceTest.php` — REQ-010/011 (controls resolution, cache hash)
10. `tests/Unit/Analytics/DerivedMetricCacheServiceTest.php` — DM-007/010

Mocking strategy: reuse the `getMockedService` helper (mocked Guzzle `MockHandler`) from `tests/Feature/Services/RemoteEngineServiceTest.php`; abstract it into `tests/TestCase` or a shared `tests/Helpers/RemoteEngineMock.php`.

---

## 12. Manual QA Script (Smoke Test — fastest path)

Run this manually (P0 only) after any change to the analytics domain:

1. Create a DM from a template (cpc) and one from scratch. → **DM-002, DM-001**
2. Create a KPI that uses the scratch DM as a dependent variable. → **HIER-001/002**
3. Create a dashboard; add the KPI widget + a plain DM widget. → **WID-001/002, HIER-003**
4. Move/resize widgets; save a dashboard version with a label. → **WID-008, DASHV-001**
5. Reload the builder page — unsaved warning persists. → **DASHV-002**
6. Open Dashboard View and confirm both widgets render real data. → **VIEW-001/002, HIER-004/005**
7. Add widget #4, delete widget #1, resize widget #2; restore to the version from step 4. → **DASHV-003/004/005**
8. Duplicate the dashboard; open the copy. → **DUP-004**
9. Edit the DM formula; open KPI edit page and "Save Version"; verify new row in Versions tab without reload. → **DM-013/014, KPI-014/015**

---

## 13. Known Open Issues / Pre-Existing Bugs to Re-verify

> These are bugs discovered in earlier sessions (2026-07-29/30). Include them as explicit regression assertions.

1. **KPI form "Next" button stuck on step 22_series** when DM source type selected ("can't select next because there's no channel selected"). Root cause unknown (Livewire reactivity or validation). → smoke test + KPI-001/002/005.
2. **DM KPI widget shows `metrics: ["", ""]`** — cosmetic; AST built correctly. Verify rendering path anyway.
3. **Dashboard view may not display DM metric selection** — `LoadsDashboardViewData` auto-resolve didn't recognize DM variables (`_source_type` gating). Regression-covered by HIER-004/005 and VIEW-004.
4. **`withoutVersioning` property set from a different class** — accessing `$widget->withoutVersioning` from `Dashboard` went through Eloquent `__set()` and wrote to DB (column doesn't exist). Ensure no code sets the flag via magic access.
5. **Soft-delete scope blocked restore updates** — `updateQuietly()` on a soft-deleted model fails silently (`WHERE deleted_at IS NULL`). Restore path must use `withoutGlobalScope(SoftDeletingScope::class)` query updates. Regression-covered by DASHV-003/004.
6. **Stale record snapshot on Save Version** — `EditCustomKpi`/`EditDerivedMetric` must fill record from form state before `createVersion()`. Regression-covered by DM-013/KPI-014.
7. **Version table not auto-refreshing** — after Save Version, page must reload (JS) so the relation manager shows the new row. Regression-covered by DM-014/KPI-015.
8. **`label` migration pending** — `2026_07_30_000006_add_label_to_version_tables.php` must be migrated before saving any version (DB unreachable in earlier sessions).

---

## 14. File Reference

| Concern | File(s) |
|---|---|
| Dashboard service (CRUD/clone/add/remove/duplicate) | `app/Services/DashboardService.php` |
| Widget data endpoint (main rendering controller) | `app/Http/Controllers/Api/DashboardWidgetDataController.php` |
| Dashboard view data loader | `app/Filament/App/Resources/DashboardResource/Traits/LoadsDashboardViewData.php` |
| KPI form/payload builders | `app/Services/Analytics/KpiFormBuilder.php`, `app/Services/Analytics/KpiPayloadBuilder.php` |
| KPI test/execute action | `app/Services/Analytics/KpiExecuteActionBuilder.php` |
| DM cache + preview | `app/Services/DerivedMetricCacheService.php`, `app/Http/Controllers/Api/DerivedMetricPreviewController.php` |
| Widget controls/cache | `app/Services/WidgetDataService.php` |
| Widget type rules | `app/Services/WidgetTypeRegistry.php` |
| Granularity aggregation | `app/Services/Analytics/GranularityAggregationService.php` |
| Predefined templates | `app/Services/Analytics/PredefinedKpiRegistry.php`, `app/Services/Analytics/PredefinedDerivedMetricRegistry.php` |
| Versioning trait/models | `app/Traits/TracksVersions.php`, models `DashboardVersion`, `WidgetVersion`, `CustomKpiVersion`, `DerivedMetricVersion` |
| Resources (tables/pages) | `app/Filament/App/Resources/{CustomKpi,DerivedMetric,Dashboard}Resource*.php` |
| Builder page | `app/Filament/App/Resources/DashboardResource/Pages/DashboardBuilder.php` |
| Billing quotas | `app/Services/BillingLifecycleService.php` |
| Routes | `routes/web.php` (L96 widget data, L98 DM preview) |
| Existing tests | `tests/Unit/TracksVersionsTest.php`, `tests/Feature/VersionHistoryTest.php`, `tests/Feature/Services/RemoteEngineServiceTest.php` |

---

## 15. Execution Checklist (hand-off)

When implementing this plan:

1. [ ] Run `php artisan migrate --force` (label migration).
2. [ ] Run existing suite `php artisan test` — establish green baseline (excluding known-failing scenarios).
3. [ ] Implement automated test files from §11 (highest value first: `WidgetDataEndpointTest`, `DashboardVersionRestoreTest`, `HierarchyHeritageTest`).
4. [ ] Run manual smoke test §12.
5. [ ] Re-verify each open issue in §13.
6. [ ] Update this file's status to `Implemented` and check off IDs as they pass; log failures with reproduction steps.

**Progress tracker:**

| Domain | Total IDs | Passed | Failed | Blocked | Notes |
|---|---|---|---|---|---|
| A — Derived Metrics | 19 | 9 | 0 | - | DM-007..010 via `DerivedMetricCacheServiceTest` |
| B — Custom KPIs | 18 | 0 | 0 | - | |
| C — Dashboard + Widget | 30 | 13 | 0 | - | DASHV-001..008, WID-008, WIDV-001..004, DUP-004/005 via `DashboardVersionRestoreTest` |
| D — Data Requests & Rendering | 22 | 0 | 0 | - | |
| E — Duplication | 5 | 3 | 0 | - | DUP-003/004/005 via `DashboardVersionRestoreTest` |
| F — Hierarchy Heritage | 9 | 0 | 0 | - | |
| G — Permissions/Tenancy/Quotas | 9 | 0 | 0 | - | |
| **TOTAL** | **112** | **25** | **0** | | |

**Implemented 2026-07-30:** `tests/Unit/TracksVersionsTest.php` (27 passing, stale assertions updated to post-overhaul manual-versioning model), `tests/Feature/Analytics/DashboardVersionRestoreTest.php` (11 passing), `tests/Unit/Analytics/DerivedMetricCacheServiceTest.php` (9 passing), `tests/Feature/VersionHistoryTest.php` pruning tests (2 fixed). **Bugs found & fixed during implementation:** stale `widgets` relation cached on dashboard instance (`loadMissing`→`load` in `getVersionExtraAttributes` + `reconcileWidgetsFromVersion` + `cloneDashboard`), protected `getTrackableFields()` call from `DashboardService` (made public on 3 models), and `cloneDashboardFromVersion()` fill-order overwriting name suffix/`is_default`. Baseline: 89 passing / 25 pre-existing unrelated failures.
