<?php

namespace App\Filament\App\Pages\Kb;

use App\Filament\App\Clusters\KnowledgeBase\Data as DataCluster;
use App\Filament\App\Pages\DataExplorerReference;
use Filament\Pages\Page;

class DataOverview extends Page
{
    protected static ?string $cluster = DataCluster::class;
    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static ?int $navigationSort = 1;
    protected static string $view = 'filament.app.pages.kb.data-overview';
    protected static ?string $slug = 'overview';

    public static function getNavigationLabel(): string
    {
        return __('Overview');
    }

    public function getTitle(): string
    {
        return __('Data');
    }

    protected function getViewData(): array
    {
        return [
            'intro' => __('Reference material for exploring and querying your cached datasets.'),
            'links' => [
                [
                    'url' => DataExplorerReference::getUrl(),
                    'icon' => 'heroicon-o-presentation-chart-line',
                    'title' => __('Data Explorers'),
                    'description' => __('Understand how to explore and query your cached datasets.'),
                ],
            ],
        ];
    }
}
