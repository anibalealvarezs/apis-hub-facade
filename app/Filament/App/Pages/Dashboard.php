<?php

namespace App\Filament\App\Pages;

use App\Filament\App\Resources\DashboardResource;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    use \App\Filament\App\Resources\DashboardResource\Traits\LoadsDashboardViewData;

    protected static string $view = 'filament.app.pages.home-dashboard';

    public function getHeading(): string | \Illuminate\Contracts\Support\Htmlable
    {
        return '';
    }

    public static function getNavigationLabel(): string
    {
        return __('Home');
    }

    public function getTitle(): string | \Illuminate\Contracts\Support\Htmlable
    {
        return __('Home');
    }

    public function getWidgets(): array
    {
        return [];
    }

    public function mount(): void
    {
        if (!auth()->user()->can('view_data')) {
            $this->redirect(DashboardResource::getUrl('index'));
            return;
        }

        $project = \Filament\Facades\Filament::getTenant();

        if (!$project) {
            return;
        }

        $default = \App\Models\Dashboard::where('project_id', $project->id)
            ->where('is_default', true)
            ->first();

        if ($default) {
            $this->loadDashboardViewData($default);
        }
    }
}
