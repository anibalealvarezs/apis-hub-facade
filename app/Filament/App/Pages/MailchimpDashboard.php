<?php

namespace App\Filament\App\Pages;

use App\Services\RemoteEngineService;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;

class MailchimpDashboard extends Page
{
    use \App\Filament\App\Pages\Concerns\RedirectsWhenChannelDisabled;

    protected static ?string $navigationIcon = 'heroicon-o-envelope';
    protected static ?string $cluster = \App\Filament\App\Clusters\DataExplorer::class;

    public static function getNavigationIcon(): string | \Illuminate\Contracts\Support\Htmlable | null
    {
        return \App\Support\BrandIcon::mailchimp();
    }

    public static function getNavigationLabel(): string
    {
        return __('Mailchimp');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Email Marketing');
    }

    public function getTitle(): string
    {
        return __('Performance on Mailchimp campaigns');
    }

    public function getHeading(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return '';
    }

    protected static string $view = 'filament.app.pages.mailchimp-dashboard';
    protected static ?string $slug = 'mailchimp';

    public ?string $selectedAccount = null;
    public ?string $dateStart = null;
    public ?string $dateEnd = null;

    public array $accounts = [];
    public string $activeTab = 'campaigns';

    protected static function getChannelConfigKey(): string
    {
        return 'mailchimp';
    }

    public static function canAccess(): bool
    {
        if (!auth()->user()->can('view_data')) {
            return false;
        }

        return static::isChannelEnabled();
    }

    public function mount(): void
    {
        $this->dateEnd = Carbon::now()->subDays(1)->format('Y-m-d');
        $this->dateStart = Carbon::now()->subDays(31)->format('Y-m-d');

        $this->loadAccounts();
    }

    /**
     * Build the audience selector from the worker's channeled accounts, restricted to the
     * audiences the user explicitly enabled in Data Sources.
     *
     * Mailchimp audiences are the lists of a connected Mailchimp account, so the platform id
     * stored in sync_config is already the Mailchimp list id (no hashing, unlike GSC sites).
     */
    public function loadAccounts(): void
    {
        try {
            $service = app(RemoteEngineService::class);
            $tenant = Filament::getTenant();

            $response = $service->listChanneled($tenant, 'mailchimp', 'channeled_account', ['limit' => 1000, 'enabled' => 1]);

            $config = $tenant->sync_config['mailchimp']['assets']['audiences']
                ?? $tenant->sync_config['mailchimp']['audiences']
                ?? [];

            $enabledIds = [];
            foreach ($config as $audience) {
                $listId = $audience['id'] ?? $audience['list_id'] ?? null;
                if (!empty($audience['enabled']) && !empty($listId)) {
                    $enabledIds[] = (string) $listId;
                }
            }

            \Illuminate\Support\Facades\Log::info('Mailchimp Dashboard - config audiences', [
                'config_audiences' => $config,
                'enabledIds' => $enabledIds,
            ]);

            if (isset($response['data']) && is_array($response['data']) && !empty($response['data'])) {
                \Illuminate\Support\Facades\Log::info('Mailchimp Dashboard - remote channeled_accounts', [
                    'count' => count($response['data']),
                    'sample' => array_slice($response['data'], 0, 3),
                ]);

                foreach ($response['data'] as $audience) {
                    $platformId = (string) ($audience['platformId'] ?? $audience['platform_id'] ?? $audience['id'] ?? '');

                    // Match against enabled list IDs (or allow all if no explicit enabled filter is defined yet)
                    if ($platformId !== '' && (empty($enabledIds) || in_array($platformId, $enabledIds, true))) {
                        $this->accounts[$audience['id']] = $audience['name'] ?? $platformId;
                    }
                }
            }

            // Fallback: If no remote channeled accounts were found (e.g. worker has not synced yet or is unreachable),
            // populate directly from discovered/configured audiences in sync_config.
            if (empty($this->accounts) && !empty($config)) {
                \Illuminate\Support\Facades\Log::info('Mailchimp Dashboard - falling back to sync_config audiences', [
                    'count' => count($config),
                ]);

                foreach ($config as $audience) {
                    $listId = (string) ($audience['id'] ?? $audience['list_id'] ?? $audience['platform_id'] ?? '');
                    if ($listId === '') {
                        continue;
                    }

                    // Prioritize enabled audiences, or fall back to all if none explicitly enabled
                    if (empty($enabledIds) || in_array($listId, $enabledIds, true) || !empty($audience['enabled'])) {
                        $this->accounts[$listId] = $audience['name'] ?? $listId;
                    }
                }
            }

            if (!empty($this->accounts)) {
                uasort($this->accounts, fn ($a, $b) => strcasecmp((string) $a, (string) $b));

                if (!$this->selectedAccount || !isset($this->accounts[$this->selectedAccount])) {
                    $this->selectedAccount = (string) array_key_first($this->accounts);
                }
            } else {
                \Illuminate\Support\Facades\Log::warning('Mailchimp Dashboard - no accounts found from worker or sync_config', [
                    'has_config' => !empty($config),
                    'enabled_count' => count($enabledIds),
                ]);
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Mailchimp Accounts Error: ' . $e->getMessage());
        }
    }

    public function setActiveTab(string $tab): void
    {
        $this->activeTab = $tab;
    }
}
