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
- **Next steps:** Add a reference page similar to `KpiReference`, add Spanish translations to `es.json`