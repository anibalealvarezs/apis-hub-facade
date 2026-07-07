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
}
