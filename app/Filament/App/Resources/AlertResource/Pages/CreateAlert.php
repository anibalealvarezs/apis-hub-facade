<?php

namespace App\Filament\App\Resources\AlertResource\Pages;

use App\Filament\App\Resources\AlertResource;
use App\Services\DeployerService;
use Filament\Resources\Pages\CreateRecord;

class CreateAlert extends CreateRecord
{
    protected static string $resource = AlertResource::class;

    public function mount(): void
    {
        parent::mount();

        $widgetId = request()->query('widget_id');
        if ($widgetId) {
            $widget = \App\Models\DashboardWidget::find($widgetId);
            if ($widget) {
                $sourceConfig = array_merge(['widget_id' => $widget->id], $widget->source_config ?? []);
                if ($widget->custom_kpi_id) {
                    $sourceConfig['kpi_id'] = $widget->custom_kpi_id;
                }
                if ($widget->derived_metric_id) {
                    $sourceConfig['dm_id'] = $widget->derived_metric_id;
                }

                $this->form->fill([
                    'name' => __('Alert: ') . ($widget->title ?? $widget->name ?? 'Widget #' . $widget->id),
                    'source_type' => $widget->source_type ?? 'metric',
                    'source_config' => $sourceConfig,
                    'is_active' => true,
                ]);
            }
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $project = \Filament\Facades\Filament::getTenant();
        $data['project_id'] = $project->id;
        $data['user_id'] = auth()->id();

        // Build AST snapshot if source is KPI or Derived Metric
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

        // Trigger automatic SSH configuration sync
        if ($alert->project) {
            app(DeployerService::class)->syncAlertConfig($alert->project);
        }
    }
}
