<?php

namespace App\Filament\App\Resources\AssetGroupResource\Pages;

use App\Filament\App\Resources\AssetGroupResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateAssetGroup extends CreateRecord
{
    protected static string $resource = AssetGroupResource::class;

    protected function afterCreate(): void
    {
        $state = $this->data['assets_data'] ?? [];
        if (is_object($state)) {
            $state = (array) $state;
        }
        
        \Illuminate\Support\Facades\Log::info('CreateAssetGroup afterCreate - assets_data state:', ['state' => $state]);
        
        $record = $this->record;
        
        $record->items()->delete();
        if (!is_array($state) || empty($state)) {
            \Illuminate\Support\Facades\Log::warning('CreateAssetGroup afterCreate - state is empty or not an array, skipping saving items');
            return;
        }
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
    }
}
