<x-filament-panels::page>
    <style>
        :root {
            --fb-spend: #10b981;
            --fb-impr: #6366f1;
            --fb-clicks: #0ea5e9;
            --fb-ctr: #8b5cf6;
            --fb-cpc: #f59e0b;
            --fb-roas: #ec4899;
            --fb-purchases: #14b8a6;
            
            --fb-text-main: #111827;
            --fb-text-dim: #6b7280;
            --fb-bg-card: rgba(0,0,0,0.02);
            --fb-border: rgba(0,0,0,0.06);
            --fb-bg-hover: rgba(0,0,0,0.04);
            --fb-bg-active: rgba(0,0,0,0.06);
            --fb-chart-grid: rgba(0, 0, 0, 0.05);
            --fb-chart-ticks: #6b7280;
        }

        .dark {
            --fb-text-main: #ffffff;
            --fb-text-dim: #94a3b8;
            --fb-bg-card: rgba(255,255,255,0.03);
            --fb-border: rgba(255,255,255,0.05);
            --fb-bg-hover: rgba(255,255,255,0.05);
            --fb-bg-active: rgba(255,255,255,0.08);
            --fb-chart-grid: rgba(255, 255, 255, 0.05);
            --fb-chart-ticks: #94a3b8;
        }

        .fb-header-row { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 30px; }
        .fb-header-title { font-size: 1.8rem; font-weight: 800; color: var(--fb-text-main); margin-bottom: 5px; display: flex; align-items: center; gap: 12px; }
        .fb-header-subtitle { color: var(--fb-text-dim); font-size: 0.9rem; }
        .fb-header-controls { display: flex; align-items: center; gap: 15px; margin-bottom: 0; }
        
        .metrics-grid-fb { display: grid; grid-template-columns: repeat(7, 1fr); gap: 12px; margin-bottom: 25px; }

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
        .tab-fb.active { background: var(--fb-bg-card); color: var(--fb-spend); border-bottom: 2px solid var(--fb-spend); }

        .fb-table { width: 100%; border-collapse: collapse; text-align: left; }
        .fb-table th { padding: 12px 20px; font-size: 0.75rem; text-transform: uppercase; color: var(--fb-text-dim); font-weight: 700; border-bottom: 1px solid var(--fb-border); }
        .fb-table td { padding: 12px 20px; border-bottom: 1px solid var(--fb-border); vertical-align: middle; font-size: 0.9rem; color: var(--fb-text-main); }
        .fb-table tr:hover { background: var(--fb-bg-hover); }
        
        .metric-cell { text-align: right; }
        
        .fb-status-active { display: inline-block; width: 8px; height: 8px; border-radius: 50%; background-color: #10b981; margin-right: 6px; }
        .fb-status-paused { display: inline-block; width: 8px; height: 8px; border-radius: 50%; background-color: #94a3b8; margin-right: 6px; }

        .fb-pagination-container { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; padding: 15px 25px; border-top: 1px solid var(--fb-border); background: var(--fb-bg-active); }
        .fb-pagination-text { font-size: 0.875rem; color: var(--fb-text-dim); }
        .fb-pagination-text strong { color: var(--fb-text-main); font-weight: 700; }
        .fb-pagination-select { background: var(--fb-bg-card); border: 1px solid var(--fb-border); color: var(--fb-text-main); font-size: 0.875rem; border-radius: 8px; padding: 8px 12px; outline: none; }
        .fb-pagination-btn { padding: 8px 16px; background: var(--fb-bg-card); border: 1px solid var(--fb-border); border-radius: 8px; font-size: 0.875rem; font-weight: 500; color: var(--fb-text-main); cursor: pointer; transition: background 0.2s; }
        .fb-pagination-btn:hover:not(:disabled) { background: var(--fb-bg-hover); }
        .fb-pagination-btn:disabled { opacity: 0.4; cursor: not-allowed; }
        .fb-pagination-badge { margin-left: 8px; padding: 4px 8px; background: var(--fb-bg-card); border-radius: 4px; font-size: 0.75rem; }
    </style>

    <div x-data="fbDashboard()" x-init="initDashboard()">
        <div class="fb-header-row">
            <div>
                <h1 class="fb-header-title">
                    <x-heroicon-o-presentation-chart-line class="w-8 h-8 text-[#1877F2]" />
                    {{ __('Meta Ads Manager Insights') }}
                </h1>
            </div>
            <div class="fb-header-controls">
                <button type="button" @click="forceRefresh()" class="flex items-center justify-center bg-primary-600 hover:bg-primary-500 text-white text-sm font-medium rounded-lg px-4 py-2.5 transition duration-75 shadow-sm" :class="{ 'opacity-50 cursor-not-allowed': isSummaryLoading || isChartLoading || isTableLoading }" :disabled="isSummaryLoading || isChartLoading || isTableLoading">
                    <x-heroicon-o-arrow-path class="w-5 h-5 mr-2" x-bind:class="{ 'animate-spin': isSummaryLoading || isChartLoading || isTableLoading }" />
                    <span>{{ __('Update') }}</span>
                </button>
                <div class="relative" x-data="{ open: false, searchAccount: '' }">
                    <button @click="open = !open" @click.outside="open = false" type="button" class="bg-white dark:bg-white/5 border border-gray-300 dark:border-white/10 text-gray-950 dark:text-white text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 flex items-center justify-between w-full sm:w-64 md:w-72 px-4 py-2.5 h-[42px]">
                        <span class="truncate font-medium text-gray-700 dark:text-gray-200" x-text="accounts.length === 0 ? '{{ __('Select Ad Accounts...') }}' : (accounts.length === 1 ? '1 {{ __('account') }}' : accounts.length + ' {{ __('accounts') }}')"></span>
                        <x-heroicon-m-chevron-down class="w-4 h-4 ml-2 flex-shrink-0 text-gray-500 dark:text-gray-400" />
                    </button>
                    
                    <div x-show="open" x-transition style="display: none; min-width: 320px;" class="absolute z-50 w-full sm:w-72 mt-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-xl right-0 md:left-0 md:right-auto flex flex-col">
                        
                        <!-- Search and Select All Header -->
                        <div class="p-3 border-b border-gray-200 dark:border-gray-700 space-y-3">
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                    <x-heroicon-o-magnifying-glass class="w-4 h-4 text-gray-500 dark:text-gray-400" />
                                </div>
                                <input type="text" x-model="searchAccount" class="bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full pl-9 p-2" placeholder="{{ __('Search accounts...') }}">
                            </div>
                            
                            @if(count($accounts) > 0)
                            <div class="flex items-center justify-between px-1">
                                <button type="button" @click="accounts = {{ json_encode(array_keys($accounts)) }}" class="text-xs font-medium text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300">{{ __('Select All') }}</button>
                                <button type="button" @click="accounts = []" class="text-xs font-medium text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200">{{ __('Clear All') }}</button>
                            </div>
                            @endif
                        </div>

                        <!-- Accounts List -->
                        <div class="p-2 flex flex-col gap-1 overflow-y-auto max-h-96">
                            @if(count($accounts) === 0)
                                <div class="px-3 py-2 text-sm text-gray-500 dark:text-gray-400 italic">{{ __('No accounts available.') }}</div>
                            @endif
                            @foreach($accounts as $id => $name)
                                <label x-show="searchAccount === '' || '{{ strtolower(addslashes($name)) }}'.includes(searchAccount.toLowerCase()) || '{{ strtolower($id) }}'.includes(searchAccount.toLowerCase())" class="flex items-center px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md cursor-pointer transition-colors duration-150">
                                    <input type="checkbox" value="{{ $id }}" x-model="accounts" class="w-4 h-4 text-primary-600 bg-gray-100 border-gray-300 rounded focus:ring-primary-500 dark:focus:ring-primary-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600 mr-3">
                                    <div class="flex flex-col overflow-hidden">
                                        <span class="truncate font-medium" title="{{ $name }}">{{ $name }}</span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $id }}</span>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>
                <input type="date" x-model.lazy="dateStart" class="bg-white dark:bg-white/5 border border-gray-300 dark:border-white/10 text-gray-950 dark:text-white text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-40 p-2.5">
                <input type="date" x-model.lazy="dateEnd" class="bg-white dark:bg-white/5 border border-gray-300 dark:border-white/10 text-gray-950 dark:text-white text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-40 p-2.5">
            </div>
        </div>

        <div class="metrics-grid-fb relative">
            <div x-show="isSummaryLoading" class="absolute inset-0 z-10 flex items-center justify-center bg-white/50 dark:bg-gray-900/50 backdrop-blur-sm rounded-xl">
                <x-filament::loading-indicator class="h-8 w-8 text-primary-500" />
            </div>

            <div class="card-stat-fb" :class="activeMetrics.spend ? 'active' : ''" @click="toggleMetric('spend')" style="--color: var(--fb-spend);">
                <div class="fb-label">{{ __('Amount Spent') }}</div>
                <div class="card-metric-value" x-text="formatCurrency(summary.spend)"></div>
                <div class="card-metric-trend" :class="getVarianceClass(variance.spend, true)">
                    <span x-text="getVarianceIcon(variance.spend, true)"></span>
                    <span x-text="formatVariance(variance.spend)"></span>
                </div>
            </div>
            <div class="card-stat-fb" :class="activeMetrics.impressions ? 'active' : ''" @click="toggleMetric('impressions')" style="--color: var(--fb-impr);">
                <div class="fb-label">{{ __('Impressions') }}</div>
                <div class="card-metric-value" x-text="formatNumber(summary.impressions)"></div>
                <div class="card-metric-trend" :class="getVarianceClass(variance.impressions)">
                    <span x-text="getVarianceIcon(variance.impressions)"></span>
                    <span x-text="formatVariance(variance.impressions)"></span>
                </div>
            </div>
            <div class="card-stat-fb" :class="activeMetrics.clicks ? 'active' : ''" @click="toggleMetric('clicks')" style="--color: var(--fb-clicks);">
                <div class="fb-label">{{ __('Link Clicks') }}</div>
                <div class="card-metric-value" x-text="formatNumber(summary.clicks)"></div>
                <div class="card-metric-trend" :class="getVarianceClass(variance.clicks)">
                    <span x-text="getVarianceIcon(variance.clicks)"></span>
                    <span x-text="formatVariance(variance.clicks)"></span>
                </div>
            </div>
            <div class="card-stat-fb" :class="activeMetrics.ctr ? 'active' : ''" @click="toggleMetric('ctr')" style="--color: var(--fb-ctr);">
                <div class="fb-label">{{ __('CTR (Link)') }}</div>
                <div class="card-metric-value" x-text="formatPercent(summary.ctr)"></div>
                <div class="card-metric-trend" :class="getVarianceClass(variance.ctr)">
                    <span x-text="getVarianceIcon(variance.ctr)"></span>
                    <span x-text="formatVariance(variance.ctr)"></span>
                </div>
            </div>
            <div class="card-stat-fb" :class="activeMetrics.cpc ? 'active' : ''" @click="toggleMetric('cpc')" style="--color: var(--fb-cpc);">
                <div class="fb-label">{{ __('CPC (Link)') }}</div>
                <div class="card-metric-value" x-text="formatCurrency(summary.cpc)"></div>
                <div class="card-metric-trend" :class="getVarianceClass(variance.cpc, true)">
                    <span x-text="getVarianceIcon(variance.cpc, true)"></span>
                    <span x-text="formatVariance(variance.cpc)"></span>
                </div>
            </div>
            <div class="card-stat-fb" :class="activeMetrics.results ? 'active' : ''" @click="toggleMetric('results')" style="--color: var(--fb-purchases);">
                <div class="fb-label">{{ __('Purchases/Results') }}</div>
                <div class="card-metric-value" x-text="formatNumber(summary.results)"></div>
                <div class="card-metric-trend" :class="getVarianceClass(variance.results)">
                    <span x-text="getVarianceIcon(variance.results)"></span>
                    <span x-text="formatVariance(variance.results)"></span>
                </div>
            </div>
            <div class="card-stat-fb" :class="activeMetrics.purchase_roas ? 'active' : ''" @click="toggleMetric('purchase_roas')" style="--color: var(--fb-roas);">
                <div class="fb-label">{{ __('ROAS') }}</div>
                <div class="card-metric-value" x-text="formatNumber(summary.purchase_roas) + 'x'"></div>
                <div class="card-metric-trend" :class="getVarianceClass(variance.purchase_roas)">
                    <span x-text="getVarianceIcon(variance.purchase_roas)"></span>
                    <span x-text="formatVariance(variance.purchase_roas)"></span>
                </div>
            </div>
        </div>

        <div class="chart-container-fb relative w-full" wire:ignore>
            <div x-show="isChartLoading" class="absolute inset-0 z-10 flex items-center justify-center bg-white/50 dark:bg-gray-900/50 backdrop-blur-sm rounded-xl">
                <x-filament::loading-indicator class="h-8 w-8 text-primary-500" />
            </div>
            <div style="position: relative; width: 100%; height: 100%; display: block;">
                <canvas x-ref="canvas"></canvas>
            </div>
        </div>

        <div x-show="hasAnyFilters" class="mb-6 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 shadow-sm" style="display: none;" x-transition>
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 flex items-center gap-2">
                    <x-heroicon-o-funnel class="w-4 h-4 text-primary-500" />
                    {{ __('Active Filters') }}
                </h3>
                <button @click="clearFilters()" class="text-xs text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 font-medium">{{ __('Clear All') }}</button>
            </div>
            <div class="flex flex-wrap gap-2">
                <template x-for="(values, tab) in activeFilters" :key="tab">
                    <template x-for="val in values" :key="val">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium bg-primary-100 text-primary-800 dark:bg-primary-900/40 dark:text-primary-300 border border-primary-200 dark:border-primary-800">
                            <span class="opacity-70 uppercase text-[10px] mr-1" x-text="tab + ':'"></span>
                            <span x-text="filterLabels[val] || val" class="max-w-xs truncate" :title="filterLabels[val] || val"></span>
                            <button @click.stop="toggleFilter(tab, val)" class="ml-1 text-primary-600 hover:text-primary-900 dark:text-primary-400 dark:hover:text-primary-200">
                                <x-heroicon-m-x-mark class="w-3 h-3" />
                            </button>
                        </span>
                    </template>
                </template>
            </div>
        </div>

        <div class="fb-table-container relative">
            <div x-show="isTableLoading" class="absolute inset-0 z-10 flex items-center justify-center bg-white/50 dark:bg-gray-900/50 backdrop-blur-sm rounded-xl">
                <x-filament::loading-indicator class="h-8 w-8 text-primary-500" />
            </div>
            
            <div class="tab-nav-fb">
                <div class="tab-fb" :class="activeTab === 'campaigns' ? 'active' : ''" @click="setTab('campaigns')">{{ __('CAMPAIGNS') }}</div>
                <div class="tab-fb" :class="activeTab === 'adsets' ? 'active' : ''" @click="setTab('adsets')">{{ __('AD SETS') }}</div>
                <div class="tab-fb" :class="activeTab === 'ads' ? 'active' : ''" @click="setTab('ads')">{{ __('ADS') }}</div>
                <div class="tab-fb" :class="activeTab === 'age' ? 'active' : ''" @click="setTab('age')">{{ __('AGE') }}</div>
                <div class="tab-fb" :class="activeTab === 'gender' ? 'active' : ''" @click="setTab('gender')">{{ __('GENDER') }}</div>
            </div>

            <div class="p-4 border-b border-gray-200 dark:border-white/5 bg-white dark:bg-transparent">
                <div class="relative w-full max-w-md">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                        <x-heroicon-o-magnifying-glass class="w-4 h-4 text-gray-500 dark:text-gray-400" />
                    </div>
                    <input type="text" x-model.debounce.300ms="searchQuery" class="bg-gray-50 dark:bg-white/5 border border-gray-300 dark:border-white/10 text-gray-900 dark:text-white text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full pl-10 p-2" placeholder="{{ __('Filter rows...') }}">
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="fb-table">
                    <thead>
                        <tr>
                            <th><span x-text="activeTab.toUpperCase()"></span></th>
                            <th x-show="['campaigns', 'adsets', 'ads'].includes(activeTab)">{{ __('Delivery') }}</th>
                            <th class="metric-cell cursor-pointer" @click="sortBy('spend')">{{ __('Amount Spent') }} <span x-show="sortCol === 'spend'" x-text="sortDir === 'desc' ? '↓' : '↑'"></span></th>
                            <th class="metric-cell cursor-pointer" @click="sortBy('impressions')">{{ __('Impressions') }} <span x-show="sortCol === 'impressions'" x-text="sortDir === 'desc' ? '↓' : '↑'"></span></th>
                            <th class="metric-cell cursor-pointer" @click="sortBy('clicks')">{{ __('Link Clicks') }} <span x-show="sortCol === 'clicks'" x-text="sortDir === 'desc' ? '↓' : '↑'"></span></th>
                            <th class="metric-cell cursor-pointer" @click="sortBy('results')">{{ __('Purchases') }} <span x-show="sortCol === 'results'" x-text="sortDir === 'desc' ? '↓' : '↑'"></span></th>
                            <th class="metric-cell cursor-pointer" @click="sortBy('purchase_roas')">{{ __('ROAS') }} <span x-show="sortCol === 'purchase_roas'" x-text="sortDir === 'desc' ? '↓' : '↑'"></span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(row, index) in paginatedTableData" :key="row.id + '_' + index">
                            <tr @click="canFilter(activeTab) ? toggleFilter(activeTab, row) : null" class="transition duration-150" :class="[isFilterActive(activeTab, row.id) ? 'bg-primary-50 dark:bg-primary-900/20 shadow-inner' : 'hover:bg-gray-50 dark:hover:bg-white/5', canFilter(activeTab) ? 'cursor-pointer' : 'opacity-50 cursor-not-allowed']">
                                <td class="font-medium">
                                    <div class="flex items-center gap-2">
                                        <div x-show="isFilterActive(activeTab, row.id)" class="text-primary-500">
                                            <x-heroicon-s-check-circle class="w-4 h-4" />
                                        </div>
                                        <span x-text="row.name"></span>
                                    </div>
                                </td>
                                <td x-show="['campaigns', 'adsets', 'ads'].includes(activeTab)">
                                    <div class="flex items-center" x-data="{ status: activeTab === 'campaigns' ? row.campaign_status : (activeTab === 'adsets' ? row.adset_status : row.ad_status) }">
                                        <span :class="status === 'ACTIVE' ? 'fb-status-active' : 'fb-status-paused'"></span>
                                        <span x-text="status || '{{ __('Unknown') }}'" class="text-xs uppercase font-semibold"></span>
                                    </div>
                                </td>
                                <td class="metric-cell" x-text="formatCurrency(row.spend)"></td>
                                <td class="metric-cell" x-text="formatNumber(row.impressions)"></td>
                                <td class="metric-cell" x-text="formatNumber(row.clicks)"></td>
                                <td class="metric-cell" x-text="formatNumber(row.results)"></td>
                                <td class="metric-cell" x-text="formatNumber(row.purchase_roas) + 'x'"></td>
                            </tr>
                        </template>
                        <tr x-show="paginatedTableData.length === 0">
                            <td colspan="7" class="text-center py-8 text-gray-500 dark:text-gray-400">{{ __('No data available.') }}</td>
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
                        {{ __('Page') }} <strong x-text="currentPage"></strong> {{ __('of') }} <strong x-text="totalPages"></strong>
                        <span class="fb-pagination-badge">(<span x-text="tableDataRaw.length"></span> {{ __('results') }})</span>
                    </span>
                    <div class="flex gap-2">
                        <button @click="prevPage()" :disabled="currentPage === 1" class="fb-pagination-btn">{{ __('Prev') }}</button>
                        <button @click="nextPage()" :disabled="currentPage === totalPages" class="fb-pagination-btn">{{ __('Next') }}</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const registerFbDashboard = () => {
                Alpine.data('fbDashboard', () => {
                return {
                    tenantId: '{{ Filament\Facades\Filament::getTenant()->id ?? Filament\Facades\Filament::getTenant()->slug }}',
                    accounts: @entangle('selectedAccounts'),
                    dateStart: @entangle('dateStart'),
                    dateEnd: @entangle('dateEnd'),
                    activeTab: @entangle('activeTab'),
                    
                    isSummaryLoading: false,
                    isChartLoading: false,
                    isTableLoading: false,
                    
                    summary: { spend: 0, clicks: 0, impressions: 0, ctr: 0, cpc: 0, results: 0, purchase_roas: 0 },
                    previous: { spend: 0, clicks: 0, impressions: 0, ctr: 0, cpc: 0, results: 0, purchase_roas: 0 },
                    chartDataRaw: [],
                    tableDataRaw: [],
                    
                    activeMetrics: { spend: true, clicks: true, impressions: true, ctr: false, cpc: false, results: false, purchase_roas: false },
                    
                    activeFilters: { campaigns: [], adsets: [], ads: [], age: [], gender: [] },
                    filterLabels: {},
                    searchQuery: '',
                    
                    sortCol: 'spend',
                    sortDir: 'desc',
                    
                    currentPage: 1,
                    pageSize: 10,
                    
                    get hasAnyFilters() {
                        return Object.values(this.activeFilters).some(arr => arr.length > 0);
                    },

                    initDashboard() {
                        const boot = () => {
                            this.initChart();
                            
                            this.$watch('accounts', (val) => {
                                if (val && val.length !== 1) {
                                    this.activeFilters.campaigns = [];
                                    this.activeFilters.adsets = [];
                                    this.activeFilters.ads = [];
                                    this.saveFilters();
                                }
                                this.loadFilters();
                                this.fetchAll();
                            });
                            this.$watch('activeFilters.campaigns', (val) => {
                                if (val && val.length !== 1) {
                                    this.activeFilters.adsets = [];
                                    this.activeFilters.ads = [];
                                    this.saveFilters();
                                }
                            });
                            this.$watch('activeFilters.adsets', (val) => {
                                if (val && val.length !== 1) {
                                    this.activeFilters.ads = [];
                                    this.saveFilters();
                                }
                            });
                            this.$watch('dateStart', () => this.fetchAll());
                            this.$watch('dateEnd', () => this.fetchAll());
                            this.$watch('pageSize', () => { this.currentPage = 1; });
                            
                            if (this.accounts.length > 0 && this.dateStart && this.dateEnd) {
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
                        if (!this.accounts.length) return;
                        const accountKey = this.accounts.join('_');
                        const saved = sessionStorage.getItem(`fbm_filters_${this.tenantId}_${accountKey}`);
                        const savedLabels = sessionStorage.getItem(`fbm_labels_${this.tenantId}_${accountKey}`);
                        if (saved) {
                            try {
                                this.activeFilters = JSON.parse(saved);
                                this.filterLabels = savedLabels ? JSON.parse(savedLabels) : {};
                            } catch(e) {
                                this.clearFiltersLocal();
                            }
                        } else {
                            this.clearFiltersLocal();
                        }
                    },

                    saveFilters() {
                        if (!this.accounts.length) return;
                        const accountKey = this.accounts.join('_');
                        sessionStorage.setItem(`fbm_filters_${this.tenantId}_${accountKey}`, JSON.stringify(this.activeFilters));
                        sessionStorage.setItem(`fbm_labels_${this.tenantId}_${accountKey}`, JSON.stringify(this.filterLabels));
                    },

                    clearFiltersLocal() {
                        this.activeFilters = { campaigns: [], adsets: [], ads: [], age: [], gender: [] };
                        this.filterLabels = {};
                    },

                    clearFilters() {
                        this.clearFiltersLocal();
                        this.saveFilters();
                        this.fetchSummary();
                        this.fetchChart();
                    },

                    canFilter(tab) {
                        if (tab === 'age' || tab === 'gender') return true;
                        if (tab === 'campaigns') return this.accounts.length === 1;
                        if (tab === 'adsets') return this.accounts.length === 1 && this.activeFilters.campaigns.length === 1;
                        if (tab === 'ads') return this.accounts.length === 1 && this.activeFilters.campaigns.length === 1 && this.activeFilters.adsets.length === 1;
                        return false;
                    },

                    toggleFilter(tab, rowOrId) {
                        if (!this.canFilter(tab)) return;
                        if (!this.activeFilters[tab]) this.activeFilters[tab] = [];
                        
                        const isObject = typeof rowOrId === 'object' && rowOrId !== null;
                        const value = isObject ? rowOrId.id : rowOrId;
                        
                        const idx = this.activeFilters[tab].indexOf(value);
                        if (idx > -1) {
                            this.activeFilters[tab].splice(idx, 1);
                        } else {
                            this.activeFilters[tab].push(value);
                            if (isObject && rowOrId.name) {
                                this.filterLabels[value] = rowOrId.name;
                            }
                        }
                        this.activeFilters[tab] = [...this.activeFilters[tab]]; // Force Alpine reactivity
                        this.saveFilters();
                        this.fetchSummary();
                        this.fetchChart();
                        this.fetchTable();
                    },

                    isFilterActive(tab, value) {
                        return this.activeFilters[tab] && this.activeFilters[tab].includes(value);
                    },

                    forceRefresh() {
                        this.clearCache();
                        this.fetchAll();
                    },

                    clearCache() {
                        const accountKey = this.accounts.join('_');
                        const prefix = `fbm_${this.tenantId}_${accountKey}_${this.dateStart}_${this.dateEnd}`;
                        Object.keys(sessionStorage).forEach(key => {
                            if (key.startsWith(prefix)) {
                                sessionStorage.removeItem(key);
                            }
                        });
                    },

                    getCacheKey(endpoint, filterMode = 'all') {
                        const accountKey = this.accounts.join('_');
                        let filtersObj = {};
                        if (filterMode === 'all') filtersObj = this.activeFilters;
                        else if (filterMode === 'table') filtersObj = this.getTableFilters();

                        const filterHash = filterMode === 'none' ? 'no_filters' : JSON.stringify(filtersObj);
                        return `fbm_${this.tenantId}_${accountKey}_${this.dateStart}_${this.dateEnd}_${endpoint}_${this.activeTab}_${filterHash}_v2`;
                    },

                    async fetchAll() {
                        if (!this.accounts.length || !this.dateStart || !this.dateEnd) return;
                        this.fetchSummary();
                        this.fetchChart();
                        this.fetchTable();
                    },
                    
                    async fetchSummary() {
                        if (!this.accounts.length || !this.dateStart || !this.dateEnd) return;
                        const cacheKey = this.getCacheKey('summary');
                        
                        if (sessionStorage.getItem(cacheKey)) {
                            const data = JSON.parse(sessionStorage.getItem(cacheKey));
                            this.summary = data.summary || { spend: 0, clicks: 0, impressions: 0, ctr: 0, cpc: 0, results: 0, purchase_roas: 0 };
                            this.previous = data.previous || { spend: 0, clicks: 0, impressions: 0, ctr: 0, cpc: 0, results: 0, purchase_roas: 0 };
                            return;
                        }

                        this.isSummaryLoading = true;
                        try {
                            const response = await fetch('/api/fbm/summary', this.getFetchOptions());
                            const data = await response.json();
                            if (!data.error) {
                                sessionStorage.setItem(cacheKey, JSON.stringify(data));
                                this.summary = data.summary || { spend: 0, clicks: 0, impressions: 0, ctr: 0, cpc: 0, results: 0, purchase_roas: 0 };
                                this.previous = data.previous || { spend: 0, clicks: 0, impressions: 0, ctr: 0, cpc: 0, results: 0, purchase_roas: 0 };
                            }
                        } catch (error) {
                            console.error('Error fetching summary:', error);
                        } finally {
                            this.isSummaryLoading = false;
                        }
                    },

                    async fetchChart() {
                        if (!this.accounts.length || !this.dateStart || !this.dateEnd) return;
                        const cacheKey = this.getCacheKey('chart');
                        
                        if (sessionStorage.getItem(cacheKey)) {
                            const data = JSON.parse(sessionStorage.getItem(cacheKey));
                            this.chartDataRaw = data.chart || [];
                            this.updateChart();
                            return;
                        }

                        this.isChartLoading = true;
                        try {
                            const response = await fetch('/api/fbm/chart', this.getFetchOptions());
                            const data = await response.json();
                            if (!data.error) {
                                sessionStorage.setItem(cacheKey, JSON.stringify(data));
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
                        if (!this.accounts.length || !this.dateStart || !this.dateEnd) return;
                        const cacheKey = this.getCacheKey('table', 'table'); // Table uses contextual filters
                        
                        if (sessionStorage.getItem(cacheKey)) {
                            const data = JSON.parse(sessionStorage.getItem(cacheKey));
                            this.tableDataRaw = data.table || [];
                            return;
                        }

                        this.isTableLoading = true;
                        try {
                            const response = await fetch('/api/fbm/table', this.getFetchOptions('table'));
                            const data = await response.json();
                            if (!data.error) {
                                sessionStorage.setItem(cacheKey, JSON.stringify(data));
                                this.tableDataRaw = data.table || [];
                                this.currentPage = 1;
                            }
                        } catch (error) {
                            console.error('Error fetching table:', error);
                        } finally {
                            this.isTableLoading = false;
                        }
                    },

                    getTableFilters() {
                        const filters = JSON.parse(JSON.stringify(this.activeFilters));
                        
                        if (this.activeTab === 'campaigns') {
                            filters.campaigns = [];
                            filters.adsets = [];
                            filters.ads = [];
                        } else if (this.activeTab === 'adsets') {
                            filters.adsets = [];
                            filters.ads = [];
                        } else if (this.activeTab === 'ads') {
                            filters.ads = [];
                        }
                        
                        return filters;
                    },

                    getFetchOptions(filterMode = 'all') {
                        const payload = {
                            tenant: this.tenantId,
                            account: this.accounts,
                            dateStart: this.dateStart,
                            dateEnd: this.dateEnd,
                            activeTab: this.activeTab
                        };
                        
                        if (filterMode === 'all') {
                            payload.activeFilters = this.activeFilters;
                        } else if (filterMode === 'table') {
                            payload.activeFilters = this.getTableFilters();
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
                            spend: calc(this.summary.spend, this.previous.spend),
                            clicks: calc(this.summary.clicks, this.previous.clicks),
                            impressions: calc(this.summary.impressions, this.previous.impressions),
                            ctr: calc(this.summary.ctr, this.previous.ctr),
                            cpc: calc(this.summary.cpc, this.previous.cpc),
                            results: calc(this.summary.results, this.previous.results),
                            purchase_roas: calc(this.summary.purchase_roas, this.previous.purchase_roas)
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
                            data: { labels: [], datasets: [] },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                interaction: { mode: 'index', intersect: false },
                                plugins: {
                                    legend: { display: false },
                                    tooltip: {
                                        backgroundColor: 'rgba(15, 23, 42, 0.9)',
                                        titleColor: '#fff',
                                        bodyColor: '#e2e8f0',
                                        borderColor: 'rgba(255,255,255,0.1)',
                                        borderWidth: 1,
                                        padding: 12,
                                        boxPadding: 6,
                                        usePointStyle: true
                                    }
                                },
                                scales: {
                                    x: { grid: { color: 'var(--fb-chart-grid)', drawBorder: false }, ticks: { color: 'var(--fb-chart-ticks)' } },
                                    ySpend: { type: 'linear', position: 'left', display: false, grid: { color: 'var(--fb-chart-grid)', drawBorder: false }, ticks: { color: '#10b981' } },
                                    yImpressions: { type: 'linear', position: 'right', display: false, grid: { drawOnChartArea: false, drawBorder: false }, ticks: { color: '#6366f1' } },
                                    yClicks: { type: 'linear', position: 'right', display: false, grid: { drawOnChartArea: false, drawBorder: false }, ticks: { color: '#0ea5e9' } }
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
                            if (dataByDate[dateStr]) return dataByDate[dateStr];
                            return {
                                daily: dateStr,
                                spend: 0, trend_total_spend: 0,
                                impressions: 0, trend_total_impressions: 0,
                                clicks: 0, trend_total_clicks: 0,
                                ctr: 0, trend_average_ctr: 0,
                                cpc: 0, trend_average_cpc: 0,
                                results: 0, trend_total_results: 0,
                                purchase_roas: 0, trend_average_purchase_roas: 0
                            };
                        });
                        
                        const labels = paddedData.map(r => dayjs(r.daily || r.date).format('MMM D'));
                        const datasets = [];
                        
                        const chartData = paddedData;
                        
                        if (this.activeMetrics.spend) {
                            datasets.push({
                                label: 'Amount Spent',
                                data: chartData.map(r => r.spend || r.trend_total_spend),
                                borderColor: '#10b981',
                                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                                borderWidth: 2,
                                pointRadius: 0,
                                pointHoverRadius: 6,
                                fill: true,
                                yAxisID: 'ySpend',
                                tension: 0.4
                            });
                        }
                        
                        if (this.activeMetrics.impressions) {
                            datasets.push({
                                label: 'Impressions',
                                data: chartData.map(r => r.impressions || r.trend_total_impressions),
                                borderColor: '#6366f1',
                                backgroundColor: 'rgba(99, 102, 241, 0.1)',
                                borderWidth: 2,
                                pointRadius: 0,
                                pointHoverRadius: 6,
                                fill: true,
                                yAxisID: 'yImpressions',
                                tension: 0.4
                            });
                        }
                        
                        if (this.activeMetrics.clicks) {
                            datasets.push({
                                label: 'Clicks',
                                data: chartData.map(r => r.clicks || r.trend_total_clicks),
                                borderColor: '#0ea5e9',
                                backgroundColor: 'rgba(14, 165, 233, 0.1)',
                                borderWidth: 2,
                                pointRadius: 0,
                                pointHoverRadius: 6,
                                fill: true,
                                yAxisID: 'yClicks',
                                tension: 0.4
                            });
                        }
                        
                        if (this.activeMetrics.ctr) {
                            datasets.push({
                                label: 'CTR',
                                data: chartData.map(r => (r.ctr || r.trend_average_ctr || 0) * 100),
                                borderColor: '#8b5cf6',
                                backgroundColor: 'rgba(139, 92, 246, 0.1)',
                                borderWidth: 2,
                                pointRadius: 0,
                                pointHoverRadius: 6,
                                fill: false,
                                yAxisID: 'yCtr',
                                tension: 0.4
                            });
                        }
                        
                        if (this.activeMetrics.cpc) {
                            datasets.push({
                                label: 'CPC',
                                data: chartData.map(r => r.cpc || r.trend_average_cpc || 0),
                                borderColor: '#f59e0b',
                                backgroundColor: 'rgba(245, 158, 11, 0.1)',
                                borderWidth: 2,
                                pointRadius: 0,
                                pointHoverRadius: 6,
                                fill: false,
                                yAxisID: 'yCpc',
                                tension: 0.4
                            });
                        }
                        
                        if (this.activeMetrics.results) {
                            datasets.push({
                                label: 'Purchases',
                                data: chartData.map(r => r.results || r.trend_total_results || 0),
                                borderColor: '#14b8a6',
                                backgroundColor: 'rgba(20, 184, 166, 0.1)',
                                borderWidth: 2,
                                pointRadius: 0,
                                pointHoverRadius: 6,
                                fill: true,
                                yAxisID: 'yResults',
                                tension: 0.4
                            });
                        }
                        
                        if (this.activeMetrics.purchase_roas) {
                            datasets.push({
                                label: 'ROAS',
                                data: chartData.map(r => r.purchase_roas || r.trend_average_purchase_roas || 0),
                                borderColor: '#ec4899',
                                backgroundColor: 'rgba(236, 72, 153, 0.1)',
                                borderWidth: 2,
                                pointRadius: 0,
                                pointHoverRadius: 6,
                                fill: false,
                                yAxisID: 'yRoas',
                                tension: 0.4
                            });
                        }
                        
                        // Manage scale visibility and background grid dynamically
                        let gridDrawn = false;
                        const cssGridColor = getComputedStyle(document.documentElement).getPropertyValue('--fb-chart-grid').trim();
                        const cssTicksColor = getComputedStyle(document.documentElement).getPropertyValue('--fb-chart-ticks').trim();
                        
                        chart.options.scales.x.grid.color = cssGridColor;
                        chart.options.scales.x.ticks.color = cssTicksColor;
                        
                        ['spend', 'impressions', 'clicks', 'ctr', 'cpc', 'results', 'purchase_roas'].forEach(m => {
                            let scaleId;
                            if (m === 'purchase_roas') scaleId = 'yRoas';
                            else scaleId = 'y' + m.charAt(0).toUpperCase() + m.slice(1);
                            
                            if(!chart.options.scales[scaleId]) {
                                chart.options.scales[scaleId] = { type: 'linear', display: false, grid: { drawOnChartArea: false, drawBorder: false } };
                            }

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
                            data = data.filter(row => String(row.name || '').toLowerCase().includes(query));
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
                    
                    formatNumber(num) {
                        if (num === undefined || num === null) return '0';
                        return new Intl.NumberFormat('en-US').format(num);
                    },
                    
                    formatCurrency(num) {
                        if (num === undefined || num === null) return '$0.00';
                        return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(num);
                    },

                    formatPercent(num) {
                        if (num === undefined || num === null) return '0%';
                        return (num * 100).toFixed(2) + '%';
                    }
                }
            });
            };

            if (window.Alpine) {
                registerFbDashboard();
            } else {
                document.addEventListener('alpine:init', registerFbDashboard);
            }
        })();
    </script>
</x-filament-panels::page>
