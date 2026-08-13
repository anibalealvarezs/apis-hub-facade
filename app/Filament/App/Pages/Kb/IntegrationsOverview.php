<?php

namespace App\Filament\App\Pages\Kb;

use App\Filament\App\Clusters\KnowledgeBase\Integrations as IntegrationsCluster;
use App\Filament\App\Pages\ChannelsIntegrationsReference;
use App\Filament\App\Pages\SyncingProcessReference;
use Filament\Pages\Page;

class IntegrationsOverview extends Page
{
    protected static ?string $cluster = IntegrationsCluster::class;
    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static ?int $navigationSort = 1;
    protected static string $view = 'filament.app.pages.kb.integrations-overview';
    protected static ?string $slug = 'overview';

    public static function getNavigationLabel(): string
    {
        return __('Overview');
    }

    public function getTitle(): string
    {
        return __('Integrations');
    }

    protected function getViewData(): array
    {
        return [
            'intro' => __('Reference material for supported channels and the syncing engine.'),
            'links' => [
                [
                    'url' => ChannelsIntegrationsReference::getUrl(),
                    'icon' => 'heroicon-o-puzzle-piece',
                    'title' => __('Channels & Integrations'),
                    'description' => __('See which channels are supported and how their integrations work.'),
                ],
                [
                    'url' => SyncingProcessReference::getUrl(),
                    'icon' => 'heroicon-o-arrow-path-rounded-square',
                    'title' => __('Syncing Engine & Telemetry'),
                    'description' => __('Understand how data is fetched, cached, and kept fresh.'),
                ],
            ],
        ];
    }
}
