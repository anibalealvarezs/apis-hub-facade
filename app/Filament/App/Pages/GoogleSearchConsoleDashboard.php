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
    public string $activeTab = 'queries';

    public function mount(): void
    {
        $this->dateEnd = Carbon::now()->subDays(3)->format('Y-m-d');
        $this->dateStart = Carbon::now()->subDays(31)->format('Y-m-d');

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
                }
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("GSC Accounts Error: " . $e->getMessage());
        }
    }

    public function setActiveTab(string $tab): void
    {
        $this->activeTab = $tab;
    }
}
