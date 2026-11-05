# Catalog Read Foundation & Phase 0 Audit

This document defines the shared read foundation contract and records the Phase 0 data & ownership audit for the **Marketing Entity Pages** ([`marketing-entity-pages.md`](file:///d:/laragon/www/apis-hub-facade/docs/plans/marketing-entity-pages.md)) and the companion **Entity Measures Aggregation** track ([`entity-measures-aggregation-plan.md`](file:///d:/laragon/www/apis-hub/docs/entity-measures-aggregation-plan.md)).

---

## 1. Phase 0 Audit: Candidate Entity Types & Channels

### 1.1 Identity, Asset Ownership, Profile Fields & Relationships

| Catalog Entity Type | Underlying Tables | Primary / Unique Identity | Asset Ownership Path | Profile Fields | Relationships | Metric Grain & Definitions | Activity / Milestone Sources |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **`accounts`** | `channeled_accounts` (joined with `accounts`, `channels`) | `channeled_accounts.id` (unique `(platform_id, channel)`) | Direct: `channeled_accounts.id` is the asset boundary (`platform_id` matched against Facade `sync_config` / `AssetGroupItem.asset_id`) | `name`, `platform_id`, `channel`, `type`, `enabled`, `currency` (`data->>'currency'` or `data->>'currency_code'`), `timezone` (`data->>'timezone_name'` / `data->>'time_zone'`), `platform_created_at`, `created_at`, `updated_at` | Parent: Base `Account` (`account_id`). Children: `campaigns`, `ad-sets` / `ad-groups`, `ads`, `pages`, `posts`, `events` | Grain: `mc.channeled_account_id = :id AND mc.channeled_campaign_id IS NULL AND mc.channeled_ad_group_id IS NULL AND mc.channeled_ad_id IS NULL AND mc.page_id IS NULL AND mc.post_id IS NULL AND mc.query_id IS NULL AND mc.channeled_event_id IS NULL` (or channel-level rollup when account-grain rows are not separately stored). Metrics: `spend`, `impressions`, `clicks`, `reach`, `conversions`, `purchases`, `purchase_value`, `leads`, `ctr`, `cpc`, `cpm`, `roas_purchase` | `platform_created_at`, `created_at`, `updated_at`, sync telemetry status |
| **`campaigns`** | `channeled_campaigns` (joined with `campaigns`, `channeled_accounts`, `channels`) | `channeled_campaigns.id` (unique `(platform_id, channeled_account_id)`) | `channeled_campaigns.channeled_account_id` -> `channeled_accounts.id`. *(Note: Base `campaigns` is upserted by `campaign_id` globally without channel/account namespace and is also populated by GA4 `sessionCampaignName`; Ads views MUST anchor on `channeled_campaigns` scoped by `channeled_account_id`)* | `name` (`campaigns.name` or `channeled_campaigns.data->>'name'`), `platform_id`, `status` (`CampaignStatus`), `objective` (`CampaignObjective`), `buying_type` (`CampaignBuyingType`), `type`, `budget`, `start_date` (`campaigns.start_date`), `end_date` (`campaigns.end_date`), `currency` (inherited from owning `channeled_accounts.data` or campaign `data`) | Owning Asset: `ChanneledAccount` (`channeled_account_id`). Source sibling: Base `Campaign` (`campaign_id`). Children: `ad-sets` (`facebook_marketing`), `ad-groups` (`google_ads`), `ads` | Grain: `mc.channeled_campaign_id = :id AND mc.channeled_ad_group_id IS NULL AND mc.channeled_ad_id IS NULL AND mc.dimension_set_id IS NULL`. Metrics: `spend`, `impressions`, `clicks`, `reach`, `conversions`, `purchases`, `purchase_value`, `leads`, `ctr`, `cpc`, `cpm`, `roas_purchase`, `cost_per_conversion` | `campaigns.start_date`, `campaigns.end_date`, `platform_created_at`, `created_at`, `updated_at` (Milestone-only coverage; providers do not ingest raw change logs) |
| **`ad-sets`** | `channeled_ad_groups` (`channel = facebook_marketing`) | `channeled_ad_groups.id` (unique `(platform_id, channeled_account_id)`) | `channeled_ad_groups.channeled_account_id` -> `channeled_accounts.id` | `name`, `platform_id`, `status` (`CampaignStatus`), `optimization_goal` (`OptimizationGoal`), `billing_event` (`BillingEvent`), `start_date`, `end_date`, `targeting` (JSON summary), `budget` (`data->>'daily_budget'` / `data->>'lifetime_budget'`) | Owning Asset: `ChanneledAccount`. Parent: `ChanneledCampaign` (`channeled_campaign_id`). Children: `ads` (`channeled_ads`) | Grain: `mc.channeled_ad_group_id = :id AND mc.channeled_ad_id IS NULL AND mc.dimension_set_id IS NULL`. Metrics: `spend`, `impressions`, `clicks`, `reach`, `conversions`, `purchases`, `purchase_value`, `leads`, `ctr`, `cpc`, `cpm`, `roas_purchase` | `start_date`, `end_date`, `platform_created_at`, `created_at`, `updated_at` (Milestone-only coverage) |
| **`ad-groups`** | `channeled_ad_groups` (`channel = google_ads`) | `channeled_ad_groups.id` (unique `(platform_id, channeled_account_id)`) | `channeled_ad_groups.channeled_account_id` -> `channeled_accounts.id` | `name`, `platform_id`, `status` (`CampaignStatus`), `optimization_goal`, `billing_event`, `start_date`, `end_date`, `type` (`data->>'type'`), `cpc_bid` (`data->>'cpc_bid_micros'`) | Owning Asset: `ChanneledAccount`. Parent: `ChanneledCampaign` (`channeled_campaign_id`). Children: `ads` (`channeled_ads`) | Grain: `mc.channeled_ad_group_id = :id AND mc.channeled_ad_id IS NULL`. Metrics: `spend`, `impressions`, `clicks`, `conversions`, `ctr`, `cpc`, `cpm`, `cost_per_conversion`, `roas_purchase` | `start_date`, `end_date`, `platform_created_at`, `created_at`, `updated_at` (Milestone-only coverage) |
| **`ads`** | `channeled_ads` (joined with `channeled_ad_groups`, `channeled_campaigns`, `campaigns`, `channeled_accounts`, `creatives`) | `channeled_ads.id` (unique `(platform_id, channeled_campaign_id)`) | `channeled_ads.channeled_account_id` -> `channeled_accounts.id` | `name`, `platform_id`, `status` (`CampaignStatus`), `creative` (`creatives.name`, `creatives.creative_id`, `creatives.data`), `data` | Owning Asset: `ChanneledAccount`. Parents: `ChanneledCampaign` (`channeled_campaign_id`), `ChanneledAdGroup` (`channeled_ad_group_id`), `Creative` (`creative_id`) | Grain: `mc.channeled_ad_id = :id AND mc.dimension_set_id IS NULL`. Metrics: `spend`, `impressions`, `clicks`, `reach`, `conversions`, `purchases`, `purchase_value`, `leads`, `ctr`, `cpc`, `cpm`, `roas_purchase` | `platform_created_at`, `created_at`, `updated_at` (Milestone-only coverage) |
| **`customers`** *(Phase 2)* | `customers`, `channeled_customers` | `customers.id` (`email` unique) / `channeled_customers.id` (`(platform_id, channel)`) | Indirect: `channeled_customers` has `channel` but no direct `channeled_account_id` column; ownership must resolve via `metric_configs.channeled_account_id` or `channeled_orders.channeled_store_id` / single-account channel binding | `email`, `platform_id`, `channel`, `lead_data`, `data`, `platform_created_at` | Source records: `Customer` <-> `ChanneledCustomer` across channels (Mailchimp, Klaviyo, Shopify); linked `ChanneledOrder` and `MetricConfig` | Email engagement (`sends`, `opens`, `clicks`, `bounces`, `unsubscribes`) and order metrics where linked | `platform_created_at`, metric event timestamps (`channeled_metrics`), order timestamps |
| **`pages`** *(Phase 3)* | `pages` | `pages.id` (`canonical_id` unique) | `pages.account_id` or via `metric_configs.channeled_account_id` (GSC sites, GA4 properties, Meta Organic pages) | `url`, `canonical_id`, `title`, `hostname`, `platform_id`, `type`, `published_at`, `data` | Owning asset via `metric_configs.channeled_account_id`; related `posts` (`posts.page_id`) and `queries` (via `metric_configs`) | GSC (`clicks`, `impressions`, `ctr`, `position`), GA4 (`screenPageViews`, `sessions`, `bounceRate`), Meta Organic (`views`, `likes`, `reach`) | `published_at`, `created_at`, `updated_at` |
| **`posts`** *(Phase 3)* | `posts` | `posts.id` (unique `(post_id, page_id, account_id, channeled_account_id)`) | Direct: `posts.channeled_account_id` -> `channeled_accounts.id` | `post_id`, `data` (caption/message, media_type, permalink, timestamp) | Owning Asset: `ChanneledAccount` (`channeled_account_id`), `Page` (`page_id`) | Meta Organic post metrics (`likes`, `comments`, `shares`, `saves`, `reach`, `views`, `total_interactions`) | Post publish timestamp (`data->>'timestamp'` / `data->>'created_time'`), `created_at` |
| **`queries`** *(Phase 3)* | `queries`, `query_classifications`, `account_query_classifications` | `queries.id` (`query` unique) | Scoped via `metric_configs.channeled_account_id` (and `account_query_classifications.channeled_account_id`) | `query`, `intent`, `brand_relation`, `business_relevance` | Related `pages` and `channeled_accounts` via `metric_configs` | GSC (`clicks`, `impressions`, `ctr`, `position`), Google Ads search term (`spend`, `clicks`, `impressions`, `conversions`) | First/last observed `metric_date`, classification timestamps |
| **`events`** *(Phase 3)* | `events`, `channeled_events` | `channeled_events.id` (unique `(platform_id, channeled_account_id)`) | Direct: `channeled_events.channeled_account_id` -> `channeled_accounts.id` | `name` (`events.name`), `platform_id`, `data`, `platform_created_at` | Owning Asset: `ChanneledAccount` (`channeled_account_id`), Base `Event` (`event_id`) | GA4 (`eventCount`, `conversions`, `totalRevenue`), Mailchimp/Klaviyo event counts | `platform_created_at`, `created_at`, observed `metric_date` |
| **`orders` / `products`** *(Phase 4)* | `orders`, `channeled_orders`, `products`, `channeled_products` | `channeled_orders.id`, `channeled_products.id` | `channeled_orders.channeled_store_id` / channel asset mapping (deferred until verified commerce channel coverage) | Order/product attributes, `sku`, `data` | `ChanneledCustomer`, `ChanneledProduct`, `ChanneledProductVariant`, `ChanneledDiscount` | Commerce revenue, quantity, order counts | `platform_created_at`, `created_at` |

### 1.2 Customer Data Permission Policy

Per operator decision in Phase 0:
- Viewing the `customers` catalog and any Customer PII (`email`, `lead_data`, customer addresses/phone numbers) requires:
  1. `view_data` permission in the project, **AND**
  2. **Unrestricted project asset access** (`CollaboratorAssetAccessService::isUnrestricted($project, $userId) === true`) or an explicit `view_customer_pii` permission.
- Collaborators restricted to specific Asset Groups cannot view Customer PII or global `customers` rows that are not strictly bound to their permitted `channeled_account_id` set, and email addresses are hidden/redacted from restricted collaborators unless explicitly permitted.

---

## 2. Shared Read Foundation Contract

### 2.1 Typed Entity Reference Format

Every catalog record is addressed by a deterministic, URL-safe, source-qualified reference:

```text
{layer}:{channel}:{entity_type}:{id}
```

- **`layer`**: `channeled` (platform-scoped entity in `channeled_*`) or `base` (cross-channel entity in base table).
- **`channel`**: canonical channel slug (e.g. `facebook_marketing`, `google_ads`, `google_analytics`, `google_search_console`, `facebook_organic`, `mailchimp`, or `global` for `base` layer).
- **`entity_type`**: canonical singular/plural catalog slug (`accounts`, `campaigns`, `ad-sets`, `ad-groups`, `ads`, `pages`, `posts`, `queries`, `customers`, `orders`, `products`, `events`).
- **`id`**: positive integer primary key in the tenant database.

Examples:
- Meta Ads Account `#42`: `channeled:facebook_marketing:accounts:42`
- Google Ads Campaign `#105`: `channeled:google_ads:campaigns:105`
- Meta Ads Ad Set `#310`: `channeled:facebook_marketing:ad-sets:310`
- Google Ads Ad Group `#412`: `channeled:google_ads:ad-groups:412`
- Meta Ads Ad `#890`: `channeled:facebook_marketing:ads:890`

For URL paths (`/catalog/{entity}/{record}`), references may be passed directly (URL-encoded or colon-delimited) and validated strictly against the route's `{entity}` slug so a reference for `campaigns` cannot be resolved on `/catalog/ads/{record}`.

### 2.2 Authorized Scope Contract

Every Catalog read request from Facade to Worker (`POST /api/catalog/*`) carries an explicit `authorized_scope` object resolved server-side by `CatalogScopeResolver` using `CollaboratorAssetAccessService`:

```json
{
  "authorized_scope": {
    "mode": "all | asset_group",
    "asset_group_id": null,
    "is_unrestricted": true,
    "can_view_customer_pii": true,
    "channels": {
      "facebook_marketing": {
        "account_ids": [12, 15],
        "platform_ids": ["111222333", "act_111222333"]
      },
      "google_ads": {
        "account_ids": [21],
        "platform_ids": ["8266857248"]
      }
    }
  }
}
```

**Scope Invariants:**
1. **Server-Side Resolution Only**: Facade never trusts browser-supplied `account_ids` or `platform_ids`. Browser URL parameters only select `scope` (`all` or `<asset_group_id>`) and optional `channel`.
2. **Intersection Enforcement**:
   - When `scope = all`, `authorized_scope.channels` contains only the enabled project assets the authenticated viewer is allowed to access per channel.
   - When `scope = <asset_group_id>`, `CatalogScopeResolver` verifies `canAccessGroup($project, $userId, $groupId)` and intersects the group's active asset IDs with the viewer's allowed asset IDs.
3. **Fail-Closed Empty Scope**: If `authorized_scope.channels` is empty (or the selected channel has neither `account_ids` nor `platform_ids`), the Worker immediately returns an empty result (`data: []`, `meta.total: 0`) without executing unfiltered queries.
4. **Related-Record & Count Isolation**: Parent links, child summaries/counts, source-record siblings, performance series, and activity entries are filtered by the exact same permitted `channeled_account_id` set. If a related record belongs to an asset outside the active scope, its reference, name, and metrics are omitted/redacted.
5. **Cache Isolation**: `RemoteEngineService` includes a canonical hash of `authorized_scope` in the `executeRead` payload and `authorization_scope` key, preventing cross-collaborator or cross-asset-group cache hits.

### 2.3 Typed Filter & Query Vocabulary

Catalog list and activity endpoints accept only allowlisted filter keys and operators—never raw SQL identifiers or expressions:

- **`entity_type`**: required allowlisted catalog entity slug.
- **`channel`**: optional channel slug (`facebook_marketing`, `google_ads`, etc.), intersected with `authorized_scope.channels`.
- **`search`**: optional plain-text query matched case-insensitively against allowlisted name/identifier columns (`name`, `platform_id`, `creative_id`) using bound parameters (`LIKE :search`).
- **`filters`**:
  - `status`: string or list of `CampaignStatus` values (`ACTIVE`, `PAUSED`, `DELETED`, `ARCHIVED`).
  - `objective`: string or list of `CampaignObjective` values (`campaigns`).
  - `buying_type`: string or list of `CampaignBuyingType` values (`campaigns`).
  - `optimization_goal`: string or list of `OptimizationGoal` values (`ad-sets`, `ad-groups`).
  - `billing_event`: string or list of `BillingEvent` values (`ad-sets`, `ad-groups`).
  - `account_id`: integer or list of integers (must be a subset of permitted `channeled_account_id`s).
  - `parent_reference`: typed entity reference (e.g., filter `ad-sets` or `ads` by parent campaign/ad-set reference).
  - `enabled`: boolean (`accounts`).
- **`sort` & `dir`**: allowlisted sort field per entity type (`name`, `status`, `platform_id`, `budget`, `start_date`, `end_date`, `updated_at`, `id`) + direction (`ASC` | `DESC`), always with `id ASC/DESC` as deterministic tie-breaker.
- **`page` & `per_page`**: bounded offset pagination (`per_page` default `25`, max `100`).
- **`date_range`** (for `profile` metrics and `activity`): `start_date` (`YYYY-MM-DD`) and `end_date` (`YYYY-MM-DD`), defaulting to the last 30 days.

### 2.4 Provenance, Currency & Time Semantics

Every record returned by `list`, `profile`, and `activity` includes standardized provenance and time metadata:
- **`provenance`**:
  - `channel`: canonical channel slug (e.g. `facebook_marketing`, `google_ads`)
  - `channel_label`: human-readable label (`Meta Ads`, `Google Ads`)
  - `account_id`: internal `channeled_accounts.id`
  - `account_reference`: `channeled:{channel}:accounts:{account_id}`
  - `account_name`: owning asset display name
  - `account_platform_id`: external asset ID
  - `platform_id`: record's external provider ID
  - `last_synced_at`: ISO-8601 timestamp from `updated_at`
- **`currency`**:
  - Extracted from owning `channeled_accounts.data` (`currency` / `currency_code`) or record `data` (`currency`), or `metrics.metadata.currency`.
  - Monetary fields (`budget`, `spend`, `purchase_value`, `cpc`, `cpm`, `cost_per_conversion`, `cost_per_purchase`, `cost_per_lead`) always carry explicit currency code (or `null` when unknown, never silently summed across mixed currencies).
- **`time_semantics`**:
  - `platform_created_at`: source creation time when provided by the platform (`nullable`, `quality: source_timestamp | unavailable`).
  - `start_date` / `end_date`: scheduled flight dates on campaigns and ad sets / ad groups (`quality: scheduled_window`).
  - `updated_at`: last local sync persistence timestamp (`quality: sync_observed_at`).
  - `metric_date`: daily reporting date (`YYYY-MM-DD`) in the asset's reporting timezone (`quality: reporting_day`).

### 2.5 Capability Discovery Schema (`POST /api/catalog/capabilities`)

Allows Facade and future consumers (including Entity Measures Aggregation) to discover tenant support without hardcoded assumptions:

```json
{
  "status": "success",
  "data": {
    "contract_version": 1,
    "supported_operations": ["capabilities", "list", "profile", "activity"],
    "entities": {
      "accounts": {
        "supported": true,
        "phase": 1,
        "channels": ["facebook_marketing", "google_ads"],
        "sortable": ["name", "platform_id", "type", "enabled", "updated_at", "id"],
        "filters": ["channel", "enabled", "search"],
        "metrics": ["spend", "impressions", "clicks", "conversions", "ctr", "cpc", "cpm", "roas_purchase"],
        "activity_coverage": "milestones_only"
      }
    }
  }
}
```
