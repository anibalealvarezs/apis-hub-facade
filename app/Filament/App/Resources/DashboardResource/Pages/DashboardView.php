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

    use \App\Filament\App\Resources\DashboardResource\Traits\LoadsDashboardViewData;

    public function resolveRouteBinding($value, $field = null)
    {
        return Dashboard::withTrashed()->where($field ?? 'id', $value)->first();
    }

    public function mount(Dashboard $record): void
    {
        if (!$record || $record->trashed()) {
            redirect()->to(DashboardResource::getUrl('index'));
            return;
        }
        $this->loadDashboardViewData($record);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('edit')
                ->label(__('Edit Dashboard'))
                ->icon('heroicon-o-pencil-square')
                ->url(DashboardResource::getUrl('builder', ['record' => $this->dashboard]))
                ->visible(fn () => auth()->user()->can('edit_preferences')),
            Actions\Action::make('back')
                ->label(__('Back to Dashboards'))
                ->icon('heroicon-o-arrow-left')
                ->url(DashboardResource::getUrl('index')),
        ];
    }
}
