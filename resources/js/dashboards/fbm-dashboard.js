export function fbDashboard(config = {}) {
    return {
        tenantId: config.tenantId || '',
        accounts: config.accounts || [],
        accountNames: config.accountNames || {},
        dateStart: config.dateStart || '',
        dateEnd: config.dateEnd || '',
        activeTab: config.activeTab || 'campaigns',
        csrfToken: config.csrfToken || '',

        isSummaryLoading: false,
        isChartLoading: false,
        isTableLoading: false,

        summary: {
            spend: 0,
            clicks: 0,
            impressions: 0,
            reach: 0,
            frequency: 0,
            cpm: 0,
            ctr: 0,
            cpc: 0,
            results: 0,
            purchase_roas: 0,
            cost_per_result: 0,
            result_rate: 0
        },
        previous: {
            spend: 0,
            clicks: 0,
            impressions: 0,
            reach: 0,
            frequency: 0,
            cpm: 0,
            ctr: 0,
            cpc: 0,
            results: 0,
            purchase_roas: 0,
            cost_per_result: 0,
            result_rate: 0
        },
        chartDataRaw: [],
        tableDataRaw: [],
        
        showTrends: false,
        trendData: {},

        activeMetrics: {
            spend: true,
            clicks: true,
            impressions: true,
            reach: false,
            frequency: false,
            cpm: false,
            ctr: false,
            cpc: false,
            results: false,
            purchase_roas: false,
            cost_per_result: false,
            result_rate: false
        },

        activeFilters: { campaigns: [], adsets: [], ads: [], age: [], gender: [] },
        filterLabels: {},
        searchQuery: '',

        sortCol: 'spend',
        sortDir: 'desc',

        currentPage: 1,
        pageSize: 10,

        get hasAnyFilters() {
            return Object.values(this.activeFilters).some(arr => arr.length > 0);
        },

        restoreFromUrl() {
            const params = new URLSearchParams(window.location.search);
            const accParam = params.get('accounts');
            if (accParam) {
                const parsed = accParam.split(',').filter(id => this.accountNames[id]);
                if (parsed.length > 0) this.accounts = parsed;
            }
            const ds = params.get('dateStart');
            if (ds && /^\d{4}-\d{2}-\d{2}$/.test(ds)) this.dateStart = ds;
            const de = params.get('dateEnd');
            if (de && /^\d{4}-\d{2}-\d{2}$/.test(de)) this.dateEnd = de;
            const tab = params.get('tab');
            if (tab && ['campaigns', 'adsets', 'ads', 'age', 'gender'].includes(tab)) this.activeTab = tab;
            const metricsParam = params.get('metrics');
            if (metricsParam) {
                const enabledMetrics = metricsParam.split(',');
                Object.keys(this.activeMetrics).forEach(key => {
                    this.activeMetrics[key] = enabledMetrics.includes(key);
                });
            }
        },

        syncToUrl() {
            const params = new URLSearchParams();
            if (this.accounts.length > 0) params.set('accounts', this.accounts.join(','));
            if (this.dateStart) params.set('dateStart', this.dateStart);
            if (this.dateEnd) params.set('dateEnd', this.dateEnd);
            if (this.activeTab) params.set('tab', this.activeTab);
            const enabledMetrics = Object.entries(this.activeMetrics).filter(([k, v]) => v).map(([k]) => k);
            if (enabledMetrics.length > 0) params.set('metrics', enabledMetrics.join(','));
            const qs = params.toString();
            const url = qs ? window.location.pathname + '?' + qs : window.location.pathname;
            history.replaceState(null, '', url);
        },

        initDashboard() {
            this.restoreFromUrl();

            const boot = () => {
                this.initChart();

                this.$watch('accounts', (val) => {
                    if (val && val.length !== 1) {
                        this.activeFilters.campaigns = [];
                        this.activeFilters.adsets = [];
                        this.activeFilters.ads = [];
                        this.saveFilters();
                    }
                    this.syncToUrl();
                    this.trendData = {};
                    this.fetchAll();
                });
                this.$watch('activeFilters.campaigns', (val) => {
                    if (val && val.length !== 1) {
                        this.activeFilters.adsets = [];
                        this.activeFilters.ads = [];
                        this.saveFilters();
                    }
                });
                this.$watch('activeFilters.adsets', (val) => {
                    if (val && val.length !== 1) {
                        this.activeFilters.ads = [];
                        this.saveFilters();
                    }
                });
                this.$watch('dateStart', () => { this.syncToUrl(); this.fetchAll(); });
                this.$watch('dateEnd', () => { this.syncToUrl(); this.fetchAll(); });
                this.$watch('pageSize', () => {
                    this.currentPage = 1;
                });

                if (this.accounts.length > 0 && this.dateStart && this.dateEnd) {
                    this.loadFilters();
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
                }).catch(err => {
                    console.error("Failed to load charting libraries", err);
                });
            }
        },

        setTab(tab) {
            this.activeTab = tab;
            this.currentPage = 1;
            this.searchQuery = '';
            this.syncToUrl();
            this.fetchTable();
        },

        loadFilters() {
            if (!this.accounts.length) return;
            const accountKey = this.accounts.join('_');
            const saved = sessionStorage.getItem(`fbm_filters_${this.tenantId}_${accountKey}`);
            const savedLabels = sessionStorage.getItem(`fbm_labels_${this.tenantId}_${accountKey}`);
            if (saved) {
                try {
                    this.activeFilters = JSON.parse(saved);
                    this.filterLabels = savedLabels ? JSON.parse(savedLabels) : {};
                } catch (e) {
                    this.clearFiltersLocal();
                }
            } else {
                this.clearFiltersLocal();
            }
        },

        saveFilters() {
            if (!this.accounts.length) return;
            const accountKey = this.accounts.join('_');
            this.safeCacheSet(`fbm_filters_${this.tenantId}_${accountKey}`, JSON.stringify(this.activeFilters));
            this.safeCacheSet(`fbm_labels_${this.tenantId}_${accountKey}`, JSON.stringify(this.filterLabels));
        },

        clearFiltersLocal() {
            this.activeFilters = { campaigns: [], adsets: [], ads: [], age: [], gender: [] };
            this.filterLabels = {};
        },

        clearFilters() {
            this.clearFiltersLocal();
            this.saveFilters();
            this.fetchSummary();
            this.fetchChart();
        },

        canFilter(tab) {
            if (tab === 'age' || tab === 'gender') return true;
            if (tab === 'campaigns') return this.accounts.length === 1;
            if (tab === 'adsets') return this.accounts.length === 1 && this.activeFilters.campaigns.length === 1;
            if (tab === 'ads') return this.accounts.length === 1 && this.activeFilters.campaigns.length === 1 && this.activeFilters.adsets.length === 1;
            return false;
        },

        toggleFilter(tab, rowOrId) {
            if (!this.canFilter(tab)) return;
            if (!this.activeFilters[tab]) this.activeFilters[tab] = [];

            const isObject = typeof rowOrId === 'object' && rowOrId !== null;
            const value = isObject ? rowOrId.id : rowOrId;

            let newArr = [...this.activeFilters[tab]];
            const idx = newArr.indexOf(value);
            if (idx > -1) {
                newArr.splice(idx, 1);
            } else {
                newArr.push(value);
                if (isObject && rowOrId.name) {
                    this.filterLabels[value] = rowOrId.name;
                }
            }
            this.activeFilters[tab] = newArr;
            this.saveFilters();
            this.fetchSummary();
            this.fetchChart();
            this.fetchTable();
        },

        isFilterActive(tab, value) {
            return this.activeFilters[tab] && this.activeFilters[tab].includes(value);
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
                        if (k.startsWith('fbm_') || k.startsWith('fbo_') || k.startsWith('gsc_')) {
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
            const prefix = `fbm_${this.tenantId}_${accountKey}_${this.dateStart}_${this.dateEnd}`;
            Object.keys(sessionStorage).forEach(key => {
                if (key.startsWith(prefix)) {
                    sessionStorage.removeItem(key);
                }
            });
        },

        getCacheKey(endpoint, filterMode = 'all') {
            const accountKey = this.accounts.join('_');
            let filtersObj = {};
            if (filterMode === 'all') filtersObj = this.activeFilters;
            else if (filterMode === 'table') filtersObj = this.getTableFilters();

            const filterHash = filterMode === 'none' ? 'no_filters' : JSON.stringify(filtersObj);
            return `fbm_${this.tenantId}_${accountKey}_${this.dateStart}_${this.dateEnd}_${endpoint}_${this.activeTab}_${filterHash}_v5`;
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
                this.summary = data.summary || {
                    spend: 0,
                    clicks: 0,
                    impressions: 0,
                    reach: 0,
                    frequency: 0,
                    cpm: 0,
                    ctr: 0,
                    cpc: 0,
                    results: 0,
                    purchase_roas: 0,
                    cost_per_result: 0,
                    result_rate: 0
                };
                this.previous = data.previous || {
                    spend: 0,
                    clicks: 0,
                    impressions: 0,
                    reach: 0,
                    frequency: 0,
                    cpm: 0,
                    ctr: 0,
                    cpc: 0,
                    results: 0,
                    purchase_roas: 0,
                    cost_per_result: 0,
                    result_rate: 0
                };
                return;
            }

            this.isSummaryLoading = true;
            try {
                const response = await fetch('/api/fbm/summary', this.getFetchOptions());
                const data = await response.json();
                if (!data.error) {
                    this.safeCacheSet(cacheKey, JSON.stringify(data));
                    this.summary = data.summary || {
                        spend: 0,
                        clicks: 0,
                        impressions: 0,
                        reach: 0,
                        frequency: 0,
                        cpm: 0,
                        ctr: 0,
                        cpc: 0,
                        results: 0,
                        purchase_roas: 0,
                        cost_per_result: 0,
                        result_rate: 0
                    };
                    this.previous = data.previous || {
                        spend: 0,
                        clicks: 0,
                        impressions: 0,
                        reach: 0,
                        frequency: 0,
                        cpm: 0,
                        ctr: 0,
                        cpc: 0,
                        results: 0,
                        purchase_roas: 0,
                        cost_per_result: 0,
                        result_rate: 0
                    };
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
            const allowedMetrics = ['cost_per_result', 'purchase_roas'];
            const validMetrics = activeKeys.filter(m => allowedMetrics.includes(m));
            
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
                    
                    const response = await fetch('/api/fbm/trend', {
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
                if (this.showTrends) {
                    this.fetchTrends();
                } else {
                    this.updateChart();
                }
                return;
            }

            this.isChartLoading = true;
            try {
                const response = await fetch('/api/fbm/chart', this.getFetchOptions());
                const data = await response.json();
                if (!data.error) {
                    this.safeCacheSet(cacheKey, JSON.stringify(data));
                    this.chartDataRaw = data.chart || [];
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
            const cacheKey = this.getCacheKey('table', 'table');

            if (sessionStorage.getItem(cacheKey)) {
                const data = JSON.parse(sessionStorage.getItem(cacheKey));
                this.tableDataRaw = data.table || [];
                return;
            }

            this.isTableLoading = true;
            try {
                const response = await fetch('/api/fbm/table', this.getFetchOptions('table'));
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

        getTableFilters() {
            const filters = JSON.parse(JSON.stringify(this.activeFilters));

            if (this.activeTab === 'campaigns') {
                filters.campaigns = [];
                filters.adsets = [];
                filters.ads = [];
            } else if (this.activeTab === 'adsets') {
                filters.adsets = [];
                filters.ads = [];
            } else if (this.activeTab === 'ads') {
                filters.ads = [];
            }

            return filters;
        },

        getFetchOptions(filterMode = 'all') {
            const payload = {
                tenant: this.tenantId,
                account: this.accounts,
                dateStart: this.dateStart,
                dateEnd: this.dateEnd,
                activeTab: this.activeTab
            };

            if (filterMode === 'all') {
                payload.activeFilters = this.activeFilters;
            } else if (filterMode === 'table') {
                payload.activeFilters = this.getTableFilters();
            }

            return {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken
                },
                body: JSON.stringify(payload)
            };
        },

        get variance() {
            const calc = (current, prev) => {
                if (!prev || Number(prev) === 0) return 0;
                return ((Number(current) - Number(prev)) / Number(prev)) * 100;
            };
            return {
                spend: calc(this.summary.spend, this.previous.spend),
                clicks: calc(this.summary.clicks, this.previous.clicks),
                impressions: calc(this.summary.impressions, this.previous.impressions),
                reach: calc(this.summary.reach, this.previous.reach),
                frequency: calc(this.summary.frequency, this.previous.frequency),
                cpm: calc(this.summary.cpm, this.previous.cpm),
                ctr: calc(this.summary.ctr, this.previous.ctr),
                cpc: calc(this.summary.cpc, this.previous.cpc),
                results: calc(this.summary.results, this.previous.results),
                purchase_roas: calc(this.summary.purchase_roas, this.previous.purchase_roas),
                cost_per_result: calc(this.summary.cost_per_result, this.previous.cost_per_result),
                result_rate: calc(this.summary.result_rate, this.previous.result_rate)
            };
        },

        getVarianceClass(val, invert = false) {
            if (val === 0) return 'trend-neutral';
            const isPositive = val > 0;
            if (invert) return isPositive ? 'trend-down' : 'trend-up';
            return isPositive ? 'trend-up' : 'trend-down';
        },

        getVarianceIcon(val, invert = false) {
            if (val === 0) return '-';
            const isPositive = val > 0;
            if (invert) return isPositive ? '↓' : '↑';
            return isPositive ? '↑' : '↓';
        },

        formatVariance(val) {
            if (val === 0) return '0%';
            return Math.abs(val).toFixed(1) + '%';
        },

        toggleMetric(metric) {
            this.activeMetrics[metric] = !this.activeMetrics[metric];
            this.syncToUrl();
            this.updateChart();
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
                                    if (['Amount Spent', 'CPC', 'Cost per Result', 'CPM'].includes(label)) {
                                        return label + ': $' + Number(value).toFixed(2);
                                    }
                                    if (['CTR', 'Result Rate'].includes(label)) {
                                        return label + ': ' + Number(value).toFixed(2) + '%';
                                    }
                                    if (['Frequency'].includes(label)) {
                                        return label + ': ' + Number(value).toFixed(2);
                                    }
                                    if (label === 'ROAS') {
                                        return label + ': ' + Number(value).toFixed(2) + 'x';
                                    }
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
                        ySpend: {
                            type: 'linear',
                            position: 'left',
                            display: false,
                            grid: { color: 'var(--fb-chart-grid)', drawBorder: false },
                            ticks: { color: '#10B981' }
                        },
                        yImpressions: {
                            type: 'linear',
                            position: 'right',
                            display: false,
                            grid: { drawOnChartArea: false, drawBorder: false },
                            ticks: { color: '#6366F1' }
                        },
                        yClicks: {
                            type: 'linear',
                            position: 'right',
                            display: false,
                            grid: { drawOnChartArea: false, drawBorder: false },
                            ticks: { color: '#0EA5E9' }
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
                if (dataByDate[dateStr]) return dataByDate[dateStr];
                return {
                    daily: dateStr,
                    spend: 0, trend_total_spend: 0,
                    impressions: 0, trend_total_impressions: 0,
                    reach: 0, trend_total_reach: 0,
                    frequency: 0, trend_average_frequency: 0,
                    cpm: 0, trend_average_cpm: 0,
                    clicks: 0, trend_total_clicks: 0,
                    ctr: 0, trend_average_ctr: 0,
                    cpc: 0, trend_average_cpc: 0,
                    results: 0, trend_total_results: 0,
                    purchase_roas: 0, trend_average_purchase_roas: 0,
                    cost_per_result: 0, trend_average_cost_per_result: 0,
                    result_rate: 0, trend_average_result_rate: 0
                };
            });

            const labels = paddedData.map(r => dayjs(r.daily || r.date).format('MMM D'));
            const datasets = [];

            const chartData = paddedData;

            if (this.activeMetrics.spend) {
                datasets.push({
                    label: 'Amount Spent',
                    data: chartData.map(r => r.spend || r.trend_total_spend),
                    borderColor: '#10B981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    borderWidth: 2,
                    pointRadius: 0,
                    pointHoverRadius: 6,
                    fill: true,
                    yAxisID: 'ySpend',
                    tension: 0.4
                });
            }

            if (this.activeMetrics.impressions) {
                datasets.push({
                    label: 'Impressions',
                    data: chartData.map(r => r.impressions || r.trend_total_impressions),
                    borderColor: '#6366F1',
                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                    borderWidth: 2,
                    pointRadius: 0,
                    pointHoverRadius: 6,
                    fill: true,
                    yAxisID: 'yImpressions',
                    tension: 0.4
                });
            }

            if (this.activeMetrics.reach) {
                datasets.push({
                    label: 'Reach',
                    data: chartData.map(r => r.reach || r.trend_total_reach || 0),
                    borderColor: '#3B82F6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    borderWidth: 2,
                    pointRadius: 0,
                    pointHoverRadius: 6,
                    fill: true,
                    yAxisID: 'yReach',
                    tension: 0.4
                });
            }

            if (this.activeMetrics.frequency) {
                datasets.push({
                    label: 'Frequency',
                    data: chartData.map(r => r.frequency || r.trend_average_frequency || 0),
                    borderColor: '#F43F5E',
                    backgroundColor: 'rgba(244, 63, 94, 0.1)',
                    borderWidth: 2,
                    pointRadius: 0,
                    pointHoverRadius: 6,
                    fill: false,
                    yAxisID: 'yFrequency',
                    tension: 0.4
                });
            }

            if (this.activeMetrics.cpm) {
                datasets.push({
                    label: 'CPM',
                    data: chartData.map(r => r.cpm || r.trend_average_cpm || 0),
                    borderColor: '#EAB308',
                    backgroundColor: 'rgba(234, 179, 8, 0.1)',
                    borderWidth: 2,
                    pointRadius: 0,
                    pointHoverRadius: 6,
                    fill: false,
                    yAxisID: 'yCpm',
                    tension: 0.4
                });
            }

            if (this.activeMetrics.clicks) {
                datasets.push({
                    label: 'Clicks',
                    data: chartData.map(r => r.clicks || r.trend_total_clicks),
                    borderColor: '#0EA5E9',
                    backgroundColor: 'rgba(14, 165, 233, 0.1)',
                    borderWidth: 2,
                    pointRadius: 0,
                    pointHoverRadius: 6,
                    fill: true,
                    yAxisID: 'yClicks',
                    tension: 0.4
                });
            }

            if (this.activeMetrics.ctr) {
                datasets.push({
                    label: 'CTR',
                    data: chartData.map(r => (r.ctr || r.trend_average_ctr || 0) * 100),
                    borderColor: '#8B5CF6',
                    backgroundColor: 'rgba(139, 92, 246, 0.1)',
                    borderWidth: 2,
                    pointRadius: 0,
                    pointHoverRadius: 6,
                    fill: false,
                    yAxisID: 'yCtr',
                    tension: 0.4
                });
            }

            if (this.activeMetrics.cpc) {
                datasets.push({
                    label: 'CPC',
                    data: chartData.map(r => r.cpc || r.trend_average_cpc || 0),
                    borderColor: '#F59E0B',
                    backgroundColor: 'rgba(245, 158, 11, 0.1)',
                    borderWidth: 2,
                    pointRadius: 0,
                    pointHoverRadius: 6,
                    fill: false,
                    yAxisID: 'yCpc',
                    tension: 0.4
                });
            }

            if (this.activeMetrics.results) {
                datasets.push({
                    label: 'Purchases',
                    data: chartData.map(r => r.results || r.trend_total_results || 0),
                    borderColor: '#14B8A6',
                    backgroundColor: 'rgba(20, 184, 166, 0.1)',
                    borderWidth: 2,
                    pointRadius: 0,
                    pointHoverRadius: 6,
                    fill: true,
                    yAxisID: 'yResults',
                    tension: 0.4
                });
            }

            if (this.activeMetrics.purchase_roas) {
                datasets.push({
                    label: 'ROAS',
                    data: chartData.map(r => r.purchase_roas || r.trend_average_purchase_roas || 0),
                    borderColor: '#EC4899',
                    backgroundColor: 'rgba(236, 72, 153, 0.1)',
                    borderWidth: 2,
                    pointRadius: 0,
                    pointHoverRadius: 6,
                    fill: false,
                    yAxisID: 'yRoas',
                    tension: 0.4
                });
            }

            if (this.activeMetrics.cost_per_result) {
                datasets.push({
                    label: 'Cost per Result',
                    data: chartData.map(r => r.cost_per_result || r.trend_average_cost_per_result || 0),
                    borderColor: '#A855F7',
                    backgroundColor: 'rgba(168, 85, 247, 0.1)',
                    borderWidth: 2,
                    pointRadius: 0,
                    pointHoverRadius: 6,
                    fill: false,
                    yAxisID: 'yCpr',
                    tension: 0.4
                });
            }

            if (this.activeMetrics.result_rate) {
                datasets.push({
                    label: 'Result Rate',
                    data: chartData.map(r => (r.result_rate || r.trend_average_result_rate || 0) * 100),
                    borderColor: '#EF4444',
                    backgroundColor: 'rgba(239, 68, 68, 0.1)',
                    borderWidth: 2,
                    pointRadius: 0,
                    pointHoverRadius: 6,
                    fill: false,
                    yAxisID: 'yRr',
                    tension: 0.4
                });
            }

            if (this.showTrends) {
                const metricConfig = {
                    spend: { label: 'Amount Spent', color: '#10B981', yAxis: 'ySpend', mult: 1 },
                    impressions: { label: 'Impressions', color: '#6366F1', yAxis: 'yImpressions', mult: 1 },
                    reach: { label: 'Reach', color: '#3B82F6', yAxis: 'yReach', mult: 1 },
                    frequency: { label: 'Frequency', color: '#F43F5E', yAxis: 'yFrequency', mult: 1 },
                    cpm: { label: 'CPM', color: '#EAB308', yAxis: 'yCpm', mult: 1 },
                    clicks: { label: 'Clicks', color: '#0EA5E9', yAxis: 'yClicks', mult: 1 },
                    ctr: { label: 'CTR', color: '#8B5CF6', yAxis: 'yCtr', mult: 100 },
                    cpc: { label: 'CPC', color: '#F59E0B', yAxis: 'yCpc', mult: 1 },
                    results: { label: 'Purchases', color: '#14B8A6', yAxis: 'yResults', mult: 1 },
                    purchase_roas: { label: 'ROAS', color: '#EC4899', yAxis: 'yRoas', mult: 1 },
                    cost_per_result: { label: 'Cost per Result', color: '#A855F7', yAxis: 'yCpr', mult: 1 },
                    result_rate: { label: 'Result Rate', color: '#EF4444', yAxis: 'yRr', mult: 100 }
                };

                Object.keys(this.activeMetrics).forEach(metric => {
                    if (this.activeMetrics[metric] && this.trendData[metric]) {
                        const config = metricConfig[metric];
                        const trendLong = this.trendData[metric].trend_long || this.trendData[metric].trend || [];
                        const trendShort = this.trendData[metric].trend_short || [];

                        if (trendShort.length) {
                            datasets.push({
                                label: config.label + ' (EMA 7)',
                                data: fullDateRange.map(d => {
                                    const point = trendShort.find(t => t.date === d);
                                    return point ? point.value * config.mult : null;
                                }),
                                borderColor: config.color,
                                borderDash: [5, 5],
                                borderWidth: 2,
                                pointRadius: 0,
                                fill: false,
                                yAxisID: config.yAxis,
                                tension: 0.4
                            });
                        }

                        if (trendLong.length) {
                            datasets.push({
                                label: config.label + (trendShort.length ? ' (EMA 14)' : ' (Trend)'),
                                data: fullDateRange.map(d => {
                                    const point = trendLong.find(t => t.date === d);
                                    return point ? point.value * config.mult : null;
                                }),
                                borderColor: config.color,
                                borderDash: [2, 2],
                                borderWidth: 1,
                                pointRadius: 0,
                                fill: false,
                                yAxisID: config.yAxis,
                                tension: 0.4
                            });
                        }
                    }
                });
            }

            let gridDrawn = false;
            const cssGridColor = getComputedStyle(document.documentElement).getPropertyValue('--fb-chart-grid').trim();
            const cssTicksColor = getComputedStyle(document.documentElement).getPropertyValue('--fb-chart-ticks').trim();

            chart.options.scales.x.grid.color = cssGridColor;
            chart.options.scales.x.ticks.color = cssTicksColor;

            ['spend', 'impressions', 'reach', 'frequency', 'cpm', 'clicks', 'ctr', 'cpc', 'results', 'purchase_roas', 'cost_per_result', 'result_rate'].forEach(m => {
                let scaleId;
                if (m === 'purchase_roas') scaleId = 'yRoas';
                else if (m === 'cost_per_result') scaleId = 'yCpr';
                else if (m === 'result_rate') scaleId = 'yRr';
                else scaleId = 'y' + m.charAt(0).toUpperCase() + m.slice(1);

                if (!chart.options.scales[scaleId]) {
                    chart.options.scales[scaleId] = {
                        type: 'linear',
                        display: false,
                        grid: { drawOnChartArea: false, drawBorder: false }
                    };
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
                chart.options.scales[scaleId].min = 0;
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
                let valA = Number(a[this.sortCol]);
                let valB = Number(b[this.sortCol]);

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
            if (num === undefined || num === null) return '0';
            return new Intl.NumberFormat('en-US').format(num);
        },

        formatDecimal(num) {
            if (num === undefined || num === null) return '0.00';
            return new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(num);
        },

        formatCurrency(num) {
            if (num === undefined || num === null) return '$0.00';
            return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(num);
        },

        formatPercent(num) {
            if (num === undefined || num === null) return '0%';
            return (num * 100).toFixed(2) + '%';
        }
    };
}

if (typeof window !== 'undefined') {
    window.fbDashboard = fbDashboard;
}
