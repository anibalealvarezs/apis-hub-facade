<x-filament-panels::page>
    <link rel="stylesheet" href="{{ asset('css/dashboard-builder.css') }}"/>

    <div x-data="dashboardBuilder" class="space-y-4">
        {{-- Toolbar --}}
        <div class="flex items-center justify-between gap-4 rounded-xl bg-gray-50 dark:bg-gray-900 p-4">
            <div class="flex items-center gap-2">
                <x-filament::icon name="heroicon-o-squares-2x2" class="w-6 h-6 text-gray-500 dark:text-gray-400"/>
                <span class="text-lg font-medium text-gray-900 dark:text-white">{{ $this->dashboard->name }}</span>
            </div>
            <div class="flex items-center gap-2">
                <button
                    class="px-3 py-2 rounded-lg text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-800 transition-colors"
                    x-on:click="openDashboardControls()">
                    <x-filament::icon name="heroicon-o-cog-6-tooth" class="w-4 h-4 inline mr-1"/>
                    Controls
                </button>
                @can('edit_preferences')
                    <button
                        class="px-3 py-2 rounded-lg text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-800 transition-colors"
                        x-on:click="openShareDialog()">
                        <x-filament::icon name="heroicon-o-share" class="w-4 h-4 inline mr-1"/>
                        Share
                    </button>
                @endcan
                <x-filament::button x-on:click="$wire.saveLayout(getLayout())" color="primary" icon="heroicon-o-check">
                    Save Layout
                </x-filament::button>
            </div>
        </div>

        <div class="grid grid-cols-12 gap-4">
            {{-- Widget Palette (sidebar) --}}
            <div class="col-span-2 hidden lg:block">
                <div class="rounded-xl bg-gray-50 dark:bg-gray-900 p-4 space-y-4">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Widget
                        Palette</h3>
                    <div class="space-y-2">
                        <div
                            class="rounded-lg border border-dashed border-gray-300 dark:border-gray-600 p-3 text-center cursor-pointer hover:border-primary-500 hover:bg-primary-50 dark:hover:bg-primary-900/20 transition-colors"
                            x-on:click="openAddWidgetModal()">
                            <x-filament::icon name="heroicon-o-plus-circle"
                                              class="w-8 h-8 mx-auto text-gray-400 dark:text-gray-500"/>
                            <span class="block text-xs text-gray-500 dark:text-gray-400 mt-1">{{ __('Add Widget') }}</span>
                        </div>
                    </div>
                    <div class="pt-4 border-t border-gray-200 dark:border-gray-700 space-y-3">
                        <p class="text-xs text-gray-400 dark:text-gray-500">Drag title to reposition. Grab bottom-right corner <span class="inline-block w-3 h-3 align-text-bottom" style="background:linear-gradient(135deg,transparent 5px,#9CA3AF 5px,transparent 6px),linear-gradient(135deg,transparent 8px,#9CA3AF 8px,transparent 9px);background-size:12px 12px;background-position:0 0,3px 3px;border-radius:1px;"></span> to resize.</p>
                        <div class="text-xs text-gray-400 dark:text-gray-500">
                            <div class="flex items-center gap-1 mb-1">
                                <span class="inline-block w-2 h-2 rounded-full bg-green-400"></span>
                                <span>Control inherited from dashboard</span>
                            </div>
                            <div class="flex items-center gap-1">
                                <span class="inline-block w-2 h-2 rounded-full bg-blue-400"></span>
                                <span>Custom control set</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Grid Area --}}
            <div class="col-span-12 lg:col-span-10">
                <div id="grid-container" wire:ignore
                     class="rounded-xl bg-white dark:bg-gray-950 p-4 border border-gray-200 dark:border-gray-800">
                    <div id="grid-stack" class="grid-stack">
                        <template x-for="(widget, index) in widgets" :key="widget.id">
                            <div class="grid-stack-item transition-all duration-700 ease-in-out"
                                 :class="{ 'ring-2 ring-primary-500 shadow-lg shadow-primary-500/50 z-50 transform scale-[1.02]': widget._isNew }"
                                 x-init="
                                    $el.setAttribute('gs-id', widget.id); 
                                    $el.setAttribute('gs-w', widget.grid_w || 4); 
                                    $el.setAttribute('gs-h', widget.grid_h || 3); 
                                    $el.setAttribute('gs-min-w', 2); 
                                    $el.setAttribute('gs-min-h', 2); 
                                    if (widget.grid_x !== undefined && widget.grid_x !== null) $el.setAttribute('gs-x', widget.grid_x); 
                                    if (widget.grid_y !== undefined && widget.grid_y !== null) $el.setAttribute('gs-y', widget.grid_y); 
                                    else $el.setAttribute('gs-auto-position', 'true');
                                    
                                    if (grid) grid.makeWidget($el);
                                    
                                    if (widget._isNew) {
                                        setTimeout(() => { widget._isNew = false; }, 2500);
                                    }
                                 ">
                                <div class="grid-stack-item-content rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-sm relative flex flex-col overflow-hidden">
                                    {{-- Widget Header --}}
                                    <div
                                        class="flex items-center justify-between px-3 py-2 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 gap-4 flex-shrink-0">
                                        <div class="flex items-center gap-2 min-w-0">
                                            <span x-show="widgetHasCustomControls(widget)"
                                                  class="inline-block w-2 h-2 rounded-full bg-blue-400 flex-shrink-0"
                                                  title="Has custom controls"></span>
                                            <span x-show="!widgetHasCustomControls(widget)"
                                                  class="inline-block w-2 h-2 rounded-full bg-green-400 flex-shrink-0"
                                                  title="Inheriting dashboard controls"></span>
                                            <span class="text-sm font-medium text-gray-900 dark:text-white truncate"
                                                  :title="widget.title || widget.name"
                                                  x-text="widget.title || widget.name"></span>
                                        </div>
                                        <div class="flex items-center gap-1 flex-shrink-0">
                                            <template x-if="widget.description">
                                                <div class="group relative flex items-center justify-center p-1">
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 cursor-help transition-colors">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                                                    </svg>
                                                    <div class="pointer-events-none absolute bottom-full mb-2 w-64 opacity-0 transition-opacity group-hover:opacity-100 z-50 right-0 sm:right-auto sm:left-1/2 sm:-translate-x-1/2">
                                                        <div class="rounded-lg bg-gray-900 dark:bg-gray-700 px-3 py-2 text-xs text-white shadow-lg whitespace-normal text-left">
                                                            <span x-text="widget.description"></span>
                                                            <div class="absolute -bottom-1 right-2 sm:right-auto sm:left-1/2 sm:-translate-x-1/2 h-2 w-2 rotate-45 bg-gray-900 dark:bg-gray-700"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </template>
                                            <button
                                                class="p-1 rounded hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300"
                                                x-on:click="openWidgetControls(widget)"
                                                title="Configure">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                                     stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                          d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                          d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                </svg>
                                            </button>
                                            <button
                                                class="p-1 rounded hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300"
                                                x-on:click="duplicateWidget(widget.id)"
                                                title="Duplicate">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                                     stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                          d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 01-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 011.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 00-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 01-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 00-3.375-3.375h-1.5a1.125 1.125 0 01-1.125-1.125v-1.5a3.375 3.375 0 00-3.375-3.375H9.75"/>
                                                </svg>
                                            </button>
                                            <button
                                                class="p-1 rounded hover:bg-red-100 dark:hover:bg-red-900/30 text-gray-500 dark:text-gray-400 hover:text-red-600 dark:hover:text-red-400"
                                                x-on:click="deleteWidget(widget.id)"
                                                title="Remove">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                                     stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                          d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                    {{-- Widget Content (placeholder) --}}
                                    <div class="flex-1 p-4 flex flex-col items-center justify-center min-h-0 overflow-y-auto">
                                        <div class="w-16 h-12 mb-4 bg-gray-50 dark:bg-gray-900/50 rounded-lg border border-gray-100 dark:border-gray-800 flex items-center justify-center opacity-70" x-html="getWidgetSvg(widget.widget_type)"></div>
                                        <div class="flex flex-wrap items-center justify-center gap-2">
                                            <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300" x-text="widgetLabels[widget.widget_type] || widget.widget_type"></span>
                                            <template x-if="widget.source_type === 'kpi'">
                                                <div class="flex flex-wrap items-center justify-center gap-2">
                                                    <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-primary-50 text-primary-600 dark:bg-primary-900/30 dark:text-primary-400">KPI</span>
                                                    <template x-if="widget.source_config?.custom_kpi_id && kpis[widget.source_config.custom_kpi_id]">
                                                        <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-white text-gray-700 dark:bg-gray-800 dark:text-gray-200 border border-gray-200 dark:border-gray-700" x-text="kpis[widget.source_config.custom_kpi_id].name"></span>
                                                    </template>
                                                </div>
                                            </template>
                                            <template x-if="widget.source_type === 'metric'">
                                                <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-amber-50 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400">Metric</span>
                                            </template>
                                            <template x-if="widget.source_type === 'entity'">
                                                <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-indigo-50 text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-400">Entity</span>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                    <template x-if="!widgets || widgets.length === 0">
                        <div class="flex flex-col items-center justify-center py-24 text-center">
                            <x-filament::icon name="heroicon-o-squares-2x2"
                                              class="w-16 h-16 text-gray-300 dark:text-gray-600 mb-4"/>
                            <p class="text-gray-500 dark:text-gray-400 text-lg">{{ __('No widgets yet') }}</p>
                            <p class="text-gray-400 dark:text-gray-500 text-sm mt-1">Click "Add Widget" in the palette
                                to get started.</p>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        {{-- ============================================================ --}}
        {{-- DASHBOARD-LEVEL CONTROLS MODAL                              --}}
        {{-- ============================================================ --}}
        <div x-show="showDashboardControls" x-cloak class="fixed inset-0 z-50 flex items-center justify-center">
            <div class="absolute inset-0 bg-black/50" x-on:click="showDashboardControls = false"></div>
            <div
                class="relative bg-white dark:bg-gray-900 rounded-xl shadow-xl max-w-xl w-full mx-4 max-h-[90vh] overflow-y-auto p-6 space-y-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('Dashboard Controls') }}</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">These defaults apply to all widgets. Widgets can
                    override individually.</p>

                {{-- Date Range --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('Date Range') }}</label>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ __('Start') }}</span>
                            <input type="date" x-model="dashboardControls.date_start"
                                   :max="dashboardControls.date_end || '{{ date('Y-m-d', strtotime('-1 day')) }}'"
                                   class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 text-sm"/>
                        </div>
                        <div>
                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ __('End') }}</span>
                            <input type="date" x-model="dashboardControls.date_end" max="{{ date('Y-m-d', strtotime('-1 day')) }}"
                                   class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 text-sm"/>
                        </div>
                    </div>
                </div>

                {{-- Asset Group --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('Asset Group') }}</label>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">Filters available assets for widgets that don&rsquo;t have their own asset group selected.</p>
                    <select x-model="dashboardControls.asset_group"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 text-sm">
                        <option value="">{{ __('All Assets (no filter)') }}</option>
                        <template x-for="(name, id) in assetGroups" :key="id">
                            <option :value="id" x-text="name"></option>
                        </template>
                    </select>
                    <label class="flex items-center gap-2 mt-2 text-sm text-gray-600 dark:text-gray-400">
                        <input type="checkbox" x-model="dashboardControls.show_asset_group_selector"
                               class="rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500"/>
                        {{ __('Show this selector in the dashboard view') }}
                    </label>
                </div>

                {{-- Zero Handling --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Zero / Missing
                        Data</label>
                    <select x-model="dashboardControls.zero_handling"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 text-sm">
                        <option value="remove">{{ __('Remove zeros from results') }}</option>
                        <option value="keep">{{ __('Keep zeros in results') }}</option>
                        <option value="trim">{{ __('Trim leading/trailing zeros') }}</option>
                    </select>
                </div>

                {{-- Granularity --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Granularity</label>
                    <select x-model="dashboardControls.granularity"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 text-sm">
                        <option value="daily">Daily</option>
                        <option value="weekly">Weekly</option>
                        <option value="monthly">Monthly</option>
                        <option value="query">Query</option>
                        <option value="dimensions.page">Page</option>
                        <option value="country">Country</option>
                        <option value="device">Device</option>
                        <option value="post">Post</option>
                    </select>
                </div>

                {{-- Edge Cases --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Edge Cases</label>
                    <div class="space-y-3">
                        <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                            <input type="checkbox" x-model="dashboardControls.edge_case_weighted"
                                   class="rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500"/>
                            Weighted regression (WLS)
                        </label>
                        <select x-model="dashboardControls.edge_case_grouping"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 text-sm">
                            <option value="none">{{ __('No grouping') }}</option>
                            <option value="histogram">{{ __('Auto histogram-elbow') }}</option>
                            <option value="percentile">{{ __('Bottom percentile') }}</option>
                        </select>
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-2 border-t border-gray-200 dark:border-gray-700">
                    <button
                        class="px-4 py-2 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 text-sm"
                        x-on:click="showDashboardControls = false">Cancel
                    </button>
                    <button class="px-4 py-2 rounded-lg bg-primary-600 text-white hover:bg-primary-500 text-sm"
                            x-on:click="confirmDashboardControls()">Save Controls
                    </button>
                </div>
            </div>
        </div>

        {{-- ============================================================ --}}
        {{-- WIDGET-LEVEL CONTROLS MODAL                                 --}}
        {{-- ============================================================ --}}
        <div x-show="showWidgetControls" style="display: none; z-index: 999999;" class="fixed inset-0 flex items-start justify-center pt-10 sm:pt-16" x-trap.noscroll="showWidgetControls" x-cloak>
            <div @click="showWidgetControls = false" class="fixed inset-0 bg-black/50 dark:bg-black/70 backdrop-blur-sm transition-opacity"></div>
            <div class="relative bg-white dark:bg-gray-900 rounded-xl shadow-xl mx-auto my-4 sm:my-6 flex flex-col ring-1 ring-gray-900/5 dark:ring-white/10" style="width: 95vw; max-width: 1400px; height: 90vh;" @click.away="showWidgetControls = false">
                <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-900 rounded-t-xl">
                    <div class="flex flex-col gap-1.5">
                        <div class="flex flex-wrap items-center gap-3">
                            <h3 class="text-base font-bold text-gray-900 dark:text-white" x-text="'Configure: ' + (widgetControlsTarget.title || widgetControlsTarget.name)"></h3>
                            
                            {{-- Data Source Badges --}}
                            <span class="inline-flex items-center rounded-md bg-primary-50 dark:bg-primary-950/40 px-2.5 py-0.5 text-xs font-semibold text-primary-700 dark:text-primary-400 ring-1 ring-inset ring-primary-700/10 dark:ring-primary-400/20"
                                  x-text="widgetControlsTarget.source_type === 'kpi' ? 'Custom KPI' : 'Metric'"></span>
                            
                            <template x-if="widgetControlsTarget.source_type === 'kpi' && widgetControlsTarget.source_config && widgetControlsTarget.source_config.custom_kpi_id">
                                <span class="inline-flex items-center rounded-md bg-gray-100 dark:bg-gray-800 px-2.5 py-0.5 text-xs font-medium text-gray-800 dark:text-gray-200 ring-1 ring-inset ring-gray-500/10 dark:ring-gray-400/20"
                                      x-text="kpis[widgetControlsTarget.source_config.custom_kpi_id] ? kpis[widgetControlsTarget.source_config.custom_kpi_id].name : ('KPI ID: ' + widgetControlsTarget.source_config.custom_kpi_id)"></span>
                            </template>
                        </div>
                        
                        <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                            <span class="inline-block w-1.5 h-1.5 rounded-full bg-green-400" x-show="!widgetHasCustomControls(widgetControlsTarget)"></span>
                            <span class="inline-block w-1.5 h-1.5 rounded-full bg-blue-400" x-show="widgetHasCustomControls(widgetControlsTarget)"></span>
                            <span x-show="!widgetHasCustomControls(widgetControlsTarget)">All controls inherited from dashboard defaults.</span>
                            <span x-show="widgetHasCustomControls(widgetControlsTarget)">Some controls have custom overrides.</span>
                            <button class="ml-2 text-primary-600 dark:text-primary-400 hover:underline font-medium" x-on:click="resetWidgetControls()">Reset all to inherit</button>
                        </div>
                    </div>
                    <button @click="showWidgetControls = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                    <style>
                        .modal-body-absolute-wrapper-custom {
                            position: absolute !important;
                            top: 1.5rem !important;
                            bottom: 1.5rem !important;
                            left: 1.5rem !important;
                            right: 1.5rem !important;
                        }
                        .flex-shrink-0 {
                            flex-shrink: 0 !important;
                        }
                        @media (min-width: 768px) {
                            .desktop-overflow-hidden {
                                overflow: hidden !important;
                            }
                            .modal-body-absolute-wrapper-custom {
                                top: 2rem !important;
                                bottom: 2rem !important;
                                left: 2rem !important;
                                right: 2rem !important;
                            }
                        }
                    </style>
                    <div class="flex-1 bg-gray-50 dark:bg-gray-900 min-h-0 overflow-y-auto desktop-overflow-hidden relative">
                        <div class="modal-body-absolute-wrapper-custom flex flex-col md:flex-row gap-6">
                            {{-- Left Column: Global Configuration --}}
                            <div class="flex flex-col gap-6 overflow-y-auto custom-scrollbar pr-2 pb-2 min-h-0" style="flex: 1 1 250px; max-width: 100%; height: 100%; padding-right: 5px;">

                        {{-- Card: Identity --}}
                        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm overflow-hidden flex-shrink-0">
                            <div class="flex items-center gap-2 px-6 py-3 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-gray-500 dark:text-gray-400">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
                                </svg>
                                <span class="text-xs font-bold text-gray-800 dark:text-white uppercase tracking-wider">Identity</span>
                            </div>
                            <div class="p-6 space-y-4">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">Widget Title <span class="text-red-500">*</span></label>
                                    <input type="text" x-model="widgetControlsForm.title"
                                           class="w-full text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 py-2.5 px-4 focus:ring-primary-500 focus:border-primary-500"
                                           placeholder="Enter widget title">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">Description <span class="text-gray-400 font-normal">(Optional)</span></label>
                                    <textarea x-model="widgetControlsForm.description" rows="2"
                                              class="w-full text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 py-2.5 px-4 focus:ring-primary-500 focus:border-primary-500 resize-none custom-scrollbar"
                                              placeholder="Enter a brief description..."></textarea>
                                </div>
                            </div>
                        </div>

                        {{-- Card: Chart Type --}}
                        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm overflow-hidden flex-shrink-0">
                            <div class="flex items-center gap-2 px-6 py-3 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-gray-500 dark:text-gray-400">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                                </svg>
                                <span class="text-xs font-bold text-gray-800 dark:text-white uppercase tracking-wider">Chart Type</span>
                            </div>
                            <div class="p-6">
                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                                    <template x-for="(label, type) in availableChartTypesForControls" :key="type">
                                        <button @click="widgetControlsForm.widget_type = type"
                                                class="flex flex-col items-center gap-2 p-3 rounded-lg border-2 transition-all text-center"
                                                :class="widgetControlsForm.widget_type === type
                                                    ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20 ring-1 ring-primary-500'
                                                    : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600 bg-white dark:bg-gray-800'">
                                            <div class="w-10 h-7" x-html="getWidgetSvg(type)"></div>
                                            <span class="text-[11px] font-semibold text-gray-700 dark:text-gray-300 leading-tight" x-text="label"></span>
                                        </button>
                                    </template>
                                </div>
                                <p class="text-xs text-gray-400 dark:text-gray-500 mt-3">Changing the chart type affects how data is displayed. Some types may not show all data dimensions.</p>
                            </div>
                        </div>

                        {{-- Card: Date Range --}}
                        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm overflow-hidden flex-shrink-0">
                            <div class="flex items-center justify-between px-6 py-3 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700">
                                <div class="flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-gray-500 dark:text-gray-400">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                                    </svg>
                                    <span class="text-xs font-bold text-gray-800 dark:text-white uppercase tracking-wider">Date Range</span>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" x-model="widgetControlsForm.date_inherit" class="sr-only peer"/>
                                    <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-gray-600 peer-checked:bg-primary-600"></div>
                                    <span class="ml-2 text-[10px] uppercase font-bold text-gray-500 dark:text-gray-400" x-text="widgetControlsForm.date_inherit ? 'Inherit' : 'Custom'"></span>
                                </label>
                            </div>
                            <div class="p-6 flex flex-row items-center gap-3">
                                <template x-if="widgetControlsForm.date_inherit">
                                    <div class="w-full text-sm text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-800 p-3 rounded-lg border border-gray-200 dark:border-gray-700"
                                         x-text="'Inherited: ' + ((widgetKpiConfig?.start_date || dashboardControls.date_start) || '—') + ' → ' + ((widgetKpiConfig?.end_date || dashboardControls.date_end) || '—')"></div>
                                </template>
                                <template x-if="!widgetControlsForm.date_inherit">
                                    <div class="w-full flex flex-row items-center gap-3">
                                        <input type="date" x-model="widgetControlsForm.date_start"
                                               :min="dashboardControls.date_start || ''"
                                               :max="widgetControlsForm.date_end || dashboardControls.date_end || '{{ date('Y-m-d', strtotime('-1 day')) }}'"
                                               class="w-full text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 py-2.5 px-4 focus:ring-primary-500 focus:border-primary-500">
                                        <span class="text-gray-400 dark:text-gray-500 text-sm">→</span>
                                        <input type="date" x-model="widgetControlsForm.date_end"
                                               :min="widgetControlsForm.date_start || dashboardControls.date_start || ''" 
                                               :max="dashboardControls.date_end || '{{ date('Y-m-d', strtotime('-1 day')) }}'"
                                               class="w-full text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 py-2.5 px-4 focus:ring-primary-500 focus:border-primary-500">
                                    </div>
                                </template>
                            </div>
                        </div>

                        {{-- Card: Zero / Missing Data --}}
                        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm overflow-hidden flex-shrink-0">
                            <div class="flex items-center justify-between px-6 py-3 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700">
                                <div class="flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-gray-500 dark:text-gray-400">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                    <span class="text-xs font-bold text-gray-800 dark:text-white uppercase tracking-wider">Zero / Missing Data</span>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" x-model="widgetControlsForm.zero_inherit" class="sr-only peer"/>
                                    <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-gray-600 peer-checked:bg-primary-600"></div>
                                    <span class="ml-2 text-[10px] uppercase font-bold text-gray-500 dark:text-gray-400" x-text="widgetControlsForm.zero_inherit ? 'Inherit' : 'Custom'"></span>
                                </label>
                            </div>
                            <div class="p-6">
                                <template x-if="widgetControlsForm.zero_inherit">
                                    <div class="w-full text-sm text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-800 p-3 rounded-lg border border-gray-200 dark:border-gray-700"
                                         x-text="'Inherited: ' + (inheritedControlLabel('zero_handling', widgetKpiConfig?.zero_handling ?? dashboardControls.zero_handling) || 'Remove zeros')"></div>
                                </template>
                                <template x-if="!widgetControlsForm.zero_inherit">
                                    <select x-model="widgetControlsForm.zero_handling"
                                            class="w-full text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 py-2.5 px-4 focus:ring-primary-500 focus:border-primary-500">
                                        <option value="remove">{{ __('Remove zeros from results') }}</option>
                                        <option value="keep">{{ __('Keep zeros in results') }}</option>
                                        <option value="trim">{{ __('Trim leading/trailing zeros') }}</option>
                                    </select>
                                </template>
                            </div>
                        </div>

                        {{-- Card: Granularity --}}
                        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm overflow-hidden flex-shrink-0">
                            <div class="flex items-center justify-between px-6 py-3 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700">
                                <div class="flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-gray-500 dark:text-gray-400">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span class="text-xs font-bold text-gray-800 dark:text-white uppercase tracking-wider">Granularity</span>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" x-model="widgetControlsForm.granularity_inherit" class="sr-only peer"/>
                                    <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-gray-600 peer-checked:bg-primary-600"></div>
                                    <span class="ml-2 text-[10px] uppercase font-bold text-gray-500 dark:text-gray-400" x-text="widgetControlsForm.granularity_inherit ? 'Inherit' : 'Custom'"></span>
                                </label>
                            </div>
                            <div class="p-6">
                                <template x-if="widgetControlsForm.granularity_inherit">
                                    <div class="w-full text-sm text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-800 p-3 rounded-lg border border-gray-200 dark:border-gray-700"
                                         x-text="'Inherited: ' + (inheritedControlLabel('granularity', widgetKpiConfig?.granularity ?? dashboardControls.granularity) || 'Default')"></div>
                                </template>
                                <template x-if="!widgetControlsForm.granularity_inherit">
                                    <div class="space-y-4">
                                        <!-- Dependency/Matrix Selector -->
                                        <template x-if="widgetControlsTarget?.source_type === 'metric' && Object.keys(availableDependencies).length > 0">
                                            <div>
                                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">Data Scope / Matrix</label>
                                                <select x-model="widgetControlsForm.dependency" @change="updateGranularities()"
                                                        class="w-full text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 py-2.5 px-4 focus:ring-primary-500 focus:border-primary-500">
                                                    <template x-for="(label, key) in availableDependencies" :key="key">
                                                        <option :value="key" x-text="label"></option>
                                                    </template>
                                                </select>
                                            </div>
                                        </template>
                                        
                                        <!-- Granularity Selector -->
                                        <div>
                                            <template x-if="widgetControlsTarget?.source_type === 'metric' && Object.keys(availableDependencies).length > 0">
                                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">Granularity</label>
                                            </template>
                                            <template x-if="widgetControlsTarget?.source_type === 'metric'">
                                                <select x-model="widgetControlsForm.granularity"
                                                        class="w-full text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 py-2.5 px-4 focus:ring-primary-500 focus:border-primary-500">
                                                    <template x-for="(label, key) in availableGranularities" :key="key">
                                                        <option :value="key" x-text="label"></option>
                                                    </template>
                                                </select>
                                            </template>
                                            
                                            <!-- Fallback for KPIs -->
                                            <template x-if="widgetControlsTarget?.source_type !== 'metric'">
                                                <select x-model="widgetControlsForm.granularity"
                                                        class="w-full text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 py-2.5 px-4 focus:ring-primary-500 focus:border-primary-500">
                                                    <option value="daily">Daily</option>
                                                    <option value="weekly">Weekly</option>
                                                    <option value="monthly">Monthly</option>
                                                    <option value="query">Query</option>
                                                    <option value="dimensions.page">Page</option>
                                                    <option value="country">Country</option>
                                                    <option value="device">Device</option>
                                                    <option value="post">Post</option>
                                                </select>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                            {{-- Card: Edge Case Handling (KPI widgets only) --}}
                            <template x-if="widgetControlsTarget.source_type === 'kpi'">
                                <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm overflow-hidden flex-shrink-0">
                                    <div class="flex items-center justify-between px-6 py-3 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700">
                                        <div class="flex items-center gap-2">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-gray-500 dark:text-gray-400">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5" />
                                            </svg>
                                            <span class="text-xs font-bold text-gray-800 dark:text-white uppercase tracking-wider">Edge Cases</span>
                                        </div>
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="checkbox" x-model="widgetControlsForm.edge_case_inherit" class="sr-only peer"/>
                                            <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-gray-600 peer-checked:bg-primary-600"></div>
                                            <span class="ml-2 text-[10px] uppercase font-bold text-gray-500 dark:text-gray-400" x-text="widgetControlsForm.edge_case_inherit ? 'Inherit' : 'Custom'"></span>
                                        </label>
                                    </div>
                                    <div class="p-6 space-y-4">
                                        <template x-if="widgetControlsForm.edge_case_inherit">
                                            <div class="w-full text-sm text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-800 p-3 rounded-lg border border-gray-200 dark:border-gray-700">
                                                <span x-text="`WLS: ${widgetControlsForm.edge_case_weighted ? 'On' : 'Off'}, Grouping: ${widgetControlsForm.edge_case_grouping === 'none' ? 'No grouping' : widgetControlsForm.edge_case_grouping === 'histogram' ? 'Auto histogram-elbow' : 'Bottom percentile'}`"></span>
                                                <span class="block text-xs text-gray-400 mt-1">Inherited from KPI configuration</span>
                                            </div>
                                        </template>
                                        <template x-if="!widgetControlsForm.edge_case_inherit">
                                            <div class="space-y-4">
                                                <label class="flex items-center gap-3 cursor-pointer">
                                                    <input type="checkbox" x-model="widgetControlsForm.edge_case_weighted"
                                                           class="w-4 h-4 rounded border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-primary-600 focus:ring-primary-500">
                                                    <span class="text-sm font-medium text-gray-900 dark:text-white">Weighted regression (WLS)</span>
                                                </label>
                                                <p class="text-xs text-gray-500 dark:text-gray-400 -mt-2">Weight each dimension value by its volume so high-volume items influence the regression line proportionally more.</p>
                                                <div>
                                                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">Group low-frequency values</label>
                                                    <select x-model="widgetControlsForm.edge_case_grouping"
                                                            class="w-full text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 py-2.5 px-4 focus:ring-primary-500 focus:border-primary-500">
                                                        <option value="none">No grouping</option>
                                                        <option value="histogram">Auto histogram-elbow</option>
                                                        <option value="percentile">Bottom percentile</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </template>

                            {{-- Card: Max Ratio (KPI widgets only) --}}
                            <template x-if="widgetControlsTarget.source_type === 'kpi'">
                                <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm overflow-hidden flex-shrink-0">
                                    <div class="flex items-center justify-between px-6 py-3 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700">
                                        <div class="flex items-center gap-2">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-gray-500 dark:text-gray-400">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5" />
                                            </svg>
                                            <span class="text-xs font-bold text-gray-800 dark:text-white uppercase tracking-wider">Max Ratio</span>
                                        </div>
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="checkbox" x-model="widgetControlsForm.max_ratio_inherit" class="sr-only peer"/>
                                            <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-gray-600 peer-checked:bg-primary-600"></div>
                                            <span class="ml-2 text-[10px] uppercase font-bold text-gray-500 dark:text-gray-400" x-text="widgetControlsForm.max_ratio_inherit ? 'Inherit' : 'Custom'"></span>
                                        </label>
                                    </div>
                                    <div class="p-6">
                                        <template x-if="widgetControlsForm.max_ratio_inherit">
                                            <div class="w-full text-sm text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-800 p-3 rounded-lg border border-gray-200 dark:border-gray-700">
                                                <span x-text="widgetControlsForm.max_ratio !== null && widgetControlsForm.max_ratio !== undefined ? 'Cap at ' + widgetControlsForm.max_ratio : 'No cap'"></span>
                                                <span class="block text-xs text-gray-400 mt-1">Inherited from KPI configuration</span>
                                            </div>
                                        </template>
                                        <template x-if="!widgetControlsForm.max_ratio_inherit">
                                            <div>
                                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">Value cap (null = no cap)</label>
                                                <input type="number" step="0.01" min="0" x-model="widgetControlsForm.max_ratio"
                                                       class="w-full text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 py-2.5 px-4 focus:ring-primary-500 focus:border-primary-500"
                                                       placeholder="e.g. 1.0"/>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>

                    {{-- Right Column: Variables Configuration --}}
                    <div class="min-w-0 min-h-0 flex overflow-x-auto gap-6 custom-scrollbar pb-2 items-stretch snap-x snap-mandatory" style="flex: 2 1 500px; max-width: 100%; max-height: 100%;">
                        
                        {{-- Series: Raw Metric --}}
                        <template x-if="widgetControlsTarget.source_type !== 'kpi'">
                            <template x-for="(series, index) in widgetControlsForm.raw_series" :key="index">
                                <div class="flex-none w-full sm:w-[calc(50%-0.75rem)] min-w-[280px] h-full min-h-0 flex flex-col snap-start">
                                    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm overflow-hidden flex flex-col h-full min-h-0">
                                        <div class="flex items-center justify-between px-6 py-3 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700">
                                            <div class="flex items-center gap-2">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-gray-500 dark:text-gray-400">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5m.75-9l3-3 2.148 2.148A12.061 12.061 0 0116.5 7.605" />
                                                </svg>
                                                <span class="text-xs font-bold text-gray-800 dark:text-white uppercase tracking-wider" x-text="'Series ' + (index + 1)"></span>
                                            </div>
                                            <button class="text-red-500 hover:text-red-700" 
                                                    x-show="widgetControlsForm.raw_series.length > 1"
                                                    x-on:click="widgetControlsForm.raw_series.splice(index, 1)">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                            </button>
                                        </div>
                                        <div class="p-6 flex-1 flex flex-col gap-5 min-h-0">
                                            <div>
                                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">Channel</label>
                                                <select x-model="series.channel" x-on:change="onWidgetRawChannelChange(index)"
                                                        x-init="$nextTick(() => { $el.value = series.channel })"
                                                        class="w-full text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 py-2.5 px-4 focus:ring-primary-500 focus:border-primary-500">
                                                    <option value="">Select a channel...</option>
                                                    <template x-for="(label, key) in channels" :key="key">
                                                        <option :value="key" x-text="label"></option>
                                                    </template>
                                                </select>
                                            </div>
                                            
                                            <div class="my-2">
                                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">Metrics (Ctrl/Cmd to multi-select)</label>
                                                <div class="flex-1 relative min-h-0 h-32 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800">
                                                    <div class="absolute inset-0 flex flex-col gap-1 overflow-y-auto p-1 custom-scrollbar">
                                                        <template x-for="(label, key) in allChannelMetrics[series.channel] || {}" :key="key">
                                                            <div @click="if ((series.metrics || []).includes(key)) { series.metrics = series.metrics.filter(m => m !== key); } else { series.metrics = [...(series.metrics || []), key]; }"
                                                                 class="flex gap-x-3 items-center px-3 py-2 text-sm text-gray-700 dark:text-gray-200 rounded-md cursor-pointer transition-colors border border-transparent"
                                                                 :class="(series.metrics || []).includes(key) ? 'bg-primary-50 dark:bg-primary-900/30 border-primary-100 dark:border-primary-900/50' : 'hover:bg-gray-100 dark:hover:bg-white/5'">
                                                                <div class="w-4 h-4 shrink-0 flex items-center justify-center rounded border transition-colors"
                                                                     :class="(series.metrics || []).includes(key) ? 'bg-primary-600 border-primary-600' : 'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800'">
                                                                    <svg x-show="(series.metrics || []).includes(key)" class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                                                                    </svg>
                                                                </div>
                                                                <span class="truncate font-medium" :class="(series.metrics || []).includes(key) ? 'text-primary-800 dark:text-primary-200' : ''" x-text="label"></span>
                                                            </div>
                                                        </template>
                                                        <template x-if="!series.channel || Object.keys(allChannelMetrics[series.channel] || {}).length === 0">
                                                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-2 mx-2">Select a channel first.</p>
                                                        </template>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="gap-3 flex-1 flex flex-col min-h-0 mt-6">
                                                <div class="flex items-center justify-between">
                                                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300">Assets (Leave empty for All Assets)</label>
                                                    <template x-if="series.channel">
                                                        <div class="flex gap-3">
                                                            <button @click="selectAllRawAssets(index)" class="text-[11px] font-semibold text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300 hover:underline">Select All</button>
                                                        </div>
                                                    </template>
                                                </div>
                                                <div class="relative">
                                                    <div class="absolute inset-y-0 left-0 w-10 flex items-center justify-center pointer-events-none">
                                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4 text-gray-400">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                                                        </svg>
                                                    </div>
                                                    <input type="text" x-model="searchQueries['raw_' + index]" placeholder="Search assets..." class="bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5" style="padding-left: 2.5rem;">
                                                </div>
                                                <div class="flex-1 relative min-h-0">
                                                    <div class="absolute inset-0 flex flex-col gap-1 overflow-y-auto pr-1 custom-scrollbar">
                                                    <template x-for="(name, id) in allChannelAssets[series.channel] || {}" :key="id">
                                                        <div x-show="(isAssetAllowedByGroups(null, series.channel, id)) && ((searchQueries['raw_' + index] || '') === '' || name.toLowerCase().includes((searchQueries['raw_' + index] || '').toLowerCase()))"
                                                             @click="toggleRawAsset(index, id)"
                                                             class="flex gap-x-3 items-center px-3 py-2.5 text-sm text-gray-700 dark:text-gray-200 rounded-lg cursor-pointer transition-colors border border-transparent"
                                                             :class="(series.assets || []).includes(String(id)) ? 'bg-primary-50 dark:bg-primary-900/30 border-primary-100 dark:border-primary-900/50' : 'hover:bg-gray-100 dark:hover:bg-white/5'">
                                                            <div class="w-4 h-4 shrink-0 flex items-center justify-center rounded border transition-colors"
                                                                 :class="(series.assets || []).includes(String(id)) ? 'bg-primary-600 border-primary-600' : 'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800'">
                                                                <svg x-show="(series.assets || []).includes(String(id))" class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                                                                </svg>
                                                            </div>
                                                            <span class="truncate font-medium" :class="(series.assets || []).includes(String(id)) ? 'text-primary-800 dark:text-primary-200' : ''" x-text="name"></span>
                                                        </div>
                                                    </template>
                                                    <template x-if="!series.channel || Object.keys(allChannelAssets[series.channel] || {}).length === 0">
                                                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Select a channel first.</p>
                                                    </template>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                            
                            {{-- Add Series Button Card --}}
                            <div class="flex-none w-full sm:w-[calc(50%-0.75rem)] min-w-[280px] h-full min-h-0 flex flex-col snap-start">
                                <button x-on:click="widgetControlsForm.raw_series.push({channel: dashboardControls.channel || '', metrics: [], assets: []}); if (dashboardControls.channel) onWidgetRawChannelChange(widgetControlsForm.raw_series.length - 1);" 
                                        class="rounded-xl border border-dashed border-gray-300 dark:border-gray-700 hover:border-primary-500 dark:hover:border-primary-500 bg-transparent hover:bg-primary-50 dark:hover:bg-primary-900/10 flex flex-col items-center justify-center h-full min-h-[300px] transition-colors group">
                                    <div class="w-12 h-12 rounded-full bg-gray-100 dark:bg-gray-800 group-hover:bg-primary-100 dark:group-hover:bg-primary-900/30 flex items-center justify-center mb-3 transition-colors">
                                        <svg class="w-6 h-6 text-gray-400 dark:text-gray-500 group-hover:text-primary-600 dark:group-hover:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                        </svg>
                                    </div>
                                    <span class="text-sm font-semibold text-gray-500 dark:text-gray-400 group-hover:text-primary-600 dark:group-hover:text-primary-400">Add Series</span>
                                </button>
                            </div>
                        </template>

                        {{-- Variables: Assets per variable (KPI) --}}
                        <template x-if="widgetControlsTarget.source_type === 'kpi'">
                            <template x-if="widgetKpiConfig.dependent_channel">
                                <div class="flex-none w-full sm:w-[calc(50%-0.75rem)] min-w-[280px] h-full min-h-0 flex flex-col snap-start">
                                    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm overflow-hidden flex flex-col h-full min-h-0">
                                        <div class="flex items-center justify-between px-6 py-3 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700">
                                            <div class="flex items-center gap-2">
                                                <span class="text-xs font-bold text-gray-800 dark:text-white uppercase tracking-wider">Dependent Series</span>
                                            </div>
                                            <div class="flex flex-col items-end gap-1">
                                                <span class="text-[10px] font-semibold text-gray-600 dark:text-gray-300 bg-gray-200 dark:bg-gray-700 px-2.5 py-1 rounded-full" x-text="channels[widgetKpiConfig.dependent_channel]"></span>
                                                <template x-if="widgetKpiConfig.dependent_metric">
                                                    <span class="text-[10px] font-medium text-gray-500 dark:text-gray-400" x-text="(allChannelMetrics[widgetKpiConfig.dependent_channel] || {})[widgetKpiConfig.dependent_metric] || widgetKpiConfig.dependent_metric"></span>
                                                </template>
                                                <template x-if="!widgetKpiConfig.dependent_metric">
                                                    <span class="text-[10px] font-semibold text-amber-600 dark:text-amber-400 bg-amber-100 dark:bg-amber-900/30 px-2.5 py-1 rounded-full">Dynamic Metric</span>
                                                </template>
                                            </div>
                                        </div>
                                        <div class="p-6 flex-1 flex flex-col gap-5 min-h-0">
                                            
                                            <template x-if="!widgetKpiConfig.dependent_metric">
                                                <div>
                                                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Metric</label>
                                                    <select x-model="widgetControlsForm.metrics[0]"
                                                            class="bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5">
                                                        <option value="">Select a metric...</option>
                                                        <template x-for="(label, key) in allChannelMetrics[widgetKpiConfig.dependent_channel] || {}" :key="key">
                                                            <option :value="key" x-text="label"></option>
                                                        </template>
                                                    </select>
                                                </div>
                                            </template>
                                            
                                            <template x-if="!widgetKpiConfig.dependent_asset_filter || (Array.isArray(widgetKpiConfig.dependent_asset_filter) && widgetKpiConfig.dependent_asset_filter.length === 0)">
                                            <div class="gap-3 flex-1 flex flex-col min-h-0 mt-6">
                                                <template x-if="!widgetKpiConfig.dependent_asset_group">
                                                <div>
                                                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Asset Group</label>
                                                    <select x-model="widgetControlsForm.series_asset_groups.dependent"
                                                            :disabled="widgetControlsForm.series_assets.dependent && widgetControlsForm.series_assets.dependent.length > 0"
                                                            class="bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 disabled:opacity-50 disabled:cursor-not-allowed">
                                                        <option value="">Select an asset group</option>
                                                        <template x-for="(groupData, groupId) in allChannelAssetGroups[widgetKpiConfig.dependent_channel] || {}" :key="groupId">
                                                            <option :value="groupId" x-text="groupData.name"></option>
                                                        </template>
                                                    </select>
                                                </div>
                                                </template>

                                                <div class="flex items-center justify-between mt-2">
                                                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300">Assets <span x-show="widgetKpiConfig.dependent_asset_group">(Limited to KPI Group)</span><span x-show="!widgetKpiConfig.dependent_asset_group">(Leave empty for All Assets)</span></label>
                                                    <div class="flex gap-3">
                                                        <button @click="selectAllKpiAssets('dependent', widgetKpiConfig.dependent_channel, widgetKpiConfig.dependent_asset_group)" :disabled="widgetControlsForm.series_asset_groups.dependent" class="text-[11px] font-semibold text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300 hover:underline disabled:opacity-50 disabled:cursor-not-allowed">Select All</button>
                                                        <button @click="clearAllKpiAssets('dependent')" class="text-[11px] font-semibold text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:underline">Clear</button>
                                                    </div>
                                                </div>
                                                <div class="relative">
                                                    <div class="absolute inset-y-0 left-0 w-10 flex items-center justify-center pointer-events-none">
                                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4 text-gray-400">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                                                        </svg>
                                                    </div>
                                                    <input type="text" x-model="searchQueries['dependent']" :disabled="widgetControlsForm.series_asset_groups.dependent" placeholder="Search assets..." class="bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 disabled:opacity-50 disabled:cursor-not-allowed" style="padding-left: 2.5rem;">
                                                </div>
                                                <div class="flex-1 relative min-h-0">
                                                    <div class="absolute inset-0 flex flex-col gap-1 overflow-y-auto pr-1 custom-scrollbar">
                                                    <template x-for="(name, id) in allChannelAssets[widgetKpiConfig.dependent_channel] || {}" :key="id">
                                                        <div x-show="isAssetAllowedByGroups('dependent', widgetKpiConfig.dependent_channel, id) && ((searchQueries['dependent'] || '') === '' || name.toLowerCase().includes((searchQueries['dependent'] || '').toLowerCase()))"
                                                             @click="if (!widgetControlsForm.series_asset_groups.dependent) toggleKpiAsset('dependent', id)"
                                                             class="flex gap-x-3 items-center px-3 py-2.5 text-sm text-gray-700 dark:text-gray-200 rounded-lg cursor-pointer transition-colors border border-transparent"
                                                             :class="(widgetControlsForm.series_asset_groups.dependent) ? 'opacity-50 cursor-not-allowed' : ((widgetControlsForm.series_assets.dependent || []).includes(String(id)) ? 'bg-primary-50 dark:bg-primary-900/30 border-primary-100 dark:border-primary-900/50' : 'hover:bg-gray-100 dark:hover:bg-white/5')">
                                                            <div class="w-4 h-4 shrink-0 flex items-center justify-center rounded border transition-colors"
                                                                 :class="(widgetControlsForm.series_assets.dependent || []).includes(String(id)) ? 'bg-primary-600 border-primary-600' : 'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800'">
                                                                <svg x-show="(widgetControlsForm.series_assets.dependent || []).includes(String(id))" class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                                                                </svg>
                                                            </div>
                                                            <span class="truncate font-medium" :class="(widgetControlsForm.series_assets.dependent || []).includes(String(id)) ? 'text-primary-800 dark:text-primary-200' : ''" x-text="name"></span>
                                                        </div>
                                                    </template>
                                                    <template x-if="!allChannelAssets[widgetKpiConfig.dependent_channel] || Object.keys(allChannelAssets[widgetKpiConfig.dependent_channel]).length === 0">
                                                        <p class="text-xs text-gray-400 dark:text-gray-500">No assets loaded for this channel.</p>
                                                    </template>
                                                    </div>
                                                </div>
                                            </div>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </template>

                        <template x-if="widgetControlsTarget.source_type === 'kpi'">
                            <template x-if="widgetKpiConfig.independent_variables">
                                <template x-for="(varCfg, idx) in widgetKpiConfig.independent_variables" :key="idx">
                                    <div class="flex-none w-full sm:w-[calc(50%-0.75rem)] min-w-[280px] h-full min-h-0 flex flex-col snap-start" x-show="varCfg.independent_channel">
                                        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm overflow-hidden flex flex-col h-full min-h-0">
                                            <div class="flex items-center justify-between px-6 py-3 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700">
                                                <div class="flex items-center gap-2">
                                                    <span class="text-xs font-bold text-gray-800 dark:text-white uppercase tracking-wider" x-text="'Independent ' + idx"></span>
                                                </div>
                                                <div class="flex flex-col items-end gap-1">
                                                    <span class="text-[10px] font-semibold text-gray-600 dark:text-gray-300 bg-gray-200 dark:bg-gray-700 px-2.5 py-1 rounded-full" x-text="channels[varCfg.independent_channel]"></span>
                                                    <template x-if="varCfg.independent_metric">
                                                        <span class="text-[10px] font-medium text-gray-500 dark:text-gray-400" x-text="(allChannelMetrics[varCfg.independent_channel] || {})[varCfg.independent_metric] || varCfg.independent_metric"></span>
                                                    </template>
                                                    <template x-if="!varCfg.independent_metric">
                                                        <span class="text-[10px] font-semibold text-amber-600 dark:text-amber-400 bg-amber-100 dark:bg-amber-900/30 px-2.5 py-1 rounded-full">Dynamic Metric</span>
                                                    </template>
                                                </div>
                                            </div>
                                            <div class="p-6 flex-1 flex flex-col gap-5 min-h-0">
                                                
                                                <template x-if="!varCfg.independent_metric">
                                                    <div>
                                                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Metric</label>
                                                        <select x-model="widgetControlsForm.metrics[idx + 1]"
                                                                class="bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5">
                                                            <option value="">Select a metric...</option>
                                                            <template x-for="(label, key) in allChannelMetrics[varCfg.independent_channel] || {}" :key="key">
                                                                <option :value="key" x-text="label"></option>
                                                            </template>
                                                        </select>
                                                    </div>
                                                </template>
                                                
                                                <template x-if="!varCfg.independent_asset_filter || (Array.isArray(varCfg.independent_asset_filter) && varCfg.independent_asset_filter.length === 0)">
                                                <div class="gap-3 flex-1 flex flex-col min-h-0 mt-6">
                                                    <template x-if="!varCfg.independent_asset_group">
                                                    <div>
                                                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Asset Group</label>
                                                        <select x-model="widgetControlsForm.series_asset_groups['independent_' + idx]"
                                                                :disabled="widgetControlsForm.series_assets['independent_' + idx] && widgetControlsForm.series_assets['independent_' + idx].length > 0"
                                                                class="bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 disabled:opacity-50 disabled:cursor-not-allowed">
                                                            <option value="">Select an asset group</option>
                                                            <template x-for="(groupData, groupId) in allChannelAssetGroups[varCfg.independent_channel] || {}" :key="groupId">
                                                                <option :value="groupId" x-text="groupData.name"></option>
                                                            </template>
                                                        </select>
                                                    </div>
                                                    </template>

                                                    <div class="flex items-center justify-between mt-2">
                                                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300">Assets <span x-show="varCfg.independent_asset_group">(Limited to KPI Group)</span><span x-show="!varCfg.independent_asset_group">(Leave empty for All Assets)</span></label>
                                                        <div class="flex gap-3">
                                                            <button @click="selectAllKpiAssets('independent_' + idx, varCfg.independent_channel, varCfg.independent_asset_group)" :disabled="widgetControlsForm.series_asset_groups['independent_' + idx]" class="text-[11px] font-semibold text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300 hover:underline disabled:opacity-50 disabled:cursor-not-allowed">Select All</button>
                                                        </div>
                                                    </div>
                                                    <div class="relative">
                                                        <div class="absolute inset-y-0 left-0 w-10 flex items-center justify-center pointer-events-none">
                                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4 text-gray-400">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                                                            </svg>
                                                        </div>
                                                        <input type="text" x-model="searchQueries['independent_' + idx]" :disabled="widgetControlsForm.series_asset_groups['independent_' + idx]" placeholder="Search assets..." class="bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 disabled:opacity-50 disabled:cursor-not-allowed" style="padding-left: 2.5rem;">
                                                    </div>
                                                    <div class="flex-1 relative min-h-0">
                                                        <div class="absolute inset-0 flex flex-col gap-1 overflow-y-auto pr-1 custom-scrollbar">
                                                        <template x-for="(name, id) in allChannelAssets[varCfg.independent_channel] || {}" :key="id">
                                                            <div x-show="isAssetAllowedByGroups('independent_' + idx, varCfg.independent_channel, id) && ((searchQueries['independent_' + idx] || '') === '' || name.toLowerCase().includes((searchQueries['independent_' + idx] || '').toLowerCase()))"
                                                                 @click="if (!widgetControlsForm.series_asset_groups['independent_' + idx]) toggleKpiAsset('independent_' + idx, id)"
                                                                 class="flex gap-x-3 items-center px-3 py-2.5 text-sm text-gray-700 dark:text-gray-200 rounded-lg cursor-pointer transition-colors border border-transparent"
                                                                 :class="(widgetControlsForm.series_asset_groups['independent_' + idx]) ? 'opacity-50 cursor-not-allowed' : ((widgetControlsForm.series_assets['independent_' + idx] || []).includes(String(id)) ? 'bg-primary-50 dark:bg-primary-900/30 border-primary-100 dark:border-primary-900/50' : 'hover:bg-gray-100 dark:hover:bg-white/5')">
                                                                <div class="w-4 h-4 shrink-0 flex items-center justify-center rounded border transition-colors"
                                                                     :class="(widgetControlsForm.series_assets['independent_' + idx] || []).includes(String(id)) ? 'bg-primary-600 border-primary-600' : 'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800'">
                                                                    <svg x-show="(widgetControlsForm.series_assets['independent_' + idx] || []).includes(String(id))" class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                                                                    </svg>
                                                                </div>
                                                                <span class="truncate font-medium" :class="(widgetControlsForm.series_assets['independent_' + idx] || []).includes(String(id)) ? 'text-primary-800 dark:text-primary-200' : ''" x-text="name"></span>
                                                            </div>
                                                        </template>
                                                        <template x-if="!allChannelAssets[varCfg.independent_channel] || Object.keys(allChannelAssets[varCfg.independent_channel]).length === 0">
                                                            <p class="text-xs text-gray-400 dark:text-gray-500">No assets loaded for this channel.</p>
                                                        </template>
                                                        </div>
                                                    </div>
                                                </div>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </template>
                        </template>

                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 p-6 sm:p-6 border-t border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 rounded-b-xl">
                    <span x-show="widgetControlsError" x-text="widgetControlsError" class="text-sm text-red-600 dark:text-red-400 mr-auto font-medium" style="display: none;"></span>
                    <button class="text-sm font-semibold text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-gray-100 px-6 py-2.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors border border-transparent"
                            x-on:click="showWidgetControls = false">Cancel
                    </button>
                    <button class="text-sm font-semibold text-white bg-primary-600 hover:bg-primary-500 px-6 py-2.5 rounded-lg shadow-sm transition-colors border border-transparent"
                            x-on:click="confirmWidgetControls()">Save Controls
                    </button>
                </div>
            </div>
        </div>

        {{-- ============================================================ --}}
        {{-- ADD WIDGET MODAL                                            --}}
        {{-- ============================================================ --}}
        <div x-show="showAddWidgetModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center">
            <div class="absolute inset-0 bg-black/50" x-on:click="showAddWidgetModal = false"></div>
            <div class="relative bg-white dark:bg-gray-900 rounded-xl shadow-xl max-w-5xl w-full mx-4 p-6 flex flex-col max-h-[90vh]">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">{{ __('Add Widget') }}</h2>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 overflow-y-auto pr-2 pb-2">
                    {{-- Column 1 (Settings) --}}
                    <div class="space-y-6 md:col-span-1">
                        {{-- Name --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('Widget Name') }}</label>
                            <input type="text" x-model="addWidgetForm.name"
                                   class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100"
                                   placeholder="My Widget"/>
                        </div>

                        {{-- Source Type --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('Data Source') }}</label>
                            <div class="grid grid-cols-1 gap-3">
                                <template x-for="(label, type) in sourceTypes" :key="type">
                                    <button class="p-3 rounded-lg border text-center text-sm transition-colors"
                                            :class="addWidgetForm.source_type === type
                                                ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-300'
                                                : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'"
                                            x-on:click="addWidgetForm.source_type = type; addWidgetForm.widget_type = ''">
                                        <span x-text="label"></span>
                                    </button>
                                </template>
                            </div>
                        </div>

                        {{-- KPI (if kpi source) --}}
                        <template x-if="addWidgetForm.source_type === 'kpi'">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Select KPI</label>
                                <select x-model="addWidgetForm.custom_kpi_id"
                                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100">
                                    <option value="">{{ __('Choose a KPI...') }}</option>
                                    <template x-for="(kpi, id) in kpis" :key="id">
                                        <option :value="id" x-text="kpi.name"></option>
                                    </template>
                                </select>
                            </div>
                        </template>
                    </div>

                    {{-- Column 2 (Widget Types) --}}
                    <div class="space-y-6 md:col-span-2">
                        {{-- Widget Type --}}
                        <template x-if="addWidgetForm.source_type">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Widget Type</label>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <template x-for="(label, type) in availableWidgetTypes" :key="type">
                                        <button class="p-3 rounded-xl border text-left transition-colors flex items-center gap-3"
                                                :class="addWidgetForm.widget_type === type
                                                    ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20 ring-1 ring-primary-500'
                                                    : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 hover:border-gray-300 dark:hover:border-gray-600 shadow-sm'"
                                                x-on:click="addWidgetForm.widget_type = type">
                                            <div class="w-12 h-10 flex-shrink-0 bg-gray-50 dark:bg-gray-900/50 rounded-lg border border-gray-100 dark:border-gray-800 flex items-center justify-center" x-html="getWidgetSvg(type)">
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center gap-2">
                                                    <span class="block text-sm font-semibold text-gray-900 dark:text-white truncate" x-text="label"></span>
                                                    <template x-if="optimalWidgetTypes.includes(type)">
                                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-primary-100 text-primary-800 dark:bg-primary-900/30 dark:text-primary-400 shrink-0">Recommended</span>
                                                    </template>
                                                </div>
                                                <span class="block text-[11px] text-gray-500 dark:text-gray-400 leading-tight mt-0.5" style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;" x-text="getWidgetDescription(type)"></span>
                                            </div>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <button class="px-4 py-2 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800"
                            x-on:click="showAddWidgetModal = false">Cancel
                    </button>
                    <button class="px-4 py-2 rounded-lg bg-primary-600 text-white hover:bg-primary-500 disabled:opacity-50"
                            :disabled="!canAddWidget()"
                            x-on:click="confirmAddWidget()">Add Widget
                    </button>
                </div>
            </div>
        </div>

        {{-- ============================================================ --}}
        {{-- SHARE DIALOG                                               --}}
        {{-- ============================================================ --}}
        <div x-show="showShareDialog" x-cloak class="fixed inset-0 z-50 flex items-center justify-center">
            <div class="absolute inset-0 bg-black/50" x-on:click="showShareDialog = false"></div>
            <div class="relative bg-white dark:bg-gray-900 rounded-xl shadow-xl max-w-md w-full mx-4 p-6 space-y-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('Share Dashboard') }}</h2>

                {{-- Public Toggle --}}
                <div
                    class="flex items-center justify-between p-3 rounded-lg border border-gray-200 dark:border-gray-700">
                    <div>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ __('Public access') }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Anyone with the link can view this
                            dashboard</p>
                    </div>
                    <button class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors"
                            :class="isPublic ? 'bg-primary-600' : 'bg-gray-300 dark:bg-gray-600'"
                            x-on:click="togglePublic()">
                        <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"
                              :class="isPublic ? 'translate-x-6' : 'translate-x-1'"></span>
                    </button>
                </div>

                {{-- Shared Users --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('Shared with') }}</label>
                    <div class="space-y-2 max-h-48 overflow-y-auto">
                        <template x-for="user in sharedUsers" :key="user.id">
                            <div
                                class="flex items-center justify-between px-3 py-2 rounded-lg bg-gray-50 dark:bg-gray-800">
                                <div>
                                    <p class="text-sm text-gray-900 dark:text-white" x-text="user.name"></p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400" x-text="user.email"></p>
                                </div>
                                <button class="text-xs text-red-500 hover:underline"
                                        x-on:click="unshareUser(user.id)">Remove
                                </button>
                            </div>
                        </template>
                        <template x-if="!sharedUsers.length">
                            <p class="text-sm text-gray-400 dark:text-gray-500 text-center py-4">{{ __('No users shared yet') }}</p>
                        </template>
                    </div>
                </div>

                {{-- Add User --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Add
                        collaborator</label>
                    <div class="flex gap-2">
                        <select x-model="shareUserId"
                                class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 text-sm">
                            <option value="">{{ __('Select a user...') }}</option>
                            <template x-for="user in collaborators" :key="user.id">
                                <template x-if="!isShared(user.id)">
                                    <option :value="user.id" x-text="user.name + ' (' + user.email + ')'"></option>
                                </template>
                            </template>
                        </select>
                        <button
                            class="px-3 py-2 rounded-lg bg-primary-600 text-white text-sm hover:bg-primary-500 disabled:opacity-50"
                            :disabled="!shareUserId"
                            x-on:click="addSharedUser()">Add
                        </button>
                    </div>
                </div>

                <div class="flex justify-end pt-2 border-t border-gray-200 dark:border-gray-700">
                    <button
                        class="px-4 py-2 rounded-lg text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800"
                        x-on:click="showShareDialog = false">Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <style>
            /* Force native GridStack resize handle to bottom right */
            .grid-stack-item > .ui-resizable-handle,
            .grid-stack-item > .gs-resize-handle {
                display: block !important;
                position: absolute !important;
                bottom: 0px !important;
                right: 0px !important;
                width: 20px !important;
                height: 20px !important;
                cursor: se-resize !important;
                z-index: 1000 !important;
                background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" fill="none" stroke="%23666" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 20 20"><path d="m10 3 2 2H8l2-2v14l-2-2h4l-2 2"/></svg>') !important;
                background-repeat: no-repeat !important;
                background-position: center !important;
            }
            .dark .grid-stack-item > .ui-resizable-handle,
            .dark .grid-stack-item > .gs-resize-handle {
                background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" fill="none" stroke="%23A1A1AA" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 20 20"><path d="m10 3 2 2H8l2-2v14l-2-2h4l-2 2"/></svg>') !important;
            }
        </style>
        <script src="https://cdn.jsdelivr.net/npm/gridstack@12.6.0/dist/gridstack-all.min.js"></script>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/gridstack@12.6.0/dist/gridstack.min.css"/>
    @endpush

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('dashboardBuilder', () => {
                const dashboardControls = @json($this->getDashboardControls());
                return {
                    // ─── Grid State ───
                    widgets: @json($this->widgets ?? []),
                    gridLayout: @json($this->gridState ?? []),
                    grid: null,
                    widgetLabels: @json(\App\Services\WidgetTypeRegistry::getWidgetLabels()),

                    // ─── Channels & Assets ───
                    channels: @json($this->getActiveChannels()),
                    allChannelAssets: {},
                    allChannelAssetGroups: {},
                    allChannelMetrics: {},
                    dashboardAssets: {},
                    dashboardMetrics: {},
                    availableDependencies: {},
                    availableGranularities: {},

                    // ─── Dashboard Controls ──
                    showDashboardControls: false,
                    showWidgetControls: false,
                    dashboardControls,
                    assetGroups: @json($this->getAllAssetGroups()),

                    // ─── Widget Controls ──
                    widgetControlsTarget: {},
                    widgetControlsForm: {
                        date_inherit: true,
                        date_start: '',
                        date_end: '',
                        zero_inherit: true,
                        zero_handling: dashboardControls.zero_handling || 'remove',
                        series_inherit: true,
                        channel: '',
                        asset_mode: 'single',
                        asset: '',
                        assets: [],
                        granularity: dashboardControls.granularity || 'daily',
                        dependency: null,
                        metrics: [],
                        series_assets: {},
                        series_asset_groups: {},
                    },
                    widgetKpiConfig: {},
                    widgetAssets: {},
                    widgetMetrics: {},
                    searchQueries: {},

                    // ─── Share ──
                    showShareDialog: false,
                    isPublic: {{ $this->dashboard->is_public ? 'true' : 'false' }},
                    collaborators: [],
                    sharedUsers: [],
                    shareUserId: '',

                    // ─── Add Widget Modal ──
                    showAddWidgetModal: false,
                    sourceTypes: @json($this->getAvailableSourceTypes()),
                    kpis: @json($this->getKpisForWidgetPicker()),
                    addWidgetForm: {
                        source_type: '',
                        custom_kpi_id: '',
                        widget_type: '',
                        name: '',
                    },

                    // ─── Computed ──
                    get optimalWidgetTypes() {
                        if (this.addWidgetForm.source_type === 'kpi' && this.addWidgetForm.custom_kpi_id) {
                            const kpiData = this.kpis[this.addWidgetForm.custom_kpi_id];
                            return kpiData ? (kpiData.optimal_widgets || []) : [];
                        }
                        return [];
                    },

                    get availableWidgetTypes() {
                        if (!this.addWidgetForm.source_type) return {};
                        
                        const allTypes = @json(\App\Services\WidgetTypeRegistry::getWidgetLabels());
                        let filtered = {};
                        
                        if (this.addWidgetForm.source_type === 'metric') {
                            const allowed = ['tile', 'line_chart', 'bar_chart', 'sparkline', 'table', 'gauge'];
                            for (const t of allowed) {
                                if (allTypes[t]) filtered[t] = allTypes[t];
                            }
                            return filtered;
                        }
                        
                        if (this.addWidgetForm.source_type === 'kpi') {
                            const kpiId = this.addWidgetForm.custom_kpi_id;
                            if (!kpiId) return {}; // Wait for KPI selection
                            
                            const kpiData = this.kpis[kpiId];
                            const allowed = kpiData ? (kpiData.compatible_widgets || []) : [];
                            
                            if (allowed.length === 0) return allTypes;
                            
                            for (const t of allowed) {
                                if (allTypes[t]) filtered[t] = allTypes[t];
                            }
                            return filtered;
                        }
                        
                        return allTypes;
                    },

                    get availableChartTypesForControls() {
                        const target = this.widgetControlsTarget;
                        if (!target || !target.source_type) return {};

                        const allTypes = @json(\App\Services\WidgetTypeRegistry::getWidgetLabels());
                        let filtered = {};

                        if (target.source_type === 'metric') {
                            const allowed = ['tile', 'line_chart', 'bar_chart', 'sparkline', 'table', 'gauge'];
                            for (const t of allowed) {
                                if (allTypes[t]) filtered[t] = allTypes[t];
                            }
                            return filtered;
                        }

                        if (target.source_type === 'kpi') {
                            const kpiId = target.source_config?.custom_kpi_id;
                            if (!kpiId) return allTypes;

                            const kpiData = this.kpis[kpiId];
                            const allowed = kpiData ? (kpiData.compatible_widgets || []) : [];

                            if (allowed.length === 0) return allTypes;

                            for (const t of allowed) {
                                if (allTypes[t]) filtered[t] = allTypes[t];
                            }
                            // Always include the current type even if no longer in compat list
                            if (target.widget_type && !filtered[target.widget_type]) {
                                filtered[target.widget_type] = allTypes[target.widget_type] || target.widget_type;
                            }
                            return filtered;
                        }

                        return allTypes;
                    },

                    // ─── UI Helpers ───
                    getWidgetDescription(type) {
                        const descs = {
                            tile: 'Single large number for totals',
                            line_chart: 'Track continuous trends over time',
                            bar_chart: 'Compare discrete volumes side-by-side',
                            scatter_plot: 'Find correlations and trendlines',
                            combo_chart: 'Dual-axis bars and lines (e.g. MACD)',
                            table: 'Detailed row-by-row data view',
                            gauge: 'Percentage or progress to a target',
                            sparkline: 'Minimalist trendline without axes',
                            anomaly_chart: 'Highlights statistical outliers in red'
                        };
                        return descs[type] || 'Standard widget';
                    },

                    getWidgetSvg(type) {
                        const svgs = {
                            tile: `<svg viewBox="0 0 40 24" class="w-full h-full"><text x="20" y="16" text-anchor="middle" font-weight="bold" font-size="14" class="fill-gray-800 dark:fill-gray-200">12K</text><path d="M 28 8 L 32 4 L 36 8 M 32 4 L 32 16" class="stroke-green-500" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none"/></svg>`,
                            line_chart: `<svg viewBox="0 0 40 24" class="w-full h-full"><path d="M 4 20 L 12 12 L 20 16 L 28 6 L 36 8" class="stroke-primary-500" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none"/><circle cx="12" cy="12" r="2.5" class="fill-primary-500"/><circle cx="20" cy="16" r="2.5" class="fill-primary-500"/><circle cx="28" cy="6" r="2.5" class="fill-primary-500"/></svg>`,
                            bar_chart: `<svg viewBox="0 0 40 24" class="w-full h-full"><rect x="6" y="10" width="6" height="10" rx="1" class="fill-primary-400"/><rect x="17" y="6" width="6" height="14" rx="1" class="fill-primary-600"/><rect x="28" y="14" width="6" height="6" rx="1" class="fill-primary-300"/></svg>`,
                            scatter_plot: `<svg viewBox="0 0 40 24" class="w-full h-full"><line x1="4" y1="20" x2="36" y2="4" class="stroke-red-500" stroke-width="1.5" stroke-dasharray="2 2" stroke-linecap="round"/><circle cx="10" cy="16" r="1.5" class="fill-primary-500"/><circle cx="14" cy="18" r="1.5" class="fill-primary-500"/><circle cx="20" cy="10" r="1.5" class="fill-primary-500"/><circle cx="24" cy="14" r="1.5" class="fill-primary-500"/><circle cx="30" cy="8" r="1.5" class="fill-primary-500"/><circle cx="34" cy="10" r="1.5" class="fill-primary-500"/></svg>`,
                            combo_chart: `<svg viewBox="0 0 40 24" class="w-full h-full"><rect x="6" y="14" width="4" height="6" class="fill-green-500 opacity-50"/><rect x="14" y="8" width="4" height="12" class="fill-green-500 opacity-50"/><rect x="22" y="16" width="4" height="4" class="fill-red-500 opacity-50"/><rect x="30" y="12" width="4" height="8" class="fill-red-500 opacity-50"/><path d="M 4 18 L 16 6 L 24 10 L 36 4" class="stroke-blue-500" stroke-width="1.5" fill="none"/><path d="M 4 14 L 16 10 L 24 16 L 36 8" class="stroke-yellow-500" stroke-width="1.5" fill="none"/></svg>`,
                            table: `<svg viewBox="0 0 40 24" class="w-full h-full"><rect x="4" y="4" width="32" height="16" rx="2" class="stroke-gray-400 dark:stroke-gray-600" stroke-width="1.5" fill="none"/><line x1="4" y1="10" x2="36" y2="10" class="stroke-gray-400 dark:stroke-gray-600" stroke-width="1.5"/><line x1="4" y1="15" x2="36" y2="15" class="stroke-gray-300 dark:stroke-gray-700" stroke-width="1"/><line x1="16" y1="4" x2="16" y2="20" class="stroke-gray-400 dark:stroke-gray-600" stroke-width="1.5"/></svg>`,
                            gauge: `<svg viewBox="0 0 40 24" class="w-full h-full"><path d="M 10 20 A 10 10 0 0 1 30 20" class="stroke-gray-200 dark:stroke-gray-700" stroke-width="4" stroke-linecap="round" fill="none"/><path d="M 10 20 A 10 10 0 0 1 20 10" class="stroke-primary-500" stroke-width="4" stroke-linecap="round" fill="none"/><circle cx="20" cy="20" r="2" class="fill-gray-800 dark:fill-gray-200"/><line x1="20" y1="20" x2="14" y2="14" class="stroke-gray-800 dark:stroke-gray-200" stroke-width="1.5" stroke-linecap="round"/></svg>`,
                            sparkline: `<svg viewBox="0 0 40 24" class="w-full h-full"><path d="M 4 18 Q 12 10 20 16 T 36 6" class="stroke-primary-400" stroke-width="2" stroke-linecap="round" fill="none"/></svg>`,
                            anomaly_chart: `<svg viewBox="0 0 40 24" class="w-full h-full"><path d="M 4 20 L 12 18 L 20 8 L 28 16 L 36 14" class="stroke-gray-400 dark:stroke-gray-600" stroke-width="1.5" stroke-linecap="round" stroke-dasharray="2 2" fill="none"/><circle cx="20" cy="8" r="4" class="fill-red-500 ring-2 ring-red-200 dark:ring-red-900/50"/></svg>`
                        };
                        return svgs[type] || svgs['tile'];
                    },

                    // ─── Initialization ──
                    init() {
                        this.$nextTick(() => {
                            const container = document.getElementById('grid-stack');
                            if (container && !this.grid) {
                                this.initGrid();
                            }
                            this.initAllAssets();
                        });
                    },

                    initAllAssets() {
                        const channelKeys = Object.keys(this.channels);
                        channelKeys.forEach(ch => {
                            @this.getAssetsForChannel(ch).then(assets => {
                                this.allChannelAssets = { ...this.allChannelAssets, [ch]: assets };
                                if (ch === this.dashboardControls.channel) {
                                    this.dashboardAssets = assets;
                                }
                            });
                            @this.getAssetGroupsForChannel(ch).then(groups => {
                                this.allChannelAssetGroups = { ...this.allChannelAssetGroups, [ch]: groups };
                            });
                            @this.getMetricsForChannel(ch).then(metrics => {
                                this.allChannelMetrics = { ...this.allChannelMetrics, [ch]: metrics };
                                if (ch === this.dashboardControls.channel) {
                                    this.dashboardMetrics = metrics;
                                }
                            });
                        });
                    },
                    
                    updateDependenciesAndGranularities(explicitSavedDependency = null, explicitSavedGranularity = null) {
                        const widget = this.widgetControlsTarget;
                        if (!widget) return;
                        
                        let ch = '';
                        if (widget.source_type === 'kpi') {
                            ch = this.widgetKpiConfig?.dependent_channel || this.widgetControlsForm.channel;
                        } else {
                            ch = this.widgetControlsForm.raw_series?.[0]?.channel || '';
                        }
                        
                        if (!ch) {
                            this.availableDependencies = {};
                            this.availableGranularities = {};
                            return;
                        }
                        
                        const savedDependency = explicitSavedDependency || this.widgetControlsForm.dependency;
                        const savedGranularity = explicitSavedGranularity || this.widgetControlsForm.granularity;
                        
                        console.log('updateDependenciesAndGranularities - input:');
                        console.log('ch:', ch);
                        console.log('explicitSavedDependency:', explicitSavedDependency);
                        console.log('explicitSavedGranularity:', explicitSavedGranularity);
                        console.log('savedDependency:', savedDependency);
                        console.log('savedGranularity:', savedGranularity);
                        
                        @this.getDependenciesForChannel(ch).then(deps => {
                            const willSetDependency = (savedDependency && deps && deps[savedDependency])
                                ? savedDependency
                                : (deps && Object.keys(deps).length > 0 ? Object.keys(deps)[0] : null);
                            
                            // Force Alpine reactivity transition
                            this.widgetControlsForm.dependency = '';
                            this.availableDependencies = deps || {};
                            console.log('getDependenciesForChannel resolved:', deps);
                            
                            this.$nextTick(() => {
                                this.widgetControlsForm.dependency = willSetDependency;
                                console.log('Dependency after $nextTick:', this.widgetControlsForm.dependency);
                                
                                this.updateGranularities(ch, savedGranularity);
                            });
                        });
                    },
                    
                    updateGranularities(ch, explicitSavedGranularity = null) {
                        if (!ch) {
                            ch = (this.widgetControlsTarget?.source_type === 'kpi')
                                ? (this.widgetKpiConfig?.dependent_channel || this.widgetControlsForm.channel)
                                : (this.widgetControlsForm.raw_series?.[0]?.channel || '');
                        }
                        if (!ch) return;
                        
                        const savedGranularity = explicitSavedGranularity || this.widgetControlsForm.granularity;
                        
                        console.log('updateGranularities - input:');
                        console.log('explicitSavedGranularity:', explicitSavedGranularity);
                        console.log('savedGranularity:', savedGranularity);
                        console.log('this.widgetControlsForm.dependency (passed to Livewire):', this.widgetControlsForm.dependency);
                        
                        @this.getGranularitiesForChannel(ch, this.widgetControlsForm.dependency).then(grans => {
                            const willSetGranularity = (savedGranularity && grans && grans[savedGranularity])
                                ? savedGranularity
                                : (grans && Object.keys(grans).length > 0 ? 'daily' : null);
                                
                            // Force Alpine reactivity transition
                            this.widgetControlsForm.granularity = '';
                            this.availableGranularities = grans || {};
                            console.log('getGranularitiesForChannel resolved:', grans);
                            
                            this.$nextTick(() => {
                                if (willSetGranularity) {
                                    this.widgetControlsForm.granularity = willSetGranularity;
                                }
                                console.log('Granularity after $nextTick:', this.widgetControlsForm.granularity);
                            });
                        });
                    },

                    // ─── Grid ──
                    initGrid() {
                        if (typeof GridStack === 'undefined') {
                            setTimeout(() => this.initGrid(), 50);
                            return;
                        }

                        const container = document.getElementById('grid-stack');
                        if (!container) return;

                        this.grid = GridStack.init({
                            column: 12,
                            minRow: 6,
                            cellHeight: 100,
                            margin: 12,
                            float: true,
                            acceptWidgets: true,
                            removable: false,
                            resizable: {handles: 'se'},
                            draggable: {handle: '.grid-stack-item-content .rounded-t-lg'},
                        });

                        this.grid.on('change', (event, items) => {
                            // The layout is now captured synchronously by getLayout()
                        });
                    },

                    getLayout() {
                        if (!this.grid) return [];
                        return this.grid.engine.nodes.map(node => ({
                            id: node.id || (node.el ? parseInt(node.el.getAttribute('gs-id')) : 0),
                            x: node.x,
                            y: node.y,
                            w: node.w,
                            h: node.h,
                        })).filter(node => node.id !== 0);
                    },

                    reloadGrid() {
                        if (this.grid) {
                            this.grid.destroy(false);
                            this.grid = null;
                        }
                        this.$nextTick(() => this.initGrid());
                    },

                    syncGridWithWidgets() {
                        // Handled natively by x-init calling grid.makeWidget($el)
                    },

                    // ─── Helpers ──
                    inheritedControlLabel(key, value) {
                        const labels = {
                            zero_handling: {remove: 'Remove zeros', keep: 'Keep zeros', trim: 'Trim zeros'},
                            granularity: {daily: 'Daily', weekly: 'Weekly', monthly: 'Monthly', query: 'Query', 'dimensions.page': 'Page', country: 'Country', device: 'Device', post: 'Post'},
                            max_ratio: 'Cap at {value}',
                        };
                        if (key === 'max_ratio') {
                            return value !== null && value !== undefined ? `Cap at ${value}` : 'No cap';
                        }
                        return (labels[key] && labels[key][value]) || value || '—';
                    },

                    widgetHasCustomControls(widget) {
                        if (!widget || !widget.controls) return false;
                        const c = widget.controls;
                        return c && Object.keys(c).length > 0;
                    },

                    // ─── Dashboard Controls ──
                    openDashboardControls() {
                        this.showDashboardControls = true;
                    },

                    confirmDashboardControls() {
                        const c = this.dashboardControls;
                        let adjustedWidgets = 0;
                        let warningTriggered = false;
                        
                        // 4. Validate all contained widgets before saving
                        this.widgets.forEach(w => {
                            let wc = w.controls || {};
                            let hasCustomDate = wc.date_start !== undefined || wc.date_end !== undefined;
                            if (hasCustomDate) {
                                let changed = false;
                                
                                if (wc.date_start && c.date_start && wc.date_start < c.date_start) {
                                    wc.date_start = c.date_start;
                                    changed = true;
                                }
                                
                                let dashEnd = c.date_end || '{{ date('Y-m-d', strtotime('-1 day')) }}';
                                if (wc.date_end && wc.date_end > dashEnd) {
                                    wc.date_end = dashEnd;
                                    changed = true;
                                }
                                
                                if (changed) {
                                    w.controls = wc;
                                    @this.saveWidgetControls(w.id, wc);
                                    adjustedWidgets++;
                                    warningTriggered = true;
                                }
                            }
                        });
                        
                        if (warningTriggered) {
                            alert("Warning: " + adjustedWidgets + " widget(s) did not comply with the new dashboard date range and were automatically adjusted.");
                        }

                        const payload = {
                            date_start: c.date_start || '',
                            date_end: c.date_end || '',
                            zero_handling: c.zero_handling || 'remove',
                            granularity: c.granularity || 'daily',
                            edge_case_weighted: c.edge_case_weighted !== undefined ? !!c.edge_case_weighted : true,
                            edge_case_grouping: c.edge_case_grouping || 'none',
                            asset_group: c.asset_group || '',
                            show_asset_group_selector: c.show_asset_group_selector === true,
                        };
                        @this.saveDashboardControls(payload);
                        this.showDashboardControls = false;
                    },

                    // ─── Widget Controls ──
                    widgetControlsError: '',
                    openWidgetControls(widget) {
                        this.widgetControlsError = '';
                        this.widgetControlsTarget = widget;
                        const wc = widget.controls || {};

                        console.log('--- OPEN WIDGET CONTROLS START ---');
                        console.log('Widget target:', widget);
                        console.log('Widget controls (wc):', wc);

                        const hasDate = wc.date_start !== undefined || wc.date_end !== undefined;
                        const hasZero = wc.zero_handling !== undefined;

                        this.widgetControlsForm = {
                            title: widget.title || widget.name || '',
                            description: widget.description || '',
                            widget_type: widget.widget_type || '',
                            date_inherit: wc.date_start === undefined && wc.date_end === undefined,
                            date_start: wc.date_start || this.dashboardControls.date_start || '',
                            date_end: wc.date_end || this.dashboardControls.date_end || '',
                            zero_inherit: wc.zero_handling === undefined,
                            zero_handling: wc.zero_handling || this.dashboardControls.zero_handling || 'remove',
                            granularity_inherit: wc.granularity === undefined,
                            granularity: wc.granularity || this.dashboardControls.granularity || 'daily',
                            dependency: wc.dependency || null,
                            channel: wc.channel || '',
                            assets: wc.assets || [],
                            metrics: wc.metrics || [],
                            series_assets: wc.series_assets || {},
                            series_asset_groups: wc.series_asset_groups || {},
                            edge_case_inherit: wc.edge_case_weighted === undefined && wc.edge_case_grouping === undefined,
                            edge_case_weighted: wc.edge_case_weighted !== undefined ? wc.edge_case_weighted : (this.dashboardControls.edge_case_weighted ?? true),
                            edge_case_grouping: wc.edge_case_grouping !== undefined ? wc.edge_case_grouping : (this.dashboardControls.edge_case_grouping || 'none'),
                            max_ratio_inherit: wc.max_ratio === undefined,
                            max_ratio: wc.max_ratio !== undefined ? wc.max_ratio : null,
                            raw_series: [],
                        };

                        if (widget.source_type !== 'kpi') {
                            if (wc.metrics && wc.metrics.length > 0) {
                                const groupedSeries = [];
                                wc.metrics.forEach((m, i) => {
                                    const channel = (wc.series_channels && wc.series_channels[i]) ? wc.series_channels[i] : (wc.channel || '');
                                    const assets = (wc.series_assets && wc.series_assets[i]) ? [...wc.series_assets[i]] : (wc.assets ? [...wc.assets] : []);
                                    
                                    const existing = groupedSeries.find(s => s.channel === channel && JSON.stringify(s.assets) === JSON.stringify(assets));
                                    if (existing) {
                                        if (m) existing.metrics.push(m);
                                    } else {
                                        groupedSeries.push({ channel, metrics: m ? [m] : [], assets });
                                    }
                                });
                                this.widgetControlsForm.raw_series = groupedSeries;
                            }
                            if (this.widgetControlsForm.raw_series.length === 0) {
                                this.widgetControlsForm.raw_series.push({ channel: '', metrics: [], assets: [] });
                            }
                            
                            // Pre-load assets and metrics for channels used in raw series
                            this.widgetControlsForm.raw_series.forEach((series, idx) => {
                                const ch = series.channel;
                                if (ch && !this.allChannelAssets[ch]) {
                                    @this.getAssetsForChannel(ch).then(assets => { this.allChannelAssets = { ...this.allChannelAssets, [ch]: assets }; });
                                }
                                if (ch && !this.allChannelAssetGroups[ch]) {
                                    @this.getAssetGroupsForChannel(ch).then(groups => { this.allChannelAssetGroups = { ...this.allChannelAssetGroups, [ch]: groups }; });
                                }
                                if (ch && !this.allChannelMetrics[ch]) {
                                    @this.getMetricsForChannel(ch).then(metrics => { 
                                        this.allChannelMetrics = { ...this.allChannelMetrics, [ch]: metrics };
                                    });
                                }
                            });
                            
                            // Apply global asset group filtering to raw series
                            this.widgetControlsForm.raw_series.forEach(series => {
                                if (series.channel && series.assets && series.assets.length > 0) {
                                    series.assets = this.ensureValidAssets(null, series.channel, series.assets);
                                }
                            });
                        }

                        const savedMetrics = wc.metrics || [];

                        this.widgetKpiConfig = {};
                        if (widget.source_type === 'kpi' && widget.source_config && widget.source_config.custom_kpi_id) {
                            @this.getKpiConfiguration(widget.source_config.custom_kpi_id).then(config => {
                                this.widgetKpiConfig = config || {};
                                if (this.widgetControlsForm.date_inherit) {
                                    this.widgetControlsForm.date_start = config?.start_date || this.dashboardControls.date_start || '';
                                    this.widgetControlsForm.date_end = config?.end_date || this.dashboardControls.date_end || '';
                                }
                                if (this.widgetControlsForm.edge_case_inherit) {
                                    this.widgetControlsForm.edge_case_weighted = config?.edge_case_weighted !== undefined ? config.edge_case_weighted : (this.dashboardControls.edge_case_weighted !== undefined ? !!this.dashboardControls.edge_case_weighted : true);
                                    this.widgetControlsForm.edge_case_grouping = config?.edge_case_grouping || this.dashboardControls.edge_case_grouping || 'none';
                                }
                                if (this.widgetControlsForm.zero_inherit) {
                                    this.widgetControlsForm.zero_handling = config?.zero_handling ?? this.dashboardControls.zero_handling ?? 'remove';
                                }
                                if (this.widgetControlsForm.granularity_inherit) {
                                    this.widgetControlsForm.granularity = config?.granularity ?? this.dashboardControls.granularity ?? 'daily';
                                }
                                if (this.widgetControlsForm.max_ratio_inherit) {
                                    this.widgetControlsForm.max_ratio = config?.max_ratio !== undefined ? config.max_ratio : null;
                                }
                                
                                // Initialize arrays if undefined
                                if (!this.widgetControlsForm.series_assets.dependent) this.widgetControlsForm.series_assets.dependent = [];
                                if (!this.widgetControlsForm.series_asset_groups.dependent) this.widgetControlsForm.series_asset_groups.dependent = '';
                                if (this.widgetKpiConfig.independent_variables) {
                                    for (let key in this.widgetKpiConfig.independent_variables) {
                                        if (!this.widgetControlsForm.series_assets['independent_' + key]) {
                                            this.widgetControlsForm.series_assets['independent_' + key] = [];
                                        }
                                        if (!this.widgetControlsForm.series_asset_groups['independent_' + key]) {
                                            this.widgetControlsForm.series_asset_groups['independent_' + key] = '';
                                        }
                                    }
                                }

                                // Fetch missing assets for all required channels
                                const channelsToLoad = new Set();
                                if (this.widgetKpiConfig.dependent_channel) channelsToLoad.add(this.widgetKpiConfig.dependent_channel);
                                if (this.widgetKpiConfig.independent_variables) {
                                    for (let key in this.widgetKpiConfig.independent_variables) {
                                        if (this.widgetKpiConfig.independent_variables[key].independent_channel) {
                                            channelsToLoad.add(this.widgetKpiConfig.independent_variables[key].independent_channel);
                                        }
                                    }
                                }
                                channelsToLoad.forEach(ch => {
                                    if (!this.allChannelAssets[ch]) {
                                        @this.getAssetsForChannel(ch).then(assets => {
                                            this.allChannelAssets = { ...this.allChannelAssets, [ch]: assets };
                                        });
                                    }
                                    if (!this.allChannelAssetGroups[ch]) {
                                        @this.getAssetGroupsForChannel(ch).then(groups => {
                                            this.allChannelAssetGroups = { ...this.allChannelAssetGroups, [ch]: groups };
                                        });
                                    }
                                    if (!this.allChannelMetrics[ch]) {
                                        @this.getMetricsForChannel(ch).then(metrics => {
                                            this.allChannelMetrics = { ...this.allChannelMetrics, [ch]: metrics };
                                        });
                                    }
                                });

                                // Apply global asset group filtering to KPI series assets
                                const allSeriesKeys = ['dependent'];
                                if (this.widgetKpiConfig.independent_variables) {
                                    for (let key in this.widgetKpiConfig.independent_variables) {
                                        allSeriesKeys.push('independent_' + key);
                                    }
                                }
                                allSeriesKeys.forEach(sk => {
                                    const ch = sk === 'dependent'
                                        ? this.widgetKpiConfig.dependent_channel
                                        : this.widgetKpiConfig.independent_variables?.[sk.replace('independent_', '')]?.independent_channel;
                                    if (ch && this.widgetControlsForm.series_assets[sk] && this.widgetControlsForm.series_assets[sk].length > 0) {
                                        this.widgetControlsForm.series_assets[sk] = this.ensureValidAssets(sk, ch, this.widgetControlsForm.series_assets[sk]);
                                    }
                                });

                                this.loadWidgetMetrics(savedMetrics);
                            });
                        } else {
                            this.loadWidgetMetrics(savedMetrics);
                        }

                        console.log('Before updateDependenciesAndGranularities:');
                        console.log('wc.dependency:', wc.dependency);
                        console.log('wc.granularity:', wc.granularity);
                        this.updateDependenciesAndGranularities(wc.dependency, wc.granularity);
                        this.showWidgetControls = true;
                    },

                    loadWidgetMetrics(savedMetrics) {
                        const ch = this.widgetControlsForm.channel || this.dashboardControls.channel;
                        if (this.allChannelMetrics[ch]) {
                            this.widgetAssets = this.allChannelAssets[ch] || {};
                            this.widgetMetrics = this.allChannelMetrics[ch] || {};
                            this.restoreWidgetMetrics(savedMetrics);
                        } else if (ch) {
                            @this.getAssetsForChannel(ch).then(assets => {
                                this.allChannelAssets = { ...this.allChannelAssets, [ch]: assets };
                                this.widgetAssets = assets;
                            });
                            @this.getAssetGroupsForChannel(ch).then(groups => {
                                this.allChannelAssetGroups = { ...this.allChannelAssetGroups, [ch]: groups };
                            });
                            @this.getMetricsForChannel(ch).then(metrics => {
                                this.allChannelMetrics = { ...this.allChannelMetrics, [ch]: metrics };
                                this.widgetMetrics = metrics;
                                this.restoreWidgetMetrics(savedMetrics);
                            });
                        } else {
                            this.widgetAssets = {};
                            this.widgetMetrics = {};
                        }
                    },

                    restoreWidgetMetrics(savedMetrics) {
                        if (savedMetrics.length > 0) {
                            this.$nextTick(() => {
                                this.widgetControlsForm.metrics = [...savedMetrics];
                            });
                        }
                    },

                    onWidgetChannelChange() {
                        const ch = this.widgetControlsForm.channel || this.dashboardControls.channel;
                        if (this.allChannelAssets[ch]) {
                            this.widgetAssets = this.allChannelAssets[ch];
                            this.widgetMetrics = this.allChannelMetrics[ch] || {};
                        } else if (ch) {
                            @this.getAssetsForChannel(ch).then(assets => {
                                this.allChannelAssets = { ...this.allChannelAssets, [ch]: assets };
                                this.widgetAssets = assets;
                            });
                            @this.getAssetGroupsForChannel(ch).then(groups => {
                                this.allChannelAssetGroups = { ...this.allChannelAssetGroups, [ch]: groups };
                            });
                            @this.getMetricsForChannel(ch).then(metrics => {
                                this.allChannelMetrics = { ...this.allChannelMetrics, [ch]: metrics };
                                this.widgetMetrics = metrics;
                            });
                        } else {
                            this.widgetAssets = {};
                            this.widgetMetrics = {};
                        }
                    },

                    onWidgetRawChannelChange(index) {
                        const ch = this.widgetControlsForm.raw_series[index].channel;
                        
                        if (index === 0) {
                            this.widgetControlsForm.channel = ch;
                            this.updateDependenciesAndGranularities();
                        }
                        
                        if (ch && !this.allChannelAssets[ch]) {
                            @this.getAssetsForChannel(ch).then(assets => {
                                this.allChannelAssets = { ...this.allChannelAssets, [ch]: assets };
                            });
                        }
                        if (ch && !this.allChannelAssetGroups[ch]) {
                            @this.getAssetGroupsForChannel(ch).then(groups => {
                                this.allChannelAssetGroups = { ...this.allChannelAssetGroups, [ch]: groups };
                            });
                        }
                        if (ch && !this.allChannelMetrics[ch]) {
                            @this.getMetricsForChannel(ch).then(metrics => {
                                this.allChannelMetrics = { ...this.allChannelMetrics, [ch]: metrics };
                            });
                        }
                    },

                    toggleRawAsset(index, id) {
                        const current = this.widgetControlsForm.raw_series[index].assets || [];
                        const strId = String(id);
                        if (current.includes(strId)) {
                            if (current.length <= 1) return;
                            this.widgetControlsForm.raw_series[index].assets = current.filter(a => a !== strId);
                        } else {
                            this.widgetControlsForm.raw_series[index].assets = [...current, strId];
                        }
                    },

                    selectAllRawAssets(index) {
                        const ch = this.widgetControlsForm.raw_series[index].channel;
                        const assets = this.allChannelAssets[ch] || {};
                        let validIds = Object.keys(assets).map(String);
                        const globalGroup = this.dashboardControls?.asset_group;
                        if (globalGroup && this.allChannelAssetGroups[ch]?.[globalGroup]) {
                            const groupAssets = this.allChannelAssetGroups[ch][globalGroup].assets.map(String);
                            validIds = validIds.filter(id => groupAssets.includes(id));
                        }
                        this.widgetControlsForm.raw_series[index].assets = validIds;
                    },

                    selectAllKpiAssets(seriesKey, channel, kpiGroup = null) {
                        const assets = this.allChannelAssets[channel] || {};
                        let validIds = Object.keys(assets).map(String);
                        if (kpiGroup && this.allChannelAssetGroups[channel] && this.allChannelAssetGroups[channel][kpiGroup]) {
                            const groupAssets = this.allChannelAssetGroups[channel][kpiGroup].assets.map(String);
                            validIds = validIds.filter(id => groupAssets.includes(id));
                        }
                        // Apply global group only if no widget-level group is set
                        const widgetGroup = this.widgetControlsForm?.series_asset_groups?.[seriesKey];
                        if (!widgetGroup && !kpiGroup) {
                            const globalGroup = this.dashboardControls?.asset_group;
                            if (globalGroup && this.allChannelAssetGroups[channel]?.[globalGroup]) {
                                const groupAssets = this.allChannelAssetGroups[channel][globalGroup].assets.map(String);
                                validIds = validIds.filter(id => groupAssets.includes(id));
                            }
                        }
                        this.widgetControlsForm.series_assets[seriesKey] = validIds;
                    },

                    toggleKpiAsset(seriesKey, id) {
                        const current = this.widgetControlsForm.series_assets[seriesKey] || [];
                        const strId = String(id);
                        
                        // Enforce single selection mode if kpiSeriesAssetMode === 'single'
                        if (this.kpiSeriesAssetMode === 'single') {
                            this.widgetControlsForm.series_assets[seriesKey] = [strId];
                            return;
                        }
                        
                        if (current.includes(strId)) {
                            if (current.length <= 1) return;
                            this.widgetControlsForm.series_assets[seriesKey] = current.filter(a => a !== strId);
                        } else {
                            this.widgetControlsForm.series_assets[seriesKey] = [...current, strId];
                        }
                    },

                    clearAllKpiAssets(seriesKey) {
                        this.widgetControlsForm.series_assets[seriesKey] = [];
                    },

                    // ─── Asset Group Helpers ───

                    getEffectiveGroup(seriesKey, channel) {
                        // Returns the group that applies: widget-level → KPI-level → global
                        
                        // 1. Widget-level group
                        const widgetGroup = this.widgetControlsForm?.series_asset_groups?.[seriesKey];
                        if (widgetGroup) return widgetGroup;
                        
                        // 2. KPI-level group
                        if (seriesKey === 'dependent' && this.widgetKpiConfig?.dependent_asset_group) {
                            return this.widgetKpiConfig.dependent_asset_group;
                        }
                        if (seriesKey && seriesKey.startsWith('independent_')) {
                            const idx = seriesKey.replace('independent_', '');
                            const kpiGroup = this.widgetKpiConfig?.independent_variables?.[idx]?.independent_asset_group;
                            if (kpiGroup) return kpiGroup;
                        }
                        
                        // 3. Global dashboard group
                        if (this.dashboardControls?.asset_group) {
                            return this.dashboardControls.asset_group;
                        }
                        
                        return '';
                    },

                    isAssetAllowedByGroups(seriesKey, channel, assetId) {
                        const groupId = this.getEffectiveGroup(seriesKey, channel);
                        if (!groupId) return true; // No group filter
                        
                        const groupData = this.allChannelAssetGroups[channel]?.[groupId];
                        if (!groupData) return true; // Group not in this channel, show all
                        
                        return (groupData.assets || []).map(String).includes(String(assetId));
                    },

                    ensureValidAssets(seriesKey, channel, selectedAssets) {
                        const groupId = this.getEffectiveGroup(seriesKey, channel);
                        if (!groupId) return selectedAssets;
                        
                        const groupData = this.allChannelAssetGroups[channel]?.[groupId];
                        if (!groupData) return selectedAssets;
                        
                        const allowedAssets = groupData.assets.map(String);
                        const validAssets = (selectedAssets || []).filter(a => allowedAssets.includes(String(a)));
                        
                        if (validAssets.length > 0) return validAssets;
                        
                        // No valid assets, auto-select first available
                        if (allowedAssets.length > 0) return [allowedAssets[0]];
                        return [];
                    },

                    resetWidgetControls() {
                        this.widgetControlsForm = {
                            date_inherit: true,
                            date_start: '',
                            date_end: '',
                            zero_inherit: true,
                            zero_handling: 'remove',
                            channel: '',
                            assets: [],
                            granularity: 'daily',
                            dependency: null,
                            metrics: [],
                            series_assets: {},
                            series_asset_groups: {},
                            edge_case_inherit: true,
                            edge_case_weighted: true,
                            edge_case_grouping: 'none',
                            max_ratio_inherit: true,
                            max_ratio: null,
                            widget_type: this.widgetControlsTarget?.widget_type || '',
                        };
                    },

                    confirmWidgetControls() {
                        const c = this.widgetControlsForm;
                        
                        let cDash = this.dashboardControls;
                        let dateAdjusted = false;
                        if (!c.date_inherit) {
                            if (c.date_start && cDash.date_start && c.date_start < cDash.date_start) {
                                c.date_start = cDash.date_start;
                                dateAdjusted = true;
                            }
                            let maxEnd = cDash.date_end || '{{ date('Y-m-d', strtotime('-1 day')) }}';
                            if (c.date_end && c.date_end > maxEnd) {
                                c.date_end = maxEnd;
                                dateAdjusted = true;
                            }
                        }
                        if (dateAdjusted) {
                            alert("Warning: The widget date range exceeded the dashboard limits and was adjusted to comply.");
                        }

                        const payload = {};

                        if (!c.title || c.title.trim() === '') {
                            this.widgetControlsError = "Please enter a title for the widget.";
                            return;
                        }

                        if (!c.date_inherit) {
                            payload.date_start = c.date_start;
                            payload.date_end = c.date_end;
                        }
                        if (!c.zero_inherit) {
                            payload.zero_handling = c.zero_handling;
                        }

                        if (!c.granularity_inherit) {
                            payload.granularity = c.granularity;
                        }
                        if (c.dependency) {
                            payload.dependency = c.dependency;
                        }

                        if (!c.edge_case_inherit) {
                            payload.edge_case_weighted = !!c.edge_case_weighted;
                            payload.edge_case_grouping = c.edge_case_grouping || 'none';
                        }

                        if (!c.max_ratio_inherit) {
                            payload.max_ratio = c.max_ratio !== '' && c.max_ratio !== null ? parseFloat(c.max_ratio) : null;
                        }

                        this.widgetControlsError = '';
                        if (this.widgetControlsTarget.source_type !== 'kpi') {
                            if (!c.raw_series || c.raw_series.length === 0) {
                                this.widgetControlsError = "Please add at least one series before saving.";
                                return;
                            }
                            
                            const missingChannel = c.raw_series.some(s => !s.channel || s.channel.trim() === '');
                            if (missingChannel) {
                                this.widgetControlsError = "Please select a channel for all series before saving.";
                                return;
                            }

                            payload.channel = '';
                            payload.assets = [];
                            payload.metrics = [];
                            payload.series_assets = {};
                            payload.series_channels = {};
                            
                            let validIdx = 0;
                            c.raw_series.forEach((s) => {
                                const metricsToSave = (Array.isArray(s.metrics) && s.metrics.length > 0) ? s.metrics : [''];
                                
                                metricsToSave.forEach(m => {
                                    payload.metrics.push(m);
                                });
                                
                                let channelAssets = this.allChannelAssets[s.channel] || {};
                                let validAssets = [...(s.assets || [])].filter(id => {
                                    return channelAssets[id] !== undefined;
                                });
                                
                                payload.series_assets[validIdx] = validAssets;
                                payload.series_channels[validIdx] = s.channel || '';
                                validIdx++;
                            });
                            if (payload.series_channels['0']) {
                                payload.channel = payload.series_channels['0'];
                            }
                        } else {
                            payload.channel = c.channel;
                            payload.assets = c.assets;
                            payload.metrics = c.metrics;
                            payload.series_assets = c.series_assets;
                            payload.series_asset_groups = c.series_asset_groups;
                        }

                        @this.saveWidgetControls(this.widgetControlsTarget.id, payload, c.title.trim(), c.description ? c.description.trim() : null);

                        // Handle widget type change
                        const newType = c.widget_type;
                        const oldType = this.widgetControlsTarget.widget_type;
                        if (newType && newType !== oldType) {
                            @this.changeWidgetType(this.widgetControlsTarget.id, newType);
                        }

                        this.showWidgetControls = false;
                        this.reloadGrid();

                        // Update local widget data
                        const idx = this.widgets.findIndex(w => w.id === this.widgetControlsTarget.id);
                        if (idx !== -1) {
                            this.widgets[idx].controls = payload;
                            this.widgets[idx].title = c.title.trim();
                            this.widgets[idx].description = c.description ? c.description.trim() : null;
                            this.widgets[idx].widget_type = c.widget_type;
                        }
                    },

                    // ─── Add Widget ──
                    openAddWidgetModal() {
                        this.addWidgetForm = {source_type: '', custom_kpi_id: '', widget_type: '', name: ''};
                        this.showAddWidgetModal = true;
                    },

                    canAddWidget() {
                        if (!this.addWidgetForm.source_type) return false;
                        if (!this.addWidgetForm.widget_type) return false;
                        if (this.addWidgetForm.source_type === 'kpi' && !this.addWidgetForm.custom_kpi_id) return false;
                        return true;
                    },

                    confirmAddWidget() {
                        if (!this.canAddWidget()) return;

                        const form = this.addWidgetForm;
                        const data = {
                            name: form.name || form.widget_type,
                            title: form.name || form.widget_type,
                            source_type: form.source_type,
                            custom_kpi_id: form.source_type === 'kpi' ? form.custom_kpi_id : null,
                            source_config: form.source_type === 'kpi' ? {custom_kpi_id: form.custom_kpi_id} : {},
                            widget_type: form.widget_type,
                            controls: {},
                            grid_x: null,
                            grid_y: null,
                            grid_w: 4,
                            grid_h: 3,
                        };

                    @this.addWidget(data).then(widget => {
                        widget._isNew = true;
                        widget.grid_x = null;
                        widget.grid_y = null;
                        this.widgets.push(widget);
                        this.showAddWidgetModal = false;
                        
                        this.$nextTick(() => {
                            setTimeout(() => {
                                @this.saveLayout(this.getLayout());
                                const el = document.querySelector(`[gs-id="${widget.id}"]`);
                                if (el) {
                                    el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                                }
                            }, 500);
                        });
                    })
                        ;
                    },

                    // ─── Share ──
                    openShareDialog() {
                    @this.getProjectCollaborators().then(users => {
                        this.collaborators = users || [];
                    })
                        ;
                    @this.getSharedUserIds().then(ids => {
                        this.sharedUsers = this.collaborators.filter(u => (ids || []).includes(u.id));
                    })
                        ;
                        this.showShareDialog = true;
                    },

                    isShared(userId) {
                        return this.sharedUsers.some(u => u.id === userId);
                    },

                    addSharedUser() {
                        const userId = parseInt(this.shareUserId);
                        if (!userId) return;
                        const user = this.collaborators.find(u => u.id === userId);
                        if (!user) return;

                    @this.shareWithUser(userId).then(() => {
                        this.sharedUsers.push(user);
                        this.shareUserId = '';
                    })
                        ;
                    },

                    unshareUser(userId) {
                    @this.unshareUser(userId).then(() => {
                        this.sharedUsers = this.sharedUsers.filter(u => u.id !== userId);
                    })
                        ;
                    },

                    togglePublic() {
                    @this.togglePublic().then(() => {
                        this.isPublic = !this.isPublic;
                    })
                        ;
                    },

                    configureWidget(id) {
                        const widget = this.widgets.find(w => w.id === id);
                        if (widget) this.openWidgetControls(widget);
                    },

                    deleteWidget(id) {
                        if (confirm('Remove this widget?')) {
                        @this.deleteWidget(id).then(() => {
                            const el = document.querySelector(`[gs-id="${id}"]`);
                            if (el && this.grid) this.grid.removeWidget(el, false);
                            this.widgets = this.widgets.filter(w => w.id !== id);
                        })
                            ;
                        }
                    },

                    duplicateWidget(id) {
                    @this.duplicateWidget(id).then(widget => {
                        widget._isNew = true;
                        widget.grid_x = null;
                        widget.grid_y = null;
                        this.widgets.push(widget);
                        
                        this.$nextTick(() => {
                            setTimeout(() => {
                                @this.saveLayout(this.getLayout());
                                const el = document.querySelector(`[gs-id="${widget.id}"]`);
                                if (el) {
                                    el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                                }
                            }, 500);
                        });
                    })
                        ;
                    }
                };
            });
        });
    </script>
</x-filament-panels::page>
