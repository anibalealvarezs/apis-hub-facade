<?php

namespace App\Filament\App\Resources\AlertResource\Pages;

use App\Filament\App\Resources\AlertResource;
use App\Services\DeployerService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAlert extends EditRecord
{
    protected static string $resource = AlertResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('evaluate')
                ->label(__('Evaluate Calculation'))
                ->icon('heroicon-o-calculator')
                ->color('warning')
                ->action(function () {
                    /** @var \App\Models\Alert $record */
                    $record = $this->record;
                    $result = app(DeployerService::class)->evaluateAlert($record);
                    if ($result['success']) {
                        \Filament\Notifications\Notification::make()
                            ->title(__('Alert Evaluation Complete'))
                            ->body($result['output'] ?? __('Calculations executed successfully.'))
                            ->success()
                            ->persistent()
                            ->send();
                    } else {
                        \Filament\Notifications\Notification::make()
                            ->title(__('Evaluation Failed'))
                            ->body($result['message'] ?? __('Failed to execute alert evaluation.'))
                            ->danger()
                            ->send();
                    }
                }),
            Actions\ReplicateAction::make()
                ->label(__('Duplicate Alert'))
                ->icon('heroicon-o-square-2-stack')
                ->beforeReplicaSaved(function (\App\Models\Alert $replica): void {
                    $replica->is_active = false;
                    $replica->name = $replica->name . ' (' . __('Copy') . ')';
                })
                ->after(function (\App\Models\Alert $replica, \App\Models\Alert $record): void {
                    foreach ($record->calculationLines as $line) {
                        $replica->calculationLines()->create([
                            'label' => $line->label,
                            'asset_filter' => $line->asset_filter,
                            'sort_order' => $line->sort_order,
                        ]);
                    }
                    if ($replica->project) {
                        app(DeployerService::class)->syncAlertConfig($replica->project);
                    }
                }),
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if (($data['unit'] ?? 'number') === 'percentage') {
            if (isset($data['upper_limit']) && $data['upper_limit'] !== null && $data['upper_limit'] !== '') {
                $data['upper_limit'] = (float) $data['upper_limit'] * 100;
            }
            if (isset($data['lower_limit']) && $data['lower_limit'] !== null && $data['lower_limit'] !== '') {
                $data['lower_limit'] = (float) $data['lower_limit'] * 100;
            }
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if ($data['source_type'] === 'kpi' && !empty($data['source_config']['kpi_id'])) {
            $kpi = \App\Models\CustomKpi::find($data['source_config']['kpi_id']);
            if ($kpi) {
                $data['ast'] = $kpi->ast;
                $data['filters'] = $kpi->filters ?? [];
                $data['source_config']['kpi_name'] = $kpi->name;
            }
        } elseif ($data['source_type'] === 'derived_metric' && !empty($data['source_config']['dm_id'])) {
            $dm = \App\Models\DerivedMetric::find($data['source_config']['dm_id']);
            if ($dm) {
                $data['ast'] = $dm->ast;
                $data['source_config']['dm_name'] = $dm->name;
            }
        } elseif ($data['source_type'] === 'metric') {
            $metricAlias = ($data['source_config']['channel'] ?? 'global') . '.' . ($data['source_config']['metric'] ?? 'metric');
            $data['ast'] = ['type' => 'metric', 'metric' => $metricAlias];
        }

        if (($data['unit'] ?? 'number') === 'percentage') {
            if (isset($data['upper_limit']) && $data['upper_limit'] !== null && $data['upper_limit'] !== '') {
                $data['upper_limit'] = (float) $data['upper_limit'] / 100;
            }
            if (isset($data['lower_limit']) && $data['lower_limit'] !== null && $data['lower_limit'] !== '') {
                $data['lower_limit'] = (float) $data['lower_limit'] / 100;
            }
        }

        if (!empty($data['calculationLines']) && is_array($data['calculationLines'])) {
            foreach ($data['calculationLines'] as &$line) {
                $assetId = $line['target_asset_platform_id']
                    ?? $line['asset_filter']['asset_platform_id']
                    ?? $line['asset_filter.asset_platform_id']
                    ?? 'all';
                $line['asset_filter'] = ['asset_platform_id' => (string) $assetId];
                unset($line['target_asset_platform_id'], $line['asset_filter.asset_platform_id']);
            }
        }

        return $data;
    }

    protected function afterSave(): void
    {
        /** @var \App\Models\Alert $alert */
        $alert = $this->record;
        $alert->next_evaluation_at = $alert->computeNextEvaluationAt();
        $alert->saveQuietly();

        if ($alert->project) {
            app(DeployerService::class)->syncAlertConfig($alert->project);
        }
    }
}
