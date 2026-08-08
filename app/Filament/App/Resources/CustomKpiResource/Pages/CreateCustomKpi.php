<?php

namespace App\Filament\App\Resources\CustomKpiResource\Pages;

use App\Filament\App\Resources\CustomKpiResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomKpi extends CreateRecord
{
    protected static string $resource = CustomKpiResource::class;

    protected function getFormActions(): array
    {
        return [];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data = array_merge($this->form->getRawState(), $data);
        
        $data['ast'] = \App\Services\Analytics\KpiPayloadBuilder::buildAstFromState($data['calculation_type'] ?? '', $data);
        
        // Package the UI state and scope into the filters column
        $filters = $data['filters'] ?? [];
        $filters['_ui_state'] = \Illuminate\Support\Arr::except($data, ['name', 'description', 'calculation_type', 'is_active', 'template', 'category_filter', 'ast', 'filters', 'project_id']);
        if (!empty($data['template']) && !empty($data['keep_template_guidance'])) {
            $filters['_ui_state']['template_key'] = $data['template'];
        }
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
            \App\Services\Analytics\KpiExecuteActionBuilder::configure(
                Actions\Action::make('execute'),
                fn () => $this->data,
                fn () => $this->data['calculation_type'] ?? null
            ),
            Actions\Action::make('debug')
                ->label(__('Payload'))
                ->icon('heroicon-o-code-bracket')
                ->color('gray')
                ->visible(fn () => config('app.env') !== 'production')
                ->modalHeading(__('Payload Debugger'))
                ->modalContent(function () {
                    $state = $this->data;
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
                ->modalCancelActionLabel(__('Close')),
        ];
    }
}
