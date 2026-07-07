<?php

namespace App\Filament\App\Resources\AssetGroupResource\Pages;

use App\Filament\App\Resources\AssetGroupResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Log;

class CreateAssetGroup extends CreateRecord
{
    protected static string $resource = AssetGroupResource::class;

    public string $assetSelections = '{}';

    public function setAssetSelections(string $json): void
    {
        $this->assetSelections = $json;
        Log::info('CreateAssetGroup setAssetSelections called:', ['json' => $json]);
    }

    protected function afterCreate(): void
    {
        Log::info('CreateAssetGroup afterCreate - assetSelections property:', ['value' => $this->assetSelections]);

        $state = json_decode($this->assetSelections, true);
        if (!is_array($state) || empty($state)) {
            Log::warning('CreateAssetGroup afterCreate - decoded state is empty or not an array');
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

        Log::info('CreateAssetGroup afterCreate - items saved successfully');
    }
}
