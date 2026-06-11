<?php

namespace App\Filament\App\Pages;

use App\Filament\App\Resources\DashboardResource;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    public function getWidgets(): array
    {
        return [];
    }

    public function mount(): void
    {
        $projectId = auth()->user()->currentProject?->id;
        if (!$projectId) {
            return;
        }

        $default = \App\Models\Dashboard::where('project_id', $projectId)
            ->where('is_default', true)
            ->first();

        if ($default) {
            $this->redirect(DashboardResource::getUrl('view', ['record' => $default]));
        } else {
            $this->redirect(DashboardResource::getUrl('index'));
        }
    }
}
