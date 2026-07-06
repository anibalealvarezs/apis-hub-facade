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
            $kpiAssetMode = 'multiple';
            if ($widgetModel->source_type === 'kpi' && !empty($widgetModel->source_config['custom_kpi_id'])) {
                $kpi = \App\Models\CustomKpi::find($widgetModel->source_config['custom_kpi_id']);
                if ($kpi) {
                    $uiState = $kpi->filters['_ui_state'] ?? [];
                    $templateKey = $uiState['template_key'] ?? null;
                    if ($templateKey) {
                        $predefined = \App\Services\Analytics\PredefinedKpiRegistry::getPredefinedKpis();
                        if (isset($predefined[$templateKey]['asset_selection_mode'])) {
                            $kpiAssetMode = $predefined[$templateKey]['asset_selection_mode'];
                        } elseif (isset($predefined[$templateKey]['scope']) && $predefined[$templateKey]['scope'] === 'asset') {
                            $kpiAssetMode = 'single';
                        }
                    }
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
            $provideAssetFilters = function(string $channel, string $key, ?string $label = null, ?array $allowedIds = null) use (&$widgetArray, $getAssetsForChannel) {
                $assets = $getAssetsForChannel($channel);
                if (!empty($allowedIds)) {
                    $filtered = [];
                    foreach ($allowedIds as $id) {
                        if (isset($assets[$id])) {
                            $filtered[$id] = $assets[$id];
                        }
                    }
                    $assets = $filtered;
                }
                if (!empty($assets)) {
                    $widgetArray['series_assets_options'][$key] = [
                        'label' => $label ?? \Illuminate\Support\Str::headline($channel),
                        'options' => $assets,
                        'mode' => $kpiAssetMode,
                    ];
                }
            };

            if (!empty($uiState['dependent_channel'])) {
                $depAssetIds = null;
                if (!empty($uiState['dependent_asset_filter'])) {
                    $depAssetIds = is_array($uiState['dependent_asset_filter'])
                        ? $uiState['dependent_asset_filter']
                        : [$uiState['dependent_asset_filter']];
                } elseif (!empty($resolved['assets'])) {
                    $depAssetIds = $resolved['assets'];
                }
                $provideAssetFilters($uiState['dependent_channel'], 'dependent', 'Dep (' . \Illuminate\Support\Str::headline($uiState['dependent_channel']) . ')', $depAssetIds);
            }

            if (isset($uiState['independent_variables']) && is_array($uiState['independent_variables'])) {
                foreach ($uiState['independent_variables'] as $key => $var) {
                    if (!empty($var['independent_channel'])) {
                        $indAssetIds = null;
                        if (!empty($var['independent_asset_filter'])) {
                            $indAssetIds = is_array($var['independent_asset_filter'])
                                ? $var['independent_asset_filter']
                                : [$var['independent_asset_filter']];
                        } elseif (!empty($resolved['assets'])) {
                            $indAssetIds = $resolved['assets'];
                        }
                        $provideAssetFilters($var['independent_channel'], 'independent_' . $key, 'Ind ' . $key . ' (' . \Illuminate\Support\Str::headline($var['independent_channel']) . ')', $indAssetIds);
                    }
                }
            }

            // Fallback for non-KPI widgets with a channel
            if (empty($widgetArray['series_assets_options']) && !empty($resolved['channel'])) {
                $configuredAssets = $resolved['assets'] ?? null;
                $provideAssetFilters($resolved['channel'], 'dependent', null, $configuredAssets);
            }

            // Safety net: enforce widget-configured assets as the max available set in ALL series_assets_options
            if (!empty($resolved['assets']) && is_array($resolved['assets']) && !empty($widgetArray['series_assets_options'])) {
                $configuredIds = array_map('strval', $resolved['assets']);
                foreach ($widgetArray['series_assets_options'] as $sk => $sv) {
                    $intersected = [];
                    foreach ($configuredIds as $aid) {
                        if (isset($sv['options'][$aid])) {
                            $intersected[$aid] = $sv['options'][$aid];
                        }
                    }
                    if (!empty($intersected)) {
                        $widgetArray['series_assets_options'][$sk]['options'] = $intersected;
                    } else {
                        unset($widgetArray['series_assets_options'][$sk]);
                    }
                }
            }

            // Expose per-variable metric options for on-the-go selection (regression KPIs)
            $variables = [];
            $varIndex = 0;

            $depChannel = $uiState['dependent_channel'] ?? $resolved['channel'] ?? '';
            $depMetrics = !empty($depChannel)
                ? \App\Services\Analytics\KpiFormBuilder::getMetricOptionsForChannel($depChannel)
                : [];
            $variables['dependent'] = [
                'index' => $varIndex++,
                'channel' => $depChannel,
                'metrics' => $depMetrics,
            ];

            if (isset($uiState['independent_variables']) && is_array($uiState['independent_variables'])) {
                foreach ($uiState['independent_variables'] as $key => $var) {
                    $indChannel = $var['independent_channel'] ?? '';
                    $indMetrics = !empty($indChannel)
                        ? \App\Services\Analytics\KpiFormBuilder::getMetricOptionsForChannel($indChannel)
                        : [];
                    $variables['independent_' . $key] = [
                        'index' => $varIndex++,
                        'channel' => $indChannel,
                        'metrics' => $indMetrics,
                    ];
                }
            }

            $widgetArray['variables'] = $variables;
            // Keep flat metric_options for backward compatibility
            $widgetArray['metric_options'] = $depMetrics;
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
