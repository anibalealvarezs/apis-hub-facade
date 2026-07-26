<?php

namespace App\Filament\App\Resources\DerivedMetricResource\Pages;

use App\Filament\App\Resources\DerivedMetricResource;
use App\Models\DerivedMetric;
use Filament\Actions;
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
        $project = \Filament\Facades\Filament::getTenant();
        $data['project_id'] = $project->id;

        if (empty($data['output_granularity'])) {
            $data['output_granularity'] = null;
        }

        if (! is_array($data['source_series'])) {
            $data['source_series'] = [];
        }

        foreach ($data['source_series'] as &$series) {
            if (empty($series['key'])) {
                $series['key'] = strtolower(substr(bin2hex(random_bytes(4)), 0, 8));
            }
            if (isset($series['asset_filter']) && is_array($series['asset_filter'])) {
                $series['asset_filter'] = array_values(array_filter($series['asset_filter']));
            }
        }
        unset($series);

        if (is_string($data['ast'] ?? null)) {
            $data['ast'] = json_decode($data['ast'], true);
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
