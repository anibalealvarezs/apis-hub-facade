<?php

namespace App\Filament\App\Pages;

use Filament\Facades\Filament;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;

class FacebookOrganicDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $cluster = \App\Filament\App\Clusters\DataExplorer::class;
    public static function getNavigationLabel(): string
    {
        return __('Facebook Organic');
    }

    
    
    public static function getNavigationGroup(): ?string
    {
        return __('Meta');
    }


    public function getTitle(): string
    {
        return __('Meta Pages & Instagram Accounts');
    }

    public function getHeading(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return '';
    }

    protected static string $view = 'filament.app.pages.facebook-organic-dashboard';
    protected static ?string $slug = 'facebook-organic';

    public static function canAccess(): bool
    {
        if (!auth()->user()->can('view_data')) return false;
        $tenant = Filament::getTenant();
        $config = $tenant->sync_config ?? [];
        return !empty($config['facebook_organic']['enabled']);
    }

    public array $selectedAccounts = [];
    public ?string $dateStart = null;
    public ?string $dateEnd = null;

    public array $accounts = [];

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

            // Fetch channeled accounts which returns both IG Accounts and FB Pages
            $response = $service->listChanneled($tenant, 'facebook_organic', 'channeled_account', ['limit' => 1000, 'enabled' => 1]);

            \Illuminate\Support\Facades\Log::info("FBO Dashboard - API Response count", ['count' => count($response['data'] ?? [])]);

            $fbPages = [];
            $igAccounts = [];
            if (isset($response['data']) && is_array($response['data'])) {
                foreach ($response['data'] as $account) {
                    $type = $account['type'] ?? $account['account_type'] ?? '';
                    if ($type === 'facebook_page') {
                        $fbPages[$account['id']] = $account;
                    } elseif ($type === 'instagram_account') {
                        $igAccounts[$account['id']] = $account;
                    }
                }
            }
            \Illuminate\Support\Facades\Log::info("FBO Dashboard - Parsed fbPages/igAccounts", ['fb_count' => count($fbPages), 'ig_count' => count($igAccounts)]);

            // Extract the mapping from the tenant's sync_config
            $configPages = $tenant->sync_config['facebook_organic']['pages'] ?? [];
            $mapping = []; // fb_page_id => ig_account_id
            $enabledFbIds = [];
            foreach ($configPages as $p) {
                if (!empty($p['id']) && !empty($p['enabled'])) {
                    $enabledFbIds[] = (string) $p['id'];
                    if (!empty($p['ig_account'])) {
                        $mapping[$p['id']] = $p['ig_account'];
                    }
                }
            }
            \Illuminate\Support\Facades\Log::info("FBO Dashboard - Config Enabled IDs", ['enabledFbIds' => $enabledFbIds, 'mapping' => $mapping]);

            // Build the dropdown options
            foreach ($fbPages as $fbId => $fbAcc) {
                // Determine the clean Facebook ID from the response (removing 'act_' if strangely present, though FBO usually doesn't have it)
                $cleanFbId = str_replace('act_', '', (string) ($fbAcc['platformId'] ?? $fbAcc['platform_id'] ?? $fbId));

                $matched = in_array($cleanFbId, $enabledFbIds);
                \Illuminate\Support\Facades\Log::info("FBO Dashboard - Channeled Account", ['name' => $fbAcc['name'] ?? '', 'cleanFbId' => $cleanFbId, 'matched' => $matched]);

                if (!$matched) {
                    continue;
                }

                $igPlatformId = $mapping[$cleanFbId] ?? null;
                $igAcc = null;
                $igInternalId = null;
                $debugMatch = [];

                if ($igPlatformId) {
                    foreach ($igAccounts as $acc) {
                        $accPlatId = str_replace('act_', '', (string) ($acc['platformId'] ?? $acc['platform_id'] ?? ''));
                        $debugMatch[] = ['accPlatId' => $accPlatId, 'targetPlatId' => (string)$igPlatformId, 'acc' => $acc];
                        if ($accPlatId === (string)$igPlatformId) {
                            $igAcc = $acc;
                            $igInternalId = $acc['id'] ?? null;
                            break;
                        }
                    }
                }

                $label = $fbAcc['name'] ?? 'Facebook Page';

                $fbPageId = (string) ($fbAcc['pageId'] ?? $fbAcc['page_id'] ?? 'NONE');
                $fbPlatformId = $cleanFbId !== '' ? $cleanFbId : 'NONE';

                // Composite value: fbChanneledAccountId|igChanneledAccountId|fbPlatformId|fbPageId
                $value = $fbId . '|' . ($igInternalId ?? 'NONE') . '|' . $fbPlatformId . '|' . $fbPageId;
                $this->accounts[$value] = $label;
            }

            // Sort accounts alphabetically by name
            uasort($this->accounts, fn($a, $b) => strcasecmp($a, $b));

            $validSelected = array_values(array_intersect($this->selectedAccounts, array_keys($this->accounts)));
            if (empty($validSelected) && !empty($this->accounts)) {
                $this->selectedAccounts = [(string) array_key_first($this->accounts)];
            } elseif (!empty($validSelected)) {
                // FB Organic dashboard now uses one account at a time.
                $this->selectedAccounts = [(string) $validSelected[0]];
            } else {
                $this->selectedAccounts = [];
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("FBO Accounts Error: " . $e->getMessage());
        }
    }
}
