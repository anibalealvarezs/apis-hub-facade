<?php

namespace App\Filament\App\Resources\CustomKpiResource\Pages;

use App\Filament\App\Resources\CustomKpiResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCustomKpi extends EditRecord
{
    protected static string $resource = CustomKpiResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['ast'] = \App\Services\Analytics\KpiPayloadBuilder::buildAstFromState($data['calculation_type'], $data);
        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('execute')
                ->label('Execute KPI')
                ->icon('heroicon-o-play')
                ->color('success')
                ->action(function () {
                    $record = $this->getRecord();
                    $payload = \App\Services\Analytics\KpiPayloadBuilder::build(
                        $record->calculation_type, 
                        $this->form->getState()
                    );

                    $project = \Filament\Facades\Filament::getTenant();
                    $service = app(\App\Services\RemoteEngineService::class);
                    $result = $service->computeKpi($project, $payload);

                    if (isset($result['success']) && $result['success']) {
                        \Filament\Notifications\Notification::make()
                            ->title('Execution Successful')
                            ->success()
                            ->body('<pre style="white-space: pre-wrap; font-size: 0.75rem;">' . json_encode($result['data'] ?? [], JSON_PRETTY_PRINT) . '</pre>')
                            ->persistent()
                            ->send();
                    } else {
                        \Filament\Notifications\Notification::make()
                            ->title('Execution Failed')
                            ->danger()
                            ->body($result['message'] ?? 'An unknown error occurred.')
                            ->persistent()
                            ->send();
                    }
                }),
            Actions\DeleteAction::make(),
        ];
    }
}
