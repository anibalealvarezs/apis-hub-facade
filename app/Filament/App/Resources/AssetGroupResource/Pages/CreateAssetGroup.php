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
        $record = $this->record;
        
        $record->items()->delete();
        if (!is_array($state)) {
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
