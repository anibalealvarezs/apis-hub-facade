<?php

namespace App\Filament\App\Pages;

use App\Filament\App\Resources\DashboardResource;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    use \App\Filament\App\Resources\DashboardResource\Traits\LoadsDashboardViewData;

    protected static string $view = 'filament.app.pages.home-dashboard';

    public function getTitle(): string | \Illuminate\Contracts\Support\Htmlable
    {
        return isset($this->dashboard) ? $this->dashboard->name : __('Dashboard');
    }

    public function getWidgets(): array
    {
        return [];
    }

    public function mount(): void
    {
        $projectId = auth()->user()->currentProject?->id;
        if (!$projectId) {
            return;
        }

        $default = \App\Models\Dashboard::where('project_id', $projectId)
            ->where('is_default', true)
            ->first();

        if ($default) {
            $this->loadDashboardViewData($default);
        }
    }

    protected function getHeaderActions(): array
    {
        if (!isset($this->dashboard)) {
            return [];
        }

        return [
            \Filament\Actions\Action::make('edit')
                ->label(__('Edit Dashboard'))
                ->icon('heroicon-o-pencil-square')
                ->url(DashboardResource::getUrl('builder', ['record' => $this->dashboard]))
                ->visible(fn () => auth()->user()->can('edit_preferences')),
            \Filament\Actions\Action::make('all_dashboards')
                ->label(__('All Dashboards'))
                ->icon('heroicon-o-squares-2x2')
                ->url(DashboardResource::getUrl('index')),
        ];
    }
}
