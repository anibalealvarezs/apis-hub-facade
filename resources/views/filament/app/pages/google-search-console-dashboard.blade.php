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


    <div x-data="gscDashboard({
        tenantId: @js(Filament\Facades\Filament::getTenant()->id ?? Filament\Facades\Filament::getTenant()->slug),
        account: @entangle('selectedAccount'),
        selectedAccount: @entangle('selectedAccount'),
        accountNames: @js($accounts),
        dateStart: @entangle('dateStart'),
        dateEnd: @entangle('dateEnd'),
        activeTab: @entangle('activeTab'),
        csrfToken: @js(csrf_token())
    })" x-init="initDashboard()">
        <div class="gsc-header-row">
            <div>
                <h1 class="gsc-header-title">
                    <x-heroicon-o-magnifying-glass class="w-8 h-8 text-[#4285f4]"/>
                    {{ __('GSC Insights') }}
                </h1>
            </div>
            <div class="gsc-header-controls">
                <div class="flex items-center mr-2 gap-2">
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
                <div class="relative" x-data="{ open: false, searchAccount: '' }" @click.outside="open = false">
                    <button @click="open = !open" type="button"
                            class="bg-white dark:bg-white/5 border border-gray-300 dark:border-white/10 text-gray-950 dark:text-white text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 flex items-center justify-between w-full px-4 py-2.5 h-[42px]"
                            style="max-width:250px;">
                        <span class="truncate font-medium text-gray-700 dark:text-gray-200"
                              x-text="!selectedAccount ? '{{ __('Select Property...') }}' : (accountNames[selectedAccount] || selectedAccount)"></span>
                        <x-heroicon-m-chevron-down class="w-4 h-4 ml-2 flex-shrink-0 text-gray-500 dark:text-gray-400"/>
                    </button>

                    <div x-show="open" x-transition style="display: none; min-width: 320px;"
                         class="absolute z-50 w-full sm:w-72 mt-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-xl right-0 md:left-0 md:right-auto flex flex-col">

                        <!-- Search Header -->
                        <div class="p-3 border-b border-gray-200 dark:border-gray-700">
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 rtl:right-0 rtl:left-auto w-10 flex items-center justify-center pointer-events-none">
                                    <x-heroicon-o-magnifying-glass class="w-4 h-4 text-gray-500 dark:text-gray-400"/>
                                </div>
                                <input type="text" x-model="searchAccount"
                                       class="bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full pl-9 p-2"
                                       style="padding-left: 2.75rem;"
                                       placeholder="{{ __('Search properties...') }}">
                            </div>
                        </div>

                        <!-- Accounts List -->
                        <div class="p-2 flex flex-col gap-1 overflow-y-auto max-h-96">
                            @if(count($accounts) === 0)
                                <div class="px-3 py-2 text-sm text-gray-500 dark:text-gray-400 italic">{{ __('No properties available.') }}</div>
                            @endif
                            @foreach($accounts as $id => $url)
                                <div
                                    x-show="searchAccount === '' || '{{ strtolower(addslashes($url)) }}'.includes(searchAccount.toLowerCase()) || '{{ strtolower($id) }}'.includes(searchAccount.toLowerCase())"
                                    @click="selectedAccount = '{{ $id }}'; $wire.set('selectedAccount', '{{ $id }}'); open = false;"
                                    class="flex gap-x-3 items-center px-3 py-2 text-sm text-gray-700 dark:text-gray-300 rounded-md cursor-pointer transition-all duration-150 border"
                                    :class="selectedAccount == '{{ $id }}' ? 'bg-primary-50 dark:bg-primary-900/20 border-primary-200 dark:border-primary-800' : 'hover:bg-gray-100 dark:hover:bg-gray-700 border-transparent'">
                                    <div
                                        class="w-5 h-5 mr-3 shrink-0 flex items-center justify-center rounded-full border-2 transition-colors duration-150"
                                        :class="selectedAccount == '{{ $id }}' ? 'bg-primary-600 border-primary-600' : 'border-gray-300 dark:border-gray-600'">
                                        <svg x-show="selectedAccount == '{{ $id }}'" class="w-3 h-3 text-white"
                                             fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                  d="m4.5 12.75 6 6 9-13.5"/>
                                        </svg>
                                    </div>
                                    <div class="flex flex-col overflow-hidden">
                                        <span class="truncate font-medium"
                                              :class="selectedAccount == '{{ $id }}' ? 'text-primary-700 dark:text-primary-300' : ''"
                                              title="{{ $url }}">{{ $url }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                <x-ui.date-input x-model.lazy="dateStart" class="w-40" />
                <x-ui.date-input x-model.lazy="dateEnd" max="{{ date('Y-m-d', strtotime('-1 day')) }}" class="w-40" />
            </div>
        </div>

        <div class="metrics-grid-gsc relative">
            <div x-show="isSummaryLoading"
                 class="absolute inset-0 z-10 flex items-center justify-center bg-white/50 dark:bg-gray-900/50 backdrop-blur-sm rounded-xl">
                <x-filament::loading-indicator class="h-8 w-8 text-primary-500"/>
            </div>

            <div class="card-stat-gsc" :class="activeMetrics.clicks ? 'active' : ''" @click="toggleMetric('clicks')"
                 style="--color: #4285f4;">
                <div style="position: absolute; top: 12px; right: 12px;" class="text-primary-500 dark:text-primary-400" title="{{ __('Trend Analysis Supported') }}">
                    <x-heroicon-s-presentation-chart-line class="w-4 h-4 opacity-50" />
                </div>
                <div class="gsc-label">{{ __('Total Clicks') }}</div>
                <div class="card-metric-value" x-text="formatNumber(summary.clicks)"></div>
                <div class="card-metric-trend" :class="getVarianceClass(variance.clicks)">
                    <span x-text="getVarianceIcon(variance.clicks)"></span>
                    <span x-text="formatVariance(variance.clicks)"></span>
                </div>
            </div>
            <div class="card-stat-gsc" :class="activeMetrics.impressions ? 'active' : ''"
                 @click="toggleMetric('impressions')" style="--color: #7e57c2;">
                <div style="position: absolute; top: 12px; right: 12px;" class="text-primary-500 dark:text-primary-400" title="{{ __('Trend Analysis Supported') }}">
                    <x-heroicon-s-presentation-chart-line class="w-4 h-4 opacity-50" />
                </div>
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
</x-filament-panels::page>
