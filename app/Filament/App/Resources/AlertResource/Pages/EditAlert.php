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
            Actions\DeleteAction::make(),
        ];
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
