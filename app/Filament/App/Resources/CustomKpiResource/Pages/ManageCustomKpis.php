<?php

namespace App\Filament\App\Resources\CustomKpiResource\Pages;

use App\Filament\App\Resources\CustomKpiResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageCustomKpis extends ManageRecords
{
    protected static string $resource = CustomKpiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
