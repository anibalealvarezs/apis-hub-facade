<?php

namespace App\Filament\App\Resources\CustomKpiResource\Pages;

use App\Filament\App\Resources\CustomKpiResource;
use App\Services\Analytics\KpiPayloadBuilder;
use Filament\Actions;
use Filament\Forms;
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
            $uiState = $data['filters']['_ui_state'];

            // Detect corruption: model-level keys shouldn't be in _ui_state
            $hasModelKeys = !empty(array_intersect(
                array_keys($uiState),
                ['ast', 'id', 'project_id', 'created_at', 'updated_at']
            ));

            if ($hasModelKeys && !empty($uiState['filters']['_ui_state'])) {
                // Walk down to the deepest non-corrupted _ui_state
                $inner = $uiState['filters']['_ui_state'];
                while (!empty($inner['filters']['_ui_state'])) {
                    $inner = $inner['filters']['_ui_state'];
                }
                // Inner keys (correct UI data) override outer (corrupted), outer extras preserved
                $uiState = array_merge($uiState, $inner);
            }

            // Strip model-level keys that were erroneously nested by the old save bug
            $uiState = \Illuminate\Support\Arr::except($uiState, ['ast', 'filters', 'id', 'project_id', 'created_at', 'updated_at']);
            // Map template_key back to the form field name 'template'
            if (isset($uiState['template_key'])) {
                $data['template'] = $uiState['template_key'];
            } else {
                // Backfill for pre-existing KPIs: match by name in the predefined registry
                $kpi = $this->record;
                if ($kpi && !empty($kpi->name)) {
                    $predefinedAll = \App\Services\Analytics\PredefinedKpiRegistry::getPredefinedKpis();
                    foreach ($predefinedAll as $key => $def) {
                        if (($def['name'] ?? '') === $kpi->name) {
                            $data['template'] = $key;
                            break;
                        }
                    }
                }
            }
            foreach ($uiState as $key => $val) {
                $data[$key] = $val;
            }
            if (!isset($data['keep_template_guidance'])) {
                $data['keep_template_guidance'] = !empty($data['template']);
            }

            // Fallback: infer source_type from channel data for KPIs saved before the field existed
            if (empty($data['dependent_source_type']) && !empty($data['dependent_channel'])) {
                $data['dependent_source_type'] = 'channel';
            }
            if (!empty($data['independent_variables'])) {
                foreach ($data['independent_variables'] as $k => &$var) {
                    if (empty($var['independent_source_type']) && !empty($var['independent_channel'])) {
                        $var['independent_source_type'] = 'channel';
                    }
                }
                unset($var);
            }
        }
        $data['_builder_step'] = '22_series';
        $data['_step_history'] = json_encode(['1_intent']);
        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $rawState = $this->form->getRawState();
        $data = array_merge($rawState, $data);
        $data['ast'] = KpiPayloadBuilder::buildAstFromState($data['calculation_type'] ?? '', $data);
        
        $filters = $data['filters'] ?? [];
        $filters['_ui_state'] = \Illuminate\Support\Arr::except($data, ['name', 'description', 'calculation_type', 'is_active', 'template', 'category_filter', 'ast', 'filters', 'project_id', 'id']);
        if (!empty($data['template']) && !empty($data['keep_template_guidance'])) {
            $filters['_ui_state']['template_key'] = $data['template'];
        } else {
            unset($filters['_ui_state']['template_key']);
        }
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
        return [];
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
                ->modalCancelActionLabel(__('Close')),
            Actions\Action::make('saveVersion')
                ->label(fn (): string => __('Save Version'))
                ->icon('heroicon-o-document-plus')
                ->color('primary')
                ->form([
                    Forms\Components\TextInput::make('label')
                        ->label(__('Version Label'))
                        ->placeholder(__('e.g. Updated formula')),
                ])
                ->action(function (array $data) {
                    $formData = $this->mutateFormDataBeforeSave($this->form->getState());
                    $this->record->fill($formData);
                    $this->record->createVersion(
                        changeSummary: 'Manually saved',
                        versionName: $data['label'] ?? null,
                    );
                    $this->dispatch('refreshRelationManagers');
                    Notification::make()
                        ->title(__('Version saved'))
                        ->success()
                        ->send();
                }),
            Actions\DeleteAction::make()
                ->visible(fn () => auth()->user()->can('edit_preferences')),
        ];
    }
}
