<?php

namespace App\Filament\App\Pages;

use Filament\Facades\Filament;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;

class FacebookMarketingDashboard extends Page
{
    use \App\Filament\App\Pages\Concerns\RedirectsWhenChannelDisabled;

    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';
    protected static ?string $cluster = \App\Filament\App\Clusters\DataExplorer::class;
    public static function getNavigationLabel(): string
    {
        return __('Facebook Marketing');
    }

    
    
    public static function getNavigationGroup(): ?string
    {
        return __('Meta');
    }

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

    protected static function getChannelConfigKey(): string
    {
        return 'facebook_marketing';
    }

    public static function canAccess(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = \Illuminate\Support\Facades\Auth::user();
        if (!$user || !$user->can('view_data')) {
            return false;
        }
        return static::isChannelEnabled();
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
            $facebookMarketingConfig = $tenant->sync_config['facebook_marketing'] ?? [];

            // Fallback strategy: check both potential locations for the array
            $config = $facebookMarketingConfig['assets']['ad_accounts'] ?? $facebookMarketingConfig['ad_accounts'] ?? [];

            $enabledIds = [];
            foreach ($config as $asset) {
                if (!empty($asset['enabled']) && !empty($asset['id'])) {
                    // Match the formatting used in discovery (removing 'act_' prefix if present)
                    $enabledIds[] = str_replace('act_', '', (string) $asset['id']);
                }
            }
            
            $response = $service->listChanneled($tenant, 'facebook_marketing', 'channeled_account', ['limit' => 1000, 'enabled' => 1]);

            if (isset($response['data']) && is_array($response['data'])) {
                foreach ($response['data'] as $account) {
                    $platformId = (string) ($account['platformId'] ?? $account['platform_id'] ?? $account['id']);
                    $cleanPlatformId = str_replace('act_', '', $platformId);
                    
                    if (in_array($cleanPlatformId, $enabledIds)) {
                        $accountId = (string) $account['id'];
                        $this->accounts[$accountId] = $account['name'] ?? $accountId;
                    }
                }
                
                if (!empty($this->accounts)) {
                    uasort($this->accounts, fn($a, $b) => strcasecmp((string)$a, (string)$b));
                    if (empty($this->selectedAccounts)) {
                        $this->selectedAccounts = [(string) array_key_first($this->accounts)];
                    }
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
