# Widget Series Breakdowns and Filtering - Implementation Summary

The **Breakdown & Filtering** capability for dashboard widget series has been implemented across `apis-hub` and `apis-hub-facade`.

---

## 1. Engine Layer (`apis-hub`)
- **FilterConditionResolver & UniversalSqlStrategy:**
  - Added support for `not_in` (`not_in`, `not in`, `!in`).
  - Implemented SQL clause generation for `not_in` ensuring null safety: `($column IS NULL OR $column NOT IN (:param))`.
  - Pattern matching (`like`) and standard operators (`eq`, `neq`, `in`, `is_null`, `is_not_null`) are fully supported across standard columns and dynamic EAV dimensions.

---

## 2. Dimension Registry & Facade Controllers (`apis-hub-facade`)
- **[ChannelBreakdownRegistry.php](file:///d:/laragon/www/apis-hub-facade/app/Services/Analytics/ChannelBreakdownRegistry.php):**
  - Unified dimension registry matching the Data Explorer tabs without modifying Data Explorer code.
  - Covers `facebook_marketing`, `google_analytics`, `google_search_console`, `facebook_organic`, and `shopify`.
  - Exposed via Livewire helper method and API endpoint `GET /api/analytics/breakdowns`.
- **Channel Controllers:**
  - [FacebookMarketingController.php](file:///d:/laragon/www/apis-hub-facade/app/Http/Controllers/Api/FacebookMarketingController.php), [FacebookOrganicController.php](file:///d:/laragon/www/apis-hub-facade/app/Http/Controllers/Api/FacebookOrganicController.php), [GoogleAnalyticsController.php](file:///d:/laragon/www/apis-hub-facade/app/Http/Controllers/Api/GoogleAnalyticsController.php), and [GoogleSearchConsoleController.php](file:///d:/laragon/www/apis-hub-facade/app/Http/Controllers/Api/GoogleSearchConsoleController.php) updated to forward `filters`, `breakdown`, and custom `groupBy` to tenant queries. For Facebook Organic, Instagram scope supports `contact_button_type`, `follow_type`, `media_product_type`, and `post`, while Facebook Page scope supports `reaction_type` and `post`.

---

## 3. Widget Data Engine & Series Fan-Out (`apis-hub-facade`)
- **[DashboardWidgetDataController.php](file:///d:/laragon/www/apis-hub-facade/app/Http/Controllers/Api/DashboardWidgetDataController.php):**
  - **`fanOutBreakdownSeries(...)`**: Takes a time-series or summary query result broken down by a dimension and fans it out into individual curves/series:
    - Ranks values by top $N \le 10$ according to selected sorting order (`value_desc`, `value_asc`, `alpha_asc`, `alpha_desc`).
    - Creates labeled series (e.g. `[Direct] Impressions - mobile`, `[Direct] Impressions - desktop`).
  - **Series Filters Normalization**:
    - `normalizeFiltersForPayload(...)` translates UI filter structures (`{dimension, operator, value}`) into tenant query filter clauses.
    - Injects series filters into Direct Metric, Derived Metric, and KPI queries.

---

## 4. UI & Dashboard Builder (`apis-hub-facade`)
- **[dashboard-builder.js](file:///d:/laragon/www/apis-hub-facade/resources/js/dashboards/dashboard-builder.js):**
  - Added methods `fetchBreakdownsForChannel()`, `getBreakdownsForSeries()`, `onSeriesBreakdownDimensionChange()`, `addSeriesFilter()`, `removeSeriesFilter()`, and `onWidgetRawSeriesDependencyChange()`.
  - Enforced single-metric restriction when a breakdown is configured on a series.
  - Persisted `breakdown` and `filters` across raw series and KPI variable cards in `openWidgetControls()` and `saveWidgetControls()`.
- **[dashboard-builder.blade.php](file:///d:/laragon/www/apis-hub-facade/resources/views/filament/app/pages/dashboard-builder.blade.php):**
  - **Breakdown Section**: Dimension selector, Max items (3, 5, 10), Sorting order (`Highest Value`, `Lowest Value`, `Alphabetical A-Z`, `Alphabetical Z-A`), and chart readability advisory badges.
  - **Filters Repeater**: Filter dimensions, operators (`in`, `not_in`, `eq`, `neq`, `like`, `is_null`, `is_not_null`), and value inputs with clear badges for active count.
- **[lang/es.json](file:///d:/laragon/www/apis-hub-facade/lang/es.json):**
  - Added Spanish translations for all breakdown, filter, and operator strings.

---

## Verification
- Ran PHP syntax linter on all modified backend and registry files.
- Executed `npm run build` in `apis-hub-facade` successfully (built in 9.43s with 106 modules transformed without errors).
