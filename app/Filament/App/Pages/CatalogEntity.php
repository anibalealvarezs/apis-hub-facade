<?php

namespace App\Filament\App\Pages;

use Filament\Navigation\NavigationItem;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Livewire\Attributes\Locked;

class CatalogEntity extends Page
{
    protected static string $view = 'filament.app.pages.catalog-entity';

    protected static ?string $slug = 'catalog/entity';

    private const ENTITIES = [
        'accounts' => 'Accounts',
        'campaigns' => 'Campaigns',
        'ad-sets' => 'Ad Sets',
        'ad-groups' => 'Ad Groups',
        'ads' => 'Ads',
        'pages' => 'Pages',
        'posts' => 'Posts',
        'queries' => 'Queries',
        'customers' => 'Customers',
        'orders' => 'Orders',
        'products' => 'Products',
        'events' => 'Events',
    ];

    #[Locked]
    public string $entity;

    public static function getRoutePath(): string
    {
        return '/catalog/{entity}';
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('view_data') ?? false;
    }

    public function mount(string $entity): void
    {
        abort_unless(static::canAccess(), 403);
        abort_unless(isset(self::ENTITIES[$entity]), 404);

        $this->entity = $entity;
    }

    public function getTitle(): string | Htmlable
    {
        return __(self::ENTITIES[$this->entity]);
    }

    public static function getNavigationItems(): array
    {
        $items = [];

        foreach (self::ENTITIES as $entity => $label) {
            $items[] = NavigationItem::make(__($label))
                ->group(__('Catalog'))
                ->sort(count($items))
                ->url(static::getUrl(['entity' => $entity]))
                ->isActiveWhen(fn (): bool => request()->routeIs(static::getRouteName())
                    && request()->route('entity') === $entity);
        }

        return $items;
    }
}
