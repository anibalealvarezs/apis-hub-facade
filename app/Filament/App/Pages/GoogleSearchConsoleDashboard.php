<?php

namespace App\Filament\App\Pages;

use App\Services\RemoteEngineService;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;

class GoogleSearchConsoleDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-magnifying-glass';
    protected static ?string $navigationGroup = 'Search';
    protected static ?string $title = 'Google Search Console';
    protected static string $view = 'filament.app.pages.google-search-console-dashboard';
    protected static ?string $slug = 'google-search-console';

    public ?string $selectedAccount = null;
    public ?string $dateStart = null;
    public ?string $dateEnd = null;

    public array $accounts = [];
    
    // Aggregation results
    public array $summaryData = [];
    public array $previousSummaryData = [];
    public array $chartData = [];
    public array $tableData = [];
    public string $activeTab = 'queries';
    
    public bool $isLoading = false;

    public function mount(): void
    {
        $this->dateEnd = Carbon::now()->subDays(3)->format('Y-m-d');
        $this->dateStart = Carbon::now()->subDays(31)->format('Y-m-d'); // 28 days

        $this->loadAccounts();
    }

    public function loadAccounts(): void
    {
        try {
            $service = app(RemoteEngineService::class);
            $tenant = Filament::getTenant();
            
            $response = $service->listChanneled($tenant, 'google_search_console', 'page');
            
            if (isset($response['data']) && is_array($response['data'])) {
                foreach ($response['data'] as $page) {
                    $this->accounts[$page['id']] = str_replace(['https://', 'http://'], '', rtrim($page['url'] ?? $page['id'], '/'));
                }
                
                if (!empty($this->accounts) && !$this->selectedAccount) {
                    $this->selectedAccount = array_key_first($this->accounts);
                    $this->loadReport();
                }
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("GSC Dashboard Error loading accounts: " . $e->getMessage());
        }
    }

    public function updatedSelectedAccount(): void
    {
        $this->loadReport();
    }

    public function updatedDateStart(): void
    {
        $this->loadReport();
    }

    public function updatedDateEnd(): void
    {
        $this->loadReport();
    }

    public function setActiveTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->loadTabData();
    }

    public function loadReport(): void
    {
        if (!$this->selectedAccount || !$this->dateStart || !$this->dateEnd) {
            return;
        }

        $this->isLoading = true;

        try {
            $service = app(RemoteEngineService::class);
            $tenant = Filament::getTenant();
            
            $start = Carbon::parse($this->dateStart);
            $end = Carbon::parse($this->dateEnd);
            $diff = $end->diffInDays($start) + 1;
            
            $prevEnd = $start->copy()->subDay();
            $prevStart = $prevEnd->copy()->subDays($diff - 1);

            $basePayload = [
                'aggregations' => ['clicks' => 'clicks', 'impressions' => 'impressions', 'ctr' => 'ctr', 'position' => 'position'],
                'filters' => ['page' => $this->selectedAccount, 'dimensions.searchAppearance' => 'standard'],
                'startDate' => $this->dateStart,
                'endDate' => $this->dateEnd,
            ];

            // 1. Summary
            $summaryPayload = array_merge($basePayload, ['groupBy' => []]);
            $summaryRes = $service->aggregateChanneled($tenant, 'google_search_console', 'metric', $summaryPayload);
            $this->summaryData = $summaryRes['data'][0] ?? [];

            // 2. Previous Summary
            $prevPayload = array_merge($summaryPayload, ['startDate' => $prevStart->format('Y-m-d'), 'endDate' => $prevEnd->format('Y-m-d')]);
            $prevRes = $service->aggregateChanneled($tenant, 'google_search_console', 'metric', $prevPayload);
            $this->previousSummaryData = $prevRes['data'][0] ?? [];

            // 3. Chart Data
            $chartPayload = array_merge($basePayload, ['groupBy' => ['daily']]);
            $chartRes = $service->aggregateChanneled($tenant, 'google_search_console', 'metric', $chartPayload);
            $this->chartData = $chartRes['data'] ?? [];
            
            // Dispatch event to re-render chart via Alpine
            $this->dispatch('gsc-chart-updated', data: $this->chartData);

            // 4. Tab Data
            $this->loadTabData();

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("GSC Dashboard Error: " . $e->getMessage());
        }

        $this->isLoading = false;
    }

    public function loadTabData(): void
    {
        if (!$this->selectedAccount || !$this->dateStart || !$this->dateEnd) {
            return;
        }

        try {
            $service = app(RemoteEngineService::class);
            $tenant = Filament::getTenant();

            $basePayload = [
                'aggregations' => ['clicks' => 'clicks', 'impressions' => 'impressions', 'ctr' => 'ctr', 'position' => 'position'],
                'startDate' => $this->dateStart,
                'endDate' => $this->dateEnd,
            ];

            if ($this->activeTab === 'appearances') {
                $basePayload['filters'] = [
                    'page' => $this->selectedAccount,
                    'dimensions.searchAppearance' => ['operator' => 'not_equal', 'value' => 'standard']
                ];
                $basePayload['groupBy'] = ['dimensions.searchAppearance'];
            } else {
                $basePayload['filters'] = [
                    'page' => $this->selectedAccount,
                    'dimensions.searchAppearance' => 'standard'
                ];
                
                $groupByMap = [
                    'queries' => ['query'],
                    'pages' => ['dimensions.page'],
                    'countries' => ['country'],
                    'devices' => ['device'],
                ];
                
                $basePayload['groupBy'] = $groupByMap[$this->activeTab] ?? ['query'];
            }

            $tableRes = $service->aggregateChanneled($tenant, 'google_search_console', 'metric', $basePayload);
            $this->tableData = $tableRes['data'] ?? [];

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("GSC Dashboard Tab Error: " . $e->getMessage());
            $this->tableData = [];
        }
    }
}
