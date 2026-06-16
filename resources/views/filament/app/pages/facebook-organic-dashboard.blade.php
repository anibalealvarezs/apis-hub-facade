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
        }

        .fb-header-row { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 30px; }

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

    <div x-data="fboDashboard()" x-init="initDashboard()">
        <div class="fb-header-row">
            <div>
                <h1 class="fb-header-title">
                    <x-heroicon-o-users class="w-8 h-8 text-[#1877F2]"/>
                    {{ __('Meta Pages & Instagram Accounts') }}
                </h1>
            </div>
            <div class="fb-header-controls">
                <div class="flex items-center mr-4">
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
                <div class="relative">
                    <select
                        x-model="accounts[0]"
                        @change="accounts = $event.target.value ? [$event.target.value] : []"
                        class="bg-white dark:bg-white/5 border border-gray-300 dark:border-white/10 text-gray-950 dark:text-white text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full sm:w-64 md:w-72 px-4 py-2.5 h-[42px]"
                    >
                        @if(count($accounts) === 0)
                            <option value="" class="bg-white dark:bg-gray-800 text-gray-950 dark:text-white">{{ __('No pages available.') }}</option>
                        @else
                            @foreach($accounts as $id => $name)
                                <option value="{{ $id }}" class="bg-white dark:bg-gray-800 text-gray-950 dark:text-white">{{ $name }}</option>
                            @endforeach
                        @endif
                    </select>
                </div>
                <input type="date" x-model.lazy="dateStart"
                       class="bg-white dark:bg-white/5 border border-gray-300 dark:border-white/10 text-gray-950 dark:text-white text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-40 p-2.5">
                <input type="date" x-model.lazy="dateEnd" max="{{ date('Y-m-d') }}"
                       class="bg-white dark:bg-white/5 border border-gray-300 dark:border-white/10 text-gray-950 dark:text-white text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-40 p-2.5">
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
                    x-text="(availableBreakdownTabs.find(tab => tab.value === activeBreakdownTab)?.label || '').toUpperCase()"></h3>
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
             aria-modal="true">
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
                     class="fb-modal-panel relative transform overflow-hidden rounded-xl bg-white dark:bg-gray-900 text-left shadow-2xl transition-all border border-gray-200 dark:border-white/10">

                    <!-- Close Button -->
                    <button @click="closePostModal()" type="button" class="fb-close-btn">
                        <span class="sr-only">Close</span>
                        <x-heroicon-o-x-mark style="width: 20px; height: 20px;"/>
                    </button>

                    <!-- Left Side: Post Preview -->
                    <div class="fb-modal-left bg-gray-50 dark:bg-gray-800 p-6 relative h-full overflow-y-auto">

                        <div x-show="isPostDetailsLoading"
                             class="absolute inset-0 z-10 flex items-center justify-center bg-gray-50/80 dark:bg-black/50 backdrop-blur-sm rounded-l-xl">
                            <x-filament::loading-indicator class="h-8 w-8 text-primary-500"/>
                        </div>

                        <div x-show="!isPostDetailsLoading && selectedPostData"
                             class="flex flex-col flex-1 min-h-0 h-full">
                            <!-- Media Preview -->
                            <div class="fb-modal-image-container bg-gray-200 dark:bg-gray-950 relative shadow-inner">
                                <template
                                    x-if="selectedPostData?.data?.media_url || selectedPostData?.data?.full_picture">
                                    <div class="w-full h-full relative">
                                        <template
                                            x-if="selectedPostData?.data?.media_type === 'VIDEO' || (selectedPostData?.data?.media_url && selectedPostData.data.media_url.includes('.mp4')) || (selectedPostData?.data?.full_picture && selectedPostData.data.full_picture.includes('.mp4'))">
                                            <video
                                                :src="selectedPostData?.data?.media_url || selectedPostData?.data?.full_picture"
                                                controls preload="metadata"
                                                class="w-full h-full object-contain bg-black" muted loop
                                                playsinline></video>
                                        </template>
                                        <template
                                            x-if="!(selectedPostData?.data?.media_type === 'VIDEO' || (selectedPostData?.data?.media_url && selectedPostData.data.media_url.includes('.mp4')) || (selectedPostData?.data?.full_picture && selectedPostData.data.full_picture.includes('.mp4')))">
                                            <img
                                                :src="selectedPostData?.data?.media_url || selectedPostData?.data?.full_picture"
                                                class="w-full h-full object-contain" alt="Post preview"/>
                                        </template>
                                    </div>
                                </template>
                                <template
                                    x-if="!(selectedPostData?.data?.media_url || selectedPostData?.data?.full_picture)">
                                    <div class="text-gray-400 dark:text-gray-500 flex flex-col items-center">
                                        <x-heroicon-o-photo class="w-12 h-12 mb-2 opacity-50"/>
                                        <span class="text-xs uppercase font-medium">{{ __('No Media') }}</span>
                                    </div>
                                </template>
                                <div x-show="selectedPostData?.data?.media_type"
                                     class="absolute top-2 left-2 bg-black/60 text-white text-[10px] uppercase font-bold px-2 py-1 rounded backdrop-blur-sm"
                                     x-text="selectedPostData?.data?.media_type"></div>
                            </div>

                            <!-- Post Details -->
                            <div class="flex-1 pr-2 mb-4">
                                <div class="text-xs text-gray-500 dark:text-gray-400 mb-2 font-medium"
                                     x-text="(selectedPostData?.data?.created_time || selectedPostData?.data?.timestamp) ? new Date(selectedPostData?.data?.created_time || selectedPostData?.data?.timestamp).toLocaleString() : ''"></div>
                                <div class="text-sm text-gray-800 dark:text-gray-100 whitespace-pre-line break-words"
                                     x-text="selectedPostData?.data?.message || selectedPostData?.data?.caption || '{{ __('No caption') }}'"></div>
                            </div>

                            <!-- Actions -->
                            <div class="shrink-0 mt-auto pt-4 border-t border-gray-200 dark:border-gray-700">
                                <a :href="selectedPostData?.data?.permalink_url || selectedPostData?.data?.permalink"
                                   target="_blank"
                                   x-show="selectedPostData?.data?.permalink_url || selectedPostData?.data?.permalink"
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

                        <div class="relative flex-1 min-h-0 min-w-0"
                             style="position: relative; height: 100%; width: 100%;">
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

    <script>
        (function () {
            const registerFboDashboard = () => {
                Alpine.data('fboDashboard', () => {
                    return {
                        tenantId: '{{ Filament\Facades\Filament::getTenant()->id ?? Filament\Facades\Filament::getTenant()->slug }}',
                        accounts: @json($selectedAccounts),
                        dateStart: '{{ $dateStart }}',
                        dateEnd: '{{ $dateEnd }}',
                        activeTab: 'instagram',
                        activeBreakdownTab: 'reaction_type',

                        isSummaryLoading: false,
                        isChartLoading: false,
                        isTableLoading: false,
                        isBreakdownTableLoading: false,

                        summaryRaw: {},
                        previousRaw: {},
                        chartDataRaw: [],
                        tableDataRaw: [],
                        breakdownDataRaw: [],
                        isPostModalOpen: false,
                        selectedPost: null,
                        isPostChartLoading: false,
                        postChartDataRaw: [],
                        isPostDetailsLoading: false,

                        showTrends: false,
                        trendData: {},

                        metricDictionary: {
                            'reach': {label: '{{ __('Reach') }}', color: 'var(--fb-reach)'},
                            'total_interactions': {label: '{{ __('Interactions') }}', color: 'var(--fb-interactions)'},
                            'interactions': {label: '{{ __('Interactions') }}', color: 'var(--fb-interactions)'},
                            'likes': {label: '{{ __('Likes') }}', color: 'var(--fb-likes)'},
                            'comments': {label: '{{ __('Comments') }}', color: 'var(--fb-comments)'},
                            'views': {label: '{{ __('Views') }}', color: 'var(--fb-views)'},
                            'content_views': {label: '{{ __('Content Views') }}', color: 'var(--fb-views)'},
                            'video_views': {label: '{{ __('Video Views') }}', color: 'var(--fb-views)'},
                            'page_views_total': {label: '{{ __('Page Views') }}', color: 'var(--fb-views)'},
                            'follows_and_unfollows': {label: '{{ __('Follows') }}', color: 'var(--fb-follows)'},
                            'follows': {label: '{{ __('Follows') }}', color: 'var(--fb-follows)'},
                            'profile_views': {label: '{{ __('Profile Views') }}', color: '#14B8A6'},
                            'website_clicks': {label: '{{ __('Website Clicks') }}', color: '#06B6D4'},
                            'profile_links_taps': {label: '{{ __('Link Taps') }}', color: '#3B82F6'},
                            'saves': {label: '{{ __('Saves') }}', color: '#8B5CF6'},
                            'saved': {label: '{{ __('Saved') }}', color: '#8B5CF6'},
                            'shares': {label: '{{ __('Shares') }}', color: '#D946EF'},
                            'replies': {label: '{{ __('Replies') }}', color: '#F43F5E'},
                            'accounts_engaged': {label: '{{ __('Accounts Engaged') }}', color: '#F97316'},
                            'post_clicks': {label: '{{ __('Post Clicks') }}', color: '#06B6D4'},
                            'post_video_avg_time_watched': {label: '{{ __('Avg Watch Time') }}', color: '#EAB308'},
                            'ig_reels_avg_watch_time': {label: '{{ __('Reels Avg Time') }}', color: '#EAB308'},
                            'ig_reels_video_view_total_time': {label: '{{ __('Reels Total Time') }}', color: '#F59E0B'},
                            'profile_activity': {label: '{{ __('Profile Activity') }}', color: '#10B981'},
                            'profile_visits': {label: '{{ __('Profile Visits') }}', color: '#14B8A6'},
                            'reposts': {label: '{{ __('Reposts') }}', color: '#8B5CF6'}
                        },

                        activeMetrics: {},

                        searchQuery: '',
                        sortCol: 'reach',
                        sortDir: 'desc',
                        breakdownSearchQuery: '',
                        breakdownSortCol: 'reach',
                        breakdownSortDir: 'desc',
                        activeFilters: {
                            reaction_type: [],
                            contact_button_type: [],
                            follow_type: [],
                            media_product_type: [],
                        },

                        currentPage: 1,
                        pageSize: 10,
                        breakdownCurrentPage: 1,
                        breakdownPageSize: 10,

                        restoreFromUrl() {
                            const params = new URLSearchParams(window.location.search);
                            const accParam = params.get('accounts');
                            if (accParam) {
                                const ids = accParam.split(',').filter(id => id);
                                if (ids.length > 0) this.accounts = ids;
                            }
                            const ds = params.get('dateStart');
                            if (ds && /^\d{4}-\d{2}-\d{2}$/.test(ds)) this.dateStart = ds;
                            const de = params.get('dateEnd');
                            if (de && /^\d{4}-\d{2}-\d{2}$/.test(de)) this.dateEnd = de;
                            const tab = params.get('tab');
                            if (tab && ['facebook', 'instagram'].includes(tab)) this.activeTab = tab;
                        },

                        syncToUrl() {
                            const params = new URLSearchParams();
                            if (this.accounts.length > 0) params.set('accounts', this.accounts.join(','));
                            if (this.dateStart) params.set('dateStart', this.dateStart);
                            if (this.dateEnd) params.set('dateEnd', this.dateEnd);
                            if (this.activeTab) params.set('tab', this.activeTab);
                            const qs = params.toString();
                            const url = qs ? window.location.pathname + '?' + qs : window.location.pathname;
                            history.replaceState(null, '', url);
                        },

                        initDashboard() {
                            this.restoreFromUrl();

                            const boot = () => {
                                this.initChart();

                                this.$watch('accounts', () => {
                                    this.syncToUrl();
                                    this.trendData = {};
                                    this.fetchAll();
                                });

                                this.$watch('dateStart', () => {
                                    this.syncToUrl();
                                    this.trendData = {};
                                    this.fetchAll();
                                });

                                this.$watch('dateEnd', () => {
                                    this.syncToUrl();
                                    this.trendData = {};
                                    this.fetchAll();
                                });
                                this.$watch('pageSize', () => {
                                    this.currentPage = 1;
                                });
                                this.$watch('breakdownPageSize', () => {
                                    this.breakdownCurrentPage = 1;
                                });

                                if (this.accounts.length > 0 && this.dateStart && this.dateEnd) {
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
                                }).catch(err => console.error("Failed to load charting libraries", err));
                            }
                        },

                        setTab(tab) {
                            this.activeTab = tab;
                            this.currentPage = 1;
                            this.breakdownCurrentPage = 1;
                            this.searchQuery = '';
                            this.breakdownSearchQuery = '';
                            this.activeMetrics = {};
                            this.chartDataRaw = [];
                            this.tableDataRaw = [];
                            this.breakdownDataRaw = [];
                            this.activeBreakdownTab = this.availableBreakdownTabs[0]?.value || '';
                            Object.keys(this.activeFilters).forEach((key) => {
                                if (!this.availableBreakdownTabs.some(t => t.value === key)) {
                                    this.activeFilters[key] = [];
                                }
                            });
                            this.syncToUrl();
                            this.clearCache();
                            this.fetchAll(); // Refetch everything since metrics depend on the tab
                        },

                        syncActiveMetricsFromSummary() {
                            Object.keys(this.summaryRaw || {}).forEach((key) => {
                                if (this.activeMetrics[key] === undefined) {
                                    this.activeMetrics[key] = true;
                                }
                            });
                        },

                        syncActiveMetricsFromChart() {
                            const firstRow = Array.isArray(this.chartDataRaw) ? this.chartDataRaw[0] : null;
                            if (!firstRow || typeof firstRow !== 'object') {
                                return;
                            }

                            const ignoredKeys = ['daily', 'date', 'metric_date', 'id', 'name'];
                            Object.keys(firstRow).forEach((rawKey) => {
                                if (ignoredKeys.includes(rawKey)) {
                                    return;
                                }

                                const key = rawKey.startsWith('trend_total_') ? rawKey.replace('trend_total_', '') : rawKey;
                                if (this.activeMetrics[key] === undefined) {
                                    this.activeMetrics[key] = true;
                                }
                            });
                        },

                        get availableBreakdownTabs() {
                            return this.activeTab === 'facebook'
                                ? [{value: 'reaction_type', label: '{{ __('Reaction type') }}'}]
                                : [
                                    {value: 'contact_button_type', label: '{{ __('Contact button type') }}'},
                                    {value: 'follow_type', label: '{{ __('Follow type') }}'},
                                    {value: 'media_product_type', label: '{{ __('Media product type') }}'},
                                ];
                        },

                        get hasAnyFilters() {
                            return Object.values(this.activeFilters).some((values) => Array.isArray(values) && values.length > 0);
                        },

                        setBreakdownTab(tab) {
                            this.activeBreakdownTab = tab;
                            this.breakdownCurrentPage = 1;
                            this.fetchBreakdownTable();
                        },

                        toggleFilter(tab, value) {
                            if (!tab || value === undefined || value === null || value === '') {
                                return;
                            }

                            if (!Array.isArray(this.activeFilters[tab])) {
                                this.activeFilters[tab] = [];
                            }

                            const normalized = String(value);
                            const existingIndex = this.activeFilters[tab].indexOf(normalized);
                            if (existingIndex >= 0) {
                                this.activeFilters[tab].splice(existingIndex, 1);
                            } else {
                                this.activeFilters[tab].push(normalized);
                            }

                            this.currentPage = 1;
                            this.fetchAll();
                        },

                        isFilterActive(tab, value) {
                            const values = this.activeFilters[tab] || [];
                            return values.includes(String(value));
                        },

                        clearFilters() {
                            Object.keys(this.activeFilters).forEach((key) => {
                                this.activeFilters[key] = [];
                            });
                            this.currentPage = 1;
                            this.fetchAll();
                        },

                        forceRefresh() {
                            this.clearCache();
                            this.trendData = {};
                            this.fetchAll();
                        },

                        safeCacheSet(key, value) {
                            try {
                                sessionStorage.setItem(key, value);
                                return true;
                            } catch (e) {
                                if (e.name === 'QuotaExceededError' || e.code === 22) {
                                    // Evict all fbo cache entries (old tenants/dates) and retry
                                    Object.keys(sessionStorage).forEach(k => {
                                        if (k.startsWith('fbo_')) {
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
                            const accountKey = this.accounts.join('_');
                            const prefix = `fbo_${this.tenantId}_${accountKey}_${this.dateStart}_${this.dateEnd}`;
                            Object.keys(sessionStorage).forEach(key => {
                                if (key.startsWith(prefix)) {
                                    sessionStorage.removeItem(key);
                                }
                            });
                        },

                        getCacheKey(endpoint) {
                            const accountKey = this.accounts.join('_');
                            const breakdownTab = this.activeBreakdownTab || 'none';
                            const filtersKey = Object.keys(this.activeFilters)
                                .sort()
                                .map(key => `${key}:${(this.activeFilters[key] || []).join(',')}`)
                                .join('|');
                            return `fbo_${this.tenantId}_${accountKey}_${this.dateStart}_${this.dateEnd}_${endpoint}_${this.activeTab}_${breakdownTab}_${filtersKey}_v4`;
                        },

                        getPostsCacheKey() {
                            const accountKey = this.accounts.join('_');
                            return `fbo_${this.tenantId}_${accountKey}_${this.dateStart}_${this.dateEnd}_posts_${this.activeTab}_v2`;
                        },

                        async fetchAll() {
                            if (!this.accounts.length || !this.dateStart || !this.dateEnd) return;
                            await this.fetchSummary();
                            await this.fetchChart();
                            await this.fetchTable();
                            await this.fetchBreakdownTable();
                        },

                        openPostModal(postId) {
                            const normalizedPostId = String(postId ?? '').trim();
                            if (!normalizedPostId) {
                                return;
                            }

                            this.selectedPost = {id: normalizedPostId};
                            this.isPostModalOpen = true;
                            // Prevent body scrolling
                            document.body.style.overflow = 'hidden';

                            // Wait for modal to render to get canvas, then init chart
                            this.$nextTick(() => {
                                if (!this.getPostChartInstance()) {
                                    this.initPostChart();
                                }
                                this.fetchPostChart(normalizedPostId);
                                this.fetchPostDetails(normalizedPostId);
                            });
                        },

                        closePostModal() {
                            this.isPostModalOpen = false;
                            this.selectedPost = null;
                            this.selectedPostData = null;
                            document.body.style.overflow = '';
                            const chart = this.getPostChartInstance();
                            if (chart) {
                                chart.destroy();
                                this.setPostChartInstance(null);
                            }
                        },

                        getPostChartInstance() {
                            return this.$refs.postCanvas?._chartInstance || null;
                        },

                        setPostChartInstance(instance) {
                            if (this.$refs.postCanvas) {
                                this.$refs.postCanvas._chartInstance = instance;
                            }
                        },

                        initPostChart() {
                            const ctx = this.$refs.postCanvas.getContext('2d');

                            Chart.defaults.color = document.documentElement.classList.contains('dark') ? '#94A3B8' : '#6B7280';
                            Chart.defaults.font.family = "'Inter', sans-serif";

                            const chart = new Chart(ctx, {
                                type: 'line',
                                data: {labels: [], datasets: []},
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    interaction: {mode: 'index', intersect: false},
                                    plugins: {
                                        legend: {
                                            display: true,
                                            position: 'bottom',
                                            labels: {
                                                usePointStyle: true,
                                                boxWidth: 8,
                                            }
                                        },
                                        tooltip: {
                                            backgroundColor: 'rgba(15, 23, 42, 0.9)',
                                            titleColor: '#FFF',
                                            bodyColor: '#E2E8F0',
                                            borderColor: 'rgba(255,255,255,0.1)',
                                            borderWidth: 1,
                                            padding: 12,
                                            boxPadding: 6,
                                            usePointStyle: true,
                                            callbacks: {
                                                label: function(context) {
                                                    var label = context.dataset.label || '';
                                                    var value = context.parsed.y;
                                                    return label + ': ' + Number(value).toLocaleString('en-US');
                                                }
                                            }
                                        }
                                    },
                                    scales: {
                                        x: {
                                            grid: {color: 'var(--fb-chart-grid)', drawBorder: false},
                                            ticks: {color: 'var(--fb-chart-ticks)'}
                                        }
                                    }
                                }
                            });
                            
                            this.setPostChartInstance(chart);
                            this.applyPostChartTheme();
                        },

                        applyPostChartTheme() {
                            const chart = this.getPostChartInstance();
                            if (!chart) return;

                            const isDark = document.documentElement.classList.contains('dark');
                            const cssGridColor = getComputedStyle(document.documentElement).getPropertyValue('--fb-chart-grid').trim() || (isDark ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.05)');
                            const cssTicksColor = getComputedStyle(document.documentElement).getPropertyValue('--fb-chart-ticks').trim() || (isDark ? '#94A3B8' : '#6B7280');

                            chart.options.plugins.legend.labels.color = cssTicksColor;
                            chart.options.scales.x.grid.color = cssGridColor;
                            chart.options.scales.x.ticks.color = cssTicksColor;
                            chart.options.plugins.tooltip.backgroundColor = isDark ? 'rgba(15, 23, 42, 0.92)' : 'rgba(255, 255, 255, 0.96)';
                            chart.options.plugins.tooltip.titleColor = isDark ? '#FFF' : '#111827';
                            chart.options.plugins.tooltip.bodyColor = isDark ? '#E2E8F0' : '#1F2937';
                        },

                        async fetchPostChart(postId) {
                            this.isPostChartLoading = true;
                            try {
                                const normalizedPostId = String(postId ?? '').trim();
                                if (!normalizedPostId) {
                                    return;
                                }

                                const payload = {
                                    tenant: this.tenantId,
                                    account: this.accounts,
                                    dateStart: this.dateStart,
                                    dateEnd: this.dateEnd,
                                    activeTab: this.activeTab,
                                    activeFilters: this.activeFilters,
                                    postId: normalizedPostId
                                };

                                const response = await fetch('/api/fbo/chart', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                    },
                                    body: JSON.stringify(payload)
                                });

                                const data = await response.json();
                                this.postChartDataRaw = data.chart || [];
                                this.renderPostChart();
                            } catch (err) {
                                console.error('Failed to fetch post chart:', err);
                            } finally {
                                this.isPostChartLoading = false;
                            }
                        },

                        async fetchPostDetails(postId) {
                            this.isPostDetailsLoading = true;
                            this.selectedPostData = null;
                            try {
                                const payload = {
                                    tenant: this.tenantId,
                                    postId: postId
                                };

                                const response = await fetch('/api/fbo/post', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                    },
                                    body: JSON.stringify(payload)
                                });

                                const data = await response.json();
                                this.selectedPostData = data.post || null;
                            } catch (err) {
                                console.error('Failed to fetch post details:', err);
                            } finally {
                                this.isPostDetailsLoading = false;
                            }
                        },

                        renderPostChart() {
                            const chart = this.getPostChartInstance();
                            if (!chart) return;

                            const raw = this.postChartDataRaw;
                            if (!raw || raw.length === 0) {
                                chart.data.labels = [];
                                chart.data.datasets = [];
                                chart.update();
                                return;
                            }

                            raw.sort((a, b) => new Date(a.daily) - new Date(b.daily));
                            const labels = raw.map(row => dayjs(row.daily).format('MMM D'));

                            const datasets = [];
                            const addDataset = (key) => {
                                const dataPoints = raw.map(row => parseFloat(row[key] || row['trend_total_' + key] || 0));
                                if (dataPoints.some(v => v > 0)) {
                                    const info = this.getMetricInfo(key);
                                    const resolvedColor = this.getComputedColor(info.color);
                                    const scaleId = 'y' + key.replace(/[^a-zA-Z0-9]/g, '_');
                                    datasets.push({
                                        label: info.label,
                                        data: dataPoints,
                                        borderColor: resolvedColor,
                                        backgroundColor: resolvedColor + '20',
                                        borderWidth: 2,
                                        pointRadius: 0,
                                        pointHoverRadius: 6,
                                        fill: true,
                                        yAxisID: scaleId,
                                        tension: 0.4
                                    });
                                }
                            };

                            // Dynamically add datasets for any metric returned in the chart payload
                            if (raw.length > 0) {
                                const firstRow = raw[0];
                                const ignoredKeys = ['daily', 'id', 'name'];
                                const metricsInChart = Object.keys(firstRow).filter(k => !ignoredKeys.includes(k));

                                metricsInChart.forEach(key => {
                                    const actualKey = key.startsWith('trend_total_') ? key.replace('trend_total_', '') : key;
                                    addDataset(actualKey);
                                });
                            }

                            // Remove old y-scales (keep x)
                            Object.keys(chart.options.scales).forEach(sid => {
                                if (sid !== 'x') delete chart.options.scales[sid];
                            });

                            let gridDrawn = false;
                            const cssGridColor = getComputedStyle(document.documentElement).getPropertyValue('--fb-chart-grid').trim() || 'rgba(0,0,0,0.05)';
                            const cssTicksColor = getComputedStyle(document.documentElement).getPropertyValue('--fb-chart-ticks').trim() || '#6B7280';

                            datasets.forEach((ds, i) => {
                                const scaleId = ds.yAxisID;
                                chart.options.scales[scaleId] = {
                                    type: 'linear',
                                    display: true,
                                    beginAtZero: true,
                                    grid: {drawOnChartArea: !gridDrawn, color: cssGridColor, drawBorder: false},
                                    position: i % 2 === 0 ? 'left' : 'right',
                                    ticks: {display: false, color: ds.borderColor}
                                };
                                gridDrawn = true;
                            });

                            chart.data.labels = labels;
                            chart.data.datasets = datasets;
                            this.applyPostChartTheme();
                            chart.update();
                        },

                        async fetchSummary() {
                            if (!this.accounts.length || !this.dateStart || !this.dateEnd) return;
                            const cacheKey = this.getCacheKey('summary');

                            if (sessionStorage.getItem(cacheKey)) {
                                const data = JSON.parse(sessionStorage.getItem(cacheKey));
                                this.summaryRaw = data.summary || {};
                                this.previousRaw = data.previous || {};
                                this.syncActiveMetricsFromSummary();
                                return;
                            }

                            this.isSummaryLoading = true;
                            try {
                                const response = await fetch('/api/fbo/summary', this.getFetchOptions());
                                const data = await response.json();
                                if (!data.error) {
                                    this.safeCacheSet(cacheKey, JSON.stringify(data));
                                    this.summaryRaw = data.summary || {};
                                    this.previousRaw = data.previous || {};
                                    this.syncActiveMetricsFromSummary();
                                }
                            } catch (error) {
                                console.error('Error fetching summary:', error);
                            } finally {
                                this.isSummaryLoading = false;
                            }
                        },

                        async fetchTrends() {
                            if (!this.showTrends || !this.chartDataRaw || this.chartDataRaw.length === 0) return;
                            
                            const activeKeys = Object.keys(this.activeMetrics).filter(k => this.activeMetrics[k]);
                            
                            const allowedTrendMetrics = {
                                'facebook': ['reach', 'interactions'],
                                'instagram': ['reach', 'saves', 'shares']
                            };
                            
                            const validMetrics = activeKeys.filter(m => (allowedTrendMetrics[this.activeTab] || []).includes(m));
                            
                            if (validMetrics.length === 0) {
                                this.updateChart();
                                return;
                            }
                            
                            this.isChartLoading = true;
                            
                            try {
                                const promises = validMetrics.map(async (metric) => {
                                    const seriesDates = this.chartDataRaw.map(r => r.daily || r.date).filter(Boolean);
                                    const seriesValues = this.chartDataRaw.map(r => r[metric] || r['trend_total_' + metric] || r['trend_average_' + metric] || 0);
                                    
                                    const payload = {
                                        tenant: this.tenantId,
                                        metric: metric,
                                        series: {
                                            dates: seriesDates,
                                            values: seriesValues
                                        },
                                        activeTab: this.activeTab
                                    };
                                    
                                    const response = await fetch('/api/fbo/trend', {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/json',
                                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                        },
                                        body: JSON.stringify(payload)
                                    });
                                    const data = await response.json();
                                    if(data.trend) {
                                        this.trendData[metric] = data.trend;
                                    }
                                });
                                
                                await Promise.all(promises);
                                this.updateChart();
                            } catch (error) {
                                console.error('Error fetching trends:', error);
                            } finally {
                                this.isChartLoading = false;
                            }
                        },

                        handleTrendToggle() {
                            if (this.showTrends) {
                                this.fetchTrends();
                            } else {
                                this.updateChart();
                            }
                        },

                        async fetchChart() {
                            if (!this.accounts.length || !this.dateStart || !this.dateEnd) return;
                            const cacheKey = this.getCacheKey('chart');

                            if (sessionStorage.getItem(cacheKey)) {
                                const data = JSON.parse(sessionStorage.getItem(cacheKey));
                                this.chartDataRaw = data.chart || [];
                                this.syncActiveMetricsFromChart();
                                this.updateChart();
                                return;
                            }

                            this.isChartLoading = true;
                            try {
                                const response = await fetch('/api/fbo/chart', this.getFetchOptions());
                                const data = await response.json();
                                if (!data.error) {
                                    this.safeCacheSet(cacheKey, JSON.stringify(data));
                                    this.chartDataRaw = data.chart || [];
                                    this.syncActiveMetricsFromChart();
                                    this.updateChart();
                                }
                            } catch (error) {
                                console.error('Error fetching chart:', error);
                            } finally {
                                this.isChartLoading = false;
                            }
                        },

                        async fetchTable() {
                            if (!this.accounts.length || !this.dateStart || !this.dateEnd) return;
                            const cacheKey = this.getPostsCacheKey();

                            if (sessionStorage.getItem(cacheKey)) {
                                const data = JSON.parse(sessionStorage.getItem(cacheKey));
                                this.tableDataRaw = data.table || [];
                                return;
                            }

                            this.isTableLoading = true;
                            try {
                                const response = await fetch('/api/fbo/table', this.getFetchOptions({tableMode: 'posts'}));
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

                        async fetchBreakdownTable() {
                            if (!this.accounts.length || !this.dateStart || !this.dateEnd) return;
                            const cacheKey = this.getCacheKey('breakdown_table');

                            if (sessionStorage.getItem(cacheKey)) {
                                const data = JSON.parse(sessionStorage.getItem(cacheKey));
                                this.breakdownDataRaw = data.table || [];
                                return;
                            }

                            this.isBreakdownTableLoading = true;
                            try {
                                const response = await fetch('/api/fbo/table', this.getFetchOptions({tableMode: 'breakdown'}));
                                const data = await response.json();
                                if (!data.error) {
                                    this.safeCacheSet(cacheKey, JSON.stringify(data));
                                    this.breakdownDataRaw = data.table || [];
                                    this.breakdownCurrentPage = 1;
                                }
                            } catch (error) {
                                console.error('Error fetching breakdown table:', error);
                            } finally {
                                this.isBreakdownTableLoading = false;
                            }
                        },

                        getFetchOptions(extraPayload = {}) {
                            const payload = {
                                tenant: this.tenantId,
                                account: this.accounts,
                                dateStart: this.dateStart,
                                dateEnd: this.dateEnd,
                                activeTab: this.activeTab,
                                activeFilters: this.activeFilters,
                                breakdownTab: this.activeBreakdownTab || null,
                                ...extraPayload
                            };

                            return {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                },
                                body: JSON.stringify(payload)
                            };
                        },

                        getMetricInfo(key) {
                            return this.metricDictionary[key] || {
                                label: key.replace(/_/g, ' ').toUpperCase(),
                                color: '#6B7280'
                            };
                        },

                        getComputedColor(colorVal) {
                            if (typeof colorVal === 'string' && colorVal.startsWith('var(')) {
                                const match = colorVal.match(/var\(([^)]+)\)/);
                                if (match) {
                                    return getComputedStyle(document.documentElement).getPropertyValue(match[1]).trim() || '#6B7280';
                                }
                            }
                            return colorVal;
                        },

                        get dynamicMetrics() {
                            const metrics = [];
                            const calcVariance = (current, prev) => {
                                if (!prev || Number(prev) === 0) return 0;
                                return ((Number(current) - Number(prev)) / Number(prev)) * 100;
                            };

                            for (const key in this.summaryRaw) {
                                // Initialize activeMetrics to true for newly discovered keys
                                if (this.activeMetrics[key] === undefined) {
                                    this.activeMetrics[key] = true;
                                }

                                const val = this.summaryRaw[key] || 0;
                                const prev = this.previousRaw[key] || 0;
                                const info = this.getMetricInfo(key);
                                metrics.push({
                                    key: key,
                                    label: info.label,
                                    color: info.color,
                                    value: val,
                                    prevValue: prev,
                                    variance: calcVariance(val, prev)
                                });
                            }
                            return metrics;
                        },

                        get availableTableMetrics() {
                            if (this.tableDataRaw.length === 0) return [];
                            const firstRow = this.tableDataRaw[0];
                            const ignoredKeys = ['id', 'name', 'page', 'page_id', 'page_title', 'channeledaccount', 'channeled_account_id', 'post_id', 'caption', 'message', 'media_type', 'permalink', 'permalink_url', 'timestamp', 'created_time', 'daily'];
                            return Object.keys(firstRow).filter(key => !ignoredKeys.includes(key.toLowerCase()) && !key.startsWith('trend_total_'));
                        },

                        get availableBreakdownMetrics() {
                            if (this.breakdownDataRaw.length === 0) return [];
                            const firstRow = this.breakdownDataRaw[0];
                            const ignoredKeys = ['id', 'name', 'daily'];
                            return Object.keys(firstRow).filter(key => !ignoredKeys.includes(key.toLowerCase()) && !key.startsWith('trend_total_'));
                        },

                        toggleMetric(key) {
                            this.activeMetrics[key] = !this.activeMetrics[key];
                            this.updateChart();
                        },

                        getVarianceClass(val) {
                            if (val === 0) return 'trend-neutral';
                            return val > 0 ? 'trend-up' : 'trend-down';
                        },

                        getVarianceIcon(val) {
                            if (val === 0) return '-';
                            return val > 0 ? '↑' : '↓';
                        },

                        formatVariance(val) {
                            if (val === 0) return '0%';
                            return Math.abs(val).toFixed(1) + '%';
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
                                            usePointStyle: true,
                                            callbacks: {
                                                label: function(context) {
                                                    var label = context.dataset.label || '';
                                                    var value = context.parsed.y;
                                                    return label + ': ' + Number(value).toLocaleString('en-US');
                                                }
                                            }
                                        }
                                    },
                                    scales: {
                                        x: {
                                            grid: {color: 'var(--fb-chart-grid)', drawBorder: false},
                                            ticks: {color: 'var(--fb-chart-ticks)'}
                                        },
                                        yReach: {
                                            type: 'linear',
                                            position: 'left',
                                            display: false,
                                            grid: {color: 'var(--fb-chart-grid)', drawBorder: false},
                                            ticks: {color: '#10B981'}
                                        },
                                        yInteractions: {
                                            type: 'linear',
                                            position: 'right',
                                            display: false,
                                            grid: {drawOnChartArea: false, drawBorder: false},
                                            ticks: {color: '#6366F1'}
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
                                if (r && (r.daily || r.date)) {
                                    const dateStr = dayjs(r.daily || r.date).format('YYYY-MM-DD');
                                    dataByDate[dateStr] = r;
                                }
                            });

                            const paddedData = fullDateRange.map(dateStr => {
                                let obj = {daily: dateStr};
                                if (dataByDate[dateStr]) {
                                    const r = dataByDate[dateStr];
                                    Object.keys(this.metricDictionary).forEach(k => {
                                        obj[k] = parseFloat(r['trend_total_' + k] || r[k] || 0);
                                    });
                                } else {
                                    Object.keys(this.metricDictionary).forEach(k => obj[k] = 0);
                                }
                                return obj;
                            });

                            const labels = paddedData.map(r => dayjs(r.daily).format('MMM D'));
                            const datasets = [];

                            const activeKeys = Object.keys(this.activeMetrics).filter(k => this.activeMetrics[k]);

                            activeKeys.forEach(key => {
                                const info = this.getMetricInfo(key);
                                const data = paddedData.map(r => r[key]);

                                if (data.some(v => v > 0)) {
                                    const resolvedColor = this.getComputedColor(info.color);
                                    datasets.push({
                                        label: info.label,
                                        data: data,
                                        borderColor: resolvedColor,
                                        backgroundColor: resolvedColor + '20',
                                        borderWidth: 2,
                                        pointRadius: 0,
                                        pointHoverRadius: 6,
                                        fill: true,
                                        yAxisID: 'y' + key,
                                        tension: 0.4
                                    });
                                }
                            });

                            if (this.showTrends) {
                                activeKeys.forEach(key => {
                                    if (this.trendData[key]) {
                                        const info = this.getMetricInfo(key);
                                        const resolvedColor = this.getComputedColor(info.color);
                                        const trendLong = this.trendData[key].trend_long || this.trendData[key].trend || [];
                                        const trendShort = this.trendData[key].trend_short || [];

                                        if (trendShort.length) {
                                            datasets.push({
                                                label: info.label + ' (Trend Short)',
                                                data: fullDateRange.map(d => {
                                                    const point = trendShort.find(t => t.date === d);
                                                    return point ? point.value : null;
                                                }),
                                                borderColor: resolvedColor,
                                                borderDash: [5, 5],
                                                borderWidth: 2,
                                                pointRadius: 0,
                                                fill: false,
                                                yAxisID: 'y' + key,
                                                tension: 0.4
                                            });
                                        }

                                        if (trendLong.length) {
                                            datasets.push({
                                                label: info.label + ' (Trend Long)',
                                                data: fullDateRange.map(d => {
                                                    const point = trendLong.find(t => t.date === d);
                                                    return point ? point.value : null;
                                                }),
                                                borderColor: resolvedColor,
                                                borderDash: [2, 2],
                                                borderWidth: 1,
                                                pointRadius: 0,
                                                fill: false,
                                                yAxisID: 'y' + key,
                                                tension: 0.4
                                            });
                                        }
                                    }
                                });
                            }

                            let gridDrawn = false;
                            const cssGridColor = getComputedStyle(document.documentElement).getPropertyValue('--fb-chart-grid').trim() || 'rgba(0,0,0,0.05)';
                            const cssTicksColor = getComputedStyle(document.documentElement).getPropertyValue('--fb-chart-ticks').trim() || '#6B7280';

                            chart.options.scales.x.grid.color = cssGridColor;
                            chart.options.scales.x.ticks.color = cssTicksColor;

                            Object.keys(chart.options.scales).forEach(scaleId => {
                                if (scaleId !== 'x') {
                                    chart.options.scales[scaleId].display = false;
                                }
                            });

                            activeKeys.forEach(key => {
                                let scaleId = 'y' + key;
                                if (!chart.options.scales[scaleId]) {
                                    chart.options.scales[scaleId] = {
                                        type: 'linear',
                                        display: false,
                                        grid: {drawOnChartArea: false, drawBorder: false},
                                        ticks: {}
                                    };
                                }
                                chart.options.scales[scaleId].min = 0;

                                const ds = datasets.find(d => d.yAxisID === scaleId);
                                if (ds) {
                                    chart.options.scales[scaleId].display = true;
                                    if (!gridDrawn) {
                                        chart.options.scales[scaleId].grid.drawOnChartArea = true;
                                        chart.options.scales[scaleId].grid.color = cssGridColor;
                                        chart.options.scales[scaleId].position = 'left';
                                        gridDrawn = true;
                                    } else {
                                        chart.options.scales[scaleId].grid.drawOnChartArea = false;
                                        chart.options.scales[scaleId].position = 'right';
                                    }
                                    chart.options.scales[scaleId].ticks.color = ds.borderColor;
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

                        sortBreakdownBy(col) {
                            if (this.breakdownSortCol === col) {
                                this.breakdownSortDir = this.breakdownSortDir === 'desc' ? 'asc' : 'desc';
                            } else {
                                this.breakdownSortCol = col;
                                this.breakdownSortDir = 'desc';
                            }
                            this.breakdownCurrentPage = 1;
                        },

                        get sortedTableData() {
                            let data = [...this.tableDataRaw];

                            if (this.searchQuery && this.searchQuery.trim() !== '') {
                                const query = this.searchQuery.toLowerCase().trim();
                                data = data.filter(row => String(row.name || '').toLowerCase().includes(query));
                            }

                            return data.sort((a, b) => {
                                const getValue = (row, field) => {
                                    if (field === 'interactions') return Number(row.total_interactions || row.interactions || 0);
                                    if (field === 'views') return Number(row.views || row.video_views || row.page_views_total || row.ig_reels_video_view_total_time || 0);
                                    if (field === 'follows') return Number(row.follows || row.follows_and_unfollows || 0);
                                    return Number(row[field] || 0);
                                };

                                let valA = getValue(a, this.sortCol);
                                let valB = getValue(b, this.sortCol);

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

                        get sortedBreakdownData() {
                            let data = [...this.breakdownDataRaw];

                            if (this.breakdownSearchQuery && this.breakdownSearchQuery.trim() !== '') {
                                const query = this.breakdownSearchQuery.toLowerCase().trim();
                                data = data.filter(row => String(row.name || '').toLowerCase().includes(query));
                            }

                            return data.sort((a, b) => {
                                let valA = Number(a[this.breakdownSortCol] || 0);
                                let valB = Number(b[this.breakdownSortCol] || 0);

                                if (isNaN(valA) || isNaN(valB)) {
                                    valA = String(a[this.breakdownSortCol] || '').toLowerCase();
                                    valB = String(b[this.breakdownSortCol] || '').toLowerCase();
                                }

                                if (valA === valB) return 0;
                                if (this.breakdownSortDir === 'desc') return valA < valB ? 1 : -1;
                                return valA > valB ? 1 : -1;
                            });
                        },

                        get breakdownTotalPages() {
                            return Math.ceil(this.sortedBreakdownData.length / this.breakdownPageSize) || 1;
                        },

                        get paginatedTableData() {
                            const start = (this.currentPage - 1) * this.pageSize;
                            const end = start + Number(this.pageSize);
                            return this.sortedTableData.slice(start, end);
                        },

                        get paginatedBreakdownData() {
                            const start = (this.breakdownCurrentPage - 1) * this.breakdownPageSize;
                            const end = start + Number(this.breakdownPageSize);
                            return this.sortedBreakdownData.slice(start, end);
                        },

                        nextPage() {
                            if (this.currentPage < this.totalPages) this.currentPage++;
                        },

                        prevPage() {
                            if (this.currentPage > 1) this.currentPage--;
                        },

                        nextBreakdownPage() {
                            if (this.breakdownCurrentPage < this.breakdownTotalPages) this.breakdownCurrentPage++;
                        },

                        prevBreakdownPage() {
                            if (this.breakdownCurrentPage > 1) this.breakdownCurrentPage--;
                        },

                        formatNumber(num) {
                            if (num === undefined || num === null || isNaN(num)) return '0';
                            return new Intl.NumberFormat('en-US').format(num);
                        },

                        formatMetaDuration(metricValue) {
                            if (!metricValue || metricValue < 0) return "00:00";

                            // Enforce millisecond conversion (1 Second = 1000 Milliseconds)
                            const totalSeconds = Math.floor(metricValue / 1000);

                            const hours = Math.floor(totalSeconds / 3600);
                            const minutes = Math.floor((totalSeconds % 3600) / 60);
                            const seconds = totalSeconds % 60;

                            const paddedMinutes = String(minutes).padStart(2, '0');
                            const paddedSeconds = String(seconds).padStart(2, '0');

                            // Display format: HH:MM:SS if hours exist, otherwise MM:SS
                            if (hours > 0) {
                                const paddedHours = String(hours).padStart(2, '0');
                                return `${paddedHours}:${paddedMinutes}:${paddedSeconds}`;
                            }

                            return `${paddedMinutes}:${paddedSeconds}`;
                        }
                    }
                });
            };

            if (window.Alpine) {
                registerFboDashboard();
            } else {
                document.addEventListener('alpine:init', registerFboDashboard);
            }
        })();
    </script>
</x-filament-panels::page>
