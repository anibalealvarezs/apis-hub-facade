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

    <div x-data="ga4Dashboard({
        tenantId: @js(Filament\Facades\Filament::getTenant()->id ?? Filament\Facades\Filament::getTenant()->slug),
        account: @entangle('selectedAccount'),
        selectedAccount: @entangle('selectedAccount'),
        accountNames: @js($accounts),
        dateStart: @entangle('dateStart'),
        dateEnd: @entangle('dateEnd'),
        activeTab: @entangle('activeTab'),
        csrfToken: @js(csrf_token())
    })" x-init="initDashboard()">
        <div class="ga4-header-row sticky top-16 z-20 py-4 -mt-4 bg-gray-50/90 dark:bg-gray-900/90 backdrop-blur-md border-b border-gray-200 dark:border-white/10">
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
                         @click="setSectionSubTab('campaigns', t)" x-text="(tabConfig[t] && tabConfig[t].label) || t"></div>
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
                        <th><span x-text="(tabConfig[ss.campaigns] && tabConfig[ss.campaigns].label) || 'Name'"></span></th>
                        <template x-for="m in ((tabConfig[ss.campaigns] && tabConfig[ss.campaigns].metrics) || [])" :key="m">
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
                            <template x-for="m in ((tabConfig[ss.campaigns] && tabConfig[ss.campaigns].metrics) || [])" :key="m">
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
                        <td :colspan="((tabConfig[ss.campaigns] && tabConfig[ss.campaigns].metrics && tabConfig[ss.campaigns].metrics.length) || 6) + 1" class="text-center py-8 text-gray-500 dark:text-gray-400">{{ __('No data available.') }}</td>
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
                         @click="setSectionSubTab('channels', t)" x-text="(tabConfig[t] && tabConfig[t].label) || t"></div>
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
                        <th><span x-text="(tabConfig[ss.channels] && tabConfig[ss.channels].label) || 'Name'"></span></th>
                        <template x-for="m in ((tabConfig[ss.channels] && tabConfig[ss.channels].metrics) || [])" :key="m">
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
                            <template x-for="m in ((tabConfig[ss.channels] && tabConfig[ss.channels].metrics) || [])" :key="m">
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
                        <td :colspan="((tabConfig[ss.channels] && tabConfig[ss.channels].metrics && tabConfig[ss.channels].metrics.length) || 6) + 1" class="text-center py-8 text-gray-500 dark:text-gray-400">{{ __('No data available.') }}</td>
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
                         @click="setSectionSubTab('traffic', t)" x-text="(tabConfig[t] && tabConfig[t].label) || t"></div>
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
                        <th><span x-text="(tabConfig[ss.traffic] && tabConfig[ss.traffic].label) || 'Name'"></span></th>
                        <template x-for="m in ((tabConfig[ss.traffic] && tabConfig[ss.traffic].metrics) || [])" :key="m">
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
                            <template x-for="m in ((tabConfig[ss.traffic] && tabConfig[ss.traffic].metrics) || [])" :key="m">
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
                        <td :colspan="((tabConfig[ss.traffic] && tabConfig[ss.traffic].metrics && tabConfig[ss.traffic].metrics.length) || 6) + 1" class="text-center py-8 text-gray-500 dark:text-gray-400">{{ __('No data available.') }}</td>
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
                         @click="setSectionSubTab('acquisition', t)" x-text="(tabConfig[t] && tabConfig[t].label) || t"></div>
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
                        <th><span x-text="(tabConfig[ss.acquisition] && tabConfig[ss.acquisition].label) || 'Name'"></span></th>
                        <template x-for="m in ((tabConfig[ss.acquisition] && tabConfig[ss.acquisition].metrics) || [])" :key="m">
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
                            <template x-for="m in ((tabConfig[ss.acquisition] && tabConfig[ss.acquisition].metrics) || [])" :key="m">
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
                        <td :colspan="((tabConfig[ss.acquisition] && tabConfig[ss.acquisition].metrics && tabConfig[ss.acquisition].metrics.length) || 6) + 1" class="text-center py-8 text-gray-500 dark:text-gray-400">{{ __('No data available.') }}</td>
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
                         @click="setSectionSubTab('events', t)" x-text="(tabConfig[t] && tabConfig[t].label) || t"></div>
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
                        <th><span x-text="(tabConfig[ss.events] && tabConfig[ss.events].label) || 'Name'"></span></th>
                        <template x-for="m in ((tabConfig[ss.events] && tabConfig[ss.events].metrics) || [])" :key="m">
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
                            <template x-for="m in ((tabConfig[ss.events] && tabConfig[ss.events].metrics) || [])" :key="m">
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
                        <td :colspan="((tabConfig[ss.events] && tabConfig[ss.events].metrics && tabConfig[ss.events].metrics.length) || 6) + 1" class="text-center py-8 text-gray-500 dark:text-gray-400">{{ __('No data available.') }}</td>
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
                         @click="setSectionSubTab('adtouchpoints', t)" x-text="(tabConfig[t] && tabConfig[t].label) || t"></div>
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
                        <th><span x-text="(tabConfig[ss.adtouchpoints] && tabConfig[ss.adtouchpoints].label) || 'Name'"></span></th>
                        <template x-for="m in ((tabConfig[ss.adtouchpoints] && tabConfig[ss.adtouchpoints].metrics) || [])" :key="m">
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
                            <template x-for="m in ((tabConfig[ss.adtouchpoints] && tabConfig[ss.adtouchpoints].metrics) || [])" :key="m">
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
                        <td :colspan="((tabConfig[ss.adtouchpoints] && tabConfig[ss.adtouchpoints].metrics && tabConfig[ss.adtouchpoints].metrics.length) || 6) + 1" class="text-center py-8 text-gray-500 dark:text-gray-400">{{ __('No data available.') }}</td>
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
</x-filament-panels::page>
