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
            Actions\Action::make('settings')
                ->label(__('Dashboard Settings'))
                ->icon('heroicon-o-cog-6-tooth')
                ->color('gray')
                ->url(DashboardResource::getUrl('edit', ['record' => $this->dashboard]))
                ->extraAttributes(['wire:navigate.none' => true])
                ->visible(fn () => auth()->user()->can('edit_preferences')),
            Actions\Action::make('builder')
                ->label(__('Dashboard Builder'))
                ->icon('heroicon-o-pencil-square')
                ->color('gray')
                ->url(DashboardResource::getUrl('builder', ['record' => $this->dashboard]))
                ->extraAttributes(['wire:navigate.none' => true])
                ->visible(fn () => auth()->user()->can('edit_preferences')),
            Actions\Action::make('view')
                ->label(__('View Dashboard'))
                ->icon('heroicon-o-eye')
                ->color('primary')
                ->disabled()
                ->extraAttributes([
                    'class' => 'opacity-100 ring-2 ring-primary-500 shadow-md cursor-default pointer-events-none',
                ]),
        ];
    }
}
