<?php

namespace App\Filament\App\Resources\DashboardResource\Pages;

use App\Filament\App\Resources\DashboardResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDashboard extends EditRecord
{
    protected static string $resource = DashboardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('open_builder')
                ->label('Open Builder')
                ->icon('heroicon-o-pencil-square')
                ->url(DashboardResource::getUrl('builder', ['record' => $this->record])),
            Actions\DeleteAction::make(),
        ];
    }
}
