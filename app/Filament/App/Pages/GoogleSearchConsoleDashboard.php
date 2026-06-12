<?php

namespace App\Filament\App\Pages;

use App\Services\RemoteEngineService;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;

class GoogleSearchConsoleDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-magnifying-glass';
    protected static ?string $cluster = \App\Filament\App\Clusters\DataExplorer::class;
    public static function getNavigationLabel(): string
    {
        return __('Google Search Console');
    }

    
    
    public static function getNavigationGroup(): ?string
    {
        return __('Google');
    }

    public function getTitle(): string
    {
        return __('Performance on Google Search results');
    }
    public function getHeading(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return '';
    }
    protected static string $view = 'filament.app.pages.google-search-console-dashboard';
    protected static ?string $slug = 'google-search-console';

    public ?string $selectedAccount = null;
    public ?string $dateStart = null;
    public ?string $dateEnd = null;

    public array $accounts = [];
    public string $activeTab = 'queries';

    public static function canAccess(): bool
    {
        if (!auth()->user()->can('view_data')) return false;
        $tenant = Filament::getTenant();
        $config = $tenant->sync_config ?? [];
        return !empty($config['google_search_console']['enabled']);
    }

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
            
            $response = $service->listChanneled($tenant, 'google_search_console', 'channeled_account', ['limit' => 1000, 'enabled' => 1]);

            $config = $tenant->sync_config['google_search_console']['assets']['sites'] ?? $tenant->sync_config['google_search_console']['sites'] ?? [];
            $enabledIds = [];
            foreach ($config as $site) {
                $siteUrl = $site['url'] ?? $site['id'] ?? null;
                if (!empty($site['enabled']) && !empty($siteUrl)) {
                    $enabledIds[] = md5(rtrim($siteUrl, '/'));
                }
            }

            if (isset($response['data']) && is_array($response['data'])) {
                foreach ($response['data'] as $page) {
                    $platformId = (string) ($page['platformId'] ?? $page['platform_id'] ?? $page['id']);
                    
                    if (in_array($platformId, $enabledIds)) {
                        $this->accounts[$page['id']] = $page['name'] ?? $platformId;
                    }
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
