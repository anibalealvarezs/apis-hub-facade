<?php

namespace App\Filament\App\Resources\DashboardResource\Pages;

use App\Filament\App\Resources\DashboardResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDashboard extends CreateRecord
{
    protected static string $resource = DashboardResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['project_id'] = \Filament\Facades\Filament::getTenant()->id;
        $data['user_id'] = auth()->id();
        return $data;
    }

    protected function afterCreate(): void
    {
        $this->redirect(DashboardResource::getUrl('builder', ['record' => $this->record]));
    }
}
