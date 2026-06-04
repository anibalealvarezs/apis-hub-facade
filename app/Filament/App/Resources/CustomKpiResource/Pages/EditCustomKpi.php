<?php

namespace App\Filament\App\Resources\CustomKpiResource\Pages;

use App\Filament\App\Resources\CustomKpiResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCustomKpi extends EditRecord
{
    protected static string $resource = CustomKpiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
