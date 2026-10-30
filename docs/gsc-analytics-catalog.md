# Google Search Console analytics tree

Runtime index: `ChannelCapabilityRegistry::getAnalyticsTree('google_search_console')`
delegates to `GscAnalyticsCatalog`. Explorer selectors, widget search-type choices,
breakdown/granularity registries, metric choices and request validation consume it.
The tenant driver remains authoritative for ingestion and aggregation profiles.
The tree also includes temporal granularities from the shared granularity registry,
separately from entity/dimensional breakdowns.

## Property → search type → scope → breakdown → metric

| Search type | Meaning | Cached breakdowns | Metrics |
|---|---|---|---|
| `web` | Google Search All tab; default for existing reports | Query, page, country, device; separate appearance scope | Clicks, impressions, CTR, position |
| `image` | Image search | Same as Web | Same as Web |
| `video` | Video search | Same as Web | Same as Web |
| `news` | News tab in Google Search | Same as Web | Same as Web |
| `discover` | Discover feed | Page, country, date | Clicks, impressions, CTR |
| `googleNews` | Google News site and mobile apps | Page, country, date | Clicks, impressions, CTR |

This is the **implemented cache surface**, not a claim that Google lacks other
dimensions. Google News also provides device and appearance reports; Discover
provides appearance reporting. Those are currently ingestion gaps. Neither feed
provides query or average-position reporting. Do not invent position=0 or a query.

## Scopes and identities

- Property/account: `channeledAccount`; multiple selected properties use an `in`
  filter over internal account IDs. Existing authorization and asset scope remain.
- Search type: `dimensions.search_type`, distinct configuration signatures and
  metric dimension identities. Existing widgets/reports default to Web. A widget
  can select several types; a search-type breakdown defaults to all six.
- **Standard scope** (`non-searchAppearance`): `dimensions.searchAppearance=standard`.
  Profiles: `gsc_search_cube`, `gsc_page_flow`. Date, query, page, country and device
  describe its search-result cube; feeds currently cache date/page/country only.
- **Appearance scope** (`searchAppearance`): `dimensions.searchAppearance != standard`.
  Profile: `gsc_search_cube_by_appearance`. This is a separately cached, overlapping
  dataset, currently available for Web/Image/Video/News at date/appearance grain.
  Never sum it together with standard observations. Ordinary Explorer totals and
  charts use standard scope; its Appearance table uses appearance scope. Widgets
  grouped by appearance explicitly select the appearance scope.

Appearance scope does not acquire meaningful query/device/country detail merely
because those fields exist in the universal model. The current appearance pass
uses unknown/default entity values. New source dimensions require an ingestion
design that preserves the separation of scopes.

## Metrics and filters

- `clicks`, `impressions`: additive counts within one compatible scope/type.
- `ctr`: total clicks / total impressions, stored/displayed as a ratio; never sum
  row CTRs to produce a total.
- `position`: impression-weighted rank, valid only for search-result types.
- Breakdowns: `query`, `dimensions.page`, `country`, `device`,
  `dimensions.searchAppearance`, plus `dimensions.search_type` for comparisons.
- Semantic query filters: `dimensions.intent`, `dimensions.brand_relation`,
  `dimensions.business_relevance`; classification availability is version-gated.
  These do not apply to query-less feeds.
- Entity attribute filters use discovered `attributes.<relationship>.<field>`
  metadata. Query/device attributes cannot be used on the current feed cache.
- Search-type widget rules use a multiselect `in` predicate. Unsupported metrics
  and breakdowns for selected types produce validation errors, not fabricated
  zeros or a silent fallback to Web. Existing filters retain account/channel scope.

## Version, scheduling and history

Search-type controls require tenant v1.18.3+. Older tenants retain the previous
request shape. Data Explorer preserves type in its URL and browser cache identity,
waits one second after changes, and locks controls until requests settle.

The tenant's `search_types` setting governs source calls; absent overrides, it
fetches all six types for new scheduled windows. Report filters select cached data,
not a new Google request. The upgrade labels legacy cache as Web without fetching
missing history. Populate other types with a GSC-only replay without wiping metrics,
within Google's available history and the configured range. This UI change does
not initiate a sync.

Do not add Google News device grain to an ordinary replay without reconciling old
coarse feed records: both grains could contribute to the same totals. Complete the
feed device/appearance gaps in a versioned ingestion change, keeping appearance
observations separate from the standard scope.

Sources: [Search Analytics API](https://developers.google.com/webmaster-tools/v1/searchanalytics/query),
[Google News report](https://support.google.com/webmasters/answer/10083653?hl=en),
[Discover report](https://support.google.com/webmasters/answer/9216516?hl=en).
