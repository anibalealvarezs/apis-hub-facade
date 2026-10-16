<x-filament-panels::page>
    <link rel="stylesheet" href="{{ asset('css/dashboards.css') }}">

    <div class="google-ads-page" x-data="googleAdsDashboard({
        tenantId: @js(Filament\Facades\Filament::getTenant()->id ?? Filament\Facades\Filament::getTenant()->slug),
        accounts: @js($selectedAccounts),
        accountNames: @js($accounts),
        dateStart: @js($dateStart),
        dateEnd: @js($dateEnd),
        activeTab: 'campaigns',
        csrfToken: @js(csrf_token())
    })" x-init="initDashboard()">
        <div class="gads-header-row py-3 px-3 mb-6 bg-gray-50/98 dark:bg-gray-900/98 backdrop-blur-md border-b border-gray-200 dark:border-white/10 transition-colors">
            <div class="flex items-center gap-4 flex-wrap">
                <x-ui.export-pdf-button />
                <x-ui.asset-selector model="accounts" options="accountNames" placeholder="{{ __('Select Google Ads Accounts...') }}" :multiple="true" :all-keys="json_encode(array_keys($accounts))" size="sm" />
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

        <div class="dash-overview-section mb-6">
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4 relative">
                <div x-show="isSummaryLoading"
                     class="absolute inset-0 z-10 flex items-center justify-center bg-white/50 dark:bg-gray-900/50 backdrop-blur-sm rounded-xl">
                    <x-filament::loading-indicator class="h-8 w-8 text-primary-500"/>
                </div>

                <div class="card-stat-fb cursor-pointer p-4 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700"
                     :class="activeMetrics.spend ? 'ring-2 ring-primary-500' : ''" @click="toggleMetric('spend')">
                    <div class="text-xs uppercase font-medium text-gray-500 dark:text-gray-400">{{ __('Cost / Spend') }}</div>
                    <div class="text-xl font-bold mt-1 text-gray-900 dark:text-white" x-text="formatCurrency(summary.spend)"></div>
                    <div class="text-xs font-semibold mt-1" :class="getVarianceClass(variance.spend, true)">
                        <span x-text="getVarianceIcon(variance.spend, true)"></span>
                        <span x-text="formatVariance(variance.spend)"></span>
                    </div>
                </div>

                <div class="card-stat-fb cursor-pointer p-4 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700"
                     :class="activeMetrics.clicks ? 'ring-2 ring-primary-500' : ''" @click="toggleMetric('clicks')">
                    <div class="text-xs uppercase font-medium text-gray-500 dark:text-gray-400">{{ __('Clicks') }}</div>
                    <div class="text-xl font-bold mt-1 text-gray-900 dark:text-white" x-text="formatNumber(summary.clicks)"></div>
                    <div class="text-xs font-semibold mt-1" :class="getVarianceClass(variance.clicks)">
                        <span x-text="getVarianceIcon(variance.clicks)"></span>
                        <span x-text="formatVariance(variance.clicks)"></span>
                    </div>
                </div>

                <div class="card-stat-fb cursor-pointer p-4 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700"
                     :class="activeMetrics.impressions ? 'ring-2 ring-primary-500' : ''" @click="toggleMetric('impressions')">
                    <div class="text-xs uppercase font-medium text-gray-500 dark:text-gray-400">{{ __('Impressions') }}</div>
                    <div class="text-xl font-bold mt-1 text-gray-900 dark:text-white" x-text="formatNumber(summary.impressions)"></div>
                    <div class="text-xs font-semibold mt-1" :class="getVarianceClass(variance.impressions)">
                        <span x-text="getVarianceIcon(variance.impressions)"></span>
                        <span x-text="formatVariance(variance.impressions)"></span>
                    </div>
                </div>

                <div class="card-stat-fb cursor-pointer p-4 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700"
                     :class="activeMetrics.conversions ? 'ring-2 ring-primary-500' : ''" @click="toggleMetric('conversions')">
                    <div class="text-xs uppercase font-medium text-gray-500 dark:text-gray-400">{{ __('Conversions') }}</div>
                    <div class="text-xl font-bold mt-1 text-gray-900 dark:text-white" x-text="formatNumber(summary.conversions)"></div>
                    <div class="text-xs font-semibold mt-1" :class="getVarianceClass(variance.conversions)">
                        <span x-text="getVarianceIcon(variance.conversions)"></span>
                        <span x-text="formatVariance(variance.conversions)"></span>
                    </div>
                </div>

                <div class="card-stat-fb cursor-pointer p-4 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700"
                     :class="activeMetrics.conversion_value ? 'ring-2 ring-primary-500' : ''" @click="toggleMetric('conversion_value')">
                    <div class="text-xs uppercase font-medium text-gray-500 dark:text-gray-400">{{ __('Conv. Value') }}</div>
                    <div class="text-xl font-bold mt-1 text-gray-900 dark:text-white" x-text="formatCurrency(summary.conversion_value)"></div>
                    <div class="text-xs font-semibold mt-1" :class="getVarianceClass(variance.conversion_value)">
                        <span x-text="getVarianceIcon(variance.conversion_value)"></span>
                        <span x-text="formatVariance(variance.conversion_value)"></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-100 dark:border-gray-700 mb-6">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">{{ __('Performance Over Time') }}</h3>
            <div id="google-ads-chart"></div>
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
                            <span class="opacity-70 uppercase bd-text-2xs mr-1" x-text="tab + ':'"></span>
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
                    <div class="tab-fb" :class="activeTab === 'adgroups' ? 'active' : ''"
                         @click="setTab('adgroups')">{{ __('AD GROUPS') }}</div>
                    <div class="tab-fb" :class="activeTab === 'ads' ? 'active' : ''"
                         @click="setTab('ads')">{{ __('ADS') }}</div>
                    <div class="tab-fb" :class="activeTab === 'devices' ? 'active' : ''"
                         @click="setTab('devices')">{{ __('DEVICES') }}</div>
                </div>
            </x-slot:header>

            <table class="fb-table">
                <thead>
                <tr>
                    <x-data-table.column sortable="false">
                        <span x-text="activeTab.toUpperCase()"></span>
                    </x-data-table.column>
                    <x-data-table.column state="tableState" key="spend" label="{{ __('Cost') }}"/>
                    <x-data-table.column state="tableState" key="clicks" label="{{ __('Clicks') }}"/>
                    <x-data-table.column state="tableState" key="impressions" label="{{ __('Impressions') }}"/>
                    <x-data-table.column state="tableState" key="ctr" label="{{ __('CTR') }}"/>
                    <x-data-table.column state="tableState" key="cpc" label="{{ __('Avg. CPC') }}"/>
                    <x-data-table.column state="tableState" key="conversions" label="{{ __('Conversions') }}"/>
                    <x-data-table.column state="tableState" key="cost_per_conversion" label="{{ __('Cost / Conv.') }}"/>
                    <x-data-table.column state="tableState" key="conversion_rate" label="{{ __('Conv. Rate') }}"/>
                    <x-data-table.column state="tableState" key="roas_purchase" label="{{ __('ROAS') }}"/>
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
                        <td class="metric-cell" x-text="formatCurrency(row.spend)"></td>
                        <td class="metric-cell" x-text="formatNumber(row.clicks)"></td>
                        <td class="metric-cell" x-text="formatNumber(row.impressions)"></td>
                        <td class="metric-cell" x-text="formatPercent(row.ctr)"></td>
                        <td class="metric-cell" x-text="formatCurrency(row.cpc)"></td>
                        <td class="metric-cell" x-text="formatNumber(row.conversions)"></td>
                        <td class="metric-cell" x-text="formatCurrency(row.cost_per_conversion)"></td>
                        <td class="metric-cell" x-text="formatPercent(row.conversion_rate)"></td>
                        <td class="metric-cell" x-text="formatDecimal(row.roas_purchase) + 'x'"></td>
                    </x-data-table.row>
                </template>
                <tr x-show="tableState.paginatedRows.length === 0">
                    <td colspan="10" class="text-center py-8 text-gray-500 dark:text-gray-400">{{ __('No data available.') }}</td>
                </tr>
                </tbody>
            </table>
        </x-data-table>
    </div>
</x-filament-panels::page>
