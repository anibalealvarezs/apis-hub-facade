<?php

namespace App\Http\Controllers;

use App\Filament\App\Resources\DashboardResource\Traits\LoadsDashboardViewData;
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
        if ($this->pv->asset_group_id && $this->pv->assetGroup) {
            return [
                (string) $this->pv->asset_group_id => $this->pv->assetGroup->name,
            ];
        }
        return [];
    }

    public function getChannelAssetGroupMap(): array
    {
        if ($this->pv->asset_group_id && $this->pv->assetGroup) {
            $group = $this->pv->assetGroup;
            $map = [];
            foreach ($group->active_items->groupBy('channel') as $channel => $items) {
                $map[$channel][(string) $group->id] = $items->pluck('asset_id')->map(fn ($v) => (string) $v)->values()->toArray();
            }
            return $map;
        }
        return [];
    }
}
