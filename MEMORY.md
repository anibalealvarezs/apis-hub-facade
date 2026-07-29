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
- **Status:** DM source series rendering refactored — each DM source series renders as a separate full-width card instead of nested panels
- **Changes in KpiFormBuilder.php:** Added `getDerivedMetricOptions()`, source type selector in `getNodeSchema()`, DM select field, `afterStateUpdated` to clear opposing field values when switching source types, summary HTML for DM display. Removed `disabled()` on next_series, removed `searchable()` on DM select, removed `required()` on dependent_metric.
- **Changes in KpiPayloadBuilder.php:** Handles `dm_<id>` metric keys in `buildAstFromState()` and `buildIndependentNodes()` for both dependent and independent variables
- **Changes in DashboardWidgetDataController.php (handleKpiSource):**
  - Pre-fetches DM series data for KPI variables using DMs; accepts DM source type for dependent variable
  - Added `depSourceType`/`indSourceType` guards to skip asset_group resolution for DM variables (avoids `___EMPTY_GROUP___` sentinel from empty channel)
  - Pre-fetches DM data for KPI variables (lines 1618–1650): builds `$dmControlKeys` mapping DM IDs → KPI variable prefix, maps KPI DM `series_assets` keys (`dep_dm_N`/`ind_N_dm_M`) → DM source series keys (`dm_N`) before calling `handleDerivedMetricSource()`
- **Bug fix (2026-07-29):** DM KPI widget shows no data (`___EMPTY_GROUP___` short-circuit)
  - **Root cause:** The independent variable's `independent_source_type` is not persisted in `_ui_state` (missing key), so `$indSourceType` defaults to `'channel'`. With `$indChannel` empty (DM has no channel), the asset group resolution at line 1362 sets `independent_asset_filter = ["___EMPTY_GROUP___"]`, which triggers the `$hasEmptyGroup` short-circuit and returns empty data.
  - **Fix:** Added `&& ! empty($indChannel)` guard to both the independent variable asset_group route (line 1362) and assets route (line 1377), so empty-channel independent variables (DM or unconfigured) skip asset group/asset resolution entirely.
- **Builder Blade changes (`dashboard-builder.blade.php`):**
  - **Channel loading:** Extended `channelsToLoad` in `openWidgetControls()` to include channels from KPI DM source series
  - **series_assets init:** Added initialization of `widgetControlsForm.series_assets['dep_dm_N']` and `widgetControlsForm.series_assets['ind_N_dm_M']` for DM source series
  - **DM dependent series cards (~line 860):** Each source series renders as a **separate full flex-none card** (matching DM widget pattern at line 1092) with header showing "DM Name · Source label" + DM badge, channel badge, read-only metric, and asset override with search/select-all/clear/checkbox list
  - **DM independent series cards (~line 924):** Each source series renders as its own standalone card (same pattern as dependent DM cards), replacing the previous nested source-series panels inside a single outer card per variable
  - **Template tag fix:** Fixed unclosed `<template>` tag wrapping the regular independent variable card (added missing `</template>` at correct indent level) to resolve Livewire `getUpdateUri` error
  - **Regular independent card:** Changed from `<div x-show>` to `<template x-if>` to prevent JS errors when `independent_channel` is empty (DM variables)
- **View Blade (`dashboard-view-content.blade.php`):** Settings modal header shows DM name + "DM" badge for DM source series entries (line 574)
- **`LoadsDashboardViewData.php`:** Added `widgetArray['dm_kpi_series']` with per-DM `{ dm_id, dm_name, source_series[] }`, `series_assets_options` entries (`dep_dm_N`, `ind_N_dm_M`), DM variables with `dm_name`/`dm_source_label`, and asset group channel mapping for `dep_dm_N`/`ind_N_dm_M` keys
- **Known issues:**
  - Next button in step 22_series may not advance when DM source type selected (user reports "can't select next because there's no channel selected") — root cause unknown; possibly client-side Livewire reactivity or validation
  - Anomaly/KPI payload shows `metrics: ["", ""]` — cosmetic; actual AST is built correctly by KpiPayloadBuilder from `_ui_state` DM IDs
  - Dashboard view may not display DM metric selection — `LoadsDashboardViewData.php` auto-resolve logic doesn't recognize DM source type variables