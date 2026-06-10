<x-filament-panels::page>
    <link rel="stylesheet" href="{{ asset('css/dashboard-builder.css') }}"/>

    <div x-data="dashboardBuilder()" class="space-y-4">
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
                <x-filament::button x-on:click="$wire.saveLayout(gridLayout)" color="primary" icon="heroicon-o-check">
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
                            <span class="block text-xs text-gray-500 dark:text-gray-400 mt-1">Add Widget</span>
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
                            <div class="grid-stack-item"
                                 x-init="$el.setAttribute('gs-id', widget.id); $el.setAttribute('gs-x', widget.grid_x); $el.setAttribute('gs-y', widget.grid_y); $el.setAttribute('gs-w', widget.grid_w); $el.setAttribute('gs-h', widget.grid_h); $el.setAttribute('gs-min-w', 2); $el.setAttribute('gs-min-h', 2);">
                                <div class="grid-stack-item-content rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-sm relative">
                                    {{-- Widget Header --}}
                                    <div
                                        class="flex items-center justify-between px-3 py-2 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 rounded-t-lg gap-4">
                                        <div class="flex items-center gap-2 min-w-0">
                                            <span x-show="widgetHasCustomControls(widget)"
                                                  class="inline-block w-2 h-2 rounded-full bg-blue-400 flex-shrink-0"
                                                  title="Has custom controls"></span>
                                            <span x-show="!widgetHasCustomControls(widget)"
                                                  class="inline-block w-2 h-2 rounded-full bg-green-400 flex-shrink-0"
                                                  title="Inheriting dashboard controls"></span>
                                            <span class="text-sm font-medium text-gray-900 dark:text-white truncate"
                                                  x-text="widget.title || widget.name"></span>
                                        </div>
                                        <div class="flex items-center gap-1 flex-shrink-0">
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
                                    <div class="p-4 flex items-center justify-center h-[calc(100%-2.5rem)]">
                                        <div class="text-center">
                                            <x-filament::icon :name="'heroicon-o-chart-bar'"
                                                              class="w-10 h-10 mx-auto text-gray-300 dark:text-gray-600"/>
                                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-2">
                                                <span x-text="widget.widget_type"></span>
                                                <template x-if="widget.source_type === 'kpi'">
                                                    <span> &middot; KPI</span>
                                                </template>
                                                <template x-if="widget.source_type === 'metric'">
                                                    <span> &middot; Metric</span>
                                                </template>
                                                <template x-if="widget.source_type === 'entity'">
                                                    <span> &middot; Entity</span>
                                                </template>
                                            </p>
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
                            <p class="text-gray-500 dark:text-gray-400 text-lg">No widgets yet</p>
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
                class="relative bg-white dark:bg-gray-900 rounded-2xl shadow-2xl max-w-xl w-full mx-4 max-h-[90vh] overflow-y-auto p-6 space-y-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Dashboard Controls</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">These defaults apply to all widgets. Widgets can
                    override individually.</p>

                {{-- Date Range --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Date Range</label>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <span class="text-xs text-gray-500 dark:text-gray-400">Start</span>
                            <input type="date" x-model="dashboardControls.date_start"
                                   class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 text-sm"/>
                        </div>
                        <div>
                            <span class="text-xs text-gray-500 dark:text-gray-400">End</span>
                            <input type="date" x-model="dashboardControls.date_end" max="{{ date('Y-m-d') }}"
                                   class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 text-sm"/>
                        </div>
                    </div>
                </div>

                {{-- Zero Handling --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Zero / Missing
                        Data</label>
                    <select x-model="dashboardControls.zero_handling"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 text-sm">
                        <option value="remove">Remove zeros from results</option>
                        <option value="keep">Keep zeros in results</option>
                        <option value="trim">Trim leading/trailing zeros</option>
                    </select>
                </div>

                {{-- Series: Channel + Asset + Granularity --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Series
                        Defaults</label>
                    <div class="space-y-3 p-3 rounded-lg border border-gray-200 dark:border-gray-700">
                        {{-- Channel --}}
                        <div>
                            <span class="text-xs text-gray-500 dark:text-gray-400">Channel</span>
                            <select x-model="dashboardControls.channel" x-on:change="onDashboardChannelChange()"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 text-sm">
                                <option value="">Auto-detect</option>
                                <template x-for="(label, key) in channels" :key="key">
                                    <option :value="key" x-text="label"></option>
                                </template>
                            </select>
                        </div>

                        {{-- Asset Mode Toggle --}}
                        <div>
                            <span class="text-xs text-gray-500 dark:text-gray-400">Asset Mode</span>
                            <div class="flex gap-2 mt-1">
                                <button class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors"
                                        :class="dashboardControls.asset_mode === 'single'
                                            ? 'bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300 border border-primary-300 dark:border-primary-700'
                                            : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-gray-700'"
                                        x-on:click="dashboardControls.asset_mode = 'single'; dashboardControls.assets = []">
                                    Single Asset
                                </button>
                                <button class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors"
                                        :class="dashboardControls.asset_mode === 'multiple'
                                            ? 'bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300 border border-primary-300 dark:border-primary-700'
                                            : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-gray-700'"
                                        x-on:click="dashboardControls.asset_mode = 'multiple'; dashboardControls.asset = ''">
                                    Multiple Assets
                                </button>
                            </div>
                        </div>

                        {{-- Single Asset --}}
                        <template x-if="dashboardControls.asset_mode === 'single'">
                            <div>
                                <span class="text-xs text-gray-500 dark:text-gray-400">Asset</span>
                                <select x-model="dashboardControls.asset"
                                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 text-sm">
                                    <option value="">All assets</option>
                                    <template x-for="(name, id) in dashboardAssets" :key="id">
                                        <option :value="id" x-text="name"></option>
                                    </template>
                                </select>
                            </div>
                        </template>

                        {{-- Multiple Assets --}}
                        <template x-if="dashboardControls.asset_mode === 'multiple'">
                            <div>
                                <span
                                    class="text-xs text-gray-500 dark:text-gray-400">Assets (select all that apply)</span>
                                <div
                                    class="mt-1 max-h-32 overflow-y-auto space-y-1 border border-gray-200 dark:border-gray-700 rounded-lg p-2">
                                    <template x-for="(name, id) in dashboardAssets" :key="id">
                                        <label
                                            class="flex items-center gap-2 text-sm cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800 px-2 py-1 rounded">
                                            <input type="checkbox" :value="id" x-model="dashboardControls.assets"
                                                   class="rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500"/>
                                            <span x-text="name" class="text-gray-700 dark:text-gray-300"></span>
                                        </label>
                                    </template>
                                </div>
                                <template x-if="Object.keys(dashboardAssets).length === 0">
                                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Select a channel first to
                                        see available assets.</p>
                                </template>
                            </div>
                        </template>

                        {{-- Granularity --}}
                        <div>
                            <span class="text-xs text-gray-500 dark:text-gray-400">Granularity</span>
                            <select x-model="dashboardControls.granularity"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 text-sm">
                                <option value="daily">Daily</option>
                                <option value="weekly">Weekly</option>
                                <option value="monthly">Monthly</option>
                            </select>
                        </div>
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
        <div x-show="showWidgetControls" x-cloak class="fixed inset-0 z-50 flex items-center justify-center">
            <div class="absolute inset-0 bg-black/50" x-on:click="showWidgetControls = false"></div>
            <div
                class="relative bg-white dark:bg-gray-900 rounded-2xl shadow-2xl max-w-xl w-full mx-4 max-h-[90vh] overflow-y-auto p-6 space-y-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white"
                    x-text="'Configure: ' + (widgetControlsTarget.title || widgetControlsTarget.name)"></h2>

                {{-- Info line --}}
                <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                    <span class="inline-block w-2 h-2 rounded-full bg-green-400"
                          x-show="!widgetHasCustomControls(widgetControlsTarget)"></span>
                    <span class="inline-block w-2 h-2 rounded-full bg-blue-400"
                          x-show="widgetHasCustomControls(widgetControlsTarget)"></span>
                    <span x-show="!widgetHasCustomControls(widgetControlsTarget)">All controls inherited from dashboard defaults.</span>
                    <span
                        x-show="widgetHasCustomControls(widgetControlsTarget)">Some controls have custom overrides.</span>
                    <button class="ml-auto text-xs text-primary-600 dark:text-primary-400 hover:underline"
                            x-on:click="resetWidgetControls()">Reset all to inherit
                    </button>
                </div>

                {{-- Date Range --}}
                <div class="control-group" :class="widgetControlsForm.date_inherit ? 'is-inherited' : 'has-custom'">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Date Range</span>
                        <label class="inherit-toggle">
                            <input type="checkbox" x-model="widgetControlsForm.date_inherit"/>
                            <span class="slider"></span>
                            <span class="text-xs"
                                  x-text="widgetControlsForm.date_inherit ? 'Inherit' : 'Custom'"></span>
                        </label>
                    </div>
                    <template x-if="widgetControlsForm.date_inherit">
                        <div class="inherited-value"
                             x-text="'Inherited: ' + (dashboardControls.date_start || '—') + ' → ' + (dashboardControls.date_end || '—')"></div>
                    </template>
                    <template x-if="!widgetControlsForm.date_inherit">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <span class="text-xs text-gray-500 dark:text-gray-400">Start</span>
                                <input type="date" x-model="widgetControlsForm.date_start"
                                       class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 text-sm"/>
                            </div>
                            <div>
                                <span class="text-xs text-gray-500 dark:text-gray-400">End</span>
                                <input type="date" x-model="widgetControlsForm.date_end" max="{{ date('Y-m-d') }}"
                                       class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 text-sm"/>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- Zero Handling --}}
                <div class="control-group" :class="widgetControlsForm.zero_inherit ? 'is-inherited' : 'has-custom'">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Zero / Missing Data</span>
                        <label class="inherit-toggle">
                            <input type="checkbox" x-model="widgetControlsForm.zero_inherit"/>
                            <span class="slider"></span>
                            <span class="text-xs"
                                  x-text="widgetControlsForm.zero_inherit ? 'Inherit' : 'Custom'"></span>
                        </label>
                    </div>
                    <template x-if="widgetControlsForm.zero_inherit">
                        <div class="inherited-value"
                             x-text="'Inherited: ' + (inheritedControlLabel('zero_handling', dashboardControls.zero_handling) || 'Remove zeros')"></div>
                    </template>
                    <template x-if="!widgetControlsForm.zero_inherit">
                        <select x-model="widgetControlsForm.zero_handling"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 text-sm">
                            <option value="remove">Remove zeros from results</option>
                            <option value="keep">Keep zeros in results</option>
                            <option value="trim">Trim leading/trailing zeros</option>
                        </select>
                    </template>
                </div>

                {{-- Series: Channel + Asset + Granularity --}}
                <div class="control-group" :class="widgetControlsForm.series_inherit ? 'is-inherited' : 'has-custom'">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Series</span>
                        <label class="inherit-toggle">
                            <input type="checkbox" x-model="widgetControlsForm.series_inherit"/>
                            <span class="slider"></span>
                            <span class="text-xs"
                                  x-text="widgetControlsForm.series_inherit ? 'Inherit' : 'Custom'"></span>
                        </label>
                    </div>

                    <template x-if="widgetControlsForm.series_inherit">
                        <div class="space-y-1">
                            <div class="inherited-value"
                                 x-text="'Channel: ' + (channels[dashboardControls.channel] || dashboardControls.channel || 'Auto-detect')"></div>
                            <div class="inherited-value"
                                 x-text="'Asset: ' + (dashboardControls.asset || (dashboardControls.assets && dashboardControls.assets.length > 0 ? dashboardControls.assets.length + ' assets' : 'All'))"></div>
                            <div class="inherited-value"
                                 x-text="'Granularity: ' + inheritedControlLabel('granularity', dashboardControls.granularity)"></div>
                        </div>
                    </template>
                    <template x-if="!widgetControlsForm.series_inherit">
                        <div class="space-y-3">
                            <div>
                                <span class="text-xs text-gray-500 dark:text-gray-400">Channel</span>
                                <select x-model="widgetControlsForm.channel" x-on:change="onWidgetChannelChange()"
                                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 text-sm">
                                    <option value="">Auto-detect</option>
                                    <template x-for="(label, key) in channels" :key="key">
                                        <option :value="key" x-text="label"></option>
                                    </template>
                                </select>
                            </div>
                            <div>
                                <span class="text-xs text-gray-500 dark:text-gray-400">Asset Mode</span>
                                <div class="flex gap-2 mt-1">
                                    <button class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors"
                                            :class="widgetControlsForm.asset_mode === 'single'
                                                ? 'bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300 border border-primary-300 dark:border-primary-700'
                                                : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-gray-700'"
                                            x-on:click="widgetControlsForm.asset_mode = 'single'; widgetControlsForm.assets = []">
                                        Single Asset
                                    </button>
                                    <button class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors"
                                            :class="widgetControlsForm.asset_mode === 'multiple'
                                                ? 'bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300 border border-primary-300 dark:border-primary-700'
                                                : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-gray-700'"
                                            x-on:click="widgetControlsForm.asset_mode = 'multiple'; widgetControlsForm.asset = ''">
                                        Multiple Assets
                                    </button>
                                </div>
                            </div>
                            <template x-if="widgetControlsForm.asset_mode === 'single'">
                                <div>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">Asset</span>
                                    <select x-model="widgetControlsForm.asset"
                                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 text-sm">
                                        <option value="">All assets</option>
                                        <template x-for="(name, id) in widgetAssets" :key="id">
                                            <option :value="id" x-text="name"></option>
                                        </template>
                                    </select>
                                </div>
                            </template>
                            <template x-if="widgetControlsForm.asset_mode === 'multiple'">
                                <div>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">Assets</span>
                                    <div
                                        class="mt-1 max-h-32 overflow-y-auto space-y-1 border border-gray-200 dark:border-gray-700 rounded-lg p-2">
                                        <template x-for="(name, id) in widgetAssets" :key="id">
                                            <label
                                                class="flex items-center gap-2 text-sm cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800 px-2 py-1 rounded">
                                                <input type="checkbox" :value="id" x-model="widgetControlsForm.assets"
                                                       class="rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500"/>
                                                <span x-text="name" class="text-gray-700 dark:text-gray-300"></span>
                                            </label>
                                        </template>
                                    </div>
                                    <template x-if="Object.keys(widgetAssets).length === 0">
                                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Select a channel first
                                            to see available assets.</p>
                                    </template>
                                </div>
                            </template>
                            <div>
                                <span class="text-xs text-gray-500 dark:text-gray-400">Granularity</span>
                                <select x-model="widgetControlsForm.granularity"
                                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 text-sm">
                                    <option value="daily">Daily</option>
                                    <option value="weekly">Weekly</option>
                                    <option value="monthly">Monthly</option>
                                </select>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="flex justify-end gap-3 pt-2 border-t border-gray-200 dark:border-gray-700">
                    <button
                        class="px-4 py-2 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 text-sm"
                        x-on:click="showWidgetControls = false">Cancel
                    </button>
                    <button class="px-4 py-2 rounded-lg bg-primary-600 text-white hover:bg-primary-500 text-sm"
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
            <div class="relative bg-white dark:bg-gray-900 rounded-2xl shadow-2xl max-w-lg w-full mx-4 p-6 space-y-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Add Widget</h2>

                {{-- Step 1: Source Type --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Data Source</label>
                    <div class="grid grid-cols-3 gap-3">
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

                {{-- Step 2: KPI (if kpi source) --}}
                <template x-if="addWidgetForm.source_type === 'kpi'">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Select
                            KPI</label>
                        <select x-model="addWidgetForm.custom_kpi_id"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100">
                            <option value="">Choose a KPI...</option>
                            <template x-for="(name, id) in kpis" :key="id">
                                <option :value="id" x-text="name"></option>
                            </template>
                        </select>
                    </div>
                </template>

                {{-- Step 3: Widget Type --}}
                <template x-if="addWidgetForm.source_type">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Widget
                            Type</label>
                        <div class="grid grid-cols-2 gap-2">
                            <template x-for="(label, type) in availableWidgetTypes" :key="type">
                                <button class="p-2 rounded-lg border text-center text-xs transition-colors"
                                        :class="addWidgetForm.widget_type === type
                                            ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-300'
                                            : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'"
                                        x-on:click="addWidgetForm.widget_type = type">
                                    <span x-text="label"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                </template>

                {{-- Step 4: Name --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Widget Name</label>
                    <input type="text" x-model="addWidgetForm.name"
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100"
                           placeholder="My Widget"/>
                </div>

                <div class="flex justify-end gap-3">
                    <button
                        class="px-4 py-2 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800"
                        x-on:click="showAddWidgetModal = false">Cancel
                    </button>
                    <button
                        class="px-4 py-2 rounded-lg bg-primary-600 text-white hover:bg-primary-500 disabled:opacity-50"
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
            <div class="relative bg-white dark:bg-gray-900 rounded-2xl shadow-2xl max-w-md w-full mx-4 p-6 space-y-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Share Dashboard</h2>

                {{-- Public Toggle --}}
                <div
                    class="flex items-center justify-between p-3 rounded-lg border border-gray-200 dark:border-gray-700">
                    <div>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">Public access</p>
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
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Shared with</label>
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
                            <p class="text-sm text-gray-400 dark:text-gray-500 text-center py-4">No users shared yet</p>
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
                            <option value="">Select a user...</option>
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

        <script>
            function dashboardBuilder() {
                return {
                    // ─── Grid State ───
                    widgets: @json($this->widgets ?? []),
                    gridLayout: @json($this->gridState ?? []),
                    grid: null,

                    // ─── Channels & Assets ───
                    channels: @json($this->getActiveChannels()),
                    allChannelAssets: {},
                    dashboardAssets: {},

                    // ─── Dashboard Controls ──
                    showDashboardControls: false,
                    showWidgetControls: false,
                    dashboardControls: @json($this->getDashboardControls()),

                    // ─── Widget Controls ──
                    widgetControlsTarget: {},
                    widgetControlsForm: {
                        date_inherit: true,
                        date_start: '',
                        date_end: '',
                        zero_inherit: true,
                        zero_handling: 'remove',
                        series_inherit: true,
                        channel: '',
                        asset_mode: 'single',
                        asset: '',
                        assets: [],
                        granularity: 'daily',
                    },
                    widgetAssets: {},

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
                    get availableWidgetTypes() {
                        if (!this.addWidgetForm.source_type) return {};
                        return @json($this->getAvailableWidgetTypes());
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
                            this.allChannelAssets[ch] = assets;
                            if (ch === this.dashboardControls.channel) {
                                this.dashboardAssets = assets;
                            }
                        })
                            ;
                        });
                    },

                    // ─── Grid ──
                    initGrid() {
                        const container = document.getElementById('grid-stack');
                        if (!container) return;

                        this.grid = GridStack.init({
                            column: 12,
                            cellHeight: 100,
                            margin: 12,
                            float: true,
                            acceptWidgets: true,
                            removable: false,
                            resizable: {handles: 'se'},
                            draggable: {handle: '.grid-stack-item-content .rounded-t-lg'},
                        });

                        this.grid.on('change', (event, items) => {
                            this.gridLayout = items.map(item => ({
                                id: item.id || parseInt(item.getAttribute?.('gs-id')) || 0,
                                x: item.x, y: item.y, w: item.w, h: item.h,
                            }));
                        });
                    },

                    reloadGrid() {
                        if (this.grid) {
                            this.grid.destroy(false);
                            this.grid = null;
                        }
                        this.$nextTick(() => this.initGrid());
                    },

                    syncGridWithWidgets() {
                        if (this.grid && this.widgets && this.widgets.length > 0) {
                            this.grid.load(this.widgets.map(w => ({
                                id: w.id,
                                x: w.grid_x,
                                y: w.grid_y,
                                w: w.grid_w,
                                h: w.grid_h,
                                'gs-id': String(w.id),
                            })));
                        }
                    },

                    // ─── Helpers ──
                    inheritedControlLabel(key, value) {
                        const labels = {
                            zero_handling: {remove: 'Remove zeros', keep: 'Keep zeros', trim: 'Trim zeros'},
                            granularity: {daily: 'Daily', weekly: 'Weekly', monthly: 'Monthly'},
                        };
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
                        this.onDashboardChannelChange();
                    },

                    onDashboardChannelChange() {
                        const ch = this.dashboardControls.channel;
                        if (ch && this.allChannelAssets[ch]) {
                            this.dashboardAssets = this.allChannelAssets[ch];
                        } else if (ch) {
                        @this.getAssetsForChannel(ch).then(assets => {
                            this.allChannelAssets[ch] = assets;
                            this.dashboardAssets = assets;
                        })
                            ;
                        } else {
                            this.dashboardAssets = {};
                        }
                    },

                    confirmDashboardControls() {
                        const c = this.dashboardControls;
                        const payload = {
                            date_start: c.date_start || '',
                            date_end: c.date_end || '',
                            zero_handling: c.zero_handling || 'remove',
                            channel: c.channel || '',
                            asset_mode: c.asset_mode || 'single',
                            asset: c.asset_mode === 'single' ? (c.asset || '') : '',
                            assets: c.asset_mode === 'multiple' ? (c.assets || []) : [],
                            granularity: c.granularity || 'daily',
                        };
                    @this.saveDashboardControls(payload)
                        ;
                        this.showDashboardControls = false;
                    },

                    // ─── Widget Controls ──
                    openWidgetControls(widget) {
                        this.widgetControlsTarget = widget;
                        const wc = widget.controls || {};

                        const hasDate = wc.date_start !== undefined || wc.date_end !== undefined;
                        const hasZero = wc.zero_handling !== undefined;
                        const hasSeries = wc.channel !== undefined || wc.asset !== undefined || wc.assets !== undefined || wc.granularity !== undefined;

                        this.widgetControlsForm = {
                            date_inherit: !hasDate,
                            date_start: wc.date_start || '',
                            date_end: wc.date_end || '',
                            zero_inherit: !hasZero,
                            zero_handling: wc.zero_handling || 'remove',
                            series_inherit: !hasSeries,
                            channel: wc.channel || '',
                            asset_mode: wc.asset_mode || 'single',
                            asset: wc.asset || '',
                            assets: wc.assets || [],
                            granularity: wc.granularity || 'daily',
                        };

                        this.onWidgetChannelChange();
                        this.showWidgetControls = true;
                    },

                    onWidgetChannelChange() {
                        const ch = this.widgetControlsForm.channel || this.dashboardControls.channel;
                        if (this.allChannelAssets[ch]) {
                            this.widgetAssets = this.allChannelAssets[ch];
                        } else if (ch) {
                        @this.getAssetsForChannel(ch).then(assets => {
                            this.allChannelAssets[ch] = assets;
                            this.widgetAssets = assets;
                        })
                            ;
                        } else {
                            this.widgetAssets = {};
                        }
                    },

                    resetWidgetControls() {
                        this.widgetControlsForm = {
                            date_inherit: true,
                            date_start: '',
                            date_end: '',
                            zero_inherit: true,
                            zero_handling: 'remove',
                            series_inherit: true,
                            channel: '',
                            asset_mode: 'single',
                            asset: '',
                            assets: [],
                            granularity: 'daily',
                        };
                    },

                    confirmWidgetControls() {
                        const f = this.widgetControlsForm;
                        const payload = {};

                        if (!f.date_inherit) {
                            payload.date_start = f.date_start || '';
                            payload.date_end = f.date_end || '';
                        }
                        if (!f.zero_inherit) {
                            payload.zero_handling = f.zero_handling || 'remove';
                        }
                        if (!f.series_inherit) {
                            payload.channel = f.channel || '';
                            payload.asset_mode = f.asset_mode || 'single';
                            if (f.asset_mode === 'single') {
                                payload.asset = f.asset || '';
                            } else {
                                payload.assets = f.assets || [];
                            }
                            payload.granularity = f.granularity || 'daily';
                        }

                    @this.saveWidgetControls(this.widgetControlsTarget.id, payload)
                        ;
                        this.showWidgetControls = false;

                        // Update local widget data
                        const idx = this.widgets.findIndex(w => w.id === this.widgetControlsTarget.id);
                        if (idx !== -1) {
                            this.widgets[idx].controls = payload;
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
                            source_config: form.source_type === 'kpi' ? {custom_kpi_id: form.custom_kpi_id} : {},
                            widget_type: form.widget_type,
                            controls: {},
                            grid_x: 0,
                            grid_y: 0,
                            grid_w: 4,
                            grid_h: 3,
                        };

                    @this.addWidget(data).then(widget => {
                        this.widgets.push(widget);
                        this.showAddWidgetModal = false;
                        this.$nextTick(() => this.syncGridWithWidgets());
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
                            this.widgets = this.widgets.filter(w => w.id !== id);
                            this.$nextTick(() => this.syncGridWithWidgets());
                        })
                            ;
                        }
                    },

                    duplicateWidget(id) {
                    @this.duplicateWidget(id).then(widget => {
                        this.widgets.push(widget);
                        this.$nextTick(() => this.syncGridWithWidgets());
                    })
                        ;
                    },
                };
            }
        </script>
    @endpush
</x-filament-panels::page>
