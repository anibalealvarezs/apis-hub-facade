<?php

namespace App\Filament\App\Resources\AlertResource\Pages;

use App\Filament\App\Resources\AlertResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAlerts extends ListRecords
{
    protected static string $resource = AlertResource::class;

    public function getSubheading(): string
    {
        $counts = AlertResource::getEloquentQuery()->toBase()
            ->selectRaw('COUNT(*) AS configured, COUNT(CASE WHEN is_active THEN 1 END) AS enabled')
            ->first();

        return __(':configured configured · :enabled enabled · :paused paused', [
            'configured' => (int) $counts->configured,
            'enabled' => (int) $counts->enabled,
            'paused' => (int) $counts->configured - (int) $counts->enabled,
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
