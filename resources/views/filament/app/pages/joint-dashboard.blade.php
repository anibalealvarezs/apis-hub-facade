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
                    {{ __('Performance Correlations') }}
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
            <!-- Playbook Section -->
            <div class="mb-6 pb-6 border-b border-gray-200 dark:border-white/10" x-show="getAvailablePlays().length > 0">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2 flex items-center gap-2">
                    <x-heroicon-o-book-open class="w-5 h-5 text-primary-500" />
                    {{ __('Analysis Playbook') }}
                </h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                    {{ __('Select a predefined marketing theory scenario to auto-configure the dashboard. You will only need to select your specific assets.') }}
                </p>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <template x-for="play in getAvailablePlays()" :key="play.id">
                        <button type="button" @click="applyPlay(play)" 
                                class="text-left px-4 py-3 rounded-xl border transition-all duration-200 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-primary-500"
                                :class="selectedPlay?.id === play.id ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20' : 'border-gray-200 dark:border-white/10 bg-white dark:bg-white/5 hover:border-primary-300 dark:hover:border-primary-500/50'">
                            <div class="font-bold text-sm text-gray-900 dark:text-white" x-text="play.name"></div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1 truncate" x-text="play.short_desc"></div>
                        </button>
                    </template>
                </div>

                <!-- Active Play Info -->
                <div x-show="selectedPlay" x-collapse class="mt-4">
                    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                        <h4 class="font-bold text-blue-900 dark:text-blue-300 text-sm mb-2" x-text="selectedPlay?.name"></h4>
                        <div class="text-sm text-blue-800 dark:text-blue-200 space-y-2">
                            <p><strong>Theory:</strong> <span x-text="selectedPlay?.theory"></span></p>
                            <p><strong>Expected Results:</strong> <span x-text="selectedPlay?.expected"></span></p>
                            <p class="text-xs italic mt-2 opacity-80">{{ __('Note: Please select your specific assets below to complete the configuration.') }}</p>
                        </div>
                    </div>
                </div>
            </div>

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
                        <div x-show="curveA.channel" class="grid grid-cols-2 gap-3 mt-3">
                            <div>
                                <label class="text-sm font-semibold text-gray-600 dark:text-gray-400">Analysis Level</label>
                                <select x-model="curveA.level" @change="chartRendered && renderChart()" class="bg-white dark:bg-white/5 border border-gray-300 dark:border-white/10 text-gray-950 dark:text-white text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 mt-1">
                                    <option value="level">Level (Original)</option>
                                    <option value="diff1">1st Difference (Δ)</option>
                                    <option value="diff2">2nd Difference (ΔΔ)</option>
                                    <option value="zscore">Z-Score (Normalized)</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-sm font-semibold text-gray-600 dark:text-gray-400">Lag (Shift)</label>
                                <select x-model="curveA.lag" @change="curveB.lag = '0'; chartRendered && renderChart()" class="bg-white dark:bg-white/5 border border-gray-300 dark:border-white/10 text-gray-950 dark:text-white text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 mt-1">
                                    <option value="0">No Lag</option>
                                    <option value="1">+1 Day</option>
                                    <option value="2">+2 Days</option>
                                    <option value="3">+3 Days</option>
                                    <option value="4">+4 Days</option>
                                    <option value="5">+5 Days</option>
                                    <option value="6">+6 Days</option>
                                    <option value="7">+7 Days</option>
                                    <option value="-1">-1 Day</option>
                                    <option value="-2">-2 Days</option>
                                    <option value="-3">-3 Days</option>
                                    <option value="-4">-4 Days</option>
                                    <option value="-5">-5 Days</option>
                                    <option value="-6">-6 Days</option>
                                    <option value="-7">-7 Days</option>
                                </select>
                            </div>
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
                        <div x-show="curveB.channel" class="grid grid-cols-2 gap-3 mt-3">
                            <div>
                                <label class="text-sm font-semibold text-gray-600 dark:text-gray-400">Analysis Level</label>
                                <select x-model="curveB.level" @change="chartRendered && renderChart()" class="bg-white dark:bg-white/5 border border-gray-300 dark:border-white/10 text-gray-950 dark:text-white text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 mt-1">
                                    <option value="level">Level (Original)</option>
                                    <option value="diff1">1st Difference (Δ)</option>
                                    <option value="diff2">2nd Difference (ΔΔ)</option>
                                    <option value="zscore">Z-Score (Normalized)</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-sm font-semibold text-gray-600 dark:text-gray-400">Lag (Shift)</label>
                                <select x-model="curveB.lag" @change="curveA.lag = '0'; chartRendered && renderChart()" class="bg-white dark:bg-white/5 border border-gray-300 dark:border-white/10 text-gray-950 dark:text-white text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 mt-1">
                                    <option value="0">No Lag</option>
                                    <option value="1">+1 Day</option>
                                    <option value="2">+2 Days</option>
                                    <option value="3">+3 Days</option>
                                    <option value="4">+4 Days</option>
                                    <option value="5">+5 Days</option>
                                    <option value="6">+6 Days</option>
                                    <option value="7">+7 Days</option>
                                    <option value="-1">-1 Day</option>
                                    <option value="-2">-2 Days</option>
                                    <option value="-3">-3 Days</option>
                                    <option value="-4">-4 Days</option>
                                    <option value="-5">-5 Days</option>
                                    <option value="-6">-6 Days</option>
                                    <option value="-7">-7 Days</option>
                                </select>
                            </div>
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
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1 max-w-2xl">
                        {{ __('Displays both metrics mapped over time. Z-Score (default) scales both metrics by their standard deviations, allowing you to perfectly compare relative volatility regardless of their raw numbers.') }}
                    </p>
                </div>
                
                <!-- Correlation Display -->
                <div x-show="correlation !== null" class="text-right">
                    <div class="text-xs font-semibold text-gray-500 uppercase tracking-widest">{{ __('Pearson Correlation') }}</div>
                    <div class="correlation-badge" :class="getCorrelationClass()">
                        <span x-text="getCorrelationIcon()" class="text-xl"></span>
                        <span x-text="correlation !== null ? correlation.toFixed(3) : ''"></span>
                    </div>
                </div>
            </div>

            <div class="chart-container-joint pb-8">
                <canvas id="jointChart"></canvas>
            </div>
            
            <div class="mt-12 border-t border-gray-200 dark:border-white/10 pt-10 pb-4">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">Rolling Correlation (7-Day Window)</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-6 max-w-3xl">
                    {{ __('Shows how the Pearson correlation between the two metrics evolves day by day. A drop to zero indicates the day a relationship broke (e.g., ad fatigue or an algorithm update).') }}
                </p>
                <div class="chart-container-joint pb-8" style="height: 250px;">
                    <canvas id="rollingChart"></canvas>
                </div>
            </div>

            <div class="mt-12 border-t border-gray-200 dark:border-white/10 pt-10 pb-4">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">Scatter Plot (Correlation Distribution)</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-6 max-w-3xl">
                    {{ __('Removes the element of time. Helps identify non-linear relationships, data clustering, or points of diminishing returns (where higher values on the X axis stop producing higher values on the Y axis).') }}
                </p>
                <div class="chart-container-joint" style="height: 350px;">
                    <canvas id="scatterChart"></canvas>
                </div>
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
                    curveA: { channel: '', asset: '', metric: '', level: 'zscore', lag: '0' },
                    curveB: { channel: '', asset: '', metric: '', level: 'zscore', lag: '0' },
                    chartData: null,
                    correlation: null,
                    subtitle: '',
                    scatterChartInstance: null,
                    rollingChartInstance: null,
                    selectedPlay: null,

                    allPlays: [
                        {
                            id: 'brand_search_synergy',
                            name: 'Brand Search Synergy',
                            short_desc: 'FB Ads vs GSC',
                            theory: 'Paid social campaigns drive top-of-funnel awareness. People see an ad, don\'t click, but later search for the brand on Google.',
                            expected: 'Positive correlation with a 2-4 day lag. If correlation is 0, your ads are not generating residual search intent.',
                            requires: ['facebook_marketing', 'google_search_console'],
                            config: {
                                curveA: { channel: 'facebook_marketing', metric: 'spend', level: 'zscore', lag: '0' },
                                curveB: { channel: 'google_search_console', metric: 'clicks', level: 'zscore', lag: '3' }
                            }
                        },
                        {
                            id: 'organic_lift_paid',
                            name: 'Organic Lift via Paid',
                            short_desc: 'FB Ads vs FB Organic',
                            theory: 'Aggressive paid spending can create a halo effect on your organic profile visits and reach.',
                            expected: 'Positive correlation. When spend spikes, organic reach should spike proportionally.',
                            requires: ['facebook_marketing', 'facebook_organic'],
                            config: {
                                curveA: { channel: 'facebook_marketing', metric: 'spend', level: 'zscore', lag: '0' },
                                curveB: { channel: 'facebook_organic', metric: 'reach', level: 'zscore', lag: '0' }
                            }
                        },
                        {
                            id: 'ad_fatigue',
                            name: 'Ad Fatigue & Efficiency',
                            short_desc: 'FB CTR vs FB Cost',
                            theory: 'As audience saturates, click-through rates drop while cost per acquisition (CPA) spikes.',
                            expected: 'Strong negative correlation. The Rolling Correlation chart is critical here to spot the exact day fatigue started.',
                            requires: ['facebook_marketing'],
                            config: {
                                curveA: { channel: 'facebook_marketing', metric: 'ctr', level: 'zscore', lag: '0' },
                                curveB: { channel: 'facebook_marketing', metric: 'cost_per_result', level: 'zscore', lag: '0' }
                            }
                        },
                        {
                            id: 'google_evaluation_cycle',
                            name: 'SEO Evaluation Cycle',
                            short_desc: 'GSC Impressions vs Position',
                            theory: 'When Google gives you an impression spike, it takes the algorithm a few days to process the user-behavior signals (CTR, Bounce Rate) from that traffic before adjusting your ranking.',
                            expected: 'Positive correlation with Lag +4. A spike in impressions 4 days ago often correlates with a temporary drop in ranking (higher position number) today as Google processes the broad, low-engagement traffic test.',
                            requires: ['google_search_console'],
                            config: {
                                curveA: { channel: 'google_search_console', metric: 'impressions', level: 'zscore', lag: '4' },
                                curveB: { channel: 'google_search_console', metric: 'position', level: 'zscore', lag: '0' }
                            }
                        },
                        {
                            id: 'empty_traffic',
                            name: 'Empty Traffic Check',
                            short_desc: 'FB Clicks vs Conversions',
                            theory: 'More clicks should theoretically mean more conversions. If they don\'t, you might have a "clickbait" ad or a broken landing page.',
                            expected: 'Should be a strong positive correlation. If the correlation drops to zero or goes negative, your ads are driving low-intent traffic that doesn\'t convert.',
                            requires: ['facebook_marketing'],
                            config: {
                                curveA: { channel: 'facebook_marketing', metric: 'clicks', level: 'zscore', lag: '0' },
                                curveB: { channel: 'facebook_marketing', metric: 'results', level: 'zscore', lag: '0' }
                            }
                        },
                        {
                            id: 'cpm_vs_roas',
                            name: 'Auction Competition vs ROAS',
                            short_desc: 'FB CPM vs ROAS',
                            theory: 'When market competition drives up the cost of impressions (CPM), does your return on ad spend (ROAS) immediately drop, or does the higher quality of expensive traffic maintain the ROAS?',
                            expected: 'Typically a negative correlation. As CPMs rise, ROAS drops unless the more expensive audience is converting at a proportionally higher rate.',
                            requires: ['facebook_marketing'],
                            config: {
                                curveA: { channel: 'facebook_marketing', metric: 'cpm', level: 'zscore', lag: '0' },
                                curveB: { channel: 'facebook_marketing', metric: 'purchase_roas', level: 'zscore', lag: '0' }
                            }
                        },
                        {
                            id: 'organic_algorithm_reward',
                            name: 'Algorithm Reward',
                            short_desc: 'FB Engagements vs Reach',
                            theory: 'Social algorithms reward high engagement (likes/comments) today with broader reach tomorrow.',
                            expected: 'Positive correlation with a 1 to 2 day lag. A spike in interactions today should cause a delayed spike in reach as the algorithm pushes the content.',
                            requires: ['facebook_organic'],
                            config: {
                                curveA: { channel: 'facebook_organic', metric: 'total_interactions', level: 'zscore', lag: '0' },
                                curveB: { channel: 'facebook_organic', metric: 'reach', level: 'zscore', lag: '2' }
                            }
                        }
                    ],

                    getAvailablePlays() {
                        let availableKeys = Object.keys(this.channels);
                        return this.allPlays.filter(play => {
                            return play.requires.every(req => availableKeys.includes(req) && Object.keys(this.availableAccounts[req] || {}).length > 0);
                        });
                    },

                    applyPlay(play) {
                        this.selectedPlay = play;
                        
                        // Set Curve A (preserve asset if channel matches)
                        let assetA = this.curveA.channel === play.config.curveA.channel ? this.curveA.asset : '';
                        this.curveA = { ...play.config.curveA, asset: assetA };
                        
                        // Set Curve B (preserve asset if channel matches)
                        let assetB = this.curveB.channel === play.config.curveB.channel ? this.curveB.asset : '';
                        this.curveB = { ...play.config.curveB, asset: assetB };
                    },

                    transformData(dates, values, level, lag, targetStart, targetEnd) {
                        let resDates = [...dates];
                        let resValues = [...values];

                        // Apply differencing
                        if (level === 'diff1' || level === 'diff2') {
                            let newDates = [];
                            let newVals = [];
                            for (let i = 1; i < resValues.length; i++) {
                                newDates.push(resDates[i]);
                                if (resValues[i] === null || resValues[i-1] === null) {
                                    newVals.push(null);
                                } else {
                                    newVals.push(resValues[i] - resValues[i-1]);
                                }
                            }
                            resDates = newDates;
                            resValues = newVals;
                        }
                        if (level === 'diff2') {
                            let newDates = [];
                            let newVals = [];
                            for (let i = 1; i < resValues.length; i++) {
                                newDates.push(resDates[i]);
                                if (resValues[i] === null || resValues[i-1] === null) {
                                    newVals.push(null);
                                } else {
                                    newVals.push(resValues[i] - resValues[i-1]);
                                }
                            }
                            resDates = newDates;
                            resValues = newVals;
                        }

                        // Apply Lag (Shift)
                        // A positive lag of +2 means today's value is what happened 2 days ago.
                        // So we shift the values array to the right relative to the dates array.
                        let l = parseInt(lag, 10);
                        if (l !== 0) {
                            let shiftedVals = [];
                            for (let i = 0; i < resDates.length; i++) {
                                let sourceIdx = i - l;
                                if (sourceIdx >= 0 && sourceIdx < resValues.length) {
                                    shiftedVals.push(resValues[sourceIdx]);
                                } else {
                                    shiftedVals.push(null);
                                }
                            }
                            resValues = shiftedVals;
                        }

                        // Truncate to target start and end dates
                        let finalDates = [];
                        let finalValues = [];
                        for (let i = 0; i < resDates.length; i++) {
                            if (resDates[i] >= targetStart && resDates[i] <= targetEnd) {
                                finalDates.push(resDates[i]);
                                finalValues.push(resValues[i]);
                            }
                        }

                        // Apply Z-Score normalization over the truncated window
                        if (level === 'zscore') {
                            let validVals = finalValues.filter(v => v !== null);
                            if (validVals.length > 1) {
                                let mean = validVals.reduce((a, b) => a + b, 0) / validVals.length;
                                let variance = validVals.reduce((a, b) => a + Math.pow(b - mean, 2), 0) / validVals.length;
                                let stdDev = Math.sqrt(variance);
                                if (stdDev === 0) stdDev = 1; // avoid division by zero

                                for (let i = 0; i < finalValues.length; i++) {
                                    if (finalValues[i] !== null) {
                                        finalValues[i] = (finalValues[i] - mean) / stdDev;
                                    }
                                }
                            }
                        }

                        return { dates: finalDates, values: finalValues };
                    },

                    calculatePearson(arr1, arr2) {
                        let valid1 = [];
                        let valid2 = [];
                        for (let i = 0; i < arr1.length; i++) {
                            if (arr1[i] !== null && arr2[i] !== null) {
                                valid1.push(arr1[i]);
                                valid2.push(arr2[i]);
                            }
                        }

                        if (valid1.length < 3) return null;

                        const n = valid1.length;
                        const sum1 = valid1.reduce((a, b) => a + b, 0);
                        const sum2 = valid2.reduce((a, b) => a + b, 0);
                        const sum1Sq = valid1.reduce((a, b) => a + b * b, 0);
                        const sum2Sq = valid2.reduce((a, b) => a + b * b, 0);
                        const pSum = valid1.reduce((acc, val, i) => acc + val * valid2[i], 0);

                        const num = pSum - (sum1 * sum2 / n);
                        const den = Math.sqrt((sum1Sq - sum1 * sum1 / n) * (sum2Sq - sum2 * sum2 / n));

                        if (den === 0) return 0;
                        return num / den;
                    },

                    calculateRollingPearson(arr1, arr2, windowSize) {
                        let rolling = [];
                        for (let i = 0; i < arr1.length; i++) {
                            if (i < windowSize - 1) {
                                rolling.push(null);
                            } else {
                                let slice1 = arr1.slice(i - windowSize + 1, i + 1);
                                let slice2 = arr2.slice(i - windowSize + 1, i + 1);
                                rolling.push(this.calculatePearson(slice1, slice2));
                            }
                        }
                        return rolling;
                    },

                    initDashboard() {
                        window.addEventListener('joint-data-loaded', (e) => {
                            // Livewire 3 sometimes passes named params inside e.detail.data, or unnamed as e.detail[0]
                            let payload = e.detail;
                            if (payload && payload[0]) payload = payload[0];
                            if (payload && payload.data) payload = payload.data;
                            
                            this.chartData = payload;
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
                        const coef = this.correlation;
                        if (coef > 0.4) return 'corr-strong-pos';
                        if (coef < -0.4) return 'corr-strong-neg';
                        return 'corr-weak';
                    },

                    getCorrelationIcon() {
                        if (!this.correlation) return '≈';
                        const coef = this.correlation;
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
                        if (this.scatterChartInstance) {
                            this.scatterChartInstance.destroy();
                        }
                        if (this.rollingChartInstance) {
                            this.rollingChartInstance.destroy();
                        }

                        const targetStart = this.chartData.originalStartDate;
                        const targetEnd = this.chartData.originalEndDate;

                        const rawA = this.chartData.curveA;
                        const rawB = this.chartData.curveB;

                        const dataA = this.transformData(rawA.dates, rawA.values, this.curveA.level, this.curveA.lag, targetStart, targetEnd);
                        const dataB = this.transformData(rawB.dates, rawB.values, this.curveB.level, this.curveB.lag, targetStart, targetEnd);

                        const pearson = this.calculatePearson(dataA.values, dataB.values);
                        this.correlation = pearson;

                        let titleA = rawA.name;
                        if (this.curveA.level === 'diff1') titleA = 'Δ ' + titleA;
                        if (this.curveA.level === 'diff2') titleA = 'ΔΔ ' + titleA;
                        if (this.curveA.level === 'zscore') titleA = 'Z-Score ' + titleA;
                        if (parseInt(this.curveA.lag) !== 0) titleA += ` (Lag ${this.curveA.lag})`;

                        let titleB = rawB.name;
                        if (this.curveB.level === 'diff1') titleB = 'Δ ' + titleB;
                        if (this.curveB.level === 'diff2') titleB = 'ΔΔ ' + titleB;
                        if (this.curveB.level === 'zscore') titleB = 'Z-Score ' + titleB;
                        if (parseInt(this.curveB.lag) !== 0) titleB += ` (Lag ${this.curveB.lag})`;

                        this.subtitle = `${titleA} vs ${titleB}`;

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
                                        label: titleA,
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
                                        label: titleB,
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
                                            text: titleA,
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
                                            text: titleB,
                                            color: '#f43f5e',
                                            font: { weight: 'bold' }
                                        }
                                    }
                                }
                            }
                        });

                        // Render Scatter Plot
                        const scatterData = [];
                        for(let i=0; i<dataA.values.length; i++) {
                            if (dataA.values[i] !== null && dataB.values[i] !== null) {
                                scatterData.push({ x: dataA.values[i], y: dataB.values[i] });
                            }
                        }

                        const scatterCtx = document.getElementById("scatterChart").getContext('2d');
                        this.scatterChartInstance = new Chart(scatterCtx, {
                            type: 'scatter',
                            data: {
                                datasets: [{
                                    label: 'Distribution',
                                    data: scatterData,
                                    backgroundColor: '#8b5cf6',
                                    pointRadius: 6,
                                    pointHoverRadius: 8
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: { display: false },
                                    tooltip: {
                                        callbacks: {
                                            label: function(context) {
                                                return `(${context.parsed.x}, ${context.parsed.y})`;
                                            }
                                        }
                                    }
                                },
                                scales: {
                                    x: {
                                        grid: { color: gridColor },
                                        ticks: { color: textColor },
                                        title: {
                                            display: true,
                                            text: titleA,
                                            color: textColor
                                        }
                                    },
                                    y: {
                                        grid: { color: gridColor },
                                        ticks: { color: textColor },
                                        title: {
                                            display: true,
                                            text: titleB,
                                            color: textColor
                                        }
                                    }
                                }
                            }
                        });

                        // Render Rolling Correlation Chart
                        const rollingData = this.calculateRollingPearson(dataA.values, dataB.values, 7);
                        const rollingCtx = document.getElementById("rollingChart").getContext('2d');
                        this.rollingChartInstance = new Chart(rollingCtx, {
                            type: 'line',
                            data: {
                                labels: dataA.dates,
                                datasets: [{
                                    label: '7-Day Rolling Pearson Correlation',
                                    data: rollingData,
                                    borderColor: '#10b981',
                                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                                    borderWidth: 2,
                                    pointRadius: 0,
                                    pointHoverRadius: 5,
                                    tension: 0.3,
                                    fill: true
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                interaction: {
                                    mode: 'index',
                                    intersect: false,
                                },
                                plugins: {
                                    legend: { labels: { color: textColor } }
                                },
                                scales: {
                                    x: {
                                        grid: { color: gridColor },
                                        ticks: { color: textColor }
                                    },
                                    y: {
                                        min: -1,
                                        max: 1,
                                        grid: { color: gridColor },
                                        ticks: { color: textColor }
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
