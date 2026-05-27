<?php

namespace App\Filament\App\Pages;

use App\Services\RemoteEngineService;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Notifications\Notification;

class DataSync extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationLabel = 'Telemetry';
    protected static ?string $title = 'Data Telemetry';
    protected static string $view = 'filament.app.pages.data-sync';
    protected static ?string $slug = 'telemetry';

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
            
            $response = $service->getMonitoringData($tenant);
            
            \Illuminate\Support\Facades\Log::info("DataSync Telemetry Response:", ['response' => $response]);

            if (is_array($response) && isset($response['completion_percentage'])) {
                $this->syncData = $response;
            } else {
                $this->syncData = [];
                // Temporarily disabled while the Explorer's Status page is being reworked.
                /*
                Notification::make()
                    ->title('Explorers status unavailable')
                    ->body(function() use ($response) {
                        if (empty($response)) return 'The remote server returned an empty response.';
                        return $response['message'] ?? $response['error'] ?? 'Node responded with success: false. Data might not be ready yet.';
                    })
                    ->warning()
                    ->send();
                */
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
                ->label('Refresh Data')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action(fn() => $this->refreshData()),

            Action::make('triggerSync')
                ->label('Run All Explorers')
                ->icon('heroicon-o-play')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Start All Explorers?')
                ->modalDescription('This will launch all resting explorers to fetch the latest data from your social platforms.')
                ->action(function (RemoteEngineService $service) {
                    $tenant = Filament::getTenant();
                    $service->triggerSync($tenant);
                    Notification::make()->title('Explorers are now working')->success()->send();
                    $this->refreshData();
                }),
        ];
    }
}

