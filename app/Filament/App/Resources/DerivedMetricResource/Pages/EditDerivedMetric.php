<?php

namespace App\Filament\App\Resources\DerivedMetricResource\Pages;

use App\Filament\App\Resources\DerivedMetricResource;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditDerivedMetric extends EditRecord
{
    protected static string $resource = DerivedMetricResource::class;

    protected function getFormActions(): array
    {
        return [];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['_builder_step'] = '3_formula';
        $data['_step_history'] = json_encode(['2_series']);

        if (empty($data['format'])) {
            $data['format'] = 'decimal';
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $rawState = $this->form->getRawState();
        $data = array_merge($rawState, $data);

        if (empty($data['output_granularity'])) {
            $data['output_granularity'] = null;
        }

        if (is_array($data['source_series'] ?? null)) {
            $data['source_series'] = array_values($data['source_series']);
            foreach ($data['source_series'] as $index => &$series) {
                $series['key'] = chr(97 + $index);
                if (isset($series['asset_filter']) && is_array($series['asset_filter'])) {
                    $series['asset_filter'] = array_values(array_filter($series['asset_filter']));
                }
            }
            unset($series);
        }

        if (is_string($data['ast'] ?? null)) {
            $data['ast'] = json_decode($data['ast'], true);
        }

        unset($data['_builder_step'], $data['_step_history'], $data['_formula_editor']);

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
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
                    Notification::make()
                        ->title(__('Version saved'))
                        ->success()
                        ->send();

                    $this->js('window.location.reload()');
                }),
            Actions\DeleteAction::make()
                ->visible(fn () => auth()->user()->can('edit_preferences')),
        ];
    }
}
