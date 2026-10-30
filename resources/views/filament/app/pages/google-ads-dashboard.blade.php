<x-filament-panels::page>
    <link rel="stylesheet" href="{{ asset('css/dashboards.css') }}">

    <div class="fb-marketing-page google-ads-page" x-data="googleAdsDashboard({
        tenantId: @js(Filament\Facades\Filament::getTenant()->id ?? Filament\Facades\Filament::getTenant()->slug),
        accounts: @js($selectedAccounts),
        accountNames: @js($accounts),
        dateStart: @js($dateStart),
        dateEnd: @js($dateEnd),
        activeTab: 'campaigns',
        csrfToken: @js(csrf_token())
    })" x-init="initDashboard()">
        <div class="fb-header-row py-3 px-3 mb-6 bg-gray-50/98 dark:bg-gray-900/98 backdrop-blur-md border-b border-gray-200 dark:border-white/10 transition-colors">
            <div class="fb-header-controls flex-wrap">
                <x-ui.export-pdf-button />
                <x-ui.asset-selector model="accounts" options="accountNames" placeholder="{{ __('Select Google Ads Accounts...') }}" :multiple="true" :all-keys="json_encode(array_keys($accounts))" size="sm" />
                <x-ui.asset-selector model="activeFilters.campaign_types" options="campaignTypeOptions" placeholder="{{ __('All Campaign Types') }}" :multiple="true" size="sm" changeEvent="filterDropdownChanged()" />
                <x-ui.asset-selector model="activeFilters.intents" options="intentOptions" placeholder="{{ __('All Search Intents') }}" :multiple="true" size="sm" changeEvent="filterDropdownChanged()" />
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

        <div class="dash-overview-section">
            <div class="metrics-grid-fb relative">
                <div x-show="isSummaryLoading"
                     class="absolute inset-0 z-10 flex items-center justify-center bg-white/50 dark:bg-gray-900/50 backdrop-blur-sm rounded-xl">
                    <x-filament::loading-indicator class="h-8 w-8 text-primary-500"/>
                </div>

                <div class="card-stat-fb" :class="activeMetrics.spend ? 'active' : ''" @click="toggleMetric('spend')"
                     data-metric="spend">
                    <div class="fb-label">{{ __('Cost / Spend') }}</div>
                    <div class="card-metric-value" x-text="formatCurrency(summary.spend)"></div>
                    <div class="card-metric-trend" :class="getVarianceClass(variance.spend, true)">
                        <span x-text="getVarianceIcon(variance.spend, true)"></span>
                        <span x-text="formatVariance(variance.spend)"></span>
                    </div>
                </div>

                <div class="card-stat-fb" :class="activeMetrics.clicks ? 'active' : ''" @click="toggleMetric('clicks')"
                     data-metric="clicks">
                    <div class="fb-label">{{ __('Clicks') }}</div>
                    <div class="card-metric-value" x-text="formatNumber(summary.clicks)"></div>
                    <div class="card-metric-trend" :class="getVarianceClass(variance.clicks)">
                        <span x-text="getVarianceIcon(variance.clicks)"></span>
                        <span x-text="formatVariance(variance.clicks)"></span>
                    </div>
                </div>

                <div class="card-stat-fb" :class="activeMetrics.impressions ? 'active' : ''" @click="toggleMetric('impressions')"
                     data-metric="impressions">
                    <div class="fb-label">{{ __('Impressions') }}</div>
                    <div class="card-metric-value" x-text="formatNumber(summary.impressions)"></div>
                    <div class="card-metric-trend" :class="getVarianceClass(variance.impressions)">
                        <span x-text="getVarianceIcon(variance.impressions)"></span>
                        <span x-text="formatVariance(variance.impressions)"></span>
                    </div>
                </div>

                <div class="card-stat-fb" :class="activeMetrics.conversions ? 'active' : ''" @click="toggleMetric('conversions')"
                     data-metric="conversions">
                    <div class="fb-label">{{ __('Conversions') }}</div>
                    <div class="card-metric-value" x-text="formatNumber(summary.conversions)"></div>
                    <div class="card-metric-trend" :class="getVarianceClass(variance.conversions)">
                        <span x-text="getVarianceIcon(variance.conversions)"></span>
                        <span x-text="formatVariance(variance.conversions)"></span>
                    </div>
                </div>

                <div class="card-stat-fb" :class="activeMetrics.conversion_value ? 'active' : ''" @click="toggleMetric('conversion_value')"
                     data-metric="conversion_value">
                    <div class="fb-label">{{ __('Conv. Value') }}</div>
                    <div class="card-metric-value" x-text="formatCurrency(summary.conversion_value)"></div>
                    <div class="card-metric-trend" :class="getVarianceClass(variance.conversion_value)">
                        <span x-text="getVarianceIcon(variance.conversion_value)"></span>
                        <span x-text="formatVariance(variance.conversion_value)"></span>
                    </div>
                </div>

                <div class="card-stat-fb" :class="activeMetrics.ctr ? 'active' : ''" @click="toggleMetric('ctr')"
                     data-metric="ctr">
                    <div class="fb-label">{{ __('CTR') }}</div>
                    <div class="card-metric-value" x-text="formatPercent(summary.ctr)"></div>
                    <div class="card-metric-trend" :class="getVarianceClass(variance.ctr)">
                        <span x-text="getVarianceIcon(variance.ctr)"></span>
                        <span x-text="formatVariance(variance.ctr)"></span>
                    </div>
                </div>

                <div class="card-stat-fb" :class="activeMetrics.cpc ? 'active' : ''" @click="toggleMetric('cpc')"
                     data-metric="cpc">
                    <div class="fb-label">{{ __('Avg. CPC') }}</div>
                    <div class="card-metric-value" x-text="formatCurrency(summary.cpc)"></div>
                    <div class="card-metric-trend" :class="getVarianceClass(variance.cpc, true)">
                        <span x-text="getVarianceIcon(variance.cpc, true)"></span>
                        <span x-text="formatVariance(variance.cpc)"></span>
                    </div>
                </div>

                <div class="card-stat-fb" :class="activeMetrics.cost_per_conversion ? 'active' : ''" @click="toggleMetric('cost_per_conversion')"
                     data-metric="cost_per_conversion">
                    <div class="fb-label">{{ __('Cost / Conv.') }}</div>
                    <div class="card-metric-value" x-text="formatCurrency(summary.cost_per_conversion)"></div>
                    <div class="card-metric-trend" :class="getVarianceClass(variance.cost_per_conversion, true)">
                        <span x-text="getVarianceIcon(variance.cost_per_conversion, true)"></span>
                        <span x-text="formatVariance(variance.cost_per_conversion)"></span>
                    </div>
                </div>

                <div class="card-stat-fb" :class="activeMetrics.conversion_rate ? 'active' : ''" @click="toggleMetric('conversion_rate')"
                     data-metric="conversion_rate">
                    <div class="fb-label">{{ __('Conv. Rate') }}</div>
                    <div class="card-metric-value" x-text="formatPercent(summary.conversion_rate)"></div>
                    <div class="card-metric-trend" :class="getVarianceClass(variance.conversion_rate)">
                        <span x-text="getVarianceIcon(variance.conversion_rate)"></span>
                        <span x-text="formatVariance(variance.conversion_rate)"></span>
                    </div>
                </div>

                <div class="card-stat-fb" :class="activeMetrics.roas_purchase ? 'active' : ''" @click="toggleMetric('roas_purchase')"
                     data-metric="roas_purchase">
                    <div class="fb-label">{{ __('ROAS') }}</div>
                    <div class="card-metric-value" x-text="formatDecimal(summary.roas_purchase) + 'x'"></div>
                    <div class="card-metric-trend" :class="getVarianceClass(variance.roas_purchase)">
                        <span x-text="getVarianceIcon(variance.roas_purchase)"></span>
                        <span x-text="formatVariance(variance.roas_purchase)"></span>
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
                            <span class="opacity-70 uppercase bd-text-2xs mr-1" x-text="formatFilterTab(tab) + ':'"></span>
                            <span x-text="getFilterLabel(tab, val)" class="max-w-xs truncate"
                                  :title="getFilterLabel(tab, val)"></span>
                            <button @click.stop="removeFilter(tab, val)"
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
                    <div class="tab-fb" :class="activeTab === 'search_terms' ? 'active' : ''"
                         @click="setTab('search_terms')">{{ __('SEARCH TERMS') }}</div>
                </div>
                <x-entity-attribute-filters />
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
                    <x-data-table.column state="tableState" key="conversion_value" label="{{ __('Conv. Value') }}"/>
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
                            <div class="flex items-center gap-2 flex-wrap">
                                <div x-show="isFilterActive(activeTab, row.id)" class="text-primary-500">
                                    <x-heroicon-s-check-circle class="w-4 h-4"/>
                                </div>
                                <span x-text="row.name"></span>
                                <template x-if="row.campaign_type">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300 border border-blue-200 dark:border-blue-800"
                                          x-text="formatCampaignType(row.campaign_type)"></span>
                                </template>
                                <template x-if="row.intent">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold uppercase tracking-wider"
                                          :class="getIntentBadgeClass(row.intent)"
                                          x-text="row.intent"></span>
                                </template>
                            </div>
                        </td>
                        <td class="metric-cell" x-text="formatCurrency(row.spend)"></td>
                        <td class="metric-cell" x-text="formatNumber(row.clicks)"></td>
                        <td class="metric-cell" x-text="formatNumber(row.impressions)"></td>
                        <td class="metric-cell" x-text="formatPercent(row.ctr)"></td>
                        <td class="metric-cell" x-text="formatCurrency(row.cpc)"></td>
                        <td class="metric-cell" x-text="formatNumber(row.conversions)"></td>
                        <td class="metric-cell" x-text="formatCurrency(row.conversion_value)"></td>
                        <td class="metric-cell" x-text="formatCurrency(row.cost_per_conversion)"></td>
                        <td class="metric-cell" x-text="formatPercent(row.conversion_rate)"></td>
                        <td class="metric-cell" x-text="formatDecimal(row.roas_purchase) + 'x'"></td>
                    </x-data-table.row>
                </template>
                <tr x-show="tableState.paginatedRows.length === 0">
                    <td colspan="11" class="text-center py-8 text-gray-500 dark:text-gray-400">{{ __('No data available.') }}</td>
                </tr>
                </tbody>
            </table>
        </x-data-table>
    </div>
</x-filament-panels::page>
