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

        // Read cached sync telemetry (same cache key used by DataSync page)
        $cacheKey = "telemetry_data_{$tenant->id}";
        $telemetry = \Illuminate\Support\Facades\Cache::get($cacheKey);
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
