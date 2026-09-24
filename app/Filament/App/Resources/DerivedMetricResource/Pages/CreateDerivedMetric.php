<?php

namespace App\Filament\App\Resources\DerivedMetricResource\Pages;

use App\Filament\App\Resources\DerivedMetricResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDerivedMetric extends CreateRecord
{
    protected static string $resource = DerivedMetricResource::class;

    public function mount(): void
    {
        $project = \Filament\Facades\Filament::getTenant();
        if ($project && $project->billingProfile) {
            $currentCount = \App\Models\DerivedMetric::where('project_id', $project->id)->count();
            $maxMetrics = app(\App\Services\BillingLifecycleService::class)
                ->getMaxDerivedMetricsForTier($project->billingProfile->tier);

            if ($currentCount >= $maxMetrics) {
                \Filament\Notifications\Notification::make()
                    ->title(__('Derived metric limit reached'))
                    ->body(__('You have reached the maximum number of derived metrics allowed by your plan (:limit). Please upgrade your subscription to create more.', ['limit' => $maxMetrics]))
                    ->danger()
                    ->send();

                $this->redirect(DerivedMetricResource::getUrl('index'));
                return;
            }
        }

        parent::mount();
    }

    protected function getFormActions(): array
    {
        return [];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $rawState = $this->form->getRawState();
        $data = array_merge($rawState, $data);

        $project = \Filament\Facades\Filament::getTenant();
        $data['project_id'] = $project->id;

        if (empty($data['output_granularity'])) {
            $data['output_granularity'] = null;
        }

        if (! is_array($data['source_series'])) {
            $data['source_series'] = [];
        }

        $data['source_series'] = array_values($data['source_series']);
        foreach ($data['source_series'] as $index => &$series) {
            $series['key'] = chr(97 + $index);
            if (isset($series['asset_filter']) && is_array($series['asset_filter'])) {
                $series['asset_filter'] = array_values(array_filter($series['asset_filter']));
            }
        }
        unset($series);

        if (is_string($data['ast'] ?? null)) {
            $data['ast'] = json_decode($data['ast'], true);
        }

        unset($data['_builder_step'], $data['_step_history'], $data['_formula_editor']);

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
