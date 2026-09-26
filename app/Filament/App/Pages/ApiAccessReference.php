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
        $user = \Illuminate\Support\Facades\Auth::user();
        if ($this->tenant && $this->tenant->isEditorOrOwner($user)) {
            return $this->tenant->public_api_key ?? '';
        }

        return $this->tenant?->public_api_key ?? '';
    }

    public function getIsEditorOrOwnerProperty(): bool
    {
        return (bool) $this->tenant?->isEditorOrOwner(\Illuminate\Support\Facades\Auth::user());
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
        $isEditorOrOwner = $this->isEditorOrOwner;

        return $form
            ->schema([
                TextInput::make('app_api_key')
                    ->label($isEditorOrOwner ? __('Tenant Public API Key (Master Key)') : __('Personal Scoped API Key'))
                    ->password()
                    ->revealable()
                    ->disabled()
                    ->helperText($isEditorOrOwner 
                        ? __('Master API key granting full access across all channels and connected accounts.')
                        : __('Scoped API key restricted strictly to your assigned asset groups in this project.'))
                    ->hintAction(
                        Action::make('rotateKey')
                            ->icon('heroicon-m-arrow-path')
                            ->color('warning')
                            ->disabled(fn () => ! $tenant || ! $tenant->is_active || $tenant->billing_status === 'suspended')
                            ->requiresConfirmation()
                            ->modalHeading(__('Rotate API Key?'))
                            ->modalDescription($isEditorOrOwner 
                                ? __('Generating a new master key will immediately invalidate the current one and affect all external integrations.')
                                : __('Generating a new personal key will invalidate your current token. Update your external scripts and tools immediately.'))
                            ->modalSubmitActionLabel(__('Yes, rotate and push'))
                            ->action(function (\App\Services\DeployerService $deployer) {
                                $tenant = $this->tenant;
                                if (! $tenant) {
                                    return;
                                }

                                $currentUser = \Illuminate\Support\Facades\Auth::user();
                                $newKey = bin2hex(random_bytes(32));

                                if ($this->isEditorOrOwner) {
                                    // 1. Master key rotation
                                    $tenant->update(['public_api_key' => $newKey]);

                                    $response = $deployer->updateCredentials($tenant, [
                                        'APP_API_KEY' => $newKey,
                                        'TOKEN_AUTHORITY_BEARER' => $newKey,
                                    ]);

                                    if (($response['success'] ?? false) || ($response['status'] ?? '') === 'success') {
                                        \Filament\Notifications\Notification::make()
                                            ->title(__('API Key Rotated!'))
                                            ->success()
                                            ->body(__('The new key has been generated and synchronized with your node.'))
                                            ->send();
                                    } else {
                                        \Filament\Notifications\Notification::make()
                                            ->title(__('Key Saved Locally'))
                                            ->warning()
                                            ->body(__('Key updated in the database, but remote synchronization failed: ') . ($response['message'] ?? 'SSH connection error.'))
                                            ->send();
                                    }

                                    // Notify team owners & editors
                                    $notification = new \App\Notifications\ApiKeyRotatedNotification($tenant, $currentUser);
                                    foreach ($tenant->getEditorsAndOwners() as $userToNotify) {
                                        $userToNotify->notify($notification);
                                    }
                                } else {
                                    // 2. Viewer personal key rotation
                                    $deployer->syncUserApiKeys($tenant);

                                    $notification = new \App\Notifications\UserApiKeyRotatedNotification($tenant, $currentUser, false);
                                    $currentUser->notify($notification);

                                    \Filament\Notifications\Notification::make()
                                        ->title(__('Personal Key Rotated'))
                                        ->success()
                                        ->body(__('Your personal scoped API key has been regenerated and synchronized with the node.'))
                                        ->send();
                                }

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
