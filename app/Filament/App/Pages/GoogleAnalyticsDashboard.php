<?php

namespace App\Filament\App\Pages;

use App\Services\RemoteEngineService;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;

class GoogleAnalyticsDashboard extends Page
{
    use \App\Filament\App\Pages\Concerns\RedirectsWhenChannelDisabled;

    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';
    protected static ?string $cluster = \App\Filament\App\Clusters\DataExplorer::class;

    public static function getNavigationLabel(): string
    {
        return __('Google Analytics');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Google');
    }

    public function getTitle(): string
    {
        return __('Google Analytics 4 Insights');
    }

    public function getHeading(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return '';
    }

    protected static string $view = 'filament.app.pages.google-analytics-dashboard';
    protected static ?string $slug = 'google-analytics';

    public ?string $selectedAccount = null;
    public ?string $dateStart = null;
    public ?string $dateEnd = null;

    public array $accounts = [];
    public string $activeTab = 'campaigns';

    protected static function getChannelConfigKey(): string
    {
        return 'google_analytics';
    }

    public static function canAccess(): bool
    {
        if (!auth()->user()->can('view_data')) return false;
        return static::isChannelEnabled();
    }

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

            $response = $service->listChanneled($tenant, 'google_analytics', 'channeled_account', ['limit' => 1000, 'enabled' => 1]);

            $gaConfig = $tenant->sync_config['google_analytics'] ?? [];
            $config = $gaConfig['assets']['properties'] ?? $gaConfig['properties'] ?? [];
            $enabledIds = [];
            foreach ($config as $prop) {
                $pid = $prop['platformId'] ?? $prop['platform_id'] ?? null;
                if (!empty($prop['enabled']) && $pid) {
                    $enabledIds[] = (string) $pid;
                }
            }

            \Illuminate\Support\Facades\Log::info("GA4 Dashboard - enabled property IDs", ['enabledIds' => $enabledIds, 'config_keys' => array_keys($gaConfig)]);

            if (isset($response['data']) && is_array($response['data'])) {
                \Illuminate\Support\Facades\Log::info("GA4 Dashboard - remote channeled_accounts", ['count' => count($response['data']), 'sample' => array_slice($response['data'], 0, 3)]);

                foreach ($response['data'] as $prop) {
                    $platformId = (string) ($prop['platformId'] ?? $prop['platform_id'] ?? $prop['id']);
                    if (in_array($platformId, $enabledIds)) {
                        $this->accounts[$prop['id']] = $prop['name'] ?? $platformId;
                    }
                }

                if (!empty($this->accounts)) {
                    uasort($this->accounts, fn($a, $b) => strcasecmp((string)$a, (string)$b));
                    if (!$this->selectedAccount) {
                        $this->selectedAccount = array_key_first($this->accounts);
                    }
                }
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("GA4 Accounts Error: " . $e->getMessage());
        }
    }

    public function setActiveTab(string $tab): void
    {
        $this->activeTab = $tab;
    }
}
