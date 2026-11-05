# Marketing entity pages

**Status** · Phase 0, Phase 1, Phase 2 & Phase 3 Implemented (Catalog Entity Pages & Marketing Overview; Commerce Orders/Products Deferred to Phase 4)

**Delivery track** · Facade entity browsing and profiles, supported by worker and SDK read APIs.

Add a project navigation section called **Catalog**. Each supported entity type gets a searchable list and each record gets a durable detail page. The project scope selector offers **All assets** and individual asset groups. All assets means all assets visible to the current viewer in that project.

Use the same entity pages as destinations from Data Explorer, authenticated dashboards, and related records. Once these pages share a scope and stable references, a Marketing Overview can summarize activity across entity types and link into the relevant lists or records.

## Relationship to entity aggregation

Ship functional entity pages after establishing the shared read foundation below. Entity-derived measures have their own implementation phases and acceptance criteria in the linked plan. That track can proceed in parallel and enrich released pages as capabilities become available. Existing supported metric queries can populate profiles during the first release. Customer profiles with sparse data remain usable through their attributes, source records, relationships and recorded activity. [Entity measures aggregation plan](../../../apis-hub/docs/entity-measures-aggregation-plan.md)

### Shared read foundation

- **Entity references** distinguish a verified core identity from a source record and remain stable in deep links and API responses.
- **Authorized scope** identifies the project and permitted assets, optionally narrowed to an asset group, entity selection or channel. Entity reads can span channels. Apply scope before resolving visible source memberships, attributes or related records.
- **Typed filters** define field names, types and operators for the selected entity type, with explicit current-attribute and source-date semantics. Lists and later entity measures consume the same supported filter vocabulary.
- **Provenance and time** identify the source, owning asset, source occurrence or creation timestamp, latest synchronization time and coverage where known.
- **Capabilities** declare available fields, relationships, activity kinds and existing profile metrics. Pages enable each supported capability independently as worker versions and cached coverage allow.

Agree on these contracts before either track implements its consumers. The pages release depends on the read foundation and its supported read APIs. New entity measures join through capability discovery in later releases.

## Page design

- **Index** · Search and sort within the selected scope. Show source and status where applicable, plus a small set of columns specific to the entity type. Keep scope and filters in the URL so refresh and back navigation work.
- **Profile** · Show current attributes, their source where relevant, and separately labeled observed metrics with a date range and data coverage. An ad campaign profile could show its account, status, objective, spend, and related ad sets or ad groups.
- **Activity** · Show recorded events newest first, with an oldest-first option if needed. Include source, occurrence timestamp, and links to visible related records. Filter by event kind and date. Distinguish an empty timeline from incomplete history and a failed request. Use occurrence dates from source records and define display time zone consistently.

| Type | Profile examples | Activity examples |
| --- | --- | --- |
| Accounts, ad sets / ad groups, ads | Hierarchy, current attributes, performance at the entity's own reporting grain | Recorded milestones where available |
| Pages, posts, queries | Property or social account, URL or query text, scoped performance | Post publication where recorded, daily performance series for pages and queries |
| Customers | Current identity, verified source profiles, available marketing metrics | Lead submitted or recipient activity when linked and permitted. Add order history and value when commerce is available. |
| Orders | Status, amount and currency, customer, line items | Placed, refunded, fulfilled when those timestamps are recorded |
| Campaigns | Channel, status, dates, channel-appropriate performance | Launch or send, recorded status changes |
| Products | SKU, variants, current status, units sold | Linked orders or recorded catalog changes |
| Events | Event definition, sources, observed counts | Individual occurrences where available and authorized |

The worker's base `Event` represents a named event type. Keep **Events** as a directory of event types, and use **Activity** for individual occurrences on detail pages. A report with event counts can support an event profile even when the source supplies no individual occurrences.

## Current channel inventory

| Channel | Catalog candidates | Cached activity and metrics |
| --- | --- | --- |
| Meta Ads | Ad accounts, campaigns, ad sets, ads | Entity snapshots and performance metrics, including reported actions and lead counts. No individual action or ad change feed found in this sync path. |
| Google Ads | Ad accounts, campaigns, ad groups, ads | Scoped performance reports and aggregate conversions. No individual conversion or change history feed found in this sync path. |
| Meta Organic | Facebook Pages, Instagram accounts, posts/media | Content records and performance metrics. Use a recorded publication timestamp as a milestone where present. Engagement totals do not identify individual interactions. |
| Google Search Console | Properties, pages, queries | Dated search-performance rows, scoped to property and search type. The rows represent reported clicks and impressions, not individual searches. |
| GA4 | Properties, pages, named events | Event names and dated aggregate counts from the reporting API. The event matrix does not carry per-user occurrences. |
| Mailchimp | Audiences, campaigns, event types | Recipient-level activity in `channeled_events` feeds pre-aggregation. Includes sent-to, open, click, bounce and unsubscribe records where available. Connected-store order ingestion exists, but needs verified coverage before it can support a commerce rollout. |

This inventory describes repository sync paths. Check deployed versions, enabled scopes and actual project coverage before exposing a capability. Mailchimp occurrence details require audience-scoped access and an allowlisted response. Audit same-second click collisions, send timestamp semantics and late activity coverage before treating a timeline as complete. Use the recorded source timestamp and its quality, rather than the worker row creation time.

The separate Meta Leads driver writes lead submissions as channeled customer records with interaction metadata. Consider a later **Leads** pilot only for assets and records where access to that data is permitted and verified. Keep aggregate lead counts available independently.

## Existing code and proposed boundary

- The tenant worker already models the five entity types and their channeled records. The SDK has generic entity list/read/aggregate and channeled APIs. Audit their joins, pagination, account filters and response fields before deciding which queries to reuse. A base entity ID alone does not establish asset membership.
- Facade already has `CollaboratorAssetAccessService`, asset group sharing, and a coordinated tenant read path in `RemoteEngineService`. Reuse them. `ScopeChannelAssetAccess` checks requested account IDs when present. Catalog reads must also resolve a concrete authorized scope when the caller omits account IDs.
- Use a typed, allowlisted read contract across the worker, SDK and Facade for capability discovery, listing, profile and activity. Pass the authorized asset scope from Facade, allowing several channels and an optional channel filter, enforce it in worker queries, and return normalized display fields and provenance. Keep provider-specific mapping in existing driver capabilities or normalization paths. Avoid copying analytics records to Facade.
- Define a typed entity reference before building links. Start with an opaque reference backed by a stable internal identifier or a source-qualified key whose uniqueness has been verified. Keep the project and optional asset group in the route. Use provider IDs only with their verified namespaces. Audit email-based customer identity in the worker and order IDs that may collide across stores before extending beyond the pilot. Resolve a related link only when its target is visible in the same scope.

### Base records, source records and metric references

- A **base entity** is the candidate for the project-level profile where the worker has a verified shared identity. A **channeled entity** represents the record within a specific source and asset. Let users drill into each visible source record for its own attributes, hierarchy and metrics. Where a type has only channeled records, use those as the detail destinations.
- Keep provenance for the source reporting a metric separate from the entity it describes. A metric can contribute to a profile when it has a verified reference and its reporting asset is visible. A metric-only reference does not create a source profile. A name or dimension with no verified identity can appear as report context.
- GA4 currently has a third case worth surfacing explicitly. Campaign discovery emits GA4 channeled records derived from `sessionCampaignName`, with the GA4 property as account context. These are observed labels with sparse metadata. They do not establish a link to a native Google Ads campaign. The worker's campaign base upsert keys on the incoming platform ID, and the metric resolver queries by platform ID without channel/account predicates. Verify and test identity across same-name campaigns, properties, and providers before using these relationships for combined metrics or automatic cross-channel links.
- Scope the profile at every layer. Base records may have visible and hidden source rows, and a reporting asset may differ from the asset that owns the linked record. Resolve attributes, activity and metrics from the permitted records and reporting assets.

## Delivery phases

### 0. Audit data and permissions

- [x] For every type and candidate channel, document the record identity, asset ownership path, supported profile fields, metric definitions, activity timestamps, relationship keys and historical coverage. Check cached data for a representative project without exporting personal data.
- [x] Verify whether the same entity can appear in multiple assets, and how source records are associated with customers, orders and campaigns. Distinguish stored relationships from proposed identity matching. Agree on customer data permissions for project roles and group collaborators.
- [x] Validate the account → campaign → ad set/ad group → ad joins and reporting grains for Meta Ads and Google Ads. Select the first Ads channel based on representative tenant coverage.
- [x] Specify and validate the shared read foundation across the worker, SDK and Facade, including stable references, authorized scope, typed filters, provenance and time semantics. Publish the agreement for use by both delivery tracks.

### 1. Build the read contract and the first vertical slice

- [x] Implement worker queries, typed SDK methods and Facade list/detail pages for ad accounts, campaigns, ad sets or ad groups, and ads. Use the project-wide and asset-group modes from the start. Intersect group membership with the actor's allowed assets, and make an empty intersection return an empty result. Check authorization for lists, search results, counts, profiles, metrics, timelines and related links.
- [x] Link each Ads record to its parent and children. Show dated performance as a series. Show Activity where there are verified milestones or occurrences. Use stable activity IDs and cursor pagination with a timestamp and ID tie-breaker when an occurrence feed is available.
- [x] Query each Ads level at its supported reporting grain. Keep currency explicit, return window and freshness metadata, and distinguish partial history from measured zero.
- [x] Gate navigation and deep links on tenant capability. Distinguish unsupported versions, empty results and temporarily unavailable data. Release in worker → SDK → Facade order.
- [x] Release the first list, detail, source-record drilldown and related links using supported read APIs and existing metric queries. Activity sections expose verified occurrences or a coverage-aware empty state.

### 2. Expand types and link from existing surfaces

- [x] Expand the Ads hierarchy to the second Ads channel, then add Meta Organic accounts/pages/posts and Search Console properties/pages/queries. Keep queries scoped to property and search type.
- [x] Add Events with GA4 event-type profiles and Mailchimp activity on campaign pages. Start Mailchimp occurrence display with permitted fields and validate identity, timestamp and coverage. Add person links only after verifying the recipient relationship and permissions.
- [x] Add Customers as an early Catalog destination after the Ads pilot, independently of commerce. Start with a base customer profile, permitted source records, and activity from verified relationships. Lead submissions can contribute where access and identity are verified. Keep Mailchimp recipient activity campaign-scoped until the recipient-to-customer join is validated. Support partial and empty states for projects with sparse customer coverage.
- [x] Defer Orders, Products, and customer commerce metrics until Shopify or another commerce integration has verified production coverage and relationship semantics.
- [x] Add a shared Facade link renderer for typed entity references. Start with related records and Explorer tables, then migrate authenticated dashboard widgets, drilldowns and other surfaces that carry verified IDs. Keep display text for totals and other rows without a resolvable entity. Inventory remaining surfaces and preserve their scope on navigation.
- [x] Treat public and shared dashboards as a separate access decision. Keep this rollout within authenticated pages and check cache separation by project, authorization scope and query.

### 3. Compose the Marketing Overview

- [x] Add a project overview with the same scope selector, a shared date window and drilldowns into Catalog. Start with supported advertising, content, search and email indicators and recent activity. Declare data coverage per metric and deduplicate overlapping asset membership. Add commerce and cross-source customer views when their prerequisites are met.
- [x] Add entity-derived summary measures through the companion aggregation track's capabilities when available. Keep this enrichment separately releasable from entity browsing.

## Release checks

- [x] An authorized viewer can navigate from a list to a detail page, open a related record, and copy a deep link that restores the same scope.
- [x] The first pages work against a tenant exposing the read foundation and existing profile metrics while entity-measure capabilities are unavailable. Customer profiles with sparse coverage remain navigable.
- [x] A collaborator assigned to one group cannot discover another group's records, PII, counts, timeline entries or related names through direct URLs, search, pagination or cached responses. Test overlapping groups and access revocation.
- [x] Known fixtures reconcile profile numbers and activity against cached source records. Test repeated source IDs in different assets, same-name GA4 labels, a metric reported by a different asset, mixed currencies, missing timestamps, partial history and repeat syncs.
- [x] Measure list and timeline queries on a representative large tenant. Confirm bounded pagination, indexed access paths, no per-row remote requests, and correct behavior on tenant errors.
