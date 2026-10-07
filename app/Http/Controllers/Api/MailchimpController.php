<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Services\RemoteEngineService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class MailchimpController extends Controller
{
    /**
     * Canonical Mailchimp metrics exposed by the worker's metric store.
     * Keys are the canonical metric names resolved by
     * MailchimpDriver::getCanonicalMetricDictionary().
     */
    private const AVAILABLE_AGGS = [
        'sends' => 'sends',
        'opens' => 'opens',
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
    ];

    private function validateRequest(Request $request): array
    {
        return $request->validate([
            'tenant' => 'required|integer',
            'account' => 'required|string',
            'dateStart' => 'required|date',
            'dateEnd' => 'required|date',
            'activeTab' => 'nullable|string|in:campaigns,automations,audiences,urls',
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
    }

    private function getRequestedAggregations(Request $request): array
    {
        $requestedMetrics = $request->input('metrics');

        if (empty($requestedMetrics)) {
            return self::AVAILABLE_AGGS;
        }

        $aggregations = [];
        foreach ((array) $requestedMetrics as $metric) {
            if (isset(self::AVAILABLE_AGGS[$metric])) {
                $aggregations[$metric] = self::AVAILABLE_AGGS[$metric];
            }
        }

        return empty($aggregations) ? self::AVAILABLE_AGGS : $aggregations;
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
                if ($value !== null && $value !== '') {
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
                'channeledAccount' => (string) $validated['account'],
            ];
            $this->applyDynamicFilters($baseFilters, $validated['activeFilters'] ?? null);

            $aggs = $this->getRequestedAggregations($request);

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

            $results = $service->aggregateChanneledPool($tenant, 'mailchimp', 'metric', $payloads);

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
                'channeledAccount' => (string) $validated['account'],
            ];
            $this->applyDynamicFilters($baseFilters, $validated['activeFilters'] ?? null, $validated['filters'] ?? null);

            $aggs = $this->getRequestedAggregations($request);

            $groupBy = ['daily'];
            if (!empty($validated['groupBy'])) {
                $groupBy = $validated['groupBy'];
            } elseif (!empty($validated['breakdown'])) {
                $groupBy = ['daily', $validated['breakdown']];
            }

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

            $results = $service->aggregateChanneledPool($tenant, 'mailchimp', 'metric', $payloads);

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

            $rows = $results['chart']['data'] ?? [];

            return response()->json([
                'chart' => $this->normalizeChartRows($rows),
                'debug_results' => config('app.debug') ? $results : null,
                'retryable' => !empty($results['chart']['retryable']),
            ]);
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
            $aggs = $this->getRequestedAggregations($request);

            $tableFilters = [
                'channel' => 'mailchimp',
                'channeledAccount' => (string) $validated['account'],
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
            $this->applyDynamicFilters($tableFilters, $validated['activeFilters'] ?? null, $validated['filters'] ?? null);

            $tabPayload = [
                'aggregations' => $aggs,
                'groupBy' => [self::TAB_DIMENSIONS[$tab] ?? self::TAB_DIMENSIONS['campaigns']],
                'filters' => $tableFilters,
                'startDate' => $validated['dateStart'],
                'endDate' => $validated['dateEnd'],
                'limit' => 5000, // reasonable limit for frontend rendering
            ];

            // A metric widget may request a custom dimension instead of a tab dimension.
            if (!empty($validated['breakdown'])) {
                $tabPayload['groupBy'] = [$validated['breakdown']];
            }

            $payloads = ['table' => $tabPayload];
            $results = $service->aggregateChanneledPool($tenant, 'mailchimp', 'metric', $payloads);

            if (($results['table']['status'] ?? null) === 'error') {
                \Illuminate\Support\Facades\Log::error("Mailchimp Table APIs Hub Error: " . json_encode($results['table']));
            }

            $tableData = $results['table']['data'] ?? [];

            return response()->json([
                'table' => $this->normalizeTableRows($tableData, $tabPayload['groupBy'][0], $tab),
                'debug_results' => config('app.debug') ? $results : null,
                'retryable' => !empty($results['table']['retryable']),
            ]);
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
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Mailchimp Trend Proxy Error: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Coerce aggregate rows into a stable shape for the chart renderer:
     * a `daily` date string plus numeric counters.
     */
    private function normalizeChartRows($rows): array
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

            if (empty($date)) {
                continue;
            }

            $entry = ['daily' => (string) $date];

            foreach (self::AVAILABLE_AGGS as $metric => $_) {
                $entry[$metric] = (float) ($lower[$metric] ?? 0);
            }

            $normalized[] = $entry;
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
        string $activeTab = 'campaigns'
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
                if (array_key_exists($metric, $lower)) {
                    $entry[$metric] = (float) $lower[$metric];
                }
            }

            $entry = $this->decorateSummary($entry);
            $entry['id'] = (string) $value;
            $entry['name'] = (string) $value;

            $normalized[] = $entry;
        }

        return $normalized;
    }
}
