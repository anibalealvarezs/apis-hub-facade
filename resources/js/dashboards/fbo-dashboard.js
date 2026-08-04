export function fboDashboard(config = {}) {
    return {
        tenantId: config.tenantId || '',
        accounts: config.accounts || [],
        accountNames: config.accountNames || {},
        dateStart: config.dateStart || '',
        dateEnd: config.dateEnd || '',
        activeTab: config.activeTab || 'instagram',
        activeBreakdownTab: config.activeBreakdownTab || 'reaction_type',
        csrfToken: config.csrfToken || '',

        isSummaryLoading: false,
        isChartLoading: false,
        isTableLoading: false,
        isBreakdownTableLoading: false,

        summaryRaw: {},
        previousRaw: {},
        chartDataRaw: [],
        tableDataRaw: [],
        breakdownDataRaw: [],
        isPostModalOpen: false,
        selectedPost: null,
        selectedPostData: null,
        isPostChartLoading: false,
        postChartDataRaw: [],
        isPostDetailsLoading: false,

        showTrends: false,
        trendData: {},

        metricDictionary: {
            'reach': { label: 'Reach', color: 'var(--fb-reach)' },
            'total_interactions': { label: 'Interactions', color: 'var(--fb-interactions)' },
            'interactions': { label: 'Interactions', color: 'var(--fb-interactions)' },
            'likes': { label: 'Likes', color: 'var(--fb-likes)' },
            'comments': { label: 'Comments', color: 'var(--fb-comments)' },
            'views': { label: 'Views', color: 'var(--fb-views)' },
            'content_views': { label: 'Content Views', color: 'var(--fb-views)' },
            'video_views': { label: 'Video Views', color: 'var(--fb-views)' },
            'page_views_total': { label: 'Page Views', color: 'var(--fb-views)' },
            'follows_and_unfollows': { label: 'Follows', color: 'var(--fb-follows)' },
            'follows': { label: 'Follows', color: 'var(--fb-follows)' },
            'profile_views': { label: 'Profile Views', color: '#14B8A6' },
            'website_clicks': { label: 'Website Clicks', color: '#06B6D4' },
            'profile_links_taps': { label: 'Link Taps', color: '#3B82F6' },
            'saves': { label: 'Saves', color: '#8B5CF6' },
            'saved': { label: 'Saved', color: '#8B5CF6' },
            'shares': { label: 'Shares', color: '#D946EF' },
            'replies': { label: 'Replies', color: '#F43F5E' },
            'accounts_engaged': { label: 'Accounts Engaged', color: '#F97316' },
            'post_clicks': { label: 'Post Clicks', color: '#06B6D4' },
            'post_video_avg_time_watched': { label: 'Avg Watch Time', color: '#EAB308' },
            'ig_reels_avg_watch_time': { label: 'Reels Avg Time', color: '#EAB308' },
            'ig_reels_video_view_total_time': { label: 'Reels Total Time', color: '#F59E0B' },
            'profile_activity': { label: 'Profile Activity', color: '#10B981' },
            'profile_visits': { label: 'Profile Visits', color: '#14B8A6' },
            'reposts': { label: 'Reposts', color: '#8B5CF6' }
        },

        activeMetrics: {},

        searchQuery: '',
        sortCol: 'reach',
        sortDir: 'desc',
        breakdownSearchQuery: '',
        breakdownSortCol: 'reach',
        breakdownSortDir: 'desc',
        activeFilters: {
            reaction_type: [],
            contact_button_type: [],
            follow_type: [],
            media_product_type: [],
        },

        currentPage: 1,
        pageSize: 10,
        breakdownCurrentPage: 1,
        breakdownPageSize: 10,

        restoreFromUrl() {
            const params = new URLSearchParams(window.location.search);
            const accParam = params.get('accounts');
            if (accParam) {
                const ids = accParam.split(',').filter(id => this.accountNames[id]);
                if (ids.length > 0) this.accounts = [ids[0]];
            }
            if (this.accounts.length === 0 && Object.keys(this.accountNames).length > 0) {
                this.accounts = [Object.keys(this.accountNames)[0]];
            }
            const ds = params.get('dateStart');
            if (ds && /^\d{4}-\d{2}-\d{2}$/.test(ds)) this.dateStart = ds;
            const de = params.get('dateEnd');
            if (de && /^\d{4}-\d{2}-\d{2}$/.test(de)) this.dateEnd = de;
            const tab = params.get('tab');
            if (tab && ['facebook', 'instagram'].includes(tab)) this.activeTab = tab;
        },

        syncToUrl() {
            const params = new URLSearchParams();
            if (this.accounts.length > 0) params.set('accounts', this.accounts.join(','));
            if (this.dateStart) params.set('dateStart', this.dateStart);
            if (this.dateEnd) params.set('dateEnd', this.dateEnd);
            if (this.activeTab) params.set('tab', this.activeTab);
            const qs = params.toString();
            const url = qs ? window.location.pathname + '?' + qs : window.location.pathname;
            history.replaceState(null, '', url);
        },

        initDashboard() {
            this.restoreFromUrl();

            const boot = () => {
                this.initChart();

                this.$watch('accounts', () => {
                    this.syncToUrl();
                    this.trendData = {};
                    this.fetchAll();
                });

                this.$watch('dateStart', () => {
                    this.syncToUrl();
                    this.trendData = {};
                    this.fetchAll();
                });

                this.$watch('dateEnd', () => {
                    this.syncToUrl();
                    this.trendData = {};
                    this.fetchAll();
                });
                this.$watch('pageSize', () => {
                    this.currentPage = 1;
                });
                this.$watch('breakdownPageSize', () => {
                    this.breakdownCurrentPage = 1;
                });

                if (this.accounts.length > 0 && this.dateStart && this.dateEnd) {
                    this.fetchAll();
                }
            };

            if (window.Chart && window.dayjs) {
                boot();
            } else if (window.importChartJs && window.importDayJs) {
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
            this.breakdownCurrentPage = 1;
            this.searchQuery = '';
            this.breakdownSearchQuery = '';
            this.activeMetrics = {};
            this.chartDataRaw = [];
            this.tableDataRaw = [];
            this.breakdownDataRaw = [];
            this.activeBreakdownTab = this.availableBreakdownTabs[0]?.value || '';
            Object.keys(this.activeFilters).forEach((key) => {
                if (!this.availableBreakdownTabs.some(t => t.value === key)) {
                    this.activeFilters[key] = [];
                }
            });
            this.syncToUrl();
            this.clearCache();
            this.fetchAll();
        },

        syncActiveMetricsFromSummary() {
            Object.keys(this.summaryRaw || {}).forEach((key) => {
                if (this.activeMetrics[key] === undefined) {
                    this.activeMetrics[key] = true;
                }
            });
        },

        syncActiveMetricsFromChart() {
            const firstRow = Array.isArray(this.chartDataRaw) ? this.chartDataRaw[0] : null;
            if (!firstRow || typeof firstRow !== 'object') {
                return;
            }

            const ignoredKeys = ['daily', 'date', 'metric_date', 'id', 'name'];
            Object.keys(firstRow).forEach((rawKey) => {
                if (ignoredKeys.includes(rawKey)) {
                    return;
                }

                const key = rawKey.startsWith('trend_total_') ? rawKey.replace('trend_total_', '') : rawKey;
                if (this.activeMetrics[key] === undefined) {
                    this.activeMetrics[key] = true;
                }
            });
        },

        get availableBreakdownTabs() {
            return this.activeTab === 'facebook'
                ? [{ value: 'reaction_type', label: 'Reaction type' }]
                : [
                    { value: 'contact_button_type', label: 'Contact button type' },
                    { value: 'follow_type', label: 'Follow type' },
                    { value: 'media_product_type', label: 'Media product type' },
                ];
        },

        get hasAnyFilters() {
            return Object.values(this.activeFilters).some((values) => Array.isArray(values) && values.length > 0);
        },

        setBreakdownTab(tab) {
            this.activeBreakdownTab = tab;
            this.breakdownCurrentPage = 1;
            this.fetchBreakdownTable();
        },

        toggleFilter(tab, value) {
            if (!tab || value === undefined || value === null || value === '') {
                return;
            }

            if (!Array.isArray(this.activeFilters[tab])) {
                this.activeFilters[tab] = [];
            }

            const normalized = String(value);
            const existingIndex = this.activeFilters[tab].indexOf(normalized);
            if (existingIndex >= 0) {
                this.activeFilters[tab].splice(existingIndex, 1);
            } else {
                this.activeFilters[tab].push(normalized);
            }

            this.currentPage = 1;
            this.fetchAll();
        },

        isFilterActive(tab, value) {
            const values = this.activeFilters[tab] || [];
            return values.includes(String(value));
        },

        clearFilters() {
            Object.keys(this.activeFilters).forEach((key) => {
                this.activeFilters[key] = [];
            });
            this.currentPage = 1;
            this.fetchAll();
        },

        forceRefresh() {
            this.clearCache();
            this.trendData = {};
            this.fetchAll();
        },

        safeCacheSet(key, value) {
            try {
                sessionStorage.setItem(key, value);
                return true;
            } catch (e) {
                if (e.name === 'QuotaExceededError' || e.code === 22) {
                    Object.keys(sessionStorage).forEach(k => {
                        if (k.startsWith('fbo_')) {
                            sessionStorage.removeItem(k);
                        }
                    });
                    try {
                        sessionStorage.setItem(key, value);
                        return true;
                    } catch {
                        console.warn('Cache still full after eviction, skipping cache for', key);
                        return false;
                    }
                }
                console.warn('Cache write failed:', e);
                return false;
            }
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
            const breakdownTab = this.activeBreakdownTab || 'none';
            const filtersKey = Object.keys(this.activeFilters)
                .sort()
                .map(key => `${key}:${(this.activeFilters[key] || []).join(',')}`)
                .join('|');
            return `fbo_${this.tenantId}_${accountKey}_${this.dateStart}_${this.dateEnd}_${endpoint}_${this.activeTab}_${breakdownTab}_${filtersKey}_v4`;
        },

        getPostsCacheKey() {
            const accountKey = this.accounts.join('_');
            return `fbo_${this.tenantId}_${accountKey}_${this.dateStart}_${this.dateEnd}_posts_${this.activeTab}_v2`;
        },

        async fetchAll() {
            if (!this.accounts.length || !this.dateStart || !this.dateEnd) return;
            await this.fetchSummary();
            await this.fetchChart();
            await this.fetchTable();
            await this.fetchBreakdownTable();
        },

        openPostModal(postId) {
            const normalizedPostId = String(postId ?? '').trim();
            if (!normalizedPostId) {
                return;
            }

            this.selectedPost = { id: normalizedPostId };
            this.isPostModalOpen = true;
            document.body.style.overflow = 'hidden';

            this.$nextTick(() => {
                if (!this.getPostChartInstance()) {
                    this.initPostChart();
                }
                this.fetchPostChart(normalizedPostId);
                this.fetchPostDetails(normalizedPostId);
            });
        },

        closePostModal() {
            this.isPostModalOpen = false;
            this.selectedPost = null;
            this.selectedPostData = null;
            document.body.style.overflow = '';
            const chart = this.getPostChartInstance();
            if (chart) {
                chart.destroy();
                this.setPostChartInstance(null);
            }
        },

        getPostChartInstance() {
            return this.$refs.postCanvas?._chartInstance || null;
        },

        setPostChartInstance(instance) {
            if (this.$refs.postCanvas) {
                this.$refs.postCanvas._chartInstance = instance;
            }
        },

        initPostChart() {
            if (!this.$refs.postCanvas) return;
            const ctx = this.$refs.postCanvas.getContext('2d');

            Chart.defaults.color = document.documentElement.classList.contains('dark') ? '#94A3B8' : '#6B7280';
            Chart.defaults.font.family = "'Inter', sans-serif";

            const chart = new Chart(ctx, {
                type: 'line',
                data: { labels: [], datasets: [] },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: {
                            display: true,
                            position: 'bottom',
                            labels: {
                                usePointStyle: true,
                                boxWidth: 8,
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(15, 23, 42, 0.9)',
                            titleColor: '#FFF',
                            bodyColor: '#E2E8F0',
                            borderColor: 'rgba(255,255,255,0.1)',
                            borderWidth: 1,
                            padding: 12,
                            boxPadding: 6,
                            usePointStyle: true,
                            callbacks: {
                                label: function(context) {
                                    var label = context.dataset.label || '';
                                    var value = context.parsed.y;
                                    return label + ': ' + Number(value).toLocaleString('en-US');
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: { color: 'var(--fb-chart-grid)', drawBorder: false },
                            ticks: { color: 'var(--fb-chart-ticks)' }
                        }
                    }
                }
            });
            
            this.setPostChartInstance(chart);
            this.applyPostChartTheme();
        },

        applyPostChartTheme() {
            const chart = this.getPostChartInstance();
            if (!chart) return;

            const isDark = document.documentElement.classList.contains('dark');
            const cssGridColor = getComputedStyle(document.documentElement).getPropertyValue('--fb-chart-grid').trim() || (isDark ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.05)');
            const cssTicksColor = getComputedStyle(document.documentElement).getPropertyValue('--fb-chart-ticks').trim() || (isDark ? '#94A3B8' : '#6B7280');

            chart.options.plugins.legend.labels.color = cssTicksColor;
            chart.options.scales.x.grid.color = cssGridColor;
            chart.options.scales.x.ticks.color = cssTicksColor;
            chart.options.plugins.tooltip.backgroundColor = isDark ? 'rgba(15, 23, 42, 0.92)' : 'rgba(255, 255, 255, 0.96)';
            chart.options.plugins.tooltip.titleColor = isDark ? '#FFF' : '#111827';
            chart.options.plugins.tooltip.bodyColor = isDark ? '#E2E8F0' : '#1F2937';
        },

        async fetchPostChart(postId) {
            this.isPostChartLoading = true;
            try {
                const normalizedPostId = String(postId ?? '').trim();
                if (!normalizedPostId) {
                    return;
                }

                const payload = {
                    tenant: this.tenantId,
                    account: this.accounts,
                    dateStart: this.dateStart,
                    dateEnd: this.dateEnd,
                    activeTab: this.activeTab,
                    activeFilters: this.activeFilters,
                    postId: normalizedPostId
                };

                const response = await fetch('/api/fbo/chart', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
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
                        'X-CSRF-TOKEN': this.csrfToken || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
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
            const chart = this.getPostChartInstance();
            if (!chart) return;

            const raw = this.postChartDataRaw;
            if (!raw || raw.length === 0) {
                chart.data.labels = [];
                chart.data.datasets = [];
                chart.update();
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
                    const scaleId = 'y' + key.replace(/[^a-zA-Z0-9]/g, '_');
                    datasets.push({
                        label: info.label,
                        data: dataPoints,
                        borderColor: resolvedColor,
                        backgroundColor: resolvedColor + '20',
                        borderWidth: 2,
                        pointRadius: 0,
                        pointHoverRadius: 6,
                        fill: true,
                        yAxisID: scaleId,
                        tension: 0.4
                    });
                }
            };

            if (raw.length > 0) {
                const firstRow = raw[0];
                const ignoredKeys = ['daily', 'id', 'name'];
                const metricsInChart = Object.keys(firstRow).filter(k => !ignoredKeys.includes(k));

                metricsInChart.forEach(key => {
                    const actualKey = key.startsWith('trend_total_') ? key.replace('trend_total_', '') : key;
                    addDataset(actualKey);
                });
            }

            Object.keys(chart.options.scales).forEach(sid => {
                if (sid !== 'x') delete chart.options.scales[sid];
            });

            let gridDrawn = false;
            const cssGridColor = getComputedStyle(document.documentElement).getPropertyValue('--fb-chart-grid').trim() || 'rgba(0,0,0,0.05)';

            datasets.forEach((ds, i) => {
                const scaleId = ds.yAxisID;
                chart.options.scales[scaleId] = {
                    type: 'linear',
                    display: true,
                    beginAtZero: true,
                    grid: { drawOnChartArea: !gridDrawn, color: cssGridColor, drawBorder: false },
                    position: i % 2 === 0 ? 'left' : 'right',
                    ticks: { display: false, color: ds.borderColor }
                };
                gridDrawn = true;
            });

            chart.data.labels = labels;
            chart.data.datasets = datasets;
            this.applyPostChartTheme();
            chart.update();
        },

        async fetchSummary() {
            if (!this.accounts.length || !this.dateStart || !this.dateEnd) return;
            const cacheKey = this.getCacheKey('summary');

            if (sessionStorage.getItem(cacheKey)) {
                const data = JSON.parse(sessionStorage.getItem(cacheKey));
                this.summaryRaw = data.summary || {};
                this.previousRaw = data.previous || {};
                this.syncActiveMetricsFromSummary();
                return;
            }

            this.isSummaryLoading = true;
            try {
                const response = await fetch('/api/fbo/summary', this.getFetchOptions());
                const data = await response.json();
                if (!data.error) {
                    this.safeCacheSet(cacheKey, JSON.stringify(data));
                    this.summaryRaw = data.summary || {};
                    this.previousRaw = data.previous || {};
                    this.syncActiveMetricsFromSummary();
                }
            } catch (error) {
                console.error('Error fetching summary:', error);
            } finally {
                this.isSummaryLoading = false;
            }
        },

        async fetchTrends() {
            if (!this.showTrends || !this.chartDataRaw || this.chartDataRaw.length === 0) return;
            
            const activeKeys = Object.keys(this.activeMetrics).filter(k => this.activeMetrics[k]);
            
            const allowedTrendMetrics = {
                'facebook': ['reach', 'interactions'],
                'instagram': ['reach', 'saves', 'shares']
            };
            
            const validMetrics = activeKeys.filter(m => (allowedTrendMetrics[this.activeTab] || []).includes(m));
            
            if (validMetrics.length === 0) {
                this.updateChart();
                return;
            }
            
            this.isChartLoading = true;
            
            try {
                const promises = validMetrics.map(async (metric) => {
                    const seriesDates = this.chartDataRaw.map(r => r.daily || r.date).filter(Boolean);
                    const seriesValues = this.chartDataRaw.map(r => {
                        let v = r[metric] ?? r['trend_total_' + metric] ?? r['trend_average_' + metric];
                        return v !== undefined && v !== null && v !== '' ? parseFloat(v) : null;
                    });
                    
                    const payload = {
                        tenant: this.tenantId,
                        metric: metric,
                        series: {
                            dates: seriesDates,
                            values: seriesValues
                        },
                        activeTab: this.activeTab
                    };
                    
                    const response = await fetch('/api/fbo/trend', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this.csrfToken
                        },
                        body: JSON.stringify(payload)
                    });
                    const data = await response.json();
                    if(data.trend) {
                        this.trendData[metric] = data.trend;
                    }
                });
                
                await Promise.all(promises);
                this.updateChart();
            } catch (error) {
                console.error('Error fetching trends:', error);
            } finally {
                this.isChartLoading = false;
            }
        },

        handleTrendToggle() {
            if (this.showTrends) {
                this.fetchTrends();
            } else {
                this.updateChart();
            }
        },

        async fetchChart() {
            if (!this.accounts.length || !this.dateStart || !this.dateEnd) return;
            const cacheKey = this.getCacheKey('chart');

            if (sessionStorage.getItem(cacheKey)) {
                const data = JSON.parse(sessionStorage.getItem(cacheKey));
                this.chartDataRaw = data.chart || [];
                this.syncActiveMetricsFromChart();
                if (this.showTrends) {
                    this.fetchTrends();
                } else {
                    this.updateChart();
                }
                return;
            }

            this.isChartLoading = true;
            try {
                const response = await fetch('/api/fbo/chart', this.getFetchOptions());
                const data = await response.json();
                if (!data.error) {
                    this.safeCacheSet(cacheKey, JSON.stringify(data));
                    this.chartDataRaw = data.chart || [];
                    this.syncActiveMetricsFromChart();
                    if (this.showTrends) {
                        this.fetchTrends();
                    } else {
                        this.updateChart();
                    }
                }
            } catch (error) {
                console.error('Error fetching chart:', error);
            } finally {
                this.isChartLoading = false;
            }
        },

        async fetchTable() {
            if (!this.accounts.length || !this.dateStart || !this.dateEnd) return;
            const cacheKey = this.getPostsCacheKey();

            if (sessionStorage.getItem(cacheKey)) {
                const data = JSON.parse(sessionStorage.getItem(cacheKey));
                this.tableDataRaw = data.table || [];
                return;
            }

            this.isTableLoading = true;
            try {
                const response = await fetch('/api/fbo/table', this.getFetchOptions({ tableMode: 'posts' }));
                const data = await response.json();
                if (!data.error) {
                    this.safeCacheSet(cacheKey, JSON.stringify(data));
                    this.tableDataRaw = data.table || [];
                    this.currentPage = 1;
                }
            } catch (error) {
                console.error('Error fetching table:', error);
            } finally {
                this.isTableLoading = false;
            }
        },

        async fetchBreakdownTable() {
            if (!this.accounts.length || !this.dateStart || !this.dateEnd) return;
            const cacheKey = this.getCacheKey('breakdown_table');

            if (sessionStorage.getItem(cacheKey)) {
                const data = JSON.parse(sessionStorage.getItem(cacheKey));
                this.breakdownDataRaw = data.table || [];
                return;
            }

            this.isBreakdownTableLoading = true;
            try {
                const response = await fetch('/api/fbo/table', this.getFetchOptions({ tableMode: 'breakdown' }));
                const data = await response.json();
                if (!data.error) {
                    this.safeCacheSet(cacheKey, JSON.stringify(data));
                    this.breakdownDataRaw = data.table || [];
                    this.breakdownCurrentPage = 1;
                }
            } catch (error) {
                console.error('Error fetching breakdown table:', error);
            } finally {
                this.isBreakdownTableLoading = false;
            }
        },

        getFetchOptions(extraPayload = {}) {
            const payload = {
                tenant: this.tenantId,
                account: this.accounts,
                dateStart: this.dateStart,
                dateEnd: this.dateEnd,
                activeTab: this.activeTab,
                activeFilters: this.activeFilters,
                breakdownTab: this.activeBreakdownTab || null,
                ...extraPayload
            };

            return {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken
                },
                body: JSON.stringify(payload)
            };
        },

        getMetricInfo(key) {
            return this.metricDictionary[key] || {
                label: key.replace(/_/g, ' ').toUpperCase(),
                color: '#6B7280'
            };
        },

        getComputedColor(colorVal) {
            if (typeof colorVal === 'string' && colorVal.startsWith('var(')) {
                const match = colorVal.match(/var\(([^)]+)\)/);
                if (match) {
                    return getComputedStyle(document.documentElement).getPropertyValue(match[1]).trim() || '#6B7280';
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

        get availableBreakdownMetrics() {
            if (this.breakdownDataRaw.length === 0) return [];
            const firstRow = this.breakdownDataRaw[0];
            const ignoredKeys = ['id', 'name', 'daily'];
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

        initChart() {
            if (!this.$refs.canvas) return;
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
                            titleColor: '#FFF',
                            bodyColor: '#E2E8F0',
                            borderColor: 'rgba(255,255,255,0.1)',
                            borderWidth: 1,
                            padding: 12,
                            boxPadding: 6,
                            usePointStyle: true,
                            callbacks: {
                                label: function(context) {
                                    var label = context.dataset.label || '';
                                    var value = context.parsed.y;
                                    return label + ': ' + Number(value).toLocaleString('en-US');
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: { color: 'var(--fb-chart-grid)', drawBorder: false },
                            ticks: { color: 'var(--fb-chart-ticks)' }
                        },
                        yReach: {
                            type: 'linear',
                            position: 'left',
                            display: false,
                            grid: { color: 'var(--fb-chart-grid)', drawBorder: false },
                            ticks: { color: '#10B981' }
                        },
                        yInteractions: {
                            type: 'linear',
                            position: 'right',
                            display: false,
                            grid: { drawOnChartArea: false, drawBorder: false },
                            ticks: { color: '#6366F1' }
                        }
                    }
                }
            };

            this.$refs.canvas._chartInstance = new Chart(ctx, config);
        },

        updateChart() {
            if (!this.$refs.canvas) return;
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

            if (this.showTrends) {
                activeKeys.forEach(key => {
                    if (this.trendData[key]) {
                        const info = this.getMetricInfo(key);
                        const resolvedColor = this.getComputedColor(info.color);
                        const trendLong = this.trendData[key].trend_long || this.trendData[key].trend || [];
                        const trendShort = this.trendData[key].trend_short || [];

                        if (trendShort.length) {
                            datasets.push({
                                label: info.label + ' (Trend Short)',
                                data: fullDateRange.map(d => {
                                    const point = trendShort.find(t => t.date === d);
                                    return point ? point.value : null;
                                }),
                                borderColor: resolvedColor,
                                borderDash: [5, 5],
                                borderWidth: 2,
                                pointRadius: 0,
                                fill: false,
                                yAxisID: 'y' + key,
                                tension: 0.4
                            });
                        }

                        if (trendLong.length) {
                            datasets.push({
                                label: info.label + ' (Trend Long)',
                                data: fullDateRange.map(d => {
                                    const point = trendLong.find(t => t.date === d);
                                    return point ? point.value : null;
                                }),
                                borderColor: resolvedColor,
                                borderDash: [2, 2],
                                borderWidth: 1,
                                pointRadius: 0,
                                fill: false,
                                yAxisID: 'y' + key,
                                tension: 0.4
                            });
                        }
                    }
                });
            }

            let gridDrawn = false;
            const cssGridColor = getComputedStyle(document.documentElement).getPropertyValue('--fb-chart-grid').trim() || 'rgba(0,0,0,0.05)';

            chart.options.scales.x.grid.color = cssGridColor;

            Object.keys(chart.options.scales).forEach(scaleId => {
                if (scaleId !== 'x') {
                    chart.options.scales[scaleId].display = false;
                }
            });

            activeKeys.forEach(key => {
                let scaleId = 'y' + key;
                if (!chart.options.scales[scaleId]) {
                    chart.options.scales[scaleId] = {
                        type: 'linear',
                        display: false,
                        grid: { drawOnChartArea: false, drawBorder: false },
                        ticks: {}
                    };
                }
                chart.options.scales[scaleId].min = 0;

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

        sortBreakdownBy(col) {
            if (this.breakdownSortCol === col) {
                this.breakdownSortDir = this.breakdownSortDir === 'desc' ? 'asc' : 'desc';
            } else {
                this.breakdownSortCol = col;
                this.breakdownSortDir = 'desc';
            }
            this.breakdownCurrentPage = 1;
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

        get sortedBreakdownData() {
            let data = [...this.breakdownDataRaw];

            if (this.breakdownSearchQuery && this.breakdownSearchQuery.trim() !== '') {
                const query = this.breakdownSearchQuery.toLowerCase().trim();
                data = data.filter(row => String(row.name || '').toLowerCase().includes(query));
            }

            return data.sort((a, b) => {
                let valA = Number(a[this.breakdownSortCol] || 0);
                let valB = Number(b[this.breakdownSortCol] || 0);

                if (isNaN(valA) || isNaN(valB)) {
                    valA = String(a[this.breakdownSortCol] || '').toLowerCase();
                    valB = String(b[this.breakdownSortCol] || '').toLowerCase();
                }

                if (valA === valB) return 0;
                if (this.breakdownSortDir === 'desc') return valA < valB ? 1 : -1;
                return valA > valB ? 1 : -1;
            });
        },

        get breakdownTotalPages() {
            return Math.ceil(this.sortedBreakdownData.length / this.breakdownPageSize) || 1;
        },

        get paginatedTableData() {
            const start = (this.currentPage - 1) * this.pageSize;
            const end = start + Number(this.pageSize);
            return this.sortedTableData.slice(start, end);
        },

        get paginatedBreakdownData() {
            const start = (this.breakdownCurrentPage - 1) * this.breakdownPageSize;
            const end = start + Number(this.breakdownPageSize);
            return this.sortedBreakdownData.slice(start, end);
        },

        nextPage() {
            if (this.currentPage < this.totalPages) this.currentPage++;
        },

        prevPage() {
            if (this.currentPage > 1) this.currentPage--;
        },

        nextBreakdownPage() {
            if (this.breakdownCurrentPage < this.breakdownTotalPages) this.breakdownCurrentPage++;
        },

        prevBreakdownPage() {
            if (this.breakdownCurrentPage > 1) this.breakdownCurrentPage--;
        },

        formatNumber(num) {
            if (num === undefined || num === null || isNaN(num)) return '0';
            return new Intl.NumberFormat('en-US').format(num);
        },

        formatMetaDuration(metricValue) {
            if (!metricValue || metricValue < 0) return "00:00";

            const totalSeconds = Math.floor(metricValue / 1000);

            const hours = Math.floor(totalSeconds / 3600);
            const minutes = Math.floor((totalSeconds % 3600) / 60);
            const seconds = totalSeconds % 60;

            const paddedMinutes = String(minutes).padStart(2, '0');
            const paddedSeconds = String(seconds).padStart(2, '0');

            if (hours > 0) {
                const paddedHours = String(hours).padStart(2, '0');
                return `${paddedHours}:${paddedMinutes}:${paddedSeconds}`;
            }

            return `${paddedMinutes}:${paddedSeconds}`;
        }
    };
}

if (typeof window !== 'undefined') {
    window.fboDashboard = fboDashboard;
}
