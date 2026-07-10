<?php

namespace App\Filament\App\Resources\DashboardResource\Pages;

use App\Filament\App\Resources\DashboardResource;
use App\Models\Dashboard;
use Filament\Actions;
use Filament\Resources\Pages\Page;

class DashboardView extends Page
{
    protected static string $resource = DashboardResource::class;

    protected static string $view = 'filament.app.pages.dashboard-view';

    use \App\Filament\App\Resources\DashboardResource\Traits\LoadsDashboardViewData;

    public function getAllAssetGroups(): array
    {
        $project = \Filament\Facades\Filament::getTenant();
        if (!$project) return [];

        $groups = \App\Models\AssetGroup::where('project_id', $project->id)->get();
        $result = [];
        foreach ($groups as $group) {
            $result[$group->id] = $group->name;
        }
        return $result;
    }

    public function getChannelAssetGroupMap(): array
    {
        $project = \Filament\Facades\Filament::getTenant();
        if (!$project) return [];

        $groups = \App\Models\AssetGroup::where('project_id', $project->id)
            ->with('items')
            ->get();

        $map = [];
        foreach ($groups as $group) {
            $activeAssets = $group->active_items;
            foreach ($activeAssets->groupBy('channel') as $channel => $items) {
                if (!isset($map[$channel])) {
                    $map[$channel] = [];
                }
                $map[$channel][(string) $group->id] = $items->pluck('asset_id')->map(fn ($v) => (string) $v)->values()->toArray();
            }
        }
        return $map;
    }

    public function mount(Dashboard $record): void
    {
        $this->loadDashboardViewData($record);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('edit')
                ->label(__('Edit Dashboard'))
                ->icon('heroicon-o-pencil-square')
                ->url(DashboardResource::getUrl('builder', ['record' => $this->dashboard]))
                ->visible(fn () => auth()->user()->can('edit_preferences')),
            Actions\Action::make('back')
                ->label(__('Back to Dashboards'))
                ->icon('heroicon-o-arrow-left')
                ->url(DashboardResource::getUrl('index')),
        ];
    }
}
