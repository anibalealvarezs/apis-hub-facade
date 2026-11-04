<?php

namespace App\Filament\App\Resources\AssetGroupResource\Pages;

use App\Filament\App\Resources\AssetGroupResource;
use App\Models\AssetGroup;
use App\Services\Analytics\KpiFormBuilder;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Livewire\Attributes\Locked;

class ListAssetGroups extends ListRecords
{
    protected static string $resource = AssetGroupResource::class;

    protected static string $view = 'filament.app.resources.asset-group-resource.pages.list-asset-groups';

    #[Locked]
    public ?string $previewGroupId = null;

    protected function getViewData(): array
    {
        $group = $this->previewGroupId === null ? null : $this->getTableRecord($this->previewGroupId);
        if (! $group instanceof AssetGroup || ! AssetGroupResource::canView($group)) {
            return ['previewGroup' => null];
        }

        $channels = $group->items->groupBy('channel')->map(function ($items, string $channel): array {
            $options = KpiFormBuilder::getAllAssetsForChannel($channel);

            return [
                'label' => KpiFormBuilder::getChannelDisplayName($channel),
                'assets' => $items->map(fn ($item) => [
                    'id' => $item->asset_id,
                    'name' => $options[$item->asset_id]['name'] ?? $item->asset_id,
                ])->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE),
            ];
        })->sortBy('label');

        return ['previewGroup' => $group, 'previewChannels' => $channels];
    }

    public function getSubheading(): string
    {
        return __('Group accounts across data sources so you can report on them together.');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
