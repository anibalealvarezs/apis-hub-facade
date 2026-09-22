<?php

namespace App\Filament\Pages;

use Anibalealvarezs\TypeSafeApi\TypeSafeApi;
use App\Jobs\RevokeSharedAiKeyJob;
use App\Settings\AiSettings;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\SettingsPage;

class ManageAiSettings extends SettingsPage
{
    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';

    protected static string $settings = AiSettings::class;

    public function getTitle(): string | \Illuminate\Contracts\Support\Htmlable
    {
        return __('AI & Semantic Intelligence Settings');
    }

    protected static ?int $navigationSort = 10;

    public static function getNavigationLabel(): string
    {
        return __('AI Settings');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Settings');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('TypeSafe AI Platform License'))
                    ->description(__('Configure global credentials for TypeSafe AI (JEV System One) semantic classification.'))
                    ->schema([
                        Forms\Components\TextInput::make('typesafe_admin_api_key')
                            ->label(__('Platform TypeSafe API Key'))
                            ->password()
                            ->revealable()
                            ->placeholder('apikey_...')
                            ->helperText(__('Global API key used for system-wide semantic evaluations and shared project acceleration.')),

                        Forms\Components\Toggle::make('typesafe_admin_share_enabled')
                            ->label(__('Allow Projects to Borrow Platform License'))
                            ->helperText(__('When enabled, active projects without their own TypeSafe API key can leverage this platform key for query classification. The actual key remains strictly hidden from tenants.')),
                    ]),
            ]);
    }

    protected function afterSave(): void
    {
        $settings = app(AiSettings::class);

        // If admin sharing was disabled, revoke/scrub credentials; if enabled, push to all borrowing tenants
        if (!$settings->typesafe_admin_share_enabled) {
            dispatch(new RevokeSharedAiKeyJob());
        } elseif (!empty($settings->typesafe_admin_api_key)) {
            dispatch(new \App\Jobs\SyncSharedAiKeyJob());
        }

        // Validate key if provided
        if (!empty($settings->typesafe_admin_api_key)) {
            try {
                $client = new TypeSafeApi(apiKey: $settings->typesafe_admin_api_key);
                $res = $client->evaluateNoul(
                    state: 'test query',
                    questionId: 'health_check',
                    instructions: 'Is this a valid test query?'
                );

                if (isset($res['answers']['health_check'])) {
                    Notification::make()
                        ->title(__('TypeSafe AI License Verified'))
                        ->body(__('Connection to TypeSafe System One established successfully.'))
                        ->success()
                        ->send();
                }
            } catch (\Throwable $e) {
                Notification::make()
                    ->title(__('Warning: TypeSafe Key Verification Failed'))
                    ->body(__('The API key was saved, but verification failed: ') . $e->getMessage())
                    ->warning()
                    ->send();
            }
        }
    }
}