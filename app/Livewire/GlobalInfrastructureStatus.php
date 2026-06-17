<?php

namespace App\Livewire;

use Filament\Facades\Filament;
use Livewire\Component;

class GlobalInfrastructureStatus extends Component
{
    public function render()
    {
        $tenant = Filament::getTenant();

        if (!$tenant) {
            return <<<'HTML'
            <div></div>
            HTML;
        }

        $latestLog = $tenant->deploymentLogs()->latest()->first();

        // The widget polls every 30s. We cache for 30s to prevent spamming the node if multiple tabs are open.
        $cacheKey = "telemetry_data_{$tenant->id}";
        $telemetry = \Illuminate\Support\Facades\Cache::remember($cacheKey, now()->addSeconds(30), function () use ($tenant) {
            try {
                return app(\App\Services\RemoteEngineService::class)->getSyncTelemetry($tenant);
            } catch (\Exception $e) {
                return null;
            }
        });

        $syncPercentage = null;
        if (is_array($telemetry) && isset($telemetry['completion_percentage'])) {
            $syncPercentage = number_format((float)$telemetry['completion_percentage'], 2);
        }

        return view('livewire.global-infrastructure-status', [
            'latestLog' => $latestLog,
            'tenant' => $tenant,
            'syncPercentage' => $syncPercentage,
        ]);
    }
}
