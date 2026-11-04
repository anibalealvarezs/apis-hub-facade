<?php

namespace App\Filament\App\Resources\DashboardResource\Traits;

use Illuminate\Contracts\View\View;

trait HasDashboardEnvironmentHeader
{
    public function getHeader(): ?View
    {
        return view('filament.app.pages.partials.dashboard-environment-header');
    }
}
