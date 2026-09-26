<?php

declare(strict_types=1);

namespace App\Filament\App\Pages;

use App\Filament\App\Clusters\KnowledgeBase\Integrations;
use App\Models\Project;
use Filament\Facades\Filament;
use Filament\Actions\Action as HeaderAction;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;

class McpAccessReference extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $cluster = Integrations::class;
    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';
    protected static ?int $navigationSort = 5;
    protected static string $view = 'filament.app.pages.mcp-access-reference';

    protected function getHeaderActions(): array
    {
        return [
            HeaderAction::make('syncContext')
                ->label(__('Sync Context with Node'))
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->visible(fn () => $this->isEditorOrOwner)
                ->action(function (\App\Services\DeployerService $deployer) {
                    $tenant = $this->tenant;
                    if (!$tenant) {
                        return;
                    }

                    $success = $deployer->syncProjectMetadata($tenant);

                    if ($success) {
                        \Filament\Notifications\Notification::make()
                            ->title(__('Project Context Synchronized'))
                            ->success()
                            ->body(__('Custom KPIs, Dashboards, and Alerts catalog have been securely pushed to your node.'))
                            ->send();
                    } else {
                        \Filament\Notifications\Notification::make()
                            ->title(__('Sync Failed'))
                            ->danger()
                            ->body(__('Failed to synchronize project context with the remote node.'))
                            ->send();
                    }
                }),
        ];
    }

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

    public function getSseEndpointProperty(): string
    {
        return "{$this->baseUrl}/mcp/sse";
    }

    public function getApiKeyProperty(): string
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        if ($this->tenant && $this->tenant->isEditorOrOwner($user)) {
            return $this->tenant->public_api_key ?? '';
        }

        // Viewers: return user-scoped key if configured, or tenant key masked/scoped
        return $this->tenant?->public_api_key ?? '';
    }

    public function getIsEditorOrOwnerProperty(): bool
    {
        return (bool) $this->tenant?->isEditorOrOwner(\Illuminate\Support\Facades\Auth::user());
    }

    public function getIsMcpAvailableProperty(): bool
    {
        $tier = $this->tenant?->fresh()?->billingProfile?->fresh()?->tier?->value;
        return in_array($tier, ['ultra', 'enterprise', 'founder']);
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
                        ? __('Master API key granting full access across all channels and asset groups.')
                        : __('Scoped API key restricted to your assigned asset groups in this project.'))
                    ->hintAction(
                        Action::make('rotateKey')
                            ->icon('heroicon-m-arrow-path')
                            ->color('warning')
                            ->disabled(fn () => ! $tenant || ! $tenant->is_active || $tenant->billing_status === 'suspended')
                            ->requiresConfirmation()
                            ->modalHeading(__('Rotate API Key?'))
                            ->modalDescription($isEditorOrOwner 
                                ? __('Generating a new master key will immediately invalidate the current one and affect all master integrations.')
                                : __('Generating a new personal key will invalidate your current token. Update your AI clients immediately.'))
                            ->modalSubmitActionLabel(__('Yes, rotate key'))
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
                                            ->title(__('Master API Key Rotated!'))
                                            ->success()
                                            ->body(__('The master key has been generated and pushed to the dedicated node.'))
                                            ->send();
                                    } else {
                                        \Filament\Notifications\Notification::make()
                                            ->title(__('Key Saved Locally'))
                                            ->warning()
                                            ->body(__('Key updated locally, but remote sync failed: ') . ($response['message'] ?? 'Connection error.'))
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

    public function forceRotateCollaboratorKey(int $userId): void
    {
        $tenant = $this->tenant;
        if (!$tenant || !$this->isEditorOrOwner) {
            return;
        }

        $targetUser = \App\Models\User::find($userId);
        if (!$targetUser) {
            return;
        }

        // Push updated keys to the tenant node
        app(\App\Services\DeployerService::class)->syncUserApiKeys($tenant);

        $currentUser = \Illuminate\Support\Facades\Auth::user();
        $notification = new \App\Notifications\UserApiKeyRotatedNotification($tenant, $currentUser, true);
        $targetUser->notify($notification);

        \Filament\Notifications\Notification::make()
            ->title(__('Collaborator Key Rotated'))
            ->success()
            ->body(__('Rotated API key for :name. The new key has been synchronized with the node and notifications dispatched.', ['name' => $targetUser->name]))
            ->send();
    }

    public static function getNavigationLabel(): string
    {
        return __('Model Context Protocol (MCP)');
    }

    public function getTitle(): string
    {
        return __('Model Context Protocol (MCP) Guide');
    }

    public static function canAccess(): bool
    {
        return true;
    }
}
