<x-filament-panels::page>
    <style>
        :root {
            --gsc-clicks: #4285f4;
            --gsc-impressions: #7e57c2;
            --gsc-ctr: #0097a7;
            --gsc-pos: #f4511e;

            --gsc-text-main: #111827;
            --gsc-text-dim: #6b7280;
            --gsc-bg-card: rgba(0, 0, 0, 0.03);
            --gsc-border: rgba(0, 0, 0, 0.05);
            --gsc-bg-hover: rgba(0, 0, 0, 0.05);
            --gsc-bg-active: rgba(0, 0, 0, 0.08);
            --gsc-chart-grid: rgba(0, 0, 0, 0.05);
            --gsc-chart-ticks: #6b7280;
        }

        .dark {
            --gsc-text-main: #ffffff;
            --gsc-text-dim: #94a3b8;
            --gsc-bg-card: rgba(255, 255, 255, 0.03);
            --gsc-border: rgba(255, 255, 255, 0.05);
            --gsc-bg-hover: rgba(255, 255, 255, 0.05);
            --gsc-bg-active: rgba(255, 255, 255, 0.08);
            --gsc-chart-grid: rgba(255, 255, 255, 0.05);
            --gsc-chart-ticks: #94a3b8;
        }

        .gsc-header-row { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 30px; }

        .gsc-header-title { font-size: 1.8rem; font-weight: 800; color: var(--gsc-text-main); margin-bottom: 5px; display: flex; align-items: center; gap: 12px; }

        .gsc-header-subtitle { color: var(--gsc-text-dim); font-size: 0.9rem; }

        .gsc-header-controls { display: flex; align-items: center; gap: 15px; margin-bottom: 0; }

        .metrics-grid-gsc { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 25px; }

        .card-stat-gsc {
            background: var(--gsc-bg-card);
            border: 1px solid var(--gsc-border);
            border-bottom: 4px solid var(--color, transparent);
            border-radius: 12px;
            padding: 20px;
            cursor: pointer;
            transition: all 0.2s ease;
            opacity: 0.5;
            position: relative;
            overflow: hidden;
        }

        .card-stat-gsc:hover { transform: translateY(-3px); background: var(--gsc-bg-hover); }

        .card-stat-gsc.active { opacity: 1; border-bottom-color: var(--color); background: var(--gsc-bg-active); }

        .gsc-label { font-size: 0.72rem; font-weight: 700; color: var(--gsc-text-dim); text-transform: uppercase; margin-bottom: 8px; display: flex; align-items: center; gap: 8px; letter-spacing: 0.05em; }

        .card-metric-value { font-size: 2.2rem; font-weight: 800; color: var(--gsc-text-main); line-height: 1.2; }

        .card-metric-trend { font-size: 0.85rem; font-weight: 600; margin-top: 8px; display: flex; align-items: center; gap: 4px; padding: 4px 8px; border-radius: 6px; width: fit-content; }

        .trend-up { background: rgba(34, 197, 94, 0.1); color: #16a34a; }

        .dark .trend-up { color: #4ade80; }

        .trend-down { background: rgba(239, 68, 68, 0.1); color: #dc2626; }

        .dark .trend-down { color: #f87171; }

        .trend-neutral { background: var(--gsc-border); color: var(--gsc-text-dim); }

        .chart-container-gsc {
            background: var(--gsc-bg-card);
            border: 1px solid var(--gsc-border);
            border-radius: 16px;
            padding: 30px;
            margin-bottom: 30px;
            height: 450px;
            position: relative;
        }

        .gsc-table-container {
            background: var(--gsc-bg-card);
            border: 1px solid var(--gsc-border);
            border-radius: 16px;
            overflow: hidden;
            margin-top: 40px;
        }

        .tab-nav-gsc { display: flex; border-bottom: 1px solid var(--gsc-border); background: var(--gsc-bg-active); }

        .tab-gsc { padding: 15px 25px; cursor: pointer; font-size: 0.85rem; font-weight: 600; color: var(--gsc-text-dim); border-right: 1px solid var(--gsc-border); transition: all 0.2s; }

        .tab-gsc:hover { background: var(--gsc-bg-hover); }

        .tab-gsc.active { background: var(--gsc-bg-card); color: var(--gsc-clicks); border-bottom: 2px solid var(--gsc-clicks); }

        .gsc-table { width: 100%; border-collapse: collapse; text-align: left; }

        .gsc-table th { padding: 15px 25px; font-size: 0.75rem; text-transform: uppercase; color: var(--gsc-text-dim); font-weight: 700; border-bottom: 1px solid var(--gsc-border); }

        .gsc-table td { padding: 15px 25px; border-bottom: 1px solid var(--gsc-border); vertical-align: middle; }

        .metric-cell { text-align: right; width: 12.5%; min-width: 110px; }

        .gsc-table th:first-child, .gsc-table td:first-child { width: 50%; min-width: 300px; }

        .progress-bar-container { width: 100%; height: 4px; background: var(--gsc-border); border-radius: 2px; margin-top: 4px; overflow: hidden; }

        .progress-bar-fill { height: 100%; transition: width 0.6s ease; }

        .metric-val-main { color: var(--gsc-text-main); font-weight: 600; font-size: 0.95rem; margin-bottom: 4px; }

        .gsc-url-text { font-weight: 600; color: var(--gsc-text-main); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 650px; display: inline-block; vertical-align: middle; }

        .gsc-pagination-container { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; padding: 15px 25px; border-top: 1px solid var(--gsc-border); background: var(--gsc-bg-active); }

        .gsc-pagination-text { font-size: 0.875rem; color: var(--gsc-text-dim); }

        .gsc-pagination-text strong { color: var(--gsc-text-main); font-weight: 700; }

        .gsc-pagination-select { background: var(--fb-bg-card); border: 1px solid var(--fb-border); color: var(--fb-text-main); font-size: 0.875rem; border-radius: 8px; padding: 8px 30px 8px 12px; outline: none; background-repeat: no-repeat; background-position: right; background-size: 32px; }

        .gsc-pagination-btn { padding: 8px 16px; background: var(--gsc-bg-card); border: 1px solid var(--gsc-border); border-radius: 8px; font-size: 0.875rem; font-weight: 500; color: var(--gsc-text-main); cursor: pointer; transition: background 0.2s; }

        .gsc-pagination-btn:hover:not(:disabled) { background: var(--gsc-bg-hover); }

        .gsc-pagination-btn:disabled { opacity: 0.4; cursor: not-allowed; }

        .gsc-pagination-badge { margin-left: 8px; padding: 4px 8px; background: var(--gsc-bg-card); border-radius: 4px; font-size: 0.75rem; }
    </style>


    <div x-data="gscDashboard()" x-init="initDashboard()">
        <div class="gsc-header-row">
            <div>
                <h1 class="gsc-header-title">
                    <x-heroicon-o-magnifying-glass class="w-8 h-8 text-[#4285f4]"/>
                    {{ __('Performance on Google Search results') }}
                </h1>
            </div>
            <div class="gsc-header-controls">
                <button type="button" @click="forceRefresh()"
                        class="flex items-center justify-center bg-primary-600 hover:bg-primary-500 text-white text-sm font-medium rounded-lg px-4 py-2.5 transition duration-75 shadow-sm"
                        :class="{ 'opacity-50 cursor-not-allowed': isSummaryLoading || isChartLoading || isTableLoading }"
                        :disabled="isSummaryLoading || isChartLoading || isTableLoading">
                    <x-heroicon-o-arrow-path class="w-5 h-5 mr-2"
                                             x-bind:class="{ 'animate-spin': isSummaryLoading || isChartLoading || isTableLoading }"/>
                    <span>{{ __('Update') }}</span>
                </button>
                <select wire:model.live="selectedAccount"
                        class="bg-white dark:bg-white/5 border border-gray-300 dark:border-white/10 text-gray-950 dark:text-white text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 transition duration-75 shadow-sm">
                    <option value="" class="bg-white dark:bg-gray-800">{{ __('Select Property...') }}</option>
                    @foreach($accounts as $id => $url)
                        <option value="{{ $id }}" class="bg-white dark:bg-gray-800">{{ $url }}</option>
                    @endforeach
                </select>
                <input type="date" x-model.lazy="dateStart"
                       class="bg-white dark:bg-white/5 border border-gray-300 dark:border-white/10 text-gray-950 dark:text-white text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-40 p-2.5 transition duration-75 shadow-sm">
                <input type="date" x-model.lazy="dateEnd"
                       class="bg-white dark:bg-white/5 border border-gray-300 dark:border-white/10 text-gray-950 dark:text-white text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-40 p-2.5 transition duration-75 shadow-sm">
            </div>
        </div>

        <div class="metrics-grid-gsc relative">
            <div x-show="isSummaryLoading"
                 class="absolute inset-0 z-10 flex items-center justify-center bg-white/50 dark:bg-gray-900/50 backdrop-blur-sm rounded-xl">
                <x-filament::loading-indicator class="h-8 w-8 text-primary-500"/>
            </div>

            <div class="card-stat-gsc" :class="activeMetrics.clicks ? 'active' : ''" @click="toggleMetric('clicks')"
                 style="--color: #4285f4;">
                <div class="gsc-label">{{ __('Total Clicks') }}</div>
                <div class="card-metric-value" x-text="formatNumber(summary.clicks)"></div>
                <div class="card-metric-trend" :class="getVarianceClass(variance.clicks)">
                    <span x-text="getVarianceIcon(variance.clicks)"></span>
                    <span x-text="formatVariance(variance.clicks)"></span>
                </div>
            </div>
            <div class="card-stat-gsc" :class="activeMetrics.impressions ? 'active' : ''"
                 @click="toggleMetric('impressions')" style="--color: #7e57c2;">
                <div class="gsc-label">{{ __('Total Impressions') }}</div>
                <div class="card-metric-value" x-text="formatNumber(summary.impressions)"></div>
                <div class="card-metric-trend" :class="getVarianceClass(variance.impressions)">
                    <span x-text="getVarianceIcon(variance.impressions)"></span>
                    <span x-text="formatVariance(variance.impressions)"></span>
                </div>
            </div>
            <div class="card-stat-gsc" :class="activeMetrics.ctr ? 'active' : ''" @click="toggleMetric('ctr')"
                 style="--color: #0097a7;">
                <div class="gsc-label">{{ __('Average CTR') }}</div>
                <div class="card-metric-value" x-text="formatPercent(summary.ctr)"></div>
                <div class="card-metric-trend" :class="getVarianceClass(variance.ctr)">
                    <span x-text="getVarianceIcon(variance.ctr)"></span>
                    <span x-text="formatVariance(variance.ctr)"></span>
                </div>
            </div>
            <div class="card-stat-gsc" :class="activeMetrics.position ? 'active' : ''" @click="toggleMetric('position')"
                 style="--color: #f4511e;">
                <div class="gsc-label">{{ __('Average Position') }}</div>
                <div class="card-metric-value" x-text="formatDecimals(summary.position)"></div>
                <div class="card-metric-trend" :class="getVarianceClass(variance.position, true)">
                    <span x-text="getVarianceIcon(variance.position, true)"></span>
                    <span x-text="formatVariance(variance.position)"></span>
                </div>
            </div>
        </div>

        <div class="chart-container-gsc relative w-full" wire:ignore>
            <div x-show="isChartLoading"
                 class="absolute inset-0 z-10 flex items-center justify-center bg-white/50 dark:bg-gray-900/50 backdrop-blur-sm rounded-xl">
                <x-filament::loading-indicator class="h-8 w-8 text-primary-500"/>
            </div>
            <div style="position: relative; width: 100%; height: 100%; display: block;">
                <canvas x-ref="canvas"></canvas>
            </div>
        </div>

        <div x-show="hasAnyFilters"
             class="mb-6 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 shadow-sm"
             style="display: none;" x-transition>
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 flex items-center gap-2">
                    <x-heroicon-o-funnel class="w-4 h-4 text-primary-500"/>
                    {{ __('Active Filters') }}
                </h3>
                <button @click="clearFilters()"
                        class="text-xs text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 font-medium">{{ __('Clear All') }}</button>
            </div>
            <div class="flex flex-wrap gap-2">
                <template x-for="(values, tab) in activeFilters" :key="tab">
                    <template x-for="val in values" :key="val">
                        <span
                            class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium bg-primary-100 text-primary-800 dark:bg-primary-900/40 dark:text-primary-300 border border-primary-200 dark:border-primary-800">
                            <span class="opacity-70 uppercase text-[10px] mr-1" x-text="tab + ':'"></span>
                            <span x-text="val" class="max-w-xs truncate" :title="val"></span>
                            <button @click.stop="toggleFilter(tab, val)"
                                    class="ml-1 text-primary-600 hover:text-primary-900 dark:text-primary-400 dark:hover:text-primary-200">
                                <x-heroicon-m-x-mark class="w-3 h-3"/>
                            </button>
                        </span>
                    </template>
                </template>
            </div>
        </div>

        <div class="gsc-table-container relative">
            <div x-show="isTableLoading"
                 class="absolute inset-0 z-10 flex items-center justify-center bg-white/50 dark:bg-gray-900/50 backdrop-blur-sm rounded-xl">
                <x-filament::loading-indicator class="h-8 w-8 text-primary-500"/>
            </div>
            <div class="tab-nav-gsc">
                <div class="tab-gsc" :class="activeTab === 'queries' ? 'active' : ''"
                     @click="setTab('queries')">{{ __('QUERIES') }}</div>
                <div class="tab-gsc" :class="activeTab === 'pages' ? 'active' : ''"
                     @click="setTab('pages')">{{ __('PAGES') }}</div>
                <div class="tab-gsc" :class="activeTab === 'countries' ? 'active' : ''"
                     @click="setTab('countries')">{{ __('COUNTRIES') }}</div>
                <div class="tab-gsc" :class="activeTab === 'devices' ? 'active' : ''"
                     @click="setTab('devices')">{{ __('DEVICES') }}</div>
                <div class="tab-gsc" :class="activeTab === 'appearances' ? 'active' : ''"
                     @click="setTab('appearances')">{{ __('SEARCH APPEARANCE') }}</div>
            </div>

            <div class="p-4 border-b border-gray-200 dark:border-white/5 bg-white dark:bg-transparent">
                <div class="relative w-full max-w-md">
                    <div
                        class="absolute inset-y-0 left-0 rtl:right-0 rtl:left-auto w-10 flex items-center justify-center pointer-events-none">
                        <x-heroicon-o-magnifying-glass class="w-4 h-4 text-gray-500 dark:text-gray-400"/>
                    </div>
                    <input type="text" x-model.debounce.300ms="searchQuery"
                           class="bg-gray-50 dark:bg-white/5 border border-gray-300 dark:border-white/10 text-gray-900 dark:text-white text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full pl-10 p-2"
                           style="padding-left: 2.75rem;"
                           placeholder="{{ __('Filter rows...') }}">
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="gsc-table">
                    <thead>
                    <tr>
                        <th>
                            <span x-text="activeTab.toUpperCase()"></span>
                        </th>
                        <th class="metric-cell cursor-pointer" @click="sortBy('clicks')">{{ __('Clicks') }} <span
                                x-show="sortCol === 'clicks'" x-text="sortDir === 'desc' ? '↓' : '↑'"></span></th>
                        <th class="metric-cell cursor-pointer" @click="sortBy('impressions')">{{ __('Impressions') }}
                            <span x-show="sortCol === 'impressions'" x-text="sortDir === 'desc' ? '↓' : '↑'"></span>
                        </th>
                        <th class="metric-cell cursor-pointer" @click="sortBy('ctr')">{{ __('CTR') }} <span
                                x-show="sortCol === 'ctr'" x-text="sortDir === 'desc' ? '↓' : '↑'"></span></th>
                        <th class="metric-cell cursor-pointer" @click="sortBy('position')">{{ __('Position') }} <span
                                x-show="sortCol === 'position'" x-text="sortDir === 'desc' ? '↓' : '↑'"></span></th>
                    </tr>
                    </thead>
                    <tbody>
                    <template x-for="(row, index) in paginatedTableData" :key="row.id + '_' + index">
                        <tr @click="activeTab !== 'appearances' ? toggleFilter(activeTab, row.id) : null"
                            class="transition duration-150"
                            :class="(activeTab !== 'appearances' ? 'cursor-pointer hover:bg-gray-50 dark:hover:bg-white/5 ' : '') + (isFilterActive(activeTab, row.id) ? 'bg-primary-50 dark:bg-primary-900/20 shadow-inner' : '')">
                            <td>
                                <div class="flex items-center gap-2">
                                    <div x-show="isFilterActive(activeTab, row.id)" class="text-primary-500">
                                        <x-heroicon-s-check-circle class="w-4 h-4"/>
                                    </div>
                                    <div class="gsc-url-text" :title="row.id" x-text="row.id"></div>
                                </div>
                            </td>
                            <td class="metric-cell">
                                <div class="metric-val-main" x-text="formatNumber(row.clicks)"></div>
                                <div class="progress-bar-container">
                                    <div class="progress-bar-fill" style="background: #4285f4;"
                                         :style="`width: ${(row.clicks / maxClicks) * 100}%`"></div>
                                </div>
                            </td>
                            <td class="metric-cell">
                                <div class="metric-val-main" x-text="formatNumber(row.impressions)"></div>
                                <div class="progress-bar-container">
                                    <div class="progress-bar-fill" style="background: #7e57c2;"
                                         :style="`width: ${(row.impressions / maxImpressions) * 100}%`"></div>
                                </div>
                            </td>
                            <td class="metric-cell">
                                <div class="metric-val-main" x-text="formatPercent(row.ctr)"></div>
                                <div class="progress-bar-container">
                                    <div class="progress-bar-fill" style="background: #0097a7;"
                                         :style="`width: ${row.ctr * 100}%`"></div>
                                </div>
                            </td>
                            <td class="metric-cell">
                                <div class="metric-val-main" x-text="formatDecimals(row.position)"></div>
                            </td>
                        </tr>
                    </template>
                    </tbody>
                </table>
            </div>

            <div class="gsc-pagination-container" x-show="tableDataRaw.length > 0">
                <div class="flex items-center gap-4 mb-4 sm:mb-0">
                    <span class="gsc-pagination-text font-medium">{{ __('Rows per page:') }}</span>
                    <select x-model="pageSize" class="gsc-pagination-select">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                        <option value="250">250</option>
                    </select>
                </div>
                <div class="flex items-center gap-6">
                    <span class="gsc-pagination-text">
                        {{ __('Page') }} <strong x-text="currentPage"></strong> {{ __('of') }} <strong
                            x-text="totalPages"></strong>
                        <span class="gsc-pagination-badge">(<span x-text="tableDataRaw.length"></span> {{ __('results') }})</span>
                    </span>
                    <div class="flex gap-2">
                        <button @click="prevPage()" :disabled="currentPage === 1"
                                class="gsc-pagination-btn">{{ __('Prev') }}</button>
                        <button @click="nextPage()" :disabled="currentPage === totalPages"
                                class="gsc-pagination-btn">{{ __('Next') }}</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const registerGscDashboard = () => {
                Alpine.data('gscDashboard', () => {
                    return {
                        tenantId: '{{ Filament\Facades\Filament::getTenant()->id ?? Filament\Facades\Filament::getTenant()->slug }}',
                        account: @entangle('selectedAccount'),
                        dateStart: @entangle('dateStart'),
                        dateEnd: @entangle('dateEnd'),
                        activeTab: @entangle('activeTab'),

                        isSummaryLoading: false,
                        isChartLoading: false,
                        isTableLoading: false,

                        summary: {clicks: 0, impressions: 0, ctr: 0, position: 0},
                        previous: {clicks: 0, impressions: 0, ctr: 0, position: 0},
                        chartDataRaw: [],
                        tableDataRaw: [],

                        activeMetrics: {clicks: true, impressions: true, ctr: false, position: false},

                        activeFilters: {queries: [], pages: [], countries: [], devices: []},
                        searchQuery: '',

                        sortCol: 'clicks',
                        sortDir: 'desc',

                        currentPage: 1,
                        pageSize: 10,

                        get hasAnyFilters() {
                            return Object.values(this.activeFilters).some(arr => arr.length > 0);
                        },

                        initDashboard() {
                            const boot = () => {
                                this.initChart();

                                this.$watch('account', () => {
                                    this.loadFilters();
                                    this.fetchAll();
                                });
                                this.$watch('dateStart', () => this.fetchAll());
                                this.$watch('dateEnd', () => this.fetchAll());
                                this.$watch('pageSize', () => {
                                    this.currentPage = 1;
                                });

                                if (this.account && this.dateStart && this.dateEnd) {
                                    this.loadFilters();
                                    this.fetchAll();
                                }
                            };

                            if (window.Chart && window.dayjs) {
                                boot();
                            } else {
                                Promise.all([
                                    window.importChartJs(),
                                    window.importDayJs()
                                ]).then(([chartModule, dayjsModule]) => {
                                    window.Chart = chartModule.default;
                                    window.dayjs = dayjsModule.default;
                                    boot();
                                }).catch(err => {
                                    console.error("Failed to load charting libraries", err);
                                });
                            }
                        },

                        setTab(tab) {
                            this.activeTab = tab;
                            this.currentPage = 1;
                            this.searchQuery = ''; // Clear search when switching tabs
                            this.fetchTable();
                            this.$wire.setActiveTab(tab);
                        },

                        loadFilters() {
                            if (!this.account) return;
                            const saved = sessionStorage.getItem(`gsc_filters_${this.tenantId}_${this.account}`);
                            if (saved) {
                                try {
                                    this.activeFilters = JSON.parse(saved);
                                } catch (e) {
                                    this.clearFiltersLocal();
                                }
                            } else {
                                this.clearFiltersLocal();
                            }
                        },

                        saveFilters() {
                            if (!this.account) return;
                            this.safeCacheSet(`gsc_filters_${this.tenantId}_${this.account}`, JSON.stringify(this.activeFilters));
                        },

                        clearFiltersLocal() {
                            this.activeFilters = {queries: [], pages: [], countries: [], devices: []};
                        },

                        clearFilters() {
                            this.clearFiltersLocal();
                            this.saveFilters();
                            this.fetchSummary();
                            this.fetchChart();
                        },

                        toggleFilter(tab, value) {
                            if (tab === 'appearances') return; // Not supported
                            if (!this.activeFilters[tab]) this.activeFilters[tab] = [];
                            const idx = this.activeFilters[tab].indexOf(value);
                            if (idx > -1) {
                                this.activeFilters[tab].splice(idx, 1);
                            } else {
                                this.activeFilters[tab].push(value);
                            }
                            this.saveFilters();
                            this.fetchSummary();
                            this.fetchChart();
                        },

                        isFilterActive(tab, value) {
                            return this.activeFilters[tab] && this.activeFilters[tab].includes(value);
                        },

                        forceRefresh() {
                            this.clearCache();
                            this.fetchAll();
                        },

                        safeCacheSet(key, value) {
                            try {
                                sessionStorage.setItem(key, value);
                                return true;
                            } catch (e) {
                                if (e.name === 'QuotaExceededError' || e.code === 22) {
                                    Object.keys(sessionStorage).forEach(k => {
                                        if (k.startsWith('gsc_') || k.startsWith('fbo_') || k.startsWith('fbm_')) {
                                            sessionStorage.removeItem(k);
                                        }
                                    });
                                    try {
                                        sessionStorage.setItem(key, value);
                                        return true;
                                    } catch {
                                        console.warn('Cache still full after eviction, skipping cache for', key);
                                        return false;
                                    }
                                }
                                console.warn('Cache write failed:', e);
                                return false;
                            }
                        },

                        clearCache() {
                            const prefix = `gsc_${this.tenantId}_${this.account}_${this.dateStart}_${this.dateEnd}`;
                            Object.keys(sessionStorage).forEach(key => {
                                if (key.startsWith(prefix)) {
                                    sessionStorage.removeItem(key);
                                }
                            });
                        },

                        getCacheKey(endpoint, includeFilters = true) {
                            const filterHash = includeFilters ? JSON.stringify(this.activeFilters) : 'no_filters';
                            return `gsc_${this.tenantId}_${this.account}_${this.dateStart}_${this.dateEnd}_${endpoint}_${this.activeTab}_${filterHash}`;
                        },

                        async fetchAll() {
                            if (!this.account || !this.dateStart || !this.dateEnd) return;
                            this.fetchSummary();
                            this.fetchChart();
                            this.fetchTable();
                        },

                        async fetchSummary() {
                            if (!this.account || !this.dateStart || !this.dateEnd) return;
                            const cacheKey = this.getCacheKey('summary');

                            if (sessionStorage.getItem(cacheKey)) {
                                const data = JSON.parse(sessionStorage.getItem(cacheKey));
                                this.summary = data.summary || {clicks: 0, impressions: 0, ctr: 0, position: 0};
                                this.previous = data.previous || {clicks: 0, impressions: 0, ctr: 0, position: 0};
                                return;
                            }

                            this.isSummaryLoading = true;
                            try {
                                const response = await fetch('/api/gsc/summary', this.getFetchOptions());
                                const data = await response.json();
                                if (!data.error) {
                                    this.safeCacheSet(cacheKey, JSON.stringify(data));
                                    this.summary = data.summary || {clicks: 0, impressions: 0, ctr: 0, position: 0};
                                    this.previous = data.previous || {clicks: 0, impressions: 0, ctr: 0, position: 0};
                                }
                            } catch (error) {
                                console.error('Error fetching summary:', error);
                            } finally {
                                this.isSummaryLoading = false;
                            }
                        },

                        async fetchChart() {
                            if (!this.account || !this.dateStart || !this.dateEnd) return;
                            const cacheKey = this.getCacheKey('chart');

                            if (sessionStorage.getItem(cacheKey)) {
                                const data = JSON.parse(sessionStorage.getItem(cacheKey));
                                this.chartDataRaw = data.chart || [];
                                this.updateChart();
                                return;
                            }

                            this.isChartLoading = true;
                            try {
                                const response = await fetch('/api/gsc/chart', this.getFetchOptions());
                                const data = await response.json();
                                if (!data.error) {
                                    this.safeCacheSet(cacheKey, JSON.stringify(data));
                                    this.chartDataRaw = data.chart || [];
                                    this.updateChart();
                                }
                            } catch (error) {
                                console.error('Error fetching chart:', error);
                            } finally {
                                this.isChartLoading = false;
                            }
                        },

                        async fetchTable() {
                            if (!this.account || !this.dateStart || !this.dateEnd) return;
                            const cacheKey = this.getCacheKey('table', false); // Table does NOT use active filters

                            if (sessionStorage.getItem(cacheKey)) {
                                const data = JSON.parse(sessionStorage.getItem(cacheKey));
                                this.tableDataRaw = data.table || [];
                                return;
                            }

                            this.isTableLoading = true;
                            try {
                                const response = await fetch('/api/gsc/table', this.getFetchOptions(false));
                                const data = await response.json();
                                if (!data.error) {
                                    this.safeCacheSet(cacheKey, JSON.stringify(data));
                                    this.tableDataRaw = data.table || [];
                                    this.currentPage = 1;
                                }
                            } catch (error) {
                                console.error('Error fetching table:', error);
                            } finally {
                                this.isTableLoading = false;
                            }
                        },

                        getFetchOptions(includeFilters = true) {
                            const payload = {
                                tenant: this.tenantId,
                                account: this.account,
                                dateStart: this.dateStart,
                                dateEnd: this.dateEnd,
                                activeTab: this.activeTab
                            };

                            if (includeFilters) {
                                payload.activeFilters = this.activeFilters;
                            }

                            return {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                },
                                body: JSON.stringify(payload)
                            };
                        },

                        get variance() {
                            const calc = (current, prev) => {
                                if (!prev || Number(prev) === 0) return 0;
                                return ((Number(current) - Number(prev)) / Number(prev)) * 100;
                            };
                            return {
                                clicks: calc(this.summary.clicks, this.previous.clicks),
                                impressions: calc(this.summary.impressions, this.previous.impressions),
                                ctr: calc(this.summary.ctr, this.previous.ctr),
                                position: calc(this.summary.position, this.previous.position)
                            };
                        },

                        getVarianceClass(val, invert = false) {
                            if (val === 0) return 'trend-neutral';
                            const isPositive = val > 0;
                            if (invert) return isPositive ? 'trend-down' : 'trend-up';
                            return isPositive ? 'trend-up' : 'trend-down';
                        },

                        getVarianceIcon(val, invert = false) {
                            if (val === 0) return '-';
                            const isPositive = val > 0;
                            if (invert) return isPositive ? '↓' : '↑';
                            return isPositive ? '↑' : '↓';
                        },

                        formatVariance(val) {
                            if (val === 0) return '0%';
                            return Math.abs(val).toFixed(1) + '%';
                        },

                        toggleMetric(metric) {
                            this.activeMetrics[metric] = !this.activeMetrics[metric];
                            this.updateChart();
                        },

                        initChart() {
                            const ctx = this.$refs.canvas.getContext('2d');

                            const config = {
                                type: 'line',
                                data: {labels: [], datasets: []},
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    interaction: {mode: 'index', intersect: false},
                                    plugins: {
                                        legend: {display: false},
                                        tooltip: {
                                            backgroundColor: 'rgba(15, 23, 42, 0.9)',
                                            titleColor: '#FFF',
                                            bodyColor: '#E2E8F0',
                                            borderColor: 'rgba(255,255,255,0.1)',
                                            borderWidth: 1,
                                            padding: 12,
                                            boxPadding: 6,
                                            usePointStyle: true
                                        }
                                    },
                                    scales: {
                                        x: {
                                            grid: {color: 'var(--gsc-chart-grid)', drawBorder: false},
                                            ticks: {color: 'var(--gsc-chart-ticks)'}
                                        },
                                        yClicks: {
                                            type: 'linear',
                                            position: 'left',
                                            display: false,
                                            grid: {color: 'var(--gsc-chart-grid)', drawBorder: false},
                                            ticks: {color: '#4285F4'},
                                            min: 0
                                        },
                                        yImpressions: {
                                            type: 'linear',
                                            position: 'right',
                                            display: false,
                                            grid: {drawOnChartArea: false, drawBorder: false},
                                            ticks: {color: '#7E57C2'}
                                        },
                                        yCtr: {
                                            type: 'linear',
                                            position: 'left',
                                            display: false,
                                            grid: {drawOnChartArea: false, drawBorder: false},
                                            ticks: {color: '#0097A7'},
                                            min: 0
                                        },
                                        yPosition: {
                                            type: 'linear',
                                            position: 'right',
                                            reverse: true,
                                            display: false,
                                            grid: {drawOnChartArea: false, drawBorder: false},
                                            ticks: {color: '#F4511E'}
                                        }
                                    }
                                }
                            };

                            this.$refs.canvas._chartInstance = new Chart(ctx, config);
                        },

                        updateChart() {
                            let chart = this.$refs.canvas._chartInstance;
                            if (!chart || !this.chartDataRaw) return;

                            const startDate = dayjs(this.dateStart);
                            const endDate = dayjs(this.dateEnd);
                            const daysDiff = endDate.diff(startDate, 'day');

                            const fullDateRange = [];
                            for (let i = 0; i <= daysDiff; i++) {
                                fullDateRange.push(startDate.add(i, 'day').format('YYYY-MM-DD'));
                            }

                            const dataByDate = {};
                            this.chartDataRaw.forEach(r => {
                                if (r && (r.daily || r.date || r.metric_date)) {
                                    const dateStr = dayjs(r.daily || r.date || r.metric_date).format('YYYY-MM-DD');
                                    dataByDate[dateStr] = r;
                                }
                            });

                            const paddedData = fullDateRange.map(dateStr => {
                                if (dataByDate[dateStr]) return dataByDate[dateStr];
                                return {
                                    daily: dateStr,
                                    clicks: 0,
                                    impressions: 0,
                                    ctr: 0,
                                    position: 0
                                };
                            });

                            const labels = paddedData.map(r => dayjs(r.daily || r.date || r.metric_date).format('MMM D'));
                            const datasets = [];

                            const chartData = paddedData;

                            if (this.activeMetrics.clicks) {
                                datasets.push({
                                    label: 'Clicks',
                                    data: chartData.map(r => r.clicks),
                                    borderColor: '#4285F4',
                                    backgroundColor: 'rgba(66, 133, 244, 0.1)',
                                    borderWidth: 2,
                                    pointRadius: 0,
                                    pointHoverRadius: 6,
                                    fill: true,
                                    yAxisID: 'yClicks',
                                    tension: 0.4
                                });
                            }

                            if (this.activeMetrics.impressions) {
                                datasets.push({
                                    label: 'Impressions',
                                    data: chartData.map(r => r.impressions),
                                    borderColor: '#7E57C2',
                                    backgroundColor: 'rgba(126, 87, 194, 0.1)',
                                    borderWidth: 2,
                                    pointRadius: 0,
                                    pointHoverRadius: 6,
                                    fill: true,
                                    yAxisID: 'yImpressions',
                                    tension: 0.4
                                });
                            }

                            if (this.activeMetrics.ctr) {
                                datasets.push({
                                    label: 'CTR',
                                    data: chartData.map(r => r.ctr * 100),
                                    borderColor: '#0097A7',
                                    borderWidth: 2,
                                    pointRadius: 0,
                                    pointHoverRadius: 6,
                                    fill: false,
                                    yAxisID: 'yCtr',
                                    tension: 0.4
                                });
                            }

                            if (this.activeMetrics.position) {
                                datasets.push({
                                    label: 'Position',
                                    data: chartData.map(r => r.position === 0 ? null : r.position),
                                    borderColor: '#F4511E',
                                    borderWidth: 2,
                                    borderDash: [5, 5],
                                    pointRadius: 0,
                                    pointHoverRadius: 6,
                                    fill: false,
                                    yAxisID: 'yPosition',
                                    tension: 0.4
                                });
                            }

                            // Manage scale visibility and background grid dynamically
                            let gridDrawn = false;
                            const cssGridColor = getComputedStyle(document.documentElement).getPropertyValue('--gsc-chart-grid').trim();
                            const cssTicksColor = getComputedStyle(document.documentElement).getPropertyValue('--gsc-chart-ticks').trim();

                            chart.options.scales.x.grid.color = cssGridColor;
                            chart.options.scales.x.ticks.color = cssTicksColor;

                            ['clicks', 'impressions', 'ctr', 'position'].forEach(m => {
                                let scaleId = 'y' + m.charAt(0).toUpperCase() + m.slice(1);
                                chart.options.scales[scaleId].display = this.activeMetrics[m];
                                if (this.activeMetrics[m]) {
                                    if (!gridDrawn) {
                                        chart.options.scales[scaleId].grid.drawOnChartArea = true;
                                        chart.options.scales[scaleId].grid.color = cssGridColor;
                                        gridDrawn = true;
                                    } else {
                                        chart.options.scales[scaleId].grid.drawOnChartArea = false;
                                    }
                                }
                            });

                            chart.data.labels = labels;
                            chart.data.datasets = datasets;
                            chart.update();
                        },

                        sortBy(col) {
                            if (this.sortCol === col) {
                                this.sortDir = this.sortDir === 'desc' ? 'asc' : 'desc';
                            } else {
                                this.sortCol = col;
                                this.sortDir = 'desc';
                            }
                            this.currentPage = 1;
                        },

                        get sortedTableData() {
                            let data = [...this.tableDataRaw];

                            if (this.searchQuery && this.searchQuery.trim() !== '') {
                                const query = this.searchQuery.toLowerCase().trim();
                                data = data.filter(row => String(row.id || '').toLowerCase().includes(query));
                            }

                            return data.sort((a, b) => {
                                let valA = Number(a[this.sortCol]);
                                let valB = Number(b[this.sortCol]);

                                if (isNaN(valA) || isNaN(valB)) {
                                    valA = String(a[this.sortCol] || '').toLowerCase();
                                    valB = String(b[this.sortCol] || '').toLowerCase();
                                }

                                if (valA === valB) return 0;
                                if (this.sortDir === 'desc') return valA < valB ? 1 : -1;
                                return valA > valB ? 1 : -1;
                            });
                        },

                        get totalPages() {
                            return Math.ceil(this.sortedTableData.length / this.pageSize) || 1;
                        },

                        get paginatedTableData() {
                            const start = (this.currentPage - 1) * this.pageSize;
                            const end = start + Number(this.pageSize);
                            return this.sortedTableData.slice(start, end);
                        },

                        nextPage() {
                            if (this.currentPage < this.totalPages) this.currentPage++;
                        },

                        prevPage() {
                            if (this.currentPage > 1) this.currentPage--;
                        },

                        get maxClicks() {
                            if (!this.sortedTableData.length) return 1;
                            return Math.max(...this.sortedTableData.map(r => r.clicks));
                        },

                        get maxImpressions() {
                            if (!this.sortedTableData.length) return 1;
                            return Math.max(...this.sortedTableData.map(r => r.impressions));
                        },

                        formatNumber(num) {
                            if (num === undefined || num === null) return '0';
                            return new Intl.NumberFormat('en-US').format(num);
                        },

                        formatPercent(num) {
                            if (num === undefined || num === null) return '0%';
                            return (num * 100).toFixed(2) + '%';
                        },

                        formatDecimals(num) {
                            if (num === undefined || num === null) return '0.0';
                            return Number(num).toFixed(1);
                        }
                    }
                });
            };

            if (window.Alpine) {
                registerGscDashboard();
            } else {
                document.addEventListener('alpine:init', registerGscDashboard);
            }
        })();
    </script>
</x-filament-panels::page>
