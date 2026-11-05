<?php

declare(strict_types=1);

namespace App\Services\Catalog;

use App\Models\AssetGroupItem;
use App\Models\Project;
use App\Models\User;
use App\Services\CollaboratorAssetAccessService;

final class CatalogScopeResolver
{
    public const array PHASE_1_ADS_CHANNELS = [
        'facebook_marketing' => 'Meta Ads',
        'google_ads'         => 'Google Ads',
    ];

    public const array SUPPORTED_CHANNELS = [
        'facebook_marketing'    => 'Meta Ads',
        'google_ads'            => 'Google Ads',
        'facebook_organic'      => 'Meta Organic',
        'google_search_console' => 'Google Search Console',
        'google_analytics'      => 'Google Analytics',
        'mailchimp'             => 'Mailchimp',
        'facebook_leads'        => 'Meta Leads',
    ];

    public function __construct(
        private readonly CollaboratorAssetAccessService $assetAccessService,
    ) {
    }

    /**
     * Return the list of scope options visible to the viewer (`all` + permitted Asset Groups).
     *
     * @return array<string, string>
     */
    public function getScopeOptions(Project $project, User $user): array
    {
        $options = [
            'all' => __('All permitted assets'),
        ];

        $groups = $this->assetAccessService
            ->getAllowedAssetGroupQuery($project, (int) $user->id)
            ->orderBy('name')
            ->get();

        foreach ($groups as $group) {
            $options[(string) $group->id] = $group->name;
        }

        return $options;
    }

    /**
     * Check whether the user is allowed to view Customer PII / customer records.
     * Per Phase 0 contract: requires `view_data` AND (unrestricted asset access OR explicit `view_customer_pii`).
     */
    public function canViewCustomerPii(Project $project, User $user): bool
    {
        if (! $user->can('view_data')) {
            return false;
        }

        if ($this->assetAccessService->isUnrestricted($project, (int) $user->id)) {
            return true;
        }

        try {
            return $user->hasPermissionTo('view_customer_pii');
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Resolve the normalized `authorized_scope` envelope for a project, viewer, selected scope, and optional channel filter.
     *
     * @param  array<int, string>|null  $supportedChannels
     * @return array<string, mixed>
     */
    public function resolveScope(
        Project $project,
        User $user,
        string $scope = 'all',
        ?string $channelFilter = null,
        ?array $supportedChannels = null
    ): array {
        $normalizedScope = trim($scope) !== '' ? trim($scope) : 'all';
        $normalizedChannel = $channelFilter !== null && trim($channelFilter) !== ''
            ? strtolower(trim($channelFilter))
            : null;

        $channelsToEvaluate = $supportedChannels ?? array_keys(self::SUPPORTED_CHANNELS);
        if ($normalizedChannel !== null) {
            $channelsToEvaluate = array_values(array_intersect($channelsToEvaluate, [$normalizedChannel]));
        }

        $isUnrestricted = $this->assetAccessService->isUnrestricted($project, (int) $user->id);
        $canViewCustomerPii = $this->canViewCustomerPii($project, $user);

        $selectedGroupId = null;
        if ($normalizedScope !== 'all') {
            if (! ctype_digit($normalizedScope) || (int) $normalizedScope <= 0) {
                return $this->buildUnauthorizedScopePayload($project, $user, $normalizedScope, $isUnrestricted, $canViewCustomerPii);
            }

            $selectedGroupId = (int) $normalizedScope;
            if (! $this->assetAccessService->canAccessGroup($project, (int) $user->id, $selectedGroupId)) {
                return $this->buildUnauthorizedScopePayload($project, $user, $normalizedScope, $isUnrestricted, $canViewCustomerPii);
            }
        }

        $resolvedChannels = [];

        foreach ($channelsToEvaluate as $channel) {
            if ($channel === 'facebook_leads') {
                continue; // Evaluated via facebook_organic / facebook_marketing below
            }

            $allowedPlatformIds = $this->assetAccessService->getAllowedAssetIdsForChannel(
                $project,
                (int) $user->id,
                $channel
            );

            if ($allowedPlatformIds === []) {
                continue;
            }

            if ($selectedGroupId !== null) {
                $groupAssetIds = AssetGroupItem::where('asset_group_id', $selectedGroupId)
                    ->where('channel', $channel)
                    ->distinct()
                    ->pluck('asset_id')
                    ->map(static fn ($id): string => (string) $id)
                    ->all();

                if ($channel === 'facebook_organic') {
                    $groupAssetIds = $this->expandFacebookOrganicLinkedIgIds($project, $groupAssetIds);
                }

                $allowedPlatformIds = array_values(array_intersect($allowedPlatformIds, $groupAssetIds));
            } elseif ($channel === 'facebook_organic') {
                $allowedPlatformIds = $this->expandFacebookOrganicLinkedIgIds($project, $allowedPlatformIds);
            }

            if ($allowedPlatformIds === []) {
                continue;
            }

            $resolvedChannels[$channel] = [
                'account_ids'  => [],
                'platform_ids' => array_values(array_unique(array_map('strval', $allowedPlatformIds))),
            ];
        }

        if (in_array('facebook_leads', $channelsToEvaluate, true)) {
            $leadsPlatformIds = [];
            foreach (['facebook_organic', 'facebook_marketing'] as $metaChannel) {
                if (! empty($resolvedChannels[$metaChannel]['platform_ids'])) {
                    $leadsPlatformIds = array_merge($leadsPlatformIds, $resolvedChannels[$metaChannel]['platform_ids']);
                } elseif ($normalizedChannel === 'facebook_leads') {
                    $allowedMeta = $this->assetAccessService->getAllowedAssetIdsForChannel($project, (int) $user->id, $metaChannel);
                    if ($selectedGroupId !== null) {
                        $groupMeta = AssetGroupItem::where('asset_group_id', $selectedGroupId)
                            ->where('channel', $metaChannel)
                            ->distinct()
                            ->pluck('asset_id')
                            ->map(static fn ($id): string => (string) $id)
                            ->all();
                        $allowedMeta = array_values(array_intersect($allowedMeta, $groupMeta));
                    }
                    $leadsPlatformIds = array_merge($leadsPlatformIds, $allowedMeta);
                }
            }
            if ($leadsPlatformIds !== []) {
                $resolvedChannels['facebook_leads'] = [
                    'account_ids'  => [],
                    'platform_ids' => array_values(array_unique(array_map('strval', $leadsPlatformIds))),
                ];
            }
        }

        ksort($resolvedChannels);

        $signaturePayload = [
            'project_id'  => (int) $project->id,
            'user_id'     => (int) $user->id,
            'scope'       => $normalizedScope,
            'channels'    => $resolvedChannels,
        ];

        return [
            'project_id'          => (int) $project->id,
            'user_id'             => (int) $user->id,
            'scope_type'          => $selectedGroupId !== null ? 'asset_group' : 'all',
            'scope_id'            => $selectedGroupId,
            'scope_value'         => $normalizedScope,
            'unauthorized_group'  => false,
            'is_unrestricted'     => $isUnrestricted,
            'can_view_pii'        => $canViewCustomerPii,
            'channels'            => $resolvedChannels,
            'has_any_assets'      => $resolvedChannels !== [],
            'scope_signature'     => hash('sha256', json_encode($signaturePayload)),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildUnauthorizedScopePayload(
        Project $project,
        User $user,
        string $requestedScope,
        bool $isUnrestricted,
        bool $canViewCustomerPii
    ): array {
        return [
            'project_id'          => (int) $project->id,
            'user_id'             => (int) $user->id,
            'scope_type'          => 'asset_group',
            'scope_id'            => ctype_digit($requestedScope) ? (int) $requestedScope : null,
            'scope_value'         => $requestedScope,
            'unauthorized_group'  => true,
            'is_unrestricted'     => $isUnrestricted,
            'can_view_pii'        => $canViewCustomerPii,
            'channels'            => [],
            'has_any_assets'      => false,
            'scope_signature'     => hash('sha256', json_encode([
                'project_id'   => (int) $project->id,
                'user_id'      => (int) $user->id,
                'scope'        => $requestedScope,
                'unauthorized' => true,
            ])),
        ];
    }

    /**
     * @param  array<int, string>  $assetIds
     * @return array<int, string>
     */
    private function expandFacebookOrganicLinkedIgIds(Project $project, array $assetIds): array
    {
        $config = $project->sync_config['facebook_organic'] ?? [];
        $pages = $config['pages'] ?? ($config['assets']['pages'] ?? []);
        if (! is_array($pages) || $pages === []) {
            return $assetIds;
        }

        $expanded = $assetIds;
        foreach ($pages as $page) {
            if (! is_array($page) || empty($page['enabled']) || ! empty($page['lost_access'])) {
                continue;
            }
            $pageId = (string) ($page['id'] ?? $page['platformId'] ?? '');
            $igId = (string) ($page['ig_account'] ?? '');
            if ($pageId !== '' && $igId !== '' && in_array($pageId, $assetIds, true)) {
                $expanded[] = $igId;
            }
        }

        return array_values(array_unique($expanded));
    }
}
