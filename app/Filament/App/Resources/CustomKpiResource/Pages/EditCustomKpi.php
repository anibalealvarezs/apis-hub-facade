<?php

namespace App\Filament\App\Resources\CustomKpiResource\Pages;

use App\Filament\App\Resources\CustomKpiResource;
use App\Services\Analytics\KpiPayloadBuilder;
use Filament\Actions;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\HtmlString;

class EditCustomKpi extends EditRecord
{
    protected static string $resource = CustomKpiResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if (!empty($data['filters']['_ui_state'])) {
            foreach ($data['filters']['_ui_state'] as $key => $val) {
                $data[$key] = $val;
            }
        }
        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data = array_merge($this->form->getRawState(), $data);
        $data['ast'] = KpiPayloadBuilder::buildAstFromState($data['calculation_type'] ?? '', $data);
        
        $filters = $data['filters'] ?? [];
        $filters['_ui_state'] = \Illuminate\Support\Arr::except($data, ['name', 'description', 'calculation_type', 'is_active', 'template', 'category_filter']);
        $data['filters'] = $filters;

        $allowedColumns = ['name', 'description', 'calculation_type', 'is_active', 'template', 'ast', 'filters', 'project_id'];
        foreach (array_keys($data) as $key) {
            if (!in_array($key, $allowedColumns)) {
                unset($data[$key]);
            }
        }

        return $data;
    }



    protected function getFormActions(): array
    {
        if (!auth()->user()->can('edit_preferences')) {
            return [];
        }
        return parent::getFormActions();
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
                ->label(__('Debug Payload'))
                ->icon('heroicon-o-code-bracket')
                ->color('gray')
                ->visible(fn () => auth()->user()->can('edit_preferences') && config('app.env') !== 'production')
                ->modalHeading(__('Payload Debugger'))
                ->modalContent(function () {
                    $state = $this->data;
                    if (empty($state['calculation_type'])) {
                        return new HtmlString('<p>Please select a calculation type first.</p>');
                    }
                    $payload = KpiPayloadBuilder::build(
                        $state['calculation_type'], 
                        $state
                    );
                    return new HtmlString('<pre style="background: #1f2937; color: #10b981; padding: 1rem; border-radius: 0.5rem; overflow-x: auto; font-size: 0.875rem;">' . json_encode($payload, JSON_PRETTY_PRINT) . '</pre>');
                })
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Close'),
            Actions\DeleteAction::make()
                ->visible(fn () => auth()->user()->can('edit_preferences')),
        ];
    }
}
