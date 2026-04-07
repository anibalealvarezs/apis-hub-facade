<?php

namespace App\Filament\App\Widgets;

use Filament\Facades\Filament;
use Filament\Widgets\Widget;

class ProjectInfoWidget extends Widget
{
    protected static string $view = 'filament.app.widgets.project-info-widget';

    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = -3;

    public function getProject(): \App\Models\Project
    {
        return Filament::getTenant();
    }

    public function getServerName(): string
    {
        $project = $this->getProject();
        return $project->server?->name ?? 'Not assigned';
    }

    public function getStatusColor(): string
    {
        $project = $this->getProject();

        if ($project->health_status === 'online') {
            return 'success';
        }

        if ($project->is_active) {
            return 'warning';
        }

        return 'danger';
    }

    public function getStatusLabel(): string
    {
        $project = $this->getProject();

        if ($project->health_status === 'online') {
            return 'Server Online';
        }

        if ($project->is_active) {
            return 'Building Server';
        }

        return 'Server Offline';
    }
}
