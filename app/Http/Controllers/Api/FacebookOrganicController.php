<?php

    namespace App\Http\Controllers\Api;

    use App\Http\Controllers\Controller;
    use App\Models\Project;
    use App\Services\RemoteEngineService;
    use Carbon\Carbon;
    use Illuminate\Http\Request;

    class FacebookOrganicController extends Controller
    {
        private function validateRequest(Request $request): array
        {
            return $request->validate([
                'tenant'          => 'required|string',
                'account'         => 'required|array',
                'account.*'       => 'nullable',
                'dateStart'       => 'required|date',
                'dateEnd'         => 'required|date',
                'activeTab'       => 'required|string|in:facebook,instagram',
                'postId'          => 'nullable|string',
                'activeFilters'   => 'nullable|array',
                'activeFilters.*' => 'nullable|array',
                'breakdownTab'    => 'nullable|string',
                'tableMode'       => 'nullable|string|in:posts,breakdown',
            ]);
        }

        private function getTabConfig(string $tab): array
        {
            switch ($tab) {
                case 'ig_accounts':
                    return [
                        'filters'      => ['account_type' => 'instagram_account'],
                        'groupBy'      => ['channeledAccount', 'channeled_account_id', 'page_platform_id', 'linked_fb_page_id'],
                        'aggregations' => [
                            'likes'              => 'likes', 'comments' => 'comments', 'reach' => 'reach', 'views' => 'views',
                            'profile_views'      => 'profile_views', 'website_clicks' => 'website_clicks',
                            'profile_links_taps' => 'profile_links_taps', 'follows_and_unfollows' => 'follows_and_unfollows',
                            'saves'              => 'saves', 'shares' => 'shares', 'total_interactions' => 'total_interactions',
                            'replies'            => 'replies', 'accounts_engaged' => 'accounts_engaged', 'content_views' => 'content_views'
                        ]
                    ];
                case 'fb_pages':
                    return [
                        'filters'      => ['account_type' => 'facebook_page', 'channel' => 'facebook_organic', 'period' => 'daily'],
                        'groupBy'      => ['page', 'page_id', 'page_title'],
                        'aggregations' => [
                            'reach'                 => 'reach', 'page_views_total' => 'page_views_total', 'video_views' => 'video_views',
                            'follows_and_unfollows' => 'follows_and_unfollows', 'total_interactions' => 'total_interactions', 'likes' => 'likes'
                        ]
                    ];
                case 'ig_posts':
                    return [
                        'filters'      => ['account_type' => 'instagram_account', 'post' => 'NOT_NULL', 'snapshot_fallback_mode' => 'resilient', 'period' => 'lifetime', 'latest_snapshot' => true, 'dimension_set_id' => ['operator' => 'is_null']],
                        'groupBy'      => ['post', 'post_id', 'caption', 'message', 'media_type', 'permalink', 'permalink_url', 'timestamp', 'created_time'],
                        'aggregations' => [
                            'comments'                       => 'comments', 'follows' => 'follows', 'ig_reels_avg_watch_time' => 'ig_reels_avg_watch_time',
                            'ig_reels_video_view_total_time' => 'ig_reels_video_view_total_time', 'likes' => 'likes',
                            'profile_activity'               => 'profile_activity', 'profile_visits' => 'profile_visits', 'reach' => 'reach',
                            'reposts'                        => 'reposts', 'saved' => 'saved', 'shares' => 'shares', 'total_interactions' => 'total_interactions', 'views' => 'views'
                        ]
                    ];
                case 'fb_posts':
                    return [
                        'filters'      => ['account_type' => 'facebook_page', 'post' => 'NOT_NULL', 'snapshot_fallback_mode' => 'resilient', 'period' => 'lifetime', 'latest_snapshot' => true],
                        'groupBy'      => ['post', 'post_id', 'caption', 'message', 'media_type', 'permalink', 'permalink_url', 'timestamp', 'created_time'],
                        'aggregations' => [
                            'reach'                       => 'reach', 'total_interactions' => 'total_interactions', 'likes' => 'likes',
                            'post_clicks'                 => 'post_clicks', 'views' => 'views', 'video_views' => 'video_views',
                            'post_video_avg_time_watched' => 'post_video_avg_time_watched'
                        ]
                    ];
            }

            return [];
        }

        private function parseSelectedAccounts(array $selectedAccounts): array
        {
            $parsed = [
                'fbAccountIds'  => [],
                'igAccountIds'  => [],
                'fbPlatformIds' => [],
                'fbPageIds'     => [],
            ];

            foreach ($selectedAccounts as $accValue) {
                $parts = explode('|', (string)$accValue);

                if (!empty($parts[0]) && $parts[0] !== 'NONE') {
                    $parsed['fbAccountIds'][] = (string)$parts[0];
                }

                if (!empty($parts[1]) && $parts[1] !== 'NONE') {
                    $parsed['igAccountIds'][] = (string)$parts[1];
                }

                if (!empty($parts[2]) && $parts[2] !== 'NONE') {
                    $parsed['fbPlatformIds'][] = (string)$parts[2];
                }

                if (!empty($parts[3]) && $parts[3] !== 'NONE') {
                    $parsed['fbPageIds'][] = (string)$parts[3];
                }
            }

            foreach ($parsed as $key => $values) {
                $parsed[$key] = array_values(array_unique($values));
            }

            return $parsed;
        }

        private function applySelectedAccountFilters(array &$filters, string $activeTab, string $internalTab, array $accounts): void
        {
            if ($activeTab === 'instagram') {
                if (empty($accounts['igAccountIds'])) {
                    $filters['channeledAccount'] = ['operator' => 'in', 'value' => ['__NONE__']];

                    return;
                }

                $filters['channeledAccount'] = count($accounts['igAccountIds']) === 1
                    ? $accounts['igAccountIds'][0]
                    : ['operator' => 'in', 'value' => $accounts['igAccountIds']];

                return;
            }

            // For Facebook tabs, prefer the most specific available identity filter to avoid over-constraining the query.
            if (in_array($internalTab, ['fb_pages', 'fb_posts'], true)) {
                if (!empty($accounts['fbPageIds'])) {
                    $filters['page'] = count($accounts['fbPageIds']) === 1
                        ? $accounts['fbPageIds'][0]
                        : ['operator' => 'in', 'value' => $accounts['fbPageIds']];

                    if (!empty($accounts['fbPlatformIds'])) {
                        $filters['page_platform_id'] = count($accounts['fbPlatformIds']) === 1
                            ? $accounts['fbPlatformIds'][0]
                            : ['operator' => 'in', 'value' => $accounts['fbPlatformIds']];
                    }

                    return;
                }

                // Page-level summary/breakdown/chart queries resolve best by platform page id.
                if ($internalTab === 'fb_pages' && !empty($accounts['fbPlatformIds'])) {
                    $filters['page_platform_id'] = count($accounts['fbPlatformIds']) === 1
                        ? $accounts['fbPlatformIds'][0]
                        : ['operator' => 'in', 'value' => $accounts['fbPlatformIds']];

                    return;
                }

                // FB post queries should prefer page platform id to avoid pulling linked IG posts.
                if ($internalTab === 'fb_posts' && !empty($accounts['fbPlatformIds'])) {
                    $filters['page_platform_id'] = count($accounts['fbPlatformIds']) === 1
                        ? $accounts['fbPlatformIds'][0]
                        : ['operator' => 'in', 'value' => $accounts['fbPlatformIds']];

                    return;
                }

                if (!empty($accounts['fbPlatformIds'])) {
                    $filters['page_platform_id'] = count($accounts['fbPlatformIds']) === 1
                        ? $accounts['fbPlatformIds'][0]
                        : ['operator' => 'in', 'value' => $accounts['fbPlatformIds']];

                    return;
                }

                if (!empty($accounts['fbAccountIds'])) {
                    $filters['channeledAccount'] = count($accounts['fbAccountIds']) === 1
                        ? $accounts['fbAccountIds'][0]
                        : ['operator' => 'in', 'value' => $accounts['fbAccountIds']];

                    return;
                }
            }
        }

        private function getAllowedBreakdownKeys(string $tab): array
        {
            return match ($tab) {
                'ig_accounts', 'ig_posts' => ['contact_button_type', 'follow_type', 'media_product_type'],
                'fb_pages', 'fb_posts' => ['reaction_type'],
                default => [],
            };
        }

        private function applyBreakdownFilters(array &$filters, string $tab, ?array $activeFilters = null): void
        {
            $activeFilters = $activeFilters ?? [];
            $allowedKeys = $this->getAllowedBreakdownKeys($tab);
            $hasAnyBreakdownFilter = false;

            foreach ($allowedKeys as $allowedKey) {
                $filterValues = array_values(array_filter((array)($activeFilters[$allowedKey] ?? []), static fn($v) => $v !== null && $v !== ''));
                if ($filterValues === []) {
                    continue;
                }

                $hasAnyBreakdownFilter = true;
                $filters['dimensions.'.$allowedKey] = count($filterValues) === 1
                    ? $filterValues[0]
                    : ['operator' => 'in', 'value' => $filterValues];
            }

            if (!$hasAnyBreakdownFilter) {
                if (str_starts_with($tab, 'ig_')) {
                    $filters['dimension_set_id'] = ['operator' => 'is_null'];
                } else {
                    unset($filters['dimension_set_id']);
                }

                return;
            }

            $filters['dimension_set_id'] = ['operator' => 'is_not_null'];
        }

        private function resolveBreakdownGroupBy(string $tab, ?string $breakdownTab = null): ?string
        {
            $allowedKeys = $this->getAllowedBreakdownKeys($tab);
            if ($allowedKeys === []) {
                return null;
            }

            if (!empty($breakdownTab) && in_array($breakdownTab, $allowedKeys, true)) {
                return 'dimensions.'.$breakdownTab;
            }

            return 'dimensions.'.$allowedKeys[0];
        }

        /**
         * @param array<int, array<string, mixed>> $rows
         * @param array<int, string> $metricKeys
         * @return array<int, array<string, mixed>>
         */
        private function collapseRowsByDate(array $rows, array $metricKeys): array
        {
            $collapsed = [];

            foreach ($rows as $row) {
                if (!is_array($row)) {
                    continue;
                }

                $date = $row['daily'] ?? $row['metric_date'] ?? $row['date'] ?? null;
                if (empty($date)) {
                    continue;
                }

                if (!isset($collapsed[$date])) {
                    $collapsed[$date] = array_fill_keys($metricKeys, 0);
                    $collapsed[$date]['daily'] = $date;
                }

                foreach ($metricKeys as $metricKey) {
                    $value = $row[$metricKey] ?? $row['trend_total_'.$metricKey] ?? null;
                    if ($value === null || $value === '') {
                        continue;
                    }

                    $collapsed[$date][$metricKey] += (float)$value;
                }
            }

            return array_values($collapsed);
        }

        private function areAllMetricValuesNull(array $row, array $metricKeys): bool
        {
            if (empty($row)) {
                return true;
            }

            foreach ($metricKeys as $metricKey) {
                if (array_key_exists($metricKey, $row) && $row[$metricKey] !== null) {
                    return false;
                }
            }

            return true;
        }

        private function collapseGroupedMetrics(array $rows, array $metricKeys): array
        {
            $collapsed = [];
            foreach ($metricKeys as $metricKey) {
                $collapsed[$metricKey] = 0;
            }

            foreach ($rows as $row) {
                if (!is_array($row)) {
                    continue;
                }

                foreach ($metricKeys as $metricKey) {
                    $value = $row[$metricKey] ?? null;
                    if ($value === null || $value === '') {
                        continue;
                    }

                    $collapsed[$metricKey] += (float)$value;
                }
            }

            return $collapsed;
        }

        /**
         * @param array<string, mixed> $response
         * @return array<int, string>
         */
        private function extractFacebookPageIdsFromAggregateResponse(array $response): array
        {
            $pageIds = [];

            foreach ((array)($response['data'] ?? []) as $row) {
                if (!is_array($row)) {
                    continue;
                }

                $pageId = $row['page_id'] ?? $row['PAGE_ID'] ?? null;
                if ($pageId === null || $pageId === '') {
                    continue;
                }

                $pageIds[] = (string)$pageId;
            }

            return array_values(array_unique($pageIds));
        }

        /**
         * @param array{fbAccountIds: array<int, string>, igAccountIds: array<int, string>, fbPlatformIds: array<int, string>, fbPageIds: array<int, string>} $accounts
         * @param array<string, mixed> $validated
         * @return array{fbAccountIds: array<int, string>, igAccountIds: array<int, string>, fbPlatformIds: array<int, string>, fbPageIds: array<int, string>}
         */
        private function hydrateResolvedFacebookPageIds(Project $tenant, RemoteEngineService $service, array $validated, array $accounts): array
        {
            if (($validated['activeTab'] ?? null) !== 'facebook' || $accounts['fbPageIds'] !== [] || $accounts['fbPlatformIds'] === []) {
                return $accounts;
            }

            $pagePlatformFilter = count($accounts['fbPlatformIds']) === 1
                ? $accounts['fbPlatformIds'][0]
                : ['operator' => 'in', 'value' => $accounts['fbPlatformIds']];

            $response = $service->aggregateChanneled($tenant, 'facebook_organic', 'metric', [
                'aggregations' => ['reach' => 'reach'],
                'filters'      => [
                    'account_type'     => 'facebook_page',
                    'channel'          => 'facebook_organic',
                    'period'           => 'daily',
                    'page_platform_id' => $pagePlatformFilter,
                ],
                'groupBy'      => ['page', 'page_id', 'page_title'],
                'startDate'    => $validated['dateStart'],
                'endDate'      => $validated['dateEnd'],
                'limit'        => 100,
            ]);

            $resolvedPageIds = $this->extractFacebookPageIdsFromAggregateResponse($response);
            if ($resolvedPageIds !== []) {
                $accounts['fbPageIds'] = $resolvedPageIds;
            }

            return $accounts;
        }

        /**
         * @param array<int, array<string, mixed>> $rows
         * @param array<int, string> $fbPlatformIds
         * @return array<int, array<string, mixed>>
         */
        private function filterFacebookPostRows(array $rows, array $fbPlatformIds): array
        {
            if ($fbPlatformIds === []) {
                return $rows;
            }

            $prefixes = array_map(static fn(string $id): string => $id.'_', $fbPlatformIds);

            return array_values(array_filter($rows, static function ($row) use ($prefixes): bool {
                if (!is_array($row)) {
                    return false;
                }

                $postId = (string)($row['post_id'] ?? $row['POST_ID'] ?? '');
                if ($postId === '') {
                    return false;
                }

                foreach ($prefixes as $prefix) {
                    if (str_starts_with($postId, $prefix)) {
                        return true;
                    }
                }

                return false;
            }));
        }

        /**
         * @param array<string, string> $aggregations
         * @return array<string, string>
         */
        private function buildChartAggregations(array $aggregations, bool $useTrendAliases): array
        {
            if (!$useTrendAliases) {
                return $aggregations;
            }

            $trendAggregations = [];
            foreach ($aggregations as $alias => $metric) {
                $trendAggregations['trend_total_'.$alias] = $metric;
            }

            return $trendAggregations;
        }

        /**
         * @param array<string, mixed> $context
         * @param array<string, mixed> $payloads
         */
        private function appendAggregationDebugLog(string $scope, array $context, array $payloads): void
        {
            try {
                $logPath = storage_path('logs/datasources_save_debug.log');
                $entry = [
                    'timestamp_utc' => gmdate('c'),
                    'scope'         => $scope,
                    'context'       => $context,
                    'payloads'      => $payloads,
                ];

                @file_put_contents(
                    $logPath,
                    json_encode($entry, JSON_UNESCAPED_SLASHES).PHP_EOL,
                    FILE_APPEND | LOCK_EX
                );
            } catch (\Throwable) {
                // Debug logging must never break API responses.
            }
        }

        /**
         * For non-summary Facebook queries, prefer the resolved internal page id when available.
         * This matches the internal APIs Hub post/breakdown flows more closely than platform-id-only filters.
         *
         * @param array<string, mixed> $filters
         * @param array{fbAccountIds: array<int, string>, igAccountIds: array<int, string>, fbPlatformIds: array<int, string>, fbPageIds: array<int, string>} $accounts
         */
        private function preferResolvedFacebookPageFilter(array &$filters, string $activeTab, string $internalTab, array $accounts): void
        {
            if ($activeTab !== 'facebook' || !in_array($internalTab, ['fb_pages', 'fb_posts'], true) || $accounts['fbPageIds'] === []) {
                return;
            }

            unset($filters['page_platform_id'], $filters['channeledAccount']);
        }

        public function summary(Request $request)
        {
            try {
                $validated = $this->validateRequest($request);
                $tenant = Project::findOrFail($validated['tenant']);
                $service = app(RemoteEngineService::class);

                $start = Carbon::parse($validated['dateStart']);
                $end = Carbon::parse($validated['dateEnd']);
                $diff = $start->diffInDays($end) + 1;

                $prevEnd = $start->copy()->subDay();
                $prevStart = $prevEnd->copy()->subDays($diff - 1);

                $internalTab = $validated['activeTab'] === 'facebook' ? 'fb_pages' : 'ig_accounts';
                $config = $this->getTabConfig($internalTab);

                $baseFilters = $config['filters'];
                $this->applyBreakdownFilters($baseFilters, $internalTab, $validated['activeFilters'] ?? null);

                $parsedAccounts = $this->parseSelectedAccounts($validated['account'] ?? []);
                $this->applySelectedAccountFilters($baseFilters, $validated['activeTab'], $internalTab, $parsedAccounts);

                $summaryGroupBy = $validated['activeTab'] === 'facebook' ? $config['groupBy'] : [];

                $payloads = [
                    'summary'  => [
                        'aggregations' => $config['aggregations'],
                        'groupBy'      => $summaryGroupBy,
                        'filters'      => $baseFilters,
                        'startDate'    => $validated['dateStart'],
                        'endDate'      => $validated['dateEnd']
                    ],
                    'previous' => [
                        'aggregations' => $config['aggregations'],
                        'groupBy'      => $summaryGroupBy,
                        'filters'      => $baseFilters,
                        'startDate'    => $prevStart->format('Y-m-d'),
                        'endDate'      => $prevEnd->format('Y-m-d')
                    ],
                ];

                $this->appendAggregationDebugLog('fbo.summary', [
                    'tenant'      => (string)$validated['tenant'],
                    'activeTab'   => (string)$validated['activeTab'],
                    'internalTab' => $internalTab,
                    'account'     => $validated['account'] ?? [],
                ], $payloads);

                \Illuminate\Support\Facades\Log::info("FBO Summary - Payloads for tab={$validated['activeTab']}", [
                    'internalTab'     => $internalTab,
                    'fbAccountIds'    => $parsedAccounts['fbAccountIds'],
                    'igAccountIds'    => $parsedAccounts['igAccountIds'],
                    'fbPlatformIds'   => $parsedAccounts['fbPlatformIds'],
                    'fbPageIds'       => $parsedAccounts['fbPageIds'],
                    'baseFilters'     => $baseFilters,
                    'summary_payload' => $payloads['summary'],
                ]);

                $results = $service->aggregateChanneledPool($tenant, 'facebook_organic', 'metric', $payloads);

                $metricKeys = array_keys($config['aggregations']);
                $summaryData = $results['summary']['data'] ?? [];
                $previousData = $results['previous']['data'] ?? [];

                if ($validated['activeTab'] === 'facebook') {
                    // FB can return page identity fields when grouped; collapse to metrics-only output for tiles.
                    $summaryRow = $this->collapseGroupedMetrics($summaryData, $metricKeys);
                    $previousRow = $this->collapseGroupedMetrics($previousData, $metricKeys);
                } else {
                    $summaryRow = (array)($summaryData[0] ?? []);
                    $previousRow = (array)($previousData[0] ?? []);
                }

                if ($validated['activeTab'] === 'instagram' && $this->areAllMetricValuesNull($summaryRow, $metricKeys)) {
                    $fallbackPayloads = [
                        'summary'  => [
                            'aggregations' => $config['aggregations'],
                            'groupBy'      => [],
                            'filters'      => $baseFilters,
                            'startDate'    => $validated['dateStart'],
                            'endDate'      => $validated['dateEnd'],
                        ],
                        'previous' => [
                            'aggregations' => $config['aggregations'],
                            'groupBy'      => $config['groupBy'],
                            'filters'      => $baseFilters,
                            'startDate'    => $prevStart->format('Y-m-d'),
                            'endDate'      => $prevEnd->format('Y-m-d'),
                        ],
                    ];

                    $fallbackResults = $service->aggregateChanneledPool($tenant, 'facebook_organic', 'metric', $fallbackPayloads);
                    $summaryRow = $this->collapseGroupedMetrics($fallbackResults['summary']['data'] ?? [], $metricKeys);
                    $previousRow = $this->collapseGroupedMetrics($fallbackResults['previous']['data'] ?? [], $metricKeys);
                }

                \Illuminate\Support\Facades\Log::info("FBO Summary - Raw results for tab={$validated['activeTab']}", [
                    'summary_data'   => $results['summary']['data'] ?? 'NO_DATA_KEY',
                    'summary_status' => $results['summary']['status'] ?? 'NO_STATUS',
                    'summary_error'  => $results['summary']['error'] ?? null,
                    'summary_meta'   => $results['summary']['meta'] ?? null,
                    'previous_data'  => $results['previous']['data'] ?? 'NO_DATA_KEY',
                ]);

                return response()->json([
                    'summary'  => !empty($summaryRow) ? $summaryRow : new \stdClass(),
                    'previous' => !empty($previousRow) ? $previousRow : new \stdClass(),
                ]);
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()], 500);
            }
        }

        public function chart(Request $request)
        {
            try {
                $validated = $this->validateRequest($request);
                $tenant = Project::findOrFail($validated['tenant']);
                $service = app(RemoteEngineService::class);

                $internalTab = $validated['activeTab'] === 'facebook' ? 'fb_pages' : 'ig_accounts';
                if (!empty($validated['postId'])) {
                    $internalTab = $validated['activeTab'] === 'facebook' ? 'fb_posts' : 'ig_posts';
                }
                $config = $this->getTabConfig($internalTab);

                $baseFilters = $config['filters'];
                $this->applyBreakdownFilters($baseFilters, $internalTab, $validated['activeFilters'] ?? null);

                $parsedAccounts = $this->parseSelectedAccounts($validated['account'] ?? []);
                if ($validated['activeTab'] === 'facebook') {
                    $parsedAccounts = $this->hydrateResolvedFacebookPageIds($tenant, $service, $validated, $parsedAccounts);
                }
                $this->applySelectedAccountFilters($baseFilters, $validated['activeTab'], $internalTab, $parsedAccounts);
                $this->preferResolvedFacebookPageFilter($baseFilters, $validated['activeTab'], $internalTab, $parsedAccounts);

                if (!empty($validated['postId'])) {
                    $baseFilters['post'] = $validated['postId'];
                    // For historic charts of posts, we want the daily deltas (which are virtual metrics generated from lifetime snapshots).
                    // We must query the lifetime snapshots to allow the backend to generate the deltas,
                    // so we only unset 'latest_snapshot' to get all historic records instead of just one.
                    unset($baseFilters['latest_snapshot']);
                }

                // Trend aliases are required only for post-level charts (snapshot-delta rendering).
                $useTrendAliases = !empty($validated['postId']);
                $aggregations = $this->buildChartAggregations($config['aggregations'], $useTrendAliases);

                $chartGroupBy = ['daily'];

                $payloads = [
                    'chart' => [
                        'aggregations' => $aggregations,
                        'groupBy'      => $chartGroupBy,
                        'filters'      => $baseFilters,
                        'startDate'    => $validated['dateStart'],
                        'endDate'      => $validated['dateEnd'],
                        'limit'        => 1000
                    ]
                ];

                $this->appendAggregationDebugLog('fbo.chart', [
                    'tenant'      => (string)$validated['tenant'],
                    'activeTab'   => (string)$validated['activeTab'],
                    'internalTab' => $internalTab,
                    'account'     => $validated['account'] ?? [],
                    'postId'      => $validated['postId'] ?? null,
                ], $payloads);

                $results = $service->aggregateChanneledPool($tenant, 'facebook_organic', 'metric', $payloads);
                $chartData = $results['chart']['data'] ?? [];

                if ($validated['activeTab'] === 'facebook' && is_array($chartData)) {
                    $chartData = $this->collapseRowsByDate($chartData, array_keys($config['aggregations']));
                }

                return response()->json([
                    'chart' => $chartData,
                ]);
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()], 500);
            }
        }

        public function table(Request $request)
        {
            try {
                $validated = $this->validateRequest($request);
                $tenant = Project::findOrFail($validated['tenant']);
                $service = app(RemoteEngineService::class);

                $tableMode = $validated['tableMode'] ?? 'posts';

                if ($tableMode === 'breakdown') {
                    $internalTab = $validated['activeTab'] === 'facebook' ? 'fb_pages' : 'ig_accounts';
                    $config = $this->getTabConfig($internalTab);
                    $breakdownGroupBy = $this->resolveBreakdownGroupBy($internalTab, $validated['breakdownTab'] ?? null);

                    $baseFilters = $config['filters'];
                    $this->applyBreakdownFilters($baseFilters, $internalTab, $validated['activeFilters'] ?? null);
                    unset($baseFilters['dimension_set_id']);

                    $parsedAccounts = $this->parseSelectedAccounts($validated['account'] ?? []);
                    if ($validated['activeTab'] === 'facebook') {
                        $parsedAccounts = $this->hydrateResolvedFacebookPageIds($tenant, $service, $validated, $parsedAccounts);
                    }
                    $this->applySelectedAccountFilters($baseFilters, $validated['activeTab'], $internalTab, $parsedAccounts);
                    $this->preferResolvedFacebookPageFilter($baseFilters, $validated['activeTab'], $internalTab, $parsedAccounts);

                    $payloads = [
                        'table' => [
                            'aggregations' => $config['aggregations'],
                            'groupBy'      => $breakdownGroupBy ? [$breakdownGroupBy] : [],
                            'filters'      => $baseFilters,
                            'startDate'    => $validated['dateStart'],
                            'endDate'      => $validated['dateEnd'],
                            'limit'        => 500
                        ]
                    ];

                    $this->appendAggregationDebugLog('fbo.table.breakdown', [
                        'tenant'       => (string)$validated['tenant'],
                        'activeTab'    => (string)$validated['activeTab'],
                        'internalTab'  => $internalTab,
                        'account'      => $validated['account'] ?? [],
                        'breakdownTab' => $validated['breakdownTab'] ?? null,
                    ], $payloads);

                    $results = $service->aggregateChanneledPool($tenant, 'facebook_organic', 'metric', $payloads);
                    $tableData = $results['table']['data'] ?? [];

                    foreach ($tableData as &$row) {
                        $rowLower = array_change_key_case($row, CASE_LOWER);
                        $breakdownKey = strtolower(str_replace('dimensions.', '', (string)$breakdownGroupBy));
                        $breakdownValue = $rowLower[$breakdownKey]
                            ?? $rowLower['dimensions.'.$breakdownKey]
                            ?? $rowLower['id']
                            ?? null;
                        $row['id'] = $breakdownValue ?? 'Unknown';
                        $row['name'] = $breakdownValue ?? 'Unknown';
                    }

                    return response()->json([
                        'table' => $tableData,
                    ]);
                }

                $internalTab = $validated['activeTab'] === 'facebook' ? 'fb_posts' : 'ig_posts';
                $config = $this->getTabConfig($internalTab);

                $baseFilters = $config['filters'];

                $parsedAccounts = $this->parseSelectedAccounts($validated['account'] ?? []);
                if ($validated['activeTab'] === 'facebook') {
                    $parsedAccounts = $this->hydrateResolvedFacebookPageIds($tenant, $service, $validated, $parsedAccounts);
                }
                $this->applySelectedAccountFilters($baseFilters, $validated['activeTab'], $internalTab, $parsedAccounts);
                $this->preferResolvedFacebookPageFilter($baseFilters, $validated['activeTab'], $internalTab, $parsedAccounts);

                $payloads = [
                    'table' => [
                        'aggregations' => $config['aggregations'],
                        'groupBy'      => $config['groupBy'],
                        'filters'      => $baseFilters,
                        'startDate'    => $validated['dateStart'],
                        'endDate'      => $validated['dateEnd'],
                        'limit'        => 500
                    ]
                ];

                $this->appendAggregationDebugLog('fbo.table.posts', [
                    'tenant'      => (string)$validated['tenant'],
                    'activeTab'   => (string)$validated['activeTab'],
                    'internalTab' => $internalTab,
                    'account'     => $validated['account'] ?? [],
                ], $payloads);

                $results = $service->aggregateChanneledPool($tenant, 'facebook_organic', 'metric', $payloads);
                $tableData = $results['table']['data'] ?? [];

                if ($validated['activeTab'] === 'facebook') {
                    $tableData = $this->filterFacebookPostRows($tableData, $parsedAccounts['fbPlatformIds']);
                }

                // Normalize ID and Name for frontend table rendering
                foreach ($tableData as &$row) {
                    $rowLower = array_change_key_case($row, CASE_LOWER);

                    if (isset($rowLower['post_id'])) {
                        $row['id'] = $rowLower['post_id'];
                        // Limit name length for posts
                        $name = $rowLower['message'] ?? $rowLower['caption'] ?? $rowLower['post_id'];
                        $row['name'] = mb_strlen($name) > 50 ? mb_substr($name, 0, 47).'...' : $name;
                    } elseif (isset($rowLower['page_id'])) {
                        $row['id'] = $rowLower['page_id'];
                        $row['name'] = $rowLower['page_title'] ?? $rowLower['page'] ?? $rowLower['page_id'];
                    } elseif (isset($rowLower['channeled_account_id'])) {
                        $row['id'] = $rowLower['channeled_account_id'];
                        $row['name'] = $rowLower['channeledaccount'] ?? $rowLower['channeled_account_id'];
                    } else {
                        $row['id'] = 'Unknown';
                        $row['name'] = 'Unknown';
                    }
                }

                return response()->json([
                    'table' => $tableData,
                ]);
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()], 500);
            }
        }

        public function post(Request $request)
        {
            try {
                $validated = $request->validate([
                    'tenant' => 'required|string',
                    'postId' => 'required|string',
                ]);

                $tenant = Project::findOrFail($validated['tenant']);
                $service = app(RemoteEngineService::class);

                // Query the orchestrator for the specific post entity
                $results = $service->listChanneled($tenant, 'facebook_organic', 'post', [
                    'limit'  => 1,
                    'postId' => $validated['postId'] // Doctrine ORM expects camelCase property name
                ]);

                return response()->json([
                    'post' => $results['data'][0] ?? null,
                ]);
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()], 500);
            }
        }
    }
