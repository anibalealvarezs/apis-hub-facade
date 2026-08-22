<x-filament-panels::page>
    <link rel="stylesheet" href="{{ asset('css/dashboards.css') }}">


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
        <div class="gsc-header-row py-3 px-3 mb-6 bg-gray-50/98 dark:bg-gray-900/98 backdrop-blur-md border-b border-gray-200 dark:border-white/10 transition-colors">
            <div class="gsc-header-controls">
                <x-ui.export-pdf-button />
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
                <div class="relative" x-data="uiDropdown()" @click.outside="open = false"
                     @scroll.document.capture="onScroll($event)" @resize.window="recompute()">
                    <button @click="toggle()" type="button" x-ref="trigger"
class="bg-white dark:bg-white/5 border border-gray-300 dark:border-white/10 text-gray-950 dark:text-white text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 flex items-center justify-between w-full px-4 py-2.5 h-[42px] dash-select-wide">
                        <span class="truncate font-medium text-gray-700 dark:text-gray-200"
                              x-text="!selectedAccount ? '{{ __('Select Property...') }}' : (accountNames[selectedAccount] || selectedAccount)"></span>
                        <x-heroicon-m-chevron-down class="w-4 h-4 ml-2 flex-shrink-0 text-gray-500 dark:text-gray-400"/>
                    </button>

                    <div x-show="open" x-transition x-cloak x-ref="panel"
                         class="dash-dropdown absolute z-50 w-full sm:w-72 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-xl right-0 md:left-0 md:right-auto flex flex-col"
                         :class="dropUp ? 'dropdown-open-above' : ''">

                        <!-- Search Header -->
                        <div class="ui-asset-search-header p-3 border-b border-gray-200 dark:border-gray-700">
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 rtl:right-0 rtl:left-auto w-10 flex items-center justify-center pointer-events-none">
                                    <x-heroicon-o-magnifying-glass class="w-4 h-4 text-gray-500 dark:text-gray-400"/>
                                </div>
                                <input type="text" x-model="searchAccount"
class="bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2 dash-search-input"
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

        <div class="metrics-grid-gsc relative">
            <div x-show="isSummaryLoading"
                 class="absolute inset-0 z-10 flex items-center justify-center bg-white/50 dark:bg-gray-900/50 backdrop-blur-sm rounded-xl">
                <x-filament::loading-indicator class="h-8 w-8 text-primary-500"/>
            </div>

            <div class="card-stat-gsc" :class="activeMetrics.clicks ? 'active' : ''" @click="toggleMetric('clicks')"
                 data-metric="clicks">
                <div class="dash-modal-close text-primary-500 dark:text-primary-400" title="{{ __('Trend Analysis Supported') }}">
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
                 @click="toggleMetric('impressions')" data-metric="impressions">
                <div class="dash-modal-close text-primary-500 dark:text-primary-400" title="{{ __('Trend Analysis Supported') }}">
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
                 data-metric="ctr">
                <div class="gsc-label">{{ __('Average CTR') }}</div>
                <div class="card-metric-value" x-text="formatPercent(summary.ctr)"></div>
                <div class="card-metric-trend" :class="getVarianceClass(variance.ctr)">
                    <span x-text="getVarianceIcon(variance.ctr)"></span>
                    <span x-text="formatVariance(variance.ctr)"></span>
                </div>
            </div>
            <div class="card-stat-gsc" :class="activeMetrics.position ? 'active' : ''" @click="toggleMetric('position')"
                 data-metric="position">
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

        <x-data-table variant="gsc" state="tableState" loading="isTableLoading" search>
            <x-slot:header>
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
            </x-slot:header>

            <table class="gsc-table">
                <thead>
                <tr>
                    <x-data-table.column sortable="false">
                        <span x-text="activeTab.toUpperCase()"></span>
                    </x-data-table.column>
                    <x-data-table.column state="tableState" key="clicks" label="{{ __('Clicks') }}"/>
                    <x-data-table.column state="tableState" key="impressions" label="{{ __('Impressions') }}"/>
                    <x-data-table.column state="tableState" key="ctr" label="{{ __('CTR') }}"/>
                    <x-data-table.column state="tableState" key="position" label="{{ __('Position') }}"/>
                </tr>
                </thead>
                <tbody>
                <template x-for="(row, index) in tableState.paginatedRows" :key="row.id + '_' + index">
                    <x-data-table.row onClick="activeTab !== 'appearances' ? toggleFilter(activeTab, row.id) : null"
                                      clickable="activeTab !== 'appearances'"
                                      active="isFilterActive(activeTab, row.id)"
                                      inactive-class="" disabled-class="">
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
<div class="progress-bar-fill gsc-bar-clicks"
:style="`width: ${(row.clicks / maxClicks) * 100}%`"></div>
                            </div>
                        </td>
                        <td class="metric-cell">
                            <div class="metric-val-main" x-text="formatNumber(row.impressions)"></div>
                            <div class="progress-bar-container">
<div class="progress-bar-fill gsc-bar-impressions"
:style="`width: ${(row.impressions / maxImpressions) * 100}%`"></div>
                            </div>
                        </td>
                        <td class="metric-cell">
                            <div class="metric-val-main" x-text="formatPercent(row.ctr)"></div>
                            <div class="progress-bar-container">
<div class="progress-bar-fill gsc-bar-ctr"
:style="`width: ${row.ctr * 100}%`"></div>
                            </div>
                        </td>
                        <td class="metric-cell">
                            <div class="metric-val-main" x-text="formatDecimals(row.position)"></div>
                        </td>
                    </x-data-table.row>
                </template>
                </tbody>
            </table>
        </x-data-table>
    </div>
</x-filament-panels::page>


