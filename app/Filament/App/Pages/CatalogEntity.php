<?php

namespace App\Filament\App\Pages;

use App\Models\Project;
use App\Models\User;
use App\Services\Catalog\CatalogScopeResolver;
use App\Services\Catalog\EntityReference;
use App\Services\RemoteEngineService;
use Filament\Facades\Filament;
use Filament\Navigation\NavigationItem;
use Filament\Pages\Page;
use Filament\Panel;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Route;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;

class CatalogEntity extends Page
{
    protected static string $view = 'filament.app.pages.catalog-entity';

    protected static ?string $slug = 'catalog/entity';

    public const array ENTITIES = [
        'accounts'  => 'Accounts',
        'campaigns' => 'Campaigns',
        'ad-sets'   => 'Ad Sets',
        'ad-groups' => 'Ad Groups',
        'ads'       => 'Ads',
        'pages'     => 'Pages',
        'posts'     => 'Posts',
        'queries'   => 'Queries',
        'events'    => 'Events',
        'customers' => 'Customers',
        'stores'    => 'Stores',
        'orders'    => 'Orders',
        'products'  => 'Products',
        'variants'  => 'Product Variants',
        'promotions'=> 'Promotions',
        'promo-codes'=> 'Promo Codes',
        'carts'     => 'Carts',
    ];

    public const array ENTITY_SINGULAR = [
        'accounts'   => 'Account',
        'campaigns'  => 'Campaign',
        'ad-sets'    => 'Ad Set',
        'ad-groups'  => 'Ad Group',
        'ads'        => 'Ad',
        'pages'      => 'Page',
        'posts'      => 'Post',
        'queries'    => 'Query',
        'events'     => 'Event',
        'customers'  => 'Customer',
        'stores'     => 'Store',
        'orders'     => 'Order',
        'products'   => 'Product',
        'variants'   => 'Product Variant',
        'promotions' => 'Promotion',
        'promo-codes'=> 'Promo Code',
        'carts'      => 'Cart',
    ];

    public const array ENTITY_ICONS = [
        'accounts'   => 'heroicon-o-identification',
        'campaigns'  => 'heroicon-o-megaphone',
        'ad-sets'    => 'heroicon-o-rectangle-stack',
        'ad-groups'  => 'heroicon-o-rectangle-stack',
        'ads'        => 'heroicon-o-cursor-arrow-rays',
        'pages'      => 'heroicon-o-document-duplicate',
        'posts'      => 'heroicon-o-chat-bubble-bottom-center-text',
        'queries'    => 'heroicon-o-magnifying-glass',
        'events'     => 'heroicon-o-bolt',
        'customers'  => 'heroicon-o-user-group',
        'stores'     => 'heroicon-o-building-storefront',
        'orders'     => 'heroicon-o-shopping-bag',
        'products'   => 'heroicon-o-cube',
        'variants'   => 'heroicon-o-swatch',
        'promotions' => 'heroicon-o-ticket',
        'promo-codes'=> 'heroicon-o-tag',
        'carts'      => 'heroicon-o-shopping-cart',
    ];

    public const array ENTITY_PHASES = [
        'accounts'  => 1,
        'campaigns' => 1,
        'ad-sets'   => 1,
        'ad-groups' => 1,
        'ads'       => 1,
        'pages'     => 2,
        'posts'     => 2,
        'queries'   => 2,
        'events'    => 2,
        'customers' => 2,
        'stores'    => 4,
        'variants'  => 4,
        'promotions'=> 4,
        'promo-codes'=> 4,
        'carts'     => 4,
        'orders'    => 4,
        'products'  => 4,
    ];

    public const array SEARCH_TYPE_OPTIONS = [
        ''           => 'All search types',
        'web'        => 'Web',
        'image'      => 'Image',
        'video'      => 'Video',
        'news'       => 'News',
        'discover'   => 'Discover',
        'googleNews' => 'Google News',
    ];

    #[Locked]
    public string $entity;

    #[Locked]
    public ?string $record = null;

    #[Url(except: 'all')]
    public string $scope = 'all';

    #[Url(except: '')]
    public string $channel = '';

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $status = '';

    #[Url(except: '')]
    public string $objective = '';

    #[Url(except: '')]
    public string $buyingType = '';

    #[Url(except: '')]
    public string $optimizationGoal = '';

    #[Url(except: '')]
    public string $billingEvent = '';

    #[Url(as: 'search_type', except: '')]
    public string $searchType = '';

    #[Url(except: '')]
    public string $parent = '';

    #[Url(except: 'name')]
    public string $sort = 'name';

    #[Url(except: 'ASC')]
    public string $dir = 'ASC';

    #[Url(except: 1)]
    public int $page = 1;

    #[Url(except: 25)]
    public int $perPage = 25;

    #[Url(except: '')]
    public string $dateStart = '';

    #[Url(except: '')]
    public string $dateEnd = '';

    #[Url(except: 'newest')]
    public string $activitySort = 'newest';

    #[Url(except: '')]
    public string $activityKind = '';

    #[Url(except: '')]
    public string $activityCursor = '';

    /** @var array<string, string> */
    public array $scopeOptions = [];

    /** @var array<string, mixed> */
    public array $authorizedScope = [];

    /** @var array<string, mixed> */
    public array $capabilities = [];

    /** @var array<int, array<string, mixed>> */
    public array $items = [];

    /** @var array<string, mixed> */
    public array $listMeta = [];

    /** @var array<string, mixed>|null */
    public ?array $profileData = null;

    /** @var array<string, mixed>|null */
    public ?array $activityData = null;

    /** @var array<string, mixed>|null */
    public ?array $summaryData = null;

    public ?string $stateCode = null;

    public ?string $stateMessage = null;

    public static function getRoutePath(): string
    {
        return '/catalog/{entity}/{record?}';
    }

    public static function routes(Panel $panel): void
    {
        Route::get(static::getRoutePath(), static::class)
            ->whereIn('entity', array_keys(static::ENTITIES))
            ->middleware(static::getRouteMiddleware($panel))
            ->withoutMiddleware(static::getWithoutRouteMiddleware($panel))
            ->name(static::getRelativeRouteName());
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('view_data') ?? false;
    }

    public function mount(string $entity, ?string $record = null): void
    {
        abort_unless(static::canAccess(), 403);
        abort_unless(isset(self::ENTITIES[$entity]), 404);

        $this->entity = $entity;
        $this->record = $record !== null && trim($record) !== '' ? trim(urldecode($record)) : null;

        if ($this->dateStart === '' || $this->dateEnd === '') {
            $today = now()->startOfDay();
            $this->dateEnd = $this->dateEnd !== '' ? $this->dateEnd : $today->format('Y-m-d');
            $this->dateStart = $this->dateStart !== '' ? $this->dateStart : $today->copy()->subDays(29)->format('Y-m-d');
        }

        if ($this->channel === '') {
            $this->channel = match ($this->entity) {
                'ad-sets'   => 'facebook_marketing',
                'ad-groups' => 'google_ads',
                'posts'     => 'facebook_organic',
                'queries'   => 'google_search_console',
                'stores', 'orders', 'products', 'variants', 'promotions', 'promo-codes', 'carts' => 'mailchimp',
                default     => '',
            };
        }

        $this->loadCatalogState();
    }

    public function getHeading(): string | Htmlable
    {
        if ($this->isDetailMode() && ! empty($this->profileData['record']['name'])) {
            return (string) $this->profileData['record']['name'];
        }

        return __(self::ENTITIES[$this->entity] ?? 'Entities');
    }

    public function getTitle(): string | Htmlable
    {
        if ($this->isDetailMode()) {
            $name = ! empty($this->profileData['record']['name'])
                ? (string) $this->profileData['record']['name']
                : (string) $this->record;

            $singular = __(self::ENTITY_SINGULAR[$this->entity] ?? self::ENTITIES[$this->entity] ?? 'Entity');
            $channelLabel = $this->profileData['record']['channel_label']
                ?? $this->getChannelOptionsForEntity($this->entity)[$this->profileData['record']['channel'] ?? '']
                ?? null;

            return $channelLabel
                ? "{$name} · {$singular} ({$channelLabel})"
                : "{$name} · {$singular}";
        }

        $base = __(self::ENTITIES[$this->entity] ?? 'Entities');
        $channelLabel = $this->channel !== '' ? ($this->getChannelOptions()[$this->channel] ?? null) : null;
        $scopeLabel = ($this->scope !== 'all' && ! empty($this->scopeOptions[$this->scope]))
            ? (string) $this->scopeOptions[$this->scope]
            : null;

        if ($channelLabel && $scopeLabel) {
            return "{$base} · {$channelLabel} ({$scopeLabel})";
        }

        if ($channelLabel) {
            return "{$base} · {$channelLabel}";
        }

        if ($scopeLabel) {
            return "{$base} ({$scopeLabel})";
        }

        return $base;
    }

    public function isDetailMode(): bool
    {
        return $this->record !== null && $this->record !== '';
    }

    public static function getNavigationItems(): array
    {
        $items = [];
        $sortIndex = 10;

        /** @var Project|null $project */
        $project = Filament::getTenant();
        /** @var User|null $user */
        $user = auth()->user();
        if (!$project || !$user) return [];
        $entities = app(\App\Services\Catalog\CatalogNavigation::class)->visibleEntities($project, $user);
        $groupingTypes = array_intersect_key($entities, array_flip(['ad-sets', 'ad-groups']));
        $groupingAdded = false;
        foreach ($entities as $entity => $label) {
            if (isset($groupingTypes[$entity])) {
                if ($groupingAdded) continue;
                $groupingAdded = true;
                $children = [];
                if (count($groupingTypes) > 1) {
                    foreach (['ad-sets' => 'Meta Ad Sets', 'ad-groups' => 'Google Ad Groups'] as $type => $providerLabel) {
                        $children[] = NavigationItem::make(__($providerLabel))->url(static::getUrl(['entity' => $type]))
                            ->isActiveWhen(fn (): bool => request()->routeIs(static::getRouteName()) && request()->route('entity') === $type);
                    }
                }
                $items[] = NavigationItem::make(__('Ad sets / groups'))->group(__('Entities'))->sort($sortIndex++)->icon('heroicon-o-rectangle-stack')
                    ->url(static::getUrl(['entity' => array_key_first($groupingTypes)]))->childItems($children)
                    ->isActiveWhen(fn (): bool => request()->routeIs(static::getRouteName()) && isset($groupingTypes[request()->route('entity')]));
                continue;
            }

            $items[] = NavigationItem::make(__($label))
                ->group(__('Entities'))
                ->sort($sortIndex++)
                ->icon(self::ENTITY_ICONS[$entity] ?? 'heroicon-o-cube')
                ->url(static::getUrl(['entity' => $entity]))
                ->isActiveWhen(fn (): bool => request()->routeIs(static::getRouteName())
                    && request()->route('entity') === $entity);
        }

        return $items;
    }

    public function updatedScope(): void
    {
        $this->page = 1;
        $this->activityCursor = '';
        $this->loadCatalogState();
    }

    public function updatedChannel(): void
    {
        $this->page = 1;
        $this->activityCursor = '';
        $this->loadCatalogState();
    }

    public function updatedSearch(): void
    {
        $this->page = 1;
        $this->loadCatalogState();
    }

    public function updatedStatus(): void
    {
        $this->page = 1;
        $this->loadCatalogState();
    }

    public function updatedObjective(): void
    {
        $this->page = 1;
        $this->loadCatalogState();
    }

    public function updatedBuyingType(): void
    {
        $this->page = 1;
        $this->loadCatalogState();
    }

    public function updatedOptimizationGoal(): void
    {
        $this->page = 1;
        $this->loadCatalogState();
    }

    public function updatedBillingEvent(): void
    {
        $this->page = 1;
        $this->loadCatalogState();
    }

    public function updatedSearchType(): void
    {
        $this->page = 1;
        $this->loadCatalogState();
    }

    public function updatedParent(): void
    {
        $this->page = 1;
        $this->loadCatalogState();
    }

    public function updatedDateStart(): void
    {
        $this->loadCatalogState();
    }

    public function updatedDateEnd(): void
    {
        $this->loadCatalogState();
    }

    public function updatedActivitySort(): void
    {
        $this->activityCursor = '';
        $this->loadCatalogState();
    }

    public function updatedActivityKind(): void
    {
        $this->activityCursor = '';
        $this->loadCatalogState();
    }

    public function sortBy(string $field): void
    {
        if ($this->sort === $field) {
            $this->dir = $this->dir === 'ASC' ? 'DESC' : 'ASC';
        } else {
            $this->sort = $field;
            $this->dir = 'ASC';
        }

        $this->page = 1;
        $this->loadCatalogState();
    }

    public function goToPage(int $targetPage): void
    {
        $lastPage = max(1, (int) ($this->listMeta['last_page'] ?? 1));
        $this->page = max(1, min($lastPage, $targetPage));
        $this->loadCatalogState();
    }

    public function updatedPerPage(): void
    {
        $this->perPage = in_array($this->perPage, [10, 25, 50, 100], true) ? $this->perPage : 25;
        $this->page = 1;
        $this->loadCatalogState();
    }

    /** A bounded page window, retaining access to the first and last pages. */
    public function paginationPages(): array
    {
        $last = max(1, (int) ($this->listMeta['last_page'] ?? 1));
        $current = max(1, min($last, (int) ($this->listMeta['page'] ?? $this->page)));
        $pages = array_unique([1, $last, ...range(max(1, $current - 1), min($last, $current + 1))]);
        sort($pages);
        $result = [];
        $previous = 0;
        foreach ($pages as $number) {
            if ($previous > 0 && $number > $previous + 1) $result[] = null;
            $result[] = $number;
            $previous = $number;
        }
        return $result;
    }

    public function loadMoreActivity(string $cursor): void
    {
        $this->activityCursor = $cursor;
        $this->loadCatalogState();
    }

    public function resetActivityPagination(): void
    {
        $this->activityCursor = '';
        $this->loadCatalogState();
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->status = '';
        $this->objective = '';
        $this->buyingType = '';
        $this->optimizationGoal = '';
        $this->billingEvent = '';
        $this->searchType = '';
        $this->parent = '';
        if (! in_array($this->entity, ['ad-sets', 'ad-groups', 'posts', 'queries'], true)) {
            $this->channel = '';
        }
        $this->page = 1;
        $this->loadCatalogState();
    }

    /**
     * Build a Catalog link preserving the viewer's active scope and compatible channel selection.
     *
     * @param  array<string, mixed>  $extraQuery
     */
    public function catalogUrl(string $targetEntity, ?string $targetRecord = null, array $extraQuery = []): string
    {
        $baseQuery = [
            'scope' => $this->scope !== 'all' ? $this->scope : null,
        ];

        if ($this->channel !== '' && ! isset($extraQuery['channel'])) {
            $allowedTargetChannels = array_keys($this->getChannelOptionsForEntity($targetEntity));
            if (in_array($this->channel, $allowedTargetChannels, true)) {
                $baseQuery['channel'] = $this->channel;
            }
        }

        if ($targetEntity === 'queries' && $this->searchType !== '' && ! isset($extraQuery['search_type'])) {
            $baseQuery['search_type'] = $this->searchType;
        }

        return EntityReference::buildUrl(
            $targetEntity,
            $targetRecord,
            array_merge($baseQuery, $extraQuery),
            Filament::getTenant()
        );
    }

    /**
     * Return available channel filter options for the current entity type.
     *
     * @return array<string, string>
     */
    public function getChannelOptions(): array
    {
        return $this->getChannelOptionsForEntity($this->entity);
    }

    /**
     * @return array<string, string>
     */
    public function getChannelOptionsForEntity(string $entity): array
    {
        if (in_array($entity, EntityReference::COMMERCE_ENTITY_TYPES, true)) {
            return [
                ''                 => __('All commerce channels'),
                'mailchimp'        => 'Mailchimp',
                'shopify'          => 'Shopify',
                'google_analytics' => 'Google Analytics',
            ];
        }

        return match ($entity) {
            'accounts' => [
                ''                      => __('All channels'),
                'facebook_marketing'    => 'Meta Ads',
                'google_ads'            => 'Google Ads',
                'facebook_organic'      => 'Meta Organic',
                'google_search_console' => 'Google Search Console',
                'google_analytics'      => 'Google Analytics',
                'mailchimp'             => 'Mailchimp',
            ],
            'campaigns' => [
                ''                   => __('All campaign channels'),
                'facebook_marketing' => 'Meta Ads',
                'google_ads'         => 'Google Ads',
                'mailchimp'          => 'Mailchimp',
            ],
            'ad-sets' => [
                'facebook_marketing' => 'Meta Ads',
            ],
            'ad-groups' => [
                'google_ads' => 'Google Ads',
            ],
            'ads' => [
                ''                   => __('All Ads channels'),
                'facebook_marketing' => 'Meta Ads',
                'google_ads'         => 'Google Ads',
            ],
            'pages' => [
                ''                      => __('All page channels'),
                'facebook_organic'      => 'Meta Organic',
                'google_search_console' => 'Google Search Console',
                'google_analytics'      => 'Google Analytics',
            ],
            'posts' => [
                'facebook_organic' => 'Meta Organic',
            ],
            'queries' => [
                'google_search_console' => 'Google Search Console',
            ],
            'events' => [
                ''                 => __('All event channels'),
                'google_analytics' => 'Google Analytics',
                'mailchimp'        => 'Mailchimp',
            ],
            'customers' => [
                ''               => __('All customer channels'),
                'mailchimp'      => 'Mailchimp',
                'facebook_leads' => 'Meta Leads',
                'shopify'        => 'Shopify',
                'klaviyo'        => 'Klaviyo',
            ],
            default => [
                '' => __('All channels'),
            ],
        };
    }

    /**
     * @return array<string, string>
     */
    public function getSearchTypeOptions(): array
    {
        $options = [];
        foreach (self::SEARCH_TYPE_OPTIONS as $key => $label) {
            $options[$key] = __($label);
        }

        return $options;
    }

    public function loadCatalogState(): void
    {
        $this->stateCode = null;
        $this->stateMessage = null;
        $this->items = [];
        $this->listMeta = [];
        $this->profileData = null;
        $this->activityData = null;
        $this->summaryData = null;

        /** @var Project|null $project */
        $project = Filament::getTenant();
        /** @var User|null $user */
        $user = auth()->user();

        if (! $project || ! $user) {
            $this->stateCode = 'empty_scope';
            $this->stateMessage = __('No active project context found.');

            return;
        }

        /** @var CatalogScopeResolver $scopeResolver */
        $scopeResolver = app(CatalogScopeResolver::class);
        $this->scopeOptions = $scopeResolver->getScopeOptions($project, $user);

        // Enforce Customer PII guard before any remote call
        if ($this->entity === 'customers' && ! $scopeResolver->canViewCustomerPii($project, $user)) {
            if ($this->isDetailMode()) {
                abort(404);
            }
            $this->stateCode = 'pii_restricted';
            $this->stateMessage = __('Customer records contain personally identifiable information (PII) and require unrestricted project asset access or explicit customer PII permission.');

            return;
        }

        // Check if entity is part of Phase 4 deferred entities (orders, products)
        if (! EntityReference::isSupported($this->entity)) {
            $phase = self::ENTITY_PHASES[$this->entity] ?? 4;
            $this->stateCode = 'phase_deferred';
            $this->stateMessage = __('This catalog entity (:entity) is registered in the shared catalog read contract and scheduled for Phase :phase rollout.', [
                'entity' => __(self::ENTITIES[$this->entity]),
                'phase'  => $phase,
            ]);

            return;
        }

        $effectiveChannel = $this->channel !== '' ? $this->channel : null;
        $singleChannelMap = [
            'ad-sets'   => 'facebook_marketing',
            'ad-groups' => 'google_ads',
            'posts'     => 'facebook_organic',
            'queries'   => 'google_search_console',
        ];
        if (isset($singleChannelMap[$this->entity])) {
            $effectiveChannel = $singleChannelMap[$this->entity];
        }

        $this->authorizedScope = $scopeResolver->resolveScope(
            $project,
            $user,
            $this->scope,
            $effectiveChannel
        );

        if (! empty($this->authorizedScope['unauthorized_group'])) {
            if ($this->isDetailMode()) {
                abort(404);
            }
            $this->stateCode = 'unauthorized_scope';
            $this->stateMessage = __('You do not have access to the selected Asset Group.');

            return;
        }

        if (empty($this->authorizedScope['has_any_assets'])) {
            if ($this->isDetailMode()) {
                abort(404);
            }
            $this->stateCode = 'empty_scope';
            $this->stateMessage = __('No enabled assets are available in the selected scope.');

            return;
        }

        /** @var RemoteEngineService $engine */
        $engine = app(RemoteEngineService::class);

        if (in_array($this->entity, EntityReference::COMMERCE_ENTITY_TYPES, true)) {
            $response = $engine->getCatalogCapabilities($project, ['authorized_scope' => $this->authorizedScope]);
            if (($response['status'] ?? null) === 'error') {
                $this->applyRemoteErrorState($response);
                return;
            }
            $this->capabilities = $response['data'] ?? [];
            if (empty($this->capabilities['entities'][$this->entity]['supported'])) {
                $this->stateCode = 'phase_deferred';
                $this->stateMessage = __('Upgrade the tenant to enable this commerce catalog capability.');
                return;
            }
        }

        if ($this->isDetailMode()) {
            $this->loadDetailMode($engine, $project);

            return;
        }

        $this->loadListMode($engine, $project, $effectiveChannel);
    }

    private function loadListMode(RemoteEngineService $engine, Project $project, ?string $effectiveChannel): void
    {
        $filters = array_filter([
            'channel'           => $effectiveChannel,
            'status'            => $this->status !== '' ? $this->status : null,
            'objective'         => $this->objective !== '' ? $this->objective : null,
            'buying_type'       => $this->buyingType !== '' ? $this->buyingType : null,
            'optimization_goal' => $this->optimizationGoal !== '' ? $this->optimizationGoal : null,
            'billing_event'     => $this->billingEvent !== '' ? $this->billingEvent : null,
            'search_type'       => ($this->entity === 'queries' && $this->searchType !== '') ? $this->searchType : null,
            'parent_reference'  => $this->parent !== '' ? $this->parent : null,
        ], static fn ($v) => $v !== null && $v !== '');

        $payload = [
            'authorized_scope' => $this->authorizedScope,
            'search'           => $this->search,
            'filters'          => $filters,
            'sort'             => [
                'field' => $this->sort,
                'dir'   => $this->dir,
            ],
            'pagination'       => [
                'page'     => $this->page,
                'per_page' => $this->perPage,
            ],
        ];

        $response = $engine->listCatalogEntities($project, $this->entity, $payload);

        if (($response['status'] ?? null) === 'error') {
            $this->applyRemoteErrorState($response);

            return;
        }

        $this->items = is_array($response['data'] ?? null) ? $response['data'] : [];
        $this->listMeta = is_array($response['meta'] ?? null) ? $response['meta'] : [
            'total'     => count($this->items),
            'page'      => $this->page,
            'per_page'  => $this->perPage,
            'last_page' => 1,
        ];

        if ($this->items === []) {
            $this->stateCode = 'empty_results';
            $this->stateMessage = __('No catalog records matched the current filters in your authorized scope.');

            return;
        }

        $this->loadListSummary($engine, $project, $effectiveChannel);
    }

    private function loadDetailMode(RemoteEngineService $engine, Project $project): void
    {
        $parsedRef = EntityReference::tryParse($this->record);
        if ($parsedRef === null || $parsedRef->getEntityType() !== $this->entity) {
            abort(404);
        }

        $profileFilters = array_filter([
            'search_type'      => ($this->entity === 'queries' && $this->searchType !== '') ? $this->searchType : null,
            'parent_reference' => $this->parent !== '' ? $this->parent : null,
        ], static fn ($v) => $v !== null && $v !== '');

        $profilePayload = [
            'authorized_scope' => $this->authorizedScope,
            'date_range'       => [
                'start_date' => $this->dateStart,
                'end_date'   => $this->dateEnd,
            ],
            'filters'          => $profileFilters,
        ];

        $profileResponse = $engine->getCatalogEntityProfile($project, $this->entity, $this->record, $profilePayload);

        if (($profileResponse['status'] ?? null) === 'error') {
            if (($profileResponse['error_code'] ?? null) === 'catalog_record_not_found' || ($profileResponse['http_status'] ?? null) === 404) {
                abort(404);
            }
            $this->applyRemoteErrorState($profileResponse);

            return;
        }

        if (empty($profileResponse['data']) || ! is_array($profileResponse['data'])) {
            abort(404);
        }

        $this->profileData = $profileResponse['data'];

        $activityPayload = [
            'authorized_scope' => $this->authorizedScope,
            'sort'             => $this->activitySort,
            'cursor'           => $this->activityCursor !== '' ? $this->activityCursor : null,
            'limit'            => 20,
            'filters'          => array_filter([
                'kind' => $this->activityKind !== '' ? $this->activityKind : null,
            ]),
        ];

        $activityResponse = $engine->getCatalogEntityActivity($project, $this->entity, $this->record, $activityPayload);
        if (($activityResponse['status'] ?? null) === 'success' && is_array($activityResponse['data'] ?? null)) {
            $this->activityData = $activityResponse['data'];
        }

        $this->loadDetailSummary($engine, $project, $parsedRef);
    }

    /**
     * Load Stage 2 Population-Level Entity Summary for List Mode.
     */
    private function loadListSummary(RemoteEngineService $engine, Project $project, ?string $effectiveChannel): void
    {
        $aggFilters = array_filter([
            'channel'          => $effectiveChannel,
            'status'           => $this->status !== '' ? strtolower($this->status) : null,
            'objective'        => $this->objective !== '' ? $this->objective : null,
            'buying_type'      => $this->buyingType !== '' ? $this->buyingType : null,
        ], static fn ($v) => $v !== null && $v !== '');

        if ($this->entity === 'orders') {
            $res = $this->fetchEntityAggregation($engine, $project, [
                'entity_type'           => 'orders',
                'authorized_scope'      => $this->authorizedScope,
                'measures'              => ['order_count', 'canonical_order_count', 'revenue', 'aov', 'total_discount'],
                'deduplicate_canonical' => true,
                'filters'               => $aggFilters,
            ]);
            if ($res === null) {
                return;
            }
            $row = $res['data'][0] ?? [];
            $meta = $res['meta'] ?? [];
            $byChannelRes = $this->fetchEntityAggregation($engine, $project, [
                'entity_type'      => 'orders',
                'authorized_scope' => $this->authorizedScope,
                'measures'         => ['order_count', 'revenue', 'aov'],
                'dimensions'       => ['channel'],
                'filters'          => $aggFilters,
            ]);

            $this->summaryData = [
                'mode'               => 'list',
                'entity'             => 'orders',
                'row'                => $row,
                'meta'               => $meta,
                'channel_comparison' => $byChannelRes['data'] ?? [],
            ];

            return;
        }

        if ($this->entity === 'customers') {
            $res = $this->fetchEntityAggregation($engine, $project, [
                'entity_type'      => 'customers',
                'authorized_scope' => $this->authorizedScope,
                'measures'         => [
                    'customer_count',
                    'canonical_customer_count',
                    'customer_ltv',
                    'avg_revenue_per_customer',
                    'repeat_customer_count',
                    'high_frequency_customer_count',
                    'repeat_buyer_rate',
                ],
                'filters'          => $aggFilters,
            ]);
            if ($res === null) {
                return;
            }
            $row = $res['data'][0] ?? [];
            $meta = $res['meta'] ?? [];

            $touchesRes = $this->fetchEntityAggregation($engine, $project, [
                'entity_type'      => 'customers',
                'authorized_scope' => $this->authorizedScope,
                'measures'         => ['pre_purchase_touches'],
            ]);
            $byChannelRes = $this->fetchEntityAggregation($engine, $project, [
                'entity_type'      => 'customers',
                'authorized_scope' => $this->authorizedScope,
                'measures'         => ['customer_count', 'canonical_customer_count'],
                'dimensions'       => ['channel'],
            ]);

            $this->summaryData = [
                'mode'               => 'list',
                'entity'             => 'customers',
                'row'                => $row,
                'meta'               => $meta,
                'touches'            => $touchesRes['data'] ?? [],
                'channel_comparison' => $byChannelRes['data'] ?? [],
            ];

            return;
        }

        if ($this->entity === 'stores') {
            $storeRes = $this->fetchEntityAggregation($engine, $project, [
                'entity_type'      => 'stores',
                'authorized_scope' => $this->authorizedScope,
                'measures'         => ['store_count'],
                'filters'          => $aggFilters,
            ]);
            $orderRes = $this->fetchEntityAggregation($engine, $project, [
                'entity_type'           => 'orders',
                'authorized_scope'      => $this->authorizedScope,
                'measures'              => ['order_count', 'revenue', 'aov'],
                'deduplicate_canonical' => true,
                'filters'               => $aggFilters,
            ]);
            if ($storeRes === null && $orderRes === null) {
                return;
            }
            $this->summaryData = [
                'mode'   => 'list',
                'entity' => 'stores',
                'row'    => array_merge($storeRes['data'][0] ?? [], $orderRes['data'][0] ?? []),
                'meta'   => $orderRes['meta'] ?? $storeRes['meta'] ?? [],
            ];

            return;
        }

        if ($this->entity === 'products') {
            $res = $this->fetchEntityAggregation($engine, $project, [
                'entity_type'      => 'products',
                'authorized_scope' => $this->authorizedScope,
                'measures'         => ['product_count', 'canonical_product_count', 'average_price', 'min_price', 'max_price'],
                'filters'          => $aggFilters,
            ]);
            if ($res === null) {
                return;
            }
            $this->summaryData = [
                'mode'   => 'list',
                'entity' => 'products',
                'row'    => $res['data'][0] ?? [],
                'meta'   => $res['meta'] ?? [],
            ];

            return;
        }

        if (in_array($this->entity, ['promotions', 'promo-codes'], true)) {
            $res = $this->fetchEntityAggregation($engine, $project, [
                'entity_type'      => 'discounts',
                'authorized_scope' => $this->authorizedScope,
                'measures'         => ['discount_count', 'total_usage'],
                'filters'          => $aggFilters,
            ]);
            if ($res === null) {
                return;
            }
            $this->summaryData = [
                'mode'   => 'list',
                'entity' => $this->entity,
                'row'    => $res['data'][0] ?? [],
                'meta'   => $res['meta'] ?? [],
            ];

            return;
        }

        if ($this->entity === 'campaigns') {
            $res = $this->fetchEntityAggregation($engine, $project, [
                'entity_type'      => 'campaigns',
                'authorized_scope' => $this->authorizedScope,
                'measures'         => ['campaign_count', 'total_budget', 'average_budget'],
                'filters'          => $aggFilters,
            ]);
            if ($res === null) {
                return;
            }
            $this->summaryData = [
                'mode'   => 'list',
                'entity' => 'campaigns',
                'row'    => $res['data'][0] ?? [],
                'meta'   => $res['meta'] ?? [],
            ];
        }
    }

    /**
     * Load Stage 1 Entity-Specific 360° Contextual Summary for Detail Mode.
     */
    private function loadDetailSummary(RemoteEngineService $engine, Project $project, EntityReference $parsedRef): void
    {
        $record = $this->profileData['record'] ?? [];
        $entityId = $parsedRef->getId();

        if ($this->entity === 'customers') {
            $ltvRes = $this->fetchEntityAggregation($engine, $project, [
                'entity_type'      => 'customers',
                'authorized_scope' => $this->authorizedScope,
                'measures'         => ['customer_count', 'canonical_customer_count', 'customer_ltv', 'repeat_customer_count'],
                'filters'          => ['customer_id' => $entityId],
            ]);
            $storeOrdersRes = $this->fetchEntityAggregation($engine, $project, [
                'entity_type'      => 'orders',
                'authorized_scope' => $this->authorizedScope,
                'measures'         => ['order_count', 'revenue'],
                'dimensions'       => ['store', 'channel'],
                'filters'          => ['customer_id' => $entityId],
            ]);
            $touchesRes = $this->fetchEntityAggregation($engine, $project, [
                'entity_type'      => 'customers',
                'authorized_scope' => $this->authorizedScope,
                'measures'         => ['pre_purchase_touches'],
                'filters'          => ['customer_id' => $entityId],
            ]);

            if ($ltvRes === null && $storeOrdersRes === null && $touchesRes === null) {
                return;
            }

            $this->summaryData = [
                'mode'            => 'detail',
                'entity'          => 'customers',
                'ltv_row'         => $ltvRes['data'][0] ?? [],
                'ltv_meta'        => $ltvRes['meta'] ?? [],
                'store_breakdown' => $storeOrdersRes['data'] ?? [],
                'touches'         => $touchesRes['data'] ?? [],
            ];

            return;
        }

        if ($this->entity === 'orders') {
            $canonicalOrderId = (int) ($record['attributes']['order_id'] ?? $entityId);
            $footprintRes = $this->fetchEntityAggregation($engine, $project, [
                'entity_type'      => 'orders',
                'authorized_scope' => $this->authorizedScope,
                'measures'         => ['order_count', 'revenue', 'total_discount'],
                'dimensions'       => ['channel', 'store', 'financial_status'],
                'filters'          => ['order_id' => $canonicalOrderId],
            ]);
            if ($footprintRes === null) {
                return;
            }

            $this->summaryData = [
                'mode'              => 'detail',
                'entity'            => 'orders',
                'channel_footprint' => $footprintRes['data'] ?? [],
                'meta'              => $footprintRes['meta'] ?? [],
            ];

            return;
        }

        if ($this->entity === 'stores') {
            $storeOrdersRes = $this->fetchEntityAggregation($engine, $project, [
                'entity_type'      => 'orders',
                'authorized_scope' => $this->authorizedScope,
                'measures'         => ['order_count', 'revenue', 'aov', 'total_discount', 'unique_customers'],
                'filters'          => ['store_id' => $entityId],
            ]);
            $storeProdsRes = $this->fetchEntityAggregation($engine, $project, [
                'entity_type'      => 'products',
                'authorized_scope' => $this->authorizedScope,
                'measures'         => ['product_count'],
                'filters'          => ['store_id' => $entityId],
            ]);
            if ($storeOrdersRes === null && $storeProdsRes === null) {
                return;
            }

            $this->summaryData = [
                'mode'   => 'detail',
                'entity' => 'stores',
                'row'    => array_merge($storeOrdersRes['data'][0] ?? [], $storeProdsRes['data'][0] ?? []),
                'meta'   => $storeOrdersRes['meta'] ?? [],
            ];

            return;
        }

        if ($this->entity === 'products') {
            $prodOrdersRes = $this->fetchEntityAggregation($engine, $project, [
                'entity_type'      => 'orders',
                'authorized_scope' => $this->authorizedScope,
                'measures'         => ['order_count', 'revenue'],
                'filters'          => ['product' => $entityId],
            ]);
            if ($prodOrdersRes === null) {
                return;
            }

            $this->summaryData = [
                'mode'   => 'detail',
                'entity' => 'products',
                'row'    => $prodOrdersRes['data'][0] ?? [],
                'meta'   => $prodOrdersRes['meta'] ?? [],
            ];
        }
    }

    /**
     * Safely invoke aggregateCatalogEntities without breaking callers or mocks when unavailable.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>|null
     */
    private function fetchEntityAggregation(RemoteEngineService $engine, Project $project, array $payload): ?array
    {
        try {
            $res = $engine->aggregateCatalogEntities($project, $payload);
            if (is_array($res) && ($res['status'] ?? null) === 'success' && isset($res['data'])) {
                return $res;
            }
        } catch (\Throwable) {
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $response
     */
    private function applyRemoteErrorState(array $response): void
    {
        $code = (string) ($response['error_code'] ?? 'remote_error');
        $this->stateCode = match ($code) {
            'tenant_upgrading'            => 'tenant_upgrading',
            'upstream_capacity_exhausted' => 'capacity_exhausted',
            'catalog_unavailable'         => 'catalog_unavailable',
            default                       => 'remote_error',
        };

        $this->stateMessage = (string) ($response['message'] ?? $response['error'] ?? __('Unable to load catalog data from the tenant node.'));
    }
}
