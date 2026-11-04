<?php

namespace App\Filament\App\Resources\AssetGroupResource\Pages;

use App\Filament\App\Resources\AssetGroupResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAssetGroups extends ListRecords
{
    protected static string $resource = AssetGroupResource::class;

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
