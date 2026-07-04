<x-filament-panels::page>
    <link rel="stylesheet" href="{{ asset('css/dashboard-builder.css') }}" />

    <div x-data="dashboardView()" x-init="init()" class="space-y-4">
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

        {{-- Controls Summary --}}
        @if ($this->dashboard->controls)
            <div class="flex flex-wrap gap-3 text-xs">
                @php $c = $this->dashboard->controls; @endphp
                @if (!empty($c['channel']))
                    <span class="px-2 py-1 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400">
                        Channel: {{ \Illuminate\Support\Str::headline($c['channel']) }}
                    </span>
                @endif
                @if (!empty($c['asset']))
                    <span class="px-2 py-1 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400">
                        Asset: {{ $c['asset'] }}
                    </span>
                @endif
                @if (!empty($c['granularity']))
                    <span class="px-2 py-1 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400">
                        {{ ucfirst($c['granularity']) }}
                    </span>
                @endif
                @if (!empty($c['date_start']) || !empty($c['date_end']))
                    <span class="px-2 py-1 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400">
                        {{ $c['date_start'] ?? '—' }} → {{ $c['date_end'] ?? '—' }}
                    </span>
                @endif
            </div>
        @endif

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
                                 x-data="widgetHeader({{ $widget['id'] }}, '{{ addslashes(json_encode($widget['resolved_controls'])) }}', '{{ addslashes(json_encode($widget['series_assets_options'])) }}')"
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
                                    @if (!empty($widget['series_assets_options']))
                                        <div class="relative">
                                            <button @click="openFilters = !openFilters; $el.closest('.grid-stack-item').style.zIndex = openFilters ? 50 : ''" @click.away="openFilters = false; $el.closest('.grid-stack-item').style.zIndex = ''" class="text-xs rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 py-1 px-2 hover:bg-gray-50 dark:hover:bg-gray-700 flex items-center gap-1 shadow-sm">
                                                <x-filament::icon name="heroicon-m-funnel" class="w-3 h-3 text-primary-500" />
                                                <span class="font-medium">Filters</span>
                                                <span x-show="getActiveFilterCount() > 0" class="ml-1 bg-primary-100 text-primary-700 dark:bg-primary-900/40 dark:text-primary-300 text-[10px] font-bold px-1.5 py-0.5 rounded-full" x-text="getActiveFilterCount()"></span>
                                            </button>
                                            
                                            <div x-show="openFilters" x-transition style="display: none;" class="absolute right-0 mt-2 w-72 bg-white dark:bg-gray-800 rounded-xl shadow-xl border border-gray-200 dark:border-gray-700 z-50 flex flex-col overflow-hidden">
                                                <div class="max-h-96 overflow-y-auto p-4 space-y-6">
                                                    <template x-for="(seriesData, seriesKey) in seriesOptions" :key="seriesKey">
                                                        <div class="space-y-2">
                                                            <div class="flex items-center justify-between">
                                                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider" x-text="seriesData.label"></label>
                                                                <div class="flex gap-2">
                                                                    <button @click="selectAll(seriesKey)" class="text-[10px] font-medium text-primary-600 dark:text-primary-400 hover:underline">All</button>
                                                                    <button @click="clearAll(seriesKey)" class="text-[10px] font-medium text-gray-500 dark:text-gray-400 hover:underline">Clear</button>
                                                                </div>
                                                            </div>
                                                            <div class="relative">
                                                                <div class="absolute inset-y-0 left-0 w-8 flex items-center justify-center pointer-events-none">
                                                                    <x-filament::icon name="heroicon-m-magnifying-glass" class="w-3 h-3 text-gray-400" />
                                                                </div>
                                                                <input type="text" x-model="searchQueries[seriesKey]" placeholder="Search..." class="bg-gray-50 dark:bg-gray-900/50 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 text-[11px] rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full pl-8 p-1.5">
                                                            </div>
                                                            <div class="flex flex-col gap-1 max-h-40 overflow-y-auto pr-1">
                                                                <template x-for="[assetId, assetName] in Object.entries(seriesData.options)" :key="assetId">
                                                                    <div x-show="searchQueries[seriesKey] === '' || assetName.toLowerCase().includes(searchQueries[seriesKey].toLowerCase())"
                                                                         @click="toggleAsset(seriesKey, assetId)"
                                                                         class="flex gap-x-2 items-center px-2 py-1.5 text-xs text-gray-700 dark:text-gray-300 rounded cursor-pointer transition-colors"
                                                                         :class="isSelected(seriesKey, assetId) ? 'bg-primary-50 dark:bg-primary-900/20' : 'hover:bg-gray-100 dark:hover:bg-gray-700'">
                                                                        <div class="w-4 h-4 shrink-0 flex items-center justify-center rounded-sm border transition-colors"
                                                                             :class="isSelected(seriesKey, assetId) ? 'bg-primary-600 border-primary-600' : 'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800'">
                                                                            <svg x-show="isSelected(seriesKey, assetId)" class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
                                                                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                                                                            </svg>
                                                                        </div>
                                                                        <span class="truncate font-medium" :class="isSelected(seriesKey, assetId) ? 'text-primary-700 dark:text-primary-300' : ''" x-text="assetName"></span>
                                                                    </div>
                                                                </template>
                                                            </div>
                                                        </div>
                                                    </template>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif
                        <div class="widget-content flex-grow p-4 relative overflow-y-auto"
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
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/gridstack@12.6.0/dist/gridstack-all.min.js"></script>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/gridstack@12.6.0/dist/gridstack.min.css"/>
        <script src="{{ asset('js/dashboard-renderer.js') }}"></script>
        <script>
            function dashboardView() {
                return {
                    loadedCount: 0,
                    totalCount: {{ count($this->widgets) }},
                    tenant: '{{ \Filament\Facades\Filament::getTenant()?->subdomain ?? '' }}',
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

                    renderWidget(widgetId, el, controls) {
                        el.setAttribute('data-raw-controls', JSON.stringify(controls));
                        
                        let effectiveControls = { ...controls };

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
                    }
                };
            }

            function widgetHeader(widgetId, rawControls, rawSeriesOptions) {
                return {
                    widgetId: widgetId,
                    controls: JSON.parse(rawControls),
                    seriesOptions: JSON.parse(rawSeriesOptions) || {},
                    openFilters: false,
                    searchQueries: {},
                    
                    init() {
                        if (!this.controls.series_assets) this.controls.series_assets = {};
                        for (const key in this.seriesOptions) {
                            this.searchQueries[key] = '';
                        }
                    },
                    
                    isSelected(seriesKey, assetId) {
                        if (!this.controls.series_assets[seriesKey]) return false;
                        return this.controls.series_assets[seriesKey].includes(String(assetId));
                    },
                    
                    toggleAsset(seriesKey, assetId) {
                        if (!this.controls.series_assets[seriesKey]) {
                            this.controls.series_assets[seriesKey] = [];
                        }
                        let arr = this.controls.series_assets[seriesKey];
                        const idx = arr.indexOf(String(assetId));
                        if (idx > -1) {
                            arr.splice(idx, 1);
                        } else {
                            arr.push(String(assetId));
                        }
                        this.controls.series_assets[seriesKey] = arr;
                        this.updateWidget();
                    },
                    
                    selectAll(seriesKey) {
                        const allIds = Object.keys(this.seriesOptions[seriesKey].options).map(String);
                        this.controls.series_assets[seriesKey] = allIds;
                        this.updateWidget();
                    },
                    
                    clearAll(seriesKey) {
                        this.controls.series_assets[seriesKey] = [];
                        this.updateWidget();
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
                        // Dispatch to the main dashboardView component
                        const dbView = document.getElementById('view-grid-stack');
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
            }
        </script>
    @endpush
</x-filament-panels::page>
