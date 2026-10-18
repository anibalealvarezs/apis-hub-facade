import { dataTable } from './data-table';

export function googleAdsDashboard(config = {}) {
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
            conversions: 0,
            conversion_value: 0,
            ctr: 0,
            cpc: 0,
            roas_purchase: 0,
            cost_per_conversion: 0,
            conversion_rate: 0
        },
        previous: {
            spend: 0,
            clicks: 0,
            impressions: 0,
            conversions: 0,
            conversion_value: 0,
            ctr: 0,
            cpc: 0,
            roas_purchase: 0,
            cost_per_conversion: 0,
            conversion_rate: 0
        },
        chartDataRaw: [],
        tableState: dataTable({ sortCol: 'spend', sortDir: 'desc', searchKeys: ['name'] }),

        activeMetrics: {
            spend: true,
            clicks: true,
            impressions: true,
            conversions: false,
            conversion_value: false,
            ctr: false,
            cpc: false,
            roas_purchase: false,
            cost_per_conversion: false,
            conversion_rate: false
        },

        activeFilters: { campaigns: [], adgroups: [], ads: [], devices: [], search_terms: [] },
        filterLabels: {},

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
            if (tab && ['campaigns', 'adgroups', 'ads', 'devices', 'search_terms'].includes(tab)) this.activeTab = tab;
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

        get variance() {
            const calculate = (curr, prev) => {
                const c = parseFloat(curr) || 0;
                const p = parseFloat(prev) || 0;
                if (p === 0) return c > 0 ? 100 : 0;
                return ((c - p) / p) * 100;
            };

            return {
                spend: calculate(this.summary.spend, this.previous.spend),
                clicks: calculate(this.summary.clicks, this.previous.clicks),
                impressions: calculate(this.summary.impressions, this.previous.impressions),
                conversions: calculate(this.summary.conversions, this.previous.conversions),
                conversion_value: calculate(this.summary.conversion_value, this.previous.conversion_value),
                ctr: calculate(this.summary.ctr, this.previous.ctr),
                cpc: calculate(this.summary.cpc, this.previous.cpc),
                roas_purchase: calculate(this.summary.roas_purchase, this.previous.roas_purchase),
                cost_per_conversion: calculate(this.summary.cost_per_conversion, this.previous.cost_per_conversion),
                conversion_rate: calculate(this.summary.conversion_rate, this.previous.conversion_rate)
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

        initDashboard() {
            this.restoreFromUrl();

            const boot = () => {
                this.initChart();

                this.$watch('accounts', (val) => {
                    if (val && val.length !== 1) {
                        this.activeFilters.campaigns = [];
                        this.activeFilters.adgroups = [];
                        this.activeFilters.ads = [];
                        this.saveFilters();
                    }
                    this.syncToUrl();
                    this.fetchAll();
                });
                this.$watch('activeFilters.campaigns', (val) => {
                    if (val && val.length !== 1) {
                        this.activeFilters.adgroups = [];
                        this.activeFilters.ads = [];
                        this.saveFilters();
                    }
                });
                this.$watch('activeFilters.adgroups', (val) => {
                    if (val && val.length !== 1) {
                        this.activeFilters.ads = [];
                        this.saveFilters();
                    }
                });
                this.$watch('dateStart', () => { this.syncToUrl(); this.fetchAll(); });
                this.$watch('dateEnd', () => { this.syncToUrl(); this.fetchAll(); });
                this.$watch('activeTab', () => {
                    this.tableState.currentPage = 1;
                    this.tableState.searchQuery = '';
                    this.syncToUrl();
                    this.fetchTable();
                });
                this.$watch('tableState.pageSize', () => {
                    this.tableState.currentPage = 1;
                });

                if (this.accounts.length > 0 && this.dateStart && this.dateEnd) {
                    this.loadSavedFilters();
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
                    console.error("Failed to load charting libraries for Google Ads", err);
                });
            }
        },

        loadSavedFilters() {
            if (!this.accounts.length) return;
            const accountKey = this.accounts.join('_');
            const saved = sessionStorage.getItem(`gads_filters_${this.tenantId}_${accountKey}`);
            const savedLabels = sessionStorage.getItem(`gads_labels_${this.tenantId}_${accountKey}`);
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
            this.safeCacheSet(`gads_filters_${this.tenantId}_${accountKey}`, JSON.stringify(this.activeFilters));
            this.safeCacheSet(`gads_labels_${this.tenantId}_${accountKey}`, JSON.stringify(this.filterLabels));
        },

        clearFiltersLocal() {
            this.activeFilters = { campaigns: [], adgroups: [], ads: [], devices: [], search_terms: [] };
            this.filterLabels = {};
        },

        clearFilters() {
            this.clearFiltersLocal();
            this.saveFilters();
            this.fetchSummary();
            this.fetchChart();
            this.fetchTable();
        },

        canFilter(tab) {
            if (tab === 'devices') return true;
            if (tab === 'campaigns') return this.accounts.length === 1;
            if (tab === 'adgroups') return this.accounts.length === 1 && this.activeFilters.campaigns.length === 1;
            if (tab === 'ads') return this.accounts.length === 1 && this.activeFilters.campaigns.length === 1 && this.activeFilters.adgroups.length === 1;
            if (tab === 'search_terms') return false;
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
            this.fetchAll();
        },

        safeCacheSet(key, value) {
            try {
                sessionStorage.setItem(key, value);
                return true;
            } catch (e) {
                return false;
            }
        },

        clearCache() {
            Object.keys(sessionStorage).forEach(k => {
                if (k.startsWith('gads_')) {
                    sessionStorage.removeItem(k);
                }
            });
        },

        getTableFilters() {
            const filters = JSON.parse(JSON.stringify(this.activeFilters));
            if (this.activeTab === 'campaigns') {
                filters.campaigns = [];
                filters.adgroups = [];
                filters.ads = [];
                filters.search_terms = [];
            } else if (this.activeTab === 'adgroups') {
                filters.adgroups = [];
                filters.ads = [];
                filters.search_terms = [];
            } else if (this.activeTab === 'ads') {
                filters.ads = [];
                filters.search_terms = [];
            } else if (this.activeTab === 'search_terms') {
                filters.search_terms = [];
            }
            return filters;
        },

        getCacheKey(endpoint, filterMode = 'all') {
            const accountKey = [...this.accounts].sort().join('_');
            let filtersObj = {};
            if (filterMode === 'all') filtersObj = this.activeFilters;
            else if (filterMode === 'table') filtersObj = this.getTableFilters();

            const filterHash = filterMode === 'none' ? 'no_filters' : JSON.stringify(filtersObj);
            return `gads_${this.tenantId}_${accountKey}_${this.dateStart}_${this.dateEnd}_${endpoint}_${this.activeTab}_${filterHash}`;
        },

        getFetchOptions(filterMode = 'all', extra = {}) {
            let activeFilters = this.activeFilters;
            if (filterMode === 'table') {
                activeFilters = this.getTableFilters();
            } else if (filterMode === 'none') {
                activeFilters = {};
            }

            return {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    tenant: this.tenantId,
                    account: this.accounts,
                    dateStart: this.dateStart,
                    dateEnd: this.dateEnd,
                    activeTab: this.activeTab,
                    activeFilters: activeFilters,
                    ...extra
                })
            };
        },

        async fetchAll() {
            if (!this.accounts.length || !this.dateStart || !this.dateEnd) return;
            this.fetchSummary();
            this.fetchChart();
            this.fetchTable();
        },

        async fetchSummary() {
            if (!this.accounts.length || !this.dateStart || !this.dateEnd) return;
            const cacheKey = this.getCacheKey('summary', 'all');

            if (sessionStorage.getItem(cacheKey)) {
                const data = JSON.parse(sessionStorage.getItem(cacheKey));
                this.summary = data.summary || {};
                this.previous = data.previous || {};
                return;
            }

            this.isSummaryLoading = true;
            try {
                const response = await fetch('/api/google-ads/summary', this.getFetchOptions('all'));
                const data = await response.json();
                if (!data.error) {
                    this.safeCacheSet(cacheKey, JSON.stringify(data));
                    this.summary = data.summary || {};
                    this.previous = data.previous || {};
                }
            } catch (e) {
                console.error('Google Ads summary error:', e);
            } finally {
                this.isSummaryLoading = false;
            }
        },

        async fetchChart() {
            if (!this.accounts.length || !this.dateStart || !this.dateEnd) return;
            const cacheKey = this.getCacheKey('chart', 'all');

            if (sessionStorage.getItem(cacheKey)) {
                const data = JSON.parse(sessionStorage.getItem(cacheKey));
                this.chartDataRaw = data.chart || [];
                this.updateChart();
                return;
            }

            this.isChartLoading = true;
            try {
                const response = await fetch('/api/google-ads/chart', this.getFetchOptions('all'));
                const data = await response.json();
                if (!data.error) {
                    this.safeCacheSet(cacheKey, JSON.stringify(data));
                    this.chartDataRaw = data.chart || [];
                    this.updateChart();
                }
            } catch (e) {
                console.error('Google Ads chart error:', e);
            } finally {
                this.isChartLoading = false;
            }
        },

        async fetchTable() {
            if (!this.accounts.length || !this.dateStart || !this.dateEnd) return;
            const cacheKey = this.getCacheKey('table', 'table');

            if (sessionStorage.getItem(cacheKey)) {
                const data = JSON.parse(sessionStorage.getItem(cacheKey));
                this.tableState.rows = data || [];
                return;
            }

            this.isTableLoading = true;
            try {
                const response = await fetch('/api/google-ads/table', this.getFetchOptions('table'));
                const data = await response.json();
                if (!data.error) {
                    const rows = (data.data || []).map(r => {
                        const name = r.name || r.channeledcampaign || r.channeledCampaign || r.adgroup || r.adGroup || r.ad || r.device || r.id || 'Unknown';
                        const id = r.id || r.channeledcampaign_id || r.channeledCampaign_id || r.adgroup_id || r.adGroup_id || r.ad_id || r.device_id || name;
                        return {
                            ...r,
                            id: id,
                            name: name,
                        spend: parseFloat(r.spend) || 0,
                        clicks: parseInt(r.clicks) || 0,
                        impressions: parseInt(r.impressions) || 0,
                        conversions: parseFloat(r.conversions) || 0,
                        conversion_value: parseFloat(r.conversion_value) || 0,
                        ctr: parseFloat(r.ctr) || 0,
                        cpc: parseFloat(r.cpc) || 0,
                        roas_purchase: parseFloat(r.roas_purchase) || 0,
                        cost_per_conversion: parseFloat(r.cost_per_conversion) || 0,
                        conversion_rate: parseFloat(r.conversion_rate) || 0,
                    };
                });
                    this.safeCacheSet(cacheKey, JSON.stringify(rows));
                    this.tableState.rows = rows;
                    this.tableState.currentPage = 1;
                }
            } catch (e) {
                console.error('Google Ads table error:', e);
            } finally {
                this.isTableLoading = false;
            }
        },

        initChart() {
            if (!this.$refs.canvas || typeof Chart === 'undefined') return;
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
                                    const label = context.dataset.label || '';
                                    const value = context.parsed.y;
                                    if (['Cost / Spend', 'Avg. CPC', 'Cost / Conv.', 'Conv. Value'].includes(label)) {
                                        return label + ': $' + Number(value).toFixed(2);
                                    }
                                    if (['CTR', 'Conv. Rate'].includes(label)) {
                                        return label + ': ' + Number(value).toFixed(2) + '%';
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
            const chart = this.$refs.canvas._chartInstance;
            if (!chart || !this.chartDataRaw || typeof dayjs === 'undefined') return;

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
                    spend: 0,
                    clicks: 0,
                    impressions: 0,
                    conversions: 0,
                    conversion_value: 0,
                    ctr: 0,
                    cpc: 0,
                    roas_purchase: 0,
                    cost_per_conversion: 0,
                    conversion_rate: 0
                };
            });

            const labels = paddedData.map(r => dayjs(r.daily || r.date).format('MMM D'));
            const datasets = [];

            if (this.activeMetrics.spend) {
                datasets.push({
                    label: 'Cost / Spend',
                    data: paddedData.map(r => parseFloat(r.spend) || 0),
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

            if (this.activeMetrics.clicks) {
                datasets.push({
                    label: 'Clicks',
                    data: paddedData.map(r => parseInt(r.clicks) || 0),
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

            if (this.activeMetrics.impressions) {
                datasets.push({
                    label: 'Impressions',
                    data: paddedData.map(r => parseInt(r.impressions) || 0),
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

            if (this.activeMetrics.conversions) {
                datasets.push({
                    label: 'Conversions',
                    data: paddedData.map(r => parseFloat(r.conversions) || 0),
                    borderColor: '#14B8A6',
                    backgroundColor: 'rgba(20, 184, 166, 0.1)',
                    borderWidth: 2,
                    pointRadius: 0,
                    pointHoverRadius: 6,
                    fill: true,
                    yAxisID: 'yConversions',
                    tension: 0.4
                });
            }

            if (this.activeMetrics.conversion_value) {
                datasets.push({
                    label: 'Conv. Value',
                    data: paddedData.map(r => parseFloat(r.conversion_value) || 0),
                    borderColor: '#10B981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    borderWidth: 2,
                    pointRadius: 0,
                    pointHoverRadius: 6,
                    fill: false,
                    yAxisID: 'yConversionValue',
                    tension: 0.4
                });
            }

            if (this.activeMetrics.ctr) {
                datasets.push({
                    label: 'CTR',
                    data: paddedData.map(r => (parseFloat(r.ctr) || 0) * 100),
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
                    label: 'Avg. CPC',
                    data: paddedData.map(r => parseFloat(r.cpc) || 0),
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

            if (this.activeMetrics.cost_per_conversion) {
                datasets.push({
                    label: 'Cost / Conv.',
                    data: paddedData.map(r => parseFloat(r.cost_per_conversion) || 0),
                    borderColor: '#A855F7',
                    backgroundColor: 'rgba(168, 85, 247, 0.1)',
                    borderWidth: 2,
                    pointRadius: 0,
                    pointHoverRadius: 6,
                    fill: false,
                    yAxisID: 'yCostPerConversion',
                    tension: 0.4
                });
            }

            if (this.activeMetrics.conversion_rate) {
                datasets.push({
                    label: 'Conv. Rate',
                    data: paddedData.map(r => (parseFloat(r.conversion_rate) || 0) * 100),
                    borderColor: '#EF4444',
                    backgroundColor: 'rgba(239, 68, 68, 0.1)',
                    borderWidth: 2,
                    pointRadius: 0,
                    pointHoverRadius: 6,
                    fill: false,
                    yAxisID: 'yConversionRate',
                    tension: 0.4
                });
            }

            if (this.activeMetrics.roas_purchase) {
                datasets.push({
                    label: 'ROAS',
                    data: paddedData.map(r => parseFloat(r.roas_purchase) || 0),
                    borderColor: '#EC4899',
                    backgroundColor: 'rgba(236, 72, 153, 0.1)',
                    borderWidth: 2,
                    pointRadius: 0,
                    pointHoverRadius: 6,
                    fill: false,
                    yAxisID: 'yRoasPurchase',
                    tension: 0.4
                });
            }

            let gridDrawn = false;
            const cssGridColor = getComputedStyle(document.documentElement).getPropertyValue('--fb-chart-grid').trim() || 'rgba(0,0,0,0.05)';
            const cssTicksColor = getComputedStyle(document.documentElement).getPropertyValue('--fb-chart-ticks').trim() || '#6b7280';

            chart.options.scales.x.grid.color = cssGridColor;
            chart.options.scales.x.ticks.color = cssTicksColor;

            ['spend', 'clicks', 'impressions', 'conversions', 'conversion_value', 'ctr', 'cpc', 'cost_per_conversion', 'conversion_rate', 'roas_purchase'].forEach(m => {
                let scaleId;
                if (m === 'conversion_value') scaleId = 'yConversionValue';
                else if (m === 'cost_per_conversion') scaleId = 'yCostPerConversion';
                else if (m === 'conversion_rate') scaleId = 'yConversionRate';
                else if (m === 'roas_purchase') scaleId = 'yRoasPurchase';
                else scaleId = 'y' + m.charAt(0).toUpperCase() + m.slice(1);

                if (!chart.options.scales[scaleId]) {
                    chart.options.scales[scaleId] = {
                        type: 'linear',
                        display: false,
                        grid: { drawOnChartArea: false, drawBorder: false }
                    };
                }

                chart.options.scales[scaleId].display = !!this.activeMetrics[m];
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

        setTab(tab) {
            this.activeTab = tab;
            this.tableState.currentPage = 1;
            this.tableState.searchQuery = '';
            this.syncToUrl();
            this.fetchTable();
        },

        toggleMetric(metric) {
            this.activeMetrics[metric] = !this.activeMetrics[metric];
            this.syncToUrl();
            this.updateChart();
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
    window.googleAdsDashboard = googleAdsDashboard;
}
