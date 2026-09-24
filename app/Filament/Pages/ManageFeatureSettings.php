<?php

namespace App\Filament\Pages;

use App\Jobs\RevokeSharedAiKeyJob;
use App\Jobs\SyncSharedAiKeyJob;
use App\Settings\FeatureSettings;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\SettingsPage;

class ManageFeatureSettings extends SettingsPage
{
    protected static ?string $navigationIcon = 'heroicon-o-sparkles';

    protected static string $settings = FeatureSettings::class;

    public function getTitle(): string | \Illuminate\Contracts\Support\Htmlable
    {
        return __('Features');
    }

    protected static ?int $navigationSort = 3;

    public static function getNavigationLabel(): string
    {
        return __('Features');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('SaaS Management');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('Temporary Feature Flags'))
                    ->description(__('Manage global and tier-specific temporary feature toggles.'))
                    ->schema([
                        Forms\Components\Toggle::make('enable_free_ai_classification')
                            ->label(__('Enable AI-assisted keywords classification for free accounts'))
                            ->helperText(__('When enabled, projects associated with Free billing profiles will be allowed to use AI-assisted keywords classification and borrow the shared platform TypeSafe AI license.'))
                            ->default(false),
                    ]),
            ]);
    }

    protected function afterSave(): void
    {
        $settings = app(FeatureSettings::class);

        // If enabled, push credentials to eligible Free projects; if disabled, scrub/revoke
        if ($settings->enable_free_ai_classification) {
            dispatch(new SyncSharedAiKeyJob());
        } else {
            dispatch(new RevokeSharedAiKeyJob());
        }
    }
}
