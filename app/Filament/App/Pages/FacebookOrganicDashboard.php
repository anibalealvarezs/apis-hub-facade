<?php

namespace App\Filament\App\Pages;

use Filament\Facades\Filament;
use Filament\Pages\Page;

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
    protected static string $view = 'filament.app.pages.facebook-organic-dashboard';
    protected static ?string $slug = 'facebook-organic';

    public static function canAccess(): bool
    {
        if (!auth()->user()->can('view_data')) return false;
        $tenant = Filament::getTenant();
        $config = $tenant->sync_config ?? [];
        return !empty($config['facebook_organic']['enabled']);
    }
}
