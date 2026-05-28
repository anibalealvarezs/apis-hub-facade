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
    protected static ?string $title = 'Facebook Marketing';
    protected static string $view = 'filament.app.pages.facebook-marketing-dashboard';
    protected static ?string $slug = 'facebook-marketing';

    public static function canAccess(): bool
    {
        $tenant = Filament::getTenant();
        $config = $tenant->sync_config ?? [];
        return !empty($config['facebook_marketing']['enabled']);
    }

    public ?string $selectedAccount = null;
    public ?string $dateStart = null;
    public ?string $dateEnd = null;

    public array $accounts = [
        'act_123456789' => 'Acme Corp Ads',
        'act_987654321' => 'Beta Test Account',
    ];

    public function mount(): void
    {
        $this->dateEnd = Carbon::now()->subDays(1)->format('Y-m-d');
        $this->dateStart = Carbon::now()->subDays(31)->format('Y-m-d');

        if (!empty($this->accounts) && !$this->selectedAccount) {
            $this->selectedAccount = array_key_first($this->accounts);
        }
    }
}
