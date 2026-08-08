<?php

namespace App\Services;

use App\Models\AssetGroup;
use App\Models\AssetGroupItem;
use App\Models\Project;
use App\Models\ProjectUserAssetGroup;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class CollaboratorAssetAccessService
{
    protected array $channeledAccountCache = [];

    public function isOwnerOrEditor(int $userId, Project $project): bool
    {
        $roles = $this->projectRoleNames($userId, $project->id);

        return in_array('project_owner', $roles, true) || in_array('project_editor', $roles, true);
    }

    public function isUnrestricted(Project $project, int $userId): bool
    {
        if ($this->isOwnerOrEditor($userId, $project)) {
            return true;
        }

        $row = DB::table('project_user')
            ->where('project_id', $project->id)
            ->where('user_id', $userId)
            ->first();

        return $row ? (bool) $row->asset_access_unrestricted : true;
    }

    public function getSharedAssetGroupIds(Project $project, int $userId): array
    {
        return ProjectUserAssetGroup::where('project_id', $project->id)
            ->where('user_id', $userId)
            ->pluck('asset_group_id')
            ->all();
    }

    public function getSharedAssetGroups(Project $project, int $userId)
    {
        $groupIds = $this->getSharedAssetGroupIds($project, $userId);

        return AssetGroup::where('project_id', $project->id)
            ->whereIn('id', $groupIds)
            ->get();
    }

    public function getAllowedAssetGroupQuery(Project $project, ?int $userId): Builder
    {
        $query = AssetGroup::where('project_id', $project->id);

        if ($userId === null || $this->isUnrestricted($project, $userId)) {
            return $query;
        }

        $groupIds = $this->getSharedAssetGroupIds($project, $userId);

        return $query->whereIn('id', $groupIds);
    }

    public function canAccessGroup(Project $project, int $userId, int $groupId): bool
    {
        if ($this->isUnrestricted($project, $userId)) {
            return true;
        }

        return in_array($groupId, $this->getSharedAssetGroupIds($project, $userId), true);
    }

    public function getValidEnabledAssetsForChannel(Project $project, string $channel): array
    {
        $config = $project->sync_config[$channel] ?? [];
        $assets = [];
        $assetKeys = ['sites', 'ad_accounts', 'pages', 'locations', 'profiles', 'accounts', 'shops', 'properties'];

        foreach ($assetKeys as $assetKey) {
            if (! empty($config[$assetKey]) && is_array($config[$assetKey])) {
                foreach ($config[$assetKey] as $item) {
                    if (is_array($item) && ! empty($item['enabled']) && empty($item['lost_access'])) {
                        $id = $item['id'] ?? $item['platformId'] ?? $item['url'] ?? '';
                        if ($id) {
                            $assets[] = (string) $id;
                        }
                    }
                }
            }
        }

        if (! empty($config['assets']) && is_array($config['assets'])) {
            foreach ($assetKeys as $assetKey) {
                if (! empty($config['assets'][$assetKey]) && is_array($config['assets'][$assetKey])) {
                    foreach ($config['assets'][$assetKey] as $item) {
                        if (is_array($item) && ! empty($item['enabled']) && empty($item['lost_access'])) {
                            $id = $item['id'] ?? $item['platformId'] ?? $item['url'] ?? '';
                            if ($id) {
                                $assets[] = (string) $id;
                            }
                        }
                    }
                }
            }
        }

        return array_values(array_unique($assets));
    }

    public function getAllowedAssetIdsForChannel(Project $project, int $userId, string $channel): array
    {
        if ($this->isUnrestricted($project, $userId)) {
            return $this->getValidEnabledAssetsForChannel($project, $channel);
        }

        $groupIds = $this->getSharedAssetGroupIds($project, $userId);
        if (empty($groupIds)) {
            return [];
        }

        $groupAssetIds = AssetGroupItem::whereIn('asset_group_id', $groupIds)
            ->where('channel', $channel)
            ->distinct()
            ->pluck('asset_id')
            ->all();

        return array_values(array_intersect(
            array_map('strval', $groupAssetIds),
            $this->getValidEnabledAssetsForChannel($project, $channel)
        ));
    }

    public function filterAllowedAssets(Project $project, int $userId, string $channel, array $assetIds): array
    {
        $allowed = $this->getAllowedAssetIdsForChannel($project, $userId, $channel);

        return $allowed ? array_values(array_intersect($assetIds, $allowed)) : [];
    }

    protected function projectRoleNames(int $userId, int $projectId): array
    {
        return DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('model_has_roles.model_id', $userId)
            ->where('model_has_roles.project_id', $projectId)
            ->pluck('roles.name')
            ->all();
    }

    public function isProjectMember(Project $project, int $userId): bool
    {
        return DB::table('model_has_roles')
                ->where('model_id', $userId)
                ->where('model_type', (new User)->getMorphClass())
                ->where('project_id', $project->id)
                ->exists()
            || DB::table('project_user')
                ->where('project_id', $project->id)
                ->where('user_id', $userId)
                ->exists();
    }

    /**
     * Filter a list of requested channel account identifiers (engine internal ids,
     * platform ids, or FBO composite strings) down to those the collaborator may access.
     */
    public function filterRequestedChannelAssets(Project $project, int $userId, string $channel, array $requestedIds): array
    {
        if ($this->isUnrestricted($project, $userId)) {
            return array_values($requestedIds);
        }

        $set = $this->buildAllowedIdentifierSet($project, $userId, $channel);

        $filtered = [];
        foreach ($requestedIds as $requested) {
            if ($this->requestedIdentifierAllowed($requested, $set)) {
                $filtered[] = $requested;
            }
        }

        return array_values($filtered);
    }

    /**
     * Build a lookup set of every identifier form a restricted collaborator may reference
     * for a channel: the shared platform ids plus their variants (act_-stripped, act_-prefixed,
     * md5) and every engine internal id that maps to a shared platform id.
     */
    public function buildAllowedIdentifierSet(Project $project, int $userId, string $channel): array
    {
        $allowed = $this->getAllowedAssetIdsForChannel($project, $userId, $channel);
        if (empty($allowed)) {
            return [];
        }

        $set = [];
        foreach ($allowed as $id) {
            $this->addIdentifierVariants($set, (string) $id);
        }

        $this->enrichEngineIdentifiers($set, $project, $channel);

        return $set;
    }

    public function requestedIdentifierAllowed(mixed $identifier, array $set): bool
    {
        if ($identifier === null || $identifier === '') {
            return false;
        }

        $raw = (string) $identifier;

        if (str_contains($raw, '|')) {
            $parts = explode('|', $raw);
            foreach ($parts as $part) {
                $part = (string) $part;
                if ($part === '' || $part === 'NONE') {
                    continue;
                }
                if ($this->identifierAllowed($part, $set)) {
                    return true;
                }
            }

            return false;
        }

        return $this->identifierAllowed($raw, $set);
    }

    public function identifierAllowed(string $identifier, array $set): bool
    {
        if (isset($set[$identifier])) {
            return true;
        }

        $stripped = preg_replace('/^act_/i', '', $identifier);
        if ($stripped !== $identifier && isset($set[$stripped])) {
            return true;
        }

        return isset($set['act_'.$stripped]);
    }

    public function channeledAccountList(Project $project, string $channel): array
    {
        if (isset($this->channeledAccountCache[$project->id][$channel])) {
            return $this->channeledAccountCache[$project->id][$channel];
        }

        try {
            $service = app(\App\Services\RemoteEngineService::class);
            $response = $service->listChanneled($project, $channel, 'channeled_account', ['limit' => 1000, 'enabled' => 1]);
        } catch (\Throwable $e) {
            $this->channeledAccountCache[$project->id][$channel] = [];

            return [];
        }

        $accounts = is_array($response['data'] ?? null) ? $response['data'] : [];
        $this->channeledAccountCache[$project->id][$channel] = $accounts;

        return $accounts;
    }

    protected function addIdentifierVariants(array &$set, string $identifier): void
    {
        $set[$identifier] = true;
        $set[md5($identifier)] = true;

        $stripped = preg_replace('/^act_/i', '', $identifier);
        $set[$stripped] = true;
        if ($stripped !== $identifier) {
            $set['act_'.$stripped] = true;
        }

        $trimmed = rtrim($identifier, '/');
        if ($trimmed !== $identifier) {
            $set[md5($trimmed)] = true;
        }
    }

    protected function enrichEngineIdentifiers(array &$set, Project $project, string $channel): void
    {
        foreach ($this->channeledAccountList($project, $channel) as $account) {
            $engineId = (string) ($account['id'] ?? '');
            $platformId = (string) ($account['platformId'] ?? $account['platform_id'] ?? '');
            if ($engineId === '' || $platformId === '') {
                continue;
            }

            if ($this->identifierAllowed($platformId, $set)) {
                $this->addIdentifierVariants($set, $engineId);
            }
        }
    }
}
