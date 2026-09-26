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
    public function buildSpecification(string $locale = 'en'): array
    {
        $isEs = ($locale === 'es');

        $infoTitle = $isEs 
            ? 'API RESTful y Analítica de APIs Hub' 
            : 'APIs Hub RESTful Analytical API';

        $infoDescription = $isEs
            ? "Bienvenido a la Referencia para Desarrolladores de APIs Hub.\n\n### Guía Completa de Temas\n\n- **Autenticación y Seguridad**: Autentique cada solicitud proporcionando la clave de API de su proyecto a través del encabezado `X-API-KEY` (o alternativamente `Authorization: Bearer <key>`). Se admiten dos tipos de credenciales:\n  1. **Clave Maestra del Proyecto (`APP_API_KEY`)**: Otorgada a Propietarios y Editores para acceso global de lectura a todos los canales, entidades y grupos de recursos.\n  2. **Claves de API de Usuario**: Claves individuales generadas para Colaboradores y Viewers con aislamiento granular. Las consultas realizadas con estas claves están estrictamente delimitadas a los grupos de recursos asignados al usuario en el proyecto.\n  Las claves se administran y rotan con cero tiempo de inactividad desde el panel de control. Las claves de API públicas son estrictamente de solo lectura.\n- **Subdominios y Enrutamiento del Proyecto**: Todas las solicitudes se enrutan directamente al nodo contenedor aislado de su proyecto utilizando el formato de host `https://{subdomain}.apis-hub.cloud`.\n- **Descubrimiento de Recursos y Cuentas Conectadas**: En APIs Hub, las cuentas conectadas, perfiles publicitarios, páginas y propiedades web se denominan **recursos** (representados internamente como `channeledAccount`). Utilice `GET /{channel}/account` para descubrir dinámicamente los recursos conectados y obtener su ID correspondiente, ID de plataforma y nombre visible. Pase estos identificadores de recurso en las consultas analíticas de reducción bajo `filters: { \"channeledAccount\": \"<asset_id>\" }` o `groupBy: [\"channeledAccount\"]`. Todos los puntos finales de entidades son de solo lectura (únicamente solicitudes GET).\n- **Paginación, Tamaño de Página y Orden**: Estándares de consumo secuencial de datos en toda la plataforma. La navegación de entidades (`GET /{channel}/{entity}`) utiliza paginación por desplazamiento basada en índice cero (`pagination`, `limit` de hasta 50,000, `orderBy`, `orderDir`). Las reducciones analíticas (`POST /{channel}/metric/aggregate`) utilizan límites de filas (`limit` hasta 5,000) y ordenamiento por métricas (`orderBy`, `orderDir`).\n- **Agregaciones de Alto Rendimiento y Telemetría de Caché**: El motor de `/metric/aggregate` proporciona cálculos OLAP reducidos en submilisegundos en Google Search Console, Google Analytics 4, Meta Ads, Meta Organic, Shopify y Klaviyo con agrupaciones flexibles, ventanas de fechas y fórmulas de reducción ponderadas. Los metadatos de respuesta indican si una reducción analítica se sirvió desde la memoria caché (`meta.cached`) y la duración de la ejecución (`meta.execution_time_ms`).\n- **Límites de Frecuencia y Uso Justo**: Las solicitudes se rastrean en una ventana deslizante de un minuto. Los planes Ultra/Founder reciben 500 sol/min; los planes Enterprise reciben 1,000 sol/min. Cuando se alcanza el límite, la API responde con HTTP `429 Too Many Requests` y el encabezado `Retry-After`."
            : "Welcome to the APIs Hub Developer Reference.\n\n### Comprehensive Topic Guide\n\n- **Authentication & Security**: Authenticate every request by supplying your API key via the `X-API-KEY` header (or alternatively `Authorization: Bearer <key>`). Two credential levels are supported:\n  1. **Project Master Key (`APP_API_KEY`)**: Available to Project Owners and Editors with unrestricted read access across all channels, accounts, and asset groups.\n  2. **User-Scoped API Keys**: Individual keys generated for Viewers and Collaborators with granular asset restriction. Requests authenticated with a user key are strictly scoped to the asset groups assigned to that user in the dashboard.\n  Keys are managed and rotated with zero downtime in your project dashboard. Public API Keys are strictly read-only.\n- **Project Subdomains & Routing**: All requests are routed directly to your isolated project container node using the host format `https://{subdomain}.apis-hub.cloud`.\n- **Assets & Channeled Account Discovery**: In APIs Hub, connected accounts, advertising profiles, pages, and web properties are referred to as **assets** (internally represented as `channeledAccount`). Use `GET /{channel}/account` to dynamically discover connected assets and retrieve their corresponding ID, platform ID, and display name. Pass these asset IDs into analytical reduction queries under `filters: { \"channeledAccount\": \"<asset_id>\" }` or `groupBy: [\"channeledAccount\"]`. All entity endpoints are read-only (GET requests only).\n- **Pagination, Page Size & Sorting**: Sequential data consumption standards across the platform. Entity browsing (`GET /{channel}/{entity}`) uses zero-indexed offset pagination (`pagination`, `limit` up to 50,000, `orderBy`, `orderDir`). Analytical reductions (`POST /{channel}/metric/aggregate`) use row caps (`limit` up to 5,000) and metric ranking (`orderBy`, `orderDir`).\n- **High-Performance Aggregations & Query Caching**: The `/metric/aggregate` engine provides sub-millisecond reduced OLAP calculations across Google Search Console, Google Analytics 4, Meta Ads, Meta Organic, Shopify, and Klaviyo with flexible grouping, date windows, and weighted reduction formulas. The query response metadata reports whether an analytical reduction was served from cache (`meta.cached`) and the execution duration (`meta.execution_time_ms`).\n- **Rate Limits & Fair Use**: Requests are tracked on a per-minute sliding window. Ultra/Founder plans receive 500 req/min; Enterprise plans receive 1,000 req/min. When rate limits are reached, the API returns HTTP `429 Too Many Requests` with a `Retry-After` header.";

        $tagAuthentication = $isEs ? 'Autenticación' : 'Authentication';
        $tagSystemHealth = $isEs ? 'Estado del Sistema' : 'System Health';
        $tagDataSync = $isEs ? 'Sincronización de Datos' : 'Data Synchronization';
        $tagAssetsEntities = $isEs ? 'Descubrimiento de Recursos y Entidades' : 'Assets Discovery & Entities';
        $tagChannelAnalytics = $isEs ? 'Analítica de Canal (Agregaciones)' : 'Channel Analytics (Aggregations)';
        $tagOmnichannel = $isEs ? 'Analítica Omnicanal' : 'Omnichannel Analytics';
        $tagPagination = $isEs ? 'Paginación, Tamaño de Página y Orden' : 'Pagination, Page Size & Sorting';
        $tagErrorHandling = $isEs ? 'Manejo de Errores y Límites de Frecuencia' : 'Error Handling & Rate Limits';

        $tags = [
            [
                'name' => $tagAuthentication,
                'description' => $isEs
                    ? 'Protocolos de seguridad, encabezados de clave de API, rotación de credenciales y resolución del nodo del proyecto.'
                    : 'Security protocols, API key headers, credential rotation, and project node resolution.',
            ],
            [
                'name' => $tagSystemHealth,
                'description' => $isEs
                    ? 'Monitoreo de latido, verificación de conectividad de red y comprobación de latencia pública sin consumir cuota de límite de velocidad.'
                    : 'Heartbeat monitoring, network connectivity checks, and public latency verification without consuming rate limit quota.',
            ],
            [
                'name' => $tagDataSync,
                'description' => $isEs
                    ? 'Telemetría en tiempo real, inspección del estado de sincronización, marcas de tiempo de actualización de datos y volumen total de registros sincronizados por cuenta.'
                    : 'Real-time telemetry, sync state inspection, data freshness timestamps, and total synced record volume per account.',
            ],
            [
                'name' => $tagAssetsEntities,
                'description' => $isEs
                    ? 'Descubra recursos conectados (`channeledAccount`), inspeccione campañas, grupos de anuncios, publicaciones, páginas y examine los límites de fechas de las entidades.'
                    : 'Discover connected assets (`channeledAccount`), inspect campaigns, ad groups, posts, pages, and browse entity date boundaries.',
            ],
            [
                'name' => $tagChannelAnalytics,
                'description' => $isEs
                    ? 'Ejecute reducciones analíticas multidimensionales de alta velocidad, gráficos de series temporales, tarjetas de puntuación de KPI y cubos de desglose para canales específicos.'
                    : 'Execute high-speed multi-dimensional analytical reductions, time-series line charts, KPI scorecards, and breakdown cubes for specific channels.',
            ],
            [
                'name' => $tagOmnichannel,
                'description' => $isEs
                    ? 'Resúmenes ejecutivos multicanal que combinan el rendimiento de Meta, Google y plataformas de comercio electrónico en una única consulta unificada.'
                    : 'Cross-network executive rollups combining Meta, Google, and eCommerce platforms into a single unified query.',
            ],
            [
                'name' => $tagPagination,
                'description' => $isEs
                    ? 'Estándares de consumo secuencial de datos: paginación basada en desplazamiento con índice cero, límites de tamaño de página (límite de hasta 50,000 para entidades, 5,000 para agregaciones) y órdenes de clasificación deterministas (orderBy, orderDir).'
                    : 'Sequential data consumption standards: zero-indexed offset pagination, page-size bounds (limit up to 50,000 for entities, 5,000 for aggregations), and deterministic sorting orders (orderBy, orderDir).',
            ],
            [
                'name' => $tagErrorHandling,
                'description' => $isEs
                    ? 'Estructuras de respuesta de error estándar, semántica de estados HTTP (400, 401, 403, 404, 429) y encabezados de limitación de frecuencia.'
                    : 'Standard error response structures, HTTP status semantics (400, 401, 403, 404, 429), and rate limiting headers.',
            ],
        ];

        return [
            'openapi' => '3.1.0',
            'info' => [
                'title' => $infoTitle,
                'version' => '1.0.0',
                'description' => $infoDescription,
                'contact' => [
                    'name' => 'APIs Hub Developer Portal',
                    'url' => 'https://apis-hub.cloud',
                ],
            ],
            'tags' => $tags,
            'servers' => [
                [
                    'url' => 'https://{subdomain}.apis-hub.cloud',
                    'description' => $isEs ? 'Nodo dedicado del proyecto (producción)' : 'Dedicated Project Node (production)',
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
                        'tags' => [$tagSystemHealth],
                        'summary' => $isEs ? 'Verificación de Conectividad y Ping del Nodo' : 'Node Connectivity & Heartbeat Ping',
                        'description' => $isEs
                            ? 'Verifique que su nodo dedicado esté activo, receptivo y aceptando solicitudes autenticadas. No consume tokens de límite de velocidad.'
                            : 'Verify that your dedicated node is healthy, responsive, and accepting authenticated requests. Does not consume rate limit tokens.',
                        'operationId' => 'getPing',
                        'responses' => [
                            '200' => [
                                'description' => $isEs ? 'El nodo está operativo y las credenciales son válidas' : 'Node is operational and credentials are valid',
                                'content' => [
                                    'application/json' => [
                                        'schema' => [
                                            '$ref' => '#/components/schemas/PingResponse',
                                        ],
                                    ],
                                ],
                            ],
                            '401' => [
                                'description' => $isEs ? 'Clave de API inválida o ausente' : 'Invalid or missing API key',
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
                        'tags' => [$tagDataSync],
                        'summary' => $isEs ? 'Estado de Sincronización y Actualización de Datos' : 'Sync Status & Data Freshness',
                        'description' => $isEs
                            ? 'Inspeccione los procesos de sincronización en segundo plano, el estado de finalización y las fechas de registros más recientes en los canales integrados.'
                            : 'Inspect active background sync processes, data completion state, and latest record dates across integrated channels.',
                        'operationId' => 'getSyncStatus',
                        'parameters' => [
                            [
                                'name' => 'channel',
                                'in' => 'query',
                                'required' => false,
                                'description' => $isEs
                                    ? 'Filtro opcional para un canal específico (ej. google_search_console, facebook_marketing)'
                                    : 'Optional filter for a specific channel (e.g. google_search_console, facebook_marketing)',
                                'schema' => [
                                    '$ref' => '#/components/schemas/ChannelEnum',
                                ],
                            ],
                            [
                                'name' => 'account_id',
                                'in' => 'query',
                                'required' => false,
                                'description' => $isEs ? 'Filtro opcional por ID de cuenta conectada' : 'Optional filter for a specific connected account ID',
                                'schema' => [
                                    'type' => 'integer',
                                ],
                            ],
                        ],
                        'responses' => [
                            '200' => [
                                'description' => $isEs ? 'Detalles de telemetría de sincronización' : 'Synchronization telemetry details',
                                'content' => [
                                    'application/json' => [
                                        'schema' => [
                                            '$ref' => '#/components/schemas/SyncStatusResponse',
                                        ],
                                    ],
                                ],
                            ],
                            '401' => [
                                'description' => $isEs ? 'No autorizado o clave de API faltante' : 'Unauthorized or missing API Key',
                                'content' => [
                                    'application/json' => [
                                        'schema' => ['$ref' => '#/components/schemas/ErrorResponse'],
                                    ],
                                ],
                            ],
                            '429' => [
                                'description' => $isEs ? 'Límite de frecuencia excedido' : 'Rate limit exceeded',
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
                        'tags' => [$tagDataSync],
                        'summary' => $isEs ? 'Estadísticas de Sincronización y Volumen por Cuenta' : 'Account Sync Statistics & Volume',
                        'description' => $isEs
                            ? 'Proporciona el recuento total de registros normalizados, la fecha del primer registro sincronizado y la fecha del registro más reciente por cuenta conectada.'
                            : 'Provides total normalized record count, initial synced record date, and most recent synced record date per connected account.',
                        'operationId' => 'getAccountSyncStats',
                        'parameters' => [
                            [
                                'name' => 'channel',
                                'in' => 'query',
                                'required' => false,
                                'description' => $isEs
                                    ? 'Filtro opcional para un canal específico (ej. google_search_console, facebook_marketing)'
                                    : 'Optional filter for a specific channel (e.g. google_search_console, facebook_marketing)',
                                'schema' => [
                                    '$ref' => '#/components/schemas/ChannelEnum',
                                ],
                            ],
                        ],
                        'responses' => [
                            '200' => [
                                'description' => $isEs ? 'Resumen estadístico de la cuenta' : 'Account statistics summary',
                                'content' => [
                                    'application/json' => [
                                        'schema' => [
                                            '$ref' => '#/components/schemas/AccountStatsResponse',
                                        ],
                                    ],
                                ],
                            ],
                            '401' => [
                                'description' => $isEs ? 'No autorizado o clave de API faltante' : 'Unauthorized or missing API Key',
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
                        'tags' => [$tagAssetsEntities, $tagPagination],
                        'summary' => $isEs ? 'Listar Entidades Canalizadas (Descubrimiento de Recursos y Paginación)' : 'List Channeled Entities (Assets Discovery & Paginated Browsing)',
                        'description' => $isEs
                            ? "Explore y pagine registros normalizados por canal (ej. `account`, `campaign`, `ad_group`, `ad`, `page`, `post`, `metric`).\n\n> [!IMPORTANT]\n> **Descubrimiento de IDs de Recursos / `channeledAccount`**: En APIs Hub, las cuentas conectadas, perfiles publicitarios, páginas y propiedades web se denominan **recursos**. Realice una llamada a `GET /{channel}/account` (como `GET /facebook_marketing/account` o `GET /google_search_console/account`). Los registros devueltos representan los recursos conectados e incluyen `id`, `platform_id` y `name`. Pase este `id` en las consultas analíticas de reducción bajo `filters: { \"channeledAccount\": \"<asset_id>\" }` o `groupBy: [\"channeledAccount\"]`."
                            : "Browse and paginate normalized channeled records (e.g. `account`, `campaign`, `ad_group`, `ad`, `page`, `post`, `metric`).\n\n> [!IMPORTANT]\n> **Discovering Asset / `channeledAccount` IDs**: In APIs Hub, connected accounts, advertising profiles, pages, and web properties are known as **assets**. Make a call to `GET /{channel}/account` (such as `GET /facebook_marketing/account` or `GET /google_search_console/account`). The returned records represent the connected assets and include `id`, `platform_id`, and `name`. Pass this `id` into analytical reduction queries under `filters: { \"channeledAccount\": \"<asset_id>\" }` or `groupBy: [\"channeledAccount\"]`.",
                        'operationId' => 'listChanneledEntities',
                        'parameters' => [
                            [
                                'name' => 'channel',
                                'in' => 'path',
                                'required' => true,
                                'description' => $isEs ? 'Identificador del canal (ej. google_search_console, facebook_marketing)' : 'Channel identifier (e.g. google_search_console, facebook_marketing)',
                                'schema' => ['$ref' => '#/components/schemas/ChannelEnum'],
                            ],
                            [
                                'name' => 'entity',
                                'in' => 'path',
                                'required' => true,
                                'description' => $isEs ? 'Tipo de entidad (ej. account, campaign, ad_group, ad, post, page)' : 'Entity type (e.g. account, campaign, ad_group, ad, post, page)',
                                'schema' => ['$ref' => '#/components/schemas/EntityEnum'],
                            ],
                            [
                                'name' => 'limit',
                                'in' => 'query',
                                'schema' => ['type' => 'integer', 'default' => 50, 'maximum' => 50000],
                                'description' => $isEs ? 'Cantidad de registros a devolver por página (máx: 50,000)' : 'Number of records to return per page (max: 50,000)',
                            ],
                            [
                                'name' => 'pagination',
                                'in' => 'query',
                                'schema' => ['type' => 'integer', 'default' => 0],
                                'description' => $isEs ? 'Desplazamiento u offset de página (índice basado en 0)' : 'Page offset (0-indexed)',
                            ],
                            [
                                'name' => 'orderBy',
                                'in' => 'query',
                                'schema' => ['type' => 'string'],
                                'description' => $isEs ? 'Campo o columna para ordenar los resultados' : 'Field or column to sort results by',
                            ],
                            [
                                'name' => 'orderDir',
                                'in' => 'query',
                                'schema' => ['type' => 'string', 'enum' => ['ASC', 'DESC'], 'default' => 'ASC'],
                                'description' => $isEs ? 'Dirección de ordenación (ASC o DESC)' : 'Sort order direction (ASC or DESC)',
                            ],
                        ],
                        'responses' => [
                            '200' => [
                                'description' => $isEs ? 'Lista paginada de registros de la entidad' : 'Paginated entity records',
                                'content' => [
                                    'application/json' => [
                                        'schema' => [
                                            '$ref' => '#/components/schemas/EntityListResponse',
                                        ],
                                    ],
                                ],
                            ],
                            '400' => [
                                'description' => $isEs ? 'Canal o nombre de entidad inválido' : 'Invalid channel or entity name',
                                'content' => [
                                    'application/json' => [
                                        'schema' => ['$ref' => '#/components/schemas/ErrorResponse'],
                                    ],
                                ],
                            ],
                            '401' => [
                                'description' => $isEs ? 'No autorizado o clave de API faltante' : 'Unauthorized or missing API Key',
                                'content' => [
                                    'application/json' => [
                                        'schema' => ['$ref' => '#/components/schemas/ErrorResponse'],
                                    ],
                                ],
                            ],
                            '429' => [
                                'description' => $isEs ? 'Límite de frecuencia excedido' : 'Rate limit exceeded',
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
                        'tags' => [$tagAssetsEntities],
                        'summary' => $isEs ? 'Obtener Entidad Canalizada por ID' : 'Retrieve Channeled Entity by ID',
                        'description' => $isEs
                            ? 'Obtiene un registro individual de una entidad canalizada mediante su identificador único.'
                            : 'Fetch a single channeled entity record by its unique identifier.',
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
                                'description' => $isEs ? 'ID interno único del registro' : 'Unique internal record ID',
                            ],
                        ],
                        'responses' => [
                            '200' => [
                                'description' => $isEs ? 'Detalles de la entidad' : 'Entity details',
                                'content' => [
                                    'application/json' => [
                                        'schema' => [
                                            '$ref' => '#/components/schemas/SingleEntityResponse',
                                        ],
                                    ],
                                ],
                            ],
                            '404' => [
                                'description' => $isEs ? 'Entidad no encontrada' : 'Entity not found',
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
                        'tags' => [$tagAssetsEntities],
                        'summary' => $isEs ? 'Contar Entidades Canalizadas' : 'Count Channeled Entities',
                        'description' => $isEs
                            ? 'Devuelve el recuento total de registros sincronizados para el canal y entidad solicitados.'
                            : 'Returns the total count of synchronized records for the requested channel and entity.',
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
                                'description' => $isEs ? 'Recuento total de registros' : 'Total count',
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
                        'tags' => [$tagAssetsEntities],
                        'summary' => $isEs ? 'Obtener Límites de Rango de Fechas' : 'Get Entity Date Range Bounds',
                        'description' => $isEs
                            ? 'Recupera las fechas mínima y máxima registradas (`minDate`, `maxDate`) para la entidad canalizada indicada para ayudar a definir filtros de límites de consulta.'
                            : 'Retrieves the minimum and maximum recorded dates (`minDate`, `maxDate`) for the given channeled entity to help establish query boundary filters.',
                        'operationId' => 'getChanneledEntityRange',
                        'parameters' => [
                            [
                                'name' => 'channel',
                                'in' => 'path',
                                'required' => true,
                                'description' => $isEs ? 'Identificador del canal (ej. google_search_console, facebook_marketing)' : 'Channel identifier (e.g. google_search_console, facebook_marketing)',
                                'schema' => ['$ref' => '#/components/schemas/ChannelEnum'],
                            ],
                            [
                                'name' => 'entity',
                                'in' => 'path',
                                'required' => true,
                                'description' => $isEs ? 'Tipo de entidad (ej. account, campaign, ad_group, metric)' : 'Entity type (e.g. account, campaign, ad_group, metric)',
                                'schema' => ['$ref' => '#/components/schemas/EntityEnum'],
                            ],
                        ],
                        'responses' => [
                            '200' => [
                                'description' => $isEs ? 'Límites de fechas' : 'Date bounds',
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
                        'tags' => [$tagChannelAnalytics, $tagPagination],
                        'summary' => $isEs ? 'Ejecutar Consulta de Agregación de Canal' : 'Execute Single-Channel Aggregation Query',
                        'description' => $isEs
                            ? "Ejecuta consultas analíticas multidimensionales con agrupaciones de series temporales, filtrado relacional y fórmulas de reducción ponderadas para un canal específico.\n\n### Referencia Específica por Canal:\n- **`google_search_console`**:\n  - Ámbitos: `gsc_site_totals`, `gsc_site_query_breakdown`, `gsc_site_geo_device_breakdown`, `gsc_site_full_breakdown`, `gsc_page_flow`\n  - Granularidades: `lifetime`, `daily` (`date`), `weekly`, `monthly`, `quarterly`, `yearly`\n  - Métricas: `clicks`, `impressions`, `ctr`, `position` (ponderado)\n  - Dimensiones: `query`, `dimensions.page`, `dimensions.country`, `dimensions.device`, `dimensions.searchAppearance`\n\n- **`facebook_marketing`**:\n  - Ámbitos: `facebook_marketing_account`, `facebook_marketing_campaign`, `facebook_marketing_ad_group`, `facebook_marketing_ad`, `facebook_marketing_ads_hierarchy`\n  - Granularidades: `all_time`, `daily` (`date`), `weekly`, `monthly`, `quarterly`, `yearly`\n  - Métricas: `spend`, `impressions`, `clicks`, `reach`, `frequency`, `conversions`, `cost_per_conversion`, `conversion_rate`, `roas_purchase`\n  - Dimensiones: `channeledAccount`, `campaign`, `ad_group`, `ad`, `date`\n\n- **`facebook_organic`**:\n  - Ámbitos: `facebook_organic_page`, `facebook_organic_post`, `facebook_organic_linked_pages`\n  - Métricas Página FB: `reach`, `page_views_total`, `views`, `follows`, `likes`, `total_interactions`, `video_views`\n  - Métricas Cuenta IG: `reach`, `views`, `follows`, `profile_views`, `website_clicks`, `accounts_engaged`, `total_interactions`, `likes`, `comments`, `shares`, `saves`\n  - Métricas Publicación FB: `reach`, `views`, `video_views`, `likes`, `post_clicks`, `total_interactions`, `comments`, `shares`\n  - Métricas Media IG: `reach`, `views`, `likes`, `comments`, `shares`, `saves`, `replies`, `profile_visits`\n\n- **`google_analytics`** (GA4):\n  - Ámbitos: `traffic_matrix`, `acquisition_matrix`, `event_matrix`, `ad_touchpoint_matrix`, `ga4_universal_matrix`\n  - Métricas: `sessions`, `activeUsers`, `totalUsers`, `newUsers`, `screenPageViews`, `bounceRate`, `averageSessionDuration`, `eventCount`, `conversions`, `totalRevenue`"
                            : "Executes multidimensional analytical queries with time-series groupings, relational filtering, and weighted reduction formulas for a specific channel.\n\n### Channel-Specific Reference:\n- **`google_search_console`**:\n  - Scopes: `gsc_site_totals`, `gsc_site_query_breakdown`, `gsc_site_geo_device_breakdown`, `gsc_site_full_breakdown`, `gsc_page_flow`\n  - Granularities: `lifetime`, `daily` (`date`), `weekly`, `monthly`, `quarterly`, `yearly`\n  - Metrics: `clicks`, `impressions`, `ctr`, `position` (weighted)\n  - Dimensions: `query`, `dimensions.page`, `dimensions.country`, `dimensions.device`, `dimensions.searchAppearance`\n\n- **`facebook_marketing`**:\n  - Scopes: `facebook_marketing_account`, `facebook_marketing_campaign`, `facebook_marketing_ad_group`, `facebook_marketing_ad`, `facebook_marketing_ads_hierarchy`\n  - Granularities: `all_time`, `daily` (`date`), `weekly`, `monthly`, `quarterly`, `yearly`\n  - Metrics: `spend`, `impressions`, `clicks`, `reach`, `frequency`, `conversions`, `cost_per_conversion`, `conversion_rate`, `roas_purchase`\n  - Dimensions: `channeledAccount`, `campaign`, `ad_group`, `ad`, `date`\n\n- **`facebook_organic`**:\n  - Scopes: `facebook_organic_page`, `facebook_organic_post`, `facebook_organic_linked_pages`\n  - FB Page Metrics: `reach`, `page_views_total`, `views`, `follows`, `likes`, `total_interactions`, `video_views`\n  - IG Account Metrics: `reach`, `views`, `follows`, `profile_views`, `website_clicks`, `accounts_engaged`, `total_interactions`, `likes`, `comments`, `shares`, `saves`\n  - FB Post Metrics: `reach`, `views`, `video_views`, `likes`, `post_clicks`, `total_interactions`, `comments`, `shares`\n  - IG Media Metrics: `reach`, `views`, `likes`, `comments`, `shares`, `saves`, `replies`, `profile_visits`\n\n- **`google_analytics`** (GA4):\n  - Scopes: `traffic_matrix`, `acquisition_matrix`, `event_matrix`, `ad_touchpoint_matrix`, `ga4_universal_matrix`\n  - Metrics: `sessions`, `activeUsers`, `totalUsers`, `newUsers`, `screenPageViews`, `bounceRate`, `averageSessionDuration`, `eventCount`, `conversions`, `totalRevenue`",
                        'operationId' => 'aggregateChannelMetrics',
                        'parameters' => [
                            [
                                'name' => 'channel',
                                'in' => 'path',
                                'required' => true,
                                'description' => $isEs ? 'Identificador del canal a consultar' : 'Channel identifier to aggregate',
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
                                'description' => $isEs ? 'Respuesta de métricas agregadas con metadatos y telemetría de caché' : 'Aggregated metrics response with metadata',
                                'content' => [
                                    'application/json' => [
                                        'schema' => ['$ref' => '#/components/schemas/AggregateQueryResponse'],
                                    ],
                                ],
                            ],
                            '400' => [
                                'description' => $isEs ? 'Carga útil de consulta inválida, faltan agregaciones o dimensión inválida para el canal' : 'Invalid query payload, missing aggregations, or invalid dimension for channel',
                                'content' => [
                                    'application/json' => [
                                        'schema' => ['$ref' => '#/components/schemas/ErrorResponse'],
                                    ],
                                ],
                            ],
                            '401' => [
                                'description' => $isEs ? 'No autorizado o clave de API faltante' : 'Unauthorized or missing API Key',
                                'content' => [
                                    'application/json' => [
                                        'schema' => ['$ref' => '#/components/schemas/ErrorResponse'],
                                    ],
                                ],
                            ],
                            '422' => [
                                'description' => $isEs ? 'Error de validación (ej. formato de fecha inválido YYYY-MM-DD)' : 'Validation error (e.g. invalid date format YYYY-MM-DD)',
                                'content' => [
                                    'application/json' => [
                                        'schema' => ['$ref' => '#/components/schemas/ErrorResponse'],
                                    ],
                                ],
                            ],
                            '429' => [
                                'description' => $isEs ? 'Cuota de límite de velocidad alcanzada' : 'Rate limit quota reached',
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
                        'tags' => [$tagOmnichannel, $tagPagination],
                        'summary' => $isEs ? 'Ejecutar Agregación Maestra Omnicanal' : 'Execute Cross-Channel Master Aggregation',
                        'description' => $isEs
                            ? 'Ejecuta una consulta multicanal unificada combinando el rendimiento entre Google, Meta y plataformas de comercio electrónico en un solo cuadro de mando o serie temporal combinada.'
                            : 'Executes a unified cross-channel query combining performance across Google, Meta, and ecommerce channels into a single scorecard or blended time series.',
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
                                'description' => $isEs ? 'Respuesta de agregación omnicanal' : 'Cross-channel aggregation response',
                                'content' => [
                                    'application/json' => [
                                        'schema' => ['$ref' => '#/components/schemas/AggregateQueryResponse'],
                                    ],
                                ],
                            ],
                            '400' => [
                                'description' => $isEs ? 'Carga útil de solicitud inválida' : 'Invalid request payload',
                                'content' => [
                                    'application/json' => [
                                        'schema' => ['$ref' => '#/components/schemas/ErrorResponse'],
                                    ],
                                ],
                            ],
                            '401' => [
                                'description' => $isEs ? 'No autorizado o clave de API faltante' : 'Unauthorized or missing API Key',
                                'content' => [
                                    'application/json' => [
                                        'schema' => ['$ref' => '#/components/schemas/ErrorResponse'],
                                    ],
                                ],
                            ],
                            '429' => [
                                'description' => $isEs ? 'Límite de frecuencia excedido' : 'Rate limit exceeded',
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
                        'description' => $isEs
                            ? 'Clave de API pública de su proyecto (Clave Maestra para propietarios/editores o Clave de Usuario delimitada para colaboradores). Se administra en el panel de APIs Hub en Configuración > Acceso a la API.'
                            : 'Project public API key (Master Key for owners/editors or User-Scoped Key for collaborators). Managed in your APIs Hub dashboard under Settings > API Access.',
                    ],
                    'BearerAuth' => [
                        'type' => 'http',
                        'scheme' => 'bearer',
                        'description' => $isEs
                            ? 'Encabezado de autenticación con token Bearer alternativo (acepta Clave Maestra o Clave de Usuario).'
                            : 'Alternative Bearer token authentication header (accepts Master Key or User-Scoped Key).',
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
                            'message' => [
                                'type' => 'string',
                                'example' => $isEs
                                    ? 'Conexión a la API de APIs Hub verificada exitosamente.'
                                    : 'APIs Hub API connection verified successfully.',
                            ],
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
                                'description' => $isEs
                                    ? 'Mapeo de alias de campos de salida a claves o fórmulas de métricas canónicas (ej. clicks, impressions, spend)'
                                    : 'Mapping of output field aliases to canonical metric keys or formulas',
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
                                'description' => $isEs
                                    ? 'Dimensiones o expresiones de granularidad temporal para agrupar (ej. ["date", "query"] o ["channeledAccount"])'
                                    : 'Dimensions or temporal granularity expressions to group by (e.g. ["date", "query"] or ["channeledAccount"])',
                                'example' => ['date', 'query'],
                            ],
                            'filters' => [
                                'type' => 'object',
                                'description' => $isEs
                                    ? 'Filtros aplicados a los registros. Admite valores exactos u objetos de operadores (in, not_equal, greater_than, contains)'
                                    : 'Filters applied to records. Supports exact values or operator objects (in, not_equal, greater_than, contains)',
                                'example' => [
                                    'channeledAccount' => '12',
                                    'dimensions.country' => 'USA',
                                ],
                            ],
                            'startDate' => [
                                'type' => 'string',
                                'format' => 'date',
                                'description' => $isEs
                                    ? 'Inicio de la ventana de fechas (YYYY-MM-DD)'
                                    : 'Beginning of date window (YYYY-MM-DD)',
                                'example' => '2026-09-01',
                            ],
                            'endDate' => [
                                'type' => 'string',
                                'format' => 'date',
                                'description' => $isEs
                                    ? 'Fin de la ventana de fechas (YYYY-MM-DD)'
                                    : 'End of date window (YYYY-MM-DD)',
                                'example' => '2026-09-24',
                            ],
                            'orderBy' => [
                                'type' => 'string',
                                'description' => $isEs
                                    ? 'Campo o alias para ordenar los resultados'
                                    : 'Field or alias to sort results by',
                                'example' => 'clicks',
                            ],
                            'orderDir' => [
                                'type' => 'string',
                                'enum' => ['ASC', 'DESC'],
                                'default' => 'ASC',
                                'description' => $isEs
                                    ? 'Dirección de ordenación (ASC o DESC)'
                                    : 'Sort direction (ASC or DESC)',
                                'example' => 'DESC',
                            ],
                            'limit' => [
                                'type' => 'integer',
                                'default' => 500,
                                'description' => $isEs
                                    ? 'Límite de filas devueltas (por defecto: 500, máximo: 5,000)'
                                    : 'Row limit (default: 500, max: 5000)',
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
                                    'cached' => [
                                        'type' => 'boolean',
                                        'example' => true,
                                        'description' => $isEs
                                            ? 'Indica si el cálculo analítico se sirvió directamente desde la memoria caché de reducciones precalculadas.'
                                            : 'Indicates whether the analytical calculation was served from the pre-computed reduction cache.',
                                    ],
                                    'execution_time_ms' => [
                                        'type' => 'number',
                                        'example' => 3.8,
                                        'description' => $isEs
                                            ? 'Duración de la ejecución de la consulta en milisegundos.'
                                            : 'Query execution duration in milliseconds.',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'ErrorResponse' => [
                        'type' => 'object',
                        'properties' => [
                            'status' => ['type' => 'string', 'example' => 'error'],
                            'error' => ['type' => 'string', 'example' => $isEs ? 'Clave de API inválida o ausente.' : 'Invalid or missing API Key.'],
                            'code' => ['type' => 'integer', 'example' => 401],
                        ],
                    ],
                    'RateLimitErrorResponse' => [
                        'type' => 'object',
                        'properties' => [
                            'status' => ['type' => 'string', 'example' => 'error'],
                            'error' => ['type' => 'string', 'example' => 'Too Many Requests'],
                            'message' => ['type' => 'string', 'example' => $isEs ? 'Límite de frecuencia excedido. Espere 60 segundos.' : 'Rate limit exceeded. Please wait 60 seconds.'],
                            'retry_after' => ['type' => 'integer', 'example' => 60],
                        ],
                    ],
                ],
            ],
        ];
    }
}
