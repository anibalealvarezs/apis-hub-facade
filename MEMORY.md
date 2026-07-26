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