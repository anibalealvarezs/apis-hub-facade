<?php

namespace App\Filament\App;

use Filament\Navigation\NavigationManager;

class AppNavigationManager extends NavigationManager
{
    public const ASSET_GROUPS = 'asset-groups-shortcut';

    public function get(): array
    {
        $groups = parent::get();

        // Filament sorts unlabeled groups first. Remove the heading after sorting.
        if (isset($groups[self::ASSET_GROUPS])) {
            $groups[self::ASSET_GROUPS]->label(null)->collapsible(false);
        }

        return $groups;
    }
}
