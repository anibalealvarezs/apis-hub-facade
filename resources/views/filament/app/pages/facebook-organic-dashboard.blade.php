<x-filament-panels::page>
    <style>
        :root {
            --fb-reach: #10b981;
            --fb-interactions: #6366f1;
            --fb-likes: #0ea5e9;
            --fb-comments: #8b5cf6;
            --fb-views: #f59e0b;
            --fb-follows: #ec4899;

            --fb-text-main: #111827;
            --fb-text-dim: #6b7280;
            --fb-bg-card: rgba(0, 0, 0, 0.02);
            --fb-border: rgba(0, 0, 0, 0.06);
            --fb-bg-hover: rgba(0, 0, 0, 0.04);
            --fb-bg-active: rgba(0, 0, 0, 0.06);
            --fb-chart-grid: rgba(0, 0, 0, 0.05);
            --fb-chart-ticks: #6b7280;
        }

        .dark {
            --fb-text-main: #ffffff;
            --fb-text-dim: #94a3b8;
            --fb-bg-card: rgba(255, 255, 255, 0.03);
            --fb-border: rgba(255, 255, 255, 0.05);
            --fb-bg-hover: rgba(255, 255, 255, 0.05);
            --fb-bg-active: rgba(255, 255, 255, 0.08);
            --fb-chart-grid: rgba(255, 255, 255, 0.05);
            --fb-chart-ticks: #94a3b8;
        .fb-header-row {
            position: sticky;
            top: 0;
            z-index: 30;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-bottom: 30px;
            padding: 1rem 0;
            background: rgba(249, 250, 251, 0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--fb-border);
        }
        .dark .fb-header-row,
        html.dark .fb-header-row {
            background: rgba(17, 24, 39, 0.85);
            border-bottom-color: rgba(255, 255, 255, 0.08);
        }

        .fb-header-title { font-size: 1.8rem; font-weight: 800; color: var(--fb-text-main); margin-bottom: 5px; display: flex; align-items: center; gap: 12px; }

        .fb-header-controls { display: flex; align-items: center; gap: 15px; margin-bottom: 0; }

        .metrics-grid-fb { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 12px; margin-bottom: 25px; }

        .card-stat-fb {
            background: var(--fb-bg-card);
            border: 1px solid var(--fb-border);
            border-bottom: 4px solid var(--color, transparent);
            border-radius: 12px;
            padding: 16px;
            cursor: pointer;
            transition: all 0.2s ease;
            opacity: 0.5;
            position: relative;
            overflow: hidden;
        }

        .card-stat-fb:hover { transform: translateY(-3px); background: var(--fb-bg-hover); }

        .card-stat-fb.active { opacity: 1; border-bottom-color: var(--color); background: var(--fb-bg-active); }

        .fb-label { font-size: 0.65rem; font-weight: 700; color: var(--fb-text-dim); text-transform: uppercase; margin-bottom: 8px; letter-spacing: 0.05em; }

        .card-metric-value { font-size: 1.5rem; font-weight: 800; color: var(--fb-text-main); line-height: 1.2; }

        .card-metric-trend { font-size: 0.75rem; font-weight: 600; margin-top: 6px; display: flex; align-items: center; gap: 4px; padding: 3px 6px; border-radius: 6px; width: fit-content; }

        .trend-up { background: rgba(34, 197, 94, 0.1); color: #16a34a; }

        .dark .trend-up { color: #4ade80; }

        .trend-down { background: rgba(239, 68, 68, 0.1); color: #dc2626; }

        .dark .trend-down { color: #f87171; }

        .trend-neutral { background: var(--fb-border); color: var(--fb-text-dim); }

        .chart-container-fb {
            background: var(--fb-bg-card);
            border: 1px solid var(--fb-border);
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 30px;
            height: 400px;
            position: relative;
        }

        .fb-table-container {
            background: var(--fb-bg-card);
            border: 1px solid var(--fb-border);
            border-radius: 16px;
            overflow: hidden;
            margin-top: 20px;
        }

        .tab-nav-fb { display: flex; border-bottom: 1px solid var(--fb-border); background: var(--fb-bg-active); }

        .tab-fb { padding: 15px 25px; cursor: pointer; font-size: 0.85rem; font-weight: 600; color: var(--fb-text-dim); border-right: 1px solid var(--fb-border); transition: all 0.2s; }

        .tab-fb:hover { background: var(--fb-bg-hover); }

        .tab-fb.active { background: var(--fb-bg-card); color: var(--fb-reach); border-bottom: 2px solid var(--fb-reach); }

        .fb-table { width: 100%; border-collapse: collapse; text-align: left; }

        .fb-table th { padding: 12px 20px; font-size: 0.75rem; text-transform: uppercase; color: var(--fb-text-dim); font-weight: 700; border-bottom: 1px solid var(--fb-border); }

        .fb-table td { padding: 12px 20px; border-bottom: 1px solid var(--fb-border); vertical-align: middle; font-size: 0.9rem; color: var(--fb-text-main); }

        .fb-table tr:hover { background: var(--fb-bg-hover); }

        .metric-cell { text-align: right; }

        .fb-pagination-container { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; padding: 15px 25px; border-top: 1px solid var(--fb-border); background: var(--fb-bg-active); }

        .fb-pagination-text { font-size: 0.875rem; color: var(--fb-text-dim); }

        .fb-pagination-text strong { color: var(--fb-text-main); font-weight: 700; }

        .fb-pagination-select { background: var(--fb-bg-card); border: 1px solid var(--fb-border); color: var(--fb-text-main); font-size: 0.875rem; border-radius: 8px; padding: 8px 30px 8px 12px; outline: none; background-repeat: no-repeat; background-position: right; background-size: 32px; }

        .fb-pagination-btn { padding: 8px 16px; background: var(--fb-bg-card); border: 1px solid var(--fb-border); border-radius: 8px; font-size: 0.875rem; font-weight: 500; color: var(--fb-text-main); cursor: pointer; transition: background 0.2s; }

        .fb-pagination-btn:hover:not(:disabled) { background: var(--fb-bg-hover); }

        .fb-pagination-btn:disabled { opacity: 0.4; cursor: not-allowed; }

        .fb-pagination-badge { margin-left: 8px; padding: 4px 8px; background: var(--fb-bg-card); border-radius: 4px; font-size: 0.75rem; }

        /* Custom Modal Layout */
        .fb-modal-panel {
            display: flex;
            flex-direction: row;
            height: 85vh;
            min-height: 500px;
            max-height: 900px;
            width: 100%;
            max-width: 1152px; /* max-w-6xl */
        }

        .fb-modal-left {
            width: 380px;
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            min-height: 0;
            border-right: 1px solid var(--fb-border);
        }

        .fb-modal-right {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-width: 0;
            min-height: 0;
            overflow-y: auto;
        }

        .fb-modal-image-container {
            width: 100%;
            height: 280px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
        }

        @media (max-width: 768px) {
            .fb-modal-panel {
                flex-direction: column;
            }

            .fb-modal-left {
                width: 100%;
                border-right: none;
                border-bottom: 1px solid var(--fb-border);
            }
        }

        .fb-close-btn {
            position: absolute;
            top: 15px;
            right: 15px;
            z-index: 50;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: var(--fb-bg-card);
            border: 1px solid var(--fb-border);
            color: var(--fb-text-dim);
            cursor: pointer;
            transition: all 0.2s ease;
            backdrop-filter: blur(4px);
        }

        .fb-close-btn:hover {
            background: var(--fb-bg-hover);
            color: var(--fb-text-main);
            transform: scale(1.05);
        }

        .fb-modal-image-container {
            width: 100%;
            aspect-ratio: 4/5;
            border-radius: 8px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
        }
    </style>

    <div x-data="fboDashboard({
        tenantId: @js(Filament\Facades\Filament::getTenant()->id ?? Filament\Facades\Filament::getTenant()->slug),
        accounts: @js($selectedAccounts),
        accountNames: @js($accounts),
        dateStart: @js($dateStart),
        dateEnd: @js($dateEnd),
        activeTab: 'instagram',
        activeBreakdownTab: 'reaction_type',
        csrfToken: @js(csrf_token())
    })" x-init="initDashboard()">
        <div class="fb-header-row">
            <div>
                <h1 class="fb-header-title">
                    <x-heroicon-o-users class="w-8 h-8 text-[#1877F2]"/>
                    {{ __('FB & IG Insights') }}
                </h1>
            </div>
            <div class="fb-header-controls">
                <div class="flex items-center mr-4 gap-2">
                    <button type="button" 
                            class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2" 
                            :class="showTrends ? 'bg-primary-600' : 'bg-gray-200 dark:bg-gray-700'" 
                            @click="showTrends = !showTrends; handleTrendToggle()" 
                            role="switch" 
                            :aria-checked="showTrends.toString()">
                        <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out" 
                              :class="showTrends ? 'translate-x-5' : 'translate-x-0'"></span>
                    </button>
                    <span class="ms-2 text-sm font-medium text-gray-900 dark:text-gray-300 cursor-pointer" @click="showTrends = !showTrends; handleTrendToggle()">{{ __('Show Trends') }}</span>
                </div>
                <button type="button" @click="forceRefresh()"
                        class="flex items-center justify-center bg-primary-600 hover:bg-primary-500 text-white text-sm font-medium rounded-lg px-4 py-2.5 transition duration-75 shadow-sm"
                        :class="{ 'opacity-50 cursor-not-allowed': isSummaryLoading || isChartLoading || isTableLoading }"
                        :disabled="isSummaryLoading || isChartLoading || isTableLoading">
                    <x-heroicon-o-arrow-path class="w-5 h-5 mr-2"
                                             x-bind:class="{ 'animate-spin': isSummaryLoading || isChartLoading || isTableLoading }"/>
                    <span>{{ __('Update') }}</span>
                </button>
                <x-ui.asset-selector model="accounts[0]" options="accountNames" placeholder="{{ __('Select Page...') }}" change-event="handleAccountChange()" size="sm" />
                <x-ui.date-input x-model.lazy="dateStart" class="w-40" />
                <x-ui.date-input x-model.lazy="dateEnd" max="{{ date('Y-m-d', strtotime('-1 day')) }}" class="w-40" />
            </div>
        </div>

        <div class="tab-nav-fb"
             style="margin-bottom: 25px; border-radius: 8px; overflow: hidden; border: 1px solid var(--fb-border);">
            <div class="tab-fb" :class="activeTab === 'facebook' ? 'active' : ''"
                 @click="setTab('facebook')">{{ __('FACEBOOK PAGE') }}</div>
            <div class="tab-fb" :class="activeTab === 'instagram' ? 'active' : ''"
                 @click="setTab('instagram')">{{ __('INSTAGRAM ACCOUNT') }}</div>
        </div>

        <div class="metrics-grid-fb relative">
            <div x-show="isSummaryLoading"
                 class="absolute inset-0 z-10 flex items-center justify-center bg-white/50 dark:bg-gray-900/50 backdrop-blur-sm rounded-xl">
                <x-filament::loading-indicator class="h-8 w-8 text-primary-500"/>
            </div>

            <template x-for="metric in dynamicMetrics" :key="metric.key">
                <div class="card-stat-fb" :class="activeMetrics[metric.key] ? 'active' : ''"
                     @click="toggleMetric(metric.key)" :style="`--color: ${metric.color};`">
                    <div style="position: absolute; top: 12px; right: 12px;" class="text-primary-500 dark:text-primary-400" title="{{ __('Trend Analysis Supported') }}"
                         x-show="(activeTab === 'facebook' && ['reach', 'interactions'].includes(metric.key)) || (activeTab === 'instagram' && ['reach', 'saves', 'shares'].includes(metric.key))">
                        <x-heroicon-s-presentation-chart-line class="w-4 h-4 opacity-50" />
                    </div>
                    <div class="fb-label" x-text="metric.label"></div>
                    <div class="card-metric-value" x-text="formatNumber(metric.value)"></div>
                    <div class="card-metric-trend" :class="getVarianceClass(metric.variance)">
                        <span x-text="getVarianceIcon(metric.variance)"></span>
                        <span x-text="formatVariance(metric.variance)"></span>
                    </div>
                </div>
            </template>
        </div>

        <div class="chart-container-fb relative w-full" wire:ignore>
            <div x-show="isChartLoading"
                 class="absolute inset-0 z-10 flex items-center justify-center bg-white/50 dark:bg-gray-900/50 backdrop-blur-sm rounded-xl">
                <x-filament::loading-indicator class="h-8 w-8 text-primary-500"/>
            </div>
            <div style="position: relative; width: 100%; height: 100%; display: block;">
                <canvas x-ref="canvas"></canvas>
            </div>
        </div>

        <div x-show="hasAnyFilters"
             class="mb-4 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-3 shadow-sm"
             style="display: none;" x-transition>
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 flex items-center gap-2">
                    <x-heroicon-o-funnel class="w-4 h-4 text-primary-500"/>
                    {{ __('Active Breakdown Filters') }}
                </h3>
                <button @click="clearFilters()"
                        class="text-xs text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 font-medium">{{ __('Clear All') }}</button>
            </div>
            <div class="flex flex-wrap gap-2">
                <template x-for="tab in availableBreakdownTabs" :key="tab.value + '_chips'">
                    <template x-for="val in (activeFilters[tab.value] || [])" :key="tab.value + '_' + val">
                        <span
                            class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium bg-primary-100 text-primary-800 dark:bg-primary-900/40 dark:text-primary-300 border border-primary-200 dark:border-primary-800">
                            <span class="opacity-70 uppercase text-[10px] mr-1" x-text="tab.label + ':'"></span>
                            <span x-text="val"></span>
                            <button @click.stop="toggleFilter(tab.value, val)"
                                    class="ml-1 text-primary-600 hover:text-primary-900 dark:text-primary-400 dark:hover:text-primary-200">
                                <x-heroicon-m-x-mark class="w-3 h-3"/>
                            </button>
                        </span>
                    </template>
                </template>
            </div>
        </div>

        <div class="fb-table-container relative" style="margin-bottom: 20px;">
            <div x-show="isBreakdownTableLoading"
                 class="absolute inset-0 z-10 flex items-center justify-center bg-white/50 dark:bg-gray-900/50 backdrop-blur-sm rounded-xl">
                <x-filament::loading-indicator class="h-8 w-8 text-primary-500"/>
            </div>

            <div class="tab-nav-fb">
                <template x-for="tab in availableBreakdownTabs" :key="tab.value">
                    <div class="tab-fb" :class="activeBreakdownTab === tab.value ? 'active' : ''"
                         @click="setBreakdownTab(tab.value)" x-text="tab.label"></div>
                </template>
            </div>

            <div
                class="p-4 border-b border-gray-200 dark:border-white/5 bg-white dark:bg-transparent flex justify-between items-center">
                <h3 class="font-bold text-gray-800 dark:text-gray-100 uppercase"
                    x-text="((availableBreakdownTabs.find(tab => tab.value === activeBreakdownTab) || {}).label || '').toUpperCase()"></h3>
            </div>

            <div class="p-4 border-b border-gray-200 dark:border-white/5 bg-white dark:bg-transparent">
                <div class="relative w-full max-w-md">
                    <div
                        class="absolute inset-y-0 left-0 rtl:right-0 rtl:left-auto w-10 flex items-center justify-center pointer-events-none">
                        <x-heroicon-o-magnifying-glass class="w-4 h-4 text-gray-500 dark:text-gray-400"/>
                    </div>
                    <input type="text" x-model.debounce.300ms="breakdownSearchQuery"
                           class="bg-gray-50 dark:bg-white/5 border border-gray-300 dark:border-white/10 text-gray-900 dark:text-white text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full pl-10 p-2"
                           style="padding-left: 2.75rem;"
                           placeholder="{{ __('Filter breakdown values...') }}">
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="fb-table" style="min-width: 800px;">
                    <thead>
                    <tr>
                        <th>{{ __('BREAKDOWN VALUE') }}</th>
                        <template x-for="metricKey in availableBreakdownMetrics" :key="metricKey">
                            <th class="metric-cell cursor-pointer" @click="sortBreakdownBy(metricKey)">
                                <span x-text="getMetricInfo(metricKey).label"></span>
                                <span x-show="breakdownSortCol === metricKey"
                                      x-text="breakdownSortDir === 'desc' ? '↓' : '↑'"></span>
                            </th>
                        </template>
                    </tr>
                    </thead>
                    <tbody>
                    <template x-for="(row, index) in paginatedBreakdownData" :key="row.id + '_' + index">
                        <tr class="hover:bg-gray-50 dark:hover:bg-white/5 transition duration-150">
                            <td class="font-medium">
                                <div class="flex items-center gap-2">
                                    <span x-text="row.name"></span>
                                </div>
                            </td>
                            <template x-for="metricKey in availableBreakdownMetrics" :key="metricKey">
                                <td class="metric-cell" x-text="formatNumber(row[metricKey] || 0)"></td>
                            </template>
                        </tr>
                    </template>
                    <tr x-show="paginatedBreakdownData.length === 0">
                        <td :colspan="availableBreakdownMetrics.length + 1"
                            class="text-center py-8 text-gray-500 dark:text-gray-400">{{ __('No breakdown data available.') }}</td>
                    </tr>
                    </tbody>
                </table>
            </div>

            <div class="fb-pagination-container" x-show="breakdownDataRaw.length > 0">
                <div class="flex items-center gap-4 mb-4 sm:mb-0">
                    <span class="fb-pagination-text font-medium">{{ __('Rows per page:') }}</span>
                    <select x-model="breakdownPageSize" class="fb-pagination-select">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                        <option value="250">250</option>
                    </select>
                </div>
                <div class="flex items-center gap-6">
                    <span class="fb-pagination-text">
                        {{ __('Page') }} <strong x-text="breakdownCurrentPage"></strong> {{ __('of') }} <strong
                            x-text="breakdownTotalPages"></strong>
                        <span class="fb-pagination-badge">(<span x-text="breakdownDataRaw.length"></span> {{ __('results') }})</span>
                    </span>
                    <div class="flex gap-2">
                        <button @click="prevBreakdownPage()" :disabled="breakdownCurrentPage === 1"
                                class="fb-pagination-btn">{{ __('Prev') }}</button>
                        <button @click="nextBreakdownPage()" :disabled="breakdownCurrentPage === breakdownTotalPages"
                                class="fb-pagination-btn">{{ __('Next') }}</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="fb-table-container relative">
            <div x-show="isTableLoading"
                 class="absolute inset-0 z-10 flex items-center justify-center bg-white/50 dark:bg-gray-900/50 backdrop-blur-sm rounded-xl">
                <x-filament::loading-indicator class="h-8 w-8 text-primary-500"/>
            </div>

            <div
                class="p-4 border-b border-gray-200 dark:border-white/5 bg-white dark:bg-transparent flex justify-between items-center">
                <h3 class="font-bold text-gray-800 dark:text-gray-100 uppercase"
                    x-text="activeTab === 'facebook' ? '{{ __('Facebook Posts') }}' : '{{ __('Instagram Posts') }}'"></h3>
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
                <table class="fb-table" style="min-width: 800px;">
                    <thead>
                    <tr>
                        <th>{{ __('POST / PAGE') }}</th>
                        <template x-for="metricKey in availableTableMetrics" :key="metricKey">
                            <th class="metric-cell cursor-pointer" @click="sortBy(metricKey)">
                                <span x-text="getMetricInfo(metricKey).label"></span>
                                <span x-show="sortCol === metricKey" x-text="sortDir === 'desc' ? '↓' : '↑'"></span>
                            </th>
                        </template>
                    </tr>
                    </thead>
                    <tbody>
                    <template x-for="(row, index) in paginatedTableData" :key="row.id + '_' + index">
                        <tr @click="openPostModal(row.id)"
                            class="cursor-pointer hover:bg-gray-50 dark:hover:bg-white/5 transition duration-150">
                            <td class="font-medium">
                                <div class="flex items-center gap-2">
                                    <a x-show="row.permalink_url || row.permalink"
                                       :href="row.permalink_url || row.permalink" target="_blank"
                                       class="text-primary-500 hover:text-primary-700">
                                        <x-heroicon-o-link class="w-4 h-4"/>
                                    </a>
                                    <span x-text="row.name"></span>
                                </div>
                                <div x-show="row.media_type" class="text-xs text-gray-500 mt-1 uppercase"
                                     x-text="row.media_type"></div>
                            </td>
                            <template x-for="metricKey in availableTableMetrics" :key="metricKey">
                                <td class="metric-cell"
                                    x-text="(metricKey === 'ig_reels_avg_watch_time') || (metricKey === 'ig_reels_video_view_total_time')
                                    ? formatMetaDuration(row[metricKey] || 0)
                                    : formatNumber(row[metricKey] || 0)"></td>
                            </template>
                        </tr>
                    </template>
                    <tr x-show="paginatedTableData.length === 0">
                        <td :colspan="availableTableMetrics.length + 1"
                            class="text-center py-8 text-gray-500 dark:text-gray-400">{{ __('No data available.') }}</td>
                    </tr>
                    </tbody>
                </table>
            </div>

            <div class="fb-pagination-container" x-show="tableDataRaw.length > 0">
                <div class="flex items-center gap-4 mb-4 sm:mb-0">
                    <span class="fb-pagination-text font-medium">{{ __('Rows per page:') }}</span>
                    <select x-model="pageSize" class="fb-pagination-select">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                        <option value="250">250</option>
                    </select>
                </div>
                <div class="flex items-center gap-6">
                    <span class="fb-pagination-text">
                        {{ __('Page') }} <strong x-text="currentPage"></strong> {{ __('of') }} <strong
                            x-text="totalPages"></strong>
                        <span class="fb-pagination-badge">(<span x-text="tableDataRaw.length"></span> {{ __('results') }})</span>
                    </span>
                    <div class="flex gap-2">
                        <button @click="prevPage()" :disabled="currentPage === 1"
                                class="fb-pagination-btn">{{ __('Prev') }}</button>
                        <button @click="nextPage()" :disabled="currentPage === totalPages"
                                class="fb-pagination-btn">{{ __('Next') }}</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Post Details & History Modal -->
        <div x-show="isPostModalOpen"
             style="display: none;"
             class="fixed inset-0 z-[999] overflow-y-auto"
             aria-labelledby="modal-title"
             role="dialog"
             aria-modal="true"
             x-cloak>
            <!-- Background overlay -->
            <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-xl transition-opacity"
                 x-show="isPostModalOpen"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="closePostModal()"></div>

            <!-- Modal panel -->
            <div class="flex min-h-full items-center justify-center p-4 sm:p-8 text-center">
                <div x-show="isPostModalOpen"
                     x-transition:enter="ease-out duration-300"
                     x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave="ease-in duration-200"
                     x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     class="fb-modal-panel relative transform overflow-hidden rounded-xl bg-white dark:bg-gray-900 text-left shadow-xl transition-all border border-gray-200 dark:border-white/10">

                    <!-- Close Button -->
                    <button @click="closePostModal()" type="button" class="fb-close-btn">
                        <x-heroicon-o-x-mark class="w-5 h-5"/>
                    </button>

                    <!-- Left Side: Post Details & Media -->
                    <div class="fb-modal-left p-6 relative flex flex-col h-full overflow-y-auto">
                        <div x-show="isPostDetailsLoading" class="flex justify-center items-center py-12">
                            <x-filament::loading-indicator class="w-8 h-8 text-primary-500"/>
                        </div>

                        <div x-show="!isPostDetailsLoading && selectedPostData" class="flex flex-col flex-1 min-h-0 h-full">
                            <!-- Media Preview -->
                            <div class="fb-modal-image-container bg-gray-200 dark:bg-gray-950 relative shadow-inner">
                                <template x-if="(selectedPostData && selectedPostData.data) && (selectedPostData.data.media_url || selectedPostData.data.full_picture)">
                                    <div class="w-full h-full relative">
                                        <template x-if="selectedPostData.data.media_type === 'VIDEO' || (selectedPostData.data.media_url && selectedPostData.data.media_url.includes('.mp4')) || (selectedPostData.data.full_picture && selectedPostData.data.full_picture.includes('.mp4'))">
                                            <video :src="selectedPostData.data.media_url || selectedPostData.data.full_picture"
                                                   controls preload="metadata"
                                                   class="w-full h-full object-contain bg-black" muted loop playsinline></video>
                                        </template>
                                        <template x-if="!(selectedPostData.data.media_type === 'VIDEO' || (selectedPostData.data.media_url && selectedPostData.data.media_url.includes('.mp4')) || (selectedPostData.data.full_picture && selectedPostData.data.full_picture.includes('.mp4')))">
                                            <img :src="selectedPostData.data.media_url || selectedPostData.data.full_picture"
                                                 class="w-full h-full object-contain" alt="Post preview"/>
                                        </template>
                                    </div>
                                </template>
                                <template x-if="!((selectedPostData && selectedPostData.data) && (selectedPostData.data.media_url || selectedPostData.data.full_picture))">
                                    <div class="text-gray-400 dark:text-gray-500 flex flex-col items-center">
                                        <x-heroicon-o-photo class="w-12 h-12 mb-2 opacity-50"/>
                                        <span class="text-xs uppercase font-medium">{{ __('No Media') }}</span>
                                    </div>
                                </template>
                                <div x-show="selectedPostData && selectedPostData.data && selectedPostData.data.media_type"
                                     class="absolute top-2 left-2 bg-black/60 text-white text-[10px] uppercase font-bold px-2 py-1 rounded backdrop-blur-sm"
                                     x-text="selectedPostData && selectedPostData.data ? selectedPostData.data.media_type : ''"></div>
                            </div>

                            <!-- Caption & Details -->
                            <div class="flex-1 pr-2 mb-4">
                                <div class="text-xs text-gray-500 dark:text-gray-400 mb-2 font-medium"
                                     x-text="(selectedPostData && selectedPostData.data && (selectedPostData.data.created_time || selectedPostData.data.timestamp)) ? new Date(selectedPostData.data.created_time || selectedPostData.data.timestamp).toLocaleString() : ''"></div>
                                <div class="text-sm text-gray-800 dark:text-gray-100 whitespace-pre-line break-words"
                                     x-text="(selectedPostData && selectedPostData.data) ? (selectedPostData.data.message || selectedPostData.data.caption || '{{ __('No caption') }}') : ''"></div>
                            </div>

                            <!-- Actions -->
                            <div class="shrink-0 mt-auto pt-4 border-t border-gray-200 dark:border-gray-700">
                                <a :href="(selectedPostData && selectedPostData.data) ? (selectedPostData.data.permalink_url || selectedPostData.data.permalink) : '#'"
                                   target="_blank"
                                   x-show="selectedPostData && selectedPostData.data && (selectedPostData.data.permalink_url || selectedPostData.data.permalink)"
                                   class="inline-flex items-center justify-center w-full px-4 py-2 bg-primary-50 dark:bg-primary-900/20 text-primary-600 dark:text-primary-400 text-sm font-medium rounded-lg hover:bg-primary-100 dark:hover:bg-primary-900/40 transition-colors border border-primary-200 dark:border-primary-800/30">
                                    <x-heroicon-o-link class="w-4 h-4 mr-2"/>
                                    {{ __('View Original Post') }}
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Right Side: Metrics Chart -->
                    <div class="fb-modal-right p-6 relative flex flex-col h-full overflow-hidden">
                        <div class="mb-4 shrink-0">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ __('Post History') }}</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Historical timeline of metrics since publication') }}</p>
                        </div>

                        <div class="relative flex-1 min-h-0 min-w-0" style="position: relative; height: 100%; width: 100%;">
                            <div x-show="isPostChartLoading"
                                 class="absolute inset-0 z-10 flex items-center justify-center bg-white/50 dark:bg-gray-900/50 backdrop-blur-sm rounded-lg">
                                <x-filament::loading-indicator class="h-8 w-8 text-primary-500"/>
                            </div>
                            <canvas x-ref="postCanvas"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
