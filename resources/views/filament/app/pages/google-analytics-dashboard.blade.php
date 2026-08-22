<x-filament-panels::page>
    <link rel="stylesheet" href="{{ asset('css/dashboards.css') }}">

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
        <div class="ga4-header-row py-3 px-3 mb-6 bg-gray-50/98 dark:bg-gray-900/98 backdrop-blur-md border-b border-gray-200 dark:border-white/10 transition-colors">
            <div class="ga4-header-controls">
                <button type="button" @click="window.print()" class="export-btn">
                    <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5Zm-3 0h.008v.008H15V10.5Z"/>
                    </svg>
                    <span>{{ __('Export PDF') }}</span>
                </button>
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
                        :class="{ 'opacity-50 cursor-not-allowed': isSummaryLoading || isChartLoading || isAnySectionLoading }"
                        :disabled="isSummaryLoading || isChartLoading || isAnySectionLoading">
                    <x-heroicon-o-arrow-path class="w-5 h-5 mr-2"
                                             x-bind:class="{ 'animate-spin': isSummaryLoading || isChartLoading || isAnySectionLoading }"/>
                    <span>{{ __('Update') }}</span>
                </button>
            </div>
        </div>

        <div class="metrics-grid-ga4 relative">
            <div x-show="isSummaryLoading"
                 class="absolute inset-0 z-10 flex items-center justify-center bg-white/50 dark:bg-gray-900/50 backdrop-blur-sm rounded-xl">
                <x-filament::loading-indicator class="h-8 w-8 text-primary-500"/>
            </div>

            <div class="card-stat-ga4" :class="activeMetrics.sessions ? 'active' : ''" @click="toggleMetric('sessions')"
                 data-metric="sessions">
                <div class="ga4-label">{{ __('Sessions') }}</div>
                <div class="card-metric-value" x-text="formatNumber(summary.sessions)"></div>
                <div class="card-metric-trend" :class="getVarianceClass(variance.sessions)">
                    <span x-text="getVarianceIcon(variance.sessions)"></span>
                    <span x-text="formatVariance(variance.sessions)"></span>
                </div>
            </div>
            <div class="card-stat-ga4" :class="activeMetrics.activeUsers ? 'active' : ''" @click="toggleMetric('activeUsers')"
                 data-metric="activeUsers">
                <div class="ga4-label">{{ __('Active Users') }}</div>
                <div class="card-metric-value" x-text="formatNumber(summary.activeUsers)"></div>
                <div class="card-metric-trend" :class="getVarianceClass(variance.activeUsers)">
                    <span x-text="getVarianceIcon(variance.activeUsers)"></span>
                    <span x-text="formatVariance(variance.activeUsers)"></span>
                </div>
            </div>
            <div class="card-stat-ga4" :class="activeMetrics.newUsers ? 'active' : ''" @click="toggleMetric('newUsers')"
                 data-metric="newUsers">
                <div class="ga4-label">{{ __('New Users') }}</div>
                <div class="card-metric-value" x-text="formatNumber(summary.newUsers)"></div>
                <div class="card-metric-trend" :class="getVarianceClass(variance.newUsers)">
                    <span x-text="getVarianceIcon(variance.newUsers)"></span>
                    <span x-text="formatVariance(variance.newUsers)"></span>
                </div>
            </div>
            <div class="card-stat-ga4" :class="activeMetrics.screenPageViews ? 'active' : ''" @click="toggleMetric('screenPageViews')"
                 data-metric="screenPageViews">
                <div class="ga4-label">{{ __('Pageviews') }}</div>
                <div class="card-metric-value" x-text="formatNumber(summary.screenPageViews)"></div>
                <div class="card-metric-trend" :class="getVarianceClass(variance.screenPageViews)">
                    <span x-text="getVarianceIcon(variance.screenPageViews)"></span>
                    <span x-text="formatVariance(variance.screenPageViews)"></span>
                </div>
            </div>
            <div class="card-stat-ga4" :class="activeMetrics.conversions ? 'active' : ''" @click="toggleMetric('conversions')"
                 data-metric="conversions">
                <div class="ga4-label">{{ __('Conversions') }}</div>
                <div class="card-metric-value" x-text="formatNumber(summary.conversions)"></div>
                <div class="card-metric-trend" :class="getVarianceClass(variance.conversions)">
                    <span x-text="getVarianceIcon(variance.conversions)"></span>
                    <span x-text="formatVariance(variance.conversions)"></span>
                </div>
            </div>
            <div class="card-stat-ga4" :class="activeMetrics.averageSessionDuration ? 'active' : ''" @click="toggleMetric('averageSessionDuration')"
                 data-metric="averageSessionDuration">
                <div class="ga4-label">{{ __('Avg Duration') }}</div>
                <div class="card-metric-value" x-text="formatDuration(summary.averageSessionDuration)"></div>
                <div class="card-metric-trend" :class="getVarianceClass(variance.averageSessionDuration)">
                    <span x-text="getVarianceIcon(variance.averageSessionDuration)"></span>
                    <span x-text="formatVariance(variance.averageSessionDuration)"></span>
                </div>
            </div>
            <div class="card-stat-ga4" :class="activeMetrics.bounceRate ? 'active' : ''" @click="toggleMetric('bounceRate')"
                 data-metric="bounceRate">
                <div class="ga4-label">{{ __('Bounce Rate') }}</div>
                <div class="card-metric-value" x-text="formatPercent(summary.bounceRate)"></div>
                <div class="card-metric-trend" :class="getVarianceClass(variance.bounceRate, true)">
                    <span x-text="getVarianceIcon(variance.bounceRate, true)"></span>
                    <span x-text="formatVariance(variance.bounceRate)"></span>
                </div>
            </div>
            <div class="card-stat-ga4" :class="activeMetrics.totalUsers ? 'active' : ''" @click="toggleMetric('totalUsers')"
                 data-metric="totalUsers">
                <div class="ga4-label">{{ __('Total Users') }}</div>
                <div class="card-metric-value" x-text="formatNumber(summary.totalUsers)"></div>
                <div class="card-metric-trend" :class="getVarianceClass(variance.totalUsers)">
                    <span x-text="getVarianceIcon(variance.totalUsers)"></span>
                    <span x-text="formatVariance(variance.totalUsers)"></span>
                </div>
            </div>
            <div class="card-stat-ga4" :class="activeMetrics.revenue ? 'active' : ''" @click="toggleMetric('revenue')"
                 data-metric="revenue">
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
            <div class="dash-chart-canvas">
                <canvas x-ref="canvas"></canvas>
            </div>
        </div>

        {{-- Campaigns Section --}}
        <x-data-table variant="ga4" state="sections.campaigns" loading="sl.campaigns" search>
            <x-slot:header>
                <div class="ga4-section-header">
                    <h3 x-text="sectionConfig.campaigns.label"></h3>
                </div>
                <div class="tab-nav-ga4">
                    <template x-for="t in sectionConfig.campaigns.tabs" :key="t">
                        <div class="tab-ga4" :class="ss.campaigns === t ? 'active' : ''"
                             @click="setSectionSubTab('campaigns', t)" x-text="(tabConfig[t] && tabConfig[t].label) || t"></div>
                    </template>
                </div>
            </x-slot:header>

            <table class="ga4-table">
                <thead>
                <tr>
                    <th><span x-text="(tabConfig[ss.campaigns] && tabConfig[ss.campaigns].label) || 'Name'"></span></th>
                    <template x-for="m in ((tabConfig[ss.campaigns] && tabConfig[ss.campaigns].metrics) || [])" :key="m">
                        <x-data-table.column state="sections.campaigns" key-bind="m">
                            <span x-text="metricLabels[m] || m"></span>
                        </x-data-table.column>
                    </template>
                </tr>
                </thead>
                <tbody>
                <template x-for="(row, idx) in sections.campaigns.paginatedRows" :key="(row.id || idx) + '_' + idx">
                    <x-data-table.row>
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
                    </x-data-table.row>
                </template>
                <tr x-show="sections.campaigns.paginatedRows.length === 0">
                    <td :colspan="((tabConfig[ss.campaigns] && tabConfig[ss.campaigns].metrics && tabConfig[ss.campaigns].metrics.length) || 6) + 1" class="text-center py-8 text-gray-500 dark:text-gray-400">{{ __('No data available.') }}</td>
                </tr>
                </tbody>
            </table>
        </x-data-table>

        {{-- Channels Section --}}
        <x-data-table variant="ga4" state="sections.channels" loading="sl.channels" search>
            <x-slot:header>
                <div class="ga4-section-header">
                    <h3 x-text="sectionConfig.channels.label"></h3>
                </div>
                <div class="tab-nav-ga4">
                    <template x-for="t in sectionConfig.channels.tabs" :key="t">
                        <div class="tab-ga4" :class="ss.channels === t ? 'active' : ''"
                             @click="setSectionSubTab('channels', t)" x-text="(tabConfig[t] && tabConfig[t].label) || t"></div>
                    </template>
                </div>
            </x-slot:header>

            <table class="ga4-table">
                <thead>
                <tr>
                    <th><span x-text="(tabConfig[ss.channels] && tabConfig[ss.channels].label) || 'Name'"></span></th>
                    <template x-for="m in ((tabConfig[ss.channels] && tabConfig[ss.channels].metrics) || [])" :key="m">
                        <x-data-table.column state="sections.channels" key-bind="m">
                            <span x-text="metricLabels[m] || m"></span>
                        </x-data-table.column>
                    </template>
                </tr>
                </thead>
                <tbody>
                <template x-for="(row, idx) in sections.channels.paginatedRows" :key="(row.id || idx) + '_' + idx">
                    <x-data-table.row>
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
                    </x-data-table.row>
                </template>
                <tr x-show="sections.channels.paginatedRows.length === 0">
                    <td :colspan="((tabConfig[ss.channels] && tabConfig[ss.channels].metrics && tabConfig[ss.channels].metrics.length) || 6) + 1" class="text-center py-8 text-gray-500 dark:text-gray-400">{{ __('No data available.') }}</td>
                </tr>
                </tbody>
            </table>
        </x-data-table>

        {{-- Traffic Section --}}
        <x-data-table variant="ga4" state="sections.traffic" loading="sl.traffic" search>
            <x-slot:header>
                <div class="ga4-section-header">
                    <h3 x-text="sectionConfig.traffic.label"></h3>
                </div>
                <div class="tab-nav-ga4">
                    <template x-for="t in sectionConfig.traffic.tabs" :key="t">
                        <div class="tab-ga4" :class="ss.traffic === t ? 'active' : ''"
                             @click="setSectionSubTab('traffic', t)" x-text="(tabConfig[t] && tabConfig[t].label) || t"></div>
                    </template>
                </div>
            </x-slot:header>

            <table class="ga4-table">
                <thead>
                <tr>
                    <th><span x-text="(tabConfig[ss.traffic] && tabConfig[ss.traffic].label) || 'Name'"></span></th>
                    <template x-for="m in ((tabConfig[ss.traffic] && tabConfig[ss.traffic].metrics) || [])" :key="m">
                        <x-data-table.column state="sections.traffic" key-bind="m">
                            <span x-text="metricLabels[m] || m"></span>
                        </x-data-table.column>
                    </template>
                </tr>
                </thead>
                <tbody>
                <template x-for="(row, idx) in sections.traffic.paginatedRows" :key="(row.id || idx) + '_' + idx">
                    <x-data-table.row>
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
                    </x-data-table.row>
                </template>
                <tr x-show="sections.traffic.paginatedRows.length === 0">
                    <td :colspan="((tabConfig[ss.traffic] && tabConfig[ss.traffic].metrics && tabConfig[ss.traffic].metrics.length) || 6) + 1" class="text-center py-8 text-gray-500 dark:text-gray-400">{{ __('No data available.') }}</td>
                </tr>
                </tbody>
            </table>
        </x-data-table>

        {{-- Acquisition Section --}}
        <x-data-table variant="ga4" state="sections.acquisition" loading="sl.acquisition" search>
            <x-slot:header>
                <div class="ga4-section-header">
                    <h3 x-text="sectionConfig.acquisition.label"></h3>
                </div>
                <div class="tab-nav-ga4">
                    <template x-for="t in sectionConfig.acquisition.tabs" :key="t">
                        <div class="tab-ga4" :class="ss.acquisition === t ? 'active' : ''"
                             @click="setSectionSubTab('acquisition', t)" x-text="(tabConfig[t] && tabConfig[t].label) || t"></div>
                    </template>
                </div>
            </x-slot:header>

            <table class="ga4-table">
                <thead>
                <tr>
                    <th><span x-text="(tabConfig[ss.acquisition] && tabConfig[ss.acquisition].label) || 'Name'"></span></th>
                    <template x-for="m in ((tabConfig[ss.acquisition] && tabConfig[ss.acquisition].metrics) || [])" :key="m">
                        <x-data-table.column state="sections.acquisition" key-bind="m">
                            <span x-text="metricLabels[m] || m"></span>
                        </x-data-table.column>
                    </template>
                </tr>
                </thead>
                <tbody>
                <template x-for="(row, idx) in sections.acquisition.paginatedRows" :key="(row.id || idx) + '_' + idx">
                    <x-data-table.row>
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
                    </x-data-table.row>
                </template>
                <tr x-show="sections.acquisition.paginatedRows.length === 0">
                    <td :colspan="((tabConfig[ss.acquisition] && tabConfig[ss.acquisition].metrics && tabConfig[ss.acquisition].metrics.length) || 6) + 1" class="text-center py-8 text-gray-500 dark:text-gray-400">{{ __('No data available.') }}</td>
                </tr>
                </tbody>
            </table>
        </x-data-table>

        {{-- Events Section --}}
        <x-data-table variant="ga4" state="sections.events" loading="sl.events" search>
            <x-slot:header>
                <div class="ga4-section-header">
                    <h3 x-text="sectionConfig.events.label"></h3>
                </div>
                <div class="tab-nav-ga4">
                    <template x-for="t in sectionConfig.events.tabs" :key="t">
                        <div class="tab-ga4" :class="ss.events === t ? 'active' : ''"
                             @click="setSectionSubTab('events', t)" x-text="(tabConfig[t] && tabConfig[t].label) || t"></div>
                    </template>
                </div>
            </x-slot:header>

            <table class="ga4-table">
                <thead>
                <tr>
                    <th><span x-text="(tabConfig[ss.events] && tabConfig[ss.events].label) || 'Name'"></span></th>
                    <template x-for="m in ((tabConfig[ss.events] && tabConfig[ss.events].metrics) || [])" :key="m">
                        <x-data-table.column state="sections.events" key-bind="m">
                            <span x-text="metricLabels[m] || m"></span>
                        </x-data-table.column>
                    </template>
                </tr>
                </thead>
                <tbody>
                <template x-for="(row, idx) in sections.events.paginatedRows" :key="(row.id || idx) + '_' + idx">
                    <x-data-table.row>
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
                    </x-data-table.row>
                </template>
                <tr x-show="sections.events.paginatedRows.length === 0">
                    <td :colspan="((tabConfig[ss.events] && tabConfig[ss.events].metrics && tabConfig[ss.events].metrics.length) || 6) + 1" class="text-center py-8 text-gray-500 dark:text-gray-400">{{ __('No data available.') }}</td>
                </tr>
                </tbody>
            </table>
        </x-data-table>

        {{-- Ad Touchpoints Section --}}
        <x-data-table variant="ga4" state="sections.adtouchpoints" loading="sl.adtouchpoints" search>
            <x-slot:header>
                <div class="ga4-section-header">
                    <h3 x-text="sectionConfig.adtouchpoints.label"></h3>
                </div>
                <div class="tab-nav-ga4">
                    <template x-for="t in sectionConfig.adtouchpoints.tabs" :key="t">
                        <div class="tab-ga4" :class="ss.adtouchpoints === t ? 'active' : ''"
                             @click="setSectionSubTab('adtouchpoints', t)" x-text="(tabConfig[t] && tabConfig[t].label) || t"></div>
                    </template>
                </div>
            </x-slot:header>

            <table class="ga4-table">
                <thead>
                <tr>
                    <th><span x-text="(tabConfig[ss.adtouchpoints] && tabConfig[ss.adtouchpoints].label) || 'Name'"></span></th>
                    <template x-for="m in ((tabConfig[ss.adtouchpoints] && tabConfig[ss.adtouchpoints].metrics) || [])" :key="m">
                        <x-data-table.column state="sections.adtouchpoints" key-bind="m">
                            <span x-text="metricLabels[m] || m"></span>
                        </x-data-table.column>
                    </template>
                </tr>
                </thead>
                <tbody>
                <template x-for="(row, idx) in sections.adtouchpoints.paginatedRows" :key="(row.id || idx) + '_' + idx">
                    <x-data-table.row>
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
                    </x-data-table.row>
                </template>
                <tr x-show="sections.adtouchpoints.paginatedRows.length === 0">
                    <td :colspan="((tabConfig[ss.adtouchpoints] && tabConfig[ss.adtouchpoints].metrics && tabConfig[ss.adtouchpoints].metrics.length) || 6) + 1" class="text-center py-8 text-gray-500 dark:text-gray-400">{{ __('No data available.') }}</td>
                </tr>
                </tbody>
            </table>
        </x-data-table>
    </div>
</x-filament-panels::page>

