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



    public function mount(Dashboard $record): void
    {
        $this->dashboard = $record;

        $this->widgets = $this->dashboard->widgets()
            ->orderBy('grid_y')
            ->orderBy('grid_x')
            ->get()
            ->toArray();

        $service = app(\App\Services\WidgetDataService::class);
        foreach ($this->widgets as &$widgetArray) {
            $widgetModel = (new \App\Models\DashboardWidget())->forceFill($widgetArray);
            $resolved = $service->resolveControls($this->dashboard, $widgetModel);
            $widgetArray['resolved_controls'] = $resolved;
            $widgetArray['series_assets_options'] = [];
            
            $uiState = [];
            if ($widgetModel->source_type === 'kpi' && !empty($widgetModel->source_config['custom_kpi_id'])) {
                $kpi = \App\Models\CustomKpi::find($widgetModel->source_config['custom_kpi_id']);
                if ($kpi) {
                    $uiState = $kpi->filters['_ui_state'] ?? [];
                }
            }

            $user = auth()->user();
            $isAdmin = $user && ($user->role === 'admin' || $user->role === 'owner');

            $getAssetsForChannel = function($channel) use ($isAdmin, $user, $service) {
                if (empty($channel)) return [];
                $allAssets = \App\Services\Analytics\KpiFormBuilder::getAssetOptionsForChannel($channel);
                if ($isAdmin) return $allAssets;
                $allowed = $service->filterAllowedAssets(\Filament\Facades\Filament::getTenant(), $user->id, $channel, array_keys($allAssets));
                $filtered = [];
                foreach ($allowed as $id) {
                    if (isset($allAssets[$id])) $filtered[$id] = $allAssets[$id];
                }
                return $filtered;
            };

            // Always provide asset filter options when a channel with assets is available
            $provideAssetFilters = function(string $channel, string $key, ?string $label = null) use (&$widgetArray, $getAssetsForChannel) {
                $assets = $getAssetsForChannel($channel);
                if (!empty($assets)) {
                    $widgetArray['series_assets_options'][$key] = [
                        'label' => $label ?? \Illuminate\Support\Str::headline($channel),
                        'options' => $assets,
                    ];
                }
            };

            if (!empty($uiState['dependent_channel'])) {
                $provideAssetFilters($uiState['dependent_channel'], 'dependent', 'Dep (' . \Illuminate\Support\Str::headline($uiState['dependent_channel']) . ')');
            }

            if (isset($uiState['independent_variables']) && is_array($uiState['independent_variables'])) {
                foreach ($uiState['independent_variables'] as $key => $var) {
                    if (!empty($var['independent_channel'])) {
                        $provideAssetFilters($var['independent_channel'], 'independent_' . $key, 'Ind ' . $key . ' (' . \Illuminate\Support\Str::headline($var['independent_channel']) . ')');
                    }
                }
            }

            // Fallback for non-KPI widgets with a channel
            if (empty($widgetArray['series_assets_options']) && !empty($resolved['channel'])) {
                $provideAssetFilters($resolved['channel'], 'dependent');
            }

            // Expose available metric options for on-the-go selection
            $metricChannel = $uiState['dependent_channel'] ?? $resolved['channel'] ?? '';
            $widgetArray['metric_options'] = !empty($metricChannel)
                ? \App\Services\Analytics\KpiFormBuilder::getMetricOptionsForChannel($metricChannel)
                : [];
        }
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
