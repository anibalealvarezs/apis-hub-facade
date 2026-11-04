<?php

namespace Tests\Feature;

use App\Filament\App\AppNavigationManager;
use Filament\Facades\Filament;
use Filament\Navigation\NavigationItem;
use Filament\Pages\Page;
use Filament\Panel;
use Tests\TestCase;

class AppNavigationTest extends TestCase
{
    public function test_page_registration_populates_the_sidebar_in_order(): void
    {
        $panel = Panel::make()
            ->id('app')
            ->navigationGroups(Filament::getPanel('app')->getNavigationGroups())
            ->pages([NavigationFixturePage::class]);

        Filament::setCurrentPanel($panel);

        $groups = $panel->getNavigation();

        $this->assertSame(
            [null, __('Data Sources'), null, __('Catalog'), __('Analysis')],
            array_values(array_map(fn ($group) => $group->getLabel(), $groups)),
        );
        $this->assertArrayHasKey(__('Asset Groups'), $groups[AppNavigationManager::ASSET_GROUPS]->getItems());
    }
}

class NavigationFixturePage extends Page
{
    public static function registerNavigationItems(): void
    {
        $items = [
            __('Home') => null,
            __('Sources') => __('Data Sources'),
            __('Asset Groups') => AppNavigationManager::ASSET_GROUPS,
            __('Accounts') => __('Catalog'),
            __('Alerts') => __('Analysis'),
        ];

        foreach ($items as $label => $group) {
            Filament::getCurrentPanel()->navigationItems([
                NavigationItem::make($label)->group($group)->url('/'),
            ]);
        }
    }
}
