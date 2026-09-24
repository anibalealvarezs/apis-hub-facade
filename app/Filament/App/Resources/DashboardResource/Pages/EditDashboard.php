<?php

namespace App\Filament\App\Resources\DashboardResource\Pages;

use App\Filament\App\Resources\DashboardResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDashboard extends EditRecord
{
    use \Filament\Resources\Pages\EditRecord\Concerns\Translatable;

    protected static string $resource = DashboardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\LocaleSwitcher::make(),
            Actions\Action::make('open_builder')
                ->label(__('Open Builder'))
                ->icon('heroicon-o-pencil-square')
                ->url(DashboardResource::getUrl('builder', ['record' => $this->record])),
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $project = \Filament\Facades\Filament::getTenant();

        // Enforce server-side restriction for public dashboards
        if (!empty($data['is_public']) && !app(\App\Services\FeatureGateService::class)->canAccess('public_dashboards', $project)) {
            $data['is_public'] = false;
        }

        return $data;
    }
}
