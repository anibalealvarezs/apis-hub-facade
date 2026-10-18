<?php

namespace App\Filament\App\Pages;

use App\Services\RemoteEngineService;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;

class GoogleAdsDashboard extends Page
{
    use \App\Filament\App\Pages\Concerns\RedirectsWhenChannelDisabled;

    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';
    protected static ?string $cluster = \App\Filament\App\Clusters\DataExplorer::class;

    public static function getNavigationIcon(): string | \Illuminate\Contracts\Support\Htmlable | null
    {
        return \App\Support\BrandIcon::google();
    }

    public static function getNavigationLabel(): string
    {
        return __('Google Ads');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Google');
    }

    public function getTitle(): string
    {
        return __('Google Ads Insights');
    }

    public function getHeading(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return '';
    }

    protected static string $view = 'filament.app.pages.google-ads-dashboard';
    protected static ?string $slug = 'google-ads';

    protected static function getChannelConfigKey(): string
    {
        return 'google_ads';
    }

    public static function canAccess(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = \Illuminate\Support\Facades\Auth::user();
        if (!$user || !$user->can('view_data')) {
            return false;
        }

        $tenant = Filament::getTenant();
        if ($tenant && method_exists($tenant, 'releaseSupportsGoogleAds') && !$tenant->releaseSupportsGoogleAds()) {
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
            $service = app(RemoteEngineService::class);
            $tenant = Filament::getTenant();

            $googleAdsConfig = $tenant->sync_config['google_ads'] ?? [];
            $config = $googleAdsConfig['assets']['ad_accounts'] ?? $googleAdsConfig['ad_accounts'] ?? [];

            $enabledIds = [];
            foreach ($config as $asset) {
                if (!empty($asset['enabled']) && !empty($asset['id'])) {
                    $cleanId = str_replace(['customers/', '-'], '', (string)$asset['id']);
                    $enabledIds[] = $cleanId;
                }
            }

            $response = $service->listChanneled($tenant, 'google_ads', 'channeled_account', ['limit' => 1000, 'enabled' => 1]);

            if (isset($response['data']) && is_array($response['data'])) {
                foreach ($response['data'] as $account) {
                    $platformId = (string) ($account['platformId'] ?? $account['platform_id'] ?? $account['id']);
                    $cleanPlatformId = str_replace(['customers/', '-'], '', $platformId);

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
            \Illuminate\Support\Facades\Log::error("Google Ads Accounts Error: " . $e->getMessage());
        }
    }

    public function setActiveTab(string $tab): void
    {
        $this->activeTab = $tab;
    }
}
