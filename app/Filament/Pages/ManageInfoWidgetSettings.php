<?php

namespace App\Filament\Pages;

use App\Settings\InfoWidgetSettings;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\SettingsPage;

class ManageInfoWidgetSettings extends SettingsPage
{
    protected static ?string $navigationIcon = 'heroicon-o-information-circle';

    protected static string $settings = InfoWidgetSettings::class;

    public function getTitle(): string | \Illuminate\Contracts\Support\Htmlable
    {
        return __('Configure Info Widgets');
    }

    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return __('Info Widgets');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Content & UI');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('Google Search Console Widgets'))
                    ->description(__('Control the visibility of explanatory widgets on the Google Search Console data sources page.'))
                    ->schema([
                        Forms\Components\Toggle::make('show_gsc_data_enrichment')
                            ->label(__('Show Data Enrichment Widget'))
                            ->helperText(__('Displays the explanation of synthetic calculations (Möbius Reconciliation) and privacy trade-offs.'))
                            ->default(true),
                    ]),

                Forms\Components\Section::make(__('Facebook Organic Widgets'))
                    ->description(__('Control the visibility of explanatory widgets on the Facebook Organic data sources page.'))
                    ->schema([
                        Forms\Components\Toggle::make('show_fb_organic_historic_limitation')
                            ->label(__('Show Historic Metrics Limitation Widget'))
                            ->helperText(__('Displays the notice explaining that Facebook does not provide historic post metrics.'))
                            ->default(true),

                        Forms\Components\Toggle::make('show_fb_organic_rate_limits')
                            ->label(__('Show Rate Limits & Inactive Assets Widget'))
                            ->helperText(__('Displays the recommendation to disable inactive pages and IG accounts to prevent rate limiting.'))
                            ->default(true),
                    ]),
            ]);
    }
}
