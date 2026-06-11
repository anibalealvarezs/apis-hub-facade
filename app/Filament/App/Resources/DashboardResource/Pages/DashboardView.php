<?php

namespace App\Filament\App\Resources\DashboardResource\Pages;

use App\Filament\App\Resources\DashboardResource;
use App\Models\Dashboard;
use Filament\Actions;
use Filament\Resources\Pages\Page;

class DashboardView extends Page
{
    protected static string $resource = DashboardResource::class;

    protected static string $view = 'filament.app.pages.dashboard-view';

    public Dashboard $dashboard;

    public array $resolvedControls = [];

    public array $runtimeAssets = [];
    public ?string $runtimeChannel = null;

    public function mount(Dashboard $record): void
    {
        $this->dashboard = $record;

        $this->widgets = $this->dashboard->widgets()
            ->orderBy('grid_y')
            ->orderBy('grid_x')
            ->get()
            ->toArray();

        $service = app(\App\Services\WidgetDataService::class);
        foreach ($this->widgets as &$widget) {
            $widget['resolved_controls'] = $service->resolveControls(
                $this->dashboard,
                (new \App\Models\DashboardWidget())->forceFill($widget)
            );
        }

        $dashboardControls = $this->dashboard->controls ?? [];
        if (!empty($dashboardControls['asset_mode']) && $dashboardControls['asset_mode'] === 'multiple' && !empty($dashboardControls['assets']) && !empty($dashboardControls['channel'])) {
            $this->runtimeChannel = $dashboardControls['channel'];
            $allAssets = \App\Services\Analytics\KpiFormBuilder::getAssetOptionsForChannel($this->runtimeChannel);
            
            $configuredAssets = $dashboardControls['assets'];
            
            // If the user has restricted access, intersect with their allowed assets
            $user = auth()->user();
            if ($user && $user->role !== 'admin' && $user->role !== 'owner') {
                $service = app(\App\Services\WidgetDataService::class);
                $configuredAssets = $service->filterAllowedAssets(\Filament\Facades\Filament::getTenant(), $user->id, $this->runtimeChannel, $configuredAssets);
            }

            foreach ($configuredAssets as $assetId) {
                if (isset($allAssets[$assetId])) {
                    $this->runtimeAssets[$assetId] = $allAssets[$assetId];
                }
            }
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('edit')
                ->label('Edit Dashboard')
                ->icon('heroicon-o-pencil-square')
                ->url(DashboardResource::getUrl('builder', ['record' => $this->dashboard]))
                ->visible(fn () => auth()->user()->can('edit_preferences')),
            Actions\Action::make('back')
                ->label('Back to Dashboards')
                ->icon('heroicon-o-arrow-left')
                ->url(DashboardResource::getUrl('index')),
        ];
    }
}
