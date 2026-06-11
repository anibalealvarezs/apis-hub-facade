<?php

namespace App\Filament\App\Resources\DashboardResource\Pages;

use App\Filament\App\Resources\DashboardResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDashboards extends ListRecords
{
    protected static string $resource = DashboardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->visible(fn () => auth()->user()->can('edit_preferences')),
        ];
    }
}
