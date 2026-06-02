<?php

namespace App\Filament\App\Pages;

use Filament\Facades\Filament;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;

class FacebookOrganicDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $cluster = \App\Filament\App\Clusters\DataExplorer::class;
    protected static ?string $navigationGroup = 'Meta';
    protected static ?string $navigationLabel = 'Facebook Organic';
    
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
            $response = $service->listChanneled($tenant, 'facebook_organic', 'channeled_account', ['limit' => 1000]);

            $fbPages = [];
            $igAccounts = [];
            if (isset($response['data']) && is_array($response['data'])) {
                foreach ($response['data'] as $account) {
                    if (($account['account_type'] ?? '') === 'facebook_page') {
                        $fbPages[$account['id']] = $account;
                    } elseif (($account['account_type'] ?? '') === 'instagram_account') {
                        $igAccounts[$account['id']] = $account;
                    }
                }
            }

            // Extract the mapping from the tenant's sync_config
            $configPages = $tenant->sync_config['facebook_organic']['pages'] ?? [];
            $mapping = []; // fb_page_id => ig_account_id
            $enabledFbIds = [];
            foreach ($configPages as $p) {
                if (!empty($p['id']) && !empty($p['enabled'])) {
                    $enabledFbIds[] = (string) $p['id'];
                    if (!empty($p['instagram_business_account']['id'])) {
                        $mapping[$p['id']] = $p['instagram_business_account']['id'];
                    }
                }
            }

            // Build the dropdown options
            foreach ($fbPages as $fbId => $fbAcc) {
                // Determine the clean Facebook ID from the response (removing 'act_' if strangely present, though FBO usually doesn't have it)
                $cleanFbId = str_replace('act_', '', (string) ($fbAcc['platformId'] ?? $fbAcc['platform_id'] ?? $fbId));
                
                if (!in_array($cleanFbId, $enabledFbIds)) {
                    continue;
                }

                $igId = $mapping[$cleanFbId] ?? null;
                $igAcc = $igId ? ($igAccounts[$igId] ?? null) : null;
                
                if ($igAcc) {
                    $label = "Instagram: {$igAcc['name']} (via {$fbAcc['name']})";
                } else {
                    $label = "Facebook: {$fbAcc['name']}";
                }
                    
                $value = $fbId . '|' . ($igId ?? 'NONE');
                $this->accounts[$value] = $label;
            }
            
            if (!empty($this->accounts) && empty($this->selectedAccounts)) {
                $this->selectedAccounts = [array_key_first($this->accounts)];
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("FBO Accounts Error: " . $e->getMessage());
        }
    }
}
