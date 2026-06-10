<?php

namespace App\Filament\App\Resources\DashboardResource\Pages;

use App\Filament\App\Resources\DashboardResource;
use App\Models\Dashboard;
use App\Models\CustomKpi;
use App\Services\WidgetTypeRegistry;
use Filament\Actions;
use Filament\Resources\Pages\Page;
use Filament\Notifications\Notification;
use Illuminate\Support\Arr;

class DashboardBuilder extends Page
{
    protected static string $resource = DashboardResource::class;

    protected static string $view = 'filament.app.pages.dashboard-builder';

    public Dashboard $dashboard;

    public ?array $widgets = [];

    public ?array $gridState = [];

    public function mount(): void
    {
        $this->dashboard = $this->record;
        $this->loadWidgets();
    }

    public function loadWidgets(): void
    {
        $this->widgets = $this->dashboard->widgets()
            ->orderBy('grid_y')
            ->orderBy('grid_x')
            ->get()
            ->toArray();

        $this->gridState = array_map(fn ($w) => [
            'id' => $w['id'],
            'x' => $w['grid_x'],
            'y' => $w['grid_y'],
            'w' => $w['grid_w'],
            'h' => $w['grid_h'],
        ], $this->widgets ?? []);
    }

    public function saveLayout(array $gridItems): void
    {
        $service = app(\App\Services\DashboardService::class);
        $service->saveLayout($this->dashboard, $gridItems);

        Notification::make()
            ->title('Layout saved')
            ->success()
            ->send();
    }

    public function getKpisForWidgetPicker(): array
    {
        $project = \Filament\Facades\Filament::getTenant();
        return CustomKpi::where('project_id', $project->id)
            ->where('is_active', true)
            ->pluck('name', 'id')
            ->toArray();
    }

    public function getAvailableSourceTypes(): array
    {
        return WidgetTypeRegistry::getSourceLabels();
    }

    public function getAvailableWidgetTypes(?string $sourceType = null): array
    {
        if ($sourceType) {
            $types = WidgetTypeRegistry::getWidgetTypesForSource($sourceType);
            $labels = WidgetTypeRegistry::getWidgetLabels();
            return array_intersect_key($labels, array_flip($types));
        }
        return WidgetTypeRegistry::getWidgetLabels();
    }

    public function addWidget(array $data): void
    {
        $service = app(\App\Services\DashboardService::class);
        $widget = $service->addWidget($this->dashboard, $data);

        $this->loadWidgets();

        Notification::make()
            ->title('Widget added')
            ->success()
            ->send();
    }

    public function deleteWidget(int $widgetId): void
    {
        $service = app(\App\Services\DashboardService::class);
        $widget = \App\Models\DashboardWidget::findOrFail($widgetId);

        if ($widget->dashboard_id !== $this->dashboard->id) {
            abort(403);
        }

        $service->removeWidget($widget);
        $this->loadWidgets();

        Notification::make()
            ->title('Widget removed')
            ->success()
            ->send();
    }

    public function duplicateWidget(int $widgetId): void
    {
        $service = app(\App\Services\DashboardService::class);
        $widget = \App\Models\DashboardWidget::findOrFail($widgetId);

        if ($widget->dashboard_id !== $this->dashboard->id) {
            abort(403);
        }

        $service->duplicateWidget($widget);
        $this->loadWidgets();

        Notification::make()
            ->title('Widget duplicated')
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('settings')
                ->label('Dashboard Settings')
                ->icon('heroicon-o-cog-6-tooth')
                ->url(DashboardResource::getUrl('edit', ['record' => $this->dashboard])),
            Actions\Action::make('view')
                ->label('View Dashboard')
                ->icon('heroicon-o-eye')
                ->url(DashboardResource::getUrl('view', ['record' => $this->dashboard])),
            Actions\Action::make('back')
                ->label('Back to Dashboards')
                ->icon('heroicon-o-arrow-left')
                ->url(DashboardResource::getUrl('index')),
        ];
    }
}
