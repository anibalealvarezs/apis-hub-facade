<x-filament-panels::page>
    <style>
        :root {
            --fb-spend: #10b981;
            --fb-impr: #6366f1;
            --fb-reach: #3b82f6;
            --fb-freq: #f43f5e;
            --fb-cpm: #eab308;
            --fb-clicks: #0ea5e9;
            --fb-ctr: #8b5cf6;
            --fb-cpc: #f59e0b;
            --fb-roas: #ec4899;
            --fb-purchases: #14b8a6;
            --fb-cpr: #a855f7;
            --fb-rr: #ef4444;

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

        .fb-header-subtitle { color: var(--fb-text-dim); font-size: 0.9rem; }

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

        .fb-pagination-select { background: var(--fb-bg-card); border: 1px solid var(--fb-border); color: var(--fb-text-main); font-size: 0.875rem; border-radius: 8px; padding: 8px 30px 8px 12px; outline: none; background-repeat: no-repeat; background-position: right; background-size: 32px; }

        .fb-pagination-btn { padding: 8px 16px; background: var(--fb-bg-card); border: 1px solid var(--fb-border); border-radius: 8px; font-size: 0.875rem; font-weight: 500; color: var(--fb-text-main); cursor: pointer; transition: background 0.2s; }

        .fb-pagination-btn:hover:not(:disabled) { background: var(--fb-bg-hover); }

        .fb-pagination-btn:disabled { opacity: 0.4; cursor: not-allowed; }

        .fb-pagination-badge { margin-left: 8px; padding: 4px 8px; background: var(--fb-bg-card); border-radius: 4px; font-size: 0.75rem; }
    </style>

    <div x-data="fbDashboard({
        tenantId: @js(Filament\Facades\Filament::getTenant()->id ?? Filament\Facades\Filament::getTenant()->slug),
        accounts: @js($selectedAccounts),
        accountNames: @js($accounts),
        dateStart: @js($dateStart),
        dateEnd: @js($dateEnd),
        activeTab: 'campaigns',
        csrfToken: @js(csrf_token())
    })" x-init="initDashboard()">
        <div class="fb-header-row sticky top-[4rem] z-20 py-3 mb-6 bg-gray-50/95 dark:bg-gray-900/95 backdrop-blur-md border-b border-gray-200 dark:border-white/10 transition-colors">
            <div>
                <h1 class="fb-header-title">
                    <x-heroicon-o-presentation-chart-line class="w-8 h-8 text-[#1877F2]"/>
                    {{ __('Meta Ads Insights') }}
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
                </div>                <button type="button" @click="forceRefresh()"
                        class="flex items-center justify-center bg-primary-600 hover:bg-primary-500 text-white text-sm font-medium rounded-lg px-4 py-2.5 transition duration-75 shadow-sm"
                        :class="{ 'opacity-50 cursor-not-allowed': isSummaryLoading || isChartLoading || isTableLoading }"
                        :disabled="isSummaryLoading || isChartLoading || isTableLoading">
                    <x-heroicon-o-arrow-path class="w-5 h-5 mr-2"
                                             x-bind:class="{ 'animate-spin': isSummaryLoading || isChartLoading || isTableLoading }"/>
                    <span>{{ __('Update') }}</span>
                </button>
                <x-ui.asset-selector model="accounts" options="accountNames" placeholder="{{ __('Select Ad Accounts...') }}" :multiple="true" :all-keys="json_encode(array_keys($accounts))" size="sm" />
                <x-ui.date-input x-model.lazy="dateStart" class="w-40" />
                <x-ui.date-input x-model.lazy="dateEnd" max="{{ date('Y-m-d', strtotime('-1 day')) }}" class="w-40" />
            </div>
        </div>

        <div class="metrics-grid-fb relative">
            <div x-show="isSummaryLoading"
                 class="absolute inset-0 z-10 flex items-center justify-center bg-white/50 dark:bg-gray-900/50 backdrop-blur-sm rounded-xl">
                <x-filament::loading-indicator class="h-8 w-8 text-primary-500"/>
            </div>

            <div class="card-stat-fb" :class="activeMetrics.spend ? 'active' : ''" @click="toggleMetric('spend')"
                 style="--color: var(--fb-spend);">
                <div class="fb-label">{{ __('Amount Spent') }}</div>
                <div class="card-metric-value" x-text="formatCurrency(summary.spend)"></div>
                <div class="card-metric-trend" :class="getVarianceClass(variance.spend, true)">
                    <span x-text="getVarianceIcon(variance.spend, true)"></span>
                    <span x-text="formatVariance(variance.spend)"></span>
                </div>
            </div>
            <div class="card-stat-fb" :class="activeMetrics.impressions ? 'active' : ''"
                 @click="toggleMetric('impressions')" style="--color: var(--fb-impr);">
                <div class="fb-label">{{ __('Impressions') }}</div>
                <div class="card-metric-value" x-text="formatNumber(summary.impressions)"></div>
                <div class="card-metric-trend" :class="getVarianceClass(variance.impressions)">
                    <span x-text="getVarianceIcon(variance.impressions)"></span>
                    <span x-text="formatVariance(variance.impressions)"></span>
                </div>
            </div>
            <div class="card-stat-fb" :class="activeMetrics.reach ? 'active' : ''"
                 @click="toggleMetric('reach')" style="--color: var(--fb-reach);">
                <div class="fb-label">{{ __('Reach') }}</div>
                <div class="card-metric-value" x-text="formatNumber(summary.reach)"></div>
                <div class="card-metric-trend" :class="getVarianceClass(variance.reach)">
                    <span x-text="getVarianceIcon(variance.reach)"></span>
                    <span x-text="formatVariance(variance.reach)"></span>
                </div>
            </div>
            <div class="card-stat-fb" :class="activeMetrics.frequency ? 'active' : ''"
                 @click="toggleMetric('frequency')" style="--color: var(--fb-freq);">
                <div class="fb-label">{{ __('Frequency') }}</div>
                <div class="card-metric-value" x-text="formatDecimal(summary.frequency)"></div>
                <div class="card-metric-trend" :class="getVarianceClass(variance.frequency, true)">
                    <span x-text="getVarianceIcon(variance.frequency, true)"></span>
                    <span x-text="formatVariance(variance.frequency)"></span>
                </div>
            </div>
            <div class="card-stat-fb" :class="activeMetrics.cpm ? 'active' : ''"
                 @click="toggleMetric('cpm')" style="--color: var(--fb-cpm);">
                <div class="fb-label">{{ __('CPM') }}</div>
                <div class="card-metric-value" x-text="formatCurrency(summary.cpm)"></div>
                <div class="card-metric-trend" :class="getVarianceClass(variance.cpm, true)">
                    <span x-text="getVarianceIcon(variance.cpm, true)"></span>
                    <span x-text="formatVariance(variance.cpm)"></span>
                </div>
            </div>
            <div class="card-stat-fb" :class="activeMetrics.clicks ? 'active' : ''" @click="toggleMetric('clicks')"
                 style="--color: var(--fb-clicks);">
                <div class="fb-label">{{ __('Link Clicks') }}</div>
                <div class="card-metric-value" x-text="formatNumber(summary.clicks)"></div>
                <div class="card-metric-trend" :class="getVarianceClass(variance.clicks)">
                    <span x-text="getVarianceIcon(variance.clicks)"></span>
                    <span x-text="formatVariance(variance.clicks)"></span>
                </div>
            </div>
            <div class="card-stat-fb" :class="activeMetrics.ctr ? 'active' : ''" @click="toggleMetric('ctr')"
                 style="--color: var(--fb-ctr);">
                <div class="fb-label">{{ __('CTR (Link)') }}</div>
                <div class="card-metric-value" x-text="formatPercent(summary.ctr)"></div>
                <div class="card-metric-trend" :class="getVarianceClass(variance.ctr)">
                    <span x-text="getVarianceIcon(variance.ctr)"></span>
                    <span x-text="formatVariance(variance.ctr)"></span>
                </div>
            </div>
            <div class="card-stat-fb" :class="activeMetrics.cpc ? 'active' : ''" @click="toggleMetric('cpc')"
                 style="--color: var(--fb-cpc);">
                <div class="fb-label">{{ __('CPC (Link)') }}</div>
                <div class="card-metric-value" x-text="formatCurrency(summary.cpc)"></div>
                <div class="card-metric-trend" :class="getVarianceClass(variance.cpc, true)">
                    <span x-text="getVarianceIcon(variance.cpc, true)"></span>
                    <span x-text="formatVariance(variance.cpc)"></span>
                </div>
            </div>
            <div class="card-stat-fb" :class="activeMetrics.results ? 'active' : ''" @click="toggleMetric('results')"
                 style="--color: var(--fb-purchases);">
                <div class="fb-label">{{ __('Purchases/Results') }}</div>
                <div class="card-metric-value" x-text="formatNumber(summary.results)"></div>
                <div class="card-metric-trend" :class="getVarianceClass(variance.results)">
                    <span x-text="getVarianceIcon(variance.results)"></span>
                    <span x-text="formatVariance(variance.results)"></span>
                </div>
            </div>
            <div class="card-stat-fb" :class="activeMetrics.cost_per_result ? 'active' : ''"
                 @click="toggleMetric('cost_per_result')" style="--color: var(--fb-cpr);">
                <div style="position: absolute; top: 12px; right: 12px;" class="text-primary-500 dark:text-primary-400" title="{{ __('Trend Analysis Supported') }}">
                    <x-heroicon-s-presentation-chart-line class="w-4 h-4 opacity-50" />
                </div>
                <div class="fb-label">{{ __('Cost per Result') }}</div>
                <div class="card-metric-value" x-text="formatCurrency(summary.cost_per_result)"></div>
                <div class="card-metric-trend" :class="getVarianceClass(variance.cost_per_result, true)">
                    <span x-text="getVarianceIcon(variance.cost_per_result, true)"></span>
                    <span x-text="formatVariance(variance.cost_per_result)"></span>
                </div>
            </div>
            <div class="card-stat-fb" :class="activeMetrics.result_rate ? 'active' : ''"
                 @click="toggleMetric('result_rate')" style="--color: var(--fb-rr);">
                <div class="fb-label">{{ __('Result Rate') }}</div>
                <div class="card-metric-value" x-text="formatPercent(summary.result_rate)"></div>
                <div class="card-metric-trend" :class="getVarianceClass(variance.result_rate)">
                    <span x-text="getVarianceIcon(variance.result_rate)"></span>
                    <span x-text="formatVariance(variance.result_rate)"></span>
                </div>
            </div>
            <div class="card-stat-fb" :class="activeMetrics.purchase_roas ? 'active' : ''"
                 @click="toggleMetric('purchase_roas')" style="--color: var(--fb-roas);">
                <div style="position: absolute; top: 12px; right: 12px;" class="text-primary-500 dark:text-primary-400" title="{{ __('Trend Analysis Supported') }}">
                    <x-heroicon-s-presentation-chart-line class="w-4 h-4 opacity-50" />
                </div>
                <div class="fb-label">{{ __('ROAS') }}</div>
                <div class="card-metric-value" x-text="formatNumber(summary.purchase_roas) + 'x'"></div>
                <div class="card-metric-trend" :class="getVarianceClass(variance.purchase_roas)">
                    <span x-text="getVarianceIcon(variance.purchase_roas)"></span>
                    <span x-text="formatVariance(variance.purchase_roas)"></span>
                </div>
            </div>
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
                            <span x-text="filterLabels[val] || val" class="max-w-xs truncate"
                                  :title="filterLabels[val] || val"></span>
                            <button @click.stop="toggleFilter(tab, val)"
                                    class="ml-1 text-primary-600 hover:text-primary-900 dark:text-primary-400 dark:hover:text-primary-200">
                                <x-heroicon-m-x-mark class="w-3 h-3"/>
                            </button>
                        </span>
                    </template>
                </template>
            </div>
        </div>

        <div class="fb-table-container relative">
            <div x-show="isTableLoading"
                 class="absolute inset-0 z-10 flex items-center justify-center bg-white/50 dark:bg-gray-900/50 backdrop-blur-sm rounded-xl">
                <x-filament::loading-indicator class="h-8 w-8 text-primary-500"/>
            </div>

            <div class="tab-nav-fb">
                <div class="tab-fb" :class="activeTab === 'campaigns' ? 'active' : ''"
                     @click="setTab('campaigns')">{{ __('CAMPAIGNS') }}</div>
                <div class="tab-fb" :class="activeTab === 'adsets' ? 'active' : ''"
                     @click="setTab('adsets')">{{ __('AD SETS') }}</div>
                <div class="tab-fb" :class="activeTab === 'ads' ? 'active' : ''"
                     @click="setTab('ads')">{{ __('ADS') }}</div>
                <div class="tab-fb" :class="activeTab === 'age' ? 'active' : ''"
                     @click="setTab('age')">{{ __('AGE') }}</div>
                <div class="tab-fb" :class="activeTab === 'gender' ? 'active' : ''"
                     @click="setTab('gender')">{{ __('GENDER') }}</div>
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
                <table class="fb-table">
                    <thead>
                    <tr>
                        <th><span x-text="activeTab.toUpperCase()"></span></th>
                        <th x-show="['campaigns', 'adsets', 'ads'].includes(activeTab)">{{ __('Delivery') }}</th>
                        <th class="metric-cell cursor-pointer" @click="sortBy('spend')">{{ __('Amount Spent') }} <span
                                x-show="sortCol === 'spend'" x-text="sortDir === 'desc' ? '↓' : '↑'"></span></th>
                        <th class="metric-cell cursor-pointer" @click="sortBy('impressions')">{{ __('Impressions') }}
                            <span x-show="sortCol === 'impressions'" x-text="sortDir === 'desc' ? '↓' : '↑'"></span>
                        </th>
                        <th class="metric-cell cursor-pointer" @click="sortBy('reach')">{{ __('Reach') }}
                            <span x-show="sortCol === 'reach'" x-text="sortDir === 'desc' ? '↓' : '↑'"></span>
                        </th>
                        <th class="metric-cell cursor-pointer" @click="sortBy('frequency')">{{ __('Freq.') }}
                            <span x-show="sortCol === 'frequency'" x-text="sortDir === 'desc' ? '↓' : '↑'"></span>
                        </th>
                        <th class="metric-cell cursor-pointer" @click="sortBy('cpm')">{{ __('CPM') }}
                            <span x-show="sortCol === 'cpm'" x-text="sortDir === 'desc' ? '↓' : '↑'"></span>
                        </th>
                        <th class="metric-cell cursor-pointer" @click="sortBy('clicks')">{{ __('Link Clicks') }} <span
                                x-show="sortCol === 'clicks'" x-text="sortDir === 'desc' ? '↓' : '↑'"></span></th>
                        <th class="metric-cell cursor-pointer" @click="sortBy('results')">{{ __('Purchases') }} <span
                                x-show="sortCol === 'results'" x-text="sortDir === 'desc' ? '↓' : '↑'"></span></th>
                        <th class="metric-cell cursor-pointer" @click="sortBy('cost_per_result')">{{ __('CPR') }} <span
                                x-show="sortCol === 'cost_per_result'" x-text="sortDir === 'desc' ? '↓' : '↑'"></span>
                        </th>
                        <th class="metric-cell cursor-pointer" @click="sortBy('result_rate')">{{ __('RR') }} <span
                                x-show="sortCol === 'result_rate'" x-text="sortDir === 'desc' ? '↓' : '↑'"></span></th>
                        <th class="metric-cell cursor-pointer" @click="sortBy('purchase_roas')">{{ __('ROAS') }} <span
                                x-show="sortCol === 'purchase_roas'" x-text="sortDir === 'desc' ? '↓' : '↑'"></span>
                        </th>
                    </tr>
                    </thead>
                    <tbody>
                    <template x-for="(row, index) in paginatedTableData" :key="row.id + '_' + index">
                        <tr @click="canFilter(activeTab) ? toggleFilter(activeTab, row) : null"
                            class="transition duration-150"
                            :class="[isFilterActive(activeTab, row.id) ? 'bg-primary-50 dark:bg-primary-900/20 shadow-inner' : 'hover:bg-gray-50 dark:hover:bg-white/5', canFilter(activeTab) ? 'cursor-pointer' : 'opacity-50 cursor-not-allowed']">
                            <td class="font-medium">
                                <div class="flex items-center gap-2">
                                    <div x-show="isFilterActive(activeTab, row.id)" class="text-primary-500">
                                        <x-heroicon-s-check-circle class="w-4 h-4"/>
                                    </div>
                                    <span x-text="row.name"></span>
                                </div>
                            </td>
                            <td x-show="['campaigns', 'adsets', 'ads'].includes(activeTab)">
                                <div class="flex items-center"
                                     x-data="{ status: activeTab === 'campaigns' ? row.campaign_status : (activeTab === 'adsets' ? row.adset_status : row.ad_status) }">
                                    <span :class="status === 'ACTIVE' ? 'fb-status-active' : 'fb-status-paused'"></span>
                                    <span x-text="status || '{{ __('Unknown') }}'"
                                          class="text-xs uppercase font-semibold"></span>
                                </div>
                            </td>
                            <td class="metric-cell" x-text="formatCurrency(row.spend)"></td>
                            <td class="metric-cell" x-text="formatNumber(row.impressions)"></td>
                            <td class="metric-cell" x-text="formatNumber(row.reach)"></td>
                            <td class="metric-cell" x-text="formatDecimal(row.frequency)"></td>
                            <td class="metric-cell" x-text="formatCurrency(row.cpm)"></td>
                            <td class="metric-cell" x-text="formatNumber(row.clicks)"></td>
                            <td class="metric-cell" x-text="formatNumber(row.results)"></td>
                            <td class="metric-cell" x-text="formatCurrency(row.cost_per_result)"></td>
                            <td class="metric-cell" x-text="formatPercent(row.result_rate)"></td>
                            <td class="metric-cell" x-text="formatNumber(row.purchase_roas) + 'x'"></td>
                        </tr>
                    </template>
                    <tr x-show="paginatedTableData.length === 0">
                        <td colspan="9"
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
</x-filament-panels::page>
