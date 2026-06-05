<x-filament-panels::page>
    <style>
        :root {
            --fb-reach: #10b981;
            --fb-interactions: #6366f1;
            --fb-likes: #0ea5e9;
            --fb-comments: #8b5cf6;
            --fb-views: #f59e0b;
            --fb-follows: #ec4899;
            
            --fb-text-main: #111827;
            --fb-text-dim: #6b7280;
            --fb-bg-card: rgba(0,0,0,0.02);
            --fb-border: rgba(0,0,0,0.06);
            --fb-bg-hover: rgba(0,0,0,0.04);
            --fb-bg-active: rgba(0,0,0,0.06);
            --fb-chart-grid: rgba(0, 0, 0, 0.05);
            --fb-chart-ticks: #6b7280;
        }

        .dark {
            --fb-text-main: #ffffff;
            --fb-text-dim: #94a3b8;
            --fb-bg-card: rgba(255,255,255,0.03);
            --fb-border: rgba(255,255,255,0.05);
            --fb-bg-hover: rgba(255,255,255,0.05);
            --fb-bg-active: rgba(255,255,255,0.08);
            --fb-chart-grid: rgba(255, 255, 255, 0.05);
            --fb-chart-ticks: #94a3b8;
        }

        .fb-header-row { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 30px; }
        .fb-header-title { font-size: 1.8rem; font-weight: 800; color: var(--fb-text-main); margin-bottom: 5px; display: flex; align-items: center; gap: 12px; }
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
        .tab-fb.active { background: var(--fb-bg-card); color: var(--fb-reach); border-bottom: 2px solid var(--fb-reach); }

        .fb-table { width: 100%; border-collapse: collapse; text-align: left; }
        .fb-table th { padding: 12px 20px; font-size: 0.75rem; text-transform: uppercase; color: var(--fb-text-dim); font-weight: 700; border-bottom: 1px solid var(--fb-border); }
        .fb-table td { padding: 12px 20px; border-bottom: 1px solid var(--fb-border); vertical-align: middle; font-size: 0.9rem; color: var(--fb-text-main); }
        .fb-table tr:hover { background: var(--fb-bg-hover); }
        
        .metric-cell { text-align: right; }

        .fb-pagination-container { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; padding: 15px 25px; border-top: 1px solid var(--fb-border); background: var(--fb-bg-active); }
        .fb-pagination-text { font-size: 0.875rem; color: var(--fb-text-dim); }
        .fb-pagination-text strong { color: var(--fb-text-main); font-weight: 700; }
        .fb-pagination-select { background: var(--fb-bg-card); border: 1px solid var(--fb-border); color: var(--fb-text-main); font-size: 0.875rem; border-radius: 8px; padding: 8px 12px; outline: none; }
        .fb-pagination-btn { padding: 8px 16px; background: var(--fb-bg-card); border: 1px solid var(--fb-border); border-radius: 8px; font-size: 0.875rem; font-weight: 500; color: var(--fb-text-main); cursor: pointer; transition: background 0.2s; }
        .fb-pagination-btn:hover:not(:disabled) { background: var(--fb-bg-hover); }
        .fb-pagination-btn:disabled { opacity: 0.4; cursor: not-allowed; }
        .fb-pagination-badge { margin-left: 8px; padding: 4px 8px; background: var(--fb-bg-card); border-radius: 4px; font-size: 0.75rem; }

        /* Custom Modal Layout */
        .fb-modal-panel {
            display: flex;
            flex-direction: row;
            height: 85vh;
            min-height: 500px;
            max-height: 900px;
            width: 100%;
            max-width: 1152px; /* max-w-6xl */
        }
        .fb-modal-left {
            width: 380px;
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            min-height: 0;
            border-right: 1px solid var(--fb-border);
        }
        .fb-modal-right {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-width: 0;
            min-height: 0;
            overflow-y: auto;
        }
        .fb-modal-image-container {
            width: 100%;
            height: 280px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
        }
        @media (max-width: 768px) {
            .fb-modal-panel {
                flex-direction: column;
            }
            .fb-modal-left {
                width: 100%;
                border-right: none;
                border-bottom: 1px solid var(--fb-border);
            }
        }
        .fb-close-btn {
            position: absolute;
            top: 15px;
            right: 15px;
            z-index: 50;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: var(--fb-bg-card);
            border: 1px solid var(--fb-border);
            color: var(--fb-text-dim);
            cursor: pointer;
            transition: all 0.2s ease;
            backdrop-filter: blur(4px);
        }
        .fb-close-btn:hover {
            background: var(--fb-bg-hover);
            color: var(--fb-text-main);
            transform: scale(1.05);
        }
    </style>

    <div x-data="fboDashboard()" x-init="initDashboard()">
        <div class="fb-header-row">
            <div>
                <h1 class="fb-header-title">
                    <x-heroicon-o-users class="w-8 h-8 text-[#1877F2]" />
                    {{ __('Meta Pages & Instagram Accounts') }}
                </h1>
            </div>
            <div class="fb-header-controls">
                <button type="button" @click="forceRefresh()" class="flex items-center justify-center bg-primary-600 hover:bg-primary-500 text-white text-sm font-medium rounded-lg px-4 py-2.5 transition duration-75 shadow-sm" :class="{ 'opacity-50 cursor-not-allowed': isSummaryLoading || isChartLoading || isTableLoading }" :disabled="isSummaryLoading || isChartLoading || isTableLoading">
                    <x-heroicon-o-arrow-path class="w-5 h-5 mr-2" x-bind:class="{ 'animate-spin': isSummaryLoading || isChartLoading || isTableLoading }" />
                    <span>{{ __('Update') }}</span>
                </button>
                <div class="relative" x-data="{ open: false, searchAccount: '' }">
                    <button @click="open = !open" @click.outside="open = false" type="button" class="bg-white dark:bg-white/5 border border-gray-300 dark:border-white/10 text-gray-950 dark:text-white text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 flex items-center justify-between w-full sm:w-64 md:w-72 px-4 py-2.5 h-[42px]">
                        <span class="truncate font-medium text-gray-700 dark:text-gray-200" x-text="accounts.length === 0 ? '{{ __('Select Pages...') }}' : (accounts.length === 1 ? '1 {{ __('page') }}' : accounts.length + ' {{ __('pages') }}')"></span>
                        <x-heroicon-m-chevron-down class="w-4 h-4 ml-2 flex-shrink-0 text-gray-500 dark:text-gray-400" />
                    </button>
                    
                    <div x-show="open" x-transition style="display: none; min-width: 320px;" class="absolute z-50 w-full sm:w-72 mt-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-xl right-0 md:left-0 md:right-auto flex flex-col">
                        
                        <!-- Search and Select All Header -->
                        <div class="p-3 border-b border-gray-200 dark:border-gray-700 space-y-3">
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                    <x-heroicon-o-magnifying-glass class="w-4 h-4 text-gray-500 dark:text-gray-400" />
                                </div>
                                <input type="text" x-model="searchAccount" class="bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full pl-9 p-2" placeholder="{{ __('Search pages...') }}">
                            </div>
                            
                            @if(count($accounts) > 0)
                            <div class="flex items-center justify-between px-1">
                                <button type="button" @click="accounts = {{ json_encode(array_keys($accounts)) }}" class="text-xs font-medium text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300">{{ __('Select All') }}</button>
                                <button type="button" @click="accounts = []" class="text-xs font-medium text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200">{{ __('Clear All') }}</button>
                            </div>
                            @endif
                        </div>

                        <!-- Accounts List -->
                        <div class="p-2 flex flex-col gap-1 overflow-y-auto max-h-96">
                            @if(count($accounts) === 0)
                                <div class="px-3 py-2 text-sm text-gray-500 dark:text-gray-400 italic">{{ __('No pages available.') }}</div>
                            @endif
                            @foreach($accounts as $id => $name)
                                <label x-show="searchAccount === '' || '{{ strtolower(addslashes($name)) }}'.includes(searchAccount.toLowerCase()) || '{{ strtolower($id) }}'.includes(searchAccount.toLowerCase())" class="flex items-center px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md cursor-pointer transition-colors duration-150">
                                    <input type="checkbox" value="{{ $id }}" x-model="accounts" class="w-4 h-4 text-primary-600 bg-gray-100 border-gray-300 rounded focus:ring-primary-500 dark:focus:ring-primary-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600 mr-3">
                                    <div class="flex flex-col overflow-hidden">
                                        <span class="truncate font-medium" title="{{ $name }}">{{ $name }}</span>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>
                <input type="date" x-model.lazy="dateStart" class="bg-white dark:bg-white/5 border border-gray-300 dark:border-white/10 text-gray-950 dark:text-white text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-40 p-2.5">
                <input type="date" x-model.lazy="dateEnd" class="bg-white dark:bg-white/5 border border-gray-300 dark:border-white/10 text-gray-950 dark:text-white text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-40 p-2.5">
            </div>
        </div>

        <div class="tab-nav-fb" style="margin-bottom: 25px; border-radius: 8px; overflow: hidden; border: 1px solid var(--fb-border);">
            <div class="tab-fb" :class="activeTab === 'facebook' ? 'active' : ''" @click="setTab('facebook')">{{ __('FACEBOOK PAGE') }}</div>
            <div class="tab-fb" :class="activeTab === 'instagram' ? 'active' : ''" @click="setTab('instagram')">{{ __('INSTAGRAM ACCOUNT') }}</div>
        </div>

        <div class="metrics-grid-fb relative">
            <div x-show="isSummaryLoading" class="absolute inset-0 z-10 flex items-center justify-center bg-white/50 dark:bg-gray-900/50 backdrop-blur-sm rounded-xl">
                <x-filament::loading-indicator class="h-8 w-8 text-primary-500" />
            </div>

            <template x-for="metric in dynamicMetrics" :key="metric.key">
                <div class="card-stat-fb" :class="activeMetrics[metric.key] ? 'active' : ''" @click="toggleMetric(metric.key)" :style="`--color: ${metric.color};`">
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
            <div x-show="isChartLoading" class="absolute inset-0 z-10 flex items-center justify-center bg-white/50 dark:bg-gray-900/50 backdrop-blur-sm rounded-xl">
                <x-filament::loading-indicator class="h-8 w-8 text-primary-500" />
            </div>
            <div style="position: relative; width: 100%; height: 100%; display: block;">
                <canvas x-ref="canvas"></canvas>
            </div>
        </div>

        <div class="fb-table-container relative">
            <div x-show="isTableLoading" class="absolute inset-0 z-10 flex items-center justify-center bg-white/50 dark:bg-gray-900/50 backdrop-blur-sm rounded-xl">
                <x-filament::loading-indicator class="h-8 w-8 text-primary-500" />
            </div>
            
            <div class="p-4 border-b border-gray-200 dark:border-white/5 bg-white dark:bg-transparent flex justify-between items-center">
                <h3 class="font-bold text-gray-800 dark:text-gray-100 uppercase" x-text="activeTab === 'facebook' ? '{{ __('Facebook Posts') }}' : '{{ __('Instagram Posts') }}'"></h3>
            </div>

            <div class="p-4 border-b border-gray-200 dark:border-white/5 bg-white dark:bg-transparent">
                <div class="relative w-full max-w-md">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                        <x-heroicon-o-magnifying-glass class="w-4 h-4 text-gray-500 dark:text-gray-400" />
                    </div>
                    <input type="text" x-model.debounce.300ms="searchQuery" class="bg-gray-50 dark:bg-white/5 border border-gray-300 dark:border-white/10 text-gray-900 dark:text-white text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full pl-10 p-2" placeholder="{{ __('Filter rows...') }}">
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="fb-table" style="min-width: 800px;">
                    <thead>
                        <tr>
                            <th>{{ __('POST / PAGE') }}</th>
                            <template x-for="metricKey in availableTableMetrics" :key="metricKey">
                                <th class="metric-cell cursor-pointer" @click="sortBy(metricKey)">
                                    <span x-text="getMetricInfo(metricKey).label"></span>
                                    <span x-show="sortCol === metricKey" x-text="sortDir === 'desc' ? '↓' : '↑'"></span>
                                </th>
                            </template>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(row, index) in paginatedTableData" :key="row.id + '_' + index">
                            <tr @click="openPostModal(row)" class="cursor-pointer hover:bg-gray-50 dark:hover:bg-white/5 transition duration-150">
                                <td class="font-medium">
                                    <div class="flex items-center gap-2">
                                        <a x-show="row.permalink_url || row.permalink" :href="row.permalink_url || row.permalink" target="_blank" class="text-primary-500 hover:text-primary-700">
                                            <x-heroicon-o-link class="w-4 h-4" />
                                        </a>
                                        <span x-text="row.name"></span>
                                    </div>
                                    <div x-show="row.media_type" class="text-xs text-gray-500 mt-1 uppercase" x-text="row.media_type"></div>
                                </td>
                                <template x-for="metricKey in availableTableMetrics" :key="metricKey">
                                    <td class="metric-cell" x-text="formatNumber(row[metricKey] || 0)"></td>
                                </template>
                            </tr>
                        </template>
                        <tr x-show="paginatedTableData.length === 0">
                            <td :colspan="availableTableMetrics.length + 1" class="text-center py-8 text-gray-500 dark:text-gray-400">{{ __('No data available.') }}</td>
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
                        {{ __('Page') }} <strong x-text="currentPage"></strong> {{ __('of') }} <strong x-text="totalPages"></strong>
                        <span class="fb-pagination-badge">(<span x-text="tableDataRaw.length"></span> {{ __('results') }})</span>
                    </span>
                    <div class="flex gap-2">
                        <button @click="prevPage()" :disabled="currentPage === 1" class="fb-pagination-btn">{{ __('Prev') }}</button>
                        <button @click="nextPage()" :disabled="currentPage === totalPages" class="fb-pagination-btn">{{ __('Next') }}</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Post Details & History Modal -->
        <div x-show="isPostModalOpen" 
             style="display: none;" 
             class="fixed inset-0 z-[999] overflow-y-auto" 
             aria-labelledby="modal-title" 
             role="dialog" 
             aria-modal="true">
            <!-- Background overlay -->
            <div class="fixed inset-0 bg-gray-900/75 backdrop-blur-sm transition-opacity" 
                 x-show="isPostModalOpen"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="closePostModal()"></div>

            <!-- Modal panel -->
            <div class="flex min-h-full items-center justify-center p-4 sm:p-8 text-center">
                <div x-show="isPostModalOpen"
                     x-transition:enter="ease-out duration-300"
                     x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave="ease-in duration-200"
                     x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     class="fb-modal-panel relative transform overflow-hidden rounded-xl bg-white dark:bg-gray-900 text-left shadow-2xl transition-all border border-gray-200 dark:border-white/10">
                    
                    <!-- Close Button -->
                    <button @click="closePostModal()" type="button" class="fb-close-btn">
                        <span class="sr-only">Close</span>
                        <x-heroicon-o-x-mark style="width: 20px; height: 20px;" />
                    </button>

                    <!-- Left Side: Post Preview -->
                    <div class="fb-modal-left bg-gray-50 dark:bg-gray-800 p-6 relative">
                        
                        <div x-show="isPostDetailsLoading" class="absolute inset-0 z-10 flex items-center justify-center bg-gray-50/80 dark:bg-black/50 backdrop-blur-sm rounded-l-xl">
                            <x-filament::loading-indicator class="h-8 w-8 text-primary-500" />
                        </div>

                        <div x-show="!isPostDetailsLoading && selectedPostData" class="flex flex-col flex-1 min-h-0">
                            <!-- Media Preview -->
                            <div class="fb-modal-image-container bg-gray-200 dark:bg-gray-950 relative shadow-inner">
                                <template x-if="selectedPostData?.data?.media_url || selectedPostData?.data?.full_picture">
                                    <div class="w-full h-full relative">
                                        <template x-if="selectedPostData?.data?.media_type === 'VIDEO' || (selectedPostData?.data?.media_url && selectedPostData.data.media_url.includes('.mp4')) || (selectedPostData?.data?.full_picture && selectedPostData.data.full_picture.includes('.mp4'))">
                                            <video :src="selectedPostData?.data?.media_url || selectedPostData?.data?.full_picture" controls class="w-full h-full object-contain bg-black" muted loop playsinline></video>
                                        </template>
                                        <template x-if="!(selectedPostData?.data?.media_type === 'VIDEO' || (selectedPostData?.data?.media_url && selectedPostData.data.media_url.includes('.mp4')) || (selectedPostData?.data?.full_picture && selectedPostData.data.full_picture.includes('.mp4')))">
                                            <img :src="selectedPostData?.data?.media_url || selectedPostData?.data?.full_picture" class="w-full h-full object-contain" alt="Post preview" />
                                        </template>
                                    </div>
                                </template>
                                <template x-if="!(selectedPostData?.data?.media_url || selectedPostData?.data?.full_picture)">
                                    <div class="text-gray-400 dark:text-gray-500 flex flex-col items-center">
                                        <x-heroicon-o-photo class="w-12 h-12 mb-2 opacity-50" />
                                        <span class="text-xs uppercase font-medium">{{ __('No Media') }}</span>
                                    </div>
                                </template>
                                <div x-show="selectedPostData?.data?.media_type" class="absolute top-2 left-2 bg-black/60 text-white text-[10px] uppercase font-bold px-2 py-1 rounded backdrop-blur-sm" x-text="selectedPostData?.data?.media_type"></div>
                            </div>

                            <!-- Post Details -->
                            <div class="flex-1 overflow-y-auto pr-2 mb-4">
                                <div class="text-xs text-gray-500 dark:text-gray-400 mb-2 font-medium" x-text="(selectedPostData?.data?.created_time || selectedPostData?.data?.timestamp) ? new Date(selectedPostData?.data?.created_time || selectedPostData?.data?.timestamp).toLocaleString() : ''"></div>
                                <div class="text-sm text-gray-800 dark:text-gray-100 whitespace-pre-line" x-text="selectedPostData?.data?.message || selectedPostData?.data?.caption || '{{ __('No caption') }}'"></div>
                            </div>
                            
                            <!-- Actions -->
                            <div class="shrink-0 mt-auto">
                                <a :href="selectedPostData?.data?.permalink_url || selectedPostData?.data?.permalink" target="_blank" x-show="selectedPostData?.data?.permalink_url || selectedPostData?.data?.permalink" class="inline-flex items-center justify-center w-full px-4 py-2 bg-primary-50 dark:bg-primary-900/20 text-primary-600 dark:text-primary-400 text-sm font-medium rounded-lg hover:bg-primary-100 dark:hover:bg-primary-900/40 transition-colors border border-primary-200 dark:border-primary-800/30">
                                    <x-heroicon-o-link class="w-4 h-4 mr-2" />
                                    {{ __('View Original Post') }}
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Right Side: Metrics Chart -->
                    <div class="fb-modal-right p-6 relative">
                        <div class="mb-4">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ __('Post History') }}</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Historical timeline of metrics since publication') }}</p>
                        </div>
                        
                        <div class="flex-grow relative min-h-0 h-full">
                            <div x-show="isPostChartLoading" class="absolute inset-0 z-10 flex items-center justify-center bg-white/50 dark:bg-gray-900/50 backdrop-blur-sm rounded-lg">
                                <x-filament::loading-indicator class="h-8 w-8 text-primary-500" />
                            </div>
                            <canvas x-ref="postCanvas"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const registerFboDashboard = () => {
                Alpine.data('fboDashboard', () => {
                return {
                    tenantId: '{{ Filament\Facades\Filament::getTenant()->id ?? Filament\Facades\Filament::getTenant()->slug }}',
                    accounts: @json($selectedAccounts),
                    dateStart: '{{ $dateStart }}',
                    dateEnd: '{{ $dateEnd }}',
                    activeTab: 'facebook',
                    
                    isSummaryLoading: false,
                    isChartLoading: false,
                    isTableLoading: false,
                    
                    summaryRaw: {},
                    previousRaw: {},
                    chartDataRaw: [],
                    tableDataRaw: [],
                    
                    isPostModalOpen: false,
                    selectedPost: null,
                    isPostChartLoading: false,
                    postChartInstance: null,
                    postChartDataRaw: [],
                    selectedPostData: null,
                    isPostDetailsLoading: false,
                    
                    metricDictionary: {
                        'reach': { label: '{{ __('Reach') }}', color: 'var(--fb-reach)' },
                        'total_interactions': { label: '{{ __('Interactions') }}', color: 'var(--fb-interactions)' },
                        'interactions': { label: '{{ __('Interactions') }}', color: 'var(--fb-interactions)' },
                        'likes': { label: '{{ __('Likes') }}', color: 'var(--fb-likes)' },
                        'comments': { label: '{{ __('Comments') }}', color: 'var(--fb-comments)' },
                        'views': { label: '{{ __('Views') }}', color: 'var(--fb-views)' },
                        'video_views': { label: '{{ __('Video Views') }}', color: 'var(--fb-views)' },
                        'page_views_total': { label: '{{ __('Page Views') }}', color: 'var(--fb-views)' },
                        'follows_and_unfollows': { label: '{{ __('Follows') }}', color: 'var(--fb-follows)' },
                        'follows': { label: '{{ __('Follows') }}', color: 'var(--fb-follows)' },
                        'profile_views': { label: '{{ __('Profile Views') }}', color: '#14b8a6' },
                        'website_clicks': { label: '{{ __('Website Clicks') }}', color: '#06b6d4' },
                        'profile_links_taps': { label: '{{ __('Link Taps') }}', color: '#3b82f6' },
                        'saves': { label: '{{ __('Saves') }}', color: '#8b5cf6' },
                        'saved': { label: '{{ __('Saved') }}', color: '#8b5cf6' },
                        'shares': { label: '{{ __('Shares') }}', color: '#d946ef' },
                        'replies': { label: '{{ __('Replies') }}', color: '#f43f5e' },
                        'accounts_engaged': { label: '{{ __('Accounts Engaged') }}', color: '#f97316' },
                        'post_clicks': { label: '{{ __('Post Clicks') }}', color: '#06b6d4' },
                        'post_video_avg_time_watched': { label: '{{ __('Avg Watch Time') }}', color: '#eab308' },
                        'ig_reels_avg_watch_time': { label: '{{ __('Reels Avg Time') }}', color: '#eab308' },
                        'ig_reels_video_view_total_time': { label: '{{ __('Reels Total Time') }}', color: '#f59e0b' },
                        'profile_activity': { label: '{{ __('Profile Activity') }}', color: '#10b981' },
                        'profile_visits': { label: '{{ __('Profile Visits') }}', color: '#14b8a6' },
                        'reposts': { label: '{{ __('Reposts') }}', color: '#8b5cf6' }
                    },
                    
                    activeMetrics: {},
                    
                    searchQuery: '',
                    sortCol: 'reach',
                    sortDir: 'desc',
                    
                    currentPage: 1,
                    pageSize: 10,
                    
                    initDashboard() {
                        const boot = () => {
                            this.initChart();
                            
                            this.$watch('accounts', () => this.fetchAll());
                            this.$watch('dateStart', () => this.fetchAll());
                            this.$watch('dateEnd', () => this.fetchAll());
                            this.$watch('pageSize', () => { this.currentPage = 1; });
                            
                            if (this.accounts.length > 0 && this.dateStart && this.dateEnd) {
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
                            }).catch(err => console.error("Failed to load charting libraries", err));
                        }
                    },
                    
                    setTab(tab) {
                        this.activeTab = tab;
                        this.currentPage = 1;
                        this.searchQuery = '';
                        this.fetchAll(); // Refetch everything since metrics depend on the tab
                    },

                    forceRefresh() {
                        this.clearCache();
                        this.fetchAll();
                    },

                    clearCache() {
                        const accountKey = this.accounts.join('_');
                        const prefix = `fbo_${this.tenantId}_${accountKey}_${this.dateStart}_${this.dateEnd}`;
                        Object.keys(sessionStorage).forEach(key => {
                            if (key.startsWith(prefix)) {
                                sessionStorage.removeItem(key);
                            }
                        });
                    },

                    getCacheKey(endpoint) {
                        const accountKey = this.accounts.join('_');
                        return `fbo_${this.tenantId}_${accountKey}_${this.dateStart}_${this.dateEnd}_${endpoint}_${this.activeTab}_v1`;
                    },

                    async fetchAll() {
                        if (!this.accounts.length || !this.dateStart || !this.dateEnd) return;
                        this.fetchSummary();
                        this.fetchChart();
                        this.fetchTable();
                    },
                    
                    openPostModal(row) {
                        this.selectedPost = row;
                        this.isPostModalOpen = true;
                        // Prevent body scrolling
                        document.body.style.overflow = 'hidden';
                        
                        // Wait for modal to render to get canvas, then init chart
                        this.$nextTick(() => {
                            if (!this.postChartInstance) {
                                this.initPostChart();
                            }
                            this.fetchPostChart(row.id);
                            this.fetchPostDetails(row.id);
                        });
                    },
                    
                    closePostModal() {
                        this.isPostModalOpen = false;
                        this.selectedPost = null;
                        this.selectedPostData = null;
                        document.body.style.overflow = '';
                        if (this.postChartInstance) {
                            this.postChartInstance.destroy();
                            this.postChartInstance = null;
                        }
                    },

                    initPostChart() {
                        const ctx = this.$refs.postCanvas.getContext('2d');
                        
                        Chart.defaults.color = document.documentElement.classList.contains('dark') ? '#9ca3af' : '#6b7280';
                        Chart.defaults.font.family = "'Inter', sans-serif";
                        
                        this.postChartInstance = new Chart(ctx, {
                            type: 'line',
                            data: { labels: [], datasets: [] },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                interaction: { mode: 'index', intersect: false },
                                plugins: {
                                    legend: { position: 'top', labels: { usePointStyle: true, boxWidth: 8 } },
                                    tooltip: { backgroundColor: 'rgba(17, 24, 39, 0.9)', titleColor: '#fff', bodyColor: '#fff', padding: 12, cornerRadius: 8, displayColors: true }
                                },
                                scales: {
                                    x: { grid: { display: false, drawBorder: false } },
                                    y: { beginAtZero: true, grid: { color: document.documentElement.classList.contains('dark') ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.05)', drawBorder: false } }
                                }
                            }
                        });
                    },
                    
                    async fetchPostChart(postId) {
                        this.isPostChartLoading = true;
                        try {
                            const payload = {
                                tenant: this.tenantId,
                                account: this.accounts,
                                dateStart: this.dateStart,
                                dateEnd: this.dateEnd,
                                activeTab: this.activeTab,
                                postId: postId
                            };
                            
                            const response = await fetch('/api/fbo/chart', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                },
                                body: JSON.stringify(payload)
                            });
                            
                            const data = await response.json();
                            this.postChartDataRaw = data.chart || [];
                            this.renderPostChart();
                        } catch (err) {
                            console.error('Failed to fetch post chart:', err);
                        } finally {
                            this.isPostChartLoading = false;
                        }
                    },
                    
                    async fetchPostDetails(postId) {
                        this.isPostDetailsLoading = true;
                        this.selectedPostData = null;
                        try {
                            const payload = {
                                tenant: this.tenantId,
                                postId: postId
                            };
                            
                            const response = await fetch('/api/fbo/post', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                },
                                body: JSON.stringify(payload)
                            });
                            
                            const data = await response.json();
                            this.selectedPostData = data.post || null;
                        } catch (err) {
                            console.error('Failed to fetch post details:', err);
                        } finally {
                            this.isPostDetailsLoading = false;
                        }
                    },
                    
                    renderPostChart() {
                        if (!this.postChartInstance) return;
                        
                        const raw = this.postChartDataRaw;
                        if (!raw || raw.length === 0) {
                            this.postChartInstance.data.labels = [];
                            this.postChartInstance.data.datasets = [];
                            this.postChartInstance.update();
                            return;
                        }
                        
                        raw.sort((a, b) => new Date(a.daily) - new Date(b.daily));
                        const labels = raw.map(row => dayjs(row.daily).format('MMM D'));
                        
                        const datasets = [];
                        const addDataset = (key) => {
                            const dataPoints = raw.map(row => parseFloat(row[key] || row['trend_total_' + key] || 0));
                            if (dataPoints.some(v => v > 0)) {
                                const info = this.getMetricInfo(key);
                                const resolvedColor = this.getComputedColor(info.color);
                                datasets.push({
                                    label: info.label,
                                    data: dataPoints,
                                    borderColor: resolvedColor,
                                    backgroundColor: resolvedColor + '20',
                                    borderWidth: 2,
                                    pointRadius: 2,
                                    pointHoverRadius: 5,
                                    fill: true,
                                    tension: 0.4
                                });
                            }
                        };
                        
                        // Dynamically add datasets for any metric returned in the chart payload
                        if (raw.length > 0) {
                            const firstRow = raw[0];
                            const ignoredKeys = ['daily', 'id', 'name'];
                            const metricsInChart = Object.keys(firstRow).filter(k => !ignoredKeys.includes(k));
                            
                            metricsInChart.forEach(key => {
                                // If trend_total_ is prefixed, extract the real key
                                const actualKey = key.startsWith('trend_total_') ? key.replace('trend_total_', '') : key;
                                addDataset(actualKey);
                            });
                        }
                        
                        this.postChartInstance.data.labels = labels;
                        this.postChartInstance.data.datasets = datasets;
                        this.postChartInstance.update();
                    },
                    
                    async fetchSummary() {
                        if (!this.accounts.length || !this.dateStart || !this.dateEnd) return;
                        const cacheKey = this.getCacheKey('summary');
                        
                        if (sessionStorage.getItem(cacheKey)) {
                            const data = JSON.parse(sessionStorage.getItem(cacheKey));
                            this.summaryRaw = data.summary || {};
                            this.previousRaw = data.previous || {};
                            return;
                        }

                        this.isSummaryLoading = true;
                        try {
                            const response = await fetch('/api/fbo/summary', this.getFetchOptions());
                            const data = await response.json();
                            if (!data.error) {
                                sessionStorage.setItem(cacheKey, JSON.stringify(data));
                                this.summaryRaw = data.summary || {};
                                this.previousRaw = data.previous || {};
                            }
                        } catch (error) {
                            console.error('Error fetching summary:', error);
                        } finally {
                            this.isSummaryLoading = false;
                        }
                    },

                    async fetchChart() {
                        if (!this.accounts.length || !this.dateStart || !this.dateEnd) return;
                        const cacheKey = this.getCacheKey('chart');
                        
                        if (sessionStorage.getItem(cacheKey)) {
                            const data = JSON.parse(sessionStorage.getItem(cacheKey));
                            this.chartDataRaw = data.chart || [];
                            this.updateChart();
                            return;
                        }

                        this.isChartLoading = true;
                        try {
                            const response = await fetch('/api/fbo/chart', this.getFetchOptions());
                            const data = await response.json();
                            if (!data.error) {
                                sessionStorage.setItem(cacheKey, JSON.stringify(data));
                                this.chartDataRaw = data.chart || [];
                                this.updateChart();
                            }
                        } catch (error) {
                            console.error('Error fetching chart:', error);
                        } finally {
                            this.isChartLoading = false;
                        }
                    },

                    async fetchTable() {
                        if (!this.accounts.length || !this.dateStart || !this.dateEnd) return;
                        const cacheKey = this.getCacheKey('table');
                        
                        if (sessionStorage.getItem(cacheKey)) {
                            const data = JSON.parse(sessionStorage.getItem(cacheKey));
                            this.tableDataRaw = data.table || [];
                            return;
                        }

                        this.isTableLoading = true;
                        try {
                            const response = await fetch('/api/fbo/table', this.getFetchOptions());
                            const data = await response.json();
                            if (!data.error) {
                                sessionStorage.setItem(cacheKey, JSON.stringify(data));
                                this.tableDataRaw = data.table || [];
                                this.currentPage = 1;
                            }
                        } catch (error) {
                            console.error('Error fetching table:', error);
                        } finally {
                            this.isTableLoading = false;
                        }
                    },

                    getFetchOptions() {
                        const payload = {
                            tenant: this.tenantId,
                            account: this.accounts,
                            dateStart: this.dateStart,
                            dateEnd: this.dateEnd,
                            activeTab: this.activeTab
                        };
                        
                        return {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify(payload)
                        };
                    },

                    getMetricInfo(key) {
                        return this.metricDictionary[key] || { label: key.replace(/_/g, ' ').toUpperCase(), color: '#6b7280' };
                    },
                    
                    getComputedColor(colorVal) {
                        if (typeof colorVal === 'string' && colorVal.startsWith('var(')) {
                            const match = colorVal.match(/var\(([^)]+)\)/);
                            if (match) {
                                return getComputedStyle(document.documentElement).getPropertyValue(match[1]).trim() || '#6b7280';
                            }
                        }
                        return colorVal;
                    },
                    
                    get dynamicMetrics() {
                        const metrics = [];
                        const calcVariance = (current, prev) => {
                            if (!prev || Number(prev) === 0) return 0;
                            return ((Number(current) - Number(prev)) / Number(prev)) * 100;
                        };
                        
                        for (const key in this.summaryRaw) {
                            // Initialize activeMetrics to true for newly discovered keys
                            if (this.activeMetrics[key] === undefined) {
                                this.activeMetrics[key] = true;
                            }
                            
                            const val = this.summaryRaw[key] || 0;
                            const prev = this.previousRaw[key] || 0;
                            const info = this.getMetricInfo(key);
                            metrics.push({
                                key: key,
                                label: info.label,
                                color: info.color,
                                value: val,
                                prevValue: prev,
                                variance: calcVariance(val, prev)
                            });
                        }
                        return metrics;
                    },
                    
                    get availableTableMetrics() {
                        if (this.tableDataRaw.length === 0) return [];
                        const firstRow = this.tableDataRaw[0];
                        const ignoredKeys = ['id', 'name', 'page', 'page_id', 'page_title', 'channeledaccount', 'channeled_account_id', 'post_id', 'caption', 'message', 'media_type', 'permalink', 'permalink_url', 'timestamp', 'created_time', 'daily'];
                        return Object.keys(firstRow).filter(key => !ignoredKeys.includes(key.toLowerCase()) && !key.startsWith('trend_total_'));
                    },
                    
                    toggleMetric(key) {
                        this.activeMetrics[key] = !this.activeMetrics[key];
                        this.updateChart();
                    },

                    getVarianceClass(val) {
                        if (val === 0) return 'trend-neutral';
                        return val > 0 ? 'trend-up' : 'trend-down';
                    },

                    getVarianceIcon(val) {
                        if (val === 0) return '-';
                        return val > 0 ? '↑' : '↓';
                    },

                    formatVariance(val) {
                        if (val === 0) return '0%';
                        return Math.abs(val).toFixed(1) + '%';
                    },
                    
                    toggleMetric(metric) {
                        this.activeMetrics[metric] = !this.activeMetrics[metric];
                        this.updateChart();
                    },
                    
                    initChart() {
                        const ctx = this.$refs.canvas.getContext('2d');
                        
                        const config = {
                            type: 'line',
                            data: { labels: [], datasets: [] },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                interaction: { mode: 'index', intersect: false },
                                plugins: {
                                    legend: { display: false },
                                    tooltip: {
                                        backgroundColor: 'rgba(15, 23, 42, 0.9)',
                                        titleColor: '#fff',
                                        bodyColor: '#e2e8f0',
                                        borderColor: 'rgba(255,255,255,0.1)',
                                        borderWidth: 1,
                                        padding: 12,
                                        boxPadding: 6,
                                        usePointStyle: true
                                    }
                                },
                                scales: {
                                    x: { grid: { color: 'var(--fb-chart-grid)', drawBorder: false }, ticks: { color: 'var(--fb-chart-ticks)' } },
                                    yReach: { type: 'linear', position: 'left', display: false, grid: { color: 'var(--fb-chart-grid)', drawBorder: false }, ticks: { color: '#10b981' } },
                                    yInteractions: { type: 'linear', position: 'right', display: false, grid: { drawOnChartArea: false, drawBorder: false }, ticks: { color: '#6366f1' } }
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
                            if (r && (r.daily || r.date)) {
                                const dateStr = dayjs(r.daily || r.date).format('YYYY-MM-DD');
                                dataByDate[dateStr] = r;
                            }
                        });
                        
                        const paddedData = fullDateRange.map(dateStr => {
                            let obj = { daily: dateStr };
                            if (dataByDate[dateStr]) {
                                const r = dataByDate[dateStr];
                                Object.keys(this.metricDictionary).forEach(k => {
                                    obj[k] = parseFloat(r['trend_total_' + k] || r[k] || 0);
                                });
                            } else {
                                Object.keys(this.metricDictionary).forEach(k => obj[k] = 0);
                            }
                            return obj;
                        });
                        
                        const labels = paddedData.map(r => dayjs(r.daily).format('MMM D'));
                        const datasets = [];
                        
                        const activeKeys = Object.keys(this.activeMetrics).filter(k => this.activeMetrics[k]);
                        
                        activeKeys.forEach(key => {
                            const info = this.getMetricInfo(key);
                            const data = paddedData.map(r => r[key]);
                            
                            if (data.some(v => v > 0)) {
                                const resolvedColor = this.getComputedColor(info.color);
                                datasets.push({
                                    label: info.label,
                                    data: data,
                                    borderColor: resolvedColor,
                                    backgroundColor: resolvedColor + '20',
                                    borderWidth: 2,
                                    pointRadius: 0,
                                    pointHoverRadius: 6,
                                    fill: true,
                                    yAxisID: 'y' + key,
                                    tension: 0.4
                                });
                            }
                        });
                        
                        let gridDrawn = false;
                        const cssGridColor = getComputedStyle(document.documentElement).getPropertyValue('--fb-chart-grid').trim() || 'rgba(0,0,0,0.05)';
                        const cssTicksColor = getComputedStyle(document.documentElement).getPropertyValue('--fb-chart-ticks').trim() || '#6b7280';
                        
                        chart.options.scales.x.grid.color = cssGridColor;
                        chart.options.scales.x.ticks.color = cssTicksColor;
                        
                        Object.keys(chart.options.scales).forEach(scaleId => {
                            if (scaleId !== 'x') {
                                chart.options.scales[scaleId].display = false;
                            }
                        });
                        
                        activeKeys.forEach(key => {
                            let scaleId = 'y' + key;
                            if(!chart.options.scales[scaleId]) {
                                chart.options.scales[scaleId] = { type: 'linear', display: false, grid: { drawOnChartArea: false, drawBorder: false }, ticks: {} };
                            }
                            
                            const ds = datasets.find(d => d.yAxisID === scaleId);
                            if (ds) {
                                chart.options.scales[scaleId].display = true;
                                if (!gridDrawn) {
                                    chart.options.scales[scaleId].grid.drawOnChartArea = true;
                                    chart.options.scales[scaleId].grid.color = cssGridColor;
                                    chart.options.scales[scaleId].position = 'left';
                                    gridDrawn = true;
                                } else {
                                    chart.options.scales[scaleId].grid.drawOnChartArea = false;
                                    chart.options.scales[scaleId].position = 'right';
                                }
                                chart.options.scales[scaleId].ticks.color = ds.borderColor;
                            }
                        });
                        
                        chart.data.labels = labels;
                        chart.data.datasets = datasets;
                        chart.update();
                    },
                    
                    sortBy(col) {
                        if (this.sortCol === col) {
                            this.sortDir = this.sortDir === 'desc' ? 'asc' : 'desc';
                        } else {
                            this.sortCol = col;
                            this.sortDir = 'desc';
                        }
                        this.currentPage = 1;
                    },
                    
                    get sortedTableData() {
                        let data = [...this.tableDataRaw];
                        
                        if (this.searchQuery && this.searchQuery.trim() !== '') {
                            const query = this.searchQuery.toLowerCase().trim();
                            data = data.filter(row => String(row.name || '').toLowerCase().includes(query));
                        }

                        return data.sort((a, b) => {
                            const getValue = (row, field) => {
                                if (field === 'interactions') return Number(row.total_interactions || row.interactions || 0);
                                if (field === 'views') return Number(row.views || row.video_views || row.page_views_total || row.ig_reels_video_view_total_time || 0);
                                if (field === 'follows') return Number(row.follows || row.follows_and_unfollows || 0);
                                return Number(row[field] || 0);
                            };

                            let valA = getValue(a, this.sortCol);
                            let valB = getValue(b, this.sortCol);
                            
                            if (isNaN(valA) || isNaN(valB)) {
                                valA = String(a[this.sortCol] || '').toLowerCase();
                                valB = String(b[this.sortCol] || '').toLowerCase();
                            }

                            if (valA === valB) return 0;
                            if (this.sortDir === 'desc') return valA < valB ? 1 : -1;
                            return valA > valB ? 1 : -1;
                        });
                    },
                    
                    get totalPages() {
                        return Math.ceil(this.sortedTableData.length / this.pageSize) || 1;
                    },
                    
                    get paginatedTableData() {
                        const start = (this.currentPage - 1) * this.pageSize;
                        const end = start + Number(this.pageSize);
                        return this.sortedTableData.slice(start, end);
                    },
                    
                    nextPage() {
                        if (this.currentPage < this.totalPages) this.currentPage++;
                    },
                    
                    prevPage() {
                        if (this.currentPage > 1) this.currentPage--;
                    },
                    
                    formatNumber(num) {
                        if (num === undefined || num === null || isNaN(num)) return '0';
                        return new Intl.NumberFormat('en-US').format(num);
                    }
                }
            });
            };

            if (window.Alpine) {
                registerFboDashboard();
            } else {
                document.addEventListener('alpine:init', registerFboDashboard);
            }
        })();
    </script>
</x-filament-panels::page>
