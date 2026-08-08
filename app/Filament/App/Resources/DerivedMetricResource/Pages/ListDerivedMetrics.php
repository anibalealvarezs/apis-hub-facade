<?php

namespace App\Filament\App\Resources\DerivedMetricResource\Pages;

use App\Filament\App\Resources\DerivedMetricResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDerivedMetrics extends ListRecords
{
    protected static string $resource = DerivedMetricResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->visible(fn () => auth()->user()->can('edit_preferences')),
        ];
    }
}
