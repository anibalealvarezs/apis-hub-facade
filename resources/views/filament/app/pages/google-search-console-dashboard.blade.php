<x-filament-panels::page>
    <style>
        :root {
            --gsc-clicks: #4285f4;
            --gsc-impressions: #7e57c2;
            --gsc-ctr: #0097a7;
            --gsc-pos: #f4511e;
        }

        .gsc-header-row { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 30px; }
        .gsc-header-title { font-size: 1.8rem; font-weight: 800; color: #fff; margin-bottom: 5px; display: flex; align-items: center; gap: 12px; }
        .gsc-header-subtitle { color: var(--text-dim, #94a3b8); font-size: 0.9rem; }
        .gsc-header-controls { display: flex; align-items: center; gap: 15px; margin-bottom: 0; }
        
        .metrics-grid-gsc {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 25px;
        }

        .card-stat-gsc {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.05);
            border-bottom: 4px solid var(--color, transparent);
            border-radius: 12px;
            padding: 20px;
            cursor: pointer;
            transition: all 0.2s ease;
            opacity: 0.5;
        }
        .card-stat-gsc:hover { transform: translateY(-3px); background: rgba(255,255,255,0.02); }
        .card-stat-gsc.active { opacity: 1; border-bottom-color: var(--color); background: rgba(255,255,255,0.03); }

        .gsc-label { font-size: 0.72rem; font-weight: 700; color: var(--text-dim, #94a3b8); text-transform: uppercase; margin-bottom: 8px; display: flex; align-items: center; gap: 8px; letter-spacing: 0.05em; }
        .card-metric-value { font-size: 2.2rem; font-weight: 800; color: #fff; line-height: 1.2; }
        .card-metric-trend { font-size: 0.85rem; font-weight: 600; margin-top: 5px; display: flex; align-items: center; gap: 4px; }

        .chart-container-gsc { 
            background: rgba(255,255,255,0.02); 
            border: 1px solid rgba(255,255,255,0.05); 
            border-radius: 16px; 
            padding: 30px; 
            margin-bottom: 30px; 
            height: 450px; 
            position: relative;
        }

        .gsc-table-container { 
            background: rgba(255,255,255,0.02); 
            border: 1px solid rgba(255,255,255,0.05); 
            border-radius: 16px; 
            overflow: hidden;
            margin-top: 40px;
        }

        .tab-nav-gsc { display: flex; border-bottom: 1px solid rgba(255,255,255,0.05); background: rgba(0,0,0,0.2); }
        .tab-gsc { padding: 15px 25px; cursor: pointer; font-size: 0.85rem; font-weight: 600; color: var(--text-dim, #94a3b8); border-right: 1px solid rgba(255,255,255,0.05); transition: all 0.2s; }
        .tab-gsc:hover { background: rgba(255,255,255,0.03); }
        .tab-gsc.active { background: rgba(255,255,255,0.02); color: #4285f4; border-bottom: 2px solid #4285f4; }

        .gsc-table { width: 100%; border-collapse: collapse; text-align: left; }
        .gsc-table th { padding: 15px 25px; font-size: 0.75rem; text-transform: uppercase; color: var(--text-dim, #94a3b8); font-weight: 700; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .gsc-table td { padding: 15px 25px; border-bottom: 1px solid rgba(255,255,255,0.02); vertical-align: middle; }
        
        .metric-cell { text-align: right; width: 12.5%; min-width: 110px; }
        .gsc-table th:first-child, .gsc-table td:first-child { width: 50%; min-width: 300px; }
        
        .progress-bar-container { width: 100%; height: 4px; background: rgba(255,255,255,0.05); border-radius: 2px; margin-top: 4px; overflow: hidden; }
        .progress-bar-fill { height: 100%; transition: width 0.6s ease; }
        
        .metric-val-main { color: #fff; font-weight: 600; font-size: 0.95rem; margin-bottom: 4px; }
        .gsc-url-text { font-weight: 600; color: #fff; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 400px; display: inline-block; vertical-align: middle; }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/dayjs@1/dayjs.min.js"></script>

    <div x-data="gscDashboard()" x-init="initDashboard()">
        <div class="gsc-header-row">
            <div>
                <h1 class="gsc-header-title">
                    <x-heroicon-o-magnifying-glass class="w-8 h-8 text-[#4285f4]" />
                    Google Search Console
                </h1>
                <p class="gsc-header-subtitle">Performance on Google Search results</p>
            </div>
            <div class="gsc-header-controls">
                <button type="button" @click="fetchReport()" class="flex items-center justify-center bg-primary-600 hover:bg-primary-500 text-white text-sm font-medium rounded-lg px-4 py-2.5 transition duration-75 shadow-sm" :class="{ 'opacity-50 cursor-not-allowed': isLoading }" :disabled="isLoading">
                    <x-heroicon-o-arrow-path class="w-5 h-5 mr-2" x-bind:class="{ 'animate-spin': isLoading }" />
                    <span x-text="isLoading ? 'Updating...' : 'Update'"></span>
                </button>
                <select wire:model.live="selectedAccount" class="bg-white dark:bg-white/5 border border-gray-300 dark:border-white/10 text-gray-950 dark:text-white text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 transition duration-75 shadow-sm">
                    <option value="" class="bg-white dark:bg-gray-800">Select Property...</option>
                    @foreach($accounts as $id => $url)
                        <option value="{{ $id }}" class="bg-white dark:bg-gray-800">{{ $url }}</option>
                    @endforeach
                </select>
                <input type="date" wire:model.live="dateStart" class="bg-white dark:bg-white/5 border border-gray-300 dark:border-white/10 text-gray-950 dark:text-white text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-40 p-2.5 transition duration-75 shadow-sm">
                <input type="date" wire:model.live="dateEnd" class="bg-white dark:bg-white/5 border border-gray-300 dark:border-white/10 text-gray-950 dark:text-white text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-40 p-2.5 transition duration-75 shadow-sm">
            </div>
        </div>

        <div class="metrics-grid-gsc relative">
            <div x-show="isLoading" class="absolute inset-0 z-10 flex items-center justify-center bg-white/50 dark:bg-gray-900/50 backdrop-blur-sm rounded-xl">
                <x-filament::loading-indicator class="h-8 w-8 text-primary-500" />
            </div>
            <div class="card-stat-gsc" :class="activeMetrics.clicks ? 'active' : ''" @click="toggleMetric('clicks')" style="--color: #4285f4;">
                <div class="gsc-label">Total Clicks</div>
                <div class="card-metric-value" x-text="formatNumber(summary.clicks)"></div>
            </div>
            <div class="card-stat-gsc" :class="activeMetrics.impressions ? 'active' : ''" @click="toggleMetric('impressions')" style="--color: #7e57c2;">
                <div class="gsc-label">Total Impressions</div>
                <div class="card-metric-value" x-text="formatNumber(summary.impressions)"></div>
            </div>
            <div class="card-stat-gsc" :class="activeMetrics.ctr ? 'active' : ''" @click="toggleMetric('ctr')" style="--color: #0097a7;">
                <div class="gsc-label">Average CTR</div>
                <div class="card-metric-value" x-text="formatPercent(summary.ctr)"></div>
            </div>
            <div class="card-stat-gsc" :class="activeMetrics.position ? 'active' : ''" @click="toggleMetric('position')" style="--color: #f4511e;">
                <div class="gsc-label">Average Position</div>
                <div class="card-metric-value" x-text="formatDecimals(summary.position)"></div>
            </div>
        </div>

        <div class="chart-container-gsc relative">
            <div x-show="isLoading" class="absolute inset-0 z-10 flex items-center justify-center bg-white/50 dark:bg-gray-900/50 backdrop-blur-sm rounded-xl">
                <x-filament::loading-indicator class="h-8 w-8 text-primary-500" />
            </div>
            <canvas x-ref="canvas"></canvas>
        </div>

        <div class="gsc-table-container relative">
            <div x-show="isLoading" class="absolute inset-0 z-10 flex items-center justify-center bg-white/50 dark:bg-gray-900/50 backdrop-blur-sm rounded-xl">
                <x-filament::loading-indicator class="h-8 w-8 text-primary-500" />
            </div>
            <div class="tab-nav-gsc">
                <div class="tab-gsc" :class="activeTab === 'queries' ? 'active' : ''" @click="setTab('queries')">QUERIES</div>
                <div class="tab-gsc" :class="activeTab === 'pages' ? 'active' : ''" @click="setTab('pages')">PAGES</div>
                <div class="tab-gsc" :class="activeTab === 'countries' ? 'active' : ''" @click="setTab('countries')">COUNTRIES</div>
                <div class="tab-gsc" :class="activeTab === 'devices' ? 'active' : ''" @click="setTab('devices')">DEVICES</div>
                <div class="tab-gsc" :class="activeTab === 'appearances' ? 'active' : ''" @click="setTab('appearances')">SEARCH APPEARANCE</div>
            </div>

            <div class="overflow-x-auto">
                <table class="gsc-table">
                    <thead>
                        <tr>
                            <th>
                                <span x-text="activeTab.toUpperCase()"></span>
                            </th>
                            <th class="metric-cell cursor-pointer" @click="sortBy('clicks')">Clicks <span x-show="sortCol === 'clicks'" x-text="sortDir === 'desc' ? '↓' : '↑'"></span></th>
                            <th class="metric-cell cursor-pointer" @click="sortBy('impressions')">Impressions <span x-show="sortCol === 'impressions'" x-text="sortDir === 'desc' ? '↓' : '↑'"></span></th>
                            <th class="metric-cell cursor-pointer" @click="sortBy('ctr')">CTR <span x-show="sortCol === 'ctr'" x-text="sortDir === 'desc' ? '↓' : '↑'"></span></th>
                            <th class="metric-cell cursor-pointer" @click="sortBy('position')">Position <span x-show="sortCol === 'position'" x-text="sortDir === 'desc' ? '↓' : '↑'"></span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="row in sortedTableData" :key="row.id">
                            <tr>
                                <td>
                                    <div class="gsc-url-text" :title="row.id" x-text="row.id"></div>
                                </td>
                                <td class="metric-cell">
                                    <div class="metric-val-main" x-text="formatNumber(row.clicks)"></div>
                                    <div class="progress-bar-container"><div class="progress-bar-fill" style="background: #4285f4;" :style="`width: ${(row.clicks / maxClicks) * 100}%`"></div></div>
                                </td>
                                <td class="metric-cell">
                                    <div class="metric-val-main" x-text="formatNumber(row.impressions)"></div>
                                    <div class="progress-bar-container"><div class="progress-bar-fill" style="background: #7e57c2;" :style="`width: ${(row.impressions / maxImpressions) * 100}%`"></div></div>
                                </td>
                                <td class="metric-cell">
                                    <div class="metric-val-main" x-text="formatPercent(row.ctr)"></div>
                                    <div class="progress-bar-container"><div class="progress-bar-fill" style="background: #0097a7;" :style="`width: ${row.ctr * 100}%`"></div></div>
                                </td>
                                <td class="metric-cell">
                                    <div class="metric-val-main" x-text="formatDecimals(row.position)"></div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('gscDashboard', () => {
                return {
                    tenantId: '{{ Filament\Facades\Filament::getTenant()->id ?? Filament\Facades\Filament::getTenant()->slug }}',
                    account: @entangle('selectedAccount'),
                    dateStart: @entangle('dateStart'),
                    dateEnd: @entangle('dateEnd'),
                    activeTab: @entangle('activeTab'),
                    
                    isLoading: false,
                    
                    summary: { clicks: 0, impressions: 0, ctr: 0, position: 0 },
                    chartDataRaw: [],
                    tableDataRaw: [],
                    
                    activeMetrics: { clicks: true, impressions: true, ctr: false, position: false },
                    
                    sortCol: 'clicks',
                    sortDir: 'desc',
                    
                    chartInstance: null,
                    
                    initDashboard() {
                        this.initChart();
                        
                        this.$watch('account', () => this.fetchReport());
                        this.$watch('dateStart', () => this.fetchReport());
                        this.$watch('dateEnd', () => this.fetchReport());
                        
                        if (this.account && this.dateStart && this.dateEnd) {
                            this.fetchReport();
                        }
                    },
                    
                    setTab(tab) {
                        this.activeTab = tab;
                        this.fetchReport();
                        // Also update Livewire state in background so it persists if needed
                        this.$wire.setActiveTab(tab);
                    },
                    
                    async fetchReport() {
                        if (!this.account || !this.dateStart || !this.dateEnd) return;
                        
                        this.isLoading = true;
                        
                        try {
                            const response = await fetch('/api/gsc/report', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                },
                                body: JSON.stringify({
                                    tenant: this.tenantId,
                                    account: this.account,
                                    dateStart: this.dateStart,
                                    dateEnd: this.dateEnd,
                                    activeTab: this.activeTab
                                })
                            });
                            
                            const data = await response.json();
                            
                            if (data.error) {
                                console.error('API Error:', data.error);
                                return;
                            }
                            
                            this.summary = data.summary || { clicks: 0, impressions: 0, ctr: 0, position: 0 };
                            this.chartDataRaw = data.chart || [];
                            this.tableDataRaw = data.table || [];
                            
                            this.updateChart();
                            
                        } catch (error) {
                            console.error('Error fetching GSC report:', error);
                        } finally {
                            this.isLoading = false;
                        }
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
                                    x: { grid: { color: 'rgba(255, 255, 255, 0.05)', drawBorder: false }, ticks: { color: '#94a3b8', maxTicksLimit: 10 } },
                                    yLeft: { type: 'linear', position: 'left', grid: { color: 'rgba(255, 255, 255, 0.05)', drawBorder: false }, ticks: { color: '#94a3b8' } },
                                    yRight: { type: 'linear', position: 'right', grid: { drawOnChartArea: false, drawBorder: false }, ticks: { color: '#94a3b8' } }
                                }
                            }
                        };
                        
                        this.chartInstance = new Chart(ctx, config);
                    },
                    
                    updateChart() {
                        if (!this.chartInstance || !this.chartDataRaw.length) return;
                        
                        const labels = this.chartDataRaw.map(r => dayjs(r.date).format('MMM D'));
                        const datasets = [];
                        
                        if (this.activeMetrics.clicks) {
                            datasets.push({
                                label: 'Clicks',
                                data: this.chartDataRaw.map(r => r.clicks),
                                borderColor: '#4285f4',
                                backgroundColor: 'rgba(66, 133, 244, 0.1)',
                                borderWidth: 2,
                                pointRadius: 0,
                                pointHoverRadius: 6,
                                fill: true,
                                yAxisID: 'yLeft',
                                tension: 0.4
                            });
                        }
                        
                        if (this.activeMetrics.impressions) {
                            datasets.push({
                                label: 'Impressions',
                                data: this.chartDataRaw.map(r => r.impressions),
                                borderColor: '#7e57c2',
                                backgroundColor: 'rgba(126, 87, 194, 0.1)',
                                borderWidth: 2,
                                pointRadius: 0,
                                pointHoverRadius: 6,
                                fill: true,
                                yAxisID: 'yLeft',
                                tension: 0.4
                            });
                        }
                        
                        if (this.activeMetrics.ctr) {
                            datasets.push({
                                label: 'CTR',
                                data: this.chartDataRaw.map(r => r.ctr * 100),
                                borderColor: '#0097a7',
                                borderWidth: 2,
                                pointRadius: 0,
                                pointHoverRadius: 6,
                                fill: false,
                                yAxisID: 'yRight',
                                tension: 0.4
                            });
                        }
                        
                        if (this.activeMetrics.position) {
                            datasets.push({
                                label: 'Position',
                                data: this.chartDataRaw.map(r => r.position),
                                borderColor: '#f4511e',
                                borderWidth: 2,
                                borderDash: [5, 5],
                                pointRadius: 0,
                                pointHoverRadius: 6,
                                fill: false,
                                yAxisID: 'yRight',
                                tension: 0.4
                            });
                        }
                        
                        this.chartInstance.data.labels = labels;
                        this.chartInstance.data.datasets = datasets;
                        this.chartInstance.update();
                    },
                    
                    sortBy(col) {
                        if (this.sortCol === col) {
                            this.sortDir = this.sortDir === 'desc' ? 'asc' : 'desc';
                        } else {
                            this.sortCol = col;
                            this.sortDir = 'desc';
                        }
                    },
                    
                    get sortedTableData() {
                        return [...this.tableDataRaw].sort((a, b) => {
                            let valA = a[this.sortCol];
                            let valB = b[this.sortCol];
                            if (this.sortDir === 'desc') return valB > valA ? 1 : -1;
                            return valA > valB ? 1 : -1;
                        });
                    },
                    
                    get maxClicks() {
                        if (!this.tableDataRaw.length) return 1;
                        return Math.max(...this.tableDataRaw.map(r => r.clicks));
                    },
                    
                    get maxImpressions() {
                        if (!this.tableDataRaw.length) return 1;
                        return Math.max(...this.tableDataRaw.map(r => r.impressions));
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
                        if (num === undefined || num === null) return '0.0';
                        return Number(num).toFixed(1);
                    }
                }
            });
        });
    </script>
</x-filament-panels::page>
