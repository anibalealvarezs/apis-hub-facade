<x-filament-panels::page>
    <link rel="stylesheet" href="{{ asset('css/dashboards.css') }}">

    <div class="fb-marketing-page" x-data="fbDashboard({
        tenantId: @js(Filament\Facades\Filament::getTenant()->id ?? Filament\Facades\Filament::getTenant()->slug),
        accounts: @js($selectedAccounts),
        accountNames: @js($accounts),
        dateStart: @js($dateStart),
        dateEnd: @js($dateEnd),
        activeTab: 'campaigns',
        csrfToken: @js(csrf_token())
    })" x-init="initDashboard()">
        <div class="fb-header-row py-3 px-3 mb-6 bg-gray-50/98 dark:bg-gray-900/98 backdrop-blur-md border-b border-gray-200 dark:border-white/10 transition-colors">
            <div class="fb-header-controls">
                <x-ui.export-pdf-button />
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
                <x-ui.asset-selector model="accounts" options="accountNames" placeholder="{{ __('Select Ad Accounts...') }}" :multiple="true" :all-keys="json_encode(array_keys($accounts))" size="sm" />
                <x-ui.date-input x-model.lazy="dateStart" class="w-40" />
                <x-ui.date-input x-model.lazy="dateEnd" max="{{ date('Y-m-d', strtotime('-1 day')) }}" class="w-40" />
                <button type="button" @click="forceRefresh()"
                        class="flex items-center justify-center bg-primary-600 hover:bg-primary-500 text-white text-sm font-medium rounded-lg px-4 py-2.5 transition duration-75 shadow-sm"
                        :class="{ 'opacity-50 cursor-not-allowed': isSummaryLoading || isChartLoading || isTableLoading }"
                        :disabled="isSummaryLoading || isChartLoading || isTableLoading">
                    <x-heroicon-o-arrow-path class="w-5 h-5 mr-2"
                                             x-bind:class="{ 'animate-spin': isSummaryLoading || isChartLoading || isTableLoading }"/>
                    <span>{{ __('Update') }}</span>
                </button>
            </div>
        </div>

        <div class="metrics-grid-fb relative">
            <div x-show="isSummaryLoading"
                 class="absolute inset-0 z-10 flex items-center justify-center bg-white/50 dark:bg-gray-900/50 backdrop-blur-sm rounded-xl">
                <x-filament::loading-indicator class="h-8 w-8 text-primary-500"/>
            </div>

            <div class="card-stat-fb" :class="activeMetrics.spend ? 'active' : ''" @click="toggleMetric('spend')"
                 data-metric="spend">
                <div class="fb-label">{{ __('Amount Spent') }}</div>
                <div class="card-metric-value" x-text="formatCurrency(summary.spend)"></div>
                <div class="card-metric-trend" :class="getVarianceClass(variance.spend, true)">
                    <span x-text="getVarianceIcon(variance.spend, true)"></span>
                    <span x-text="formatVariance(variance.spend)"></span>
                </div>
            </div>
            <div class="card-stat-fb" :class="activeMetrics.impressions ? 'active' : ''"
                 @click="toggleMetric('impressions')" data-metric="impressions">
                <div class="fb-label">{{ __('Impressions') }}</div>
                <div class="card-metric-value" x-text="formatNumber(summary.impressions)"></div>
                <div class="card-metric-trend" :class="getVarianceClass(variance.impressions)">
                    <span x-text="getVarianceIcon(variance.impressions)"></span>
                    <span x-text="formatVariance(variance.impressions)"></span>
                </div>
            </div>
            <div class="card-stat-fb" :class="activeMetrics.reach ? 'active' : ''"
                 @click="toggleMetric('reach')" data-metric="reach">
                <div class="fb-label">{{ __('Reach') }}</div>
                <div class="card-metric-value" x-text="formatNumber(summary.reach)"></div>
                <div class="card-metric-trend" :class="getVarianceClass(variance.reach)">
                    <span x-text="getVarianceIcon(variance.reach)"></span>
                    <span x-text="formatVariance(variance.reach)"></span>
                </div>
            </div>
            <div class="card-stat-fb" :class="activeMetrics.frequency ? 'active' : ''"
                 @click="toggleMetric('frequency')" data-metric="frequency">
                <div class="fb-label">{{ __('Frequency') }}</div>
                <div class="card-metric-value" x-text="formatDecimal(summary.frequency)"></div>
                <div class="card-metric-trend" :class="getVarianceClass(variance.frequency, true)">
                    <span x-text="getVarianceIcon(variance.frequency, true)"></span>
                    <span x-text="formatVariance(variance.frequency)"></span>
                </div>
            </div>
            <div class="card-stat-fb" :class="activeMetrics.cpm ? 'active' : ''"
                 @click="toggleMetric('cpm')" data-metric="cpm">
                <div class="fb-label">{{ __('CPM') }}</div>
                <div class="card-metric-value" x-text="formatCurrency(summary.cpm)"></div>
                <div class="card-metric-trend" :class="getVarianceClass(variance.cpm, true)">
                    <span x-text="getVarianceIcon(variance.cpm, true)"></span>
                    <span x-text="formatVariance(variance.cpm)"></span>
                </div>
            </div>
            <div class="card-stat-fb" :class="activeMetrics.clicks ? 'active' : ''" @click="toggleMetric('clicks')"
                 data-metric="clicks">
                <div class="fb-label">{{ __('Link Clicks') }}</div>
                <div class="card-metric-value" x-text="formatNumber(summary.clicks)"></div>
                <div class="card-metric-trend" :class="getVarianceClass(variance.clicks)">
                    <span x-text="getVarianceIcon(variance.clicks)"></span>
                    <span x-text="formatVariance(variance.clicks)"></span>
                </div>
            </div>
            <div class="card-stat-fb" :class="activeMetrics.ctr ? 'active' : ''" @click="toggleMetric('ctr')"
                 data-metric="ctr">
                <div class="fb-label">{{ __('CTR (Link)') }}</div>
                <div class="card-metric-value" x-text="formatPercent(summary.ctr)"></div>
                <div class="card-metric-trend" :class="getVarianceClass(variance.ctr)">
                    <span x-text="getVarianceIcon(variance.ctr)"></span>
                    <span x-text="formatVariance(variance.ctr)"></span>
                </div>
            </div>
            <div class="card-stat-fb" :class="activeMetrics.cpc ? 'active' : ''" @click="toggleMetric('cpc')"
                 data-metric="cpc">
                <div class="fb-label">{{ __('CPC (Link)') }}</div>
                <div class="card-metric-value" x-text="formatCurrency(summary.cpc)"></div>
                <div class="card-metric-trend" :class="getVarianceClass(variance.cpc, true)">
                    <span x-text="getVarianceIcon(variance.cpc, true)"></span>
                    <span x-text="formatVariance(variance.cpc)"></span>
                </div>
            </div>
            <div class="card-stat-fb" :class="activeMetrics.results ? 'active' : ''" @click="toggleMetric('results')"
                 data-metric="results">
                <div class="fb-label">{{ __('Purchases/Results') }}</div>
                <div class="card-metric-value" x-text="formatNumber(summary.results)"></div>
                <div class="card-metric-trend" :class="getVarianceClass(variance.results)">
                    <span x-text="getVarianceIcon(variance.results)"></span>
                    <span x-text="formatVariance(variance.results)"></span>
                </div>
            </div>
            <div class="card-stat-fb" :class="activeMetrics.cost_per_result ? 'active' : ''"
                 @click="toggleMetric('cost_per_result')" data-metric="cost_per_result">
                <div class="dash-modal-close text-primary-500 dark:text-primary-400" title="{{ __('Trend Analysis Supported') }}">
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
                 @click="toggleMetric('result_rate')" data-metric="result_rate">
                <div class="fb-label">{{ __('Result Rate') }}</div>
                <div class="card-metric-value" x-text="formatPercent(summary.result_rate)"></div>
                <div class="card-metric-trend" :class="getVarianceClass(variance.result_rate)">
                    <span x-text="getVarianceIcon(variance.result_rate)"></span>
                    <span x-text="formatVariance(variance.result_rate)"></span>
                </div>
            </div>
            <div class="card-stat-fb" :class="activeMetrics.purchase_roas ? 'active' : ''"
                 @click="toggleMetric('purchase_roas')" data-metric="purchase_roas">
                <div class="dash-modal-close text-primary-500 dark:text-primary-400" title="{{ __('Trend Analysis Supported') }}">
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
            <div class="dash-chart-canvas">
                <canvas x-ref="canvas"></canvas>
            </div>
        </div>

        <div x-show="hasAnyFilters" x-cloak
             class="mb-6 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 shadow-sm"
             x-transition>
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

        <x-data-table variant="fb" state="tableState" loading="isTableLoading" search>
            <x-slot:header>
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
            </x-slot:header>

            <table class="fb-table">
                <thead>
                <tr>
                    <x-data-table.column sortable="false">
                        <span x-text="activeTab.toUpperCase()"></span>
                    </x-data-table.column>
                    <x-data-table.column sortable="false" label="{{ __('Delivery') }}"
                                         x-show="['campaigns', 'adsets', 'ads'].includes(activeTab)"/>
                    <x-data-table.column state="tableState" key="spend" label="{{ __('Amount Spent') }}"/>
                    <x-data-table.column state="tableState" key="impressions" label="{{ __('Impressions') }}"/>
                    <x-data-table.column state="tableState" key="reach" label="{{ __('Reach') }}"/>
                    <x-data-table.column state="tableState" key="frequency" label="{{ __('Freq.') }}"/>
                    <x-data-table.column state="tableState" key="cpm" label="{{ __('CPM') }}"/>
                    <x-data-table.column state="tableState" key="clicks" label="{{ __('Link Clicks') }}"/>
                    <x-data-table.column state="tableState" key="results" label="{{ __('Purchases') }}"/>
                    <x-data-table.column state="tableState" key="cost_per_result" label="{{ __('CPR') }}"/>
                    <x-data-table.column state="tableState" key="result_rate" label="{{ __('RR') }}"/>
                    <x-data-table.column state="tableState" key="purchase_roas" label="{{ __('ROAS') }}"/>
                </tr>
                </thead>
                <tbody>
                <template x-for="(row, index) in tableState.paginatedRows" :key="row.id + '_' + index">
                    <x-data-table.row onClick="canFilter(activeTab) ? toggleFilter(activeTab, row) : null"
                                      clickable="canFilter(activeTab)"
                                      active="isFilterActive(activeTab, row.id)"
                                      inactive-class="">
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
                    </x-data-table.row>
                </template>
                <tr x-show="tableState.paginatedRows.length === 0">
                    <td colspan="9"
                        class="text-center py-8 text-gray-500 dark:text-gray-400">{{ __('No data available.') }}</td>
                </tr>
                </tbody>
            </table>
        </x-data-table>
    </div>
</x-filament-panels::page>


