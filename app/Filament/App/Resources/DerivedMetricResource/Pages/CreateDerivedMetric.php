<?php

namespace App\Filament\App\Resources\DerivedMetricResource\Pages;

use App\Filament\App\Resources\DerivedMetricResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDerivedMetric extends CreateRecord
{
    protected static string $resource = DerivedMetricResource::class;

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
