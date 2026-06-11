<?php

namespace App\Filament\Resources\ApisHubReleaseResource\Pages;

use App\Filament\Resources\ApisHubReleaseResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListApisHubReleases extends ListRecords
{
    protected static string $resource = ApisHubReleaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
