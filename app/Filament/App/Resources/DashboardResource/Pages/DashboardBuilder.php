<?php

namespace App\Filament\App\Resources\DashboardResource\Pages;

use App\Filament\App\Resources\DashboardResource;
use App\Models\Dashboard;
use App\Models\DashboardWidget;
use App\Models\CustomKpi;
use App\Services\WidgetTypeRegistry;
use App\Services\Analytics\KpiFormBuilder;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\Page;
use Filament\Notifications\Notification;

class DashboardBuilder extends Page
{
    protected static string $resource = DashboardResource::class;

    protected static string $view = 'filament.app.pages.dashboard-builder';

    public Dashboard $dashboard;

    public ?array $widgets = [];

    public ?array $gridState = [];

    public bool $unsavedChanges = false;

    public function mount(Dashboard $record): void
    {
        \Illuminate\Support\Facades\Log::debug("[DM_DEBUG] DashboardBuilder mount ENTER", ['dashboard_id' => $record->id]);
        $this->dashboard = $record;
        $this->unsavedChanges = $this->dashboard->hasUnsavedChanges();
        $this->loadWidgets();
        \Illuminate\Support\Facades\Log::debug("[DM_DEBUG] DashboardBuilder mount DONE", ['widget_count' => count($this->widgets)]);
    }

    public function loadWidgets(): void
    {
        \Illuminate\Support\Facades\Log::debug("[DM_DEBUG] DashboardBuilder loadWidgets ENTER");
        $rawWidgets = $this->dashboard->widgets()
            ->orderBy('grid_y')
            ->orderBy('grid_x')
            ->get();

        $locale = app()->getLocale();
        $resolveField = function (DashboardWidget $widget, string $field) use ($locale) {
            $trans = $widget->getTranslation($field, $locale);
            if (! empty($trans)) {
                return $trans;
            }
            $val = $widget->getAttributes()[$field] ?? null;
            if (is_string($val)) {
                return $val;
            }
            if (is_array($val) && ! empty($val)) {
                $first = reset($val);
                return is_string($first) ? $first : '';
            }
            return '';
        };

        $this->widgets = $rawWidgets->map(function ($widget) use ($resolveField) {
            $arr = $widget->toArray();
            $arr['title'] = $resolveField($widget, 'title');
            $arr['name'] = $resolveField($widget, 'name');
            $arr['description'] = $resolveField($widget, 'description');
            $arr['titles'] = $widget->getTranslations('title');
            $arr['descriptions'] = $widget->getTranslations('description');
            return $arr;
        })->toArray();

        \Illuminate\Support\Facades\Log::debug("[DM_DEBUG] DashboardBuilder loadWidgets DONE", ['count' => count($this->widgets)]);

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
        $this->gridState = $gridItems;
        $this->unsavedChanges = true;

        Notification::make()
            ->title(__('Layout saved'))
            ->success()
            ->send();
    }

    public function getHasUnsavedChanges(): bool
    {
        return $this->unsavedChanges;
    }

    // ─── Dashboard Controls ───

    public function saveDashboardControls(array $controls): void
    {
        $this->dashboard->update(['controls' => $controls]);

        $this->unsavedChanges = true;

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

        $this->unsavedChanges = true;

        Notification::make()
            ->title(__('Widget type changed to :type', ['type' => WidgetTypeRegistry::getWidgetLabel($widgetType)]))
            ->success()
            ->send();
    }

    // ─── Widget Controls ───

    public function saveWidgetControls(int $widgetId, array $controls, ?string $title = null, ?string $description = null, array $titles = [], array $descriptions = []): void
    {
        $widget = DashboardWidget::where('dashboard_id', $this->dashboard->id)
            ->findOrFail($widgetId);

        $widget->controls = $controls;
        if (! empty($titles)) {
            $widget->setTranslations('title', array_filter($titles, fn ($v) => $v !== null && $v !== ''));
        } elseif ($title !== null) {
            $widget->title = $title;
        }
        if (! empty($descriptions)) {
            $widget->setTranslations('description', array_filter($descriptions, fn ($v) => $v !== null && $v !== ''));
        } elseif ($description !== null) {
            $widget->description = $description;
        }
        $widget->save();

        $this->loadWidgets();
        $this->unsavedChanges = true;

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
        return \App\Services\Analytics\KpiFormBuilder::getAssetOptionsForChannel($channel);
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

        $groups = app(\App\Services\CollaboratorAssetAccessService::class)
            ->getAllowedAssetGroupQuery($project, auth()->user()?->getAuthIdentifier())
            ->get();

        $result = [];
        foreach ($groups as $group) {
            $result[$group->id] = $group->name;
        }
        return $result;
    }

    public function getMetricsForChannel(string $channel, ?string $granularity = null, ?string $dependency = null): array
    {
        return \App\Services\Analytics\KpiFormBuilder::getMetricOptionsForChannel($channel, $granularity, $dependency);
    }

    public function getGranularitiesForChannel(string $channel, ?string $dependency = null): array
    {
        return \App\Services\Analytics\ChannelGranularityRegistry::getGranularitiesForChannel($channel, $dependency);
    }

    public function getChannelAssetModesMap(): array
    {
        $validChannels = array_keys(\App\Services\Analytics\ChannelCapabilityRegistry::getTags());
        $map = [];
        foreach ($validChannels as $channel) {
            $map[$channel] = \App\Services\Analytics\ChannelGranularityRegistry::allowsMultipleAssets($channel);
        }
        return $map;
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
        
        return (array) (object) $result;
    }

    public function getDerivedMetricsForWidgetPicker(): array
    {
        $project = \Filament\Facades\Filament::getTenant();
        $dms = \App\Models\DerivedMetric::where('project_id', $project->id)
            ->where('is_active', true)
            ->get();

        $result = [];
        foreach ($dms as $dm) {
            $sourceSeries = array_values($dm->source_series ?? []);
            $result[$dm->id] = [
                'name' => $dm->name,
                'source_series' => $sourceSeries,
                'output_granularity' => $dm->output_granularity,
            ];
        }

        return (array) (object) $result;
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
        $this->unsavedChanges = true;

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
        $this->unsavedChanges = true;

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
        $this->unsavedChanges = true;

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
            Actions\Action::make('saveVersion')
                ->label(fn (): string => $this->getHasUnsavedChanges() ? __('Save Version') . ' ⚠' : __('Save Version'))
                ->icon('heroicon-o-document-plus')
                ->color('primary')
                ->form([
                    Forms\Components\TextInput::make('label')
                        ->label(__('Version Label'))
                        ->placeholder(__('e.g. Campaign launch layout')),
                ])
                ->action(function (array $data) {
                    $this->dashboard->createVersion(
                        changeSummary: 'Manually saved',
                        versionName: $data['label'] ?? null,
                    );
                    $this->unsavedChanges = false;
                    Notification::make()
                        ->title(__('Version saved'))
                        ->success()
                        ->send();
                }),
            Actions\Action::make('versionHistory')
                ->label(__('Version History'))
                ->icon('heroicon-o-clock')
                ->color('gray')
                ->modalHeading(__('Dashboard Version History'))
                ->modalSubmitAction(false)
                ->modalCancelActionLabel(__('Close'))
                ->modalContent(function () {
                    $versions = $this->dashboard->versions()
                        ->with('user')
                        ->orderBy('version_number', 'desc')
                        ->get();

                    return view('filament.modals.version-history', [
                        'versions' => $versions,
                        'dashboard' => $this->dashboard,
                    ]);
                }),
            Actions\Action::make('duplicateCurrent')
                ->label(__('Duplicate'))
                ->icon('heroicon-o-document-duplicate')
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading(__('Duplicate Dashboard'))
                ->modalDescription(__('Create a copy of this dashboard from its current state?'))
                ->modalSubmitActionLabel(__('Duplicate'))
                ->action(function () {
                    $service = app(\App\Services\DashboardService::class);
                    $clone = $service->cloneDashboard($this->dashboard);
                    Notification::make()
                        ->title(__('Dashboard duplicated'))
                        ->success()
                        ->send();
                    $this->redirect(DashboardResource::getUrl('builder', ['record' => $clone]));
                }),
            Actions\Action::make('settings')
                ->label(__('Dashboard Settings'))
                ->icon('heroicon-o-cog-6-tooth')
                ->url(DashboardResource::getUrl('edit', ['record' => $this->dashboard]))
                ->extraAttributes(['wire:navigate.none' => true]),
            Actions\Action::make('view')
                ->label(__('View Dashboard'))
                ->icon('heroicon-o-eye')
                ->url(DashboardResource::getUrl('view', ['record' => $this->dashboard]))
                ->extraAttributes(['wire:navigate.none' => true]),
            Actions\Action::make('back')
                ->label(__('Back to Dashboards'))
                ->icon('heroicon-o-arrow-left')
                ->url(DashboardResource::getUrl('index'))
                ->extraAttributes(['wire:navigate.none' => true]),
        ];
    }

    public function restoreVersion(int $versionId): void
    {
        $version = $this->dashboard->versions()->findOrFail($versionId);

        $this->dashboard->restoreFullVersion($version);

        $this->unsavedChanges = true;

        $this->js('window.location.reload()');

        \Filament\Notifications\Notification::make()
            ->title(__('Dashboard restored to version #:version', ['version' => $version->version_number]))
            ->success()
            ->send();
    }

    public function duplicateFromVersion(int $versionId): void
    {
        $version = $this->dashboard->versions()->findOrFail($versionId);

        $service = app(\App\Services\DashboardService::class);
        $clone = $service->cloneDashboardFromVersion($this->dashboard, $version);

        Notification::make()
            ->title(__('Dashboard duplicated from version #:version', ['version' => $version->version_number]))
            ->success()
            ->send();

        $this->redirect(DashboardResource::getUrl('builder', ['record' => $clone]));
    }
}
