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
        
        .metrics-grid-fb { display: grid; grid-template-columns: repeat(6, 1fr); gap: 12px; margin-bottom: 25px; }

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

        <div class="metrics-grid-fb relative">
            <div x-show="isSummaryLoading" class="absolute inset-0 z-10 flex items-center justify-center bg-white/50 dark:bg-gray-900/50 backdrop-blur-sm rounded-xl">
                <x-filament::loading-indicator class="h-8 w-8 text-primary-500" />
            </div>

            <div class="card-stat-fb" :class="activeMetrics.reach ? 'active' : ''" @click="toggleMetric('reach')" style="--color: var(--fb-reach);">
                <div class="fb-label">{{ __('Reach') }}</div>
                <div class="card-metric-value" x-text="formatNumber(summary.reach)"></div>
                <div class="card-metric-trend" :class="getVarianceClass(variance.reach)">
                    <span x-text="getVarianceIcon(variance.reach)"></span>
                    <span x-text="formatVariance(variance.reach)"></span>
                </div>
            </div>
            <div class="card-stat-fb" :class="activeMetrics.interactions ? 'active' : ''" @click="toggleMetric('interactions')" style="--color: var(--fb-interactions);">
                <div class="fb-label">{{ __('Interactions') }}</div>
                <div class="card-metric-value" x-text="formatNumber(summary.interactions)"></div>
                <div class="card-metric-trend" :class="getVarianceClass(variance.interactions)">
                    <span x-text="getVarianceIcon(variance.interactions)"></span>
                    <span x-text="formatVariance(variance.interactions)"></span>
                </div>
            </div>
            <div class="card-stat-fb" :class="activeMetrics.likes ? 'active' : ''" @click="toggleMetric('likes')" style="--color: var(--fb-likes);">
                <div class="fb-label">{{ __('Likes') }}</div>
                <div class="card-metric-value" x-text="formatNumber(summary.likes)"></div>
                <div class="card-metric-trend" :class="getVarianceClass(variance.likes)">
                    <span x-text="getVarianceIcon(variance.likes)"></span>
                    <span x-text="formatVariance(variance.likes)"></span>
                </div>
            </div>
            <div class="card-stat-fb" :class="activeMetrics.comments ? 'active' : ''" @click="toggleMetric('comments')" style="--color: var(--fb-comments);">
                <div class="fb-label">{{ __('Comments') }}</div>
                <div class="card-metric-value" x-text="formatNumber(summary.comments)"></div>
                <div class="card-metric-trend" :class="getVarianceClass(variance.comments)">
                    <span x-text="getVarianceIcon(variance.comments)"></span>
                    <span x-text="formatVariance(variance.comments)"></span>
                </div>
            </div>
            <div class="card-stat-fb" :class="activeMetrics.views ? 'active' : ''" @click="toggleMetric('views')" style="--color: var(--fb-views);">
                <div class="fb-label">{{ __('Views') }}</div>
                <div class="card-metric-value" x-text="formatNumber(summary.views)"></div>
                <div class="card-metric-trend" :class="getVarianceClass(variance.views)">
                    <span x-text="getVarianceIcon(variance.views)"></span>
                    <span x-text="formatVariance(variance.views)"></span>
                </div>
            </div>
            <div class="card-stat-fb" :class="activeMetrics.follows ? 'active' : ''" @click="toggleMetric('follows')" style="--color: var(--fb-follows);">
                <div class="fb-label">{{ __('Follows') }}</div>
                <div class="card-metric-value" x-text="formatNumber(summary.follows)"></div>
                <div class="card-metric-trend" :class="getVarianceClass(variance.follows)">
                    <span x-text="getVarianceIcon(variance.follows)"></span>
                    <span x-text="formatVariance(variance.follows)"></span>
                </div>
            </div>
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
            
            <div class="tab-nav-fb">
                <div class="tab-fb" :class="activeTab === 'fb_pages' ? 'active' : ''" @click="setTab('fb_pages')">{{ __('FB PAGES') }}</div>
                <div class="tab-fb" :class="activeTab === 'fb_posts' ? 'active' : ''" @click="setTab('fb_posts')">{{ __('FB POSTS') }}</div>
                <div class="tab-fb" :class="activeTab === 'ig_accounts' ? 'active' : ''" @click="setTab('ig_accounts')">{{ __('IG ACCOUNTS') }}</div>
                <div class="tab-fb" :class="activeTab === 'ig_posts' ? 'active' : ''" @click="setTab('ig_posts')">{{ __('IG POSTS') }}</div>
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
                <table class="fb-table">
                    <thead>
                        <tr>
                            <th><span x-text="activeTab.toUpperCase()"></span></th>
                            <th class="metric-cell cursor-pointer" @click="sortBy('reach')">{{ __('Reach') }} <span x-show="sortCol === 'reach'" x-text="sortDir === 'desc' ? '↓' : '↑'"></span></th>
                            <th class="metric-cell cursor-pointer" @click="sortBy('interactions')">{{ __('Interactions') }} <span x-show="sortCol === 'interactions'" x-text="sortDir === 'desc' ? '↓' : '↑'"></span></th>
                            <th class="metric-cell cursor-pointer" @click="sortBy('likes')">{{ __('Likes') }} <span x-show="sortCol === 'likes'" x-text="sortDir === 'desc' ? '↓' : '↑'"></span></th>
                            <th class="metric-cell cursor-pointer" @click="sortBy('comments')">{{ __('Comments') }} <span x-show="sortCol === 'comments'" x-text="sortDir === 'desc' ? '↓' : '↑'"></span></th>
                            <th class="metric-cell cursor-pointer" @click="sortBy('views')">{{ __('Views') }} <span x-show="sortCol === 'views'" x-text="sortDir === 'desc' ? '↓' : '↑'"></span></th>
                            <th class="metric-cell cursor-pointer" @click="sortBy('follows')">{{ __('Follows') }} <span x-show="sortCol === 'follows'" x-text="sortDir === 'desc' ? '↓' : '↑'"></span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(row, index) in paginatedTableData" :key="row.id + '_' + index">
                            <tr class="hover:bg-gray-50 dark:hover:bg-white/5 transition duration-150">
                                <td class="font-medium">
                                    <div class="flex items-center gap-2">
                                        <a x-show="row.permalink_url || row.permalink" :href="row.permalink_url || row.permalink" target="_blank" class="text-primary-500 hover:text-primary-700">
                                            <x-heroicon-o-link class="w-4 h-4" />
                                        </a>
                                        <span x-text="row.name"></span>
                                    </div>
                                    <div x-show="row.media_type" class="text-xs text-gray-500 mt-1 uppercase" x-text="row.media_type"></div>
                                </td>
                                <td class="metric-cell" x-text="formatNumber(row.reach)"></td>
                                <td class="metric-cell" x-text="formatNumber(row.total_interactions || row.interactions)"></td>
                                <td class="metric-cell" x-text="formatNumber(row.likes)"></td>
                                <td class="metric-cell" x-text="formatNumber(row.comments)"></td>
                                <td class="metric-cell" x-text="formatNumber(row.views || row.video_views || row.page_views_total || row.ig_reels_video_view_total_time)"></td>
                                <td class="metric-cell" x-text="formatNumber(row.follows || row.follows_and_unfollows)"></td>
                            </tr>
                        </template>
                        <tr x-show="paginatedTableData.length === 0">
                            <td colspan="7" class="text-center py-8 text-gray-500 dark:text-gray-400">{{ __('No data available.') }}</td>
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
                    activeTab: 'fb_pages',
                    
                    isSummaryLoading: false,
                    isChartLoading: false,
                    isTableLoading: false,
                    
                    summaryRaw: {},
                    previousRaw: {},
                    chartDataRaw: [],
                    tableDataRaw: [],
                    
                    activeMetrics: { reach: true, interactions: true, likes: true, comments: false, views: false, follows: false },
                    
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

                    get summary() {
                        return {
                            reach: this.summaryRaw.reach || 0,
                            interactions: this.summaryRaw.total_interactions || 0,
                            likes: this.summaryRaw.likes || 0,
                            comments: this.summaryRaw.comments || 0,
                            views: (this.summaryRaw.views || 0) + (this.summaryRaw.video_views || 0) + (this.summaryRaw.page_views_total || 0),
                            follows: (this.summaryRaw.follows || 0) + (this.summaryRaw.follows_and_unfollows || 0)
                        };
                    },

                    get previous() {
                        return {
                            reach: this.previousRaw.reach || 0,
                            interactions: this.previousRaw.total_interactions || 0,
                            likes: this.previousRaw.likes || 0,
                            comments: this.previousRaw.comments || 0,
                            views: (this.previousRaw.views || 0) + (this.previousRaw.video_views || 0) + (this.previousRaw.page_views_total || 0),
                            follows: (this.previousRaw.follows || 0) + (this.previousRaw.follows_and_unfollows || 0)
                        };
                    },

                    get variance() {
                        const calc = (current, prev) => {
                            if (!prev || Number(prev) === 0) return 0;
                            return ((Number(current) - Number(prev)) / Number(prev)) * 100;
                        };
                        return {
                            reach: calc(this.summary.reach, this.previous.reach),
                            interactions: calc(this.summary.interactions, this.previous.interactions),
                            likes: calc(this.summary.likes, this.previous.likes),
                            comments: calc(this.summary.comments, this.previous.comments),
                            views: calc(this.summary.views, this.previous.views),
                            follows: calc(this.summary.follows, this.previous.follows)
                        };
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
                            if (dataByDate[dateStr]) {
                                const r = dataByDate[dateStr];
                                return {
                                    daily: dateStr,
                                    reach: r.trend_total_reach || 0,
                                    interactions: r.trend_total_total_interactions || 0,
                                    likes: r.trend_total_likes || 0,
                                    comments: r.trend_total_comments || 0,
                                    views: (r.trend_total_views || 0) + (r.trend_total_video_views || 0) + (r.trend_total_page_views_total || 0),
                                    follows: (r.trend_total_follows || 0) + (r.trend_total_follows_and_unfollows || 0)
                                };
                            }
                            return {
                                daily: dateStr, reach: 0, interactions: 0, likes: 0, comments: 0, views: 0, follows: 0
                            };
                        });
                        
                        const labels = paddedData.map(r => dayjs(r.daily).format('MMM D'));
                        const datasets = [];
                        const chartData = paddedData;
                        
                        if (this.activeMetrics.reach) {
                            datasets.push({
                                label: 'Reach', data: chartData.map(r => r.reach),
                                borderColor: '#10b981', backgroundColor: 'rgba(16, 185, 129, 0.1)',
                                borderWidth: 2, pointRadius: 0, pointHoverRadius: 6, fill: true, yAxisID: 'yReach', tension: 0.4
                            });
                        }
                        
                        if (this.activeMetrics.interactions) {
                            datasets.push({
                                label: 'Interactions', data: chartData.map(r => r.interactions),
                                borderColor: '#6366f1', backgroundColor: 'rgba(99, 102, 241, 0.1)',
                                borderWidth: 2, pointRadius: 0, pointHoverRadius: 6, fill: true, yAxisID: 'yInteractions', tension: 0.4
                            });
                        }
                        
                        if (this.activeMetrics.likes) {
                            datasets.push({
                                label: 'Likes', data: chartData.map(r => r.likes),
                                borderColor: '#0ea5e9', backgroundColor: 'rgba(14, 165, 233, 0.1)',
                                borderWidth: 2, pointRadius: 0, pointHoverRadius: 6, fill: true, yAxisID: 'yLikes', tension: 0.4
                            });
                        }
                        
                        if (this.activeMetrics.comments) {
                            datasets.push({
                                label: 'Comments', data: chartData.map(r => r.comments),
                                borderColor: '#8b5cf6', backgroundColor: 'rgba(139, 92, 246, 0.1)',
                                borderWidth: 2, pointRadius: 0, pointHoverRadius: 6, fill: false, yAxisID: 'yComments', tension: 0.4
                            });
                        }

                        if (this.activeMetrics.views) {
                            datasets.push({
                                label: 'Views', data: chartData.map(r => r.views),
                                borderColor: '#f59e0b', backgroundColor: 'rgba(245, 158, 11, 0.1)',
                                borderWidth: 2, pointRadius: 0, pointHoverRadius: 6, fill: false, yAxisID: 'yViews', tension: 0.4
                            });
                        }

                        if (this.activeMetrics.follows) {
                            datasets.push({
                                label: 'Follows', data: chartData.map(r => r.follows),
                                borderColor: '#ec4899', backgroundColor: 'rgba(236, 72, 153, 0.1)',
                                borderWidth: 2, pointRadius: 0, pointHoverRadius: 6, fill: false, yAxisID: 'yFollows', tension: 0.4
                            });
                        }
                        
                        // Manage scale visibility and background grid dynamically
                        let gridDrawn = false;
                        const cssGridColor = getComputedStyle(document.documentElement).getPropertyValue('--fb-chart-grid').trim();
                        const cssTicksColor = getComputedStyle(document.documentElement).getPropertyValue('--fb-chart-ticks').trim();
                        
                        chart.options.scales.x.grid.color = cssGridColor;
                        chart.options.scales.x.ticks.color = cssTicksColor;
                        
                        ['reach', 'interactions', 'likes', 'comments', 'views', 'follows'].forEach(m => {
                            let scaleId = 'y' + m.charAt(0).toUpperCase() + m.slice(1);
                            
                            if(!chart.options.scales[scaleId]) {
                                chart.options.scales[scaleId] = { type: 'linear', display: false, grid: { drawOnChartArea: false, drawBorder: false } };
                            }

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
