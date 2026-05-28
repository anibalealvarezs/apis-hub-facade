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
            throw $e; // FORCE dump to screen!
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
        $this->loadReport();
    }

    public function dehydrate()
    {
        // Deliberately empty. All heavy data is cleared in loadReport to prevent Livewire from hanging during snapshot generation.
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
            $diff = $start->diffInDays($end) + 1;
            
            $prevEnd = $start->copy()->subDay();
            $prevStart = $prevEnd->copy()->subDays($diff - 1);

            // 1. Summary
            $payloads['summary'] = [
                'aggregations' => ['clicks' => 'clicks', 'impressions' => 'impressions', 'ctr' => 'ctr', 'position' => 'position'],
                'groupBy' => [],
                'filters' => [
                    'page' => (string)$this->selectedAccount,
                    'dimensions.searchAppearance' => 'standard'
                ],
                'startDate' => $this->dateStart,
                'endDate' => $this->dateEnd,
            ];

            // 2. Previous Summary
            $payloads['previous'] = [
                'aggregations' => ['clicks' => 'clicks', 'impressions' => 'impressions', 'ctr' => 'ctr', 'position' => 'position'],
                'groupBy' => [],
                'filters' => [
                    'page' => (string)$this->selectedAccount,
                    'dimensions.searchAppearance' => 'standard'
                ],
                'startDate' => $prevStart->format('Y-m-d'),
                'endDate' => $prevEnd->format('Y-m-d'),
            ];

            // 3. Chart Data
            $payloads['chart'] = [
                'aggregations' => ['clicks' => 'clicks', 'impressions' => 'impressions', 'ctr' => 'ctr', 'position' => 'position'],
                'groupBy' => ['daily'],
                'filters' => [
                    'page' => (string)$this->selectedAccount,
                    'dimensions.searchAppearance' => 'standard'
                ],
                'startDate' => $this->dateStart,
                'endDate' => $this->dateEnd,
            ];

            // 4. Tab Data
            $tabPayload = [
                'aggregations' => ['clicks' => 'clicks', 'impressions' => 'impressions', 'ctr' => 'ctr', 'position' => 'position'],
            ];

            if ($this->activeTab === 'appearances') {
                $tabPayload['groupBy'] = ['dimensions.searchAppearance'];
                $tabPayload['filters'] = [
                    'page' => (string)$this->selectedAccount,
                    'dimensions.searchAppearance' => ['operator' => 'not_equal', 'value' => 'standard']
                ];
            } else {
                $groupByMap = [
                    'queries' => ['query'],
                    'pages' => ['dimensions.page'],
                    'countries' => ['country'],
                    'devices' => ['device'],
                ];
                $tabPayload['groupBy'] = $groupByMap[$this->activeTab] ?? ['query'];
                $tabPayload['filters'] = [
                    'page' => (string)$this->selectedAccount,
                    'dimensions.searchAppearance' => 'standard'
                ];
            }

            $tabPayload['startDate'] = $this->dateStart;
            $tabPayload['endDate'] = $this->dateEnd;

            $payloads['table'] = $tabPayload;

            $startApi = microtime(true);
            
            // Set a strict 3-second timeout so if it hangs, it throws an exception immediately instead of 408
            config(['apis-hub-api.timeout' => 3]);
            
            $results = $service->aggregateChanneledPool($tenant, 'google_search_console', 'metric', $payloads);
            $apiDuration = microtime(true) - $startApi;
            
            // Exception removed from here

            $this->summaryData = $results['summary']['data'][0] ?? [];
            $this->previousSummaryData = $results['previous']['data'][0] ?? [];
            $this->chartData = $results['chart']['data'] ?? [];
            
            $this->chartData = $results['chart']['data'] ?? [];
            
            // Dispatch events to re-render chart and table via Alpine.js on the frontend.
            // CRITICAL: We MUST json_encode() the arrays to strings before dispatching!
            // Livewire 3 deeply traverses arrays in event payloads to look for models/wireables.
            // By passing a string, we bypass the 30-second CPU hang completely.
            $this->dispatch('gsc-chart-updated', dataJson: json_encode($this->chartData));
            $this->chartData = []; // CRUCIAL: Clear chartData so Livewire doesn't recursively serialize it!
            
            $this->dispatch('gsc-table-updated', dataJson: json_encode($results['table']['data'] ?? []), tab: $this->activeTab);
            $this->tableData = [];

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("GSC Dashboard Error: " . $e->getMessage());
            $this->addError('api', "API Error: " . $e->getMessage());
            // RE-THROW so you can physically see the exception on screen!
            throw $e;
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
            ];

            if ($this->activeTab === 'appearances') {
                $basePayload['groupBy'] = ['dimensions.searchAppearance'];
                $basePayload['filters'] = [
                    'page' => (string)$this->selectedAccount,
                    'dimensions.searchAppearance' => ['operator' => 'not_equal', 'value' => 'standard']
                ];
            } else {
                $groupByMap = [
                    'queries' => ['query'],
                    'pages' => ['dimensions.page'],
                    'countries' => ['country'],
                    'devices' => ['device'],
                ];
                
                $basePayload['groupBy'] = $groupByMap[$this->activeTab] ?? ['query'];
                $basePayload['filters'] = [
                    'page' => (string)$this->selectedAccount,
                    'dimensions.searchAppearance' => 'standard'
                ];
            }

            $basePayload['startDate'] = $this->dateStart;
            $basePayload['endDate'] = $this->dateEnd;

            $startHttp = microtime(true);
            $tableRes = $service->aggregateChanneled($tenant, 'google_search_console', 'metric', $basePayload);
            $httpDuration = microtime(true) - $startHttp;
            
            // Limit table data to 250 rows to prevent Livewire snapshot serialization from taking 30+ seconds
            $this->tableData = array_slice($tableRes['data'] ?? [], 0, 250);

        } catch (\Exception $e) {
            throw $e; // FORCE dump to screen!
        }
    }
}
