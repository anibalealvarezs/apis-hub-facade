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

class McpAccessReference extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $cluster = Integrations::class;
    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';
    protected static ?int $navigationSort = 5;
    protected static string $view = 'filament.app.pages.mcp-access-reference';

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
        return $this->tenant?->public_api_key ?? '';
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

        return $form
            ->schema([
                TextInput::make('app_api_key')
                    ->label(__('Public API Key (Bearer / Query Token)'))
                    ->password()
                    ->revealable()
                    ->disabled()
                    ->helperText(__('Used to authenticate with the Model Context Protocol (MCP) server.'))
                    ->hintAction(
                        Action::make('rotateKey')
                            ->icon('heroicon-m-arrow-path')
                            ->color('warning')
                            ->disabled(fn () => ! $tenant || ! $tenant->is_active || $tenant->billing_status === 'suspended' || ! \Illuminate\Support\Facades\Auth::user()->can('edit_preferences'))
                            ->requiresConfirmation()
                            ->modalHeading(__('Rotate API Key?'))
                            ->modalDescription(__('Generating a new key will immediately invalidate the current one. You must update all connected AI agents (Antigravity, Claude Desktop, Cursor) with the new key.'))
                            ->modalSubmitActionLabel(__('Yes, rotate and push'))
                            ->action(function (\App\Services\DeployerService $deployer) {
                                $tenant = $this->tenant;
                                if (! $tenant) {
                                    return;
                                }

                                $newKey = bin2hex(random_bytes(32));

                                // 1. Persist locally
                                $tenant->update(['public_api_key' => $newKey]);

                                // 2. Push to remote environment
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

                                // 3. Notify owners & editors
                                $currentUser = \Illuminate\Support\Facades\Auth::user();
                                $notification = new \App\Notifications\ApiKeyRotatedNotification($tenant, $currentUser);
                                foreach ($tenant->getEditorsAndOwners() as $userToNotify) {
                                    $userToNotify->notify($notification);
                                }

                                $this->form->fill(['app_api_key' => $newKey]);
                            })
                    ),
            ])
            ->statePath('data');
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
