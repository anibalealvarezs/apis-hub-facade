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

        return view('livewire.global-infrastructure-status', [
            'latestLog' => $latestLog,
        ]);
    }
}
