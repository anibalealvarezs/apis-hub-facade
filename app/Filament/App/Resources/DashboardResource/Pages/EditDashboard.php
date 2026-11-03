<?php

namespace App\Filament\App\Resources\DashboardResource\Pages;

use App\Filament\App\Resources\DashboardResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDashboard extends EditRecord
{
    use \Filament\Resources\Pages\EditRecord\Concerns\Translatable;

    protected static string $resource = DashboardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\LocaleSwitcher::make(),
            Actions\Action::make('settings')
                ->label(__('Dashboard Settings'))
                ->icon('heroicon-o-cog-6-tooth')
                ->color('primary')
                ->disabled()
                ->extraAttributes([
                    'class' => 'opacity-100 ring-2 ring-primary-500 shadow-md cursor-default pointer-events-none',
                ]),
            Actions\Action::make('builder')
                ->label(__('Dashboard Builder'))
                ->icon('heroicon-o-pencil-square')
                ->color('gray')
                ->url(DashboardResource::getUrl('builder', ['record' => $this->record]))
                ->extraAttributes(['wire:navigate.none' => true])
                ->visible(fn () => auth()->user()->can('edit_preferences')),
            Actions\Action::make('view')
                ->label(__('View Dashboard'))
                ->icon('heroicon-o-eye')
                ->color('gray')
                ->url(DashboardResource::getUrl('view', ['record' => $this->record]))
                ->extraAttributes(['wire:navigate.none' => true]),
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $project = \Filament\Facades\Filament::getTenant();

        // Enforce server-side restriction for public dashboards
        if (!empty($data['is_public']) && !app(\App\Services\FeatureGateService::class)->canAccess('public_dashboards', $project)) {
            $data['is_public'] = false;
        }

        return $data;
    }
}
