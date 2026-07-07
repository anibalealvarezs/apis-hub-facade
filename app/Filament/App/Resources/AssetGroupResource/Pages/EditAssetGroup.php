<?php

namespace App\Filament\App\Resources\AssetGroupResource\Pages;

use App\Filament\App\Resources\AssetGroupResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAssetGroup extends EditRecord
{
    protected static string $resource = AssetGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        \Illuminate\Support\Facades\Log::info('EditAssetGroup afterSave - ENTIRE DATA:', ['data' => $this->data]);
        
        $state = $this->data['assets_data'] ?? [];
        if (is_string($state)) {
            $state = json_decode($state, true);
        }
        if (is_object($state)) {
            $state = (array) $state;
        }
        
        \Illuminate\Support\Facades\Log::info('EditAssetGroup afterSave - assets_data state:', ['state' => $state]);
        
        $record = $this->record;
        
        $record->items()->delete();
        if (!is_array($state) || empty($state)) {
            \Illuminate\Support\Facades\Log::warning('EditAssetGroup afterSave - state is empty or not an array, skipping saving items');
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
