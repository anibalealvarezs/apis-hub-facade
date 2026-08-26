<x-filament-panels::page>
    <link rel="stylesheet" href="{{ asset('css/dashboards.css') }}">

    <div class="fb-organic-page" x-data="fboDashboard({
        tenantId: @js(Filament\Facades\Filament::getTenant()->id ?? Filament\Facades\Filament::getTenant()->slug),
        accounts: @js($selectedAccounts),
        accountNames: @js($accounts),
        dateStart: @js($dateStart),
        dateEnd: @js($dateEnd),
        activeTab: 'instagram',
        activeBreakdownTab: 'reaction_type',
        csrfToken: @js(csrf_token())
    })" x-init="initDashboard()">
        <div class="sticky-header-section py-3 px-3 mb-6 bg-gray-50/98 dark:bg-gray-900/98 backdrop-blur-md border-b border-gray-200 dark:border-white/10 transition-colors">
            <div class="fb-header-row mb-3">
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
                    <x-ui.asset-selector model="accounts[0]" options="accountNames" placeholder="{{ __('Select Page...') }}" change-event="handleAccountChange()" size="sm" />
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

<div class="tab-nav-fb tab-nav-fb-split mt-2 fbo-tabs">
                <div class="tab-fb" :class="activeTab === 'facebook' ? 'active' : ''"
                     @click="setTab('facebook')">{{ __('FACEBOOK PAGE') }}</div>
                <div class="tab-fb" :class="activeTab === 'instagram' ? 'active' : ''"
                     @click="setTab('instagram')">{{ __('INSTAGRAM ACCOUNT') }}</div>
            </div>
        </div>

        <div class="metrics-grid-fb relative">
            <div x-show="isSummaryLoading"
                 class="absolute inset-0 z-10 flex items-center justify-center bg-white/50 dark:bg-gray-900/50 backdrop-blur-sm rounded-xl">
                <x-filament::loading-indicator class="h-8 w-8 text-primary-500"/>
            </div>

            <template x-for="metric in dynamicMetrics" :key="metric.key">
                <div class="card-stat-fb" :class="activeMetrics[metric.key] ? 'active' : ''"
                     @click="toggleMetric(metric.key)" :style="`--color: ${metric.color};`">
                    <div class="dash-modal-close text-primary-500 dark:text-primary-400" title="{{ __('Trend Analysis Supported') }}"
                         x-show="(activeTab === 'facebook' && ['reach', 'interactions'].includes(metric.key)) || (activeTab === 'instagram' && ['reach', 'saves', 'shares'].includes(metric.key))">
                        <x-heroicon-s-presentation-chart-line class="w-4 h-4 opacity-50" />
                    </div>
                    <div class="fb-label" x-text="metric.label"></div>
                    <div class="card-metric-value" x-text="formatNumber(metric.value)"></div>
                    <div class="card-metric-trend" :class="getVarianceClass(metric.variance)">
                        <span x-text="getVarianceIcon(metric.variance)"></span>
                        <span x-text="formatVariance(metric.variance)"></span>
                    </div>
                </div>
            </template>
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
             class="mb-4 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-3 shadow-sm"
             x-transition>
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 flex items-center gap-2">
                    <x-heroicon-o-funnel class="w-4 h-4 text-primary-500"/>
                    {{ __('Active Breakdown Filters') }}
                </h3>
                <button @click="clearFilters()"
                        class="text-xs text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 font-medium">{{ __('Clear All') }}</button>
            </div>
            <div class="flex flex-wrap gap-2">
                <template x-for="tab in availableBreakdownTabs" :key="tab.value + '_chips'">
                    <template x-for="val in (activeFilters[tab.value] || [])" :key="tab.value + '_' + val">
                        <span
                            class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium bg-primary-100 text-primary-800 dark:bg-primary-900/40 dark:text-primary-300 border border-primary-200 dark:border-primary-800">
                            <span class="opacity-70 uppercase bd-text-2xs mr-1" x-text="tab.label + ':'"></span>
                            <span x-text="val"></span>
                            <button @click.stop="toggleFilter(tab.value, val)"
                                    class="ml-1 text-primary-600 hover:text-primary-900 dark:text-primary-400 dark:hover:text-primary-200">
                                <x-heroicon-m-x-mark class="w-3 h-3"/>
                            </button>
                        </span>
                    </template>
                </template>
            </div>
        </div>

        <x-data-table variant="fb" state="breakdownTable" loading="isBreakdownTableLoading"
                      search searchPlaceholder="{{ __('Filter breakdown values...') }}"
                      class="dash-mb-20">
            <x-slot:header>
                <div class="tab-nav-fb">
                    <template x-for="tab in availableBreakdownTabs" :key="tab.value">
                        <div class="tab-fb" :class="activeBreakdownTab === tab.value ? 'active' : ''"
                             @click="setBreakdownTab(tab.value)" x-text="tab.label"></div>
                    </template>
                </div>

                <div
                    class="p-4 border-b border-gray-200 dark:border-white/5 bg-white dark:bg-transparent flex justify-between items-center">
                    <h3 class="font-bold text-gray-800 dark:text-gray-100 uppercase"
                        x-text="((availableBreakdownTabs.find(tab => tab.value === activeBreakdownTab) || {}).label || '').toUpperCase()"></h3>
                </div>
            </x-slot:header>

            <table class="fb-table dash-table-wide">
                <thead>
                <tr>
                    <th>{{ __('BREAKDOWN VALUE') }}</th>
                    <template x-for="metricKey in availableBreakdownMetrics" :key="metricKey">
                        <x-data-table.column state="breakdownTable" key-bind="metricKey">
                            <span x-text="getMetricInfo(metricKey).label"></span>
                        </x-data-table.column>
                    </template>
                </tr>
                </thead>
                <tbody>
                <template x-for="(row, index) in breakdownTable.paginatedRows" :key="row.id + '_' + index">
                    <x-data-table.row>
                        <td class="font-medium">
                            <div class="flex items-center gap-2">
                                <span x-text="row.name"></span>
                            </div>
                        </td>
                        <template x-for="metricKey in availableBreakdownMetrics" :key="metricKey">
                            <td class="metric-cell" x-text="formatNumber(row[metricKey] || 0)"></td>
                        </template>
                    </x-data-table.row>
                </template>
                <tr x-show="breakdownTable.paginatedRows.length === 0">
                    <td :colspan="availableBreakdownMetrics.length + 1"
                        class="text-center py-8 text-gray-500 dark:text-gray-400">{{ __('No breakdown data available.') }}</td>
                </tr>
                </tbody>
            </table>
        </x-data-table>

        <x-data-table variant="fb" state="postsTable" loading="isTableLoading"
                      search searchPlaceholder="{{ __('Filter rows...') }}">
            <x-slot:header>
                <div
                    class="p-4 border-b border-gray-200 dark:border-white/5 bg-white dark:bg-transparent flex justify-between items-center">
                    <h3 class="font-bold text-gray-800 dark:text-gray-100 uppercase"
                        x-text="activeTab === 'facebook' ? '{{ __('Facebook Posts') }}' : '{{ __('Instagram Posts') }}'"></h3>
                </div>
            </x-slot:header>

            <table class="fb-table dash-table-wide">
                <thead>
                <tr>
                    <th>{{ __('POST / PAGE') }}</th>
                    <template x-for="metricKey in availableTableMetrics" :key="metricKey">
                        <x-data-table.column state="postsTable" key-bind="metricKey">
                            <span x-text="getMetricInfo(metricKey).label"></span>
                        </x-data-table.column>
                    </template>
                </tr>
                </thead>
                <tbody>
                <template x-for="(row, index) in postsTable.paginatedRows" :key="row.id + '_' + index">
                    <x-data-table.row onClick="openPostModal(row.id)" clickable="true">
                        <td class="font-medium">
                            <div class="flex items-center gap-2">
                                <a x-show="row.permalink_url || row.permalink"
                                   :href="row.permalink_url || row.permalink" target="_blank"
                                   class="text-primary-500 hover:text-primary-700">
                                    <x-heroicon-o-link class="w-4 h-4"/>
                                </a>
                                <span x-text="row.name"></span>
                            </div>
                            <div x-show="row.media_type" class="text-xs text-gray-500 mt-1 uppercase"
                                 x-text="row.media_type"></div>
                        </td>
                        <template x-for="metricKey in availableTableMetrics" :key="metricKey">
                            <td class="metric-cell"
                                x-text="(metricKey === 'ig_reels_avg_watch_time') || (metricKey === 'ig_reels_video_view_total_time')
                                ? formatMetaDuration(row[metricKey] || 0)
                                : formatNumber(row[metricKey] || 0)"></td>
                        </template>
                    </x-data-table.row>
                </template>
                <tr x-show="postsTable.paginatedRows.length === 0">
                    <td :colspan="availableTableMetrics.length + 1"
                        class="text-center py-8 text-gray-500 dark:text-gray-400">{{ __('No data available.') }}</td>
                </tr>
                </tbody>
            </table>
        </x-data-table>

        <!-- Post Details & History Modal -->
        <div x-show="isPostModalOpen"
             x-trap.noscroll="isPostModalOpen"
             class="fb-modal-root fixed inset-0 z-[999] overflow-y-auto"
             aria-labelledby="modal-title"
             role="dialog"
             aria-modal="true"
             x-cloak>
            <!-- Background overlay -->
            <div class="fb-modal-overlay fixed inset-0 bg-gray-900/60 backdrop-blur-xl transition-opacity"
                 x-show="isPostModalOpen"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="closePostModal()"></div>

            <!-- Modal panel -->
            <div class="flex min-h-full justify-center p-4 sm:p-8 text-center">
                <div x-show="isPostModalOpen"
                     x-transition:enter="ease-out duration-300"
                     x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave="ease-in duration-200"
                     x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     class="fb-modal-panel relative m-auto transform overflow-hidden rounded-xl bg-white dark:bg-gray-900 text-left shadow-xl transition-all border border-gray-200 dark:border-white/10">

                    <!-- Close Button -->
                    <button @click="closePostModal()" type="button" class="fb-close-btn">
                        <x-heroicon-o-x-mark class="w-5 h-5"/>
                    </button>

                    <!-- Left Side: Post Details & Media -->
                    <div class="fb-modal-left p-6 relative flex flex-col h-full overflow-y-auto">
                        <div x-show="isPostDetailsLoading" class="flex justify-center items-center py-12">
                            <x-filament::loading-indicator class="w-8 h-8 text-primary-500"/>
                        </div>

                        <div x-show="!isPostDetailsLoading && selectedPostData" class="flex flex-col flex-1 min-h-0 h-full">
                            <!-- Media Preview -->
                            <div class="fb-modal-image-container bg-gray-200 dark:bg-gray-950 relative shadow-inner">
                                <template x-if="(selectedPostData && selectedPostData.data) && (selectedPostData.data.media_url || selectedPostData.data.full_picture)">
                                    <div class="w-full h-full relative">
                                        <template x-if="selectedPostData.data.media_type === 'VIDEO' || (selectedPostData.data.media_url && selectedPostData.data.media_url.includes('.mp4')) || (selectedPostData.data.full_picture && selectedPostData.data.full_picture.includes('.mp4'))">
                                            <video :src="selectedPostData.data.media_url || selectedPostData.data.full_picture"
                                                   controls preload="metadata"
                                                   class="w-full h-full object-contain bg-black" muted loop playsinline></video>
                                        </template>
                                        <template x-if="!(selectedPostData.data.media_type === 'VIDEO' || (selectedPostData.data.media_url && selectedPostData.data.media_url.includes('.mp4')) || (selectedPostData.data.full_picture && selectedPostData.data.full_picture.includes('.mp4')))">
                                            <img :src="selectedPostData.data.media_url || selectedPostData.data.full_picture"
                                                 class="w-full h-full object-contain" alt="Post preview"/>
                                        </template>
                                    </div>
                                </template>
                                <template x-if="!((selectedPostData && selectedPostData.data) && (selectedPostData.data.media_url || selectedPostData.data.full_picture))">
                                    <div class="text-gray-400 dark:text-gray-500 flex flex-col items-center">
                                        <x-heroicon-o-photo class="w-12 h-12 mb-2 opacity-50"/>
                                        <span class="text-xs uppercase font-medium">{{ __('No Media') }}</span>
                                    </div>
                                </template>
                                <div x-show="selectedPostData && selectedPostData.data && selectedPostData.data.media_type"
                                     class="absolute top-2 left-2 bg-black/60 text-white bd-text-2xs uppercase font-bold px-2 py-1 rounded backdrop-blur-sm"
                                     x-text="selectedPostData && selectedPostData.data ? selectedPostData.data.media_type : ''"></div>
                            </div>

                            <!-- Caption & Details -->
                            <div class="flex-1 pr-2 mb-4">
                                <div class="text-xs text-gray-500 dark:text-gray-400 mb-2 font-medium"
                                     x-text="(selectedPostData && selectedPostData.data && (selectedPostData.data.created_time || selectedPostData.data.timestamp)) ? new Date(selectedPostData.data.created_time || selectedPostData.data.timestamp).toLocaleString() : ''"></div>
                                <div class="text-sm text-gray-800 dark:text-gray-100 whitespace-pre-line break-words"
                                     x-text="(selectedPostData && selectedPostData.data) ? (selectedPostData.data.message || selectedPostData.data.caption || '{{ __('No caption') }}') : ''"></div>
                            </div>

                            <!-- Actions -->
                            <div class="shrink-0 mt-auto pt-4 border-t border-gray-200 dark:border-gray-700">
                                <a :href="(selectedPostData && selectedPostData.data) ? (selectedPostData.data.permalink_url || selectedPostData.data.permalink) : '#'"
                                   target="_blank"
                                   x-show="selectedPostData && selectedPostData.data && (selectedPostData.data.permalink_url || selectedPostData.data.permalink)"
                                   class="inline-flex items-center justify-center w-full px-4 py-2 bg-primary-50 dark:bg-primary-900/20 text-primary-600 dark:text-primary-400 text-sm font-medium rounded-lg hover:bg-primary-100 dark:hover:bg-primary-900/40 transition-colors border border-primary-200 dark:border-primary-800/30">
                                    <x-heroicon-o-link class="w-4 h-4 mr-2"/>
                                    {{ __('View Original Post') }}
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Right Side: Metrics Chart -->
                    <div class="fb-modal-right p-6 relative flex flex-col h-full overflow-hidden">
                        <div class="mb-4 shrink-0">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ __('Post History') }}</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Historical timeline of metrics since publication') }}</p>
                        </div>

                        <div class="relative flex-1 min-h-0 min-w-0 dash-fill">
                            <div x-show="isPostChartLoading"
                                 class="absolute inset-0 z-10 flex items-center justify-center bg-white/50 dark:bg-gray-900/50 backdrop-blur-sm rounded-lg">
                                <x-filament::loading-indicator class="h-8 w-8 text-primary-500"/>
                            </div>
                            <canvas x-ref="postCanvas"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
