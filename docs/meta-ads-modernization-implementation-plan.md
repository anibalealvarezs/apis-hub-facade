# Meta Ads Channel Modernization Implementation Plan

## 1. Overview & Objectives

Modernize the Meta Ads integration across `facebook-graph-api`, `meta-hub-driver`, `apis-hub`, and `apis-hub-facade` to:
1. **Safely extract high-value canonical business metrics** (Purchases, Purchase Value, Leads, Cost Per Purchase, Cost Per Lead) into first-class `Metric` rows alongside existing KPIs.
2. **Support Independent Breakdown Passes** (Global un-broken totals, Demographics, Placement, Geography) to respect Meta API combinatorial limits and prevent differential privacy / thresholding data loss.
3. **Keep `Creative` as a visual Quality of Life (QoL) asset** linked to `ChanneledAd`, while keeping metric aggregation strictly focused at the Ad level (leaving `creative_id` null in `MetricConfig`).
4. **Make `MetricSet` fully configurable** (`basic`, `key`, `full`, `custom`) at the project/channel sync configuration level.
5. **Dynamically adapt the Facade UI** (Data Explorer & Widgets) based on the project's enabled breakdown dimensions.

---

## 2. Core Architectural Principles & Decisions

### 2.1. First-Class Normalized Metrics vs. JSON Metadata
* **Rule:** The `AgnosticPreAggregationEngine` operates strictly on indexed scalar `Metric::$value` floats. Complex JSON in `Metric::$metadata` cannot be efficiently indexed, filtered, or aggregated in SQL.
* **Decision:** Extract the essential business KPIs directly from Meta's `actions`, `action_values`, and `cost_per_action_type` arrays into normalized `Metric` records.

### 2.2. Safe List of Extracted Metrics
The following metrics will be supported across `KEY` and `FULL` sets:
* **Core Delivery & Financials:** `spend`, `impressions`, `reach`, `clicks`, `ctr`, `cpc`, `cpm`, `frequency`
* **Performance / Conversions:**
  * `results` & `cost_per_result` (Existing normalized fallbacks)
  * `purchase_roas` (Float ROAS ratio)
  * `purchases` (Count of omni / pixel / on-site purchases)
  * `purchase_value` (Total currency value of purchases)
  * `cost_per_purchase` (Cost per purchase action)
  * `leads` (Count of lead form / pixel / on-site leads)
  * `cost_per_lead` (Cost per lead action)
* **Diagnostic & Video (FULL set only):**
  * `unique_clicks`, `unique_ctr`
  * `video_play_actions`, `video_thruplay_watched_actions`, `cost_per_thruplay`
  * `video_p25_watched_actions`, `video_p50_watched_actions`, `video_p75_watched_actions`, `video_p100_watched_actions`

### 2.3. Multi-Pass Independent Breakdowns
To eliminate Meta's `(#100) Cannot combine breakdowns` error and preserve aggregate truth:
* **Pass 0 (Always Run):** Unbroken data (`breakdowns = []`, `dimensions_hash = 'GLOBAL'`). Guarantees that top-line KPIs are never reduced by Meta's privacy thresholding.
* **Pass 1 (Demographics - Optional):** `breakdowns = ['age', 'gender']`
* **Pass 2 (Placement - Optional):** `breakdowns = ['publisher_platform', 'platform_position']`
* **Pass 3 (Geography - Optional):** `breakdowns = ['country']`

### 2.4. Creative Handling (QoL Only)
* `ChanneledAd` links to `Creative` via `creative_id` foreign key.
* Creative assets (`name`, thumbnail URL, headline/body) are stored in `creatives` and `channeled_ads` for visual preview in the Facade UI.
* In [MetricConfig](file:///d:/laragon/www/apis-hub/src/Entities/Analytics/MetricConfig.php), the `creative_id` column remains `NULL` for ad-level metrics. Metrics will remain keyed by `[channel, channeled_account, channeled_campaign, channeled_ad_group, channeled_ad, dimension_set, name]`.

---

## 3. Step-by-Step Implementation Steps

### Phase 1: `facebook-graph-api` (SDK Level)
1. **Update [MetricSet](file:///d:/laragon/www/facebook-graph-api/src/Enums/MetricSet.php):**
   * Ensure `MetricSet::KEY` and `MetricSet::FULL` request `action_values`, `cost_per_action_type`, and video metrics.
2. **Define Breakdown Enums:**
   * Create or update enum for supported breakdown passes: `DEMOGRAPHICS`, `PLACEMENT`, `GEOGRAPHY`.

### Phase 2: `meta-hub-driver` (Normalization & Sync Engine)
1. **Extend [FacebookMarketingMetricConvert](file:///d:/laragon/www/meta-hub-driver/src/Conversions/FacebookMarketingMetricConvert.php):**
   * Add extraction logic in the converter for parsed action metrics:
     * Loop through `$row['actions']` to extract `purchases` and `leads`.
     * Loop through `$row['action_values']` to extract `purchase_value`.
     * Loop through `$row['cost_per_action_type']` to extract `cost_per_purchase` and `cost_per_lead`.
   * Add these extracted items into the metric generation loop with their respective scalar values.
2. **Implement Multi-Pass Sync in [FacebookMarketingDriver](file:///d:/laragon/www/meta-hub-driver/src/Drivers/FacebookMarketingDriver.php):**
   * In `syncInsights()`, read configured breakdown passes from `$channeledAccount->getSettings()['analytics']['breakdown_passes']`.
   * Execute Pass 0 (Unbroken) unconditionally.
   * For each enabled additional pass, request insights from Meta with that specific breakdown list and convert using the matching dimensions.
3. **Configurable `MetricSet`:**
   * Read `metric_set` from account settings (defaulting to `MetricSet::KEY`).
   * Pass down to `FacebookMarketingMetricConvert`.
4. **Ad Creative Metadata Enrichment:**
   * During entity sync (`FacebookEntitySync`), ensure the ad creative's `thumbnail_url`, `title`, and `body` are synced to the `Creative` record and associated to `ChanneledAd`.
5. **Standardized Driver Surface Manifest (`docs/surface-manifest.md`):**
   * Create `docs/surface-manifest.md` in `meta-hub-driver` displaying the visual status tree:
     * Entity Hierarchy: `AdAccount` → `Campaign` → `AdSet` → `Ad` (with `Creative` QoL preview).
     * Metric Sets & Tiers: `BASIC`, `KEY`, `FULL`, `CUSTOM` metric trees.
     * Breakdown Passes: Pass 0 (Unbroken), Pass 1 (Demographics), Pass 2 (Placements), Pass 3 (Geography).
     * Action Mappings: Table of canonical normalized metrics vs raw Meta action types.

### Phase 3: `apis-hub` (Core Engine & Aggregations)
1. **Audit [MetricConfigRepository](file:///d:/laragon/www/apis-hub/src/Repositories/MetricConfigRepository.php) & Signature:**
   * Verify that new metric names (`purchases`, `purchase_value`, `leads`, `cost_per_purchase`, `cost_per_lead`, `video_*`) generate clean, unique signatures.
   * Confirm `creative_id` remains `null` for `ChanneledAd` metric configs.
2. **Pre-Aggregation Engine Validation:**
   * Verify that [AgnosticPreAggregationEngine](file:///d:/laragon/www/apis-hub/src/Services/Aggregation/AgnosticPreAggregationEngine.php) rolls up the new metric names accurately without requiring changes to schema or table partitions.

### Phase 4: `apis-hub-facade` (UI & User Configuration)
1. **Project / Channel Configuration Form:**
   * Add settings in the Channel Configuration / Data Sync modal:
     * **Metric Set Selector:** `Basic`, `Key` (Default), `Full`.
     * **Breakdown Passes Toggle:** Checkboxes for `Demographics (Age & Gender)`, `Placements (Facebook / Instagram / Audience Network)`, `Geography (Country)`.
2. **Dynamic Data Explorer Adaptation:**
   * In [FacebookMarketingController](file:///d:/laragon/www/apis-hub-facade/app/Http/Controllers/Api/FacebookMarketingController.php) / Livewire page:
     * Dynamically filter the Dimension dropdown to only present dimensions that correspond to the passes enabled for that project.
     * Add new primary KPI tiles (`Purchases`, `Purchase Value`, `Leads`) next to `Spend`, `Impressions`, and `Clicks`.
3. **Ad Creative Preview QoL:**
   * In the Ad Breakdown table or drawer, render the ad's headline, copy snippet, and creative thumbnail image linked from `Creative::$data`.

---

## 4. Verification & Validation Plan
1. **Unit & Integration Tests:**
   * Test `FacebookMarketingMetricConvertTest` with mock rows containing complex `actions`, `action_values`, and `cost_per_action_type` payloads. Verify that `purchases`, `leads`, `purchase_value`, and `cost_per_purchase` are generated as scalar metrics.
   * Test independent breakdown passes with simulated Meta API responses.
2. **Rate Limit & Privacy Thresholding Check:**
   * Confirm that Pass 0 (Unbroken) returns identical spend and impression sums to Ads Manager even when Demographics breakdowns are empty.
3. **Facade UI Verification:**
   * Verify Data Explorer renders without errors when switching between unbroken and demographic views.
