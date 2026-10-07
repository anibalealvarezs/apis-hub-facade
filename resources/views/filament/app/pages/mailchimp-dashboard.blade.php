<x-filament-panels::page>
    <link rel="stylesheet" href="{{ asset('css/dashboards.css') }}">

    <div x-data="mailchimpDashboard({
        tenantId: @js(Filament\Facades\Filament::getTenant()->id ?? Filament\Facades\Filament::getTenant()->slug),
        account: @entangle('selectedAccount'),
        selectedAccount: @entangle('selectedAccount'),
        accountNames: @js($accounts),
        dateStart: @entangle('dateStart'),
        dateEnd: @entangle('dateEnd'),
        activeTab: @entangle('activeTab'),
        csrfToken: @js(csrf_token())
    })" x-init="initDashboard()">
        <div class="mailchimp-header-row py-3 px-3 mb-6 bg-gray-50/98 dark:bg-gray-900/98 backdrop-blur-md border-b border-gray-200 dark:border-white/10 transition-colors">
            <div class="mailchimp-header-controls">
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
                              x-text="!selectedAccount ? '{{ __('Select Audience...') }}' : (accountNames[selectedAccount] || selectedAccount)"></span>
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
                                       placeholder="{{ __('Search audiences...') }}">
                            </div>
                        </div>

                        <!-- Accounts List -->
                        <div class="p-2 flex flex-col gap-1 overflow-y-auto max-h-96">
                            @if(count($accounts) === 0)
                                <div class="px-3 py-2 text-sm text-gray-500 dark:text-gray-400 italic">{{ __('No audiences available.') }}</div>
                            @endif
                            @foreach($accounts as $id => $name)
                                <div
                                    x-show="searchAccount === '' || '{{ strtolower(addslashes($name)) }}'.includes(searchAccount.toLowerCase()) || '{{ strtolower($id) }}'.includes(searchAccount.toLowerCase())"
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
                                              title="{{ $name }}">{{ $name }}</span>
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

        <div class="dash-overview-section">
            <div class="metrics-grid-mailchimp relative">
                <div x-show="isSummaryLoading"
                     class="absolute inset-0 z-10 flex items-center justify-center bg-white/50 dark:bg-gray-900/50 backdrop-blur-sm rounded-xl">
                    <x-filament::loading-indicator class="h-8 w-8 text-primary-500"/>
                </div>

                <div class="card-stat-mailchimp" :class="activeMetrics.sends ? 'active' : ''" @click="toggleMetric('sends')"
                     data-metric="sends">
                    <div class="dash-modal-close text-primary-500 dark:text-primary-400" title="{{ __('Trend Analysis Supported') }}">
                        <x-heroicon-s-presentation-chart-line class="w-4 h-4 opacity-50" />
                    </div>
                    <div class="mailchimp-label">{{ __('Total Emails Sent') }}</div>
                    <div class="card-metric-value" x-text="formatNumber(summary.sends)"></div>
                    <div class="card-metric-trend" :class="getVarianceClass(variance.sends)">
                        <span x-text="getVarianceIcon(variance.sends)"></span>
                        <span x-text="formatVariance(variance.sends)"></span>
                    </div>
                </div>

                <div class="card-stat-mailchimp" :class="activeMetrics.opens ? 'active' : ''" @click="toggleMetric('opens')"
                     data-metric="opens">
                    <div class="dash-modal-close text-primary-500 dark:text-primary-400" title="{{ __('Trend Analysis Supported') }}">
                        <x-heroicon-s-presentation-chart-line class="w-4 h-4 opacity-50" />
                    </div>
                    <div class="mailchimp-label">{{ __('Total Opens') }}</div>
                    <div class="card-metric-value" x-text="formatNumber(summary.opens)"></div>
                    <div class="flex items-center gap-1.5 mt-2">
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[9.5px] font-semibold bg-emerald-500/20 text-emerald-300 whitespace-nowrap"
                              title="{{ __('Standard human opens by recipients') }}">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                            <span x-text="formatNumber(summary.opens_standard) + ' {{ __('Human') }}'"></span>
                        </span>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[9.5px] font-semibold bg-purple-500/20 text-purple-300 whitespace-nowrap"
                              title="{{ __('Preloaded by Apple Mail Privacy Protection (MPP) proxy') }}">
                            <span class="w-1.5 h-1.5 rounded-full bg-purple-400"></span>
                            <span x-text="formatNumber(summary.opens_proxy) + ' {{ __('MPP') }}'"></span>
                        </span>
                    </div>
                    <div class="card-metric-trend mt-2" :class="getVarianceClass(variance.opens)">
                        <span x-text="getVarianceIcon(variance.opens)"></span>
                        <span x-text="formatVariance(variance.opens)"></span>
                    </div>
                </div>

                <div class="card-stat-mailchimp" :class="activeMetrics.clicks ? 'active' : ''" @click="toggleMetric('clicks')"
                     data-metric="clicks">
                    <div class="dash-modal-close text-primary-500 dark:text-primary-400" title="{{ __('Trend Analysis Supported') }}">
                        <x-heroicon-s-presentation-chart-line class="w-4 h-4 opacity-50" />
                    </div>
                    <div class="mailchimp-label">{{ __('Total Clicks') }}</div>
                    <div class="card-metric-value" x-text="formatNumber(summary.clicks)"></div>
                    <div class="flex items-center gap-1.5 mt-2">
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[9.5px] font-semibold bg-blue-500/20 text-blue-300 whitespace-nowrap"
                              title="{{ __('Unique individual contacts who clicked at least one link') }}">
                            <span class="w-1.5 h-1.5 rounded-full bg-blue-400"></span>
                            <span x-text="formatNumber(summary.clicks_unique) + ' {{ __('Unique') }}'"></span>
                        </span>
                    </div>
                    <div class="card-metric-trend mt-2" :class="getVarianceClass(variance.clicks)">
                        <span x-text="getVarianceIcon(variance.clicks)"></span>
                        <span x-text="formatVariance(variance.clicks)"></span>
                    </div>
                </div>

                <div class="card-stat-mailchimp" :class="activeMetrics.open_rate ? 'active' : ''" @click="toggleMetric('open_rate')"
                     data-metric="open_rate">
                    <div class="mailchimp-label">{{ __('Average Open Rate') }}</div>
                    <div class="card-metric-value" x-text="formatPercent(summary.open_rate)"></div>
                    <div class="card-metric-trend" :class="getVarianceClass(variance.open_rate)">
                        <span x-text="getVarianceIcon(variance.open_rate)"></span>
                        <span x-text="formatVariance(variance.open_rate)"></span>
                    </div>
                </div>

                <div class="card-stat-mailchimp" :class="activeMetrics.click_rate ? 'active' : ''" @click="toggleMetric('click_rate')"
                     data-metric="click_rate">
                    <div class="mailchimp-label">{{ __('Average Click Rate') }}</div>
                    <div class="card-metric-value" x-text="formatPercent(summary.click_rate)"></div>
                    <div class="card-metric-trend" :class="getVarianceClass(variance.click_rate)">
                        <span x-text="getVarianceIcon(variance.click_rate)"></span>
                        <span x-text="formatVariance(variance.click_rate)"></span>
                    </div>
                </div>

                <div class="card-stat-mailchimp" :class="activeMetrics.unsubscribes ? 'active' : ''" @click="toggleMetric('unsubscribes')"
                     data-metric="unsubscribes">
                    <div class="dash-modal-close text-primary-500 dark:text-primary-400" title="{{ __('Trend Analysis Supported') }}">
                        <x-heroicon-s-presentation-chart-line class="w-4 h-4 opacity-50" />
                    </div>
                    <div class="mailchimp-label">{{ __('Unsubscribes') }}</div>
                    <div class="card-metric-value" x-text="formatNumber(summary.unsubscribes)"></div>
                    <div class="card-metric-trend mt-2" :class="getVarianceClass(variance.unsubscribes, true)">
                        <span x-text="getVarianceIcon(variance.unsubscribes, true)"></span>
                        <span x-text="formatVariance(variance.unsubscribes)"></span>
                    </div>
                </div>

                <div class="card-stat-mailchimp" :class="activeMetrics.bounces ? 'active' : ''" @click="toggleMetric('bounces')"
                     data-metric="bounces">
                    <div class="dash-modal-close text-primary-500 dark:text-primary-400" title="{{ __('Trend Analysis Supported') }}">
                        <x-heroicon-s-presentation-chart-line class="w-4 h-4 opacity-50" />
                    </div>
                    <div class="mailchimp-label">{{ __('Bounces (Undelivered)') }}</div>
                    <div class="card-metric-value" x-text="formatNumber(summary.bounces)"></div>
                    <div class="flex items-center gap-1.5 mt-2">
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[9.5px] font-semibold bg-rose-500/20 text-rose-300 whitespace-nowrap"
                              title="{{ __('Permanent delivery failures (e.g. invalid or non-existent email addresses)') }}">
                            <span class="w-1.5 h-1.5 rounded-full bg-rose-400"></span>
                            <span x-text="formatNumber(summary.bounces_hard) + ' {{ __('Hard') }}'"></span>
                        </span>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[9.5px] font-semibold bg-amber-500/20 text-amber-300 whitespace-nowrap"
                              title="{{ __('Temporary delivery issues (e.g. mailbox full or temporary server error)') }}">
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>
                            <span x-text="formatNumber(summary.bounces_soft) + ' {{ __('Soft') }}'"></span>
                        </span>
                    </div>
                    <div class="card-metric-trend mt-2" :class="getVarianceClass(variance.bounces, true)">
                        <span x-text="getVarianceIcon(variance.bounces, true)"></span>
                        <span x-text="formatVariance(variance.bounces)"></span>
                    </div>
                </div>
            </div>

            <div class="chart-container-mailchimp relative w-full" wire:ignore>
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
                            <span class="opacity-70 uppercase bd-text-2xs mr-1" x-text="tab + ':'"></span>
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

        <x-data-table variant="mailchimp" state="tableState" loading="isTableLoading" search>
            <x-slot:header>
                <div class="tab-nav-mailchimp">
                    <div class="tab-mailchimp" :class="activeTab === 'campaigns' ? 'active' : ''"
                         @click="setTab('campaigns')">{{ __('CAMPAIGNS') }}</div>
                    <div class="tab-mailchimp" :class="activeTab === 'automations' ? 'active' : ''"
                         @click="setTab('automations')">{{ __('AUTOMATIONS') }}</div>
                    <div class="tab-mailchimp" :class="activeTab === 'audiences' ? 'active' : ''"
                         @click="setTab('audiences')">{{ __('AUDIENCES') }}</div>
                    <div class="tab-mailchimp" :class="activeTab === 'urls' ? 'active' : ''"
                         @click="setTab('urls')">{{ __('URLS') }}</div>
                </div>
            </x-slot:header>

            <table class="mailchimp-table">
                <thead>
                <tr>
                    <x-data-table.column sortable="false">
                        <span x-text="activeTab === 'urls' ? '{{ __('URL / PAGE') }}' : (activeTab === 'audiences' ? '{{ __('AUDIENCE') }}' : (activeTab === 'automations' ? '{{ __('AUTOMATION') }}' : '{{ __('CAMPAIGN') }}'))"></span>
                    </x-data-table.column>

                    <!-- Campaign / Audience / Automation columns -->
                    <template x-if="activeTab !== 'urls'">
                        <x-data-table.column state="tableState" key="sends" label="{{ __('Emails Sent') }}"/>
                    </template>
                    <template x-if="activeTab !== 'urls'">
                        <x-data-table.column state="tableState" key="opens" label="{{ __('Opens') }}"/>
                    </template>
                    <template x-if="activeTab !== 'urls'">
                        <x-data-table.column state="tableState" key="open_rate" label="{{ __('Open Rate') }}"/>
                    </template>

                    <!-- Clicks column (always present) -->
                    <x-data-table.column state="tableState" key="clicks" label="{{ __('Total Clicks') }}"/>

                    <!-- Specific to URLs tab: Unique Clicks column -->
                    <template x-if="activeTab === 'urls'">
                        <x-data-table.column state="tableState" key="clicks_unique" label="{{ __('Unique Clicks') }}"/>
                    </template>

                    <!-- Campaign / Audience / Automation remaining columns -->
                    <template x-if="activeTab !== 'urls'">
                        <x-data-table.column state="tableState" key="click_rate" label="{{ __('Click Rate') }}"/>
                    </template>
                    <template x-if="activeTab !== 'urls'">
                        <x-data-table.column state="tableState" key="bounces" label="{{ __('Bounces') }}"/>
                    </template>
                    <template x-if="activeTab !== 'urls'">
                        <x-data-table.column state="tableState" key="unsubscribes" label="{{ __('Unsubscribes') }}"/>
                    </template>
                </tr>
                </thead>
                <tbody>
                <template x-for="(row, index) in tableState.paginatedRows" :key="row.id + '_' + index">
                    <x-data-table.row onClick="toggleFilter(activeTab, row.id)"
                                      clickable="true"
                                      active="isFilterActive(activeTab, row.id)"
                                      inactive-class="" disabled-class="">
                        <td>
                            <div class="flex items-center gap-2">
                                <div x-show="isFilterActive(activeTab, row.id)" class="text-primary-500">
                                    <x-heroicon-s-check-circle class="w-4 h-4"/>
                                </div>
                                <div class="mailchimp-name-text" :title="row.name || row.id" x-text="row.name || row.id"></div>
                            </div>
                        </td>

                        <!-- Sends (only non-urls) -->
                        <template x-if="activeTab !== 'urls'">
                            <td class="metric-cell">
                                <div class="metric-val-main" x-text="formatNumber(row.sends)"></div>
                                <div class="progress-bar-container">
                                    <div class="progress-bar-fill mailchimp-bar-sends"
                                         :style="`width: ${maxSends > 0 ? (row.sends / maxSends) * 100 : 0}%`"></div>
                                </div>
                            </td>
                        </template>

                        <!-- Opens (only non-urls) -->
                        <template x-if="activeTab !== 'urls'">
                            <td class="metric-cell">
                                <div class="metric-val-main" x-text="formatNumber(row.opens)"></div>
                                <div class="flex items-center gap-1 text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">
                                    <span title="{{ __('Human opens') }}" x-text="formatNumber(row.opens_standard) + 'h'"></span>
                                    <span class="opacity-40">·</span>
                                    <span title="{{ __('Apple MPP proxy opens') }}" x-text="formatNumber(row.opens_proxy) + 'p'"></span>
                                </div>
                                <div class="progress-bar-container">
                                    <div class="progress-bar-fill mailchimp-bar-opens"
                                         :style="`width: ${maxOpens > 0 ? (row.opens / maxOpens) * 100 : 0}%`"></div>
                                </div>
                            </td>
                        </template>

                        <!-- Open Rate (only non-urls) -->
                        <template x-if="activeTab !== 'urls'">
                            <td class="metric-cell">
                                <div class="metric-val-main" x-text="formatPercent(row.open_rate)"></div>
                                <div class="progress-bar-container">
                                    <div class="progress-bar-fill mailchimp-bar-open-rate"
                                         :style="`width: ${row.open_rate * 100}%`"></div>
                                </div>
                            </td>
                        </template>

                        <!-- Clicks -->
                        <td class="metric-cell">
                            <div class="metric-val-main" x-text="formatNumber(row.clicks)"></div>
                            <div x-show="activeTab !== 'urls'" class="flex items-center gap-1 text-[11px] text-gray-500 dark:text-gray-400 mt-0.5" title="{{ __('Unique clickers') }}">
                                <span x-text="formatNumber(row.clicks_unique) + ' {{ __('uniq') }}'"></span>
                            </div>
                            <div class="progress-bar-container">
                                <div class="progress-bar-fill mailchimp-bar-clicks"
                                     :style="`width: ${maxClicks > 0 ? (row.clicks / maxClicks) * 100 : 0}%`"></div>
                            </div>
                        </td>

                        <!-- Unique Clicks (only urls tab) -->
                        <template x-if="activeTab === 'urls'">
                            <td class="metric-cell">
                                <div class="metric-val-main" x-text="formatNumber(row.clicks_unique)"></div>
                                <div class="progress-bar-container">
                                    <div class="progress-bar-fill mailchimp-bar-clicks opacity-75"
                                         :style="`width: ${maxClicksUnique > 0 ? (row.clicks_unique / maxClicksUnique) * 100 : 0}%`"></div>
                                </div>
                            </td>
                        </template>

                        <!-- Click Rate (only non-urls) -->
                        <template x-if="activeTab !== 'urls'">
                            <td class="metric-cell">
                                <div class="metric-val-main" x-text="formatPercent(row.click_rate)"></div>
                                <div class="progress-bar-container">
                                    <div class="progress-bar-fill mailchimp-bar-click-rate"
                                         :style="`width: ${row.click_rate * 100}%`"></div>
                                </div>
                            </td>
                        </template>

                        <!-- Bounces (only non-urls) -->
                        <template x-if="activeTab !== 'urls'">
                            <td class="metric-cell">
                                <div class="metric-val-main" x-text="formatNumber(row.bounces)"></div>
                                <div class="flex items-center gap-1 text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">
                                    <span title="{{ __('Hard bounces') }}" x-text="formatNumber(row.bounces_hard) + 'h'"></span>
                                    <span class="opacity-40">·</span>
                                    <span title="{{ __('Soft bounces') }}" x-text="formatNumber(row.bounces_soft) + 's'"></span>
                                </div>
                                <div class="progress-bar-container">
                                    <div class="progress-bar-fill bg-rose-500"
                                         :style="`width: ${maxBounces > 0 ? (row.bounces / maxBounces) * 100 : 0}%`"></div>
                                </div>
                            </td>
                        </template>

                        <!-- Unsubscribes (only non-urls) -->
                        <template x-if="activeTab !== 'urls'">
                            <td class="metric-cell">
                                <div class="metric-val-main" x-text="formatNumber(row.unsubscribes)"></div>
                                <div class="progress-bar-container">
                                    <div class="progress-bar-fill mailchimp-bar-unsubscribes"
                                         :style="`width: ${maxUnsubscribes > 0 ? (row.unsubscribes / maxUnsubscribes) * 100 : 0}%`"></div>
                                </div>
                            </td>
                        </template>
                    </x-data-table.row>
                </template>
                </tbody>
            </table>
        </x-data-table>
    </div>
</x-filament-panels::page>
