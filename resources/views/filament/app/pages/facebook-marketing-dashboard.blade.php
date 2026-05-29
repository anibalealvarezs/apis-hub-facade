<x-filament-panels::page>
    <style>
        :root {
            --fb-spend: #10b981;
            --fb-impr: #6366f1;
            --fb-clicks: #0ea5e9;
            --fb-ctr: #8b5cf6;
            --fb-cpc: #f59e0b;
            --fb-roas: #ec4899;
            --fb-purchases: #14b8a6;
            
            --fb-text-main: #111827;
            --fb-text-dim: #6b7280;
            --fb-bg-card: rgba(0,0,0,0.02);
            --fb-border: rgba(0,0,0,0.06);
            --fb-bg-hover: rgba(0,0,0,0.04);
            --fb-bg-active: rgba(0,0,0,0.06);
            --fb-chart-grid: rgba(0, 0, 0, 0.05);
            --fb-chart-ticks: #6b7280;
        }

        .dark {
            --fb-text-main: #ffffff;
            --fb-text-dim: #94a3b8;
            --fb-bg-card: rgba(255,255,255,0.03);
            --fb-border: rgba(255,255,255,0.05);
            --fb-bg-hover: rgba(255,255,255,0.05);
            --fb-bg-active: rgba(255,255,255,0.08);
            --fb-chart-grid: rgba(255, 255, 255, 0.05);
            --fb-chart-ticks: #94a3b8;
        }

        .fb-header-row { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 30px; }
        .fb-header-title { font-size: 1.8rem; font-weight: 800; color: var(--fb-text-main); margin-bottom: 5px; display: flex; align-items: center; gap: 12px; }
        .fb-header-subtitle { color: var(--fb-text-dim); font-size: 0.9rem; }
        .fb-header-controls { display: flex; align-items: center; gap: 15px; margin-bottom: 0; }
        
        .metrics-grid-fb { display: grid; grid-template-columns: repeat(7, 1fr); gap: 12px; margin-bottom: 25px; }

        .card-stat-fb {
            background: var(--fb-bg-card);
            border: 1px solid var(--fb-border);
            border-bottom: 3px solid var(--color, transparent);
            border-radius: 10px;
            padding: 16px;
            cursor: pointer;
            transition: all 0.2s ease;
            opacity: 0.6;
        }
        .card-stat-fb:hover { background: var(--fb-bg-hover); opacity: 0.8; }
        .card-stat-fb.active { opacity: 1; border-bottom-color: var(--color); background: var(--fb-bg-active); }

        .fb-label { font-size: 0.65rem; font-weight: 700; color: var(--fb-text-dim); text-transform: uppercase; margin-bottom: 8px; letter-spacing: 0.05em; }
        .card-metric-value { font-size: 1.6rem; font-weight: 800; color: var(--fb-text-main); line-height: 1.2; }
        
        .chart-container-fb { 
            background: var(--fb-bg-card); 
            border: 1px solid var(--fb-border); 
            border-radius: 12px; 
            padding: 25px; 
            margin-bottom: 30px; 
            height: 400px; 
            position: relative;
        }

        .fb-table-container { 
            background: var(--fb-bg-card); 
            border: 1px solid var(--fb-border); 
            border-radius: 12px; 
            overflow: hidden;
            margin-top: 20px;
        }

        .fb-table { width: 100%; border-collapse: collapse; text-align: left; }
        .fb-table th { padding: 12px 20px; font-size: 0.75rem; text-transform: uppercase; color: var(--fb-text-dim); font-weight: 700; border-bottom: 1px solid var(--fb-border); background: var(--fb-bg-active); }
        .fb-table td { padding: 12px 20px; border-bottom: 1px solid var(--fb-border); vertical-align: middle; font-size: 0.9rem; color: var(--fb-text-main); }
        .fb-table tr:hover { background: var(--fb-bg-hover); }
        
        .metric-cell { text-align: right; }
        
        .fb-status-active { display: inline-block; width: 8px; height: 8px; border-radius: 50%; background-color: #10b981; margin-right: 6px; }
        .fb-status-paused { display: inline-block; width: 8px; height: 8px; border-radius: 50%; background-color: #94a3b8; margin-right: 6px; }
    </style>

    <div x-data="fbDashboard()" x-init="initDashboard()">
        <div class="fb-header-row">
            <div>
                <h1 class="fb-header-title">
                    <x-heroicon-o-presentation-chart-line class="w-8 h-8 text-[#1877F2]" />
                    Facebook Marketing
                </h1>
                <p class="fb-header-subtitle">Meta Ads Manager Mockup Data</p>
            </div>
            <div class="fb-header-controls">
                <button type="button" class="flex items-center justify-center bg-primary-600 hover:bg-primary-500 text-white text-sm font-medium rounded-lg px-4 py-2.5 transition duration-75 shadow-sm">
                    <x-heroicon-o-arrow-path class="w-5 h-5 mr-2" />
                    <span>Refresh</span>
                </button>
                <select wire:model.live="selectedAccount" class="bg-white dark:bg-white/5 border border-gray-300 dark:border-white/10 text-gray-950 dark:text-white text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5">
                    @foreach($accounts as $id => $name)
                        <option value="{{ $id }}" class="bg-white dark:bg-gray-800">{{ $name }} ({{ $id }})</option>
                    @endforeach
                </select>
                <input type="date" x-model.lazy="dateStart" class="bg-white dark:bg-white/5 border border-gray-300 dark:border-white/10 text-gray-950 dark:text-white text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-40 p-2.5">
                <input type="date" x-model.lazy="dateEnd" class="bg-white dark:bg-white/5 border border-gray-300 dark:border-white/10 text-gray-950 dark:text-white text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-40 p-2.5">
            </div>
        </div>

        <div class="metrics-grid-fb">
            <div class="card-stat-fb active" style="--color: var(--fb-spend);">
                <div class="fb-label">Amount Spent</div>
                <div class="card-metric-value">$12,450.00</div>
            </div>
            <div class="card-stat-fb" style="--color: var(--fb-impr);">
                <div class="fb-label">Impressions</div>
                <div class="card-metric-value">1.2M</div>
            </div>
            <div class="card-stat-fb active" style="--color: var(--fb-clicks);">
                <div class="fb-label">Link Clicks</div>
                <div class="card-metric-value">45,210</div>
            </div>
            <div class="card-stat-fb" style="--color: var(--fb-ctr);">
                <div class="fb-label">CTR (Link)</div>
                <div class="card-metric-value">3.75%</div>
            </div>
            <div class="card-stat-fb" style="--color: var(--fb-cpc);">
                <div class="fb-label">CPC (Link)</div>
                <div class="card-metric-value">$0.28</div>
            </div>
            <div class="card-stat-fb active" style="--color: var(--fb-purchases);">
                <div class="fb-label">Purchases</div>
                <div class="card-metric-value">1,204</div>
            </div>
            <div class="card-stat-fb" style="--color: var(--fb-roas);">
                <div class="fb-label">ROAS</div>
                <div class="card-metric-value">4.2x</div>
            </div>
        </div>

        <div class="chart-container-fb flex items-center justify-center">
            <div class="text-center text-gray-500 dark:text-gray-400">
                <x-heroicon-o-chart-bar class="w-16 h-16 mx-auto mb-4 opacity-50" />
                <p>Chart visualization placeholder.</p>
                <p class="text-sm mt-2 opacity-75">Select metrics above to map them here.</p>
            </div>
        </div>

        <div class="fb-table-container overflow-x-auto">
            <table class="fb-table">
                <thead>
                    <tr>
                        <th>Campaign Name</th>
                        <th>Delivery</th>
                        <th class="metric-cell">Amount Spent</th>
                        <th class="metric-cell">Impressions</th>
                        <th class="metric-cell">Link Clicks</th>
                        <th class="metric-cell">Purchases</th>
                        <th class="metric-cell">ROAS</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="font-medium">Retargeting_Q3_All_Audiences</td>
                        <td><span class="fb-status-active"></span>Active</td>
                        <td class="metric-cell">$3,240.50</td>
                        <td class="metric-cell">240,120</td>
                        <td class="metric-cell">12,450</td>
                        <td class="metric-cell">450</td>
                        <td class="metric-cell">5.1x</td>
                    </tr>
                    <tr>
                        <td class="font-medium">Prospecting_Lookalike_1%_US</td>
                        <td><span class="fb-status-active"></span>Active</td>
                        <td class="metric-cell">$8,100.00</td>
                        <td class="metric-cell">850,000</td>
                        <td class="metric-cell">28,100</td>
                        <td class="metric-cell">620</td>
                        <td class="metric-cell">3.8x</td>
                    </tr>
                    <tr>
                        <td class="font-medium">Promo_Flash_Sale_48H</td>
                        <td><span class="fb-status-paused"></span>Completed</td>
                        <td class="metric-cell">$1,109.50</td>
                        <td class="metric-cell">110,000</td>
                        <td class="metric-cell">4,660</td>
                        <td class="metric-cell">134</td>
                        <td class="metric-cell">4.8x</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        (function () {
            const registerFbDashboard = () => {
                Alpine.data('fbDashboard', () => {
                    return {
                        account: @entangle('selectedAccount'),
                        dateStart: @entangle('dateStart'),
                        dateEnd: @entangle('dateEnd'),
                        initDashboard() {
                            // Init logic for when we implement the actual data binding
                        }
                    }
                });
            };

            if (window.Alpine) {
                registerFbDashboard();
            } else {
                document.addEventListener('alpine:init', registerFbDashboard);
            }
        })();
    </script>
</x-filament-panels::page>
