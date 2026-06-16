<x-filament-panels::page>
    <style>
        .joint-header-row { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 30px; }
        .joint-header-title { font-size: 1.8rem; font-weight: 800; color: #111827; display: flex; align-items: center; gap: 12px; }
        .dark .joint-header-title { color: #ffffff; }
        .joint-header-controls { display: flex; align-items: center; gap: 15px; }

        .joint-card {
            background: rgba(0,0,0,0.03);
            border: 1px solid rgba(0,0,0,0.05);
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 25px;
        }
        .dark .joint-card {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.05);
        }

        .joint-form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; }
        .joint-curve-section {
            padding: 20px;
            border-radius: 12px;
            border: 1px solid rgba(0,0,0,0.1);
            background: rgba(255,255,255,0.5);
        }
        .dark .joint-curve-section {
            border: 1px solid rgba(255,255,255,0.1);
            background: rgba(0,0,0,0.2);
        }
        .curve-a { border-top: 4px solid #00a7f9; }
        .curve-b { border-top: 4px solid #f43f5e; }

        /* Selector CSS replaced by Tailwind form classes */

        .chart-container-joint { height: 450px; position: relative; }

        .correlation-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 1.1rem;
            margin-top: 20px;
        }
        .corr-strong-pos { background: rgba(34,197,94,0.1); color: #16a34a; border: 1px solid rgba(34,197,94,0.2); }
        .dark .corr-strong-pos { color: #4ade80; }
        .corr-strong-neg { background: rgba(239,68,68,0.1); color: #dc2626; border: 1px solid rgba(239,68,68,0.2); }
        .dark .corr-strong-neg { color: #f87171; }
        .corr-weak { background: rgba(156,163,175,0.1); color: #4b5563; border: 1px solid rgba(156,163,175,0.2); }
        .dark .corr-weak { color: #9ca3af; }
    </style>

    <div x-data="jointDashboard()" x-init="initDashboard()">
        <div class="joint-header-row">
            <div>
                <h1 class="joint-header-title">
                    <x-heroicon-o-arrows-right-left class="w-8 h-8 text-[#00a7f9]"/>
                    {{ __('Cross-Metric Joint Dashboard') }}
                </h1>
            </div>
            <div class="joint-header-controls">
                <input type="date" x-model.lazy="dateStart"
                       class="bg-white dark:bg-white/5 border border-gray-300 dark:border-white/10 text-gray-950 dark:text-white text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-40 p-2.5">
                <input type="date" x-model.lazy="dateEnd" max="{{ date('Y-m-d') }}"
                       class="bg-white dark:bg-white/5 border border-gray-300 dark:border-white/10 text-gray-950 dark:text-white text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-40 p-2.5">
                <button type="button" @click="fetchData()"
                        class="flex items-center justify-center bg-primary-600 hover:bg-primary-500 text-white text-sm font-medium rounded-lg px-6 py-2.5 transition shadow-sm"
                        :class="{ 'opacity-50 cursor-not-allowed': isLoading }"
                        :disabled="isLoading || !isReadyToFetch()">
                    <x-heroicon-o-arrow-path class="w-5 h-5 mr-2" x-bind:class="{ 'animate-spin': isLoading }"/>
                    <span>{{ __('Compare') }}</span>
                </button>
            </div>
        </div>

        <div class="joint-card">
            <div class="joint-form-grid">
                <!-- Curve A -->
                <div class="joint-curve-section curve-a">
                    <h3 class="text-lg font-bold mb-4 flex items-center gap-2" style="color: #00a7f9;">
                        <span class="w-3 h-3 rounded-full bg-[#00a7f9]"></span> Curve A (Blue)
                    </h3>
                    <div class="space-y-3">
                        <div>
                            <label class="text-sm font-semibold text-gray-600 dark:text-gray-400">Channel</label>
                            <select x-model="curveA.channel" @change="curveA.asset = ''; curveA.metric = ''" class="bg-white dark:bg-white/5 border border-gray-300 dark:border-white/10 text-gray-950 dark:text-white text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 mt-1">
                                <option value="">Select Channel...</option>
                                <template x-for="(label, key) in channels" :key="key">
                                    <template x-if="Object.keys(availableAccounts[key] || {}).length > 0">
                                        <option :value="key" x-text="label"></option>
                                    </template>
                                </template>
                            </select>
                        </div>
                        <div x-show="curveA.channel">
                            <label class="text-sm font-semibold text-gray-600 dark:text-gray-400">Asset / Property</label>
                            <select x-model="curveA.asset" class="bg-white dark:bg-white/5 border border-gray-300 dark:border-white/10 text-gray-950 dark:text-white text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 mt-1">
                                <option value="">Select Asset...</option>
                                <template x-for="(name, id) in availableAccounts[curveA.channel] || {}" :key="id">
                                    <option :value="id" x-text="name"></option>
                                </template>
                            </select>
                        </div>
                        <div x-show="curveA.channel">
                            <label class="text-sm font-semibold text-gray-600 dark:text-gray-400">Metric</label>
                            <select x-model="curveA.metric" class="bg-white dark:bg-white/5 border border-gray-300 dark:border-white/10 text-gray-950 dark:text-white text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 mt-1">
                                <option value="">Select Metric...</option>
                                <template x-for="(label, key) in metricsDict[curveA.channel] || {}" :key="key">
                                    <option :value="key" x-text="label"></option>
                                </template>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Curve B -->
                <div class="joint-curve-section curve-b">
                    <h3 class="text-lg font-bold mb-4 flex items-center gap-2" style="color: #f43f5e;">
                        <span class="w-3 h-3 rounded-full bg-[#f43f5e]"></span> Curve B (Red)
                    </h3>
                    <div class="space-y-3">
                        <div>
                            <label class="text-sm font-semibold text-gray-600 dark:text-gray-400">Channel</label>
                            <select x-model="curveB.channel" @change="curveB.asset = ''; curveB.metric = ''" class="bg-white dark:bg-white/5 border border-gray-300 dark:border-white/10 text-gray-950 dark:text-white text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 mt-1">
                                <option value="">Select Channel...</option>
                                <template x-for="(label, key) in channels" :key="key">
                                    <template x-if="Object.keys(availableAccounts[key] || {}).length > 0">
                                        <option :value="key" x-text="label"></option>
                                    </template>
                                </template>
                            </select>
                        </div>
                        <div x-show="curveB.channel">
                            <label class="text-sm font-semibold text-gray-600 dark:text-gray-400">Asset / Property</label>
                            <select x-model="curveB.asset" class="bg-white dark:bg-white/5 border border-gray-300 dark:border-white/10 text-gray-950 dark:text-white text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 mt-1">
                                <option value="">Select Asset...</option>
                                <template x-for="(name, id) in availableAccounts[curveB.channel] || {}" :key="id">
                                    <option :value="id" x-text="name"></option>
                                </template>
                            </select>
                        </div>
                        <div x-show="curveB.channel">
                            <label class="text-sm font-semibold text-gray-600 dark:text-gray-400">Metric</label>
                            <select x-model="curveB.metric" class="bg-white dark:bg-white/5 border border-gray-300 dark:border-white/10 text-gray-950 dark:text-white text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 mt-1">
                                <option value="">Select Metric...</option>
                                <template x-for="(label, key) in metricsDict[curveB.channel] || {}" :key="key">
                                    <option :value="key" x-text="label"></option>
                                </template>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="joint-card" x-show="chartRendered" style="display: none;">
            <div x-show="isLoading" class="absolute inset-0 z-10 flex items-center justify-center bg-white/50 dark:bg-black/50 backdrop-blur-sm rounded-2xl">
                <div class="flex flex-col items-center">
                    <svg class="animate-spin h-10 w-10 text-primary-600 mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('Processing Joint Curves & Correlation...') }}</span>
                </div>
            </div>
            
            <div class="flex justify-between items-start mb-6">
                <div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ __('Comparison View') }}</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1" x-text="subtitle"></p>
                </div>
                
                <!-- Correlation Display -->
                <div x-show="correlation" class="text-right">
                    <div class="text-xs font-semibold text-gray-500 uppercase tracking-widest">{{ __('Pearson Correlation') }}</div>
                    <div class="correlation-badge" :class="getCorrelationClass()">
                        <span x-text="getCorrelationIcon()" class="text-xl"></span>
                        <span x-text="correlation ? correlation.correlation_coefficient.toFixed(3) : ''"></span>
                    </div>
                </div>
            </div>

            <div class="chart-container-joint">
                <canvas id="jointChart"></canvas>
            </div>
        </div>
    </div>

    <script>
        (() => {
            const registerJointDashboard = () => {
                Alpine.data('jointDashboard', () => ({
                    isLoading: false,
                    chartRendered: false,
                    chartInstance: null,
                    dateStart: @entangle('dateStart'),
                    dateEnd: @entangle('dateEnd'),
                    channels: @json($channels),
                    metricsDict: @json($metricsDict),
                    availableAccounts: @json($availableAccounts),
                    curveA: { channel: '', asset: '', metric: '' },
                    curveB: { channel: '', asset: '', metric: '' },
                    chartData: null,
                    correlation: null,
                    subtitle: '',

                    initDashboard() {
                        window.addEventListener('joint-data-loaded', (e) => {
                            // Livewire 3 sometimes passes named params inside e.detail.data, or unnamed as e.detail[0]
                            let payload = e.detail;
                            if (payload && payload[0]) payload = payload[0];
                            if (payload && payload.data) payload = payload.data;
                            
                            this.chartData = payload;
                            this.correlation = this.chartData?.correlation || null;
                            if (this.chartData && this.chartData.curveA && this.chartData.curveB) {
                                this.renderChart();
                            } else {
                                console.error("Invalid chart data payload received:", e.detail);
                            }
                            this.isLoading = false;
                            this.chartRendered = true;
                        });
                    },

                    isReadyToFetch() {
                        return this.curveA.channel && this.curveA.asset && this.curveA.metric &&
                               this.curveB.channel && this.curveB.asset && this.curveB.metric &&
                               this.dateStart && this.dateEnd;
                    },

                    fetchData() {
                        if (!this.isReadyToFetch()) return;
                        this.isLoading = true;
                        if (this.chartRendered) this.chartRendered = true;
                        
                        @this.fetchJointData(this.curveA, this.curveB, this.dateStart, this.dateEnd);
                    },

                    getCorrelationClass() {
                        if (!this.correlation) return 'corr-weak';
                        const coef = this.correlation.correlation_coefficient;
                        if (coef > 0.4) return 'corr-strong-pos';
                        if (coef < -0.4) return 'corr-strong-neg';
                        return 'corr-weak';
                    },

                    getCorrelationIcon() {
                        if (!this.correlation) return '≈';
                        const coef = this.correlation.correlation_coefficient;
                        if (coef > 0.4) return '↗';
                        if (coef < -0.4) return '↘';
                        return '≈';
                    },

                    renderChart() {
                        if (typeof Chart === 'undefined' && window.importChartJs) {
                            window.importChartJs().then(module => {
                                window.Chart = module.default;
                                this.renderChart();
                            }).catch(err => console.error("Failed to load Chart.js", err));
                            return;
                        }

                        if (this.chartInstance) {
                            this.chartInstance.destroy();
                        }

                        const dataA = this.chartData.curveA;
                        const dataB = this.chartData.curveB;

                        this.subtitle = `${dataA.name} vs ${dataB.name}`;

                        const isDarkMode = document.documentElement.classList.contains('dark');
                        const textColor = isDarkMode ? '#9ca3af' : '#6b7280';
                        const gridColor = isDarkMode ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.05)';

                        const ctx = document.getElementById("jointChart").getContext('2d');
                        this.chartInstance = new Chart(ctx, {
                            type: 'line',
                            data: {
                                labels: dataA.dates,
                                datasets: [
                                    {
                                        label: dataA.name,
                                        data: dataA.values,
                                        borderColor: '#00a7f9',
                                        backgroundColor: 'rgba(0, 167, 249, 0.1)',
                                        borderWidth: 2,
                                        pointRadius: 0,
                                        pointHoverRadius: 6,
                                        yAxisID: 'yA',
                                        tension: 0.4,
                                        fill: true
                                    },
                                    {
                                        label: dataB.name,
                                        data: dataB.values,
                                        borderColor: '#f43f5e',
                                        backgroundColor: 'rgba(244, 63, 94, 0.1)',
                                        borderWidth: 2,
                                        pointRadius: 0,
                                        pointHoverRadius: 6,
                                        yAxisID: 'yB',
                                        tension: 0.4,
                                        fill: true
                                    }
                                ]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                interaction: {
                                    mode: 'index',
                                    intersect: false,
                                },
                                plugins: {
                                    legend: {
                                        labels: { color: textColor }
                                    }
                                },
                                scales: {
                                    x: {
                                        grid: { color: gridColor },
                                        ticks: { color: textColor }
                                    },
                                    yA: {
                                        type: 'linear',
                                        display: true,
                                        position: 'left',
                                        grid: { color: gridColor },
                                        ticks: { color: '#00a7f9' },
                                        title: {
                                            display: true,
                                            text: dataA.name,
                                            color: '#00a7f9',
                                            font: { weight: 'bold' }
                                        }
                                    },
                                    yB: {
                                        type: 'linear',
                                        display: true,
                                        position: 'right',
                                        grid: { drawOnChartArea: false },
                                        ticks: { color: '#f43f5e' },
                                        title: {
                                            display: true,
                                            text: dataB.name,
                                            color: '#f43f5e',
                                            font: { weight: 'bold' }
                                        }
                                    }
                                }
                            }
                        });
                    }
                }));
            };

            if (window.Alpine) {
                registerJointDashboard();
            } else {
                document.addEventListener('alpine:init', registerJointDashboard);
            }
        })();
    </script>
</x-filament-panels::page>
