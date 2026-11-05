<?php

declare(strict_types=1);

namespace App\Services\Catalog;

use App\Models\Project;

final class CatalogLinkRenderer
{
    private const array NON_ENTITY_NAMES = [
        'total',
        'totals',
        'unknown',
        '(not set)',
        '(not provided)',
        '(other)',
        'n/a',
        'none',
        '-',
        '—',
    ];

    /**
     * Build a tenant-scoped Catalog Detail URL for a verified entity reference.
     *
     * @param  array<string, mixed>  $extraQuery
     */
    public function recordUrl(
        string $layer,
        string $channel,
        string $entityType,
        int|string $id,
        ?Project $tenant = null,
        ?string $scope = null,
        array $extraQuery = []
    ): ?string {
        if (! is_numeric($id) || (int) $id <= 0) {
            return null;
        }

        if (! EntityReference::isSupported($entityType)) {
            return null;
        }

        try {
            $reference = EntityReference::format($layer, $channel, $entityType, (int) $id);
        } catch (\Throwable) {
            return null;
        }

        $query = array_merge([
            'scope' => ($scope !== null && $scope !== '' && $scope !== 'all') ? $scope : null,
        ], $extraQuery);

        return EntityReference::buildUrl($entityType, $reference, $query, $tenant);
    }

    /**
     * Build a tenant-scoped Catalog List URL pre-filtered by channel and search text.
     *
     * @param  array<string, mixed>  $extraQuery
     */
    public function listSearchUrl(
        string $entityType,
        string $channel,
        string $searchText,
        ?Project $tenant = null,
        ?string $scope = null,
        array $extraQuery = []
    ): ?string {
        if (! EntityReference::isSupported($entityType)) {
            return null;
        }

        $cleanSearch = trim($searchText);
        if (! $this->isResolvableDisplayLabel($cleanSearch)) {
            return null;
        }

        $query = array_merge([
            'scope'   => ($scope !== null && $scope !== '' && $scope !== 'all') ? $scope : null,
            'channel' => strtolower(trim($channel)),
            'search'  => $cleanSearch,
        ], $extraQuery);

        return EntityReference::buildUrl($entityType, null, $query, $tenant);
    }

    /**
     * Resolve a Catalog deep link for an authenticated Data Explorer or dashboard table row.
     * Returns null for totals rows, non-catalog breakdown tabs, or unverified label-only rows.
     *
     * @param  array<string, mixed>  $row
     */
    public function forExplorerRow(
        ?Project $tenant,
        string $channel,
        string $tab,
        array $row,
        ?string $scope = null
    ): ?string {
        if (! empty($row['is_total']) || ! empty($row['is_summary'])) {
            return null;
        }

        $lower = array_change_key_case($row, CASE_LOWER);
        $nameVal = trim((string) ($row['name'] ?? $lower['name'] ?? ''));
        if ($nameVal !== '' && ! $this->isResolvableDisplayLabel($nameVal)) {
            return null;
        }

        $normChannel = strtolower(trim($channel));
        $normTab = strtolower(trim($tab));

        return match ($normChannel) {
            'facebook_marketing'    => $this->resolveFacebookMarketingRow($tenant, $normTab, $lower, $scope),
            'google_ads'            => $this->resolveGoogleAdsRow($tenant, $normTab, $lower, $scope),
            'facebook_organic'      => $this->resolveFacebookOrganicRow($tenant, $normTab, $lower, $scope),
            'google_search_console' => $this->resolveGoogleSearchConsoleRow($tenant, $normTab, $lower, $nameVal, $scope),
            'google_analytics'      => $this->resolveGoogleAnalyticsRow($tenant, $normTab, $lower, $nameVal, $scope),
            'mailchimp'             => $this->resolveMailchimpRow($tenant, $normTab, $lower, $nameVal, $scope),
            default                 => null,
        };
    }

    public function isResolvableDisplayLabel(string $label): bool
    {
        $trimmed = trim($label);
        if ($trimmed === '') {
            return false;
        }

        return ! in_array(strtolower($trimmed), self::NON_ENTITY_NAMES, true);
    }

    /**
     * @param  array<string, mixed>  $lower
     */
    private function resolveFacebookMarketingRow(?Project $tenant, string $tab, array $lower, ?string $scope): ?string
    {
        if (in_array($tab, ['campaigns', 'campaign_level', 'campaign'], true)) {
            $id = $this->firstPositiveInt($lower, ['channeledcampaign_id', 'channeled_campaign_id']);

            return $id !== null
                ? $this->recordUrl('channeled', 'facebook_marketing', 'campaigns', $id, $tenant, $scope)
                : null;
        }

        if (in_array($tab, ['adsets', 'adset_level', 'ad_sets', 'ad-sets'], true)) {
            $id = $this->firstPositiveInt($lower, ['adgroup_id', 'channeledadgroup_id', 'ad_group_id', 'channeled_ad_group_id']);

            return $id !== null
                ? $this->recordUrl('channeled', 'facebook_marketing', 'ad-sets', $id, $tenant, $scope)
                : null;
        }

        if (in_array($tab, ['ads', 'ad_level', 'ad'], true)) {
            $id = $this->firstPositiveInt($lower, ['ad_id', 'channeledad_id', 'channeled_ad_id']);

            return $id !== null
                ? $this->recordUrl('channeled', 'facebook_marketing', 'ads', $id, $tenant, $scope)
                : null;
        }

        if (in_array($tab, ['accounts', 'account_level', 'account'], true)) {
            $id = $this->firstPositiveInt($lower, ['channeledaccount_id', 'channeled_account_id']);

            return $id !== null
                ? $this->recordUrl('channeled', 'facebook_marketing', 'accounts', $id, $tenant, $scope)
                : null;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $lower
     */
    private function resolveGoogleAdsRow(?Project $tenant, string $tab, array $lower, ?string $scope): ?string
    {
        if (in_array($tab, ['campaigns', 'campaign_level', 'campaign'], true)) {
            $id = $this->firstPositiveInt($lower, ['channeledcampaign_id', 'channeled_campaign_id']);

            return $id !== null
                ? $this->recordUrl('channeled', 'google_ads', 'campaigns', $id, $tenant, $scope)
                : null;
        }

        if (in_array($tab, ['adgroups', 'ad_groups', 'ad-groups', 'adgroup_level'], true)) {
            $id = $this->firstPositiveInt($lower, ['adgroup_id', 'channeledadgroup_id', 'ad_group_id', 'channeled_ad_group_id']);

            return $id !== null
                ? $this->recordUrl('channeled', 'google_ads', 'ad-groups', $id, $tenant, $scope)
                : null;
        }

        if (in_array($tab, ['ads', 'ad_level', 'ad'], true)) {
            $id = $this->firstPositiveInt($lower, ['ad_id', 'channeledad_id', 'channeled_ad_id']);

            return $id !== null
                ? $this->recordUrl('channeled', 'google_ads', 'ads', $id, $tenant, $scope)
                : null;
        }

        if (in_array($tab, ['search_terms', 'queries', 'keywords'], true)) {
            $id = $this->firstPositiveInt($lower, ['query_id']);

            return $id !== null
                ? $this->recordUrl('base', 'google_ads', 'queries', $id, $tenant, $scope)
                : null;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $lower
     */
    private function resolveFacebookOrganicRow(?Project $tenant, string $tab, array $lower, ?string $scope): ?string
    {
        if (str_contains($tab, 'post')) {
            // In FacebookOrganicController, 'post' is the internal integer posts.id while 'post_id' is the external platform ID
            $id = $this->firstPositiveInt($lower, ['post', 'internal_post_id']);

            return $id !== null
                ? $this->recordUrl('channeled', 'facebook_organic', 'posts', $id, $tenant, $scope)
                : null;
        }

        if (in_array($tab, ['pages', 'accounts', 'fb_pages', 'ig_accounts'], true)) {
            $accountId = $this->firstPositiveInt($lower, ['channeledaccount_id', 'channeled_account_id']);
            if ($accountId !== null) {
                return $this->recordUrl('channeled', 'facebook_organic', 'accounts', $accountId, $tenant, $scope);
            }

            $pageId = $this->firstPositiveInt($lower, ['page', 'internal_page_id']);
            if ($pageId !== null) {
                return $this->recordUrl('base', 'facebook_organic', 'pages', $pageId, $tenant, $scope);
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $lower
     */
    private function resolveGoogleSearchConsoleRow(
        ?Project $tenant,
        string $tab,
        array $lower,
        string $nameVal,
        ?string $scope
    ): ?string {
        if ($tab === 'queries') {
            $id = $this->firstPositiveInt($lower, ['query_id', 'query']);
            if ($id !== null) {
                return $this->recordUrl('base', 'google_search_console', 'queries', $id, $tenant, $scope);
            }

            $queryText = trim((string) ($lower['query'] ?? $nameVal));

            return $this->listSearchUrl('queries', 'google_search_console', $queryText, $tenant, $scope);
        }

        if ($tab === 'pages') {
            $id = $this->firstPositiveInt($lower, ['page_id', 'page']);
            if ($id !== null) {
                return $this->recordUrl('base', 'google_search_console', 'pages', $id, $tenant, $scope);
            }

            $urlText = trim((string) ($lower['page_url'] ?? $lower['url'] ?? $nameVal));

            return $this->listSearchUrl('pages', 'google_search_console', $urlText, $tenant, $scope);
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $lower
     */
    private function resolveGoogleAnalyticsRow(
        ?Project $tenant,
        string $tab,
        array $lower,
        string $nameVal,
        ?string $scope
    ): ?string {
        if ($tab === 'events') {
            $id = $this->firstPositiveInt($lower, ['event_id']);
            if ($id !== null) {
                return $this->recordUrl('base', 'google_analytics', 'events', $id, $tenant, $scope);
            }

            $eventName = trim((string) ($lower['event'] ?? $lower['event_name'] ?? $nameVal));

            return $this->listSearchUrl('events', 'google_analytics', $eventName, $tenant, $scope);
        }

        if (in_array($tab, ['traffic_pages', 'engagement_pages', 'pages'], true)) {
            $id = $this->firstPositiveInt($lower, ['page_id']);
            if ($id !== null) {
                return $this->recordUrl('base', 'google_analytics', 'pages', $id, $tenant, $scope);
            }

            $pagePath = trim((string) ($lower['page'] ?? $lower['page_url'] ?? $lower['url'] ?? $nameVal));

            return $this->listSearchUrl('pages', 'google_analytics', $pagePath, $tenant, $scope);
        }

        // Per plan: GA4 sessionCampaignName rows are label-only unless a verified numeric channeledcampaign_id exists.
        // Never link label-only GA4 campaign names to Google Ads or unverified campaign records.
        if (in_array($tab, ['campaigns', 'ecommerce_campaigns'], true)) {
            $id = $this->firstPositiveInt($lower, ['channeledcampaign_id', 'channeled_campaign_id']);

            return $id !== null
                ? $this->recordUrl('channeled', 'google_analytics', 'campaigns', $id, $tenant, $scope)
                : null;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $lower
     */
    private function resolveMailchimpRow(
        ?Project $tenant,
        string $tab,
        array $lower,
        string $nameVal,
        ?string $scope
    ): ?string {
        if (in_array($tab, ['campaigns', 'automations'], true)) {
            $id = $this->firstPositiveInt($lower, ['channeledcampaign_id', 'channeled_campaign_id']);
            if ($id !== null) {
                return $this->recordUrl('channeled', 'mailchimp', 'campaigns', $id, $tenant, $scope);
            }

            // Fallback to scoped Catalog search only when a valid title is present
            return $this->listSearchUrl('campaigns', 'mailchimp', $nameVal, $tenant, $scope);
        }

        if (in_array($tab, ['audiences', 'accounts', 'lists'], true)) {
            $id = $this->firstPositiveInt($lower, ['channeledaccount_id', 'channeled_account_id']);
            if ($id !== null) {
                return $this->recordUrl('channeled', 'mailchimp', 'accounts', $id, $tenant, $scope);
            }

            return $this->listSearchUrl('accounts', 'mailchimp', $nameVal, $tenant, $scope);
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $lower
     * @param  array<int, string>  $keys
     */
    private function firstPositiveInt(array $lower, array $keys): ?int
    {
        foreach ($keys as $key) {
            if (! array_key_exists($key, $lower)) {
                continue;
            }
            $val = $lower[$key];
            if (is_int($val) && $val > 0) {
                return $val;
            }
            if (is_string($val) && ctype_digit(trim($val)) && (int) trim($val) > 0) {
                return (int) trim($val);
            }
        }

        return null;
    }
}
