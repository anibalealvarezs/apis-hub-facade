<?php

namespace App\Filament\Resources\ApisHubReleaseResource\Pages;

use App\Filament\Resources\ApisHubReleaseResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditApisHubRelease extends EditRecord
{
    protected static string $resource = ApisHubReleaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
