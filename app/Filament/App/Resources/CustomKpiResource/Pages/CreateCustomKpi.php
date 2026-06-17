<?php

namespace App\Filament\App\Resources\CustomKpiResource\Pages;

use App\Filament\App\Resources\CustomKpiResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomKpi extends CreateRecord
{
    protected static string $resource = CustomKpiResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['ast'] = \App\Services\Analytics\KpiPayloadBuilder::buildAstFromState($data['calculation_type'], $data);
        
        // Package the UI state and scope into the filters column
        $filters = $data['filters'] ?? [];
        $filters['_ui_state'] = \Illuminate\Support\Arr::except($data, ['name', 'description', 'calculation_type', 'is_active', 'template', 'category_filter']);
        $data['filters'] = $filters;

        // Clean up flat fields so Eloquent doesn't complain
        $allowedColumns = ['name', 'description', 'calculation_type', 'is_active', 'template', 'ast', 'filters', 'project_id'];
        foreach (array_keys($data) as $key) {
            if (!in_array($key, $allowedColumns)) {
                unset($data[$key]);
            }
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('execute')
                ->label(__('Test KPI Payload'))
                ->icon('heroicon-o-play')
                ->color('success')
                ->action(function () {
                    $state = $this->form->getState();
                    
                    if (empty($state['calculation_type'])) {
                        \Filament\Notifications\Notification::make()->title(__('Missing calculation type'))->danger()->send();
                        return;
                    }

                    $payload = \App\Services\Analytics\KpiPayloadBuilder::build(
                        $state['calculation_type'], 
                        $state
                    );

                    $project = \Filament\Facades\Filament::getTenant();
                    $service = app(\App\Services\RemoteEngineService::class);
                    $result = $service->computeKpi($project, $payload);

                    if (isset($result['success']) && $result['success']) {
                        \Filament\Notifications\Notification::make()
                            ->title(__('Execution Successful'))
                            ->success()
                            ->body('<pre style="white-space: pre-wrap; font-size: 0.75rem;">' . json_encode($result['data'] ?? [], JSON_PRETTY_PRINT) . '</pre>')
                            ->persistent()
                            ->send();
                    } else {
                        \Filament\Notifications\Notification::make()
                            ->title(__('Execution Failed'))
                            ->danger()
                            ->body($result['message'] ?? 'An unknown error occurred.')
                            ->persistent()
                            ->send();
                    }
                }),
            Actions\Action::make('debug')
                ->label(__('Debug Payload'))
                ->icon('heroicon-o-code-bracket')
                ->color('gray')
                ->visible(fn () => config('app.env') !== 'production')
                ->modalHeading(__('Payload Debugger'))
                ->modalContent(function () {
                    $state = $this->form->getState();
                    if (empty($state['calculation_type'])) {
                        return new \Illuminate\Support\HtmlString('<p>Please select a calculation type first.</p>');
                    }
                    $payload = \App\Services\Analytics\KpiPayloadBuilder::build(
                        $state['calculation_type'], 
                        $state
                    );
                    return new \Illuminate\Support\HtmlString('<pre style="background: #1f2937; color: #10b981; padding: 1rem; border-radius: 0.5rem; overflow-x: auto; font-size: 0.875rem;">' . json_encode($payload, JSON_PRETTY_PRINT) . '</pre>');
                })
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Close'),
        ];
    }
}
