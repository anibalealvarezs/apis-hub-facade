<?php

namespace App\Filament\Resources\SupportTicketResource\Pages;

use App\Filament\Resources\SupportTicketResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSupportTicket extends EditRecord
{
    protected static string $resource = SupportTicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['internalUsers'] = $this->record->internalUsers->pluck('id')->toArray();
        $data['internalProjects'] = $this->record->internalProjects->pluck('id')->toArray();
        $data['internalBillingProfiles'] = $this->record->internalBillingProfiles->pluck('id')->toArray();
        return $data;
    }

    protected function afterSave(): void
    {
        $data = $this->form->getRawState();

        if (isset($data['internalUsers'])) {
            $this->record->internalUsers()->sync($data['internalUsers']);
        }
        if (isset($data['internalProjects'])) {
            $this->record->internalProjects()->sync($data['internalProjects']);
        }
        if (isset($data['internalBillingProfiles'])) {
            $this->record->internalBillingProfiles()->sync($data['internalBillingProfiles']);
        }
    }
}
