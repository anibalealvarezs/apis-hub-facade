<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Filament\Resources\ProjectResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProject extends EditRecord
{
    protected static string $resource = ProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if ($this->record) {
            $data['collaborators_display'] = ProjectResource::getCollaboratorDisplayData($this->record);
            $data['sync_telemetry_channels'] = ProjectResource::getSyncTelemetryChannels($this->record);
        }

        return $data;
    }
}
