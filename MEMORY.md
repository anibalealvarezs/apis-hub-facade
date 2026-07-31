# APIs Hub Facade Memory
## Scope
- Package role: Business (Facade)
- Purpose: This package operates within the Business (Facade) layer of the APIs Hub SaaS hierarchy, managing the SaaS application, users, projects, server configuration, and access workflows.
- Dependency stance: Consumes `anibalealvarezs/apis-hub-api`, `anibalealvarezs/facebook-graph-api`, and `anibalealvarezs/google-api`; serves SaaS users and the APIs Hub infrastructure lifecycle.
## Local working rules
- Consult `AGENTS.md` first for package-specific instructions.
- Use this `MEMORY.md` for repository-specific decisions, learnings, and follow-up notes.
- Use `D:\laragon\www\_shared\AGENTS.md` and `D:\laragon\www\_shared\MEMORY.md` for cross-repository protocols and workspace-wide learnings.
- Keep secrets, credentials, tokens, and private endpoints out of this file.
## Current notes
- Laravel business layer for SaaS management and operational workflows.

### Derived Metrics Feature (2026-07-25)
- **Status:** Core implementation complete (Steps 1-13, 17, 20-24 done)
- **Plan:** `DERIVED_METRICS_PLAN.md` — 25 steps, tracks completion
- **Versioning Plan:** `TRACKING_VERSIONING_PLAN.md` — DMs, KPIs, Dashboards
- **New files created:**
  - `database/migrations/2026_07_25_000001_create_derived_metrics_table.php`
  - `database/migrations/2026_07_25_000002_add_derived_metric_id_to_dashboard_widgets_table.php`
  - `database/migrations/2026_07_25_000003_create_derived_metric_results_table.php`
  - `app/Models/DerivedMetric.php`
  - `app/Models/DerivedMetricResult.php`
  - `app/Services/DerivedMetricCacheService.php`
  - `app/Filament/App/Resources/DerivedMetricResource.php`
  - `app/Filament/App/Resources/DerivedMetricResource/Pages/ListDerivedMetrics.php`
  - `app/Filament/App/Resources/DerivedMetricResource/Pages/CreateDerivedMetric.php`
  - `app/Filament/App/Resources/DerivedMetricResource/Pages/EditDerivedMetric.php`
  - `app/Http/Controllers/Api/DerivedMetricPreviewController.php`
  - `apis-hub/.../Nodes/DerivedMetricNode.php`
- **Modified files:**
  - `app/Models/DashboardWidget.php` — added `derived_metric_id` FK + relationship
  - `app/Services/WidgetTypeRegistry.php` — added `derived_metric` source type
  - `app/Services/BillingLifecycleService.php` — added `getMaxDerivedMetricsForTier()`
  - `app/Http/Controllers/Api/DashboardWidgetDataController.php` — added `handleDerivedMetricSource()`, `extractTimeSeriesFromResponse()`, extended `extractAssetFilter()` with `$seriesAssetFilter`
  - `apis-hub/.../Nodes/OperatorNode.php` — added 6 new operators (ratio, avg, min, max, abs_diff, pct_change)
  - `apis-hub/.../AstParser.php` — added `derived_metric` node type
  - `apis-hub/.../EvaluationContext.php` — added `derivedMetricResolver` callback
  - `routes/web.php` — added preview endpoint route
- **Remaining steps:** 14 (Widget Builder UI), 15 (KPI picker integration), 16 (validation), 18 (shared dashboard), 19 (tests), 25 (export/import)
- **Key architectural decisions:**
  - Progressive asset restriction: DM definition ∩ KPI ∩ Widget builder ∩ Dashboard runtime
  - AST evaluated locally in PHP (not via analytics engine) since DM source series are fetched independently
  - Cache key: SHA-256 of controls (date_start, date_end, granularity, asset_group, assets)
   - Recursive DM resolution via `EvaluationContext::setDerivedMetricResolver()`

### Predefined Derived Metrics Registry (2026-07-28)
- **Status:** Registry created with 21 entries
- **New file:** `app/Services/Analytics/PredefinedDerivedMetricRegistry.php`
- **Pattern:** Mirrors `PredefinedKpiRegistry` — same `required_tags` filtering via `ChannelCapabilityRegistry`, `getAvailable(array $activeChannels)` method, and placeholder channel references (`__SPENDABLE_CHANNEL_1__`, etc.)
- **Categories:**
  - **Single-Channel Paid Media (9):** `cpc`, `ctr`, `cpa`, `cvr`, `roas`, `cost_per_conversion`, `result_rate`, `cost_per_engagement`, `engagement_click_rate`
  - **Single-Channel Organic Social (3):** `organic_engagement_rate`, `organic_reach_efficiency`, `organic_impression_engagement`
  - **Single-Channel SEO (3):** `seo_ctr`, `click_position_efficiency`, `impression_position_efficiency`
  - **Cross-Channel Paid (5):** `blended_cpc`, `blended_cpa`, `blended_ctr`, `blended_roas`, `budget_share_ratio`
  - **Cross-Channel Paid + Organic (4):** `paid_organic_reach_ratio`, `paid_organic_reach_abs_diff`, `seo_paid_click_ratio`, `paid_organic_impression_ratio`
  - **Cross-Channel Hybrid (1):** `organic_reach_vs_seo_ctr`
  - **Cross-Channel Revenue (1):** `revenue_per_click`
- **Status:** Integrated into `DerivedMetricResource` form with template picker step
- **Form changes:** Added 2-step wizard prefix: `0_intent` (template vs scratch) → `1_template` (select + pre-fill) → `2_series` → `3_formula` → `4_details` → `5_summary`; existing steps renumbered
- **Template pre-fill:** Channel placeholders resolved against user's active channels via `ChannelCapabilityRegistry`; keys (a, b, c…) auto-assigned; AST, format, output_granularity, name, description pre-filled
- **Template details panel:** Shows name, description, format badge, source series keys/metrics, and AST preview
- **Helper methods:** `getDerivedMetricCategoryOptions()` (13 categories) and `getDerivedMetricTemplateOptions(array $categoryFilter)` (filters by category + active channels) added to `DerivedMetricResource`
- **Bug fix (2026-07-28):** DM table widget rendering — `handleDerivedMetricSource()` returned only chart-shaped data (`labels`/`datasets`). Added `elseif` branch in `show()` at line 961 that detects `$effectiveWidgetType === 'table' && isset($data['labels']) && isset($data['datasets'])` and transforms to `columns`/`rows` with Date column and dataset columns, respecting `percentage`/`currency` format flags.
- **Next steps:** Add a reference page similar to `KpiReference`, add Spanish translations to `es.json`

### KPI Derived Metric Integration (2026-07-29)
- **Status:** DM variables can be selected in KPI form builder; next button issue unresolved
- **Changes in KpiFormBuilder.php:** Added `getDerivedMetricOptions()`, source type selector in `getNodeSchema()`, DM select field, `afterStateUpdated` to clear opposing field values when switching source types, summary HTML for DM display. Removed `disabled()` on next_series, removed `searchable()` on DM select, removed `required()` on dependent_metric.
- **Changes in KpiPayloadBuilder.php:** Handles `dm_<id>` metric keys in `buildAstFromState()` and `buildIndependentNodes()` for both dependent and independent variables
- **Changes in DashboardWidgetDataController.php (handleKpiSource):** Pre-fetches DM series data for KPI variables using DMs; accepts DM source type for dependent variable; added `depSourceType`/`indSourceType` guards to skip asset_group resolution for DM variables (avoids `___EMPTY_GROUP___` sentinel from empty channel)
- **Bug fix (2026-07-29):** DM KPI widget shows no data (`___EMPTY_GROUP___` short-circuit)
  - **Root cause:** The independent variable's `independent_source_type` is not persisted in `_ui_state` (missing key), so `$indSourceType` defaults to `'channel'`. With `$indChannel` empty (DM has no channel), the asset group resolution at line 1362 sets `independent_asset_filter = ["___EMPTY_GROUP___"]`, which triggers the `$hasEmptyGroup` short-circuit and returns empty data.
  - **Fix:** Added `&& ! empty($indChannel)` guard to both the independent variable asset_group route (line 1362) and assets route (line 1377), so empty-channel independent variables (DM or unconfigured) skip asset group/asset resolution entirely.
  - **Logs confirmed:** The DM guard check correctly logs `depSourceType: "derived_metric"`, and the auto-resolve correctly skips DM source type. The independent variable downstream is now properly guarded.
- **Bug fix (2026-07-29, builder):** DM KPI asset selectors not showing in builder
  - **Root cause:** Blade template (`x-if`) and JS `openWidgetControls` both check `_source_type === 'derived_metric' && _dm_id` but the stored `_ui_state` only has `_dm_id` (no `_source_type`). The controller fix-ups `dependent_source_type` only during data fetch (line 1522), not for `getKpiConfiguration()` or independent vars.
  - **Fix:** Simplified all 6 gating conditions (2 blade `x-if`, 2 JS `initDmKpiAssets`, 2 JS `channelsToLoad`) to check just `dependent_dm_id` / `independent_dm_id` — presence implies it's a derived metric.
- **Known issues:**
  - Next button in step 22_series may not advance when DM source type selected (user reports "can't select next because there's no channel selected") — root cause unknown; possibly client-side Livewire reactivity or validation
  - Anomaly/KPI payload shows `metrics: ["", ""]` — cosmetic; actual AST is built correctly by KpiPayloadBuilder from `_ui_state` DM IDs
  - Dashboard view may not display DM metric selection — `LoadsDashboardViewData.php` auto-resolve logic doesn't recognize DM source type variables (same `_source_type` gating issue)

### Formula Editor Render Hook Fix (2026-07-29)
- **Problem:** Formula editor Alpine component (`formulaEditor`) not loading in DM editor page
- **Root cause chain:**
  - Original `panels::head.end` hook rendered SEO auth view directly (returned `View` object)
  - Formula editor JS was moved into `panels::head.end` alongside SEO auth via inline `<script>` (commit `152e49ac`), both wrapped in `Blade::render()`
  - SEO auth view uses `@@context` which renders to `@context` (Alpine event listener). When passed through `Blade::render()`, `@context` collides with Filament's `@context` Blade directive — breaks login page
  - Commit `647a2693` moved SEO auth OUT of `Blade::render()` to fix login page, but formula editor JS stopped loading
  - Commit `f36e45fd` put only formula editor back in `Blade::render()` (SEO auth outside) — still broken
- **Key insight from investigation:** `FilamentView::renderHook()` casts return to `(string)` before wrapping in `HtmlString` — so `Blade::render()` vs plain string shouldn't change HTML output. Root cause of formula editor not loading outside `Blade::render()` remains unclear, but `Blade::render()` context is required for it to function.
- **Fix applied:** Both formula editor JS and SEO auth wrapped in single `Blade::render()` call, with SEO auth wrapped in `@verbatim`/`@endverbatim` to prevent `@context` from being processed as a Blade directive
- **Current code** (`AppPanelProvider.php:78`):
  ```php
  fn () => \Illuminate\Support\Facades\Blade::render('
      <script>' . file_get_contents(resource_path('js/formula-editor.js')) . '</script>
      @verbatim' . view("filament.hooks.seo-auth")->render() . '@endverbatim
  ')
  ```
- **Commit:** `a2e5bf4` (HEAD)

### Dashboard Full-State Restore (2026-07-29)
- **Status:** Implemented
- **Problem:** Restoring a dashboard version only restored dashboard fields (name, description, grid_layout, controls), not the widget state — widgets remained as-is
- **Solution:** Added `widget_ids` (JSON) and `widget_version_ids` (JSON) columns to `dashboard_versions` table. When creating a dashboard version, the current widget IDs and their latest version IDs are snapshotted via `getVersionExtraAttributes()`. 
- **Restore flow:** `restoreFullVersion()` on Dashboard reconciles three cases:
  1. **Extra widgets** (exist now but not in snapshot) → soft-deleted
  2. **Missing widgets** (in snapshot but soft-deleted) → restored from trash
  3. **Changed widgets** (exist in both) → restored to their snapshot version via stored `widget_version_ids`
- **New files:** `database/migrations/2026_07_29_000005_add_widget_snapshot_to_dashboard_versions.php`
- **Modified files:** `app\Models\Dashboard.php`, `app\Models\DashboardVersion.php`, `app\Filament\App\Resources\DashboardResource\RelationManagers\VersionsRelationManager.php`
- **Bug fix (2026-07-29):** Widget size not restored on dashboard version restore
  - **Root cause 1:** `DashboardService::saveLayout()` used bulk update (`DashboardWidget::where(...)->update(...)`) which bypasses Eloquent events — no `WidgetVersion` created when resizing widgets. Every dashboard version pointed to the initial `WidgetVersion` (snapshotted on creation), making widget-size restore a no-op.
  - **Fix 1:** Changed to model-aware `update()` on each loaded `DashboardWidget` instance, firing `TracksVersions` and creating proper `WidgetVersion` records. Reordered: widgets updated first, then dashboard (so dashboard version captures post-update widget version IDs).
  - **Root cause 2:**`reconcileWidgetsFromVersion` used `$version->widget_ids ?? []` — versions created before the feature (or during `created` event before widgets exist) had null `widget_ids`, which was silently converted to `[]`, causing all current widgets to be soft-deleted on restore.
  - **Fix 2:** Explicit null check at top of `reconcileWidgetsFromVersion` skips widget reconciliation entirely when `widget_ids` is null.
  - **Bug fix (2026-07-30):** 18/21 widgets had no WidgetVersion records (created before TracksVersions was applied to DashboardWidget). `getVersionExtraAttributes` only stored `widget_version_ids` for widgets that had versions, causing restore to silently skip the other 18.
  - **Fix:** In `getVersionExtraAttributes`, if a widget has no WidgetVersion, one is created on-the-fly via `$widget->createVersion('Initial snapshot')` before snapshotting.
  - **Note:** Existing `dashboard_versions` created before this fix still have incomplete `widget_version_ids`. To fully restore them, a data migration would need to backfill WidgetVersion records and update old dashboard versions. For now, new dashboard versions will correctly capture all widgets.

### Save Version Snapshots Form Data, Not Stale Record (2026-07-30)
- **Bug:** `EditDerivedMetric` and `EditCustomKpi` "Save Version" actions called `$this->record->createVersion(...)` which snapshots `$this->record`'s current attributes — the **stale DB values**. The form's unsaved changes were never filled into the model, so the version record captured the old state, not the new edits.
- **Fix:** Before `createVersion()`, the form state is processed through `mutateFormDataBeforeSave()` and then filled into the record via `$this->record->fill($formData)`. This updates the in-memory model with the current form values without persisting to DB. `createVersion()` then snapshots the fresh values.
- **Files:** `app/.../DerivedMetricResource/Pages/EditDerivedMetric.php:74-75`, `app/.../CustomKpiResource/Pages/EditCustomKpi.php:151-152`

### Unsaved Changes Warning Persists on Reload (2026-07-30)
- **Bug:** `$unsavedChanges` flag on DashboardBuilder Livewire component was initialized to `false` in the property declaration and never checked against `hasUnsavedChanges()` on mount. After page reload, the warning always disappeared—even when the dashboard state genuinely differed from the latest version (e.g., after restore without creating a version).
- **Fix:** Added `$this->unsavedChanges = $this->dashboard->hasUnsavedChanges()` in `mount()`. On page load, if the current model state doesn't match the latest version snapshot, the warning now persists.
- **Restore flow improved:** After `restoreFullVersion()` (which sets `unsavedChanges = true` then calls `window.location.reload()`), the reload causes `mount()` to re-evaluate `hasUnsavedChanges()`. Since restore creates no new version, the dashboard state differs from the latest version → `unsavedChanges = true` → warning persists.
- **File:** `app/Filament/App/Resources/DashboardResource/Pages/DashboardBuilder.php:34`

### Versioning Overhaul (2026-07-30)- **No auto-versioning on model update:** `TracksVersions::shouldAutoVersionOnUpdate()` returns `false` by default. Versioning is manual via "Save Version" button.
- **DashboardWidget overrides** `shouldAutoVersionOnUpdate()` to `true` — WidgetVersions auto-create on widget saves (needed for dashboard restore accuracy).
- **Unsaved changes indicator:** `$unsavedChanges` flag on DashboardBuilder (Livewire component), reset when version is saved, shown in toolbar header and Save Version action label.
- **Custom version labels:** `label` column added to all 4 version tables via migration `2026_07_30_000006_add_label_to_version_tables.php`. `createVersion()` accepts optional `$versionName` param.
- **Prune all versions:** Bulk action added to all 3 VersionsRelationManagers (Dashboard, CustomKPI, DerivedMetric).
- **Duplicate dashboard from version:** `DashboardService::cloneDashboardFromVersion()` creates a new dashboard from a historic version's snapshot. Triggered from version-history modal.
- **Duplicate current dashboard:** Header action on DashboardBuilder clones current state and redirects.
- **Files modified:**
  - `app/Traits/TracksVersions.php` — removed auto-versioning, added `hasUnsavedChanges()`, `$versionName` param, `shouldAutoVersionOnUpdate()`
  - `app/Models/DashboardWidget.php` — `shouldAutoVersionOnUpdate() = true`
  - `app/Models/DashboardVersion.php`, `WidgetVersion.php`, `CustomKpiVersion.php`, `DerivedMetricVersion.php` — `label` in fillable
  - `app/Filament/App/Resources/DashboardResource/Pages/DashboardBuilder.php` — saveVersion action, duplicateCurrent, duplicateFromVersion, unsavedChanges flag
  - `app/Services/DashboardService.php` — `cloneDashboardFromVersion()`
  - `app/Filament/App/Resources/CustomKpiResource/Pages/EditCustomKpi.php` — saveVersion action
  - `app/Filament/App/Resources/DerivedMetricResource/Pages/EditDerivedMetric.php` — saveVersion action
  - All 3 VersionsRelationManagers — label column, pruneAll bulk action
  - `resources/views/filament/modals/version-history.blade.php` — label column, duplicate button
  - `resources/views/filament/app/pages/dashboard-builder.blade.php` — unsaved changes indicator in toolbar

### Test Suite + Bugs Found (2026-07-30)
- **New:** `TESTING_PLAN.md` — 112-case end-to-end plan covering DM/KPI/Dashboard/Widget lifecycle, versioning, duplication, data requests/rendering, and the DM→KPI→Builder→View heritage chain. Tracks IDs per domain for hand-off to future agents.
- **New tests written (all passing):**
  - `tests/Feature/Analytics/DashboardVersionRestoreTest.php` (11 tests) — DASHV/WIDV/DUP scenarios: widget snapshot capture, initial-snapshot fallback, saveLayout versioning, full restore reconciliation (delete extra/restore trashed/revert changed), no version on restore, unsaved-changes after restore, null widget_ids skip, clone current/from-version, duplicate widget.
  - `tests/Unit/Analytics/DerivedMetricCacheServiceTest.php` (9 tests) — DM-007/008/009/010: controls hash stability/ordering/asset sensitivity, cache miss→store→hit with 60-min TTL, upsert, expiry miss, invalidate/flush.
- **Fixed stale `tests/Unit/TracksVersionsTest.php` (11 tests):** rewrote pre-overhaul assertions (plain updates auto-versioning, before-restore snapshot) to new manual-versioning model (explicit `createVersion()`, `fill()` before snapshot, restore keeps count, `hasUnsavedChanges()`). Added coverage for `label`, widget auto-version change summary, no-snapshot-on-restore.
- **Fixed stale `tests/Feature/VersionHistoryTest.php` pruning tests (2):** moved from `Livewire::test(CustomKpiResource::class)` (never a Livewire component → ComponentNotFoundException) to direct assertions mirroring the `pruneVersions` bulk action closure.
- **Bugs found & fixed while testing:**
  1. **Stale `widgets` relation in `Dashboard::getVersionExtraAttributes()`** — `loadMissing('widgets')` never reloads a relation already cached on the instance. The `created` event auto-versions the dashboard BEFORE widgets exist, caching an empty `widgets` relation; later `createVersion()` snapshotted empty `widget_ids`/`widget_version_ids`, making restore delete all widgets. **Fix:** `load('widgets')` (forces reload). Also applied to the two `loadMissing` calls in `reconcileWidgetsFromVersion()`.
  2. **Same stale-relation bug in `DashboardService::cloneDashboard()`** — `$dashboard->widgets` returned empty cached collection → clones had no widgets. **Fix:** `$dashboard->load('widgets')` before iterating.
  3. **`DashboardService::cloneDashboardFromVersion()` called protected `getTrackableFields()`** → `BadMethodCallException`. **Fix:** made `getTrackableFields()` public on `Dashboard`, `CustomKpi`, `DerivedMetric` (matches existing `DashboardWidget`).
  4. **`cloneDashboardFromVersion()` fill-order bug** — `fill($versionData)` ran AFTER setting the `(From vN)` name suffix and `is_default=false`, overwriting both with the historical values. **Fix:** fill first, then apply suffix + is_default.
- **Baseline:** full suite = 89 passing (was 87) / 25 failing — all 25 remaining failures are pre-existing on clean HEAD (VersionHistoryTest UI-mount tests, ProjectDeployment/ProjectTransfer/ProjectSoftDelete/RemoteEngineService/UserTierManagement) and unrelated to this work. Local SQLite test DB works fine; the Postgres unreachable issue only affects real migrations.
