<?php

namespace App\Services\Catalog;

use App\Filament\App\Pages\CatalogEntity;
use App\Models\Project;
use App\Models\User;

/** Local navigation metadata. Never contacts a tenant or provider API. */
class CatalogNavigation
{
    public function visibleEntities(Project $project, User $user): array
    {
        if (! $user->can('view_data')) {
            return [];
        }
        $resolver = app(CatalogScopeResolver::class);
        $scope = $resolver->resolveScope($project, $user, 'all');
        $channels = array_keys($scope['channels'] ?? []);
        if (! empty($scope['is_unrestricted'])) {
            foreach ($project->sync_config ?? [] as $channel => $config) {
                if (is_array($config) && ! empty($config['enabled'])) {
                    $channels[] = $channel;
                }
            }
        }
        $channels = array_values(array_filter(array_unique($channels), static fn (string $channel): bool =>
            filter_var($project->sync_config[$channel]['enabled'] ?? true, FILTER_VALIDATE_BOOLEAN)));
        $page = new CatalogEntity();
        $entities = [];
        foreach (CatalogEntity::ENTITIES as $entity => $label) {
            if ($entity === 'customers' && ! $resolver->canViewCustomerPii($project, $user)) {
                continue;
            }
            $supported = array_filter(array_keys($page->getChannelOptionsForEntity($entity)));
            if (array_intersect($channels, $supported) !== []) {
                $entities[$entity] = $label;
            }
        }
        return $entities;
    }
}
