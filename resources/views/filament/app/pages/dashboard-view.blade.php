<x-filament-panels::page>
    <link rel="stylesheet" href="{{ asset('css/dashboard-builder.css') }}" />

    <div x-data="dashboardView()" x-init="init()" id="dashboard-view-container" class="space-y-4" @open-widget-settings.window="openWidgetSettings($event.detail.widgetId, $event.detail.controls, $event.detail.seriesOptions, $event.detail.variables, $event.detail.granularityOnTheGo)">
        {{-- Header --}}
        <div class="flex items-center justify-between gap-4 rounded-xl bg-gray-50 dark:bg-gray-900 p-4">
            <div>
                <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ $this->dashboard->name }}</h1>
                @if ($this->dashboard->description)
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $this->dashboard->description }}</p>
                @endif
            </div>
            <div class="flex items-center gap-2">
                <span class="text-xs text-gray-400 dark:text-gray-500">
                    <span x-text="loadedCount"></span>/<span x-text="totalCount"></span> loaded
                    <button x-on:click="refreshAll()" class="ml-2 text-primary-500 hover:underline">Refresh all</button>
                </span>
            </div>
        </div>

        {{-- Dashboard Controls (on-the-go) --}}
        @php $dc = $this->dashboard->controls ?? []; @endphp
        <div class="flex flex-wrap items-center gap-3 text-xs">
            <span class="text-xs text-gray-500 dark:text-gray-400 font-medium">Date range:</span>
            <input type="date" x-model="dashboardOverrides.date_start"
                   x-on:change.debounce.500ms="applyDateRange()"
                   class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 px-2 py-1.5 text-xs"
                   :max="dashboardOverrides.date_end || '{{ date('Y-m-d') }}'">
            <span class="text-gray-400">→</span>
            <input type="date" x-model="dashboardOverrides.date_end"
                   x-on:change.debounce.500ms="applyDateRange()"
                   class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 px-2 py-1.5 text-xs"
                   :min="dashboardOverrides.date_start || ''" max="{{ date('Y-m-d') }}">
            <span class="text-xs text-gray-400 dark:text-gray-500 ml-2">
                <span x-text="loadedCount"></span>/<span x-text="totalCount"></span> loaded
                <button x-on:click="refreshAll()" class="ml-2 text-primary-500 hover:underline">Refresh all</button>
            </span>
        </div>

        {{-- Grid --}}
        <div id="view-grid-stack" class="grid-stack">
            @foreach ($this->widgets as $widget)
                <div class="grid-stack-item"
                     gs-id="{{ $widget['id'] }}"
                     gs-x="{{ $widget['grid_x'] }}"
                     gs-y="{{ $widget['grid_y'] }}"
                     gs-w="{{ $widget['grid_w'] }}"
                     gs-h="{{ $widget['grid_h'] }}">
                    <div class="grid-stack-item-content rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-sm relative flex flex-col" style="overflow: visible !important;">
                        @if ($widget['title'] || $widget['name'])
                            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 flex items-center justify-between flex-shrink-0 rounded-t-xl relative" style="z-index: 10;"
                                  x-data="widgetHeader"
                                  data-widget-id="{{ $widget['id'] }}"
                                  data-controls="{{ json_encode($widget['resolved_controls']) }}"
                                  data-series-options="{{ json_encode($widget['series_assets_options']) }}"
                                  data-metric-options="{{ json_encode($widget['metric_options']) }}"
                                  data-source-type="{{ $widget['source_type'] }}"
                                  data-variables="{{ json_encode($widget['variables'] ?? []) }}"
                                  @reload-widget.window="if ($event.detail.id === {{ $widget['id'] }}) controls = $event.detail.controls">
                                <div>
                                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">{{ $widget['title'] ?? $widget['name'] }}</h3>
                                    <div class="flex flex-wrap gap-1 mt-1" x-show="getBadges().length > 0">
                                        <template x-for="(badge, index) in getBadges()" :key="index">
                                            <span class="inline-flex items-center rounded-full font-medium bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-300 border border-gray-200 dark:border-gray-600 shadow-sm" style="font-size: 10px; line-height: 14px; padding: 2px 8px;">
                                                <span class="font-bold mr-1" x-text="badge.label + ':'"></span>
                                                <span x-text="badge.text"></span>
                                            </span>
                                        </template>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    {{-- Settings button (gear icon) --}}
                                    <button @click="openDashboardSettings()"
                                            class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition-colors p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700"
                                            title="Widget Settings">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        @endif
                        <div class="widget-content flex-grow p-4 relative overflow-y-auto"
                             data-widget-id="{{ $widget['id'] }}"
                             x-init="renderWidget({{ $widget['id'] }}, $el, {{ json_encode($widget['resolved_controls']) }})">
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @if (empty($this->widgets))
            <div class="flex flex-col items-center justify-center py-24 text-center">
                <x-filament::icon name="heroicon-o-squares-2x2" class="w-16 h-16 text-gray-300 dark:text-gray-600 mb-4" />
                <p class="text-gray-500 dark:text-gray-400 text-lg">No widgets on this dashboard yet</p>
            </div>
        @endif

        {{-- Settings Modal (teleported to body to avoid z-index issues) --}}
        <template x-teleport="body">
            <div x-show="openSettings" style="display: none; z-index: 999999;"
                 class="fixed inset-0 flex items-start justify-center pt-10 sm:pt-16"
                 x-cloak>
                <div @click="closeSettings()" class="fixed inset-0 bg-black/50 dark:bg-black/70 backdrop-blur-sm transition-opacity"></div>

                <div class="relative bg-white dark:bg-gray-900 rounded-xl shadow-xl mx-auto my-4 sm:my-6 flex flex-col ring-1 ring-gray-900/5 dark:ring-white/10"
                     style="width: 95vw; max-width: 1400px; max-height: 90vh;"
                     @click.away="closeSettings()">
                    <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-900/50 rounded-t-xl">
                        <h3 class="text-base font-bold text-gray-900 dark:text-white">Widget Settings</h3>
                        <button @click="closeSettings()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="flex-1 overflow-hidden p-6 md:p-8 bg-gray-50 dark:bg-gray-900 flex flex-col lg:flex-row gap-6">
                        
                        {{-- Left Column: Global Configuration --}}
                        <div class="w-full lg:w-1/3 flex flex-col gap-6 overflow-y-auto custom-scrollbar pr-2 pb-2">
                            {{-- Card: Date Range --}}
                            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm overflow-hidden flex-shrink-0">
                                <div class="flex items-center gap-2 px-6 py-3 border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-700">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-gray-500 dark:text-gray-400">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                                    </svg>
                                    <span class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Date Range</span>
                                </div>
                                <div class="p-6 flex flex-col items-center gap-4">
                                    <input type="date" x-model="settingsControls.date_start"
                                           class="w-full text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 py-2.5 px-4 focus:ring-primary-500 focus:border-primary-500">
                                    <span class="text-gray-400 dark:text-gray-500 text-sm hidden sm:block">↓</span>
                                    <input type="date" x-model="settingsControls.date_end"
                                           class="w-full text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 py-2.5 px-4 focus:ring-primary-500 focus:border-primary-500">
                                </div>
                            </div>

                            {{-- Card: Granularity --}}
                            <template x-if="settingsGranularityOnTheGo">
                                <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm overflow-hidden flex-shrink-0">
                                    <div class="flex items-center gap-2 px-6 py-3 border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-700">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-gray-500 dark:text-gray-400">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Granularity</span>
                                    </div>
                                    <div class="p-6">
                                        <select x-model="settingsControls.granularity"
                                                class="w-full text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 py-2.5 px-4 focus:ring-primary-500 focus:border-primary-500">
                                            <option value="daily">Daily</option>
                                            <option value="weekly">Weekly</option>
                                            <option value="monthly">Monthly</option>
                                        </select>
                                    </div>
                                </div>
                            </template>
                        </div>

                        {{-- Right Column: Variables Configuration --}}
                        <div class="w-full lg:w-2/3 flex overflow-x-auto gap-6 custom-scrollbar pb-2 items-stretch snap-x snap-mandatory">
                            <template x-for="(vConfig, vKey) in settingsVariables" :key="vKey">
                                <template x-if="vConfig.metrics && Object.keys(vConfig.metrics).length > 0">
                                    <div class="flex-none w-full sm:w-[calc(50%-0.75rem)] min-w-[280px] h-full flex flex-col snap-start">
                                        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm overflow-hidden flex flex-col h-full">
                                    <div class="flex items-center justify-between px-6 py-3 border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-700">
                                        <div class="flex items-center gap-2">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-gray-500 dark:text-gray-400">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5m.75-9l3-3 2.148 2.148A12.061 12.061 0 0116.5 7.605" />
                                            </svg>
                                            <span class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider" x-text="vKey === 'dependent' ? 'Dependent Series' : 'Independent Variable ' + (vConfig.index)"></span>
                                        </div>
                                        <template x-if="vConfig.channel">
                                            <span class="text-[10px] font-semibold text-gray-600 dark:text-gray-300 bg-gray-200 dark:bg-gray-700 px-2.5 py-1 rounded-full" x-text="vConfig.channel"></span>
                                        </template>
                                    </div>
                                    <div class="p-6 flex-1 flex flex-col space-y-5">
                                        {{-- Metric selector --}}
                                        <div class="space-y-2">
                                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300">Metric</label>
                                            <select x-model="settingsControls.metrics[vConfig.index]"
                                                    class="w-full text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 py-2.5 px-4 focus:ring-primary-500 focus:border-primary-500">
                                                <option value="" x-text="vKey === 'dependent' ? 'Select dependent metric...' : 'Select independent metric...'"></option>
                                                <template x-for="(label, key) in vConfig.metrics" :key="key">
                                                    <option :value="key" x-text="label"></option>
                                                </template>
                                            </select>
                                        </div>

                                        {{-- Asset filter --}}
                                        <template x-if="settingsSeriesOptions[vKey] && Object.keys(settingsSeriesOptions[vKey].options).length > 0">
                                            <div class="space-y-3 flex-1 flex flex-col">
                                                <div class="flex items-center justify-between">
                                                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300">Assets</label>
                                                    <template x-if="(settingsSeriesOptions[vKey].mode || 'multiple') === 'multiple'">
                                                        <div class="flex gap-3">
                                                            <button @click="settingsSelectAll(vKey)" class="text-[11px] font-semibold text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300 hover:underline">Select All</button>
                                                            <button @click="settingsClearAll(vKey)" class="text-[11px] font-semibold text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:underline">Clear</button>
                                                        </div>
                                                    </template>
                                                </div>
                                                <div class="relative">
                                                    <div class="absolute inset-y-0 left-0 w-10 flex items-center justify-center pointer-events-none">
                                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4 text-gray-400">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                                                        </svg>
                                                    </div>
                                                    <input type="text" x-model="settingsSearchQueries[vKey]" placeholder="Search assets..." class="bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full pl-10 p-2.5">
                                                </div>
                                                <div class="flex flex-col gap-1 max-h-52 overflow-y-auto pr-1">
                                                    <template x-for="[assetId, assetName] in Object.entries(settingsSeriesOptions[vKey].options)" :key="assetId">
                                                        <div x-show="settingsSearchQueries[vKey] === '' || assetName.toLowerCase().includes(settingsSearchQueries[vKey].toLowerCase())"
                                                             @click="settingsToggleAsset(vKey, assetId)"
                                                             class="flex gap-x-3 items-center px-3 py-2.5 text-sm text-gray-700 dark:text-gray-200 rounded-lg cursor-pointer transition-colors border border-transparent"
                                                             :class="(settingsControls.series_assets[vKey] || []).includes(String(assetId)) ? 'bg-primary-50 dark:bg-primary-900/30 border-primary-100 dark:border-primary-900/50' : 'hover:bg-gray-100 dark:hover:bg-gray-700'">
                                                            <div class="w-4 h-4 shrink-0 flex items-center justify-center rounded border transition-colors"
                                                                 :class="(settingsControls.series_assets[vKey] || []).includes(String(assetId)) ? 'bg-primary-600 border-primary-600' : 'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800'">
                                                                <svg x-show="(settingsControls.series_assets[vKey] || []).includes(String(assetId))" class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                                                                </svg>
                                                            </div>
                                                            <span class="truncate font-medium" :class="(settingsControls.series_assets[vKey] || []).includes(String(assetId)) ? 'text-primary-800 dark:text-primary-200' : ''" x-text="assetName"></span>
                                                        </div>
                                                    </template>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                        </div>
                                    </div>
                                </template>
                            </template>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 p-6 sm:p-6 border-t border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 rounded-b-xl">
                        <button @click="closeSettings()"
                                class="text-sm font-semibold text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-gray-100 px-6 py-2.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors border border-transparent">
                            Cancel
                        </button>
                        <button @click="saveSettings()"
                                class="text-sm font-semibold text-white bg-primary-600 hover:bg-primary-500 px-6 py-2.5 rounded-lg shadow-sm transition-colors border border-transparent">
                            Update Settings
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/gridstack@12.6.0/dist/gridstack-all.min.js"></script>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/gridstack@12.6.0/dist/gridstack.min.css"/>
        <script src="{{ asset('js/dashboard-renderer.js') }}"></script>
        <script>
            window.dashboardView = function() {
                return {
                    loadedCount: 0,
                    totalCount: {{ count($this->widgets) }},
                    tenant: '{{ \Filament\Facades\Filament::getTenant()?->subdomain ?? '' }}',
                    dashboardOverrides: {
                        date_start: '{{ $dc['date_start'] ?? '' }}',
                        date_end: '{{ $dc['date_end'] ?? '' }}',
                        zero_handling: '{{ $dc['zero_handling'] ?? 'remove' }}',
                    },
                    init() {
                        this.$nextTick(() => {
                            const tryInit = () => {
                                if (typeof GridStack !== 'undefined') {
                                    GridStack.init({
                                        staticGrid: true,
                                        column: 12,
                                        cellHeight: 100,
                                        margin: 12,
                                        minRow: 6
                                    }, '#view-grid-stack');
                                } else {
                                    setTimeout(tryInit, 50);
                                }
                            };
                            tryInit();
                        });
                    },

                    applyDateRange() {
                        this.refreshAll();
                    },

                    renderWidget(widgetId, el, controls) {
                        el.setAttribute('data-raw-controls', JSON.stringify(controls));
                        let effectiveControls = { ...controls };
                        if (this.dashboardOverrides.date_start) {
                            effectiveControls.date_start = this.dashboardOverrides.date_start;
                        }
                        if (this.dashboardOverrides.date_end) {
                            effectiveControls.date_end = this.dashboardOverrides.date_end;
                        }

                        const tryRender = () => {
                            if (window.dashboardRenderer) {
                                window.dashboardRenderer.renderWidget(widgetId, el, effectiveControls, this.tenant)
                                    .then(() => { this.loadedCount++; })
                                    .catch(() => { this.loadedCount++; });
                            } else {
                                setTimeout(tryRender, 50);
                            }
                        };
                        tryRender();
                    },

                    refreshAll() {
                        this.loadedCount = 0;
                        const widgets = document.querySelectorAll('.grid-stack-item-content .widget-content');
                        widgets.forEach(el => {
                            el.innerHTML = '';
                            const widgetId = el.closest('.grid-stack-item').getAttribute('gs-id');
                            const rawControls = el.getAttribute('data-raw-controls');
                            if (rawControls) {
                                try {
                                    const controls = JSON.parse(rawControls);
                                    this.renderWidget(widgetId, el, controls);
                                } catch (e) {}
                            } else {
                                location.reload();
                            }
                        });
                    },

                    reloadWidget(widgetId, controls) {
                        const widgetItem = document.querySelector(`.grid-stack-item[gs-id="${widgetId}"]`);
                        if (!widgetItem) return;
                        const el = widgetItem.querySelector('.widget-content');
                        if (!el) return;
                        el.innerHTML = '';
                        if (this.loadedCount > 0) this.loadedCount--;
                        this.renderWidget(widgetId, el, controls);
                    },

                    settingsWidgetId: null,
                    settingsControls: { date_start: '', date_end: '', granularity: '', metrics: [], series_assets: {} },
                    settingsSeriesOptions: {},
                    settingsVariables: {},
                    settingsGranularityOnTheGo: false,
                    openSettings: false,
                    settingsSearchQueries: {},

                    openWidgetSettings(widgetId, controls, seriesOptions, variables, granularityOnTheGo) {
                        this.settingsWidgetId = widgetId;
                        this.settingsControls = controls;
                        this.settingsSeriesOptions = seriesOptions;
                        this.settingsVariables = variables;
                        this.settingsGranularityOnTheGo = granularityOnTheGo;
                        this.settingsSearchQueries = {};
                        for (const key in seriesOptions) {
                            this.settingsSearchQueries[key] = '';
                        }
                        for (const key in variables) {
                            if (!this.settingsSearchQueries[key]) this.settingsSearchQueries[key] = '';
                        }
                        this.openSettings = true;
                    },

                    closeSettings() {
                        this.openSettings = false;
                        this.settingsWidgetId = null;
                        this.settingsControls = null;
                    },

                    saveSettings() {
                        const widgetId = this.settingsWidgetId;
                        const controls = this.settingsControls;
                        window.dispatchEvent(new CustomEvent('reload-widget', {
                            detail: {
                                id: widgetId,
                                controls: controls
                            }
                        }));
                        this.reloadWidget(widgetId, controls);
                        this.closeSettings();
                    },

                    settingsToggleAsset(seriesKey, assetId) {
                        const mode = this.settingsSeriesOptions[seriesKey].mode || 'multiple';
                        const current = this.settingsControls.series_assets[seriesKey] || [];
                        let next;
                        
                        if (mode === 'single') {
                            next = [String(assetId)];
                        } else {
                            const idx = current.indexOf(String(assetId));
                            if (idx > -1) {
                                next = current.filter((_, i) => i !== idx);
                            } else {
                                next = [...current, String(assetId)];
                            }
                        }
                        
                        this.settingsControls.series_assets = {
                            ...this.settingsControls.series_assets,
                            [seriesKey]: next
                        };
                    },

                    settingsSelectAll(seriesKey) {
                        const allIds = Object.keys(this.settingsSeriesOptions[seriesKey].options).map(String);
                        this.settingsControls.series_assets = {
                            ...this.settingsControls.series_assets,
                            [seriesKey]: allIds
                        };
                    },

                    settingsClearAll(seriesKey) {
                        this.settingsControls.series_assets = {
                            ...this.settingsControls.series_assets,
                            [seriesKey]: []
                        };
                    }
                };
            };

            window.widgetHeader = function() {
                return {
                    widgetId: null,
                    controls: {},
                    seriesOptions: {},
                    metricOptions: {},
                    variables: {},
                    sourceType: '',
                    searchQueries: {},

                    init() {
                        const el = this.$el;
                        this.widgetId = parseInt(el.dataset.widgetId);
                        this.controls = JSON.parse(el.dataset.controls || '{}');
                        this.seriesOptions = JSON.parse(el.dataset.seriesOptions || '{}');
                        this.metricOptions = JSON.parse(el.dataset.metricOptions || '{}');
                        this.sourceType = el.dataset.sourceType || '';
                        this.variables = JSON.parse(el.dataset.variables || '{}');
                        if (!this.controls.metrics) this.controls.metrics = [];
                        if (!this.controls.series_assets) this.controls.series_assets = {};
                        const varCount = Object.keys(this.variables).length;
                        while (this.controls.metrics.length < varCount) {
                            this.controls.metrics.push('');
                        }
                        if (this.controls.assets && this.controls.assets.length > 0) {
                            if (!this.controls.series_assets.dependent) {
                                const validIds = this.seriesOptions.dependent
                                    ? Object.keys(this.seriesOptions.dependent.options)
                                    : [];
                                this.controls.series_assets.dependent = this.controls.assets
                                    .map(String)
                                    .filter(id => validIds.includes(id));
                            }
                        }
                        this.granularityOnTheGo = !this.controls.granularity || this.controls.granularity === '';
                        if (!this.controls.granularity) this.controls.granularity = '';
                        for (const key in this.seriesOptions) {
                            this.searchQueries[key] = '';
                        }
                        for (const key in this.variables) {
                            if (!this.searchQueries[key]) this.searchQueries[key] = '';
                        }
                    },

                    openDashboardSettings() {
                        window.dispatchEvent(new CustomEvent('open-widget-settings', {
                            detail: {
                                widgetId: this.widgetId,
                                controls: JSON.parse(JSON.stringify(this.controls)),
                                seriesOptions: JSON.parse(JSON.stringify(this.seriesOptions)),
                                variables: JSON.parse(JSON.stringify(this.variables)),
                                granularityOnTheGo: this.granularityOnTheGo
                            }
                        }));
                    },

                    isSelected(seriesKey, assetId) {
                        if (!this.controls.series_assets[seriesKey]) return false;
                        return this.controls.series_assets[seriesKey].includes(String(assetId));
                    },

                    toggleAsset(seriesKey, assetId) {
                        const current = this.controls.series_assets[seriesKey] || [];
                        const idx = current.indexOf(String(assetId));
                        let next;
                        if (idx > -1) {
                            next = current.filter((_, i) => i !== idx);
                        } else {
                            next = [...current, String(assetId)];
                        }
                        this.controls.series_assets[seriesKey] = next;
                    },

                    selectAll(seriesKey) {
                        const allIds = Object.keys(this.seriesOptions[seriesKey].options).map(String);
                        this.controls.series_assets[seriesKey] = allIds;
                    },

                    clearAll(seriesKey) {
                        this.controls.series_assets[seriesKey] = [];
                    },

                    getActiveFilterCount() {
                        let count = 0;
                        for (const key in this.seriesOptions) {
                            if (this.controls.series_assets[key] && this.controls.series_assets[key].length > 0 && this.controls.series_assets[key].length < Object.keys(this.seriesOptions[key].options).length) {
                                count++;
                            }
                        }
                        return count;
                    },

                    updateWidget() {
                        const raw = JSON.stringify(this.controls);
                        const el = document.querySelector(`.grid-stack-item[gs-id="${this.widgetId}"] .widget-content`);
                        if (el) {
                            el.setAttribute('data-raw-controls', raw);
                        }
                        const dbView = document.getElementById('dashboard-view-container');
                        if (dbView && dbView.__x && dbView.__x.getUnobservedData()) {
                            dbView.__x.getUnobservedData().reloadWidget(this.widgetId, this.controls);
                        }
                    },

                    getBadges() {
                        if (Object.keys(this.seriesOptions).length === 0) return [];
                        let badges = [];
                        for (const [key, data] of Object.entries(this.seriesOptions)) {
                            const selected = this.controls.series_assets[key] || [];
                            let label = data.label.replace(/ \(.+\)/, '');
                            let text = '';
                            if (selected.length === 0 || selected.length === Object.keys(data.options).length) {
                                text = 'All Assets';
                            } else {
                                let names = selected.map(id => data.options[id]).filter(Boolean);
                                if (names.length <= 2) {
                                    text = names.join(', ');
                                } else {
                                    text = names.slice(0, 2).join(', ') + ' + ' + (names.length - 2) + ' more';
                                }
                            }
                            badges.push({ label: label, text: text });
                        }
                        return badges;
                    }
                };
            };
        </script>
    @endpush
</x-filament-panels::page>
