<?php

declare(strict_types=1);

namespace App\Services;

class OpenApiSpecificationService
{
    /**
     * Build the comprehensive, public OpenAPI 3.1 specification.
     * All sensitive information is omitted:
     * - Host placeholder: https://{subdomain}.apis-hub.cloud (default: your-project)
     * - API Key placeholder: your_api_key_here
     * - Zero references to internal admin keys, internal ports, or infrastructure mechanics.
     * Organized logically into user-facing topics:
     * - Authentication & Security
     * - System Health & Heartbeat
     * - Data Synchronization
     * - Entity Management & Account Discovery
     * - Channel Analytics & Aggregations
     * - Omnichannel Analytics
     * - Error Handling & Schemas
     * - Rate Limits & Quotas
     */
    public function buildSpecification(): array
    {
        return [
            'openapi' => '3.1.0',
            'info' => [
                'title' => 'APIs Hub RESTful Analytical API',
                'version' => '1.0.0',
                'description' => "Welcome to the APIs Hub Developer Reference.\n\n### Comprehensive Topic Guide\n\n- **Authentication & Security**: Authenticate every request by supplying your project API key via the `X-API-KEY` header (or alternatively `Authorization: Bearer <key>`). Keys are managed and rotated with zero downtime in your project dashboard. Public API Keys are strictly read-only.\n- **Project Subdomains & Routing**: All requests are routed directly to your isolated project container node using the host format `https://{subdomain}.apis-hub.cloud`.\n- **Assets & Channeled Account Discovery**: In APIs Hub, connected accounts, advertising profiles, pages, and web properties are referred to as **assets** (internally represented as `channeledAccount`). Use `GET /{channel}/account` to dynamically discover connected assets and retrieve their corresponding ID, platform ID, and display name. Pass these asset IDs into analytical reduction queries under `filters: { \"channeledAccount\": \"<asset_id>\" }` or `groupBy: [\"channeledAccount\"]`. All entity endpoints are read-only (GET requests only).\n- **Pagination, Page Size & Sorting**: Sequential data consumption standards across the platform. Entity browsing (`GET /{channel}/{entity}`) uses zero-indexed offset pagination (`pagination`, `limit` up to 50,000, `orderBy`, `orderDir`). Analytical reductions (`POST /{channel}/metric/aggregate`) use row caps (`limit` up to 5,000) and metric ranking (`orderBy`, `orderDir`).\n- **High-Performance Aggregations & Query Caching**: The `/metric/aggregate` engine provides sub-millisecond reduced OLAP calculations across Google Search Console, Google Analytics 4, Meta Ads, Meta Organic, Shopify, and Klaviyo with flexible grouping, date windows, and weighted reduction formulas. The query response metadata reports whether an analytical reduction was served from cache (`meta.cached`) and the execution duration (`meta.execution_time_ms`).\n- **Rate Limits & Fair Use**: Requests are tracked on a per-minute sliding window. Ultra/Founder plans receive 500 req/min; Enterprise plans receive 1,000 req/min. When rate limits are reached, the API returns HTTP `429 Too Many Requests` with a `Retry-After` header.",
                'contact' => [
                    'name' => 'APIs Hub Developer Portal',
                    'url' => 'https://apis-hub.cloud',
                ],
            ],
            'tags' => [
                [
                    'name' => 'Authentication',
                    'description' => 'Security protocols, API key headers, credential rotation, and project node resolution.',
                ],
                [
                    'name' => 'System Health',
                    'description' => 'Heartbeat monitoring, network connectivity checks, and public latency verification without consuming rate limit quota.',
                ],
                [
                    'name' => 'Data Synchronization',
                    'description' => 'Real-time telemetry, sync state inspection, data freshness timestamps, and total synced record volume per account.',
                ],
                [
                    'name' => 'Assets Discovery & Entities',
                    'description' => 'Discover connected assets (`channeledAccount`), inspect campaigns, ad groups, posts, pages, and browse entity date boundaries.',
                ],
                [
                    'name' => 'Channel Analytics (Aggregations)',
                    'description' => 'Execute high-speed multi-dimensional analytical reductions, time-series line charts, KPI scorecards, and breakdown cubes for specific channels.',
                ],
                [
                    'name' => 'Omnichannel Analytics',
                    'description' => 'Cross-network executive rollups combining Meta, Google, and eCommerce platforms into a single unified query.',
                ],
                [
                    'name' => 'Pagination, Page Size & Sorting',
                    'description' => 'Sequential data consumption standards: zero-indexed offset pagination, page-size bounds (limit up to 50,000 for entities, 5,000 for aggregations), and deterministic sorting orders (orderBy, orderDir).',
                ],
                [
                    'name' => 'Error Handling & Rate Limits',
                    'description' => 'Standard error response structures, HTTP status semantics (400, 401, 403, 404, 429), and rate limiting headers.',
                ],
            ],
            'servers' => [
                [
                    'url' => 'https://{subdomain}.apis-hub.cloud',
                    'description' => 'Dedicated Project Node (production)',
                    'variables' => [
                        'subdomain' => [
                            'default' => 'your-project',
                            'description' => 'Your unique project subdomain identifier assigned in the dashboard',
                        ],
                    ],
                ],
            ],
            'security' => [
                ['ApiKeyAuth' => []],
                ['BearerAuth' => []],
            ],
            'paths' => [
                // 1. Health & Heartbeat
                '/api/v1/ping' => [
                    'get' => [
                        'tags' => ['System Health'],
                        'summary' => 'Node Connectivity & Heartbeat Ping',
                        'description' => 'Verify that your dedicated node is healthy, responsive, and accepting authenticated requests. Does not consume rate limit tokens.',
                        'operationId' => 'getPing',
                        'responses' => [
                            '200' => [
                                'description' => 'Node is operational and credentials are valid',
                                'content' => [
                                    'application/json' => [
                                        'schema' => [
                                            '$ref' => '#/components/schemas/PingResponse',
                                        ],
                                    ],
                                ],
                            ],
                            '401' => [
                                'description' => 'Invalid or missing API key',
                                'content' => [
                                    'application/json' => [
                                        'schema' => ['$ref' => '#/components/schemas/ErrorResponse'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],

                // 2. Data Synchronization Telemetry
                '/api/sync/status' => [
                    'get' => [
                        'tags' => ['Data Synchronization'],
                        'summary' => 'Sync Status & Data Freshness',
                        'description' => 'Inspect active background sync processes, data completion state, and latest record dates across integrated channels.',
                        'operationId' => 'getSyncStatus',
                        'parameters' => [
                            [
                                'name' => 'channel',
                                'in' => 'query',
                                'required' => false,
                                'description' => 'Optional filter for a specific channel (e.g. google_search_console, facebook_marketing)',
                                'schema' => [
                                    '$ref' => '#/components/schemas/ChannelEnum',
                                ],
                            ],
                            [
                                'name' => 'account_id',
                                'in' => 'query',
                                'required' => false,
                                'description' => 'Optional filter for a specific connected account ID',
                                'schema' => [
                                    'type' => 'integer',
                                ],
                            ],
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'Synchronization telemetry details',
                                'content' => [
                                    'application/json' => [
                                        'schema' => [
                                            '$ref' => '#/components/schemas/SyncStatusResponse',
                                        ],
                                    ],
                                ],
                            ],
                            '401' => [
                                'description' => 'Unauthorized or missing API Key',
                                'content' => [
                                    'application/json' => [
                                        'schema' => ['$ref' => '#/components/schemas/ErrorResponse'],
                                    ],
                                ],
                            ],
                            '429' => [
                                'description' => 'Rate limit exceeded',
                                'content' => [
                                    'application/json' => [
                                        'schema' => ['$ref' => '#/components/schemas/RateLimitErrorResponse'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                '/api/sync/account-stats' => [
                    'get' => [
                        'tags' => ['Data Synchronization'],
                        'summary' => 'Account Sync Statistics & Volume',
                        'description' => 'Provides total normalized record count, initial synced record date, and most recent synced record date per connected account.',
                        'operationId' => 'getAccountSyncStats',
                        'parameters' => [
                            [
                                'name' => 'channel',
                                'in' => 'query',
                                'required' => false,
                                'schema' => [
                                    '$ref' => '#/components/schemas/ChannelEnum',
                                ],
                            ],
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'Account statistics summary',
                                'content' => [
                                    'application/json' => [
                                        'schema' => [
                                            '$ref' => '#/components/schemas/AccountStatsResponse',
                                        ],
                                    ],
                                ],
                            ],
                            '401' => [
                                'description' => 'Unauthorized or missing API Key',
                                'content' => [
                                    'application/json' => [
                                        'schema' => ['$ref' => '#/components/schemas/ErrorResponse'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],

                // 3. Channeled Entity Discovery & Management (CRUD)
                '/{channel}/{entity}' => [
                    'get' => [
                        'tags' => ['Assets Discovery & Entities', 'Pagination, Page Size & Sorting'],
                        'summary' => 'List Channeled Entities (Assets Discovery & Paginated Browsing)',
                        'description' => "Browse and paginate normalized channeled records (e.g. `account`, `campaign`, `ad_group`, `ad`, `page`, `post`, `metric`).\n\n> [!IMPORTANT]\n> **Discovering Asset / `channeledAccount` IDs**: In APIs Hub, connected accounts, advertising profiles, pages, and web properties are known as **assets**. Make a call to `GET /{channel}/account` (such as `GET /facebook_marketing/account` or `GET /google_search_console/account`). The returned records represent the connected assets and include `id`, `platform_id`, and `name`. Pass this `id` into analytical reduction queries under `filters: { \"channeledAccount\": \"<asset_id>\" }` or `groupBy: [\"channeledAccount\"]`.",
                        'operationId' => 'listChanneledEntities',
                        'parameters' => [
                            [
                                'name' => 'channel',
                                'in' => 'path',
                                'required' => true,
                                'schema' => ['$ref' => '#/components/schemas/ChannelEnum'],
                            ],
                            [
                                'name' => 'entity',
                                'in' => 'path',
                                'required' => true,
                                'schema' => ['$ref' => '#/components/schemas/EntityEnum'],
                            ],
                            [
                                'name' => 'limit',
                                'in' => 'query',
                                'schema' => ['type' => 'integer', 'default' => 50, 'maximum' => 50000],
                                'description' => 'Number of records to return per page',
                            ],
                            [
                                'name' => 'pagination',
                                'in' => 'query',
                                'schema' => ['type' => 'integer', 'default' => 0],
                                'description' => 'Page offset (0-indexed)',
                            ],
                            [
                                'name' => 'orderBy',
                                'in' => 'query',
                                'schema' => ['type' => 'string'],
                                'description' => 'Field or column to sort results by',
                            ],
                            [
                                'name' => 'orderDir',
                                'in' => 'query',
                                'schema' => ['type' => 'string', 'enum' => ['ASC', 'DESC'], 'default' => 'ASC'],
                            ],
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'Paginated entity records',
                                'content' => [
                                    'application/json' => [
                                        'schema' => [
                                            '$ref' => '#/components/schemas/EntityListResponse',
                                        ],
                                    ],
                                ],
                            ],
                            '400' => [
                                'description' => 'Invalid channel or entity name',
                                'content' => [
                                    'application/json' => [
                                        'schema' => ['$ref' => '#/components/schemas/ErrorResponse'],
                                    ],
                                ],
                            ],
                            '401' => [
                                'description' => 'Unauthorized or missing API Key',
                                'content' => [
                                    'application/json' => [
                                        'schema' => ['$ref' => '#/components/schemas/ErrorResponse'],
                                    ],
                                ],
                            ],
                            '429' => [
                                'description' => 'Rate limit exceeded',
                                'content' => [
                                    'application/json' => [
                                        'schema' => ['$ref' => '#/components/schemas/RateLimitErrorResponse'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                '/{channel}/{entity}/{id}' => [
                    'get' => [
                        'tags' => ['Assets Discovery & Entities'],
                        'summary' => 'Retrieve Channeled Entity by ID',
                        'description' => 'Fetch a single channeled entity record by its unique identifier.',
                        'operationId' => 'getChanneledEntity',
                        'parameters' => [
                            [
                                'name' => 'channel',
                                'in' => 'path',
                                'required' => true,
                                'schema' => ['$ref' => '#/components/schemas/ChannelEnum'],
                            ],
                            [
                                'name' => 'entity',
                                'in' => 'path',
                                'required' => true,
                                'schema' => ['$ref' => '#/components/schemas/EntityEnum'],
                            ],
                            [
                                'name' => 'id',
                                'in' => 'path',
                                'required' => true,
                                'schema' => ['type' => 'integer'],
                                'description' => 'Unique internal record ID',
                            ],
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'Entity details',
                                'content' => [
                                    'application/json' => [
                                        'schema' => [
                                            '$ref' => '#/components/schemas/SingleEntityResponse',
                                        ],
                                    ],
                                ],
                            ],
                            '404' => [
                                'description' => 'Entity not found',
                                'content' => [
                                    'application/json' => [
                                        'schema' => ['$ref' => '#/components/schemas/ErrorResponse'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                '/{channel}/{entity}/count' => [
                    'get' => [
                        'tags' => ['Assets Discovery & Entities'],
                        'summary' => 'Count Channeled Entities',
                        'description' => 'Returns the total count of synchronized records for the requested channel and entity.',
                        'operationId' => 'countChanneledEntities',
                        'parameters' => [
                            [
                                'name' => 'channel',
                                'in' => 'path',
                                'required' => true,
                                'schema' => ['$ref' => '#/components/schemas/ChannelEnum'],
                            ],
                            [
                                'name' => 'entity',
                                'in' => 'path',
                                'required' => true,
                                'schema' => ['$ref' => '#/components/schemas/EntityEnum'],
                            ],
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'Total count',
                                'content' => [
                                    'application/json' => [
                                        'schema' => [
                                            'type' => 'object',
                                            'properties' => [
                                                'status' => ['type' => 'string', 'example' => 'success'],
                                                'data' => ['type' => 'integer', 'example' => 12480],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                '/{channel}/{entity}/range' => [
                    'get' => [
                        'tags' => ['Assets Discovery & Entities'],
                        'summary' => 'Get Entity Date Range Bounds',
                        'description' => 'Retrieves the minimum and maximum recorded dates (`minDate`, `maxDate`) for the given channeled entity to help establish query boundary filters.',
                        'operationId' => 'getChanneledEntityRange',
                        'parameters' => [
                            [
                                'name' => 'channel',
                                'in' => 'path',
                                'required' => true,
                                'schema' => ['$ref' => '#/components/schemas/ChannelEnum'],
                            ],
                            [
                                'name' => 'entity',
                                'in' => 'path',
                                'required' => true,
                                'schema' => ['$ref' => '#/components/schemas/EntityEnum'],
                            ],
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'Date bounds',
                                'content' => [
                                    'application/json' => [
                                        'schema' => [
                                            'type' => 'object',
                                            'properties' => [
                                                'status' => ['type' => 'string', 'example' => 'success'],
                                                'data' => [
                                                    'type' => 'object',
                                                    'properties' => [
                                                        'minDate' => ['type' => 'string', 'format' => 'date', 'example' => '2024-01-01'],
                                                        'maxDate' => ['type' => 'string', 'format' => 'date', 'example' => '2026-09-24'],
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],

                // 4. Analytical Aggregation Engine
                '/{channel}/metric/aggregate' => [
                    'post' => [
                        'tags' => ['Channel Analytics (Aggregations)', 'Pagination, Page Size & Sorting'],
                        'summary' => 'Execute Single-Channel Aggregation Query',
                        'description' => "Executes multidimensional analytical queries with time-series groupings, relational filtering, and weighted reduction formulas for a specific channel.\n\n### Channel-Specific Reference:\n- **`google_search_console`**:\n  - Scopes: `gsc_site_totals`, `gsc_site_query_breakdown`, `gsc_site_geo_device_breakdown`, `gsc_site_full_breakdown`, `gsc_page_flow`\n  - Granularities: `lifetime`, `daily` (`date`), `weekly`, `monthly`, `quarterly`, `yearly`\n  - Metrics: `clicks`, `impressions`, `ctr`, `position` (weighted)\n  - Dimensions: `query`, `dimensions.page`, `dimensions.country`, `dimensions.device`, `dimensions.searchAppearance`\n\n- **`facebook_marketing`**:\n  - Scopes: `facebook_marketing_account`, `facebook_marketing_campaign`, `facebook_marketing_ad_group`, `facebook_marketing_ad`, `facebook_marketing_ads_hierarchy`\n  - Granularities: `all_time`, `daily` (`date`), `weekly`, `monthly`, `quarterly`, `yearly`\n  - Metrics: `spend`, `impressions`, `clicks`, `reach`, `frequency`, `conversions`, `cost_per_conversion`, `conversion_rate`, `roas_purchase`\n  - Dimensions: `channeledAccount`, `campaign`, `ad_group`, `ad`, `date`\n\n- **`facebook_organic`**:\n  - Scopes: `facebook_organic_page`, `facebook_organic_post`, `facebook_organic_linked_pages`\n  - FB Page Metrics: `reach`, `page_views_total`, `views`, `follows`, `likes`, `total_interactions`, `video_views`\n  - IG Account Metrics: `reach`, `views`, `follows`, `profile_views`, `website_clicks`, `accounts_engaged`, `total_interactions`, `likes`, `comments`, `shares`, `saves`\n  - FB Post Metrics: `reach`, `views`, `video_views`, `likes`, `post_clicks`, `total_interactions`, `comments`, `shares`\n  - IG Media Metrics: `reach`, `views`, `likes`, `comments`, `shares`, `saves`, `replies`, `profile_visits`\n\n- **`google_analytics`** (GA4):\n  - Scopes: `traffic_matrix`, `acquisition_matrix`, `event_matrix`, `ad_touchpoint_matrix`, `ga4_universal_matrix`\n  - Metrics: `sessions`, `activeUsers`, `totalUsers`, `newUsers`, `screenPageViews`, `bounceRate`, `averageSessionDuration`, `eventCount`, `conversions`, `totalRevenue`",
                        'operationId' => 'aggregateChannelMetrics',
                        'parameters' => [
                            [
                                'name' => 'channel',
                                'in' => 'path',
                                'required' => true,
                                'schema' => ['$ref' => '#/components/schemas/ChannelEnum'],
                            ],
                        ],
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => ['$ref' => '#/components/schemas/AggregateQueryRequest'],
                                ],
                            ],
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'Aggregated metrics response with metadata',
                                'content' => [
                                    'application/json' => [
                                        'schema' => ['$ref' => '#/components/schemas/AggregateQueryResponse'],
                                    ],
                                ],
                            ],
                            '400' => [
                                'description' => 'Invalid query payload, missing aggregations, or invalid dimension for channel',
                                'content' => [
                                    'application/json' => [
                                        'schema' => ['$ref' => '#/components/schemas/ErrorResponse'],
                                    ],
                                ],
                            ],
                            '401' => [
                                'description' => 'Unauthorized or missing API Key',
                                'content' => [
                                    'application/json' => [
                                        'schema' => ['$ref' => '#/components/schemas/ErrorResponse'],
                                    ],
                                ],
                            ],
                            '422' => [
                                'description' => 'Validation error (e.g. invalid date format YYYY-MM-DD)',
                                'content' => [
                                    'application/json' => [
                                        'schema' => ['$ref' => '#/components/schemas/ErrorResponse'],
                                    ],
                                ],
                            ],
                            '429' => [
                                'description' => 'Rate limit quota reached',
                                'content' => [
                                    'application/json' => [
                                        'schema' => ['$ref' => '#/components/schemas/RateLimitErrorResponse'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                '/entity/metric/aggregate' => [
                    'post' => [
                        'tags' => ['Omnichannel Analytics', 'Pagination, Page Size & Sorting'],
                        'summary' => 'Execute Cross-Channel Master Aggregation',
                        'description' => 'Executes a unified cross-channel query combining performance across Google, Meta, and ecommerce channels into a single scorecard or blended time series.',
                        'operationId' => 'aggregateCrossChannelMetrics',
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => ['$ref' => '#/components/schemas/AggregateQueryRequest'],
                                ],
                            ],
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'Cross-channel aggregation response',
                                'content' => [
                                    'application/json' => [
                                        'schema' => ['$ref' => '#/components/schemas/AggregateQueryResponse'],
                                    ],
                                ],
                            ],
                            '400' => [
                                'description' => 'Invalid request payload',
                                'content' => [
                                    'application/json' => [
                                        'schema' => ['$ref' => '#/components/schemas/ErrorResponse'],
                                    ],
                                ],
                            ],
                            '401' => [
                                'description' => 'Unauthorized or missing API Key',
                                'content' => [
                                    'application/json' => [
                                        'schema' => ['$ref' => '#/components/schemas/ErrorResponse'],
                                    ],
                                ],
                            ],
                            '429' => [
                                'description' => 'Rate limit exceeded',
                                'content' => [
                                    'application/json' => [
                                        'schema' => ['$ref' => '#/components/schemas/RateLimitErrorResponse'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'components' => [
                'securitySchemes' => [
                    'ApiKeyAuth' => [
                        'type' => 'apiKey',
                        'in' => 'header',
                        'name' => 'X-API-KEY',
                        'description' => 'Your project API key. Found in your APIs Hub dashboard under Settings > API Access.',
                    ],
                    'BearerAuth' => [
                        'type' => 'http',
                        'scheme' => 'bearer',
                        'description' => 'Alternative Bearer token authentication header.',
                    ],
                ],
                'schemas' => [
                    'ChannelEnum' => [
                        'type' => 'string',
                        'enum' => [
                            'google_search_console',
                            'google_analytics',
                            'facebook_marketing',
                            'facebook_organic',
                            'shopify',
                            'klaviyo',
                        ],
                        'example' => 'google_search_console',
                    ],
                    'EntityEnum' => [
                        'type' => 'string',
                        'enum' => [
                            'account',
                            'campaign',
                            'ad_group',
                            'ad',
                            'page',
                            'post',
                            'metric',
                            'event',
                        ],
                        'example' => 'account',
                    ],
                    'PingResponse' => [
                        'type' => 'object',
                        'properties' => [
                            'status' => ['type' => 'string', 'example' => 'ok'],
                            'message' => ['type' => 'string', 'example' => 'APIs Hub API connection verified successfully.'],
                            'timestamp' => ['type' => 'string', 'format' => 'date-time', 'example' => '2026-09-24T23:15:00Z'],
                        ],
                    ],
                    'SyncStatusResponse' => [
                        'type' => 'object',
                        'properties' => [
                            'status' => ['type' => 'string', 'example' => 'success'],
                            'data' => [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'channel' => ['type' => 'string', 'example' => 'google_search_console'],
                                        'status' => ['type' => 'string', 'example' => 'completed'],
                                        'last_synced_at' => ['type' => 'string', 'format' => 'date-time', 'example' => '2026-09-24T22:00:00Z'],
                                        'latest_record_date' => ['type' => 'string', 'format' => 'date', 'example' => '2026-09-23'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'AccountStatsResponse' => [
                        'type' => 'object',
                        'properties' => [
                            'status' => ['type' => 'string', 'example' => 'success'],
                            'data' => [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'account_id' => ['type' => 'integer', 'example' => 12],
                                        'channel' => ['type' => 'string', 'example' => 'facebook_marketing'],
                                        'total_metrics' => ['type' => 'integer', 'example' => 45890],
                                        'first_synced_date' => ['type' => 'string', 'format' => 'date', 'example' => '2024-01-01'],
                                        'last_synced_date' => ['type' => 'string', 'format' => 'date', 'example' => '2026-09-23'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'EntityListResponse' => [
                        'type' => 'object',
                        'properties' => [
                            'status' => ['type' => 'string', 'example' => 'success'],
                            'data' => [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'id' => ['type' => 'integer', 'example' => 12],
                                        'platform_id' => ['type' => 'string', 'example' => 'sc-domain:example.com'],
                                        'name' => ['type' => 'string', 'example' => 'Primary Production Website'],
                                        'channel' => ['type' => 'string', 'example' => 'google_search_console'],
                                    ],
                                    'additionalProperties' => true,
                                ],
                            ],
                            'meta' => [
                                'type' => 'object',
                                'properties' => [
                                    'limit' => ['type' => 'integer', 'example' => 50],
                                    'pagination' => ['type' => 'integer', 'example' => 0],
                                ],
                            ],
                        ],
                    ],
                    'SingleEntityResponse' => [
                        'type' => 'object',
                        'properties' => [
                            'status' => ['type' => 'string', 'example' => 'success'],
                            'data' => [
                                'type' => 'object',
                                'properties' => [
                                    'id' => ['type' => 'integer', 'example' => 12],
                                    'name' => ['type' => 'string', 'example' => 'Primary Account'],
                                ],
                                'additionalProperties' => true,
                            ],
                        ],
                    ],
                    'AggregateQueryRequest' => [
                        'type' => 'object',
                        'required' => ['aggregations'],
                        'properties' => [
                            'aggregations' => [
                                'type' => 'object',
                                'description' => 'Mapping of output field aliases to canonical metric keys or formulas',
                                'example' => [
                                    'clicks' => 'clicks',
                                    'impressions' => 'impressions',
                                    'ctr' => 'ctr',
                                    'position' => 'position',
                                ],
                            ],
                            'groupBy' => [
                                'type' => 'array',
                                'items' => ['type' => 'string'],
                                'description' => 'Dimensions or temporal granularity expressions to group by (e.g. ["date", "query"] or ["channeledAccount"])',
                                'example' => ['date', 'query'],
                            ],
                            'filters' => [
                                'type' => 'object',
                                'description' => 'Filters applied to records. Supports exact values or operator objects (in, not_equal, greater_than, contains)',
                                'example' => [
                                    'channeledAccount' => '12',
                                    'dimensions.country' => 'USA',
                                ],
                            ],
                            'startDate' => [
                                'type' => 'string',
                                'format' => 'date',
                                'description' => 'Beginning of date window (YYYY-MM-DD)',
                                'example' => '2026-09-01',
                            ],
                            'endDate' => [
                                'type' => 'string',
                                'format' => 'date',
                                'description' => 'End of date window (YYYY-MM-DD)',
                                'example' => '2026-09-24',
                            ],
                            'orderBy' => [
                                'type' => 'string',
                                'description' => 'Field or alias to sort results by',
                                'example' => 'clicks',
                            ],
                            'orderDir' => [
                                'type' => 'string',
                                'enum' => ['ASC', 'DESC'],
                                'default' => 'ASC',
                                'example' => 'DESC',
                            ],
                            'limit' => [
                                'type' => 'integer',
                                'default' => 500,
                                'description' => 'Row limit (default: 500, max: 5000)',
                                'example' => 100,
                            ],
                        ],
                    ],
                    'AggregateQueryResponse' => [
                        'type' => 'object',
                        'properties' => [
                            'status' => ['type' => 'string', 'example' => 'success'],
                            'data' => [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'object',
                                    'additionalProperties' => true,
                                ],
                                'example' => [
                                    [
                                        'date' => '2026-09-01',
                                        'clicks' => 540,
                                        'impressions' => 14820,
                                        'ctr' => 0.0364,
                                        'position' => 12.8,
                                    ],
                                ],
                            ],
                            'meta' => [
                                'type' => 'object',
                                'properties' => [
                                    'cached' => ['type' => 'boolean', 'example' => true, 'description' => 'Indicates whether the analytical calculation was served from the pre-computed reduction cache.'],
                                    'execution_time_ms' => ['type' => 'number', 'example' => 3.8, 'description' => 'Query execution duration in milliseconds.'],
                                ],
                            ],
                        ],
                    ],
                    'ErrorResponse' => [
                        'type' => 'object',
                        'properties' => [
                            'status' => ['type' => 'string', 'example' => 'error'],
                            'error' => ['type' => 'string', 'example' => 'Invalid or missing API Key.'],
                            'code' => ['type' => 'integer', 'example' => 401],
                        ],
                    ],
                    'RateLimitErrorResponse' => [
                        'type' => 'object',
                        'properties' => [
                            'status' => ['type' => 'string', 'example' => 'error'],
                            'error' => ['type' => 'string', 'example' => 'Too Many Requests'],
                            'message' => ['type' => 'string', 'example' => 'Rate limit exceeded. Please wait 60 seconds.'],
                            'retry_after' => ['type' => 'integer', 'example' => 60],
                        ],
                    ],
                ],
            ],
        ];
    }
}
