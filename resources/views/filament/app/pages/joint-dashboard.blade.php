<x-filament-panels::page>
    <style>
        .joint-header-row {
            position: sticky;
            top: 0;
            z-index: 30;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-bottom: 30px;
            padding: 1rem 0;
            background: rgba(249, 250, 251, 0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }
        .dark .joint-header-row,
        html.dark .joint-header-row {
            background: rgba(17, 24, 39, 0.85);
            border-bottom-color: rgba(255, 255, 255, 0.05);
        }
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
            background: rgba(255,255,255,0.02);
            border-color: rgba(255,255,255,0.05);
        }

        .joint-form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
        }

        .joint-curve-section {
            border-radius: 12px;
            padding: 20px;
            background: #ffffff;
            border: 1px solid #e5e7eb;
        }
        .dark .joint-curve-section {
            background: rgba(255,255,255,0.03);
            border-color: rgba(255,255,255,0.08);
        }
        .curve-a { border-left: 4px solid #00a7f9; }
        .curve-b { border-left: 4px solid #f43f5e; }

        .chart-container-joint {
            position: relative;
            height: 400px;
            width: 100%;
        }

        .correlation-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 9999px;
            font-weight: 700;
            font-size: 1.25rem;
        }
        .corr-strong-pos { background: rgba(34,197,94,0.1); color: #16a34a; border: 1px solid rgba(34,197,94,0.2); }
        .dark .corr-strong-pos { color: #4ade80; }
        .corr-strong-neg { background: rgba(239,68,68,0.1); color: #dc2626; border: 1px solid rgba(239,68,68,0.2); }
        .dark .corr-strong-neg { color: #f87171; }
        .corr-weak { background: rgba(156,163,175,0.1); color: #4b5563; border: 1px solid rgba(156,163,175,0.2); }
        .dark .corr-weak { color: #9ca3af; }

        .export-btn {
            background-color: #4b5563;
            color: #ffffff;
            font-size: 0.875rem;
            font-weight: 500;
            border-radius: 0.5rem;
            padding: 0.625rem 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background-color 0.2s;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            margin-right: 0.5rem;
        }
        .export-btn:hover { background-color: #374151; }
        .dark .export-btn { background-color: #374151; border: 1px solid rgba(255,255,255,0.1); }
        .dark .export-btn:hover { background-color: #4b5563; }

        .select-warning {
            box-shadow: 0 0 0 2px #f59e0b;
            border-color: #f59e0b !important;
            background-color: #fffbeb !important;
            color: #78350f !important;
        }
        .dark .select-warning {
            background-color: rgba(120, 53, 15, 0.2) !important;
            color: #fef3c7 !important;
        }

        .select-success {
            box-shadow: 0 0 0 2px #22c55e;
            border-color: #22c55e !important;
            background-color: #f0fdf4 !important;
            color: #065f46 !important;
        }
        .dark .select-success {
            background-color: rgba(6, 78, 59, 0.2) !important;
            color: #d1fae5 !important;
        }

        select { color-scheme: light; }
        .dark select { color-scheme: dark; }
        select option { background-color: #ffffff; color: #111827; }
        .dark select option { background-color: #1f2937; color: #f9fafb; }

        @media print {
            .fi-sidebar, .fi-topbar, .fi-header, .export-btn, .joint-header-controls {
                display: none !important;
            }
            html, body, .fi-layout, .fi-main, .fi-main-content {
                height: auto !important;
                min-height: auto !important;
                overflow: visible !important;
                position: static !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            .fi-main {
                width: 100% !important;
                max-width: 100% !important;
            }
            .joint-card, .chart-container-joint, .mt-16 {
                page-break-inside: avoid;
            }
            body { background: white !important; }
        }
    </style>

    <script>
        window.jointConfig = @js($this->getJointConfig());
    </script>

    <div id="joint-dashboard-container" x-data="jointDashboard(Object.assign({
        dateStart: @entangle('dateStart'),
        dateEnd: @entangle('dateEnd')
    }, window.jointConfig || {}))" x-init="initDashboard()">
        <div class="joint-header-row">
            <div>
                <h1 class="joint-header-title">
                    <x-heroicon-o-arrows-right-left class="w-8 h-8 text-primary-500" />
                    {{ __('Cross-Channel Joint Correlations') }}
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    {{ __('Analyze statistical dependencies, lead/lag effects, and z-score normalized cross-channel trends.') }}
                </p>
            </div>
            <div class="joint-header-controls">
                <button type="button" @click="window.print()" class="export-btn">
                    <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5Zm-3 0h.008v.008H15V10.5Z" />
                    </svg>
                    <span>{{ __('Export PDF') }}</span>
                </button>
                <x-ui.date-input x-model.lazy="dateStart" class="w-40" />
                <x-ui.date-input x-model.lazy="dateEnd" max="{{ date('Y-m-d', strtotime('-1 day')) }}" class="w-40" />
                <button type="button" @click="fetchData((a,b,s,e) => $wire.fetchJointData(a,b,s,e))"
                        class="flex items-center justify-center bg-primary-600 hover:bg-primary-500 dark:bg-primary-500 dark:hover:bg-primary-400 text-white text-sm font-medium rounded-lg px-6 py-2.5 transition shadow-sm"
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
                                :class="(selectedPlay && selectedPlay.id === play.id) ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20' : 'border-gray-200 dark:border-white/10 bg-white dark:bg-white/5 hover:border-primary-300 dark:hover:border-primary-500/50'">
                            <div class="font-bold text-sm text-gray-900 dark:text-white" x-text="play.name"></div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1 truncate" x-text="play.short_desc"></div>
                        </button>
                    </template>
                </div>

                <!-- Theory Explanation Callout -->
                <div x-show="selectedPlay" class="mt-4 p-4 rounded-xl bg-blue-50/50 dark:bg-blue-950/20 border border-blue-100 dark:border-blue-900/30 flex items-start gap-3">
                    <x-heroicon-o-light-bulb class="w-5 h-5 text-blue-500 shrink-0 mt-0.5" />
                    <div>
                        <h4 class="font-bold text-blue-900 dark:text-blue-300 text-sm mb-2" x-text="selectedPlay ? selectedPlay.name : ''"></h4>
                        <div class="text-sm text-blue-800 dark:text-blue-200 space-y-2">
                            <p><strong>{{ __('Theory:') }}</strong> <span x-text="selectedPlay ? selectedPlay.theory : ''"></span></p>
                            <p><strong>{{ __('Expected Results:') }}</strong> <span x-text="selectedPlay ? selectedPlay.expected : ''"></span></p>
                            <p class="text-xs italic mt-2 opacity-80">{{ __('Note: Please select your specific assets below to complete the configuration.') }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="joint-form-grid">
                <!-- Curve A -->
                <div class="joint-curve-section curve-a">
                    <h3 class="text-lg font-bold mb-4 flex items-center gap-2" style="color: #00a7f9;">
                        <span class="w-3 h-3 rounded-full bg-[#00a7f9]"></span> {{ __('Curve A (Blue)') }}
                    </h3>
                    <div class="space-y-3">
                        <div>
                            <label class="text-sm font-semibold text-gray-600 dark:text-gray-400">{{ __('Channel') }}</label>
                            <x-ui.asset-selector model="curveA.channel"
                                                 options="channels"
                                                 placeholder="{{ __('Select Channel...') }}"
                                                 change-event="curveA.asset = ''; curveA.metric = ''; if (curveA.channel === curveB.channel) curveB.asset = ''"
                                                 size="sm"
                                                 class="w-full mt-1" />
                        </div>
                        <div x-show="curveA.channel">
                            <label class="text-sm font-semibold text-gray-600 dark:text-gray-400">{{ __('Asset / Property') }}</label>
                            <x-ui.asset-selector model="curveA.asset"
                                                 options="availableAccounts[curveA.channel] || {}"
                                                 placeholder="{{ __('Select Asset...') }}"
                                                 change-event="if (curveA.channel === curveB.channel) curveB.asset = curveA.asset"
                                                 size="sm"
                                                 class="w-full mt-1 transition-colors duration-300"
                                                 x-bind:class="{
                                                     'select-warning': selectedPlay && selectedPlay.id !== 'custom_analysis' && !curveA.asset,
                                                     'select-success': selectedPlay && selectedPlay.id !== 'custom_analysis' && curveA.asset
                                                 }" />
                        </div>
                        <div x-show="curveA.channel">
                            <label class="text-sm font-semibold text-gray-600 dark:text-gray-400">{{ __('Metric') }}</label>
                            <x-ui.asset-selector model="curveA.metric"
                                                 options="metricsDict[curveA.channel] || {}"
                                                 placeholder="{{ __('Select Metric...') }}"
                                                 size="sm"
                                                 class="w-full mt-1" />
                        </div>
                        <div x-show="curveA.channel" class="grid grid-cols-2 gap-3 mt-3">
                            <div>
                                <label class="text-sm font-semibold text-gray-600 dark:text-gray-400">{{ __('Analysis Level') }}</label>
                                <x-ui.asset-selector model="curveA.level"
                                                     options="analysisLevelOptions"
                                                     placeholder="{{ __('Analysis Level') }}"
                                                     change-event="chartRendered && renderChart()"
                                                     size="sm"
                                                     class="w-full mt-1" />
                            </div>
                            <div>
                                <label class="text-sm font-semibold text-gray-600 dark:text-gray-400">{{ __('Lag (Shift)') }}</label>
                                <x-ui.asset-selector model="curveA.lag"
                                                     options="lagOptions"
                                                     placeholder="{{ __('Lag (Shift)') }}"
                                                     change-event="curveB.lag = '0'; chartRendered && renderChart()"
                                                     size="sm"
                                                     class="w-full mt-1" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Curve B -->
                <div class="joint-curve-section curve-b">
                    <h3 class="text-lg font-bold mb-4 flex items-center gap-2" style="color: #f43f5e;">
                        <span class="w-3 h-3 rounded-full bg-[#f43f5e]"></span> {{ __('Curve B (Red)') }}
                    </h3>
                    <div class="space-y-3">
                        <div>
                            <label class="text-sm font-semibold text-gray-600 dark:text-gray-400">{{ __('Channel') }}</label>
                            <x-ui.asset-selector model="curveB.channel"
                                                 options="channels"
                                                 placeholder="{{ __('Select Channel...') }}"
                                                 change-event="curveB.asset = (curveB.channel === curveA.channel) ? curveA.asset : ''; curveB.metric = ''"
                                                 size="sm"
                                                 class="w-full mt-1" />
                        </div>
                        <div x-show="curveB.channel">
                            <label class="text-sm font-semibold text-gray-600 dark:text-gray-400">{{ __('Asset / Property') }}</label>
                            <x-ui.asset-selector model="curveB.asset"
                                                 options="availableAccounts[curveB.channel] || {}"
                                                 placeholder="{{ __('Select Asset...') }}"
                                                 size="sm"
                                                 class="w-full mt-1 transition-colors duration-300"
                                                 x-bind:disabled="curveA.channel && curveB.channel && curveA.channel === curveB.channel"
                                                 x-bind:class="{
                                                     'opacity-50 cursor-not-allowed': curveA.channel && curveB.channel && curveA.channel === curveB.channel,
                                                     'select-warning': selectedPlay && selectedPlay.id !== 'custom_analysis' && !curveB.asset && curveA.channel !== curveB.channel,
                                                     'select-success': selectedPlay && selectedPlay.id !== 'custom_analysis' && curveB.asset && curveA.channel !== curveB.channel
                                                 }" />
                        </div>
                        <div x-show="curveB.channel">
                            <label class="text-sm font-semibold text-gray-600 dark:text-gray-400">{{ __('Metric') }}</label>
                            <x-ui.asset-selector model="curveB.metric"
                                                 options="metricsDict[curveB.channel] || {}"
                                                 placeholder="{{ __('Select Metric...') }}"
                                                 size="sm"
                                                 class="w-full mt-1" />
                        </div>
                        <div x-show="curveB.channel" class="grid grid-cols-2 gap-3 mt-3">
                            <div>
                                <label class="text-sm font-semibold text-gray-600 dark:text-gray-400">{{ __('Analysis Level') }}</label>
                                <x-ui.asset-selector model="curveB.level"
                                                     options="analysisLevelOptions"
                                                     placeholder="{{ __('Analysis Level') }}"
                                                     change-event="chartRendered && renderChart()"
                                                     size="sm"
                                                     class="w-full mt-1" />
                            </div>
                            <div>
                                <label class="text-sm font-semibold text-gray-600 dark:text-gray-400">{{ __('Lag (Shift)') }}</label>
                                <x-ui.asset-selector model="curveB.lag"
                                                     options="lagOptions"
                                                     placeholder="{{ __('Lag (Shift)') }}"
                                                     change-event="curveA.lag = '0'; chartRendered && renderChart()"
                                                     size="sm"
                                                     class="w-full mt-1" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="joint-card" x-show="chartRendered" style="display: none;">
            <div x-show="isLoading" class="absolute inset-0 z-10 flex items-center justify-center bg-white/50 dark:bg-black/50 backdrop-blur-sm rounded-xl">
                <div class="flex flex-col items-center">
                    <svg class="animate-spin h-10 w-10 text-primary-600 mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('Processing Joint Curves & Correlation...') }}</span>
                </div>
            </div>
            
            <div class="flex justify-between items-start mb-10">
                <div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-2">{{ __('Comparison View') }}</h2>
                    <p class="text-md font-semibold text-gray-600 dark:text-gray-300 mb-4" x-text="subtitle"></p>
                    <p class="text-sm text-gray-500 dark:text-gray-400 leading-relaxed max-w-3xl">
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
            
            <div class="border-t border-gray-200 dark:border-white/10 pt-12 pb-4">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4 mt-6">{{ __('Rolling Correlation (7-Day Window)') }}</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-10 max-w-4xl leading-relaxed">
                    {{ __('Shows how the Pearson correlation between the two metrics evolves day by day. A drop to zero indicates the day a relationship broke (e.g., ad fatigue or an algorithm update).') }}
                </p>
                <div class="chart-container-joint pb-8" style="height: 250px;">
                    <canvas id="rollingChart"></canvas>
                </div>
            </div>

            <div class="border-t border-gray-200 dark:border-white/10 pt-12 pb-4">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4 mt-6">{{ __('Scatter Plot (Correlation Distribution)') }}</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-10 max-w-4xl leading-relaxed">
                    {{ __('Removes the element of time. Helps identify non-linear relationships, data clustering, or points of diminishing returns (where higher values on the X axis stop producing higher values on the Y axis).') }}
                </p>
                <div class="chart-container-joint" style="height: 350px;">
                    <canvas id="scatterChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
