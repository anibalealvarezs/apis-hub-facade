<?php

namespace App\Filament\App\Resources\AssetGroupResource\Pages;

use App\Filament\App\Resources\AssetGroupResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Log;

class EditAssetGroup extends EditRecord
{
    protected static string $resource = AssetGroupResource::class;

    public string $assetSelections = '{}';

    public function mount(int | string $record): void
    {
        parent::mount($record);

        $state = [];
        foreach ($this->record->items as $item) {
            if (!isset($state[$item->channel])) {
                $state[$item->channel] = [];
            }
            $state[$item->channel][] = $item->asset_id;
        }
        $this->assetSelections = !empty($state) ? json_encode($state) : '{}';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    public function setAssetSelections(string $json): void
    {
        $this->assetSelections = $json;
        Log::info('EditAssetGroup setAssetSelections called:', ['json' => $json]);
    }

    protected function afterSave(): void
    {
        Log::info('EditAssetGroup afterSave - assetSelections property:', ['value' => $this->assetSelections]);

        $state = json_decode($this->assetSelections, true);
        if (!is_array($state) || empty($state)) {
            Log::warning('EditAssetGroup afterSave - decoded state is empty or not an array');
            return;
        }

        $record = $this->record;
        $record->items()->delete();

        foreach ($state as $channel => $assetIds) {
            if (!is_array($assetIds)) {
                continue;
            }
            foreach ($assetIds as $assetId) {
                $record->items()->create([
                    'channel' => $channel,
                    'asset_id' => $assetId,
                ]);
            }
        }

        Log::info('EditAssetGroup afterSave - items saved successfully');
    }
}
