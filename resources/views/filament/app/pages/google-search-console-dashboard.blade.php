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

    <!-- Global Loader (kept for full page sync if needed, but we'll use section loaders mostly) -->
    <div wire:loading wire:target="loadAccounts" class="absolute inset-0 z-50 flex items-center justify-center bg-white/80 dark:bg-gray-900/50 backdrop-blur-sm rounded-xl">
        <div class="flex flex-col items-center">
            <x-filament::loading-indicator class="h-8 w-8 text-primary-500" />
            <span class="mt-2 text-sm font-medium text-gray-900 dark:text-white">Loading Accounts...</span>
        </div>
    </div>

    <div class="gsc-header-row">
        <div>
            <h1 class="gsc-header-title">
                <x-heroicon-o-magnifying-glass class="w-8 h-8 text-[#4285f4]" />
                Google Search Console
            </h1>
            <p class="gsc-header-subtitle">Performance on Google Search results</p>
        </div>
        <div class="gsc-header-controls">
            <!-- Account Selector -->
            <select wire:model.live="selectedAccount" class="bg-white dark:bg-white/5 border border-gray-300 dark:border-white/10 text-gray-950 dark:text-white text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 transition duration-75 shadow-sm">
                <option value="" class="bg-white dark:bg-gray-800">Select Property...</option>
                @foreach($accounts as $id => $url)
                    <option value="{{ $id }}" class="bg-white dark:bg-gray-800">{{ $url }}</option>
                @endforeach
            </select>

            <!-- Date Range -->
            <div class="flex items-center gap-2">
                <input type="date" wire:model.live="dateStart" class="bg-white dark:bg-white/5 border border-gray-300 dark:border-white/10 text-gray-950 dark:text-white text-sm rounded-lg p-2.5 shadow-sm">
                <span class="text-gray-500 dark:text-gray-400 font-medium">to</span>
                <input type="date" wire:model.live="dateEnd" class="bg-white dark:bg-white/5 border border-gray-300 dark:border-white/10 text-gray-950 dark:text-white text-sm rounded-lg p-2.5 shadow-sm">
            </div>
            
            <x-filament::button wire:click="loadReport" icon="heroicon-o-arrow-path">
                Update
            </x-filament::button>
        </div>
    </div>

    @php
        function calcTrend($current, $previous, $isPos = false) {
            if (!$previous) return ['val' => '--', 'color' => '#94a3b8', 'icon' => ''];
            
            if ($isPos) {
                $diff = $previous - $current;
                $pct = $previous > 0 ? ($diff / $previous) * 100 : 0;
            } else {
                $diff = $current - $previous;
                $pct = $previous > 0 ? ($diff / $previous) * 100 : 0;
            }
            
            $isPositive = $diff > 0;
            $color = $isPositive ? '#22C55E' : '#EF4444';
            $sign = $diff > 0 ? '+' : '';
            $icon = $isPositive ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down';
            
            return ['val' => $sign . number_format($pct, 1) . '%', 'color' => $color, 'icon' => $icon];
        }

        $cClicks = (int)($summaryData['clicks'] ?? 0);
        $pClicks = (int)($previousSummaryData['clicks'] ?? 0);
        $tClicks = calcTrend($cClicks, $pClicks);

        $cImps = (int)($summaryData['impressions'] ?? 0);
        $pImps = (int)($previousSummaryData['impressions'] ?? 0);
        $tImps = calcTrend($cImps, $pImps);

        $cCtr = (float)($summaryData['ctr'] ?? 0);
        $pCtr = (float)($previousSummaryData['ctr'] ?? 0);
        $tCtr = calcTrend($cCtr, $pCtr);

        $cPos = (float)($summaryData['position'] ?? 0);
        $pPos = (float)($previousSummaryData['position'] ?? 0);
        $tPos = calcTrend($cPos, $pPos, true);
    @endphp

    <div class="metrics-grid-gsc relative" x-data="{ activeMetrics: { clicks: true, impressions: true, ctr: false, position: false } }">
        <div wire:loading wire:target="loadReport, selectedAccount, dateStart, dateEnd" class="absolute inset-0 z-10 flex items-center justify-center bg-white/50 dark:bg-gray-900/50 backdrop-blur-sm rounded-xl">
            <x-filament::loading-indicator class="h-8 w-8 text-primary-500" />
        </div>
        <div class="card-stat-gsc" :class="activeMetrics.clicks ? 'active' : ''" @click="activeMetrics.clicks = !activeMetrics.clicks; window.dispatchEvent(new CustomEvent('toggle-metric', {detail: 'clicks'}))" style="--color: #4285f4;">
            <div class="gsc-label">Total Clicks</div>
            <div class="card-metric-value">{{ number_format($cClicks) }}</div>
            <div class="card-metric-trend" style="color: {{ $tClicks['color'] }}">
                @if($tClicks['icon']) <x-dynamic-component :component="$tClicks['icon']" class="w-4 h-4" /> @endif
                {{ $tClicks['val'] }}
            </div>
        </div>
        <div class="card-stat-gsc" :class="activeMetrics.impressions ? 'active' : ''" @click="activeMetrics.impressions = !activeMetrics.impressions; window.dispatchEvent(new CustomEvent('toggle-metric', {detail: 'impressions'}))" style="--color: #7e57c2;">
            <div class="gsc-label">Total Impressions</div>
            <div class="card-metric-value">{{ number_format($cImps) }}</div>
            <div class="card-metric-trend" style="color: {{ $tImps['color'] }}">
                @if($tImps['icon']) <x-dynamic-component :component="$tImps['icon']" class="w-4 h-4" /> @endif
                {{ $tImps['val'] }}
            </div>
        </div>
        <div class="card-stat-gsc" :class="activeMetrics.ctr ? 'active' : ''" @click="activeMetrics.ctr = !activeMetrics.ctr; window.dispatchEvent(new CustomEvent('toggle-metric', {detail: 'ctr'}))" style="--color: #0097a7;">
            <div class="gsc-label">Average CTR</div>
            <div class="card-metric-value">{{ number_format($cCtr * 100, 2) }}%</div>
            <div class="card-metric-trend" style="color: {{ $tCtr['color'] }}">
                @if($tCtr['icon']) <x-dynamic-component :component="$tCtr['icon']" class="w-4 h-4" /> @endif
                {{ $tCtr['val'] }}
            </div>
        </div>
        <div class="card-stat-gsc" :class="activeMetrics.position ? 'active' : ''" @click="activeMetrics.position = !activeMetrics.position; window.dispatchEvent(new CustomEvent('toggle-metric', {detail: 'position'}))" style="--color: #f4511e;">
            <div class="gsc-label">Average Position</div>
            <div class="card-metric-value">{{ number_format($cPos, 1) }}</div>
            <div class="card-metric-trend" style="color: {{ $tPos['color'] }}">
                @if($tPos['icon']) <x-dynamic-component :component="$tPos['icon']" class="w-4 h-4" /> @endif
                {{ $tPos['val'] }}
            </div>
        </div>
    </div>

    <!-- Chart -->
    <div class="chart-container-gsc relative" x-data="gscChart(@js($chartData))" x-init="initChart()" @gsc-chart-updated.window="updateChart($event.detail.data)">
        <div wire:loading wire:target="loadReport, selectedAccount, dateStart, dateEnd" class="absolute inset-0 z-10 flex items-center justify-center bg-white/50 dark:bg-gray-900/50 backdrop-blur-sm rounded-xl">
            <x-filament::loading-indicator class="h-8 w-8 text-primary-500" />
        </div>
        <canvas x-ref="canvas"></canvas>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('gscChart', (initialData = []) => {
                let chartInstance = null; // Store outside Alpine reactive proxy
                
                return {
                    activeMetrics: { clicks: true, impressions: true, ctr: false, position: false },
                    
                    initChart() {
                        const ctx = this.$refs.canvas.getContext('2d');
                        
                        const config = {
                            type: 'line',
                            data: { labels: [], datasets: [] },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                interaction: {mode: 'index', intersect: false},
                                scales: {
                                    yClicks: { display: true, position: 'left', ticks: { color: '#4285f4' } },
                                    yImpressions: { display: false, position: 'left', grid: {display: false}, ticks: { color: '#7e57c2' } },
                                    yPct: { display: false, position: 'right', grid: {display: false}, ticks: { color: '#0097a7', callback: (v) => v + '%' } },
                                    yPos: { display: false, position: 'right', grid: {display: false}, reverse: true, ticks: { color: '#f4511e' } },
                                    x: { grid: {display: false}, ticks: { color: '#8B949E' } },
                                },
                                plugins: { legend: { display: false } }
                            }
                        };
                        
                        chartInstance = new Chart(ctx, config);

                        window.addEventListener('toggle-metric', (e) => {
                            this.activeMetrics[e.detail] = !this.activeMetrics[e.detail];
                            this.refreshVisibility();
                        });

                        // Run initial render if we have data
                        if (initialData && initialData.length > 0) {
                            this.updateChart(initialData);
                        }
                    },
                    
                    updateChart(data) {
                        if (!chartInstance || !data) return;
                        
                        const sortedData = [...data].sort((a, b) => new Date(a.daily).getTime() - new Date(b.daily).getTime());
                        
                        chartInstance.data.labels = sortedData.map(d => {
                            const date = new Date(d.daily);
                            // Adjust for timezone offset to prevent off-by-one day issues
                            const userTimezoneOffset = date.getTimezoneOffset() * 60000;
                            const adjustedDate = new Date(date.getTime() + userTimezoneOffset);
                            return adjustedDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                        });
                        chartInstance.data.datasets = [
                            { label: "Clicks", data: sortedData.map(d => d.clicks), borderColor: '#4285f4', backgroundColor: 'rgba(66, 133, 244, 0.1)', fill: true, tension: 0.3, yAxisID: 'yClicks', hidden: !this.activeMetrics.clicks },
                            { label: "Impressions", data: sortedData.map(d => d.impressions), borderColor: '#7e57c2', backgroundColor: 'rgba(126, 87, 194, 0.1)', fill: true, tension: 0.3, yAxisID: 'yImpressions', hidden: !this.activeMetrics.impressions },
                            { label: "CTR", data: sortedData.map(d => (d.ctr * 100).toFixed(2)), borderColor: '#0097a7', backgroundColor: 'rgba(0, 151, 167, 0.1)', fill: true, tension: 0.3, yAxisID: 'yPct', hidden: !this.activeMetrics.ctr },
                            { label: "Position", data: sortedData.map(d => parseFloat(d.position).toFixed(1)), borderColor: '#f4511e', backgroundColor: 'rgba(244, 81, 30, 0.1)', fill: true, tension: 0.3, yAxisID: 'yPos', hidden: !this.activeMetrics.position },
                        ];
                        
                        this.refreshVisibility();
                    },

                    refreshVisibility() {
                        if (!chartInstance) return;
                        const scales = chartInstance.options.scales;
                        scales.yClicks.display = this.activeMetrics.clicks;
                        scales.yImpressions.display = this.activeMetrics.impressions;
                        scales.yPct.display = this.activeMetrics.ctr;
                        scales.yPos.display = this.activeMetrics.position;
                        chartInstance.update();
                    }
                };
            });
        });
    </script>

    <!-- Breakdown Table -->
    <div class="gsc-table-container relative">
        <div wire:loading wire:target="loadReport, loadTabData, selectedAccount, dateStart, dateEnd, setActiveTab" class="absolute inset-0 z-10 flex items-center justify-center bg-white/50 dark:bg-gray-900/50 backdrop-blur-sm rounded-xl">
            <x-filament::loading-indicator class="h-8 w-8 text-primary-500" />
        </div>
        <div class="tab-nav-gsc">
            <div class="tab-gsc {{ $activeTab === 'queries' ? 'active' : '' }}" wire:click="setActiveTab('queries')">QUERIES</div>
            <div class="tab-gsc {{ $activeTab === 'pages' ? 'active' : '' }}" wire:click="setActiveTab('pages')">PAGES</div>
            <div class="tab-gsc {{ $activeTab === 'countries' ? 'active' : '' }}" wire:click="setActiveTab('countries')">COUNTRIES</div>
            <div class="tab-gsc {{ $activeTab === 'devices' ? 'active' : '' }}" wire:click="setActiveTab('devices')">DEVICES</div>
            <div class="tab-gsc {{ $activeTab === 'appearances' ? 'active' : '' }}" wire:click="setActiveTab('appearances')">SEARCH APPEARANCE</div>
        </div>

        <div class="overflow-x-auto">
            <table class="gsc-table">
                <thead>
                    <tr>
                        <th>
                            @if($activeTab === 'queries') Search Query
                            @elseif($activeTab === 'pages') Page URL
                            @elseif($activeTab === 'countries') Country
                            @elseif($activeTab === 'devices') Device
                            @else Search Appearance @endif
                        </th>
                        <th class="text-right">Clicks</th>
                        <th class="text-right">Impressions</th>
                        <th class="text-right">CTR</th>
                        <th class="text-right">Position</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $maxClicks = collect($tableData)->max('clicks') ?: 1;
                        $maxImps = collect($tableData)->max('impressions') ?: 1;
                    @endphp
                    
                    @forelse($tableData as $row)
                        @php
                            $dimKey = 'query';
                            if ($activeTab === 'pages') $dimKey = 'dimensions.page';
                            elseif ($activeTab === 'countries') $dimKey = 'country';
                            elseif ($activeTab === 'devices') $dimKey = 'device';
                            elseif ($activeTab === 'appearances') $dimKey = 'dimensions.searchAppearance';
                            
                            $dimVal = \Illuminate\Support\Arr::get($row, $dimKey, 'Unknown');
                            
                            if (in_array($dimVal, [null, 'unknown', 'UNK', 'null'], true)) continue;
                            
                            $clicks = (int)($row['clicks'] ?? 0);
                            $imps = (int)($row['impressions'] ?? 0);
                            $ctr = (float)($row['ctr'] ?? 0);
                            $pos = (float)($row['position'] ?? 0);
                            
                            $cPct = ($clicks / $maxClicks) * 100;
                            $iPct = ($imps / $maxImps) * 100;
                        @endphp
                        <tr>
                            <td>
                                @if($activeTab === 'pages')
                                    <div class="flex items-center gap-2">
                                        <span class="gsc-url-text" title="{{ $dimVal }}">{{ $dimVal }}</span>
                                        <a href="{{ $dimVal }}" target="_blank" class="text-[#4285f4] hover:text-white transition">
                                            <x-heroicon-o-arrow-top-right-on-square class="w-4 h-4" />
                                        </a>
                                    </div>
                                @else
                                    <span class="gsc-url-text" title="{{ $dimVal }}">{{ $dimVal }}</span>
                                @endif
                            </td>
                            <td class="metric-cell">
                                <div class="metric-val-main">{{ number_format($clicks) }}</div>
                                <div class="progress-bar-container"><div class="progress-bar-fill bg-[#4285f4]" style="width: {{ $cPct }}%;"></div></div>
                            </td>
                            <td class="metric-cell">
                                <div class="metric-val-main">{{ number_format($imps) }}</div>
                                <div class="progress-bar-container"><div class="progress-bar-fill bg-[#7e57c2]" style="width: {{ $iPct }}%;"></div></div>
                            </td>
                            <td class="metric-cell metric-val-main">{{ number_format($ctr * 100, 2) }}%</td>
                            <td class="metric-cell metric-val-main">{{ number_format($pos, 1) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-8 text-gray-500">No data available for this dimension.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @php
        throw new \Exception("DEBUG DUMP (BLADE): Blade Template Rendered Successfully! The 30s hang is strictly happening in Livewire Serialization or Web Server network transit!");
    @endphp
</x-filament-panels::page>
