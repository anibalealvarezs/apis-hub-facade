import { dataTable } from './data-table';

/**
 * Canonical Mailchimp metrics. The first four are counters that can be fed to the
 * trend service; the two rates are always derived from the counters, never sent
 * to the worker, because the worker only stores raw counters.
 */
const MAILCHIMP_METRICS = {
    sends: {
        key: 'sends',
        label: 'Emails Sent',
        color: '#1F9E8F',
        background: 'rgba(31, 158, 143, 0.1)',
        axis: 'ySends',
        trendable: true,
    },
    opens: {
        key: 'opens',
        label: 'Opens',
        color: '#F5A623',
        background: 'rgba(245, 166, 35, 0.1)',
        axis: 'yOpens',
        trendable: true,
    },
    clicks: {
        key: 'clicks',
        label: 'Clicks',
        color: '#2F6FE0',
        background: 'rgba(47, 111, 224, 0.1)',
        axis: 'yClicks',
        trendable: true,
    },
    open_rate: {
        key: 'open_rate',
        label: 'Open Rate',
        color: '#7E57C2',
        background: 'rgba(126, 87, 194, 0.08)',
        axis: 'yOpenRate',
        trendable: false,
        isRate: true,
    },
    click_rate: {
        key: 'click_rate',
        label: 'Click Rate',
        color: '#E4572E',
        background: 'rgba(228, 87, 46, 0.08)',
        axis: 'yClickRate',
        trendable: false,
        isRate: true,
    },
    unsubscribes: {
        key: 'unsubscribes',
        label: 'Unsubscribes',
        color: '#D64545',
        background: 'rgba(214, 69, 69, 0.1)',
        axis: 'yUnsubscribes',
        trendable: true,
    },
    bounces: {
        key: 'bounces',
        label: 'Bounces',
        color: '#F43F5E',
        background: 'rgba(244, 63, 94, 0.1)',
        axis: 'yBounces',
        trendable: true,
    },
};

const TABS = ['campaigns', 'automations', 'audiences', 'urls'];

const EMPTY_SUMMARY = {
    sends: 0,
    opens: 0,
    opens_standard: 0,
    opens_proxy: 0,
    clicks: 0,
    clicks_unique: 0,
    open_rate: 0,
    click_rate: 0,
    bounces: 0,
    bounces_hard: 0,
    bounces_soft: 0,
    unsubscribes: 0,
};

export function mailchimpDashboard(config = {}) {
    return {
        tenantId: config.tenantId || '',
        account: config.account || '',
        selectedAccount: config.selectedAccount || '',
        accountNames: config.accountNames || {},
        dateStart: config.dateStart || '',
        dateEnd: config.dateEnd || '',
        activeTab: config.activeTab || 'campaigns',
        csrfToken: config.csrfToken || '',

        isSummaryLoading: false,
        isChartLoading: false,
        isTableLoading: false,

        summary: { ...EMPTY_SUMMARY },
        previous: { ...EMPTY_SUMMARY },
        chartDataRaw: [],
        tableState: dataTable({ sortCol: 'clicks', sortDir: 'desc', searchKeys: ['id'] }),
        trendData: {},
        showTrends: false,

        activeMetrics: {
            sends: true,
            opens: true,
            clicks: true,
            open_rate: false,
            click_rate: false,
            unsubscribes: false,
            bounces: false,
        },

        activeFilters: { campaigns: [], automations: [], audiences: [], urls: [] },

        get hasAnyFilters() {
            return Object.values(this.activeFilters).some(arr => arr.length > 0);
        },

        restoreFromUrl() {
            const params = new URLSearchParams(window.location.search);
            const accParam = params.get('account');
            if (accParam && this.accountNames[accParam]) {
                this.account = accParam;
                this.selectedAccount = accParam;
            }
            if (!this.selectedAccount && Object.keys(this.accountNames).length > 0) {
                const firstAcc = Object.keys(this.accountNames)[0];
                this.account = firstAcc;
                this.selectedAccount = firstAcc;
            }
            const ds = params.get('dateStart');
            if (ds && /^\d{4}-\d{2}-\d{2}$/.test(ds)) this.dateStart = ds;
            const de = params.get('dateEnd');
            if (de && /^\d{4}-\d{2}-\d{2}$/.test(de)) this.dateEnd = de;
            const tab = params.get('tab');
            if (tab && TABS.includes(tab)) this.activeTab = tab;
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
            if (this.account) params.set('account', this.account);
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

                this.$watch('account', () => {
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

                this.$watch('tableState.pageSize', () => {
                    this.tableState.currentPage = 1;
                });

                if (this.account && this.dateStart && this.dateEnd) {
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
            this.tableState.currentPage = 1;
            this.tableState.searchQuery = '';
            this.syncToUrl();
            this.fetchTable();
            if (this.$wire && typeof this.$wire.setActiveTab === 'function') {
                this.$wire.setActiveTab(tab);
            }
        },

        loadFilters() {
            if (!this.account) return;
            const saved = sessionStorage.getItem(`mailchimp_filters_${this.tenantId}_${this.account}`);
            if (saved) {
                try {
                    const parsed = JSON.parse(saved);
                    // Merge onto the known tabs so a stale cache from another build cannot inject keys.
                    this.activeFilters = {
                        campaigns: Array.isArray(parsed?.campaigns) ? parsed.campaigns : [],
                        automations: Array.isArray(parsed?.automations) ? parsed.automations : [],
                        audiences: Array.isArray(parsed?.audiences) ? parsed.audiences : [],
                        urls: Array.isArray(parsed?.urls) ? parsed.urls : [],
                    };
                } catch (e) {
                    this.clearFiltersLocal();
                }
            } else {
                this.clearFiltersLocal();
            }
        },

        saveFilters() {
            if (!this.account) return;
            this.safeCacheSet(`mailchimp_filters_${this.tenantId}_${this.account}`, JSON.stringify(this.activeFilters));
        },

        clearFiltersLocal() {
            this.activeFilters = { campaigns: [], automations: [], audiences: [], urls: [] };
        },

        clearFilters() {
            this.clearFiltersLocal();
            this.saveFilters();
            this.fetchSummary();
            this.fetchChart();
        },

        toggleFilter(tab, value) {
            if (!TABS.includes(tab)) return;
            if (!this.activeFilters[tab]) this.activeFilters[tab] = [];
            const idx = this.activeFilters[tab].indexOf(value);
            if (idx > -1) {
                this.activeFilters[tab].splice(idx, 1);
            } else {
                this.activeFilters[tab].push(value);
            }
            this.saveFilters();
            this.fetchSummary();
            this.fetchChart();
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
                        if (k.startsWith('gsc_') || k.startsWith('fbo_') || k.startsWith('fbm_') || k.startsWith('mailchimp_')) {
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
            const prefix = `mailchimp_${this.tenantId}_${this.account}_${this.dateStart}_${this.dateEnd}`;
            Object.keys(sessionStorage).forEach(key => {
                if (key.startsWith(prefix)) {
                    sessionStorage.removeItem(key);
                }
            });
        },

        getCacheKey(endpoint, includeFilters = true) {
            const filterHash = includeFilters ? JSON.stringify(this.activeFilters) : 'no_filters';
            return `mailchimp_${this.tenantId}_${this.account}_${this.dateStart}_${this.dateEnd}_${endpoint}_${this.activeTab}_${filterHash}`;
        },

        async fetchAll() {
            if (!this.account || !this.dateStart || !this.dateEnd) return;
            this.fetchSummary();
            this.fetchChart();
            this.fetchTable();
        },

        async fetchSummary() {
            if (!this.account || !this.dateStart || !this.dateEnd) return;
            const cacheKey = this.getCacheKey('summary');

            if (sessionStorage.getItem(cacheKey)) {
                const data = JSON.parse(sessionStorage.getItem(cacheKey));
                this.summary = { ...EMPTY_SUMMARY, ...(data.summary || {}) };
                this.previous = { ...EMPTY_SUMMARY, ...(data.previous || {}) };
                return;
            }

            this.isSummaryLoading = true;
            try {
                const response = await fetch('/api/mailchimp/summary', this.getFetchOptions());
                const data = await response.json();
                if (!data.error) {
                    this.safeCacheSet(cacheKey, JSON.stringify(data));
                    this.summary = { ...EMPTY_SUMMARY, ...(data.summary || {}) };
                    this.previous = { ...EMPTY_SUMMARY, ...(data.previous || {}) };
                }
            } catch (error) {
                console.error('Error fetching summary:', error);
            } finally {
                this.isSummaryLoading = false;
            }
        },

        async fetchChart() {
            if (!this.account || !this.dateStart || !this.dateEnd) return;
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
                const response = await fetch('/api/mailchimp/chart', this.getFetchOptions());
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

        async fetchTrends() {
            if (!this.showTrends || !this.chartDataRaw || this.chartDataRaw.length === 0) return;

            const validMetrics = Object.keys(this.activeMetrics)
                .filter(k => this.activeMetrics[k] && MAILCHIMP_METRICS[k]?.trendable);

            if (validMetrics.length === 0) {
                this.updateChart();
                return;
            }

            this.isChartLoading = true;

            try {
                const promises = validMetrics.map(async (metric) => {
                    const seriesDates = this.chartDataRaw.map(r => r.daily || r.date || r.metric_date).filter(Boolean);
                    const seriesValues = this.chartDataRaw.map(r => {
                        const v = r[metric];
                        return v !== undefined && v !== null && v !== '' ? parseFloat(v) : null;
                    });

                    const payload = {
                        tenant: this.tenantId,
                        metric: metric,
                        series: {
                            dates: seriesDates,
                            values: seriesValues
                        }
                    };

                    const response = await fetch('/api/mailchimp/trend', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this.csrfToken
                        },
                        body: JSON.stringify(payload)
                    });
                    const data = await response.json();
                    if (data.trend) {
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

        async fetchTable() {
            if (!this.account || !this.dateStart || !this.dateEnd) return;
            const cacheKey = this.getCacheKey('table', false);

            if (sessionStorage.getItem(cacheKey)) {
                const data = JSON.parse(sessionStorage.getItem(cacheKey));
                this.tableState.rows = data.table || [];
                return;
            }

            this.isTableLoading = true;
            try {
                const response = await fetch('/api/mailchimp/table', this.getFetchOptions(false));
                const data = await response.json();
                if (!data.error) {
                    this.safeCacheSet(cacheKey, JSON.stringify(data));
                    this.tableState.rows = data.table || [];
                    this.tableState.currentPage = 1;
                }
            } catch (error) {
                console.error('Error fetching table:', error);
            } finally {
                this.isTableLoading = false;
            }
        },

        getFetchOptions(includeFilters = true) {
            const payload = {
                tenant: this.tenantId,
                account: this.account,
                dateStart: this.dateStart,
                dateEnd: this.dateEnd,
                activeTab: this.activeTab
            };

            if (includeFilters && this.activeFilters) {
                payload.activeFilters = { ...this.activeFilters };
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
                sends: calc(this.summary.sends, this.previous.sends),
                opens: calc(this.summary.opens, this.previous.opens),
                clicks: calc(this.summary.clicks, this.previous.clicks),
                open_rate: calc(this.summary.open_rate, this.previous.open_rate),
                click_rate: calc(this.summary.click_rate, this.previous.click_rate),
                unsubscribes: calc(this.summary.unsubscribes, this.previous.unsubscribes),
                bounces: calc(this.summary.bounces, this.previous.bounces),
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
            if (!MAILCHIMP_METRICS[metric]) return;
            this.activeMetrics[metric] = !this.activeMetrics[metric];
            this.syncToUrl();
            this.updateChart();
        },

        initChart() {
            if (!this.$refs.canvas) return;
            const ctx = this.$refs.canvas.getContext('2d');

            const counterAxis = (position) => ({
                type: 'linear',
                position: position,
                display: false,
                grid: { drawOnChartArea: false, drawBorder: false },
                min: 0,
                suggestedMax: 5,
            });

            const rateAxis = (position) => ({
                type: 'linear',
                position: position,
                display: false,
                grid: { drawOnChartArea: false, drawBorder: false },
                ticks: { callback: (v) => Number(v).toFixed(2) + '%' },
                min: 0,
                suggestedMax: 5,
            });

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
                                label: function (context) {
                                    var label = context.dataset.label || '';
                                    var value = context.parsed.y;
                                    if (label.includes('Rate')) {
                                        return label + ': ' + Number(value).toFixed(2) + '%';
                                    }
                                    return label + ': ' + Number(value).toLocaleString('en-US');
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: { color: 'var(--mailchimp-chart-grid)', drawBorder: false },
                            ticks: { color: 'var(--mailchimp-chart-ticks)' }
                        },
                        ySends: counterAxis('left'),
                        yOpens: counterAxis('right'),
                        yClicks: counterAxis('right'),
                        yOpenRate: rateAxis('right'),
                        yClickRate: rateAxis('right'),
                        yUnsubscribes: counterAxis('right'),
                        yBounces: counterAxis('right'),
                    }
                }
            };

            this.$refs.canvas._chartInstance = new Chart(ctx, config);
        },

        /**
         * Recompute the per-day ratios from the per-day counters so a rate line is always
         * consistent with the counters plotted next to it.
         */
        withDerivedRates(row) {
            const sends = Number(row.sends) || 0;
            return {
                ...row,
                sends,
                opens: Number(row.opens) || 0,
                clicks: Number(row.clicks) || 0,
                unsubscribes: Number(row.unsubscribes) || 0,
                bounces: Number(row.bounces) || 0,
                open_rate: sends > 0 ? (Number(row.opens) || 0) / sends : 0,
                click_rate: sends > 0 ? (Number(row.clicks) || 0) / sends : 0,
            };
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
                if (r && (r.daily || r.date || r.metric_date)) {
                    const dateStr = dayjs(r.daily || r.date || r.metric_date).format('YYYY-MM-DD');
                    dataByDate[dateStr] = r;
                }
            });

            const paddedData = fullDateRange.map(dateStr => {
                if (dataByDate[dateStr]) return this.withDerivedRates(dataByDate[dateStr]);
                return { ...EMPTY_SUMMARY, daily: dateStr };
            });

            const labels = paddedData.map(r => dayjs(r.daily).format('MMM D'));
            const datasets = [];

            Object.entries(MAILCHIMP_METRICS).forEach(([key, config]) => {
                if (!this.activeMetrics[key]) return;

                datasets.push({
                    label: config.label,
                    data: paddedData.map(r => config.isRate ? (r[key] * 100) : r[key]),
                    borderColor: config.color,
                    backgroundColor: config.background,
                    borderWidth: 2,
                    pointRadius: 0,
                    pointHoverRadius: 6,
                    fill: !config.isRate,
                    yAxisID: config.axis,
                    tension: 0.4
                });
            });

            if (this.showTrends) {
                Object.entries(MAILCHIMP_METRICS).forEach(([key, config]) => {
                    if (!this.activeMetrics[key] || !config.trendable || !this.trendData[key]) return;

                    const trendLinear = this.trendData[key].trend_linear || [];
                    const trendSma = this.trendData[key].trend_sma || [];

                    if (trendLinear.length) {
                        datasets.push({
                            label: config.label + ' (Trend)',
                            data: fullDateRange.map(d => {
                                const point = trendLinear.find(t => t.date === d);
                                return point ? point.value : null;
                            }),
                            borderColor: config.color,
                            borderDash: [5, 5],
                            borderWidth: 2,
                            pointRadius: 0,
                            fill: false,
                            yAxisID: config.axis,
                            tension: 0.4
                        });
                    }

                    if (trendSma.length) {
                        datasets.push({
                            label: config.label + ' (SMA 28)',
                            data: fullDateRange.map(d => {
                                const point = trendSma.find(t => t.date === d);
                                return point ? point.value : null;
                            }),
                            borderColor: config.color,
                            borderDash: [2, 2],
                            borderWidth: 1,
                            pointRadius: 0,
                            fill: false,
                            yAxisID: config.axis,
                            tension: 0.4
                        });
                    }
                });
            }

            let gridDrawn = false;
            const cssGridColor = getComputedStyle(document.documentElement).getPropertyValue('--mailchimp-chart-grid').trim();
            const cssTicksColor = getComputedStyle(document.documentElement).getPropertyValue('--mailchimp-chart-ticks').trim();

            chart.options.scales.x.grid.color = cssGridColor;
            chart.options.scales.x.ticks.color = cssTicksColor;

            Object.entries(MAILCHIMP_METRICS).forEach(([key, config]) => {
                const scaleId = config.axis;
                if (!chart.options.scales[scaleId]) return;
                chart.options.scales[scaleId].display = this.activeMetrics[key];
                if (this.activeMetrics[key]) {
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

        get maxSends() {
            if (!this.tableState.sortedRows.length) return 1;
            return Math.max(...this.tableState.sortedRows.map(r => Number(r.sends) || 0)) || 1;
        },

        get maxOpens() {
            if (!this.tableState.sortedRows.length) return 1;
            return Math.max(...this.tableState.sortedRows.map(r => Number(r.opens) || 0)) || 1;
        },

        get maxClicks() {
            if (!this.tableState.sortedRows.length) return 1;
            return Math.max(...this.tableState.sortedRows.map(r => Number(r.clicks) || 0)) || 1;
        },

        get maxClicksUnique() {
            if (!this.tableState.sortedRows.length) return 1;
            return Math.max(...this.tableState.sortedRows.map(r => Number(r.clicks_unique) || 0)) || 1;
        },

        get maxUnsubscribes() {
            if (!this.tableState.sortedRows.length) return 1;
            return Math.max(...this.tableState.sortedRows.map(r => Number(r.unsubscribes) || 0)) || 1;
        },

        get maxBounces() {
            if (!this.tableState.sortedRows.length) return 1;
            return Math.max(...this.tableState.sortedRows.map(r => Number(r.bounces) || 0)) || 1;
        },

        formatNumber(num) {
            if (num === undefined || num === null) return '0';
            return new Intl.NumberFormat('en-US').format(num);
        },

        formatPercent(num) {
            if (num === undefined || num === null) return '0%';
            return (num * 100).toFixed(2) + '%';
        },

        formatDecimals(num) {
            if (num === undefined || num === null) return '0.00';
            return Number(num).toFixed(2);
        }
    };
}

if (typeof window !== 'undefined') {
    window.mailchimpDashboard = mailchimpDashboard;
}
