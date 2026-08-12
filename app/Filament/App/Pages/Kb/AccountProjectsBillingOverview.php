<?php

namespace App\Filament\App\Pages\Kb;

use App\Filament\App\Clusters\KnowledgeBase\AccountProjectsBilling as AccountProjectsBillingCluster;
use App\Filament\App\Pages\AccountStructureReference;
use App\Filament\App\Pages\AssetBillingReference;
use App\Filament\App\Pages\SubscriptionFeatures;
use Filament\Pages\Page;

class AccountProjectsBillingOverview extends Page
{
    protected static ?string $cluster = AccountProjectsBillingCluster::class;
    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static ?int $navigationSort = 1;
    protected static string $view = 'filament.app.pages.kb.account-projects-billing-overview';
    protected static ?string $slug = 'overview';

    public static function getNavigationLabel(): string
    {
        return __('Overview');
    }

    public function getTitle(): string
    {
        return __('Account, Projects & Billing');
    }

    protected function getViewData(): array
    {
        return [
            'intro' => __('Reference material for account structure, billing, quotas, and plan features.'),
            'links' => [
                [
                    'url' => AccountStructureReference::getUrl(),
                    'icon' => 'heroicon-o-rectangle-group',
                    'title' => __('Account & Projects Structure'),
                    'description' => __('Understand how accounts, projects, and profiles relate.'),
                ],
                [
                    'url' => AssetBillingReference::getUrl(),
                    'icon' => 'heroicon-o-credit-card',
                    'title' => __('Billing & Quotas'),
                    'description' => __('See how billing works, along with quotas and limits per tier.'),
                ],
                [
                    'url' => SubscriptionFeatures::getUrl(),
                    'icon' => 'heroicon-o-star',
                    'title' => __('Features & Tiers'),
                    'description' => __('Compare features across the free and paid plans.'),
                ],
            ],
        ];
    }
}
