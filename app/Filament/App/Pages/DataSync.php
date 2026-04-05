<?php

namespace App\Filament\App\Pages;

use App\Services\RemoteEngineService;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Notifications\Notification;

class DataSync extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';
    protected static ?string $navigationLabel = 'Data Sync Status';
    protected static string $view = 'filament.app.pages.data-sync';
    protected static ?string $slug = 'data-sync';

    public array $syncData = [];
    public bool $isLoading = true;

    public function mount(): void
    {
        $this->refreshData();
    }

    /**
     * Fetch real-time synchronization data from the remote node.
     */
    public function refreshData(): void
    {
        $this->isLoading = true;
        
        try {
            $service = app(RemoteEngineService::class);
            $tenant = Filament::getTenant();
            
            // Calling the GBS Monitoring API we just discovered
            $response = $service->getMonitoringData($tenant);
            
            if ($response && ($response['success'] ?? false)) {
                $this->syncData = $response;
            } else {
                $this->syncData = [];
                Notification::make()
                    ->title('Unable to fetch live sync data')
                    ->body($response['message'] ?? 'Check connection to the remote node.')
                    ->warning()
                    ->send();
            }
        } catch (\Exception $e) {
            $this->syncData = [];
        }

        $this->isLoading = false;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Refresh Status')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action(fn() => $this->refreshData()),

            Action::make('triggerSync')
                ->label('Run All Jobs')
                ->icon('heroicon-o-play')
                ->color('success')
                ->requiresConfirmation()
                ->action(function (RemoteEngineService $service) {
                    $tenant = Filament::getTenant();
                    $service->triggerSync($tenant);
                    Notification::make()->title('Global Sync Triggered')->success()->send();
                    $this->refreshData();
                }),
        ];
    }

    /**
     * Perform an infrastructure action on a specific container.
     */
    public function toggleContainer(string $name, string $action): void
    {
        try {
            $service = app(RemoteEngineService::class);
            $tenant = Filament::getTenant();
            
            $response = $service->containerAction($tenant, $name, $action);
            
            if ($response && ($response['success'] ?? false)) {
                Notification::make()
                    ->title("Container $action successful")
                    ->body("$name has been $action" . "ed.")
                    ->success()
                    ->send();
            } else {
                 Notification::make()
                    ->title("Failed to $action container")
                    ->body($response['error'] ?? 'Unknown error.')
                    ->danger()
                    ->send();
            }
        } catch (\Exception $e) {
             Notification::make()
                ->title("Error")
                ->body($e->getMessage())
                ->danger()
                ->send();
        }

        $this->refreshData();
    }
}

