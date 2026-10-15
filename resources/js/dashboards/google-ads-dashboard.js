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

        activeFilters: { campaigns: [], adgroups: [], ads: [], devices: [] },
        filterLabels: {},

        get hasAnyFilters() {
            return Object.values(this.activeFilters).some(arr => arr.length > 0);
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

        initDashboard() {
            this.loadSavedFilters();
            this.fetchAll();

            this.$watch('accounts', () => {
                this.loadSavedFilters();
                this.fetchAll();
            });
            this.$watch('dateStart', () => this.fetchAll());
            this.$watch('dateEnd', () => this.fetchAll());
            this.$watch('activeTab', () => this.fetchTable());
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
            this.activeFilters = { campaigns: [], adgroups: [], ads: [], devices: [] };
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

        getCacheKey(endpoint) {
            const accountKey = this.accounts.sort().join('_');
            return `gads_${this.tenantId}_${accountKey}_${this.dateStart}_${this.dateEnd}_${endpoint}_${this.activeTab}`;
        },

        getFetchOptions(extra = {}) {
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
                    activeFilters: this.activeFilters,
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
            const cacheKey = this.getCacheKey('summary');

            if (sessionStorage.getItem(cacheKey)) {
                const data = JSON.parse(sessionStorage.getItem(cacheKey));
                this.summary = data.summary || {};
                this.previous = data.previous || {};
                return;
            }

            this.isSummaryLoading = true;
            try {
                const response = await fetch('/api/google-ads/summary', this.getFetchOptions());
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
            const cacheKey = this.getCacheKey('chart');

            if (sessionStorage.getItem(cacheKey)) {
                this.chartDataRaw = JSON.parse(sessionStorage.getItem(cacheKey));
                this.renderChart();
                return;
            }

            this.isChartLoading = true;
            try {
                const response = await fetch('/api/google-ads/chart', this.getFetchOptions());
                const data = await response.json();
                if (!data.error) {
                    this.chartDataRaw = data.chart || [];
                    this.safeCacheSet(cacheKey, JSON.stringify(this.chartDataRaw));
                    this.renderChart();
                }
            } catch (e) {
                console.error('Google Ads chart error:', e);
            } finally {
                this.isChartLoading = false;
            }
        },

        async fetchTable() {
            if (!this.accounts.length || !this.dateStart || !this.dateEnd) return;
            const cacheKey = this.getCacheKey('table');

            if (sessionStorage.getItem(cacheKey)) {
                const data = JSON.parse(sessionStorage.getItem(cacheKey));
                this.tableState.setRows(data);
                return;
            }

            this.isTableLoading = true;
            try {
                const response = await fetch('/api/google-ads/table', this.getFetchOptions());
                const data = await response.json();
                if (!data.error) {
                    const rows = (data.data || []).map(r => ({
                        ...r,
                        name: r.name || r.id || 'Unknown',
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
                    }));
                    this.safeCacheSet(cacheKey, JSON.stringify(rows));
                    this.tableState.setRows(rows);
                }
            } catch (e) {
                console.error('Google Ads table error:', e);
            } finally {
                this.isTableLoading = false;
            }
        },

        renderChart() {
            // Chart rendering logic matching ApexCharts or ChartJS in dashboard-view
            const chartEl = document.querySelector('#google-ads-chart');
            if (!chartEl || !window.ApexCharts) return;

            const dates = this.chartDataRaw.map(d => d.date || d.day);
            const series = [];

            if (this.activeMetrics.spend) {
                series.push({ name: 'Spend', data: this.chartDataRaw.map(d => parseFloat(d.spend) || 0) });
            }
            if (this.activeMetrics.clicks) {
                series.push({ name: 'Clicks', data: this.chartDataRaw.map(d => parseInt(d.clicks) || 0) });
            }
            if (this.activeMetrics.impressions) {
                series.push({ name: 'Impressions', data: this.chartDataRaw.map(d => parseInt(d.impressions) || 0) });
            }
            if (this.activeMetrics.conversions) {
                series.push({ name: 'Conversions', data: this.chartDataRaw.map(d => parseFloat(d.conversions) || 0) });
            }

            if (this._chartInstance) {
                this._chartInstance.updateOptions({
                    xaxis: { categories: dates },
                    series: series
                });
            } else {
                const options = {
                    chart: { type: 'line', height: 320, toolbar: { show: false } },
                    series: series,
                    xaxis: { categories: dates },
                    stroke: { curve: 'smooth', width: 2 },
                    colors: ['#4285F4', '#34A853', '#FBBC05', '#EA4335']
                };
                this._chartInstance = new window.ApexCharts(chartEl, options);
                this._chartInstance.render();
            }
        },

        setTab(tab) {
            this.activeTab = tab;
            this.fetchTable();
        },

        toggleMetric(metric) {
            this.activeMetrics[metric] = !this.activeMetrics[metric];
            this.renderChart();
        },

        formatCurrency(val) {
            return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(val || 0);
        },

        formatNumber(val) {
            return new Intl.NumberFormat('en-US').format(val || 0);
        },

        formatDecimal(val) {
            return (parseFloat(val) || 0).toFixed(2);
        },

        formatPercent(val) {
            return (parseFloat(val) || 0).toFixed(2) + '%';
        },

        getVarianceClass(val, invert = false) {
            const v = parseFloat(val) || 0;
            if (v === 0) return 'text-gray-500';
            const positive = invert ? v < 0 : v > 0;
            return positive ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400';
        },

        getVarianceIcon(val, invert = false) {
            const v = parseFloat(val) || 0;
            if (v === 0) return '•';
            return v > 0 ? '▲ ' : '▼ ';
        },

        formatVariance(val) {
            const v = parseFloat(val) || 0;
            return Math.abs(v).toFixed(1) + '%';
        }
    };
}
