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
    protected static ?string $navigationGroup = 'Exploration & Telemetry';
    public static function getNavigationLabel(): string
    {
        return __('Telemetry');
    }

    public function getTitle(): string
    {
        return __('Data Telemetry');
    }
    protected static string $view = 'filament.app.pages.data-sync';
    protected static ?string $slug = 'telemetry';

    public array $syncData = [];
    public bool $isLoading = true;

    public function mount(): void
    {
        $this->refreshData();
    }

    /**
     * Fetch real-time synchronization data from the remote node or from cache.
     */
    public function refreshData(bool $force = false): void
    {
        $this->isLoading = true;
        
        try {
            $service = app(RemoteEngineService::class);
            $tenant = Filament::getTenant();
            
            $cacheKey = "telemetry_data_{$tenant->id}";
            if ($force) {
                \Illuminate\Support\Facades\Cache::forget($cacheKey);
            }

            $response = \Illuminate\Support\Facades\Cache::remember($cacheKey, now()->addHours(6), function () use ($service, $tenant) {
                return $service->getSyncTelemetry($tenant);
            });
            
            \Illuminate\Support\Facades\Log::info("DataSync Telemetry Response:", ['response' => $response]);

            if (is_array($response) && isset($response['completion_percentage'])) {
                // Enrich with names from Project sync_config for channels like FB Organic/Marketing
                try {
                    $accountMap = [];
                    $syncConfig = $tenant->sync_config ?? [];
                    
                    // Recursively search for any array that contains arrays with 'id' and 'name'
                    $extractNames = function($data) use (&$extractNames, &$accountMap) {
                        if (!is_array($data)) return;
                        
                        // Check if current node represents an asset
                        if (isset($data['id']) && isset($data['name']) && is_string($data['name'])) {
                            $accountMap[(string)$data['id']] = [
                                'name' => $data['name'],
                                'ig_username' => null,
                            ];
                            
                            // Check for IG account inside FB page
                            if (isset($data['instagram_business_account']) && is_array($data['instagram_business_account'])) {
                                $ig = $data['instagram_business_account'];
                                if (isset($ig['username'])) {
                                    $accountMap[(string)$data['id']]['ig_username'] = $ig['username'];
                                }
                            }
                        }
                        
                        // Keep digging deeper
                        foreach ($data as $key => $val) {
                            if (is_array($val)) {
                                $extractNames($val);
                            }
                        }
                    };
                    
                    $extractNames($syncConfig);

                    // Apply to response and normalize assets to associative array
                    if (isset($response['channels'])) {
                        foreach ($response['channels'] as &$chanData) {
                            if (isset($chanData['assets'])) {
                                $newAssets = [];
                                foreach ($chanData['assets'] as $key => $asset) {
                                    $actualId = (is_array($asset) && isset($asset['id'])) ? (string)$asset['id'] : (string)$key;
                                    
                                    // Set actual ID explicitly
                                    if (is_array($asset)) {
                                        $asset['id'] = $actualId;
                                        if (empty($asset['name']) && isset($accountMap[$actualId])) {
                                            $asset['name'] = $accountMap[$actualId]['name'];
                                            if (!empty($accountMap[$actualId]['ig_username'])) {
                                                $asset['ig_username'] = $accountMap[$actualId]['ig_username'];
                                            }
                                        }
                                    }
                                    
                                    $newAssets[$actualId] = $asset;
                                }
                                $chanData['assets'] = $newAssets;
                            }
                        }
                    }
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::warning("DataSync Name Enrichment failed: " . $e->getMessage());
                }

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
            \Illuminate\Support\Facades\Log::error("DataSync refreshData Exception: " . $e->getMessage());
            $this->syncData = [];
        }

        $this->isLoading = false;
    }

    protected function getHeaderActions(): array
    {
        $tenant = Filament::getTenant();
        $cooldownDays = 15;
        $canResync = true;
        $resyncMessage = __('This action will clear all queues and restart telemetry, forcing a total historical resync.');
        
        if ($tenant->last_historical_resync_at) {
            $daysSince = now()->diffInDays($tenant->last_historical_resync_at);
            if ($daysSince < $cooldownDays) {
                $canResync = false;
                $resyncMessage = __('Available in :days days.', ['days' => ($cooldownDays - $daysSince)]);
            }
        }

        return [
            Action::make('refresh')
                ->label(__('Refresh Data'))
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action(fn() => $this->refreshData(true)),


            Action::make('stopJobs')
                ->label(__('Pause All Explorers'))
                ->icon('heroicon-o-stop-circle')
                ->color('danger')
                ->disabled(fn () => !Filament::getTenant()->is_active || Filament::getTenant()->billing_status === 'suspended' || !auth()->user()->can('edit_preferences'))
                ->requiresConfirmation()
                ->action(function (RemoteEngineService $service) {
                    $tenant = Filament::getTenant();
                    $response = $service->stopJobs($tenant);
                    
                    Notification::make()
                        ->title(($response['status'] ?? '') === 'success' ? __('Explorers are resting') : __('Action Failed'))
                        ->body($response['message'] ?? '')
                        ->send();
                    $this->refreshData(true);
                }),

            Action::make('resyncAll')
                ->label(__('Nuclear Resync'))
                ->icon('heroicon-o-fire')
                ->color('danger')
                ->visible(fn () => auth()->user()->can('edit_preferences'))
                ->disabled(!$canResync)
                ->tooltip($canResync ? __('Reset all jobs and force a historical resync') : $resyncMessage)
                ->requiresConfirmation()
                ->modalHeading(__('Historical Resync (Nuclear)'))
                ->modalDescription($resyncMessage . ' ' . __('Please, type "RESYNC" to confirm this destructive action.'))
                ->form([
                    \Filament\Forms\Components\TextInput::make('confirmation')
                        ->label(__('Confirmation'))
                        ->required()
                        ->rules(['in:RESYNC'])
                        ->placeholder(__('Type RESYNC'))
                        ->validationMessages([
                            'in' => __('You must type RESYNC exactly to continue.'),
                        ])
                ])
                ->action(function (array $data, RemoteEngineService $service) use ($tenant) {
                    if ($data['confirmation'] !== 'RESYNC') {
                        return;
                    }
                    $response = $service->triggerHistoricalResync($tenant);
                    if (($response['status'] ?? '') === 'error') {
                        Notification::make()->title(__('Error:') . ' ' . ($response['error'] ?? 'Unknown'))->danger()->send();
                    } else {
                        $tenant->update(['last_historical_resync_at' => now()]);
                        Notification::make()->title(__('Historical resync initiated'))->success()->send();
                    }
                }),
        ];
    }
}

