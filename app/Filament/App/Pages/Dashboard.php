<?php

namespace App\Filament\App\Pages;

use App\Filament\App\Widgets\ProjectInfoWidget;
use App\Filament\App\Widgets\ChannelHealthWidget;
use App\Filament\App\Widgets\EntityCounterWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    public function getWidgets(): array
    {
        return [
            ProjectInfoWidget::class,
            ChannelHealthWidget::class,
            EntityCounterWidget::class,
        ];
    }
}
