<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Services\RemoteEngineService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class MailchimpController extends Controller
{
    use Concerns\AppliesEntityAttributeFilters;

    /**
     * Canonical Mailchimp metrics exposed by the worker's metric store.
     * Keys are the canonical metric names resolved by
     * MailchimpDriver::getCanonicalMetricDictionary().
     */
    private const DAILY_UNIQUE_OPENS = 'opens_unique_daily';

    private const AVAILABLE_AGGS = [
        'sends' => 'sends',
        'opens' => 'opens',
        self::DAILY_UNIQUE_OPENS => self::DAILY_UNIQUE_OPENS,
        'opens_standard' => 'opens_standard',
        'opens_proxy' => 'opens_proxy',
        'clicks' => 'clicks',
        'clicks_unique' => 'clicks_unique',
        'bounces' => 'bounces',
        'bounces_hard' => 'bounces_hard',
        'bounces_soft' => 'bounces_soft',
        'unsubscribes' => 'unsubscribes',
        'orders' => 'orders',
        'revenue' => 'revenue',
    ];

    /**
     * Breakdown dimension used by each Data Explorer tab.
     */
    private const TAB_DIMENSIONS = [
        'campaigns' => 'channeledCampaign',
        'automations' => 'channeledCampaign',
        'audiences' => 'channeledAccount',
        'urls' => 'dimensions.page',
        'stores' => 'dimensions.store_id',
    ];

    private function validateRequest(Request $request): array
    {
        $validated = $request->validate([
            'tenant' => 'required|integer',
            'account' => ['required', function ($attribute, $value, $fail) {
                $ids = is_array($value) ? $value : [$value];
                if ($ids === [] || array_filter($ids, static fn ($id) => !is_scalar($id) || !ctype_digit((string)$id) || (int)$id < 1)) {
                    $fail(__('Select valid audience accounts.'));
                }
            }],
            'dateStart' => 'required|date',
            'dateEnd' => 'required|date',
            'activeTab' => 'nullable|string|in:campaigns,automations,audiences,urls,stores',
            'dependency' => 'nullable|string|in:campaign_engagement,store_conversions',
            'asset_group' => 'nullable|string',
            'campaignType' => 'nullable|string|in:all,regular,rss,variate,plaintext',
            'activeFilters' => 'nullable|array',
            'activeFilters.*' => 'nullable',
            'filters' => 'nullable|array',
            'breakdown' => 'nullable|string',
            'groupBy' => 'nullable|array',
            'groupBy.*' => 'string',
            'metrics' => 'nullable|array',
            'metrics.*' => 'string',
        ]);
        if (($validated['dependency'] ?? null) === 'store_conversions') {
            $validated['activeTab'] = 'stores';
        }

        if (($validated['activeTab'] ?? null) === 'stores') {
            if (!empty($validated['breakdown']) && !in_array($validated['breakdown'], ['dimensions.store_id', 'dimensions.currency'], true)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'breakdown' => 'Store metrics support store and currency breakdowns.',
                ]);
            }
            foreach (array_keys($validated['filters'] ?? []) as $key) {
                if ((str_starts_with($key, 'dimensions.') && !in_array($key, ['dimensions.store_id', 'dimensions.currency'], true))
                    || in_array($key, ['channeledCampaign', 'campaignType'], true)) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'filters' => 'Use store or currency filters for store metrics.',
                    ]);
                }
            }
        }

        return $validated;
    }

    private function getRequestedAggregations(Request $request, array $filters, array $groupBy): array
    {
        $storeMetrics = ['orders' => 'orders', 'revenue' => 'revenue'];
        $storeScope = $request->input('activeTab') === 'stores' || $request->input('dependency') === 'store_conversions';
        $available = $storeScope ? $storeMetrics : array_diff_key(self::AVAILABLE_AGGS, $storeMetrics);
        if (isset($filters['dimensions.page']) || in_array('dimensions.page', $groupBy, true)) {
            $available = array_intersect_key($available, array_flip(['clicks', 'clicks_unique']));
        }

        $requestedMetrics = (array) $request->input('metrics');
        foreach (['open_rate' => 'opens', 'click_rate' => 'clicks'] as $rate => $counter) {
            if (in_array($rate, $requestedMetrics, true)) {
                $requestedMetrics[] = $counter;
                $requestedMetrics[] = 'sends';
            }
        }
        $aggregations = array_intersect_key($available, array_fill_keys($requestedMetrics, true));

        return $aggregations ?: $available;
    }

    /**
     * Build the filter bag shared by every Mailchimp Data Explorer request.
     *
     * The dashboard account selector sends channeled_account IDs (Mailchimp audiences/lists),
     * so `channeledAccount` is always the primary asset scope. Cross-tab breakdowns add the
     * secondary dimension on top of it.
     */
    private function applyDynamicFilters(array &$filters, ?array $activeFilters, ?array $explicitFilters = null): void
    {
        $filters['channeledAccount'] = $filters['channeledAccount'] ?? null;

        if (!empty($explicitFilters)) {
            foreach ($explicitFilters as $key => $value) {
                if ($key !== 'channeledAccount' && $value !== null && $value !== '') {
                    $filters[$key] = $value;
                }
            }
        }

        if (empty($activeFilters)) {
            return;
        }

        // Cross-filter between the two breakdown tabs: selecting a campaign narrows the
        // audience tab and vice versa, exactly like the GSC/FB explorers do.
        $tabDimensionMap = self::TAB_DIMENSIONS;

        foreach ($tabDimensionMap as $tab => $dimension) {
            $values = $activeFilters[$tab] ?? null;
            if (empty($values) || !is_array($values)) {
                continue;
            }

            $values = array_values(array_filter(array_map(
                static fn ($v) => $v === null ? null : trim((string) $v),
                $values
            ), static fn ($v) => $v !== null && $v !== '' && $v !== 'N/A'));

            if ($values === []) {
                continue;
            }

            // A single value is sent as a scalar; several values become an `in` filter.
            $filters[$dimension] = count($values) === 1
                ? $values[0]
                : ['operator' => 'in', 'value' => $values];
        }
    }

    /**
     * Derive the ratio metrics shown as tiles from the raw counters.
     * Open/click rates are ratios of the selected window, never averages of daily ratios.
     */
    private function decorateSummary(array $row): array
    {
        $row = is_array($row) ? $row : [];

        $sends = (float) ($row['sends'] ?? 0);
        $opens = (float) ($row['opens'] ?? 0);
        $clicks = (float) ($row['clicks'] ?? 0);

        $row[self::DAILY_UNIQUE_OPENS] = isset($row[self::DAILY_UNIQUE_OPENS])
            ? (float) $row[self::DAILY_UNIQUE_OPENS]
            : null;
        $row['open_rate'] = $sends > 0 ? round($opens / $sends, 6) : 0.0;
        $row['click_rate'] = $sends > 0 ? round($clicks / $sends, 6) : 0.0;

        return $row;
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

            $baseFilters = [
                'channel' => 'mailchimp',
                'channeledAccount' => $this->accountFilter($validated['account']),
            ];
            $this->applyDynamicFilters(
                $baseFilters,
                ($validated['activeTab'] ?? null) === 'stores' ? null : ($validated['activeFilters'] ?? null),
                $validated['filters'] ?? null,
            );

            $aggs = $this->getRequestedAggregations($request, $baseFilters, []);

            $payloads = [
                'summary' => [
                    'aggregations' => $aggs,
                    'groupBy' => [],
                    'filters' => $baseFilters,
                    'startDate' => $validated['dateStart'],
                    'endDate' => $validated['dateEnd'],
                ],
                'previous' => [
                    'aggregations' => $aggs,
                    'groupBy' => [],
                    'filters' => $baseFilters,
                    'startDate' => $prevStart->format('Y-m-d'),
                    'endDate' => $prevEnd->format('Y-m-d'),
                ],
            ];

            $results = $service->aggregateChanneledPool($tenant, 'mailchimp', 'metric', $this->withEntityAttributeFilters($payloads, $validated));
            if (($validated['activeTab'] ?? null) === 'stores') {
                foreach ($results as $result) {
                    if (($result['status'] ?? null) === 'error') {
                        throw new \RuntimeException('Store metrics could not be loaded. Please retry.');
                    }
                }
            }

            $retryable = false;
            foreach (['summary', 'previous'] as $key) {
                if (($results[$key]['status'] ?? null) === 'error') {
                    \Illuminate\Support\Facades\Log::error("Mailchimp {$key} APIs Hub Error: " . json_encode($results[$key]));
                    $retryable = $retryable || !empty($results[$key]['retryable']);
                }
            }

            return response()->json([
                'summary' => $this->decorateSummary($results['summary']['data'][0] ?? []),
                'previous' => $this->decorateSummary($results['previous']['data'][0] ?? []),
                'debug_results' => config('app.debug') ? $results : null,
                'retryable' => $retryable,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => $e->getMessage(), 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Mailchimp Summary Error: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function chart(Request $request)
    {
        try {
            $validated = $this->validateRequest($request);
            $tenant = Project::findOrFail($validated['tenant']);
            $service = app(RemoteEngineService::class);

            $baseFilters = [
                'channel' => 'mailchimp',
                'channeledAccount' => $this->accountFilter($validated['account']),
            ];
            $this->applyDynamicFilters(
                $baseFilters,
                ($validated['activeTab'] ?? null) === 'stores' ? null : ($validated['activeFilters'] ?? null),
                $validated['filters'] ?? null,
            );

            $groupBy = ['daily'];
            if (!empty($validated['groupBy'])) {
                $groupBy = $validated['groupBy'];
            } elseif (!empty($validated['breakdown'])) {
                $groupBy = ['daily', $validated['breakdown']];
            }

            $aggs = $this->getRequestedAggregations($request, $baseFilters, $groupBy);
            $payloads = [
                'chart' => [
                    'aggregations' => $aggs,
                    'groupBy' => $groupBy,
                    'filters' => $baseFilters,
                    'startDate' => $validated['dateStart'],
                    'endDate' => $validated['dateEnd'],
                    'limit' => 5000, // ensure all days of the selected range are returned
                ],
            ];

            \Illuminate\Support\Facades\Log::info('[MAILCHIMP_DEBUG] Chart calling aggregateChanneledPool', [
                'tenant' => $tenant->id,
                'groupBy' => $groupBy,
                'aggregations' => array_keys($aggs),
            ]);

            if (isset($aggs['revenue'])) {
                $payloads['currency_guard'] = $payloads['chart'];
                $payloads['currency_guard']['aggregations'] = ['revenue' => 'revenue'];
                $payloads['currency_guard']['groupBy'] = [];
            }

            $results = $service->aggregateChanneledPool($tenant, 'mailchimp', 'metric', $this->withEntityAttributeFilters($payloads, $validated));
            if (($validated['activeTab'] ?? null) === 'stores') {
                foreach ($results as $result) {
                    if (($result['status'] ?? null) === 'error') {
                        throw new \RuntimeException('Store metrics could not be loaded. Please retry.');
                    }
                }
            }

            \Illuminate\Support\Facades\Log::info('[MAILCHIMP_DEBUG] Chart received from aggregateChanneledPool', [
                'status' => $results['chart']['status'] ?? null,
                'rowCount' => isset($results['chart']['data']) && is_array($results['chart']['data']) ? count($results['chart']['data']) : 0,
                'sample' => isset($results['chart']['data']) && is_array($results['chart']['data']) ? array_slice($results['chart']['data'], 0, 3) : null,
                'error' => $results['chart']['error'] ?? $results['chart']['message'] ?? null,
                'meta' => $results['chart']['meta'] ?? null,
            ]);

            if (($results['chart']['status'] ?? null) === 'error') {
                \Illuminate\Support\Facades\Log::error("Mailchimp Chart APIs Hub Error: " . json_encode($results['chart']));
            }

            $rows = $this->normalizeChartRows($results['chart']['data'] ?? [], $validated['breakdown'] ?? null);
            if (isset($aggs['revenue']) && !isset($results['currency_guard']['data'][0]['revenue'])) {
                foreach ($rows as &$row) {
                    $row['revenue'] = null;
                }
                unset($row);
            }
            return response()->json([
                'chart' => $rows,
                'debug_results' => config('app.debug') ? $results : null,
                'retryable' => !empty($results['chart']['retryable']),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => $e->getMessage(), 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Mailchimp Chart Error: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function table(Request $request)
    {
        try {
            $validated = $this->validateRequest($request);
            $tenant = Project::findOrFail($validated['tenant']);
            $service = app(RemoteEngineService::class);

            $tab = $validated['activeTab'] ?? 'campaigns';
            $tableFilters = [
                'channel' => 'mailchimp',
                'channeledAccount' => $this->accountFilter($validated['account']),
            ];
            if ($tab === 'campaigns') {
                $typeFilter = $validated['campaignType'] ?? 'all';
                if ($typeFilter && $typeFilter !== 'all') {
                    $tableFilters['campaignType'] = $typeFilter;
                } else {
                    $tableFilters['campaignType'] = [
                        'operator' => 'in',
                        'value' => ['regular', 'rss', 'variate', 'ab_split', 'plaintext']
                    ];
                }
            } elseif ($tab === 'automations') {
                $tableFilters['campaignType'] = ['operator' => 'in', 'value' => ['automation', 'automation-email']];
            }
            $this->applyDynamicFilters($tableFilters, $tab === 'stores' ? null : ($validated['activeFilters'] ?? null), $validated['filters'] ?? null);

            $tabPayload = [
                'groupBy' => [self::TAB_DIMENSIONS[$tab] ?? self::TAB_DIMENSIONS['campaigns']],
                'filters' => $tableFilters,
                'startDate' => $validated['dateStart'],
                'endDate' => $validated['dateEnd'],
                'limit' => 5000, // reasonable limit for frontend rendering
            ];

            // A metric widget may request a custom dimension instead of a tab dimension.
            if ($tab === 'stores') {
                $tabPayload['groupBy'] = ($validated['breakdown'] ?? null) === 'dimensions.currency'
                    ? ['dimensions.currency']
                    : ['dimensions.store_id', 'dimensions.currency'];
            } elseif (!empty($validated['breakdown'])) {
                $tabPayload['groupBy'] = [$validated['breakdown']];
            }

            $tabPayload['aggregations'] = $this->getRequestedAggregations($request, $tableFilters, $tabPayload['groupBy']);
            $payloads = ['table' => $tabPayload];
            $results = $service->aggregateChanneledPool($tenant, 'mailchimp', 'metric', $this->withEntityAttributeFilters($payloads, $validated));
            if (($validated['activeTab'] ?? null) === 'stores') {
                foreach ($results as $result) {
                    if (($result['status'] ?? null) === 'error') {
                        throw new \RuntimeException('Store metrics could not be loaded. Please retry.');
                    }
                }
            }

            if (($results['table']['status'] ?? null) === 'error') {
                \Illuminate\Support\Facades\Log::error("Mailchimp Table APIs Hub Error: " . json_encode($results['table']));
            }

            $tableData = $results['table']['data'] ?? [];

            $rows = $this->normalizeTableRows($tableData, $tabPayload['groupBy'][0], $tab, $tenant);
            if ($tab === 'stores' && $tabPayload['groupBy'][0] === 'dimensions.store_id') {
                $rows = $this->withStoreMetadata($rows, $tenant, $validated, $service, $validated['asset_group'] ?? null);
            }

            return response()->json([
                'table' => $rows,
                'debug_results' => config('app.debug') ? $results : null,
                'retryable' => !empty($results['table']['retryable']),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => $e->getMessage(), 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Mailchimp Table Error: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function trend(Request $request)
    {
        try {
            $validated = $request->validate([
                'tenant' => 'required|integer',
                'metric' => 'required|string',
                'series' => 'required|array',
                'series.dates' => 'required|array',
                'series.values' => 'required|array',
            ]);

            $tenant = Project::findOrFail($validated['tenant']);
            $service = app(RemoteEngineService::class);

            $payload = [
                'metric' => $validated['metric'],
                'series' => $validated['series'],
            ];

            $linearResult = $service->getTrend('linear', $payload);

            $smaPayload = array_merge($payload, ['window' => 28]);
            $smaResult = $service->getTrend('sma', $smaPayload);

            $trendData = [];

            if (isset($linearResult['success']) && $linearResult['success']) {
                $trendData['trend_linear'] = $linearResult['trend'] ?? [];
            }

            if (isset($smaResult['success']) && $smaResult['success']) {
                $trendData['trend_sma'] = $smaResult['trend'] ?? [];
            }

            if (empty($trendData)) {
                return response()->json(['success' => false, 'error' => 'No trend calculated']);
            }

            return response()->json(['trend' => array_merge(['success' => true], $trendData)]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => $e->getMessage(), 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Mailchimp Trend Proxy Error: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Coerce aggregate rows into a stable shape for the chart renderer:
     * a `daily` date string plus numeric counters.
     */
    private function normalizeChartRows($rows, ?string $breakdown = null): array
    {
        if (!is_array($rows)) {
            return [];
        }

        $normalized = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $lower = array_change_key_case($row, CASE_LOWER);
            $date = $lower['daily'] ?? $lower['date'] ?? $lower['metric_date'] ?? null;

            // When broken down without daily date (e.g. lifetime breakdown), keep the row
            if (empty($date) && empty($breakdown)) {
                continue;
            }

            $entry = $row;
            if (!empty($date)) {
                $entry['daily'] = (string) $date;
            }

            foreach (self::AVAILABLE_AGGS as $metric => $_) {
                if ($metric === self::DAILY_UNIQUE_OPENS) {
                    $entry[$metric] = isset($lower[$metric]) ? (float) $lower[$metric] : null;
                } elseif (isset($lower[$metric])) {
                    $entry[$metric] = (float) ($lower[$metric] ?? 0);
                }
            }

            $normalized[] = $this->decorateSummary($entry);
        }

        return $normalized;
    }

    /**
     * Give every table row an `id`/`name` the frontend can sort, filter and display,
     * append the derived open/click rate so the table can show them too,
     * exclude unassigned/rollup rows when breaking down by individual campaigns,
     * and filter rows based on the active breakdown tab (campaigns vs automations).
     */
    private function normalizeTableRows(
        $rows,
        string $dimensionKey,
        string $activeTab = 'campaigns',
        ?Project $tenant = null
    ): array {
        if (!is_array($rows)) {
            return [];
        }

        $dimensionFull = strtolower($dimensionKey);
        $dimensionStripped = strtolower(str_replace(['channeled', 'dimensions.'], '', $dimensionKey));
        $isCampaignDimension = in_array($dimensionStripped, ['campaign', 'channeledcampaign'], true);

        $normalized = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $lower = array_change_key_case($row, CASE_LOWER);

            $idVal = $lower[$dimensionFull . '_id']
                ?? $lower[$dimensionStripped . '_id']
                ?? $lower['page_id']
                ?? $lower['id']
                ?? null;

            $value = $lower[$dimensionFull]
                ?? $lower[$dimensionStripped]
                ?? $lower['page']
                ?? $lower['name']
                ?? $lower['id']
                ?? null;

            // When grouping by campaign, omit unassigned account-level rollup rows (N/A / Unknown)
            if ($isCampaignDimension && ($idVal === null || $value === null || $value === '' || $value === 'N/A' || $value === 'Unknown' || $value === '(not set)')) {
                continue;
            }

            // When grouping by URLs / pages, omit unknown rollup rows
            if ($activeTab === 'urls' && ($value === null || $value === '' || $value === 'N/A' || $value === 'unknown' || $value === 'Unknown' || $value === '(not set)')) {
                continue;
            }

            if ($value === null || $value === '' || $value === 'N/A' || $value === '(not set)') {
                $value = 'Unknown';
            }

            $entry = $row;
            $entry['id'] = (string) $value;
            $entry['name'] = (string) $value;

            if ($isCampaignDimension) {
                $rawType = $lower['type'] ?? $lower['campaigntype'] ?? $lower['campaign_type'] ?? null;
                $entry['campaign_type'] = $rawType ? (string) $rawType : ($activeTab === 'automations' ? 'automation' : 'regular');
            }

            foreach (self::AVAILABLE_AGGS as $metric => $_) {
                if ($metric === self::DAILY_UNIQUE_OPENS) {
                    $entry[$metric] = isset($lower[$metric]) ? (float) $lower[$metric] : null;
                } elseif (array_key_exists($metric, $lower)) {
                    $entry[$metric] = $lower[$metric] === null ? null : (float) $lower[$metric];
                }
            }

            $entry = $this->decorateSummary($entry);
            $entry['id'] = (string) $value;
            $entry['name'] = (string) $value;
            if ($activeTab === 'stores') {
                if ($dimensionKey === 'dimensions.store_id') $entry['store_id'] = (string)$value;
                $entry['currency'] = $lower['currency'] ?? $lower['dimensions.currency'] ?? null;
                $entry['id'] .= ':' . $entry['currency'];
                if ($entry['currency'] === null || (!isset($entry['orders']) && !isset($entry['revenue']))) {
                    continue;
                }
            }
            $entry['catalog_url'] = app(\App\Services\Catalog\CatalogLinkRenderer::class)->forExplorerRow(
                $tenant,
                'mailchimp',
                $activeTab,
                $entry
            );

            $normalized[] = $entry;
        }

        return $normalized;
    }

    private function accountFilter(string|array $accounts): string|array
    {
        $accounts = array_values(array_unique(array_map('strval', (array)$accounts)));
        return count($accounts) === 1 ? $accounts[0] : ['operator' => 'in', 'value' => $accounts];
    }

    private function withStoreMetadata(array $rows, Project $project, array $validated, RemoteEngineService $service, ?string $scope): array
    {
        $ids = array_values(array_unique(array_column($rows, 'store_id')));
        $stores = [];
        foreach (array_chunk($ids, 100) as $chunk) {
            $response = $service->listCatalogEntities($project, 'stores', [
                'authorized_scope' => ['channels' => ['mailchimp' => [
                    'account_ids' => array_map('intval', (array)$validated['account']),
                    'platform_ids' => [],
                ]]],
                'filters' => ['channel' => 'mailchimp', 'platform_ids' => $chunk],
                'pagination' => ['page' => 1, 'per_page' => 100],
            ]);
            if (($response['status'] ?? null) === 'error') break;
            foreach ($response['data'] ?? [] as $store) {
                $reference = \App\Services\Catalog\EntityReference::tryParse($store['reference'] ?? '');
                if (!$reference || $reference->getEntityType() !== 'stores' || $reference->getChannel() !== 'mailchimp') continue;
                $stores[$store['platform_id']] = $store;
            }
        }
        foreach ($rows as &$row) {
            $store = $stores[$row['store_id']] ?? null;
            if (!$store) continue;
            $row['name'] = $store['name'];
            $row['domain'] = $store['attributes']['domain'] ?? null;
            $row['source_store_id'] = $store['attributes']['source_store_id'] ?? null;
            $row['catalog_url'] = \App\Services\Catalog\EntityReference::buildUrl('stores', $store['reference'],
                ['scope' => $scope, 'channel' => 'mailchimp'], $project);
        }
        unset($row);
        return $rows;
    }
}
