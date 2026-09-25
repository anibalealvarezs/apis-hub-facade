<?php

declare(strict_types=1);

namespace App\Filament\App\Pages;

use App\Filament\App\Clusters\KnowledgeBase\Integrations;
use App\Models\Project;
use Filament\Facades\Filament;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;

class ApiAccessReference extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $cluster = Integrations::class;
    protected static ?string $navigationIcon = 'heroicon-o-code-bracket';
    protected static ?int $navigationSort = 4;
    protected static string $view = 'filament.app.pages.api-access-reference';

    public ?array $data = [];

    public function getTenantProperty(): ?Project
    {
        return Filament::getTenant();
    }

    public function getBaseUrlProperty(): string
    {
        $tenant = $this->tenant;
        $domain = config('app.network_domain') ?: 'apis-hub.cloud';
        $subdomain = $tenant ? $tenant->subdomain : 'your-project';
        return "https://{$subdomain}.{$domain}";
    }

    public function getApiKeyProperty(): string
    {
        return $this->tenant?->public_api_key ?? '';
    }

    public function mount(): void
    {
        $this->form->fill([
            'app_api_key' => $this->apiKey,
        ]);
    }

    public function form(Form $form): Form
    {
        $tenant = $this->tenant;

        return $form
            ->schema([
                TextInput::make('app_api_key')
                    ->label(__('Public API Key'))
                    ->password()
                    ->revealable()
                    ->disabled()
                    ->helperText(__('Keep this key secure. It provides programmatic access to your tenant data node.'))
                    ->hintAction(
                        Action::make('rotateKey')
                            ->icon('heroicon-m-arrow-path')
                            ->color('warning')
                            ->disabled(fn () => ! $tenant || ! $tenant->is_active || $tenant->billing_status === 'suspended' || ! \Illuminate\Support\Facades\Auth::user()->can('edit_preferences'))
                            ->requiresConfirmation()
                            ->modalHeading(__('Rotate API Key?'))
                            ->modalDescription(__('Generating a new key will immediately invalidate the current one. You must update all external integrations (PowerBI, Looker, scripts) with the new key.'))
                            ->modalSubmitActionLabel(__('Yes, rotate and push'))
                            ->action(function (\App\Services\DeployerService $deployer) {
                                $tenant = $this->tenant;
                                if (! $tenant) {
                                    return;
                                }

                                $newKey = bin2hex(random_bytes(32));

                                $tenant->update(['public_api_key' => $newKey]);

                                $deployer->updateCredentials($tenant, [
                                    'APP_API_KEY' => $newKey,
                                ]);

                                \Filament\Notifications\Notification::make()
                                    ->title(__('API Key Rotated!'))
                                    ->success()
                                    ->body(__('The new key has been generated and synchronized with your node.'))
                                    ->send();

                                $this->form->fill(['app_api_key' => $newKey]);
                            })
                    ),
            ])
            ->statePath('data');
    }

    public static function getNavigationLabel(): string
    {
        return __('API Access & Integration');
    }

    public function getTitle(): string
    {
        return __('API Access & Integration Guide');
    }

    public static function canAccess(): bool
    {
        return true;
    }
}
