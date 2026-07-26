<?php

namespace App\Filament\App\Resources\DerivedMetricResource\Pages;

use App\Filament\App\Resources\DerivedMetricResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDerivedMetric extends EditRecord
{
    protected static string $resource = DerivedMetricResource::class;

    protected function getFormActions(): array
    {
        return [];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (empty($data['output_granularity'])) {
            $data['output_granularity'] = null;
        }

        foreach ($data['source_series'] as &$series) {
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
        return [
            Actions\DeleteAction::make()
                ->visible(fn () => auth()->user()->can('edit_preferences')),
        ];
    }
}
