<?php

namespace App\Filament\App\Resources\DashboardResource\Pages;

use App\Filament\App\Resources\DashboardResource;
use App\Models\Dashboard;
use App\Models\DashboardWidget;
use App\Models\CustomKpi;
use App\Services\WidgetTypeRegistry;
use App\Services\Analytics\KpiFormBuilder;
use Filament\Actions;
use Filament\Resources\Pages\Page;
use Filament\Notifications\Notification;

class DashboardBuilder extends Page
{
    protected static string $resource = DashboardResource::class;

    protected static string $view = 'filament.app.pages.dashboard-builder';

    public Dashboard $dashboard;

    public ?array $widgets = [];

    public ?array $gridState = [];

    public function mount(Dashboard $record): void
    {
        $this->dashboard = $record;
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
            ->title(__('Layout saved'))
            ->success()
            ->send();
    }

    // ─── Dashboard Controls ───

    public function saveDashboardControls(array $controls): void
    {
        $this->dashboard->update(['controls' => $controls]);

        Notification::make()
            ->title(__('Dashboard controls saved'))
            ->success()
            ->send();
    }

    // ─── Widget Type Change ───

    public function changeWidgetType(int $widgetId, string $widgetType): void
    {
        $widget = DashboardWidget::where('dashboard_id', $this->dashboard->id)
            ->findOrFail($widgetId);

        if (!WidgetTypeRegistry::isWidgetTypeCompatible($widget->source_type, $widgetType)) {
            Notification::make()
                ->title(__('Invalid widget type for this source type'))
                ->danger()
                ->send();
            return;
        }

        $widget->update(['widget_type' => $widgetType]);

        Notification::make()
            ->title(__('Widget type changed to :type', ['type' => WidgetTypeRegistry::getWidgetLabel($widgetType)]))
            ->success()
            ->send();
    }

    // ─── Widget Controls ───

    public function saveWidgetControls(int $widgetId, array $controls, string $title, ?string $description = null): void
    {
        $widget = DashboardWidget::where('dashboard_id', $this->dashboard->id)
            ->findOrFail($widgetId);

        $widget->update([
            'controls' => $controls,
            'title' => $title,
            'description' => $description,
        ]);

        Notification::make()
            ->title(__('Widget controls saved'))
            ->success()
            ->send();
    }

    public function getDashboardControls(): array
    {
        return $this->dashboard->controls ?? [
            'date_start' => '',
            'date_end' => '',
            'zero_handling' => 'remove',
            'granularity' => 'daily',
            'edge_case_weighted' => true,
            'edge_case_grouping' => 'none',
            'channel' => '',
            'asset_mode' => 'single',
            'asset' => '',
            'assets' => [],
            'asset_group' => '',
            'show_asset_group_selector' => false,
        ];
    }

    // ─── Data Sources ───

    public function getActiveChannels(): array
    {
        $project = \Filament\Facades\Filament::getTenant();
        if (!$project || empty($project->sync_config)) {
            return [];
        }

        $validChannels = array_keys(\App\Services\Analytics\ChannelCapabilityRegistry::getTags());
        $active = [];
        foreach ($project->sync_config as $channel => $data) {
            if (in_array($channel, $validChannels) && !empty($data['enabled'])) {
                $active[$channel] = \Illuminate\Support\Str::headline($channel);
            }
        }
        return $active;
    }

    public function getAssetsForChannel(string $channel): array
    {
        $project = \Filament\Facades\Filament::getTenant();
        if (!$project) return [];

        $config = $project->sync_config[$channel] ?? [];
        $assets = [];
        $assetKeys = ['sites', 'ad_accounts', 'pages', 'locations', 'profiles', 'accounts', 'shops', 'properties'];

        foreach ($assetKeys as $assetKey) {
            if (!empty($config[$assetKey]) && is_array($config[$assetKey])) {
                foreach ($config[$assetKey] as $item) {
                    if (is_array($item) && !empty($item['enabled']) && empty($item['lost_access'])) {
                        $id = $item['id'] ?? $item['platformId'] ?? $item['url'] ?? '';
                        $name = $item['name'] ?? $item['url'] ?? $id;
                        if ($id) $assets[$id] = $name;
                    }
                }
            }
        }

        if (!empty($config['assets']) && is_array($config['assets'])) {
            foreach ($assetKeys as $assetKey) {
                if (!empty($config['assets'][$assetKey]) && is_array($config['assets'][$assetKey])) {
                    foreach ($config['assets'][$assetKey] as $item) {
                        if (is_array($item) && !empty($item['enabled']) && empty($item['lost_access'])) {
                            $id = $item['id'] ?? $item['platformId'] ?? $item['url'] ?? '';
                            $name = $item['name'] ?? $item['url'] ?? $id;
                            if ($id) $assets[$id] = $name;
                        }
                    }
                }
            }
        }

        return $assets;
    }

    public function getAssetGroupsForChannel(string $channel): array
    {
        $project = \Filament\Facades\Filament::getTenant();
        if (!$project) return [];

        $groups = \App\Models\AssetGroup::where('project_id', $project->id)
            ->whereHas('items', function ($query) use ($channel) {
                $query->where('channel', $channel);
            })
            ->with(['items' => function ($query) use ($channel) {
                $query->where('channel', $channel);
            }])
            ->get();

        $result = [];
        foreach ($groups as $group) {
            $activeAssets = $group->active_items->pluck('asset_id')->toArray();
            if (!empty($activeAssets)) {
                $result[$group->id] = [
                    'name' => $group->name,
                    'assets' => array_values($activeAssets),
                ];
            }
        }
        return $result;
    }

    public function getAllAssetGroups(): array
    {
        $project = \Filament\Facades\Filament::getTenant();
        if (!$project) return [];

        $groups = \App\Models\AssetGroup::where('project_id', $project->id)->get();
        $result = [];
        foreach ($groups as $group) {
            $result[$group->id] = $group->name;
        }
        return $result;
    }

    public function getMetricsForChannel(string $channel): array
    {
        return \App\Services\Analytics\KpiFormBuilder::getMetricOptionsForChannel($channel);
    }

    public function getGranularitiesForChannel(string $channel, ?string $dependency = null): array
    {
        return \App\Services\Analytics\ChannelGranularityRegistry::getGranularitiesForChannel($channel, $dependency);
    }

    public function getDependenciesForChannel(string $channel): array
    {
        return \App\Services\Analytics\ChannelGranularityRegistry::getDependenciesForChannel($channel);
    }

    public function getKpisForWidgetPicker(): array
    {
        $project = \Filament\Facades\Filament::getTenant();
        $kpis = CustomKpi::where('project_id', $project->id)
            ->where('is_active', true)
            ->get();
            
        $predefined = \App\Services\Analytics\PredefinedKpiRegistry::getPredefinedKpis();
        
        $result = [];
        foreach ($kpis as $kpi) {
            $templateKey = $kpi->filters['_ui_state']['template_key'] ?? null;
            $compatible = [];
            $optimal = [];
            
            if ($templateKey && isset($predefined[$templateKey]['compatible_widgets'])) {
                $compatible = $predefined[$templateKey]['compatible_widgets'];
                $optimal = $predefined[$templateKey]['optimal_widgets'] ?? [];
            } else {
                foreach ($predefined as $def) {
                    if (($def['calculation_type'] ?? '') === $kpi->calculation_type) {
                        $compatible = $def['compatible_widgets'] ?? [];
                        $optimal = $def['optimal_widgets'] ?? [];
                        break;
                    }
                }
            }
            
            $result[$kpi->id] = [
                'name' => $kpi->name,
                'compatible_widgets' => $compatible,
                'optimal_widgets' => $optimal
            ];
        }
        
        return $result;
    }

    public function getKpiConfiguration(int $kpiId): array
    {
        $project = \Filament\Facades\Filament::getTenant();
        $kpi = CustomKpi::where('project_id', $project->id)
            ->where('id', $kpiId)
            ->first();

        if (!$kpi) return [];

        return $kpi->filters['_ui_state'] ?? [];
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

    // ─── Widget CRUD ───

    public function addWidget(array $data): array
    {
        $service = app(\App\Services\DashboardService::class);
        $widget = $service->addWidget($this->dashboard, $data);

        $this->loadWidgets();

        Notification::make()
            ->title(__('Widget added'))
            ->success()
            ->send();

        return $widget->toArray();
    }

    public function deleteWidget(int $widgetId): void
    {
        $service = app(\App\Services\DashboardService::class);
        $widget = DashboardWidget::findOrFail($widgetId);

        if ($widget->dashboard_id !== $this->dashboard->id) {
            abort(403);
        }

        $service->removeWidget($widget);
        $this->loadWidgets();

        Notification::make()
            ->title(__('Widget removed'))
            ->success()
            ->send();
    }

    public function duplicateWidget(int $widgetId): array
    {
        $service = app(\App\Services\DashboardService::class);
        $widget = DashboardWidget::findOrFail($widgetId);

        if ($widget->dashboard_id !== $this->dashboard->id) {
            abort(403);
        }

        $newWidget = $service->duplicateWidget($widget);
        $this->loadWidgets();

        Notification::make()
            ->title(__('Widget duplicated'))
            ->success()
            ->send();

        return $newWidget->toArray();
    }

    // ─── Sharing ───

    public function getProjectCollaborators(): array
    {
        $project = \Filament\Facades\Filament::getTenant();
        return $project->users()
            ->select('users.id', 'users.name', 'users.email')
            ->get()
            ->toArray();
    }

    public function getSharedUserIds(): array
    {
        return $this->dashboard->sharedUsers()->pluck('user_id')->toArray();
    }

    public function shareWithUser(int $userId): void
    {
        $project = \Filament\Facades\Filament::getTenant();
        $user = \App\Models\User::findOrFail($userId);

        if (!$project->users()->where('user_id', $userId)->exists()) {
            Notification::make()->title(__('User is not a collaborator on this project'))->danger()->send();
            return;
        }

        if ($this->dashboard->sharedUsers()->where('user_id', $userId)->exists()) {
            Notification::make()->title(__('Already shared with this user'))->warning()->send();
            return;
        }

        $this->dashboard->sharedUsers()->attach($userId);
        Notification::make()->title(__('Dashboard shared with :name', ['name' => $user->name]))->success()->send();
    }

    public function unshareUser(int $userId): void
    {
        $this->dashboard->sharedUsers()->detach($userId);
        Notification::make()->title(__('User removed from shared list'))->success()->send();
    }

    public function togglePublic(): void
    {
        $this->dashboard->update(['is_public' => !$this->dashboard->is_public]);
        $this->dashboard->refresh();
        Notification::make()
            ->title($this->dashboard->is_public ? __('Dashboard is now public') : __('Dashboard is now private'))
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('settings')
                ->label(__('Dashboard Settings'))
                ->icon('heroicon-o-cog-6-tooth')
                ->url(DashboardResource::getUrl('edit', ['record' => $this->dashboard])),
            Actions\Action::make('view')
                ->label(__('View Dashboard'))
                ->icon('heroicon-o-eye')
                ->url(DashboardResource::getUrl('view', ['record' => $this->dashboard])),
            Actions\Action::make('back')
                ->label(__('Back to Dashboards'))
                ->icon('heroicon-o-arrow-left')
                ->url(DashboardResource::getUrl('index')),
        ];
    }
}
