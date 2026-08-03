<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="referrer" content="no-referrer">
    <title>{{ $dashboard->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: { '50': '#eff6ff', '100': '#dbeafe', '200': '#bfdbfe', '300': '#93c5fd', '400': '#60a5fa', '500': '#3b82f6', '600': '#2563eb', '700': '#1d4ed8', '800': '#1e40af', '900': '#1e3a8a', '950': '#172554' },
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="{{ asset('css/dashboard-builder.css') }}" />
    <style>
        body { font-family: 'Outfit', system-ui, sans-serif; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen {{ $isEmbedded ? 'p-2' : '' }}">
    <div x-data="publicView()" x-init="init()" class="{{ $isEmbedded ? 'w-full' : 'max-w-7xl mx-auto px-4 py-6' }} space-y-6">
        @if (!$isEmbedded)
            {{-- Header --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">{{ $dashboard->name }}</h1>
                        @if ($dashboard->description)
                            <p class="text-sm text-gray-500 mt-1">{{ $dashboard->description }}</p>
                        @endif
                    </div>
                    <div class="flex items-center gap-3">
                        <button @click="openDashboardControls()"
                                class="px-3 py-2 rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 text-xs font-semibold flex items-center gap-2 shadow-sm transition-colors">
                            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path>
                            </svg>
                            <span>{{ __('Controls') }}</span>
                        </button>
                    </div>
                </div>
            </div>
        @endif

        {{-- Widget Grid --}}
        <div id="view-grid-stack" class="grid-stack">
            @foreach ($widgets as $widget)
                <div class="grid-stack-item"
                     gs-id="{{ $widget->id }}"
                     gs-x="{{ $widget->grid_x }}"
                     gs-y="{{ $widget->grid_y }}"
                     gs-w="{{ $widget->grid_w }}"
                     gs-h="{{ $widget->grid_h }}">
                    <div class="grid-stack-item-content rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-sm relative flex flex-col" style="overflow: visible !important;">
                        @if ($widget->title || $widget->name)
                            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 flex items-center justify-between flex-shrink-0 rounded-t-xl relative" style="z-index: 10;"
                                 x-data="widgetHeader({{ $widget->id }}, '{{ addslashes(json_encode($widget->resolved_controls)) }}', '{{ addslashes(json_encode($widget->series_assets_options)) }}')"
                                 @reload-widget.window="if ($event.detail.id === {{ $widget->id }}) controls = $event.detail.controls">
                                <div>
                                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">{{ $widget->title ?? $widget->name }}</h3>
                                    <div class="flex flex-wrap gap-1 mt-1" x-show="getBadges().length > 0">
                                        <template x-for="(badge, index) in getBadges()" :key="index">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full font-medium bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-300 border border-gray-200 dark:border-gray-600 shadow-sm" style="font-size: 10px; line-height: 14px; padding: 2px 8px;">
                                                <span class="font-bold mr-1" x-text="badge.label + ':'"></span>
                                                <span x-text="badge.text"></span>
                                            </span>
                                        </template>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    @if (!empty($widget->series_assets_options))
                                        <div class="relative">
                                            <button @click="openFilters = !openFilters; $el.closest('.grid-stack-item').style.zIndex = openFilters ? 50 : ''" @click.away="openFilters = false; $el.closest('.grid-stack-item').style.zIndex = ''" class="text-xs rounded border border-gray-300 bg-white text-gray-700 py-1 px-2 hover:bg-gray-50 flex items-center gap-1 shadow-sm">
                                                <svg class="w-3 h-3 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
                                                <span class="font-medium">Filters</span>
                                                <span x-show="getActiveFilterCount() > 0" class="ml-1 bg-primary-100 text-primary-700 text-[10px] font-bold px-1.5 py-0.5 rounded-full" x-text="getActiveFilterCount()"></span>
                                            </button>
                                            
                                            <div x-show="openFilters" x-transition style="display: none;" class="absolute right-0 mt-2 w-72 bg-white rounded-xl shadow-xl border border-gray-200 z-50 flex flex-col overflow-hidden">
                                                <div class="max-h-96 overflow-y-auto p-4 space-y-6">
                                                    <template x-for="(seriesData, seriesKey) in seriesOptions" :key="seriesKey">
                                                        <div class="space-y-2">
                                                            <div class="flex items-center justify-between">
                                                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider" x-text="seriesData.label"></label>
                                                                <div class="flex gap-2">
                                                                    <button @click="selectAll(seriesKey)" class="text-[10px] font-medium text-primary-600 hover:underline">All</button>
                                                                    <button @click="clearAll(seriesKey)" class="text-[10px] font-medium text-gray-500 hover:underline">Clear</button>
                                                                </div>
                                                            </div>
                                                            <div class="relative">
                                                                <div class="absolute inset-y-0 left-0 w-8 flex items-center justify-center pointer-events-none">
                                                                    <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                                                                </div>
                                                                <input type="text" x-model="searchQueries[seriesKey]" placeholder="Search..." class="bg-gray-50 border border-gray-300 text-gray-900 text-[11px] rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full pl-8 p-1.5">
                                                            </div>
                                                            <div class="flex flex-col gap-1 max-h-40 overflow-y-auto pr-1">
                                                                <template x-for="[assetId, assetName] in Object.entries(seriesData.options)" :key="assetId">
                                                                    <div x-show="searchQueries[seriesKey] === '' || assetName.toLowerCase().includes(searchQueries[seriesKey].toLowerCase())"
                                                                         @click="toggleAsset(seriesKey, assetId)"
                                                                         class="flex gap-x-2 items-center px-2 py-1.5 text-xs text-gray-700 rounded cursor-pointer transition-colors"
                                                                         :class="isSelected(seriesKey, assetId) ? 'bg-primary-50' : 'hover:bg-gray-100'">
                                                                        <div class="w-4 h-4 shrink-0 flex items-center justify-center rounded-sm border transition-colors"
                                                                             :class="isSelected(seriesKey, assetId) ? 'bg-primary-600 border-primary-600' : 'border-gray-300 bg-white'">
                                                                            <svg x-show="isSelected(seriesKey, assetId)" class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
                                                                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                                                                            </svg>
                                                                        </div>
                                                                        <span class="truncate font-medium" :class="isSelected(seriesKey, assetId) ? 'text-primary-700' : ''" x-text="assetName"></span>
                                                                    </div>
                                                                </template>
                                                            </div>
                                                        </div>
                                                    </template>
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                    {{-- Widget Controls Gear Icon --}}
                                    <button @click="openWidgetControlsModal()" class="p-1 rounded hover:bg-gray-200 text-gray-500 hover:text-gray-700 transition-colors" title="Configure Widget">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        @endif
                        <div class="widget-content flex-grow p-4 relative overflow-y-auto"
                             x-init="renderWidget({{ $widget->id }}, $el, {{ json_encode($widget->resolved_controls) }})">
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @if ($widgets->isEmpty())
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
                <p class="text-gray-400 text-lg">No widgets on this dashboard yet</p>
            </div>
        @endif

        {{-- ============================================================ --}}
        {{-- DASHBOARD-LEVEL CONTROLS MODAL (Excludes Asset Group)        --}}
        {{-- ============================================================ --}}
        <div x-show="showDashboardControls" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="showDashboardControls = false"></div>
            <div class="relative bg-white rounded-xl shadow-xl max-w-xl w-full p-6 space-y-6 z-10 max-h-[90vh] overflow-y-auto">
                <h2 class="text-lg font-bold text-gray-900">{{ __('Dashboard Controls') }}</h2>
                <p class="text-sm text-gray-500">These default controls apply to all widgets on this public view.</p>

                {{-- Date Range --}}
                <div class="space-y-2">
                    <label class="block text-sm font-semibold text-gray-700">{{ __('Date Range') }}</label>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <span class="text-xs text-gray-500">{{ __('Start') }}</span>
                            <input type="date" x-model="dashboardControls.date_start"
                                   class="w-full rounded-lg border-gray-300 bg-white text-gray-900 text-sm p-2 border focus:ring-primary-500 focus:border-primary-500"/>
                        </div>
                        <div>
                            <span class="text-xs text-gray-500">{{ __('End') }}</span>
                            <input type="date" x-model="dashboardControls.date_end"
                                   class="w-full rounded-lg border-gray-300 bg-white text-gray-900 text-sm p-2 border focus:ring-primary-500 focus:border-primary-500"/>
                        </div>
                    </div>
                </div>

                {{-- Granularity --}}
                <div class="space-y-2">
                    <label class="block text-sm font-semibold text-gray-700">{{ __('Granularity') }}</label>
                    <select x-model="dashboardControls.granularity"
                            class="w-full rounded-lg border-gray-300 bg-white text-gray-900 text-sm p-2 border focus:ring-primary-500 focus:border-primary-500">
                        <option value="daily">{{ __('Daily') }}</option>
                        <option value="weekly">{{ __('Weekly') }}</option>
                        <option value="monthly">{{ __('Monthly') }}</option>
                        <option value="quarterly">{{ __('Quarterly') }}</option>
                        <option value="annually">{{ __('Annually') }}</option>
                    </select>
                </div>

                {{-- Zero Handling --}}
                <div class="space-y-2">
                    <label class="block text-sm font-semibold text-gray-700">{{ __('Zero / Missing Data') }}</label>
                    <select x-model="dashboardControls.zero_handling"
                            class="w-full rounded-lg border-gray-300 bg-white text-gray-900 text-sm p-2 border focus:ring-primary-500 focus:border-primary-500">
                        <option value="remove">{{ __('Remove zeros from results') }}</option>
                        <option value="keep">{{ __('Keep zeros in results') }}</option>
                        <option value="trim">{{ __('Trim leading/trailing zeros') }}</option>
                    </select>
                </div>

                {{-- Edge Cases --}}
                <div class="space-y-2">
                    <label class="block text-sm font-semibold text-gray-700">{{ __('Edge Cases') }}</label>
                    <div class="space-y-2">
                        <label class="flex items-center gap-2 text-sm text-gray-600">
                            <input type="checkbox" x-model="dashboardControls.edge_case_weighted" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"/>
                            {{ __('Weighted regression (WLS)') }}
                        </label>
                        <select x-model="dashboardControls.edge_case_grouping"
                                class="w-full rounded-lg border-gray-300 bg-white text-gray-900 text-sm p-2 border focus:ring-primary-500 focus:border-primary-500">
                            <option value="none">{{ __('No grouping') }}</option>
                            <option value="histogram">{{ __('Auto histogram-elbow') }}</option>
                            <option value="percentile">{{ __('Bottom percentile') }}</option>
                        </select>
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t border-gray-200">
                    <button class="px-4 py-2 rounded-lg text-gray-700 hover:bg-gray-100 text-sm" @click="showDashboardControls = false">Cancel</button>
                    <button class="px-4 py-2 rounded-lg bg-primary-600 text-white hover:bg-primary-500 text-sm font-semibold" @click="confirmDashboardControls()">Save Controls</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/gridstack@12.6.0/dist/gridstack-all.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/gridstack@12.6.0/dist/gridstack.min.css"/>
    <script src="{{ asset('js/dashboard-renderer.js') }}?v={{ filemtime(public_path('js/dashboard-renderer.js')) }}"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
    <script>
        function publicView() {
            return {
                loadedCount: 0,
                totalCount: {{ $widgets->count() }},
                tenant: '{{ $project->subdomain }}',
                pvToken: '{{ $pv->token }}',
                isEmbedded: {{ $isEmbedded ? 'true' : 'false' }},
                showDashboardControls: false,
                dashboardControls: {
                    date_start: '{{ $dashboard->controls['date_start'] ?? '' }}',
                    date_end: '{{ $dashboard->controls['date_end'] ?? '' }}',
                    granularity: '{{ $dashboard->controls['granularity'] ?? 'daily' }}',
                    zero_handling: '{{ $dashboard->controls['zero_handling'] ?? 'remove' }}',
                    edge_case_weighted: {{ ($dashboard->controls['edge_case_weighted'] ?? true) ? 'true' : 'false' }},
                    edge_case_grouping: '{{ $dashboard->controls['edge_case_grouping'] ?? 'none' }}'
                },

                init() {
                    this.$nextTick(() => {
                        const tryInit = () => {
                            if (typeof GridStack !== 'undefined') {
                                GridStack.init({
                                    staticGrid: true,
                                    float: true,
                                    column: 12,
                                    cellHeight: 100,
                                    margin: 12,
                                    minRow: 6
                                }, '#view-grid-stack');

                                if (this.isEmbedded) {
                                    this.notifyResize();
                                }
                            } else {
                                setTimeout(tryInit, 50);
                            }
                        };
                        tryInit();
                    });
                },

                openDashboardControls() {
                    this.showDashboardControls = true;
                },

                confirmDashboardControls() {
                    this.showDashboardControls = false;
                    const gridItems = document.querySelectorAll('.grid-stack-item');
                    gridItems.forEach(item => {
                        const widgetId = item.getAttribute('gs-id');
                        const el = item.querySelector('.widget-content');
                        if (el) {
                            const rawControls = JSON.parse(el.getAttribute('data-raw-controls') || '{}');
                            const merged = { ...rawControls, ...this.dashboardControls };
                            this.renderWidget(widgetId, el, merged);
                        }
                    });
                },

                notifyResize() {
                    const sendMsg = () => {
                        window.parent.postMessage({
                            type: 'apis-hub-resize',
                            height: document.body.scrollHeight
                        }, '*');
                    };
                    sendMsg();
                    setTimeout(sendMsg, 500);
                    setTimeout(sendMsg, 1500);
                },

                renderWidget(widgetId, el, controls) {
                    el.setAttribute('data-raw-controls', JSON.stringify(controls));
                    let effectiveControls = { ...controls, pv_token: this.pvToken };

                    const tryRender = () => {
                        if (window.dashboardRenderer) {
                            window.dashboardRenderer.renderWidget(widgetId, el, effectiveControls, this.tenant)
                                .then(() => {
                                    this.loadedCount++;
                                    if (this.isEmbedded) this.notifyResize();
                                })
                                .catch(() => {
                                    this.loadedCount++;
                                });
                        } else {
                            setTimeout(tryRender, 50);
                        }
                    };
                    tryRender();
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

        document.addEventListener('alpine:init', () => {
            Alpine.data('widgetHeader', (widgetId, rawControls, rawSeriesOptions) => ({
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

                openWidgetControlsModal() {
                    this.openFilters = !this.openFilters;
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
            }));
        });
    </script>
</body>
</html>
