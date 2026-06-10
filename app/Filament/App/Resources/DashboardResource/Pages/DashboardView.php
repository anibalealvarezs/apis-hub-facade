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

    public array $widgets = [];

    public array $resolvedControls = [];

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
