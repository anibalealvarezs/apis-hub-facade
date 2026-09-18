# Implementation Plan: Widget Series Breakdowns & Advanced Filtering

Provide support for series-level **Breakdowns** (categorical splitting of a single metric into multiple series, e.g., Top-N campaigns/devices over time) and **Advanced Filtering** (SQL-grade condition filtering per series on Direct Metrics, Derived Metrics, and KPIs) across the APIs Hub ecosystem.

---

## Architecture Overview & Design Principles

1. **Separation of Modeling vs. Projection**:
   - **Custom KPIs & Derived Metrics definitions remain untouched**. They define mathematical models with a fixed topological shape.
   - **Breakdown ($1 \rightarrow N$ fan-out)**: Configured exclusively at the **Widget Series level** for **Direct Metrics**. DMs and KPIs do NOT allow breakdown because their mathematical contracts expect fixed input/output series.
   - **Filtering ($N \rightarrow \text{subset of } N$)**: Configured at the **Widget Series level** and supported across **Direct Metrics, Derived Metrics, and KPIs**. Filtering reduces the underlying sample of data before aggregation or calculation without altering the model topology.

2. **Full Engine Utilization (`apis-hub`)**:
   - Leverage `apis-hub`'s `FilterConditionResolver` and `UniversalSqlStrategy` which already support:
     - Relational columns (`channeledCampaign`, `adGroup`, `ad`, `page`, `post`, etc.)
     - Dynamic EAV dimensions (`dimensions.<name>`, e.g., `dimensions.landing_page`, `dimensions.device`, `dimensions.country`)
     - Operators: `in` (multiple values), `eq` (equals), `neq` (not equal), `like` (contains), `is_null` (empty), `is_not_null` (not empty).

3. **Breakdown Mechanics**:
   - Available on Direct Metric series only (enforcing a single metric per broken-down series).
   - Query adds the dimension to `groupBy`: `groupBy: ['date', $breakdownDimension]` (or `[$breakdownDimension]` if lifetime).
   - Engine or Facade aggregates and sorts breakdown buckets:
     - **Ordering**: `value_desc` (highest to lowest), `value_asc` (lowest to highest), `alpha_asc` (A-Z), `alpha_desc` (Z-A).
     - **Top-N Slicing**: Slices the top $N$ breakdown values (max 10).
     - **Series Multiplier**: Fans out the single metric into $N$ distinct curve series: `{ key: "series_0_dim_val", label: "Spend - Campaign A", data: [...] }`.

---

## User Review Required

> [!IMPORTANT]
> **Cardinality Safeguard**: Breakdowns on time-series charts will enforce a strict limit of **$N \le 10$** series to avoid canvas performance degradation and unreadable "spaghetti charts".
> 
> **Single Metric Constraint**: A series configured with a breakdown can only select **1 metric**. If the user needs multiple metrics broken down, they add multiple series (or view via a Lifetime Table/Bar widget).

---

## Proposed Changes

Grouped by component layer:

### 1. Dimension Discovery & Registry Layer (`apis-hub-facade`)

#### [NEW] [ChannelBreakdownRegistry.php](file:///d:/laragon/www/apis-hub-facade/app/Services/Analytics/ChannelBreakdownRegistry.php)
Unifies the available dimensions proven in Data Explorer pages into a single source of truth for dashboard widgets.
- Defines per-channel and per-scope breakdown capabilities.
- Examples:
  - `facebook_marketing`:
    - `channeledCampaign` (Label: Campaign, Type: relational)
    - `adGroup` (Label: Ad Set, Type: relational)
    - `ad` (Label: Ad, Type: relational)
    - `dimensions.age` (Label: Age, Type: dimension)
    - `dimensions.gender` (Label: Gender, Type: dimension)
  - `google_analytics`:
    - `channeledCampaign` (Label: Campaign)
    - `channeledAdGroup` (Label: Ad Group)
    - `dimensions.sessionDefaultChannelGroup` (Label: Channel Group)
    - `dimensions.sessionSourceMedium` (Label: Source / Medium)
    - `dimensions.landing_page` (Label: Landing Page)
    - `dimensions.country` (Label: Country)
    - `dimensions.device` (Label: Device)
    - `event` (Label: Event Name)
  - `facebook_organic`, `google_search_console`, `shopify`, etc.
- Provides helper methods:
  - `getAvailableBreakdowns(string $channel, ?string $scope = null): array`
  - `isValidDimension(string $channel, string $dimension, ?string $scope = null): bool`
  - `getDimensionType(string $channel, string $dimension): string` (relational vs `dimensions.*`)

---

### 2. Series Data Model & Storage Schema (`apis-hub-facade`)

#### [MODIFY] [DashboardWidget.php](file:///d:/laragon/www/apis-hub-facade/app/Models/DashboardWidget.php)
Widgets store per-series configuration inside `source_config['series']` and/or `controls['series_config']`.
Each series object in `series` is enriched with:
```json
{
  "type": "metric",
  "channel": "facebook_marketing",
  "metric": "spend",
  
  "breakdown": {
    "dimension": "channeledCampaign",
    "limit": 5,
    "order": "value_desc"
  },

  "filters": [
    {
      "dimension": "channeledCampaign",
      "operator": "in",
      "value": ["Camp 1", "Camp 2"]
    },
    {
      "dimension": "dimensions.gender",
      "operator": "eq",
      "value": "female"
    }
  ]
}
```

---

### 3. Backend Query & Data Processing (`DashboardWidgetDataController.php`)

#### [MODIFY] [DashboardWidgetDataController.php](file:///d:/laragon/www/apis-hub-facade/app/Http/Controllers/Api/DashboardWidgetDataController.php)

1. **Filtering Injection**:
   - In `handleMultiSeriesSource()`, for each series (whether Direct Metric or Derived Metric component):
     - Extract `filters` from `series['filters']` or runtime controls `controls['series_filters'][$sIdx]`.
     - Inject resolved filters into `$payload['filters']` or `$payload['activeFilters']` forwarded to channel endpoints.
   - In `handleKpiSource()`:
     - For KPI variables that specify series filters, inject filter clauses into the variable metric query payloads sent to the engine.

2. **Breakdown Execution & Fan-Out**:
   - In `handleMultiSeriesSource()`:
     - Detect if `series['breakdown']` is configured.
     - When present:
       - Set query `groupBy` to `['date', $breakdownDimension]` (or `[$breakdownDimension]` if lifetime).
       - Forward query to the channel/engine endpoint.
       - Group returned rows by the breakdown dimension values.
       - Compute aggregate sums across the date range to determine ranking.
       - Sort according to `order` (`value_desc`, `value_asc`, `alpha_asc`, `alpha_desc`).
       - Slice Top $N$ (up to `limit`, max 10).
       - Generate $N$ distinct curve series in `$seriesCurves`, each with:
         - `label`: `[Series Label or Metric] - [Breakdown Value]`
         - `key`: `"series_{$sIdx}_{$dimValueSlug}"`
         - `metric`: `$cleanMetric`
         - `data`: Time-series dictionary `[ 'YYYY-MM-DD' => float ]`
         - Assigned a distinct color palette entry.

3. **Lifetime Categorical Widgets (Bar / Table)**:
   - When `granularity === 'lifetime'` and breakdown is active:
     - Output is formatted as categorical series or tabular rows where each breakdown value is a category / row label.

---

### 4. Engine Protocol & Strategy Layer (`apis-hub`)

#### [MODIFY] [UniversalSqlStrategy.php](file:///d:/laragon/www/apis-hub/src/Services/Aggregation/Strategies/UniversalSqlStrategy.php)
- Verify and ensure `buildFilterClause()` handles all desired operators cleanly:
  - Add `'not_in'` support (`$col NOT IN (...)`)
  - Add `'like'` support (`$col LIKE :$alias`)
- Ensure multi-column `groupBy: ['date', $breakdownDimension]` generates proper SQL `GROUP BY metric_date, dv_dim.value` (or relational foreign key/name) and correctly aliases the breakdown column in the SQL SELECT clause.

---

### 5. UI & Widget Builder Components (`apis-hub-facade`)

#### [MODIFY] [dashboard-builder.js](file:///d:/laragon/www/apis-hub-facade/resources/js/dashboards/dashboard-builder.js) & Series Modal
1. **Series Edit Drawer / Modal**:
   - **For Direct Metrics**:
     - Add **Breakdown Accordion**:
       - Dimension Select (populated from `ChannelBreakdownRegistry` based on selected channel/scope).
       - Limit input (slider or dropdown: 3, 5, 10).
       - Order Select (`Top Values (Highest First)`, `Bottom Values (Lowest First)`, `Alphabetical A-Z`, `Alphabetical Z-A`).
       - Constraint rule: If breakdown is selected, metrics selector locks to single metric.
   - **For All Series (Direct Metrics, DMs, KPIs)**:
     - Add **Filters Accordion / Repeater**:
       - "+ Add Filter" button.
       - Dimension select (from supported dimensions for that channel).
       - Operator select (`Is any of`, `Equals`, `Does not equal`, `Contains`, `Is set / not empty`, `Is empty`).
       - Value input (tag multi-select or text input depending on operator).

2. **Visual Recommendations Hint**:
   - When breakdown is active + `lifetime`: show subtle hint badge: *"Recommended: Bar Chart or Table"*.
   - When breakdown is active + `daily/weekly`: show subtle hint badge: *"Recommended: Line Chart or Stacked Area"*.

---

## Verification Plan

### Automated Tests
1. **Unit Tests for Registry**:
   - Test `ChannelBreakdownRegistryTest`: Ensure correct dimensions returned for GA4, FBM, FBO, Shopify, etc.
2. **Feature Tests for Filtering (`apis-hub-facade`)**:
   - `WidgetSeriesFilterTest`:
     - Test that series with `filters` properly translates into `filters` array in `aggregateChanneledPool` payload.
     - Test filtering on Direct Metric, Derived Metric, and Custom KPI.
     - Test operators (`in`, `eq`, `neq`, `like`, `is_null`, `is_not_null`).
3. **Feature Tests for Breakdowns (`apis-hub-facade`)**:
   - `WidgetSeriesBreakdownTest`:
     - Test fan-out: 1 series with `breakdown: channeledCampaign` returning 3 campaigns yields 3 separate series in the API response.
     - Test Top-N limit enforcement (e.g. asking for 5 when 20 exist returns top 5).
     - Test ordering (`value_desc` vs `value_asc` vs `alpha_asc`).
     - Test combination: 1 broken-down series + 1 aggregate series on the same widget.

### Manual Verification
1. Open Dashboard Builder.
2. Add a Line Chart with **Total Spend** (no breakdown) + **Spend broken down by Campaign (Top 3)**.
3. Verify that the rendered chart shows 4 lines (1 total + 3 campaign lines) with proper legends and colors.
4. Add a filter to exclude a specific campaign (`!= Brand_Campaign`) and verify the data re-aggregates accurately.
