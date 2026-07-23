<x-filament-panels::page>
    <style>
        :root {
            --ga4-sessions: #4285f4;
            --ga4-activeUsers: #0f9d58;
            --ga4-newUsers: #fbbc04;
            --ga4-conversions: #ea4335;
            --ga4-pageViews: #9c27b0;
            --ga4-revenue: #10b981;
            --ga4-avgSessionDuration: #ff6d6d;
            --ga4-totalUsers: #00bcd4;

            --ga4-text-main: #111827;
            --ga4-text-dim: #6b7280;
            --ga4-bg-card: rgba(0, 0, 0, 0.03);
            --ga4-border: rgba(0, 0, 0, 0.05);
            --ga4-bg-hover: rgba(0, 0, 0, 0.05);
            --ga4-bg-active: rgba(0, 0, 0, 0.08);
            --ga4-chart-grid: rgba(0, 0, 0, 0.05);
            --ga4-chart-ticks: #6b7280;
        }

        .dark {
            --ga4-text-main: #ffffff;
            --ga4-text-dim: #94a3b8;
            --ga4-bg-card: rgba(255, 255, 255, 0.03);
            --ga4-border: rgba(255, 255, 255, 0.05);
            --ga4-bg-hover: rgba(255, 255, 255, 0.05);
            --ga4-bg-active: rgba(255, 255, 255, 0.08);
            --ga4-chart-grid: rgba(255, 255, 255, 0.05);
            --ga4-chart-ticks: #94a3b8;
        }

        .ga4-header-row { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 30px; }
        .ga4-header-title { font-size: 1.8rem; font-weight: 800; color: var(--ga4-text-main); margin-bottom: 5px; display: flex; align-items: center; gap: 12px; }
        .ga4-header-subtitle { color: var(--ga4-text-dim); font-size: 0.9rem; }
        .ga4-header-controls { display: flex; align-items: center; gap: 15px; margin-bottom: 0; }

        .metrics-grid-ga4 { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 12px; margin-bottom: 25px; }

        .card-stat-ga4 {
            background: var(--ga4-bg-card);
            border: 1px solid var(--ga4-border);
            border-bottom: 4px solid var(--color, transparent);
            border-radius: 12px;
            padding: 16px;
            cursor: pointer;
            transition: all 0.2s ease;
            opacity: 0.5;
            position: relative;
            overflow: hidden;
        }
        .card-stat-ga4:hover { transform: translateY(-3px); background: var(--ga4-bg-hover); }
        .card-stat-ga4.active { opacity: 1; border-bottom-color: var(--color); background: var(--ga4-bg-active); }

        .ga4-label { font-size: 0.65rem; font-weight: 700; color: var(--ga4-text-dim); text-transform: uppercase; margin-bottom: 8px; letter-spacing: 0.05em; }
        .card-metric-value { font-size: 1.5rem; font-weight: 800; color: var(--ga4-text-main); line-height: 1.2; }
        .card-metric-trend { font-size: 0.75rem; font-weight: 600; margin-top: 6px; display: flex; align-items: center; gap: 4px; padding: 3px 6px; border-radius: 6px; width: fit-content; }
        .trend-up { background: rgba(34, 197, 94, 0.1); color: #16a34a; }
        .dark .trend-up { color: #4ade80; }
        .trend-down { background: rgba(239, 68, 68, 0.1); color: #dc2626; }
        .dark .trend-down { color: #f87171; }
        .trend-neutral { background: var(--ga4-border); color: var(--ga4-text-dim); }

        .chart-container-ga4 {
            background: var(--ga4-bg-card);
            border: 1px solid var(--ga4-border);
            border-radius: 16px;
            padding: 30px;
            margin-bottom: 30px;
            height: 450px;
            position: relative;
        }

        .ga4-table-container {
            background: var(--ga4-bg-card);
            border: 1px solid var(--ga4-border);
            border-radius: 16px;
            overflow: hidden;
            margin-top: 40px;
        }

        .tab-nav-ga4 { display: flex; border-bottom: 1px solid var(--ga4-border); background: var(--ga4-bg-active); flex-wrap: wrap; }
        .tab-ga4 { padding: 15px 25px; cursor: pointer; font-size: 0.85rem; font-weight: 600; color: var(--ga4-text-dim); border-right: 1px solid var(--ga4-border); transition: all 0.2s; }
        .tab-ga4:hover { background: var(--ga4-bg-hover); }
        .tab-ga4.active { background: var(--ga4-bg-card); color: var(--ga4-sessions); border-bottom: 2px solid var(--ga4-sessions); }
        .tab-group-label { padding: 15px 12px 15px 20px; font-size: 0.7rem; font-weight: 700; color: var(--ga4-text-dim); text-transform: uppercase; letter-spacing: 0.1em; border-right: 1px solid var(--ga4-border); background: transparent; display: flex; align-items: center; }

        .ga4-table { width: 100%; border-collapse: collapse; text-align: left; }
        .ga4-table th { padding: 15px 25px; font-size: 0.75rem; text-transform: uppercase; color: var(--ga4-text-dim); font-weight: 700; border-bottom: 1px solid var(--ga4-border); }
        .ga4-table td { padding: 15px 25px; border-bottom: 1px solid var(--ga4-border); vertical-align: middle; }
        .metric-cell { text-align: right; width: 12.5%; min-width: 110px; }
        .ga4-table th:first-child, .ga4-table td:first-child { width: 40%; min-width: 250px; }

        .progress-bar-container { width: 100%; height: 4px; background: var(--ga4-border); border-radius: 2px; margin-top: 4px; overflow: hidden; }
        .progress-bar-fill { height: 100%; transition: width 0.6s ease; }
        .metric-val-main { color: var(--ga4-text-main); font-weight: 600; font-size: 0.95rem; margin-bottom: 4px; }

        .ga4-pagination-container { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; padding: 15px 25px; border-top: 1px solid var(--ga4-border); background: var(--ga4-bg-active); }
        .ga4-pagination-text { font-size: 0.875rem; color: var(--ga4-text-dim); }
        .ga4-pagination-text strong { color: var(--ga4-text-main); font-weight: 700; }
        .ga4-pagination-select { background: var(--ga4-bg-card); border: 1px solid var(--ga4-border); color: var(--ga4-text-main); font-size: 0.875rem; border-radius: 8px; padding: 8px 30px 8px 12px; outline: none; }
        .ga4-pagination-btn { padding: 8px 16px; background: var(--ga4-bg-card); border: 1px solid var(--ga4-border); border-radius: 8px; font-size: 0.875rem; font-weight: 500; color: var(--ga4-text-main); cursor: pointer; transition: background 0.2s; }
        .ga4-pagination-btn:hover:not(:disabled) { background: var(--ga4-bg-hover); }
        .ga4-pagination-btn:disabled { opacity: 0.4; cursor: not-allowed; }
        .ga4-pagination-badge { margin-left: 8px; padding: 4px 8px; background: var(--ga4-bg-card); border-radius: 4px; font-size: 0.75rem; }

        .ga4-section-header { padding: 15px 20px; border-bottom: 1px solid var(--ga4-border); background: var(--ga4-bg-card); }
        .ga4-section-header h3 { font-size: 1.1rem; font-weight: 700; color: var(--ga4-text-main); margin: 0; }
    </style>

    <div x-data="ga4Dashboard()" x-init="initDashboard()">
        <div class="ga4-header-row">
            <div>
                <h1 class="ga4-header-title">
                    <x-heroicon-o-presentation-chart-line class="w-8 h-8 text-[#fbbc04]"/>
                    {{ __('GA4 Insights') }}
                </h1>
            </div>
            <div class="ga4-header-controls">
                <button type="button" @click="forceRefresh()"
                        class="flex items-center justify-center bg-primary-600 hover:bg-primary-500 text-white text-sm font-medium rounded-lg px-4 py-2.5 transition duration-75 shadow-sm"
                        :class="{ 'opacity-50 cursor-not-allowed': isSummaryLoading || isChartLoading || isAnySectionLoading }"
                        :disabled="isSummaryLoading || isChartLoading || isAnySectionLoading">
                    <x-heroicon-o-arrow-path class="w-5 h-5 mr-2"
                                             x-bind:class="{ 'animate-spin': isSummaryLoading || isChartLoading || isAnySectionLoading }"/>
                    <span>{{ __('Update') }}</span>
                </button>
                <select wire:model.live="selectedAccount"
                        class="bg-white dark:bg-white/5 border border-gray-300 dark:border-white/10 text-gray-950 dark:text-white text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 transition duration-75 shadow-sm"
                        style="max-width:250px;">
                    <option value="" class="bg-white dark:bg-gray-800 text-gray-950 dark:text-white">{{ __('Select Property...') }}</option>
                    @foreach($accounts as $id => $url)
                        <option value="{{ $id }}" class="bg-white dark:bg-gray-800 text-gray-950 dark:text-white">{{ $url }}</option>
                    @endforeach
                </select>
                <input type="date" x-model.lazy="dateStart"
                       class="bg-white dark:bg-white/5 border border-gray-300 dark:border-white/10 text-gray-950 dark:text-white text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-40 p-2.5 transition duration-75 shadow-sm">
                <input type="date" x-model.lazy="dateEnd" max="{{ date('Y-m-d', strtotime('-1 day')) }}"
                       class="bg-white dark:bg-white/5 border border-gray-300 dark:border-white/10 text-gray-950 dark:text-white text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-40 p-2.5 transition duration-75 shadow-sm">
            </div>
        </div>

        <div class="metrics-grid-ga4 relative">
            <div x-show="isSummaryLoading"
                 class="absolute inset-0 z-10 flex items-center justify-center bg-white/50 dark:bg-gray-900/50 backdrop-blur-sm rounded-xl">
                <x-filament::loading-indicator class="h-8 w-8 text-primary-500"/>
            </div>

            <div class="card-stat-ga4" :class="activeMetrics.sessions ? 'active' : ''" @click="toggleMetric('sessions')"
                 style="--color: var(--ga4-sessions);">
                <div class="ga4-label">{{ __('Sessions') }}</div>
                <div class="card-metric-value" x-text="formatNumber(summary.sessions)"></div>
                <div class="card-metric-trend" :class="getVarianceClass(variance.sessions)">
                    <span x-text="getVarianceIcon(variance.sessions)"></span>
                    <span x-text="formatVariance(variance.sessions)"></span>
                </div>
            </div>
            <div class="card-stat-ga4" :class="activeMetrics.activeUsers ? 'active' : ''" @click="toggleMetric('activeUsers')"
                 style="--color: var(--ga4-activeUsers);">
                <div class="ga4-label">{{ __('Active Users') }}</div>
                <div class="card-metric-value" x-text="formatNumber(summary.activeUsers)"></div>
                <div class="card-metric-trend" :class="getVarianceClass(variance.activeUsers)">
                    <span x-text="getVarianceIcon(variance.activeUsers)"></span>
                    <span x-text="formatVariance(variance.activeUsers)"></span>
                </div>
            </div>
            <div class="card-stat-ga4" :class="activeMetrics.newUsers ? 'active' : ''" @click="toggleMetric('newUsers')"
                 style="--color: var(--ga4-newUsers);">
                <div class="ga4-label">{{ __('New Users') }}</div>
                <div class="card-metric-value" x-text="formatNumber(summary.newUsers)"></div>
                <div class="card-metric-trend" :class="getVarianceClass(variance.newUsers)">
                    <span x-text="getVarianceIcon(variance.newUsers)"></span>
                    <span x-text="formatVariance(variance.newUsers)"></span>
                </div>
            </div>
            <div class="card-stat-ga4" :class="activeMetrics.screenPageViews ? 'active' : ''" @click="toggleMetric('screenPageViews')"
                 style="--color: #a855f7;">
                <div class="ga4-label">{{ __('Pageviews') }}</div>
                <div class="card-metric-value" x-text="formatNumber(summary.screenPageViews)"></div>
                <div class="card-metric-trend" :class="getVarianceClass(variance.screenPageViews)">
                    <span x-text="getVarianceIcon(variance.screenPageViews)"></span>
                    <span x-text="formatVariance(variance.screenPageViews)"></span>
                </div>
            </div>
            <div class="card-stat-ga4" :class="activeMetrics.conversions ? 'active' : ''" @click="toggleMetric('conversions')"
                 style="--color: var(--ga4-conversions);">
                <div class="ga4-label">{{ __('Conversions') }}</div>
                <div class="card-metric-value" x-text="formatNumber(summary.conversions)"></div>
                <div class="card-metric-trend" :class="getVarianceClass(variance.conversions)">
                    <span x-text="getVarianceIcon(variance.conversions)"></span>
                    <span x-text="formatVariance(variance.conversions)"></span>
                </div>
            </div>
            <div class="card-stat-ga4" :class="activeMetrics.averageSessionDuration ? 'active' : ''" @click="toggleMetric('averageSessionDuration')"
                 style="--color: #06b6d4;">
                <div class="ga4-label">{{ __('Avg Duration') }}</div>
                <div class="card-metric-value" x-text="formatDuration(summary.averageSessionDuration)"></div>
                <div class="card-metric-trend" :class="getVarianceClass(variance.averageSessionDuration)">
                    <span x-text="getVarianceIcon(variance.averageSessionDuration)"></span>
                    <span x-text="formatVariance(variance.averageSessionDuration)"></span>
                </div>
            </div>
            <div class="card-stat-ga4" :class="activeMetrics.bounceRate ? 'active' : ''" @click="toggleMetric('bounceRate')"
                 style="--color: #ec4899;">
                <div class="ga4-label">{{ __('Bounce Rate') }}</div>
                <div class="card-metric-value" x-text="formatPercent(summary.bounceRate)"></div>
                <div class="card-metric-trend" :class="getVarianceClass(variance.bounceRate, true)">
                    <span x-text="getVarianceIcon(variance.bounceRate, true)"></span>
                    <span x-text="formatVariance(variance.bounceRate)"></span>
                </div>
            </div>
            <div class="card-stat-ga4" :class="activeMetrics.totalUsers ? 'active' : ''" @click="toggleMetric('totalUsers')"
                 style="--color: #6366f1;">
                <div class="ga4-label">{{ __('Total Users') }}</div>
                <div class="card-metric-value" x-text="formatNumber(summary.totalUsers)"></div>
                <div class="card-metric-trend" :class="getVarianceClass(variance.totalUsers)">
                    <span x-text="getVarianceIcon(variance.totalUsers)"></span>
                    <span x-text="formatVariance(variance.totalUsers)"></span>
                </div>
            </div>
            <div class="card-stat-ga4" :class="activeMetrics.revenue ? 'active' : ''" @click="toggleMetric('revenue')"
                 style="--color: #10b981;">
                <div class="ga4-label">{{ __('Revenue') }}</div>
                <div class="card-metric-value" x-text="'$' + formatDecimals(summary.revenue)"></div>
                <div class="card-metric-trend" :class="getVarianceClass(variance.revenue)">
                    <span x-text="getVarianceIcon(variance.revenue)"></span>
                    <span x-text="formatVariance(variance.revenue)"></span>
                </div>
            </div>
        </div>

        <div class="chart-container-ga4 relative w-full" wire:ignore>
            <div x-show="isChartLoading"
                 class="absolute inset-0 z-10 flex items-center justify-center bg-white/50 dark:bg-gray-900/50 backdrop-blur-sm rounded-xl">
                <x-filament::loading-indicator class="h-8 w-8 text-primary-500"/>
            </div>
            <div style="position: relative; width: 100%; height: 100%; display: block;">
                <canvas x-ref="canvas"></canvas>
            </div>
        </div>

        {{-- Campaigns Section --}}
        <div class="ga4-table-container relative">
            <div x-show="sl.campaigns"
                 class="absolute inset-0 z-10 flex items-center justify-center bg-white/50 dark:bg-gray-900/50 backdrop-blur-sm rounded-xl">
                <x-filament::loading-indicator class="h-8 w-8 text-primary-500"/>
            </div>
            <div class="ga4-section-header">
                <h3 x-text="sectionConfig.campaigns.label"></h3>
            </div>
            <div class="tab-nav-ga4">
                <template x-for="t in sectionConfig.campaigns.tabs" :key="t">
                    <div class="tab-ga4" :class="ss.campaigns === t ? 'active' : ''"
                         @click="setSectionSubTab('campaigns', t)" x-text="tabConfig[t]?.label || t"></div>
                </template>
            </div>
            <div class="p-4 border-b border-gray-200 dark:border-white/5 bg-white dark:bg-transparent">
                <div class="relative w-full max-w-md">
                    <div class="absolute inset-y-0 left-0 rtl:right-0 rtl:left-auto w-10 flex items-center justify-center pointer-events-none">
                        <x-heroicon-o-magnifying-glass class="w-4 h-4 text-gray-500 dark:text-gray-400"/>
                    </div>
                    <input type="text" x-model.debounce.300ms="sq.campaigns"
                           class="bg-gray-50 dark:bg-white/5 border border-gray-300 dark:border-white/10 text-gray-900 dark:text-white text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full pl-10 p-2"
                           style="padding-left: 2.75rem;"
                           placeholder="{{ __('Filter rows...') }}">
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="ga4-table">
                    <thead>
                    <tr>
                        <th><span x-text="tabConfig[ss.campaigns]?.label || 'Name'"></span></th>
                        <template x-for="m in (tabConfig[ss.campaigns]?.metrics || [])" :key="m">
                            <th class="metric-cell cursor-pointer" @click="sectionSortBy('campaigns', m)">
                                <span x-text="metricLabels[m] || m"></span>
                                <span x-show="sc.campaigns === m" x-text="sd2.campaigns === 'desc' ? '↓' : '↑'"></span>
                            </th>
                        </template>
                    </tr>
                    </thead>
                    <tbody>
                    <template x-for="(row, idx) in sectionPaginated('campaigns')" :key="(row.id || idx) + '_' + idx">
                        <tr class="transition duration-150 hover:bg-gray-50 dark:hover:bg-white/5">
                            <td>
                                <div class="flex items-center gap-2">
                                    <div class="text-sm font-semibold text-[var(--ga4-text-main)] truncate max-w-md" x-text="row.name" :title="row.name"></div>
                                </div>
                            </td>
                            <template x-for="m in (tabConfig[ss.campaigns]?.metrics || [])" :key="m">
                                <td class="metric-cell">
                                    <div class="metric-val-main" x-text="formatMetricValue(m, row[m])"></div>
                                    <div class="progress-bar-container">
                                        <div class="progress-bar-fill"
                                             :style="`width: ${(row[m] / sectionMaxMetric('campaigns', m)) * 100}%; background: ${metricColors[m] || 'var(--ga4-sessions)'}`"></div>
                                    </div>
                                </td>
                            </template>
                        </tr>
                    </template>
                    <tr x-show="sectionPaginated('campaigns').length === 0">
                        <td :colspan="(tabConfig[ss.campaigns]?.metrics?.length || 6) + 1" class="text-center py-8 text-gray-500 dark:text-gray-400">{{ __('No data available.') }}</td>
                    </tr>
                    </tbody>
                </table>
            </div>
            <div class="ga4-pagination-container" x-show="sd.campaigns.length > 0">
                <div class="flex items-center gap-4 mb-4 sm:mb-0">
                    <span class="ga4-pagination-text font-medium">{{ __('Rows per page:') }}</span>
                    <select x-model="pageSize" class="ga4-pagination-select">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                        <option value="250">250</option>
                    </select>
                </div>
                <div class="flex items-center gap-6">
                    <span class="ga4-pagination-text">
                        {{ __('Page') }} <strong x-text="sp.campaigns"></strong> {{ __('of') }} <strong x-text="sectionTotalPages('campaigns')"></strong>
                        <span class="ga4-pagination-badge">(<span x-text="sd.campaigns.length"></span> {{ __('results') }})</span>
                    </span>
                    <div class="flex gap-2">
                        <button @click="sectionPrevPage('campaigns')" :disabled="sp.campaigns === 1" class="ga4-pagination-btn">{{ __('Prev') }}</button>
                        <button @click="sectionNextPage('campaigns')" :disabled="sp.campaigns === sectionTotalPages('campaigns')" class="ga4-pagination-btn">{{ __('Next') }}</button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Channels Section --}}
        <div class="ga4-table-container relative">
            <div x-show="sl.channels"
                 class="absolute inset-0 z-10 flex items-center justify-center bg-white/50 dark:bg-gray-900/50 backdrop-blur-sm rounded-xl">
                <x-filament::loading-indicator class="h-8 w-8 text-primary-500"/>
            </div>
            <div class="ga4-section-header">
                <h3 x-text="sectionConfig.channels.label"></h3>
            </div>
            <div class="tab-nav-ga4">
                <template x-for="t in sectionConfig.channels.tabs" :key="t">
                    <div class="tab-ga4" :class="ss.channels === t ? 'active' : ''"
                         @click="setSectionSubTab('channels', t)" x-text="tabConfig[t]?.label || t"></div>
                </template>
            </div>
            <div class="p-4 border-b border-gray-200 dark:border-white/5 bg-white dark:bg-transparent">
                <div class="relative w-full max-w-md">
                    <div class="absolute inset-y-0 left-0 rtl:right-0 rtl:left-auto w-10 flex items-center justify-center pointer-events-none">
                        <x-heroicon-o-magnifying-glass class="w-4 h-4 text-gray-500 dark:text-gray-400"/>
                    </div>
                    <input type="text" x-model.debounce.300ms="sq.channels"
                           class="bg-gray-50 dark:bg-white/5 border border-gray-300 dark:border-white/10 text-gray-900 dark:text-white text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full pl-10 p-2"
                           style="padding-left: 2.75rem;"
                           placeholder="{{ __('Filter rows...') }}">
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="ga4-table">
                    <thead>
                    <tr>
                        <th><span x-text="tabConfig[ss.channels]?.label || 'Name'"></span></th>
                        <template x-for="m in (tabConfig[ss.channels]?.metrics || [])" :key="m">
                            <th class="metric-cell cursor-pointer" @click="sectionSortBy('channels', m)">
                                <span x-text="metricLabels[m] || m"></span>
                                <span x-show="sc.channels === m" x-text="sd2.channels === 'desc' ? '↓' : '↑'"></span>
                            </th>
                        </template>
                    </tr>
                    </thead>
                    <tbody>
                    <template x-for="(row, idx) in sectionPaginated('channels')" :key="(row.id || idx) + '_' + idx">
                        <tr class="transition duration-150 hover:bg-gray-50 dark:hover:bg-white/5">
                            <td>
                                <div class="flex items-center gap-2">
                                    <div class="text-sm font-semibold text-[var(--ga4-text-main)] truncate max-w-md" x-text="row.name" :title="row.name"></div>
                                </div>
                            </td>
                            <template x-for="m in (tabConfig[ss.channels]?.metrics || [])" :key="m">
                                <td class="metric-cell">
                                    <div class="metric-val-main" x-text="formatMetricValue(m, row[m])"></div>
                                    <div class="progress-bar-container">
                                        <div class="progress-bar-fill"
                                             :style="`width: ${(row[m] / sectionMaxMetric('channels', m)) * 100}%; background: ${metricColors[m] || 'var(--ga4-sessions)'}`"></div>
                                    </div>
                                </td>
                            </template>
                        </tr>
                    </template>
                    <tr x-show="sectionPaginated('channels').length === 0">
                        <td :colspan="(tabConfig[ss.channels]?.metrics?.length || 6) + 1" class="text-center py-8 text-gray-500 dark:text-gray-400">{{ __('No data available.') }}</td>
                    </tr>
                    </tbody>
                </table>
            </div>
            <div class="ga4-pagination-container" x-show="sd.channels.length > 0">
                <div class="flex items-center gap-4 mb-4 sm:mb-0">
                    <span class="ga4-pagination-text font-medium">{{ __('Rows per page:') }}</span>
                    <select x-model="pageSize" class="ga4-pagination-select">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                        <option value="250">250</option>
                    </select>
                </div>
                <div class="flex items-center gap-6">
                    <span class="ga4-pagination-text">
                        {{ __('Page') }} <strong x-text="sp.channels"></strong> {{ __('of') }} <strong x-text="sectionTotalPages('channels')"></strong>
                        <span class="ga4-pagination-badge">(<span x-text="sd.channels.length"></span> {{ __('results') }})</span>
                    </span>
                    <div class="flex gap-2">
                        <button @click="sectionPrevPage('channels')" :disabled="sp.channels === 1" class="ga4-pagination-btn">{{ __('Prev') }}</button>
                        <button @click="sectionNextPage('channels')" :disabled="sp.channels === sectionTotalPages('channels')" class="ga4-pagination-btn">{{ __('Next') }}</button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Traffic Section --}}
        <div class="ga4-table-container relative">
            <div x-show="sl.traffic"
                 class="absolute inset-0 z-10 flex items-center justify-center bg-white/50 dark:bg-gray-900/50 backdrop-blur-sm rounded-xl">
                <x-filament::loading-indicator class="h-8 w-8 text-primary-500"/>
            </div>
            <div class="ga4-section-header">
                <h3 x-text="sectionConfig.traffic.label"></h3>
            </div>
            <div class="tab-nav-ga4">
                <template x-for="t in sectionConfig.traffic.tabs" :key="t">
                    <div class="tab-ga4" :class="ss.traffic === t ? 'active' : ''"
                         @click="setSectionSubTab('traffic', t)" x-text="tabConfig[t]?.label || t"></div>
                </template>
            </div>
            <div class="p-4 border-b border-gray-200 dark:border-white/5 bg-white dark:bg-transparent">
                <div class="relative w-full max-w-md">
                    <div class="absolute inset-y-0 left-0 rtl:right-0 rtl:left-auto w-10 flex items-center justify-center pointer-events-none">
                        <x-heroicon-o-magnifying-glass class="w-4 h-4 text-gray-500 dark:text-gray-400"/>
                    </div>
                    <input type="text" x-model.debounce.300ms="sq.traffic"
                           class="bg-gray-50 dark:bg-white/5 border border-gray-300 dark:border-white/10 text-gray-900 dark:text-white text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full pl-10 p-2"
                           style="padding-left: 2.75rem;"
                           placeholder="{{ __('Filter rows...') }}">
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="ga4-table">
                    <thead>
                    <tr>
                        <th><span x-text="tabConfig[ss.traffic]?.label || 'Name'"></span></th>
                        <template x-for="m in (tabConfig[ss.traffic]?.metrics || [])" :key="m">
                            <th class="metric-cell cursor-pointer" @click="sectionSortBy('traffic', m)">
                                <span x-text="metricLabels[m] || m"></span>
                                <span x-show="sc.traffic === m" x-text="sd2.traffic === 'desc' ? '↓' : '↑'"></span>
                            </th>
                        </template>
                    </tr>
                    </thead>
                    <tbody>
                    <template x-for="(row, idx) in sectionPaginated('traffic')" :key="(row.id || idx) + '_' + idx">
                        <tr class="transition duration-150 hover:bg-gray-50 dark:hover:bg-white/5">
                            <td>
                                <div class="flex items-center gap-2">
                                    <div class="text-sm font-semibold text-[var(--ga4-text-main)] truncate max-w-md" x-text="row.name" :title="row.name"></div>
                                </div>
                            </td>
                            <template x-for="m in (tabConfig[ss.traffic]?.metrics || [])" :key="m">
                                <td class="metric-cell">
                                    <div class="metric-val-main" x-text="formatMetricValue(m, row[m])"></div>
                                    <div class="progress-bar-container">
                                        <div class="progress-bar-fill"
                                             :style="`width: ${(row[m] / sectionMaxMetric('traffic', m)) * 100}%; background: ${metricColors[m] || 'var(--ga4-sessions)'}`"></div>
                                    </div>
                                </td>
                            </template>
                        </tr>
                    </template>
                    <tr x-show="sectionPaginated('traffic').length === 0">
                        <td :colspan="(tabConfig[ss.traffic]?.metrics?.length || 6) + 1" class="text-center py-8 text-gray-500 dark:text-gray-400">{{ __('No data available.') }}</td>
                    </tr>
                    </tbody>
                </table>
            </div>
            <div class="ga4-pagination-container" x-show="sd.traffic.length > 0">
                <div class="flex items-center gap-4 mb-4 sm:mb-0">
                    <span class="ga4-pagination-text font-medium">{{ __('Rows per page:') }}</span>
                    <select x-model="pageSize" class="ga4-pagination-select">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                        <option value="250">250</option>
                    </select>
                </div>
                <div class="flex items-center gap-6">
                    <span class="ga4-pagination-text">
                        {{ __('Page') }} <strong x-text="sp.traffic"></strong> {{ __('of') }} <strong x-text="sectionTotalPages('traffic')"></strong>
                        <span class="ga4-pagination-badge">(<span x-text="sd.traffic.length"></span> {{ __('results') }})</span>
                    </span>
                    <div class="flex gap-2">
                        <button @click="sectionPrevPage('traffic')" :disabled="sp.traffic === 1" class="ga4-pagination-btn">{{ __('Prev') }}</button>
                        <button @click="sectionNextPage('traffic')" :disabled="sp.traffic === sectionTotalPages('traffic')" class="ga4-pagination-btn">{{ __('Next') }}</button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Acquisition Section --}}
        <div class="ga4-table-container relative">
            <div x-show="sl.acquisition"
                 class="absolute inset-0 z-10 flex items-center justify-center bg-white/50 dark:bg-gray-900/50 backdrop-blur-sm rounded-xl">
                <x-filament::loading-indicator class="h-8 w-8 text-primary-500"/>
            </div>
            <div class="ga4-section-header">
                <h3 x-text="sectionConfig.acquisition.label"></h3>
            </div>
            <div class="tab-nav-ga4">
                <template x-for="t in sectionConfig.acquisition.tabs" :key="t">
                    <div class="tab-ga4" :class="ss.acquisition === t ? 'active' : ''"
                         @click="setSectionSubTab('acquisition', t)" x-text="tabConfig[t]?.label || t"></div>
                </template>
            </div>
            <div class="p-4 border-b border-gray-200 dark:border-white/5 bg-white dark:bg-transparent">
                <div class="relative w-full max-w-md">
                    <div class="absolute inset-y-0 left-0 rtl:right-0 rtl:left-auto w-10 flex items-center justify-center pointer-events-none">
                        <x-heroicon-o-magnifying-glass class="w-4 h-4 text-gray-500 dark:text-gray-400"/>
                    </div>
                    <input type="text" x-model.debounce.300ms="sq.acquisition"
                           class="bg-gray-50 dark:bg-white/5 border border-gray-300 dark:border-white/10 text-gray-900 dark:text-white text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full pl-10 p-2"
                           style="padding-left: 2.75rem;"
                           placeholder="{{ __('Filter rows...') }}">
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="ga4-table">
                    <thead>
                    <tr>
                        <th><span x-text="tabConfig[ss.acquisition]?.label || 'Name'"></span></th>
                        <template x-for="m in (tabConfig[ss.acquisition]?.metrics || [])" :key="m">
                            <th class="metric-cell cursor-pointer" @click="sectionSortBy('acquisition', m)">
                                <span x-text="metricLabels[m] || m"></span>
                                <span x-show="sc.acquisition === m" x-text="sd2.acquisition === 'desc' ? '↓' : '↑'"></span>
                            </th>
                        </template>
                    </tr>
                    </thead>
                    <tbody>
                    <template x-for="(row, idx) in sectionPaginated('acquisition')" :key="(row.id || idx) + '_' + idx">
                        <tr class="transition duration-150 hover:bg-gray-50 dark:hover:bg-white/5">
                            <td>
                                <div class="flex items-center gap-2">
                                    <div class="text-sm font-semibold text-[var(--ga4-text-main)] truncate max-w-md" x-text="row.name" :title="row.name"></div>
                                </div>
                            </td>
                            <template x-for="m in (tabConfig[ss.acquisition]?.metrics || [])" :key="m">
                                <td class="metric-cell">
                                    <div class="metric-val-main" x-text="formatMetricValue(m, row[m])"></div>
                                    <div class="progress-bar-container">
                                        <div class="progress-bar-fill"
                                             :style="`width: ${(row[m] / sectionMaxMetric('acquisition', m)) * 100}%; background: ${metricColors[m] || 'var(--ga4-sessions)'}`"></div>
                                    </div>
                                </td>
                            </template>
                        </tr>
                    </template>
                    <tr x-show="sectionPaginated('acquisition').length === 0">
                        <td :colspan="(tabConfig[ss.acquisition]?.metrics?.length || 6) + 1" class="text-center py-8 text-gray-500 dark:text-gray-400">{{ __('No data available.') }}</td>
                    </tr>
                    </tbody>
                </table>
            </div>
            <div class="ga4-pagination-container" x-show="sd.acquisition.length > 0">
                <div class="flex items-center gap-4 mb-4 sm:mb-0">
                    <span class="ga4-pagination-text font-medium">{{ __('Rows per page:') }}</span>
                    <select x-model="pageSize" class="ga4-pagination-select">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                        <option value="250">250</option>
                    </select>
                </div>
                <div class="flex items-center gap-6">
                    <span class="ga4-pagination-text">
                        {{ __('Page') }} <strong x-text="sp.acquisition"></strong> {{ __('of') }} <strong x-text="sectionTotalPages('acquisition')"></strong>
                        <span class="ga4-pagination-badge">(<span x-text="sd.acquisition.length"></span> {{ __('results') }})</span>
                    </span>
                    <div class="flex gap-2">
                        <button @click="sectionPrevPage('acquisition')" :disabled="sp.acquisition === 1" class="ga4-pagination-btn">{{ __('Prev') }}</button>
                        <button @click="sectionNextPage('acquisition')" :disabled="sp.acquisition === sectionTotalPages('acquisition')" class="ga4-pagination-btn">{{ __('Next') }}</button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Events Section --}}
        <div class="ga4-table-container relative">
            <div x-show="sl.events"
                 class="absolute inset-0 z-10 flex items-center justify-center bg-white/50 dark:bg-gray-900/50 backdrop-blur-sm rounded-xl">
                <x-filament::loading-indicator class="h-8 w-8 text-primary-500"/>
            </div>
            <div class="ga4-section-header">
                <h3 x-text="sectionConfig.events.label"></h3>
            </div>
            <div class="tab-nav-ga4">
                <template x-for="t in sectionConfig.events.tabs" :key="t">
                    <div class="tab-ga4" :class="ss.events === t ? 'active' : ''"
                         @click="setSectionSubTab('events', t)" x-text="tabConfig[t]?.label || t"></div>
                </template>
            </div>
            <div class="p-4 border-b border-gray-200 dark:border-white/5 bg-white dark:bg-transparent">
                <div class="relative w-full max-w-md">
                    <div class="absolute inset-y-0 left-0 rtl:right-0 rtl:left-auto w-10 flex items-center justify-center pointer-events-none">
                        <x-heroicon-o-magnifying-glass class="w-4 h-4 text-gray-500 dark:text-gray-400"/>
                    </div>
                    <input type="text" x-model.debounce.300ms="sq.events"
                           class="bg-gray-50 dark:bg-white/5 border border-gray-300 dark:border-white/10 text-gray-900 dark:text-white text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full pl-10 p-2"
                           style="padding-left: 2.75rem;"
                           placeholder="{{ __('Filter rows...') }}">
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="ga4-table">
                    <thead>
                    <tr>
                        <th><span x-text="tabConfig[ss.events]?.label || 'Name'"></span></th>
                        <template x-for="m in (tabConfig[ss.events]?.metrics || [])" :key="m">
                            <th class="metric-cell cursor-pointer" @click="sectionSortBy('events', m)">
                                <span x-text="metricLabels[m] || m"></span>
                                <span x-show="sc.events === m" x-text="sd2.events === 'desc' ? '↓' : '↑'"></span>
                            </th>
                        </template>
                    </tr>
                    </thead>
                    <tbody>
                    <template x-for="(row, idx) in sectionPaginated('events')" :key="(row.id || idx) + '_' + idx">
                        <tr class="transition duration-150 hover:bg-gray-50 dark:hover:bg-white/5">
                            <td>
                                <div class="flex items-center gap-2">
                                    <div class="text-sm font-semibold text-[var(--ga4-text-main)] truncate max-w-md" x-text="row.name" :title="row.name"></div>
                                </div>
                            </td>
                            <template x-for="m in (tabConfig[ss.events]?.metrics || [])" :key="m">
                                <td class="metric-cell">
                                    <div class="metric-val-main" x-text="formatMetricValue(m, row[m])"></div>
                                    <div class="progress-bar-container">
                                        <div class="progress-bar-fill"
                                             :style="`width: ${(row[m] / sectionMaxMetric('events', m)) * 100}%; background: ${metricColors[m] || 'var(--ga4-sessions)'}`"></div>
                                    </div>
                                </td>
                            </template>
                        </tr>
                    </template>
                    <tr x-show="sectionPaginated('events').length === 0">
                        <td :colspan="(tabConfig[ss.events]?.metrics?.length || 6) + 1" class="text-center py-8 text-gray-500 dark:text-gray-400">{{ __('No data available.') }}</td>
                    </tr>
                    </tbody>
                </table>
            </div>
            <div class="ga4-pagination-container" x-show="sd.events.length > 0">
                <div class="flex items-center gap-4 mb-4 sm:mb-0">
                    <span class="ga4-pagination-text font-medium">{{ __('Rows per page:') }}</span>
                    <select x-model="pageSize" class="ga4-pagination-select">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                        <option value="250">250</option>
                    </select>
                </div>
                <div class="flex items-center gap-6">
                    <span class="ga4-pagination-text">
                        {{ __('Page') }} <strong x-text="sp.events"></strong> {{ __('of') }} <strong x-text="sectionTotalPages('events')"></strong>
                        <span class="ga4-pagination-badge">(<span x-text="sd.events.length"></span> {{ __('results') }})</span>
                    </span>
                    <div class="flex gap-2">
                        <button @click="sectionPrevPage('events')" :disabled="sp.events === 1" class="ga4-pagination-btn">{{ __('Prev') }}</button>
                        <button @click="sectionNextPage('events')" :disabled="sp.events === sectionTotalPages('events')" class="ga4-pagination-btn">{{ __('Next') }}</button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Ad Touchpoints Section --}}
        <div class="ga4-table-container relative">
            <div x-show="sl.adtouchpoints"
                 class="absolute inset-0 z-10 flex items-center justify-center bg-white/50 dark:bg-gray-900/50 backdrop-blur-sm rounded-xl">
                <x-filament::loading-indicator class="h-8 w-8 text-primary-500"/>
            </div>
            <div class="ga4-section-header">
                <h3 x-text="sectionConfig.adtouchpoints.label"></h3>
            </div>
            <div class="tab-nav-ga4">
                <template x-for="t in sectionConfig.adtouchpoints.tabs" :key="t">
                    <div class="tab-ga4" :class="ss.adtouchpoints === t ? 'active' : ''"
                         @click="setSectionSubTab('adtouchpoints', t)" x-text="tabConfig[t]?.label || t"></div>
                </template>
            </div>
            <div class="p-4 border-b border-gray-200 dark:border-white/5 bg-white dark:bg-transparent">
                <div class="relative w-full max-w-md">
                    <div class="absolute inset-y-0 left-0 rtl:right-0 rtl:left-auto w-10 flex items-center justify-center pointer-events-none">
                        <x-heroicon-o-magnifying-glass class="w-4 h-4 text-gray-500 dark:text-gray-400"/>
                    </div>
                    <input type="text" x-model.debounce.300ms="sq.adtouchpoints"
                           class="bg-gray-50 dark:bg-white/5 border border-gray-300 dark:border-white/10 text-gray-900 dark:text-white text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full pl-10 p-2"
                           style="padding-left: 2.75rem;"
                           placeholder="{{ __('Filter rows...') }}">
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="ga4-table">
                    <thead>
                    <tr>
                        <th><span x-text="tabConfig[ss.adtouchpoints]?.label || 'Name'"></span></th>
                        <template x-for="m in (tabConfig[ss.adtouchpoints]?.metrics || [])" :key="m">
                            <th class="metric-cell cursor-pointer" @click="sectionSortBy('adtouchpoints', m)">
                                <span x-text="metricLabels[m] || m"></span>
                                <span x-show="sc.adtouchpoints === m" x-text="sd2.adtouchpoints === 'desc' ? '↓' : '↑'"></span>
                            </th>
                        </template>
                    </tr>
                    </thead>
                    <tbody>
                    <template x-for="(row, idx) in sectionPaginated('adtouchpoints')" :key="(row.id || idx) + '_' + idx">
                        <tr class="transition duration-150 hover:bg-gray-50 dark:hover:bg-white/5">
                            <td>
                                <div class="flex items-center gap-2">
                                    <div class="text-sm font-semibold text-[var(--ga4-text-main)] truncate max-w-md" x-text="row.name" :title="row.name"></div>
                                </div>
                            </td>
                            <template x-for="m in (tabConfig[ss.adtouchpoints]?.metrics || [])" :key="m">
                                <td class="metric-cell">
                                    <div class="metric-val-main" x-text="formatMetricValue(m, row[m])"></div>
                                    <div class="progress-bar-container">
                                        <div class="progress-bar-fill"
                                             :style="`width: ${(row[m] / sectionMaxMetric('adtouchpoints', m)) * 100}%; background: ${metricColors[m] || 'var(--ga4-sessions)'}`"></div>
                                    </div>
                                </td>
                            </template>
                        </tr>
                    </template>
                    <tr x-show="sectionPaginated('adtouchpoints').length === 0">
                        <td :colspan="(tabConfig[ss.adtouchpoints]?.metrics?.length || 6) + 1" class="text-center py-8 text-gray-500 dark:text-gray-400">{{ __('No data available.') }}</td>
                    </tr>
                    </tbody>
                </table>
            </div>
            <div class="ga4-pagination-container" x-show="sd.adtouchpoints.length > 0">
                <div class="flex items-center gap-4 mb-4 sm:mb-0">
                    <span class="ga4-pagination-text font-medium">{{ __('Rows per page:') }}</span>
                    <select x-model="pageSize" class="ga4-pagination-select">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                        <option value="250">250</option>
                    </select>
                </div>
                <div class="flex items-center gap-6">
                    <span class="ga4-pagination-text">
                        {{ __('Page') }} <strong x-text="sp.adtouchpoints"></strong> {{ __('of') }} <strong x-text="sectionTotalPages('adtouchpoints')"></strong>
                        <span class="ga4-pagination-badge">(<span x-text="sd.adtouchpoints.length"></span> {{ __('results') }})</span>
                    </span>
                    <div class="flex gap-2">
                        <button @click="sectionPrevPage('adtouchpoints')" :disabled="sp.adtouchpoints === 1" class="ga4-pagination-btn">{{ __('Prev') }}</button>
                        <button @click="sectionNextPage('adtouchpoints')" :disabled="sp.adtouchpoints === sectionTotalPages('adtouchpoints')" class="ga4-pagination-btn">{{ __('Next') }}</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const registerGa4Dashboard = () => {
                Alpine.data('ga4Dashboard', () => {
                    return {
                        tenantId: '{{ Filament\Facades\Filament::getTenant()->id ?? Filament\Facades\Filament::getTenant()->slug }}',
                        account: @entangle('selectedAccount'),
                        dateStart: @entangle('dateStart'),
                        dateEnd: @entangle('dateEnd'),
                        activeTab: @entangle('activeTab'),

                        isSummaryLoading: false,
                        isChartLoading: false,

                        summary: {sessions: 0, activeUsers: 0, newUsers: 0, conversions: 0, screenPageViews: 0, averageSessionDuration: 0, totalUsers: 0, bounceRate: 0, revenue: 0},
                        previous: {sessions: 0, activeUsers: 0, newUsers: 0, conversions: 0, screenPageViews: 0, averageSessionDuration: 0, totalUsers: 0, bounceRate: 0, revenue: 0},
                        chartDataRaw: [],

                        activeMetrics: {
                            sessions: true,
                            activeUsers: true,
                            newUsers: false,
                            conversions: false,
                            screenPageViews: false,
                            averageSessionDuration: false,
                            bounceRate: false,
                            totalUsers: false,
                            revenue: false,
                        },

                        sd: {
                            campaigns: [],
                            channels: [],
                            traffic: [],
                            acquisition: [],
                            events: [],
                            adtouchpoints: [],
                        },
                        sl: {
                            campaigns: false, channels: false, traffic: false,
                            acquisition: false, events: false, adtouchpoints: false,
                        },
                        ss: {
                            campaigns: 'campaigns', channels: 'channels', traffic: 'traffic_pages',
                            acquisition: 'acquisition_channels', events: 'events',
                            adtouchpoints: 'adtouchpoints_adgroups',
                        },
                        sq: { campaigns: '', channels: '', traffic: '', acquisition: '', events: '', adtouchpoints: '' },
                        sc: {
                            campaigns: 'sessions', channels: 'sessions', traffic: 'sessions',
                            acquisition: 'newUsers', events: 'eventCount', adtouchpoints: 'sessions',
                        },
                        sd2: {
                            campaigns: 'desc', channels: 'desc', traffic: 'desc',
                            acquisition: 'desc', events: 'desc', adtouchpoints: 'desc',
                        },
                        sp: { campaigns: 1, channels: 1, traffic: 1, acquisition: 1, events: 1, adtouchpoints: 1 },
                        pageSize: 10,

                        sectionConfig: {
                            campaigns: {label: 'Campaigns', tabs: ['campaigns', 'adgroups']},
                            channels: {label: 'Channels', tabs: ['channels', 'sources']},
                            traffic: {label: 'Traffic', tabs: ['traffic_pages', 'traffic_countries', 'traffic_devices']},
                            acquisition: {label: 'Acquisition', tabs: ['acquisition_channels']},
                            events: {label: 'Events', tabs: ['events']},
                            adtouchpoints: {label: 'Ad Touchpoints', tabs: ['adtouchpoints_adgroups', 'adtouchpoints_terms', 'adtouchpoints_content']},
                        },

                        tabConfig: {
                            campaigns: {label: 'Campaign',     metrics: ['sessions', 'activeUsers', 'newUsers', 'conversions', 'screenPageViews', 'averageSessionDuration', 'totalUsers', 'revenue']},
                            adgroups:   {label: 'Ad Group',    metrics: ['sessions', 'activeUsers', 'newUsers', 'conversions', 'screenPageViews', 'averageSessionDuration', 'totalUsers', 'revenue']},
                            channels:   {label: 'Channel',     metrics: ['sessions', 'activeUsers', 'newUsers', 'conversions', 'screenPageViews', 'averageSessionDuration', 'totalUsers', 'revenue']},
                            sources:    {label: 'Source/Medium',metrics: ['sessions', 'activeUsers', 'newUsers', 'conversions', 'screenPageViews', 'averageSessionDuration', 'totalUsers', 'revenue']},
                            traffic_pages:     {label: 'Landing Page', metrics: ['sessions', 'screenPageViews', 'bounceRate', 'averageSessionDuration', 'conversions', 'revenue']},
                            traffic_countries: {label: 'Country',      metrics: ['sessions', 'screenPageViews', 'bounceRate', 'averageSessionDuration', 'conversions', 'revenue']},
                            traffic_devices:   {label: 'Device',       metrics: ['sessions', 'screenPageViews', 'bounceRate', 'averageSessionDuration', 'conversions', 'revenue']},
                            acquisition_channels: {label: 'Acq. Channel', metrics: ['newUsers', 'activeUsers', 'totalUsers', 'revenue']},
                            events: {label: 'Event Name', metrics: ['eventCount', 'conversions']},
                            adtouchpoints_adgroups: {label: 'Ad Group',   metrics: ['sessions', 'conversions', 'revenue']},
                            adtouchpoints_terms:    {label: 'Manual Term', metrics: ['sessions', 'conversions', 'revenue']},
                            adtouchpoints_content:  {label: 'Ad Content',  metrics: ['sessions', 'conversions', 'revenue']},
                        },
                        metricLabels: {
                            sessions: 'Sessions', activeUsers: 'Users', newUsers: 'New',
                            conversions: 'Conv.', screenPageViews: 'Page Views',
                            bounceRate: 'Bounce Rate', eventCount: 'Event Count',
                            averageSessionDuration: 'Avg. Duration', totalUsers: 'Total Users'
                        },
                        metricColors: {
                            sessions: 'var(--ga4-sessions)', activeUsers: 'var(--ga4-activeUsers)',
                            newUsers: 'var(--ga4-newUsers)', conversions: 'var(--ga4-conversions)',
                            screenPageViews: 'var(--ga4-pageViews)', bounceRate: 'var(--ga4-revenue)',
                            eventCount: '#8B5CF6', averageSessionDuration: 'var(--ga4-avgSessionDuration)',
                            totalUsers: 'var(--ga4-totalUsers)'
                        },

                        get isAnySectionLoading() {
                            return Object.values(this.sl).some(v => v);
                        },

                        get tabLabel() {
                            return this.tabConfig[this.activeTab]?.label || this.activeTab;
                        },

                        restoreFromUrl() {
                            const params = new URLSearchParams(window.location.search);
                            const accParam = params.get('account');
                            if (accParam) this.account = accParam;
                            const ds = params.get('dateStart');
                            if (ds && /^\d{4}-\d{2}-\d{2}$/.test(ds)) this.dateStart = ds;
                            const de = params.get('dateEnd');
                            if (de && /^\d{4}-\d{2}-\d{2}$/.test(de)) this.dateEnd = de;
                            const tab = params.get('tab');
                            if (tab) this.activeTab = tab;
                            const metricsParam = params.get('metrics');
                            if (metricsParam) {
                                const enabledMetrics = metricsParam.split(',');
                                Object.keys(this.activeMetrics).forEach(key => {
                                    this.activeMetrics[key] = enabledMetrics.includes(key);
                                });
                            }
                        },

                        syncToUrl() {
                            const params = new URLSearchParams();
                            if (this.account) params.set('account', this.account);
                            if (this.dateStart) params.set('dateStart', this.dateStart);
                            if (this.dateEnd) params.set('dateEnd', this.dateEnd);
                            if (this.activeTab) params.set('tab', this.activeTab);
                            const enabledMetrics = Object.entries(this.activeMetrics).filter(([k, v]) => v).map(([k]) => k);
                            if (enabledMetrics.length > 0) params.set('metrics', enabledMetrics.join(','));
                            const qs = params.toString();
                            const url = qs ? window.location.pathname + '?' + qs : window.location.pathname;
                            history.replaceState(null, '', url);
                        },

                        initDashboard() {
                            this.restoreFromUrl();

                            const boot = () => {
                                this.initChart();

                                this.$watch('account', () => {
                                    this.syncToUrl();
                                    this.fetchAll();
                                });

                                this.$watch('dateStart', () => {
                                    this.syncToUrl();
                                    this.fetchAll();
                                });

                                this.$watch('dateEnd', () => {
                                    this.syncToUrl();
                                    this.fetchAll();
                                });

                                this.$watch('pageSize', () => {
                                    Object.keys(this.sp).forEach(k => this.sp[k] = 1);
                                });

                                if (this.account && this.dateStart && this.dateEnd) {
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

                        setSectionSubTab(section, tab) {
                            this.ss[section] = tab;
                            this.sp[section] = 1;
                            this.sq[section] = '';
                            const metrics = this.tabConfig[tab]?.metrics;
                            this.sc[section] = (metrics && metrics.length) ? metrics[0] : 'sessions';
                            this.sd2[section] = 'desc';
                            this.activeTab = tab;
                            this.syncToUrl();
                            this.fetchSection(section);
                            this.$wire.setActiveTab(tab);
                        },

                        forceRefresh() {
                            this.clearCache();
                            this.fetchAll();
                        },

                        clearCache() {
                            const prefix = `ga4_${this.tenantId}_${this.account}_${this.dateStart}_${this.dateEnd}`;
                            Object.keys(sessionStorage).forEach(key => {
                                if (key.startsWith(prefix)) {
                                    sessionStorage.removeItem(key);
                                }
                            });
                        },

                        getCacheKey(endpoint, section = null) {
                            const tab = section ? this.ss[section] : '';
                            return `ga4_${this.tenantId}_${this.account}_${this.dateStart}_${this.dateEnd}_${endpoint}_${tab}`;
                        },

                        safeCacheSet(key, value) {
                            try {
                                sessionStorage.setItem(key, value);
                                return true;
                            } catch (e) {
                                if (e.name === 'QuotaExceededError' || e.code === 22) {
                                    Object.keys(sessionStorage).forEach(k => {
                                        if (k.startsWith('ga4_') || k.startsWith('gsc_') || k.startsWith('fbm_')) {
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

                        async fetchAll() {
                            if (!this.account || !this.dateStart || !this.dateEnd) return;
                            this.fetchSummary();
                            this.fetchChart();
                            Object.keys(this.sectionConfig).forEach(s => this.fetchSection(s));
                        },

                        getFetchOptions(section = null) {
                            const body = {
                                tenant: this.tenantId,
                                account: this.account,
                                dateStart: this.dateStart,
                                dateEnd: this.dateEnd,
                            };
                            if (section) {
                                body.activeTab = this.ss[section];
                            }
                            return {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                },
                                body: JSON.stringify(body)
                            };
                        },

                        normalizeSummaryMetrics(obj) {
                            if (!obj) return {sessions: 0, activeUsers: 0, newUsers: 0, conversions: 0, screenPageViews: 0, averageSessionDuration: 0, totalUsers: 0};
                            return {
                                sessions: obj.sessions ?? 0,
                                activeUsers: obj.activeUsers ?? obj.active_users ?? 0,
                                newUsers: obj.newUsers ?? obj.new_users ?? 0,
                                conversions: obj.conversions ?? 0,
                                screenPageViews: obj.screenPageViews ?? obj.pageviews ?? 0,
                                averageSessionDuration: obj.averageSessionDuration ?? obj.average_session_duration ?? 0,
                                totalUsers: obj.totalUsers ?? obj.total_users ?? 0,
                                bounceRate: obj.bounceRate ?? obj.bounce_rate ?? 0,
                                events: obj.events ?? 0,
                            };
                        },

                        async fetchSummary() {
                            if (!this.account || !this.dateStart || !this.dateEnd) return;
                            const cacheKey = this.getCacheKey('summary');

                            if (sessionStorage.getItem(cacheKey)) {
                                const data = JSON.parse(sessionStorage.getItem(cacheKey));
                                this.summary = this.normalizeSummaryMetrics(data.summary);
                                this.previous = this.normalizeSummaryMetrics(data.previous);
                                return;
                            }

                            this.isSummaryLoading = true;
                            try {
                                const response = await fetch('/api/ga4/summary', this.getFetchOptions());
                                const data = await response.json();
                                if (!data.error) {
                                    this.safeCacheSet(cacheKey, JSON.stringify(data));
                                    this.summary = this.normalizeSummaryMetrics(data.summary);
                                    this.previous = this.normalizeSummaryMetrics(data.previous);
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
                                const response = await fetch('/api/ga4/chart', this.getFetchOptions());
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

                        async fetchSection(section) {
                            if (!this.account || !this.dateStart || !this.dateEnd) return;
                            const cacheKey = this.getCacheKey('table', section);

                            if (sessionStorage.getItem(cacheKey)) {
                                const data = JSON.parse(sessionStorage.getItem(cacheKey));
                                this.sd[section] = data.table || [];
                                return;
                            }

                            this.sl[section] = true;
                            try {
                                const response = await fetch('/api/ga4/table', this.getFetchOptions(section));
                                const data = await response.json();
                                if (!data.error) {
                                    this.safeCacheSet(cacheKey, JSON.stringify(data));
                                    this.sd[section] = data.table || [];
                                    this.sp[section] = 1;
                                }
                            } catch (error) {
                                console.error('Error fetching section ' + section + ':', error);
                            } finally {
                                this.sl[section] = false;
                            }
                        },

                        get variance() {
                            const calc = (current, prev) => {
                                if (!prev || Number(prev) === 0) return 0;
                                return ((Number(current) - Number(prev)) / Number(prev)) * 100;
                            };
                            return {
                                sessions: calc(this.summary.sessions, this.previous.sessions),
                                activeUsers: calc(this.summary.activeUsers, this.previous.activeUsers),
                                newUsers: calc(this.summary.newUsers, this.previous.newUsers),
                                conversions: calc(this.summary.conversions, this.previous.conversions),
                                screenPageViews: calc(this.summary.screenPageViews, this.previous.screenPageViews),
                                averageSessionDuration: calc(this.summary.averageSessionDuration, this.previous.averageSessionDuration),
                                bounceRate: calc(this.summary.bounceRate, this.previous.bounceRate),
                                totalUsers: calc(this.summary.totalUsers, this.previous.totalUsers),
                                revenue: calc(this.summary.revenue, this.previous.revenue),
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
                            this.syncToUrl();
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
                                            usePointStyle: true,
                                            callbacks: {
                                                label: function(context) {
                                                    var label = context.dataset.label || '';
                                                    var value = context.parsed.y;
                                                    if (label === 'Bounce Rate') {
                                                        return label + ': ' + (Number(value) * (value <= 1 ? 100 : 1)).toFixed(2) + '%';
                                                    }
                                                    if (label === 'Avg Duration') {
                                                        var mins = Math.floor(value / 60);
                                                        var secs = Math.floor(value % 60);
                                                        return label + ': ' + String(mins).padStart(2, '0') + ':' + String(secs).padStart(2, '0');
                                                    }
                                                    return label + ': ' + Number(value).toLocaleString('en-US');
                                                }
                                            }
                                        }
                                    },
                                    scales: {
                                        x: {
                                            grid: {color: 'var(--ga4-chart-grid)', drawBorder: false},
                                            ticks: {color: 'var(--ga4-chart-ticks)'}
                                        },
                                        ySessions: {
                                            type: 'linear', position: 'left', display: false,
                                            grid: {color: 'var(--ga4-chart-grid)', drawBorder: false},
                                            ticks: {color: '#4285F4'},
                                            min: 0, suggestedMax: 5
                                        },
                                        yActiveUsers: {
                                            type: 'linear', position: 'right', display: false,
                                            grid: {drawOnChartArea: false, drawBorder: false},
                                            ticks: {color: '#0F9D58'}
                                        },
                                        yNewUsers: {
                                            type: 'linear', position: 'left', display: false,
                                            grid: {drawOnChartArea: false, drawBorder: false},
                                            ticks: {color: '#FBBC04'}
                                        },
                                        yScreenPageViews: {
                                            type: 'linear', position: 'left', display: false,
                                            grid: {drawOnChartArea: false, drawBorder: false},
                                            ticks: {color: '#a855f7'}
                                        },
                                        yConversions: {
                                            type: 'linear', position: 'right', display: false,
                                            grid: {drawOnChartArea: false, drawBorder: false},
                                            ticks: {color: '#EA4335'}
                                        },
                                        yAverageSessionDuration: {
                                            type: 'linear', position: 'left', display: false,
                                            grid: {drawOnChartArea: false, drawBorder: false},
                                            ticks: {color: '#06b6d4'}
                                        },
                                        yBounceRate: {
                                            type: 'linear', position: 'right', display: false,
                                            grid: {drawOnChartArea: false, drawBorder: false},
                                            ticks: {
                                                color: '#ec4899',
                                                callback: function(v) { return (v * (v <= 1 ? 100 : 1)).toFixed(0) + '%'; }
                                            }
                                        },
                                        yTotalUsers: {
                                            type: 'linear', position: 'left', display: false,
                                            grid: {drawOnChartArea: false, drawBorder: false},
                                            ticks: {color: '#6366f1'}
                                        },
                                        yRevenue: {
                                            type: 'linear', position: 'right', display: false,
                                            grid: {drawOnChartArea: false, drawBorder: false},
                                            ticks: {color: '#10b981'}
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
                                    sessions: 0, activeUsers: 0, newUsers: 0,
                                    conversions: 0, screenPageViews: 0, bounceRate: 0,
                                    averageSessionDuration: 0, totalUsers: 0, revenue: 0
                                };
                            });

                            const labels = paddedData.map(r => dayjs(r.daily || r.date || r.metric_date).format('MMM D'));
                            const datasets = [];
                            const chartData = paddedData;

                            if (this.activeMetrics.sessions) {
                                datasets.push({
                                    label: 'Sessions',
                                    data: chartData.map(r => r.sessions),
                                    borderColor: '#4285F4',
                                    backgroundColor: 'rgba(66, 133, 244, 0.1)',
                                    borderWidth: 2,
                                    pointRadius: 0,
                                    pointHoverRadius: 6,
                                    fill: true,
                                    yAxisID: 'ySessions',
                                    tension: 0.4
                                });
                            }

                            if (this.activeMetrics.activeUsers) {
                                datasets.push({
                                    label: 'Active Users',
                                    data: chartData.map(r => r.activeUsers),
                                    borderColor: '#0F9D58',
                                    backgroundColor: 'rgba(15, 157, 88, 0.1)',
                                    borderWidth: 2,
                                    pointRadius: 0,
                                    pointHoverRadius: 6,
                                    fill: true,
                                    yAxisID: 'yActiveUsers',
                                    tension: 0.4
                                });
                            }

                            if (this.activeMetrics.newUsers) {
                                datasets.push({
                                    label: 'New Users',
                                    data: chartData.map(r => r.newUsers),
                                    borderColor: '#FBBC04',
                                    borderWidth: 2,
                                    pointRadius: 0,
                                    pointHoverRadius: 6,
                                    fill: false,
                                    yAxisID: 'yNewUsers',
                                    tension: 0.4
                                });
                            }

                            if (this.activeMetrics.screenPageViews) {
                                datasets.push({
                                    label: 'Pageviews',
                                    data: chartData.map(r => r.screenPageViews),
                                    borderColor: '#a855f7',
                                    borderWidth: 2,
                                    pointRadius: 0,
                                    pointHoverRadius: 6,
                                    fill: false,
                                    yAxisID: 'yScreenPageViews',
                                    tension: 0.4
                                });
                            }

                            if (this.activeMetrics.conversions) {
                                datasets.push({
                                    label: 'Conversions',
                                    data: chartData.map(r => r.conversions),
                                    borderColor: '#EA4335',
                                    borderWidth: 2,
                                    pointRadius: 0,
                                    pointHoverRadius: 6,
                                    fill: false,
                                    yAxisID: 'yConversions',
                                    tension: 0.4
                                });
                            }

                            if (this.activeMetrics.averageSessionDuration) {
                                datasets.push({
                                    label: 'Avg Duration',
                                    data: chartData.map(r => r.averageSessionDuration),
                                    borderColor: '#06b6d4',
                                    borderWidth: 2,
                                    pointRadius: 0,
                                    pointHoverRadius: 6,
                                    fill: false,
                                    yAxisID: 'yAverageSessionDuration',
                                    tension: 0.4
                                });
                            }

                            if (this.activeMetrics.bounceRate) {
                                datasets.push({
                                    label: 'Bounce Rate',
                                    data: chartData.map(r => r.bounceRate),
                                    borderColor: '#ec4899',
                                    borderWidth: 2,
                                    pointRadius: 0,
                                    pointHoverRadius: 6,
                                    fill: false,
                                    yAxisID: 'yBounceRate',
                                    tension: 0.4
                                });
                            }

                            if (this.activeMetrics.totalUsers) {
                                datasets.push({
                                    label: 'Total Users',
                                    data: chartData.map(r => r.totalUsers),
                                    borderColor: '#6366f1',
                                    borderWidth: 2,
                                    pointRadius: 0,
                                    pointHoverRadius: 6,
                                    fill: false,
                                    yAxisID: 'yTotalUsers',
                                    tension: 0.4
                                });
                            }

                            if (this.activeMetrics.revenue) {
                                datasets.push({
                                    label: 'Revenue',
                                    data: chartData.map(r => r.revenue),
                                    borderColor: '#10b981',
                                    borderWidth: 2,
                                    pointRadius: 0,
                                    pointHoverRadius: 6,
                                    fill: false,
                                    yAxisID: 'yRevenue',
                                    tension: 0.4
                                });
                            }

                            let gridDrawn = false;
                            const cssGridColor = getComputedStyle(document.documentElement).getPropertyValue('--ga4-chart-grid').trim();
                            const cssTicksColor = getComputedStyle(document.documentElement).getPropertyValue('--ga4-chart-ticks').trim();

                            chart.options.scales.x.grid.color = cssGridColor;
                            chart.options.scales.x.ticks.color = cssTicksColor;

                            ['sessions', 'activeUsers', 'newUsers', 'screenPageViews', 'conversions', 'averageSessionDuration', 'bounceRate', 'totalUsers', 'revenue'].forEach(m => {
                                let scaleId = 'y' + m.charAt(0).toUpperCase() + m.slice(1);
                                if (chart.options.scales[scaleId]) {
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
                                }
                            });

                            chart.data.labels = labels;
                            chart.data.datasets = datasets;
                            chart.update();
                        },

                        sectionData(section) {
                            let data = [...(this.sd[section] || [])];
                            const query = (this.sq[section] || '').toLowerCase().trim();
                            if (query) {
                                data = data.filter(row => String(row.name || row.id || '').toLowerCase().includes(query));
                            }
                            const metrics = this.tabConfig[this.ss[section]]?.metrics || [];
                            const sortKey = metrics.includes(this.sc[section]) ? this.sc[section] : (metrics[0] || 'sessions');
                            return data.sort((a, b) => {
                                let valA = Number(a[sortKey]);
                                let valB = Number(b[sortKey]);
                                if (isNaN(valA) || isNaN(valB)) {
                                    valA = String(a[sortKey] || '').toLowerCase();
                                    valB = String(b[sortKey] || '').toLowerCase();
                                }
                                if (valA === valB) return 0;
                                if (this.sd2[section] === 'desc') return valA < valB ? 1 : -1;
                                return valA > valB ? 1 : -1;
                            });
                        },

                        sectionTotalPages(section) {
                            return Math.ceil((this.sd[section] || []).length / this.pageSize) || 1;
                        },

                        sectionPaginated(section) {
                            const start = (this.sp[section] - 1) * this.pageSize;
                            const end = start + Number(this.pageSize);
                            return this.sectionData(section).slice(start, end);
                        },

                        sectionNextPage(section) {
                            if (this.sp[section] < this.sectionTotalPages(section)) this.sp[section]++;
                        },

                        sectionPrevPage(section) {
                            if (this.sp[section] > 1) this.sp[section]--;
                        },

                        sectionSortBy(section, col) {
                            if (this.sc[section] === col) {
                                this.sd2[section] = this.sd2[section] === 'desc' ? 'asc' : 'desc';
                            } else {
                                this.sc[section] = col;
                                this.sd2[section] = 'desc';
                            }
                            this.sp[section] = 1;
                        },

                        sectionMaxMetric(section, metric) {
                            const data = this.sectionData(section);
                            if (!data.length) return 1;
                            return Math.max(...data.map(r => r[metric] || 0));
                        },

                        tabForSection(section) {
                            const config = this.sectionConfig[section];
                            if (!config) return [];
                            return config.tabs.map(t => ({
                                key: t,
                                label: this.tabConfig[t]?.label || t,
                                active: this.ss[section] === t
                            }));
                        },

                        formatNumber(num) {
                            if (num === undefined || num === null) return '0';
                            return new Intl.NumberFormat('en-US').format(num);
                        },

                        formatMetricValue(key, value) {
                            if (key === 'averageSessionDuration') {
                                return this.formatDuration(value);
                            }
                            if (key === 'bounceRate') {
                                return this.formatPercent(value);
                            }
                            return this.formatNumber(value);
                        },

                        formatDuration(seconds) {
                            if (!seconds || seconds < 0) return '00:00';
                            const totalSeconds = Math.floor(seconds);
                            const hours = Math.floor(totalSeconds / 3600);
                            const minutes = Math.floor((totalSeconds % 3600) / 60);
                            const secs = totalSeconds % 60;
                            const paddedMinutes = String(minutes).padStart(2, '0');
                            const paddedSeconds = String(secs).padStart(2, '0');
                            if (hours > 0) {
                                return `${String(hours).padStart(2, '0')}:${paddedMinutes}:${paddedSeconds}`;
                            }
                            return `${paddedMinutes}:${paddedSeconds}`;
                        },

                        formatPercent(num) {
                            if (num === undefined || num === null) return '0%';
                            return (num * 100).toFixed(2) + '%';
                        },

                        formatDecimals(num) {
                            if (num === undefined || num === null) return '0.00';
                            return Number(num).toFixed(2);
                        }
                    }
                });
            };

            if (window.Alpine) {
                registerGa4Dashboard();
            } else {
                document.addEventListener('alpine:init', registerGa4Dashboard);
            }
        })();
    </script>
</x-filament-panels::page>
