<?php

namespace App\Http\Controllers;

use App\Filament\App\Resources\DashboardResource\Traits\LoadsDashboardViewData;
use App\Models\AssetGroup;
use App\Models\Dashboard;
use App\Models\DashboardPublicView;
use App\Models\Project;

class PublicDashboardViewModel
{
    use LoadsDashboardViewData;

    public DashboardPublicView $pv;
    public Project $project;
    public bool $isEmbedded = false;

    public function __construct(DashboardPublicView $pv, bool $isEmbedded = false)
    {
        $this->pv = $pv;
        $this->isEmbedded = $isEmbedded;
        $this->project = $pv->dashboard->project;

        app()->instance('current_public_project', $this->project);

        $this->loadDashboardViewData($pv->dashboard);
    }

    public function getAllAssetGroups(): array
    {
        $ids = $this->pv->asset_group_ids ?? [];
        if (empty($ids)) {
            return [];
        }

        return AssetGroup::whereIn('id', $ids)->pluck('name', 'id')
            ->mapWithKeys(fn ($name, $id) => [(string) $id => $name])
            ->toArray();
    }

    public function getChannelAssetGroupMap(): array
    {
        $ids = $this->pv->asset_group_ids ?? [];
        if (empty($ids)) {
            return [];
        }

        $groups = AssetGroup::whereIn('id', $ids)->with('items')->get();
        $map = [];
        foreach ($groups as $group) {
            foreach ($group->active_items->groupBy('channel') as $channel => $items) {
                if (!isset($map[$channel])) {
                    $map[$channel] = [];
                }
                $map[$channel][(string) $group->id] = $items->pluck('asset_id')
                    ->map(fn ($v) => (string) $v)
                    ->values()
                    ->toArray();
            }
        }
        return $map;
    }
}
