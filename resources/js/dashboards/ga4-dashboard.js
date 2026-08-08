import { dataTable } from './data-table';

export function ga4Dashboard(config = {}) {
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

        summary: { sessions: 0, activeUsers: 0, newUsers: 0, conversions: 0, screenPageViews: 0, averageSessionDuration: 0, totalUsers: 0, bounceRate: 0, revenue: 0 },
        previous: { sessions: 0, activeUsers: 0, newUsers: 0, conversions: 0, screenPageViews: 0, averageSessionDuration: 0, totalUsers: 0, bounceRate: 0, revenue: 0 },
        chartDataRaw: [],

        activeMetrics: {
            sessions: true,
            activeUsers: true,
            newUsers: false,
            conversions: false,
            screenPageViews: false,
            averageSessionDuration: false,
            bounceRate: false,
            totalUsers: false,
            revenue: false,
        },

        sections: {
            campaigns: dataTable({ sortCol: 'sessions', sortDir: 'desc', searchKeys: ['name', 'id'] }),
            channels: dataTable({ sortCol: 'sessions', sortDir: 'desc', searchKeys: ['name', 'id'] }),
            traffic: dataTable({ sortCol: 'sessions', sortDir: 'desc', searchKeys: ['name', 'id'] }),
            acquisition: dataTable({ sortCol: 'newUsers', sortDir: 'desc', searchKeys: ['name', 'id'] }),
            events: dataTable({ sortCol: 'eventCount', sortDir: 'desc', searchKeys: ['name', 'id'] }),
            adtouchpoints: dataTable({ sortCol: 'sessions', sortDir: 'desc', searchKeys: ['name', 'id'] }),
        },
        sl: {
            campaigns: false, channels: false, traffic: false,
            acquisition: false, events: false, adtouchpoints: false,
        },
        ss: {
            campaigns: 'campaigns', channels: 'channels', traffic: 'traffic_pages',
            acquisition: 'acquisition_channels', events: 'events',
            adtouchpoints: 'adtouchpoints_adgroups',
        },

        sectionConfig: {
            campaigns: { label: 'Campaigns', tabs: ['campaigns', 'adgroups'] },
            channels: { label: 'Channels', tabs: ['channels', 'sources'] },
            traffic: { label: 'Traffic', tabs: ['traffic_pages', 'traffic_countries', 'traffic_devices'] },
            acquisition: { label: 'Acquisition', tabs: ['acquisition_channels'] },
            events: { label: 'Events', tabs: ['events'] },
            adtouchpoints: { label: 'Ad Touchpoints', tabs: ['adtouchpoints_adgroups', 'adtouchpoints_terms', 'adtouchpoints_content'] },
        },

        tabConfig: {
            campaigns: { label: 'Campaign', metrics: ['sessions', 'activeUsers', 'newUsers', 'conversions', 'screenPageViews', 'averageSessionDuration', 'totalUsers', 'revenue'] },
            adgroups: { label: 'Ad Group', metrics: ['sessions', 'activeUsers', 'newUsers', 'conversions', 'screenPageViews', 'averageSessionDuration', 'totalUsers', 'revenue'] },
            channels: { label: 'Channel', metrics: ['sessions', 'activeUsers', 'newUsers', 'conversions', 'screenPageViews', 'averageSessionDuration', 'totalUsers', 'revenue'] },
            sources: { label: 'Source/Medium', metrics: ['sessions', 'activeUsers', 'newUsers', 'conversions', 'screenPageViews', 'averageSessionDuration', 'totalUsers', 'revenue'] },
            traffic_pages: { label: 'Landing Page', metrics: ['sessions', 'screenPageViews', 'bounceRate', 'averageSessionDuration', 'conversions', 'revenue'] },
            traffic_countries: { label: 'Country', metrics: ['sessions', 'screenPageViews', 'bounceRate', 'averageSessionDuration', 'conversions', 'revenue'] },
            traffic_devices: { label: 'Device', metrics: ['sessions', 'screenPageViews', 'bounceRate', 'averageSessionDuration', 'conversions', 'revenue'] },
            acquisition_channels: { label: 'Acq. Channel', metrics: ['newUsers', 'activeUsers', 'totalUsers', 'revenue'] },
            events: { label: 'Event Name', metrics: ['eventCount', 'conversions'] },
            adtouchpoints_adgroups: { label: 'Ad Group', metrics: ['sessions', 'conversions', 'revenue'] },
            adtouchpoints_terms: { label: 'Manual Term', metrics: ['sessions', 'conversions', 'revenue'] },
            adtouchpoints_content: { label: 'Ad Content', metrics: ['sessions', 'conversions', 'revenue'] },
        },
        metricLabels: {
            sessions: 'Sessions', activeUsers: 'Users', newUsers: 'New',
            conversions: 'Conv.', screenPageViews: 'Page Views',
            bounceRate: 'Bounce Rate', eventCount: 'Event Count',
            averageSessionDuration: 'Avg. Duration', totalUsers: 'Total Users'
        },
        metricColors: {
            sessions: 'var(--ga4-sessions)', activeUsers: 'var(--ga4-activeUsers)',
            newUsers: 'var(--ga4-newUsers)', conversions: 'var(--ga4-conversions)',
            screenPageViews: 'var(--ga4-pageViews)', bounceRate: 'var(--ga4-revenue)',
            eventCount: '#8B5CF6', averageSessionDuration: 'var(--ga4-avgSessionDuration)',
            totalUsers: 'var(--ga4-totalUsers)'
        },

        get isAnySectionLoading() {
            return Object.values(this.sl).some(v => v);
        },

        get sectionPageSizes() {
            return Object.values(this.sections).map(s => s.pageSize).join(',');
        },

        get tabLabel() {
            return this.tabConfig[this.activeTab]?.label || this.activeTab;
        },

        restoreFromUrl() {
            const params = new URLSearchParams(window.location.search);
            const accParam = params.get('account');
            if (accParam && this.accountNames[accParam]) {
                this.account = accParam;
                this.selectedAccount = accParam;
                if (this.$wire) this.$wire.set('selectedAccount', accParam);
            }
            if (!this.selectedAccount && Object.keys(this.accountNames).length > 0) {
                const firstAcc = Object.keys(this.accountNames)[0];
                this.account = firstAcc;
                this.selectedAccount = firstAcc;
                if (this.$wire) this.$wire.set('selectedAccount', firstAcc);
            }
            const ds = params.get('dateStart');
            if (ds && /^\d{4}-\d{2}-\d{2}$/.test(ds)) this.dateStart = ds;
            const de = params.get('dateEnd');
            if (de && /^\d{4}-\d{2}-\d{2}$/.test(de)) this.dateEnd = de;
            const tab = params.get('tab');
            if (tab) this.activeTab = tab;
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
                    this.fetchAll();
                });

                this.$watch('dateStart', () => {
                    this.syncToUrl();
                    this.fetchAll();
                });

                this.$watch('dateEnd', () => {
                    this.syncToUrl();
                    this.fetchAll();
                });

                this.$watch('sectionPageSizes', () => {
                    Object.keys(this.sections).forEach(k => this.sections[k].currentPage = 1);
                });

                if (this.account && this.dateStart && this.dateEnd) {
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

        setSectionSubTab(section, tab) {
            this.ss[section] = tab;
            const state = this.sections[section];
            state.currentPage = 1;
            state.searchQuery = '';
            const metrics = this.tabConfig[tab]?.metrics;
            state.sortCol = (metrics && metrics.length) ? metrics[0] : 'sessions';
            state.sortDir = 'desc';
            this.activeTab = tab;
            this.syncToUrl();
            this.fetchSection(section);
            if (this.$wire) this.$wire.setActiveTab(tab);
        },

        forceRefresh() {
            this.clearCache();
            this.fetchAll();
        },

        clearCache() {
            const prefix = `ga4_${this.tenantId}_${this.account}_${this.dateStart}_${this.dateEnd}`;
            Object.keys(sessionStorage).forEach(key => {
                if (key.startsWith(prefix)) {
                    sessionStorage.removeItem(key);
                }
            });
        },

        getCacheKey(endpoint, section = null) {
            const tab = section ? this.ss[section] : '';
            return `ga4_${this.tenantId}_${this.account}_${this.dateStart}_${this.dateEnd}_${endpoint}_${tab}`;
        },

        safeCacheSet(key, value) {
            try {
                sessionStorage.setItem(key, value);
                return true;
            } catch (e) {
                if (e.name === 'QuotaExceededError' || e.code === 22) {
                    Object.keys(sessionStorage).forEach(k => {
                        if (k.startsWith('ga4_') || k.startsWith('gsc_') || k.startsWith('fbm_')) {
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

        async fetchAll() {
            if (!this.account || !this.dateStart || !this.dateEnd) return;
            this.fetchSummary();
            this.fetchChart();
            Object.keys(this.sectionConfig).forEach(s => this.fetchSection(s));
        },

        getFetchOptions(section = null) {
            const body = {
                tenant: this.tenantId,
                account: this.account,
                dateStart: this.dateStart,
                dateEnd: this.dateEnd,
            };
            if (section) {
                body.activeTab = this.ss[section];
            }
            return {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken
                },
                body: JSON.stringify(body)
            };
        },

        normalizeSummaryMetrics(obj) {
            if (!obj) return { sessions: 0, activeUsers: 0, newUsers: 0, conversions: 0, screenPageViews: 0, averageSessionDuration: 0, totalUsers: 0 };
            return {
                sessions: obj.sessions ?? 0,
                activeUsers: obj.activeUsers ?? obj.active_users ?? 0,
                newUsers: obj.newUsers ?? obj.new_users ?? 0,
                conversions: obj.conversions ?? 0,
                screenPageViews: obj.screenPageViews ?? obj.pageviews ?? 0,
                averageSessionDuration: obj.averageSessionDuration ?? obj.average_session_duration ?? 0,
                totalUsers: obj.totalUsers ?? obj.total_users ?? 0,
                bounceRate: obj.bounceRate ?? obj.bounce_rate ?? 0,
                events: obj.events ?? 0,
            };
        },

        async fetchSummary() {
            if (!this.account || !this.dateStart || !this.dateEnd) return;
            const cacheKey = this.getCacheKey('summary');

            if (sessionStorage.getItem(cacheKey)) {
                const data = JSON.parse(sessionStorage.getItem(cacheKey));
                this.summary = this.normalizeSummaryMetrics(data.summary);
                this.previous = this.normalizeSummaryMetrics(data.previous);
                return;
            }

            this.isSummaryLoading = true;
            try {
                const response = await fetch('/api/ga4/summary', this.getFetchOptions());
                const data = await response.json();
                if (!data.error) {
                    this.safeCacheSet(cacheKey, JSON.stringify(data));
                    this.summary = this.normalizeSummaryMetrics(data.summary);
                    this.previous = this.normalizeSummaryMetrics(data.previous);
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
                this.updateChart();
                return;
            }

            this.isChartLoading = true;
            try {
                const response = await fetch('/api/ga4/chart', this.getFetchOptions());
                const data = await response.json();
                if (!data.error) {
                    this.safeCacheSet(cacheKey, JSON.stringify(data));
                    this.chartDataRaw = data.chart || [];
                    this.updateChart();
                }
            } catch (error) {
                console.error('Error fetching chart:', error);
            } finally {
                this.isChartLoading = false;
            }
        },

        async fetchSection(section) {
            if (!this.account || !this.dateStart || !this.dateEnd) return;
            const cacheKey = this.getCacheKey('table', section);

            if (sessionStorage.getItem(cacheKey)) {
                const data = JSON.parse(sessionStorage.getItem(cacheKey));
                this.sections[section].rows = data.table || [];
                return;
            }

            this.sl[section] = true;
            try {
                const response = await fetch('/api/ga4/table', this.getFetchOptions(section));
                const data = await response.json();
                if (!data.error) {
                    this.safeCacheSet(cacheKey, JSON.stringify(data));
                    this.sections[section].rows = data.table || [];
                    this.sections[section].currentPage = 1;
                }
            } catch (error) {
                console.error('Error fetching section ' + section + ':', error);
            } finally {
                this.sl[section] = false;
            }
        },

        get variance() {
            const calc = (current, prev) => {
                if (!prev || Number(prev) === 0) return 0;
                return ((Number(current) - Number(prev)) / Number(prev)) * 100;
            };
            return {
                sessions: calc(this.summary.sessions, this.previous.sessions),
                activeUsers: calc(this.summary.activeUsers, this.previous.activeUsers),
                newUsers: calc(this.summary.newUsers, this.previous.newUsers),
                conversions: calc(this.summary.conversions, this.previous.conversions),
                screenPageViews: calc(this.summary.screenPageViews, this.previous.screenPageViews),
                averageSessionDuration: calc(this.summary.averageSessionDuration, this.previous.averageSessionDuration),
                bounceRate: calc(this.summary.bounceRate, this.previous.bounceRate),
                totalUsers: calc(this.summary.totalUsers, this.previous.totalUsers),
                revenue: calc(this.summary.revenue, this.previous.revenue),
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
                                    if (label === 'Bounce Rate') {
                                        return label + ': ' + (Number(value) * (value <= 1 ? 100 : 1)).toFixed(2) + '%';
                                    }
                                    if (label === 'Avg Duration') {
                                        var mins = Math.floor(value / 60);
                                        var secs = Math.floor(value % 60);
                                        return label + ': ' + String(mins).padStart(2, '0') + ':' + String(secs).padStart(2, '0');
                                    }
                                    return label + ': ' + Number(value).toLocaleString('en-US');
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: { color: 'var(--ga4-chart-grid)', drawBorder: false },
                            ticks: { color: 'var(--ga4-chart-ticks)' }
                        },
                        ySessions: {
                            type: 'linear', position: 'left', display: false,
                            grid: { color: 'var(--ga4-chart-grid)', drawBorder: false },
                            ticks: { color: '#4285F4' },
                            min: 0, suggestedMax: 5
                        },
                        yActiveUsers: {
                            type: 'linear', position: 'right', display: false,
                            grid: { drawOnChartArea: false, drawBorder: false },
                            ticks: { color: '#0F9D58' }
                        },
                        yNewUsers: {
                            type: 'linear', position: 'left', display: false,
                            grid: { drawOnChartArea: false, drawBorder: false },
                            ticks: { color: '#FBBC04' }
                        },
                        yScreenPageViews: {
                            type: 'linear', position: 'left', display: false,
                            grid: { drawOnChartArea: false, drawBorder: false },
                            ticks: { color: '#a855f7' }
                        },
                        yConversions: {
                            type: 'linear', position: 'right', display: false,
                            grid: { drawOnChartArea: false, drawBorder: false },
                            ticks: { color: '#EA4335' }
                        },
                        yAverageSessionDuration: {
                            type: 'linear', position: 'left', display: false,
                            grid: { drawOnChartArea: false, drawBorder: false },
                            ticks: { color: '#06b6d4' }
                        },
                        yBounceRate: {
                            type: 'linear', position: 'right', display: false,
                            grid: { drawOnChartArea: false, drawBorder: false },
                            ticks: {
                                color: '#ec4899',
                                callback: function(v) { return (v * (v <= 1 ? 100 : 1)).toFixed(0) + '%'; }
                            }
                        },
                        yTotalUsers: {
                            type: 'linear', position: 'left', display: false,
                            grid: { drawOnChartArea: false, drawBorder: false },
                            ticks: { color: '#6366f1' }
                        },
                        yRevenue: {
                            type: 'linear', position: 'right', display: false,
                            grid: { drawOnChartArea: false, drawBorder: false },
                            ticks: { color: '#10b981' }
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
                if (r && (r.daily || r.date || r.metric_date)) {
                    const dateStr = dayjs(r.daily || r.date || r.metric_date).format('YYYY-MM-DD');
                    dataByDate[dateStr] = r;
                }
            });

            const paddedData = fullDateRange.map(dateStr => {
                if (dataByDate[dateStr]) return dataByDate[dateStr];
                return {
                    daily: dateStr,
                    sessions: 0, activeUsers: 0, newUsers: 0,
                    conversions: 0, screenPageViews: 0, bounceRate: 0,
                    averageSessionDuration: 0, totalUsers: 0, revenue: 0
                };
            });

            const labels = paddedData.map(r => dayjs(r.daily || r.date || r.metric_date).format('MMM D'));
            const datasets = [];
            const chartData = paddedData;

            if (this.activeMetrics.sessions) {
                datasets.push({
                    label: 'Sessions',
                    data: chartData.map(r => r.sessions),
                    borderColor: '#4285F4',
                    backgroundColor: 'rgba(66, 133, 244, 0.1)',
                    borderWidth: 2,
                    pointRadius: 0,
                    pointHoverRadius: 6,
                    fill: true,
                    yAxisID: 'ySessions',
                    tension: 0.4
                });
            }

            if (this.activeMetrics.activeUsers) {
                datasets.push({
                    label: 'Active Users',
                    data: chartData.map(r => r.activeUsers),
                    borderColor: '#0F9D58',
                    backgroundColor: 'rgba(15, 157, 88, 0.1)',
                    borderWidth: 2,
                    pointRadius: 0,
                    pointHoverRadius: 6,
                    fill: true,
                    yAxisID: 'yActiveUsers',
                    tension: 0.4
                });
            }

            if (this.activeMetrics.newUsers) {
                datasets.push({
                    label: 'New Users',
                    data: chartData.map(r => r.newUsers),
                    borderColor: '#FBBC04',
                    borderWidth: 2,
                    pointRadius: 0,
                    pointHoverRadius: 6,
                    fill: false,
                    yAxisID: 'yNewUsers',
                    tension: 0.4
                });
            }

            if (this.activeMetrics.screenPageViews) {
                datasets.push({
                    label: 'Pageviews',
                    data: chartData.map(r => r.screenPageViews),
                    borderColor: '#a855f7',
                    borderWidth: 2,
                    pointRadius: 0,
                    pointHoverRadius: 6,
                    fill: false,
                    yAxisID: 'yScreenPageViews',
                    tension: 0.4
                });
            }

            if (this.activeMetrics.conversions) {
                datasets.push({
                    label: 'Conversions',
                    data: chartData.map(r => r.conversions),
                    borderColor: '#EA4335',
                    borderWidth: 2,
                    pointRadius: 0,
                    pointHoverRadius: 6,
                    fill: false,
                    yAxisID: 'yConversions',
                    tension: 0.4
                });
            }

            if (this.activeMetrics.averageSessionDuration) {
                datasets.push({
                    label: 'Avg Duration',
                    data: chartData.map(r => r.averageSessionDuration),
                    borderColor: '#06b6d4',
                    borderWidth: 2,
                    pointRadius: 0,
                    pointHoverRadius: 6,
                    fill: false,
                    yAxisID: 'yAverageSessionDuration',
                    tension: 0.4
                });
            }

            if (this.activeMetrics.bounceRate) {
                datasets.push({
                    label: 'Bounce Rate',
                    data: chartData.map(r => r.bounceRate),
                    borderColor: '#ec4899',
                    borderWidth: 2,
                    pointRadius: 0,
                    pointHoverRadius: 6,
                    fill: false,
                    yAxisID: 'yBounceRate',
                    tension: 0.4
                });
            }

            if (this.activeMetrics.totalUsers) {
                datasets.push({
                    label: 'Total Users',
                    data: chartData.map(r => r.totalUsers),
                    borderColor: '#6366f1',
                    borderWidth: 2,
                    pointRadius: 0,
                    pointHoverRadius: 6,
                    fill: false,
                    yAxisID: 'yTotalUsers',
                    tension: 0.4
                });
            }

            if (this.activeMetrics.revenue) {
                datasets.push({
                    label: 'Revenue',
                    data: chartData.map(r => r.revenue),
                    borderColor: '#10b981',
                    borderWidth: 2,
                    pointRadius: 0,
                    pointHoverRadius: 6,
                    fill: false,
                    yAxisID: 'yRevenue',
                    tension: 0.4
                });
            }

            let gridDrawn = false;
            const cssGridColor = getComputedStyle(document.documentElement).getPropertyValue('--ga4-chart-grid').trim();
            const cssTicksColor = getComputedStyle(document.documentElement).getPropertyValue('--ga4-chart-ticks').trim();

            chart.options.scales.x.grid.color = cssGridColor;
            chart.options.scales.x.ticks.color = cssTicksColor;

            ['sessions', 'activeUsers', 'newUsers', 'screenPageViews', 'conversions', 'averageSessionDuration', 'bounceRate', 'totalUsers', 'revenue'].forEach(m => {
                let scaleId = 'y' + m.charAt(0).toUpperCase() + m.slice(1);
                if (chart.options.scales[scaleId]) {
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
                }
            });

            chart.data.labels = labels;
            chart.data.datasets = datasets;
            chart.update();
        },

        sectionMaxMetric(section, metric) {
            const data = this.sections[section]?.sortedRows || [];
            if (!data.length) return 1;
            return Math.max(...data.map(r => r[metric] || 0));
        },

        tabForSection(section) {
            const config = this.sectionConfig[section];
            if (!config) return [];
            return config.tabs.map(t => ({
                key: t,
                label: this.tabConfig[t]?.label || t,
                active: this.ss[section] === t
            }));
        },

        formatNumber(num) {
            if (num === undefined || num === null) return '0';
            return new Intl.NumberFormat('en-US').format(num);
        },

        formatMetricValue(key, value) {
            if (key === 'averageSessionDuration') {
                return this.formatDuration(value);
            }
            if (key === 'bounceRate') {
                return this.formatPercent(value);
            }
            return this.formatNumber(value);
        },

        formatDuration(seconds) {
            if (!seconds || seconds < 0) return '00:00';
            const totalSeconds = Math.floor(seconds);
            const hours = Math.floor(totalSeconds / 3600);
            const minutes = Math.floor((totalSeconds % 3600) / 60);
            const secs = totalSeconds % 60;
            const paddedMinutes = String(minutes).padStart(2, '0');
            const paddedSeconds = String(secs).padStart(2, '0');
            if (hours > 0) {
                return `${String(hours).padStart(2, '0')}:${paddedMinutes}:${paddedSeconds}`;
            }
            return `${paddedMinutes}:${paddedSeconds}`;
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
    window.ga4Dashboard = ga4Dashboard;
}
