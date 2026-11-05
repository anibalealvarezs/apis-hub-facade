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
use Illuminate\Contracts\Support\Htmlable;
use Livewire\Attributes\Url;

class MarketingOverview extends Page
{
    protected static string $view = 'filament.app.pages.marketing-overview';

    protected static ?string $slug = 'catalog/overview';

    #[Url(except: 'all')]
    public string $scope = 'all';

    #[Url(except: '')]
    public string $dateStart = '';

    #[Url(except: '')]
    public string $dateEnd = '';

    #[Url(except: '30d')]
    public string $preset = '30d';

    /** @var array<string, string> */
    public array $scopeOptions = [];

    /** @var array<string, mixed> */
    public array $authorizedScope = [];

    /** @var array<string, mixed> */
    public array $capabilities = [];

    /** @var array<string, array<string, mixed>> */
    public array $domainSections = [];

    /** @var array<string, int> */
    public array $channelAssetCounts = [];

    /** @var array<int, array<string, mixed>> */
    public array $recentRecords = [];

    public bool $canViewPii = false;

    public bool $entityMeasuresSupported = false;

    public ?string $stateCode = null;

    public ?string $stateMessage = null;

    public static function getRoutePath(): string
    {
        return '/catalog/overview';
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('view_data') ?? false;
    }

    public static function getNavigationItems(): array
    {
        return [
            NavigationItem::make(__('Marketing Overview'))
                ->group(__('Catalog'))
                ->sort(1)
                ->icon('heroicon-o-squares-2x2')
                ->url(static::getUrl())
                ->isActiveWhen(fn (): bool => request()->routeIs(static::getRouteName())),
        ];
    }

    public function getTitle(): string | Htmlable
    {
        return __('Marketing Overview');
    }

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);

        if ($this->dateStart === '' || $this->dateEnd === '') {
            $this->applyPresetDates($this->preset !== '' ? $this->preset : '30d');
        }

        $this->loadOverviewState();
    }

    public function setPreset(string $preset): void
    {
        $this->preset = $preset;
        $this->applyPresetDates($preset);
        $this->loadOverviewState();
    }

    public function updatedScope(): void
    {
        $this->loadOverviewState();
    }

    public function updatedDateStart(): void
    {
        $this->preset = 'custom';
        $this->loadOverviewState();
    }

    public function updatedDateEnd(): void
    {
        $this->preset = 'custom';
        $this->loadOverviewState();
    }

    /**
     * Build a Catalog link preserving the viewer's active scope.
     *
     * @param  array<string, mixed>  $extraQuery
     */
    public function catalogUrl(string $targetEntity, ?string $targetRecord = null, array $extraQuery = []): string
    {
        $baseQuery = [
            'scope' => $this->scope !== 'all' ? $this->scope : null,
        ];

        return EntityReference::buildUrl(
            $targetEntity,
            $targetRecord,
            array_merge($baseQuery, $extraQuery),
            Filament::getTenant()
        );
    }

    public function loadOverviewState(): void
    {
        $this->stateCode = null;
        $this->stateMessage = null;
        $this->domainSections = [];
        $this->channelAssetCounts = [];
        $this->recentRecords = [];
        $this->capabilities = [];
        $this->entityMeasuresSupported = false;

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
        $this->canViewPii = $scopeResolver->canViewCustomerPii($project, $user);

        $this->authorizedScope = $scopeResolver->resolveScope($project, $user, $this->scope);

        if (! empty($this->authorizedScope['unauthorized_group'])) {
            $this->stateCode = 'unauthorized_scope';
            $this->stateMessage = __('You do not have access to the selected Asset Group.');

            return;
        }

        if (empty($this->authorizedScope['has_any_assets'])) {
            $this->stateCode = 'empty_scope';
            $this->stateMessage = __('No enabled assets are available in the selected scope.');

            return;
        }

        // Compute deduplicated permitted asset counts per channel
        foreach (($this->authorizedScope['channels'] ?? []) as $ch => $chData) {
            $platformIds = array_values(array_unique(array_map('strval', $chData['platform_ids'] ?? [])));
            $this->channelAssetCounts[$ch] = count($platformIds);
        }

        /** @var RemoteEngineService $engine */
        $engine = app(RemoteEngineService::class);

        $capResponse = $engine->getCatalogCapabilities($project, $this->authorizedScope);
        if (($capResponse['status'] ?? null) === 'error') {
            $this->applyRemoteErrorState($capResponse);

            return;
        }

        $this->capabilities = is_array($capResponse['data'] ?? null) ? $capResponse['data'] : [];
        $this->entityMeasuresSupported = ! empty($this->capabilities['entity_measures']['supported']);

        $this->composeDomains($engine, $project);
    }

    private function composeDomains(RemoteEngineService $engine, Project $project): void
    {
        $channelsInScope = array_keys($this->authorizedScope['channels'] ?? []);

        // 1. Paid Advertising (Meta Ads + Google Ads)
        $adsChannels = array_values(array_intersect($channelsInScope, ['facebook_marketing', 'google_ads']));
        $adsAssetsCount = ($this->channelAssetCounts['facebook_marketing'] ?? 0) + ($this->channelAssetCounts['google_ads'] ?? 0);
        $adsSummary = $this->fetchEntitySummary($engine, $project, 'campaigns', null, 3);
        $adSetsSummary = $this->fetchEntitySummary($engine, $project, 'ad-sets', 'facebook_marketing', 2);
        $adGroupsSummary = $this->fetchEntitySummary($engine, $project, 'ad-groups', 'google_ads', 2);
        $adsLeafSummary = $this->fetchEntitySummary($engine, $project, 'ads', null, 3);

        $this->domainSections['paid_advertising'] = [
            'title'         => __('Paid Advertising'),
            'description'   => __('Ad accounts, campaigns, ad sets/ad groups, and ads across Meta Ads and Google Ads.'),
            'coverage'      => $adsAssetsCount > 0 ? 'active' : 'no_assets',
            'asset_count'   => $adsAssetsCount,
            'channels'      => $adsChannels,
            'indicators'    => [
                [
                    'label'  => __('Ad Accounts / Assets'),
                    'count'  => $adsAssetsCount,
                    'entity' => 'accounts',
                    'url'    => $this->catalogUrl('accounts'),
                ],
                [
                    'label'  => __('Campaigns'),
                    'count'  => $adsSummary['total'],
                    'entity' => 'campaigns',
                    'url'    => $this->catalogUrl('campaigns'),
                ],
                [
                    'label'  => __('Meta Ad Sets'),
                    'count'  => $adSetsSummary['total'],
                    'entity' => 'ad-sets',
                    'url'    => $this->catalogUrl('ad-sets', null, ['channel' => 'facebook_marketing']),
                ],
                [
                    'label'  => __('Google Ad Groups'),
                    'count'  => $adGroupsSummary['total'],
                    'entity' => 'ad-groups',
                    'url'    => $this->catalogUrl('ad-groups', null, ['channel' => 'google_ads']),
                ],
                [
                    'label'  => __('Ads'),
                    'count'  => $adsLeafSummary['total'],
                    'entity' => 'ads',
                    'url'    => $this->catalogUrl('ads'),
                ],
            ],
            'highlights' => array_slice(array_merge($adsSummary['items'], $adsLeafSummary['items']), 0, 4),
        ];

        // 2. Organic & Social Content (Meta Organic Pages & Posts + GA4 Pages)
        $organicAssetsCount = ($this->channelAssetCounts['facebook_organic'] ?? 0) + ($this->channelAssetCounts['google_analytics'] ?? 0);
        $pagesSummary = $this->fetchEntitySummary($engine, $project, 'pages', null, 3);
        $postsSummary = $this->fetchEntitySummary($engine, $project, 'posts', 'facebook_organic', 3);

        $this->domainSections['organic_content'] = [
            'title'       => __('Organic & Social Content'),
            'description' => __('Facebook Pages, linked Instagram business accounts, published social posts, and tracked landing pages.'),
            'coverage'    => $organicAssetsCount > 0 ? 'active' : 'no_assets',
            'asset_count' => $organicAssetsCount,
            'channels'    => array_values(array_intersect($channelsInScope, ['facebook_organic', 'google_analytics'])),
            'indicators'  => [
                [
                    'label'  => __('Pages'),
                    'count'  => $pagesSummary['total'],
                    'entity' => 'pages',
                    'url'    => $this->catalogUrl('pages'),
                ],
                [
                    'label'  => __('Social Posts & Media'),
                    'count'  => $postsSummary['total'],
                    'entity' => 'posts',
                    'url'    => $this->catalogUrl('posts', null, ['channel' => 'facebook_organic']),
                ],
            ],
            'highlights' => array_slice(array_merge($postsSummary['items'], $pagesSummary['items']), 0, 4),
        ];

        // 3. Search Visibility (Google Search Console Properties, Pages, Queries)
        $gscAssetsCount = $this->channelAssetCounts['google_search_console'] ?? 0;
        $queriesSummary = $this->fetchEntitySummary($engine, $project, 'queries', 'google_search_console', 4);

        $this->domainSections['search_visibility'] = [
            'title'       => __('Search Visibility'),
            'description' => __('Google Search Console properties, indexed pages, and search queries scoped by search type.'),
            'coverage'    => $gscAssetsCount > 0 ? 'active' : 'no_assets',
            'asset_count' => $gscAssetsCount,
            'channels'    => array_values(array_intersect($channelsInScope, ['google_search_console'])),
            'indicators'  => [
                [
                    'label'  => __('Search Properties'),
                    'count'  => $gscAssetsCount,
                    'entity' => 'accounts',
                    'url'    => $this->catalogUrl('accounts', null, ['channel' => 'google_search_console']),
                ],
                [
                    'label'  => __('Search Queries'),
                    'count'  => $queriesSummary['total'],
                    'entity' => 'queries',
                    'url'    => $this->catalogUrl('queries', null, ['channel' => 'google_search_console']),
                ],
            ],
            'highlights' => $queriesSummary['items'],
        ];

        // 4. Email & Lifecycle Events (Mailchimp Audiences/Campaigns + GA4/Mailchimp Events)
        $lifecycleAssetsCount = ($this->channelAssetCounts['mailchimp'] ?? 0) + ($this->channelAssetCounts['google_analytics'] ?? 0);
        $mcCampaignsSummary = $this->fetchEntitySummary($engine, $project, 'campaigns', 'mailchimp', 3);
        $eventsSummary = $this->fetchEntitySummary($engine, $project, 'events', null, 3);

        $this->domainSections['email_lifecycle'] = [
            'title'       => __('Email & Lifecycle Events'),
            'description' => __('Mailchimp audiences and campaigns alongside GA4 and Mailchimp named event definitions.'),
            'coverage'    => $lifecycleAssetsCount > 0 ? 'active' : 'no_assets',
            'asset_count' => $lifecycleAssetsCount,
            'channels'    => array_values(array_intersect($channelsInScope, ['mailchimp', 'google_analytics'])),
            'indicators'  => [
                [
                    'label'  => __('Mailchimp Campaigns'),
                    'count'  => $mcCampaignsSummary['total'],
                    'entity' => 'campaigns',
                    'url'    => $this->catalogUrl('campaigns', null, ['channel' => 'mailchimp']),
                ],
                [
                    'label'  => __('Named Event Types'),
                    'count'  => $eventsSummary['total'],
                    'entity' => 'events',
                    'url'    => $this->catalogUrl('events'),
                ],
            ],
            'highlights' => array_slice(array_merge($mcCampaignsSummary['items'], $eventsSummary['items']), 0, 4),
        ];

        // 5. Customers (Only when viewer passes Customer PII guard)
        if ($this->canViewPii) {
            $customerAssetsCount = ($this->channelAssetCounts['mailchimp'] ?? 0) + ($this->channelAssetCounts['facebook_leads'] ?? 0);
            $customersSummary = $this->fetchEntitySummary($engine, $project, 'customers', null, 4);

            $this->domainSections['customers'] = [
                'title'       => __('Customers & Leads'),
                'description' => __('Verified customer profiles and permitted lead or audience memberships in the selected scope.'),
                'coverage'    => $customersSummary['total'] > 0 ? 'active' : 'sparse',
                'asset_count' => $customerAssetsCount,
                'channels'    => array_values(array_intersect($channelsInScope, ['mailchimp', 'facebook_leads'])),
                'indicators'  => [
                    [
                        'label'  => __('Verified Customer Profiles'),
                        'count'  => $customersSummary['total'],
                        'entity' => 'customers',
                        'url'    => $this->catalogUrl('customers'),
                    ],
                ],
                'highlights' => $customersSummary['items'],
            ];
        }

        // Compose recent catalog records feed across domains
        $allHighlights = [];
        foreach ($this->domainSections as $sec) {
            foreach ($sec['highlights'] ?? [] as $item) {
                $allHighlights[$item['reference']] = $item;
            }
        }
        $this->recentRecords = array_slice(array_values($allHighlights), 0, 8);
    }

    /**
     * @return array{total: int, items: array<int, array<string, mixed>>}
     */
    private function fetchEntitySummary(
        RemoteEngineService $engine,
        Project $project,
        string $entityType,
        ?string $channelFilter = null,
        int $limit = 3
    ): array {
        $filters = [];
        if ($channelFilter !== null && $channelFilter !== '') {
            if (empty($this->authorizedScope['channels'][$channelFilter])) {
                return ['total' => 0, 'items' => []];
            }
            $filters['channel'] = $channelFilter;
        }

        $response = $engine->listCatalogEntities($project, $entityType, [
            'authorized_scope' => $this->authorizedScope,
            'search'           => '',
            'filters'          => $filters,
            'sort'             => ['field' => 'name', 'dir' => 'ASC'],
            'pagination'       => ['page' => 1, 'per_page' => $limit],
        ]);

        if (($response['status'] ?? null) !== 'success') {
            return ['total' => 0, 'items' => []];
        }

        $items = is_array($response['data'] ?? null) ? $response['data'] : [];
        $total = (int) ($response['meta']['total'] ?? count($items));

        return [
            'total' => $total,
            'items' => $items,
        ];
    }

    private function applyPresetDates(string $preset): void
    {
        $today = now()->startOfDay();
        $days = match ($preset) {
            '7d'    => 6,
            '90d'   => 89,
            default => 29,
        };

        $this->dateEnd = $today->format('Y-m-d');
        $this->dateStart = $today->copy()->subDays($days)->format('Y-m-d');
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

        $this->stateMessage = (string) ($response['message'] ?? $response['error'] ?? __('Unable to load catalog overview from the tenant node.'));
    }
}
