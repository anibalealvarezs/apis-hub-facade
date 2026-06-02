<?php

namespace App\Filament\App\Pages;

use Filament\Facades\Filament;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;

class FacebookMarketingDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';
    protected static ?string $cluster = \App\Filament\App\Clusters\DataExplorer::class;
    protected static ?string $navigationGroup = 'Meta';
    protected static ?string $navigationLabel = 'Facebook Marketing';
    public function getTitle(): string
    {
        return __('Meta Ads Manager Insights');
    }
    public function getHeading(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return '';
    }
    protected static string $view = 'filament.app.pages.facebook-marketing-dashboard';
    protected static ?string $slug = 'facebook-marketing';

    public static function canAccess(): bool
    {
        if (!auth()->user()->can('view_data')) return false;
        $tenant = Filament::getTenant();
        $config = $tenant->sync_config ?? [];
        return !empty($config['facebook_marketing']['enabled']);
    }

    public array $selectedAccounts = [];
    public ?string $dateStart = null;
    public ?string $dateEnd = null;

    public array $accounts = [];
    public string $activeTab = 'campaigns';

    public function mount(): void
    {
        $this->dateEnd = Carbon::now()->subDays(1)->format('Y-m-d');
        $this->dateStart = Carbon::now()->subDays(31)->format('Y-m-d');

        $this->loadAccounts();
    }

    public function loadAccounts(): void
    {
        try {
            $service = app(\App\Services\RemoteEngineService::class);
            $tenant = Filament::getTenant();
            
            // Extract enabled accounts from sync config
            $config = $tenant->sync_config['facebook_marketing']['assets']['ad_accounts'] ?? [];
            $enabledIds = [];
            foreach ($config as $asset) {
                if (!empty($asset['enabled']) && !empty($asset['id'])) {
                    // Match the formatting used in discovery (removing 'act_' prefix if present)
                    $enabledIds[] = str_replace('act_', '', (string) $asset['id']);
                }
            }
            
            $response = $service->listChanneled($tenant, 'facebook_marketing', 'channeled_account');

            if (isset($response['data']) && is_array($response['data'])) {
                foreach ($response['data'] as $account) {
                    $platformId = (string) ($account['platform_id'] ?? $account['id']);
                    $cleanPlatformId = str_replace('act_', '', $platformId);
                    
                    if (in_array($cleanPlatformId, $enabledIds)) {
                        $this->accounts[$account['id']] = $account['name'] ?? $account['id'];
                    }
                }
                
                if (!empty($this->accounts) && empty($this->selectedAccounts)) {
                    $this->selectedAccounts = [array_key_first($this->accounts)];
                }
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("FBM Accounts Error: " . $e->getMessage());
        }
    }

    public function setActiveTab(string $tab): void
    {
        $this->activeTab = $tab;
    }
}
