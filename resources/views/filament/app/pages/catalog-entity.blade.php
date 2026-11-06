<x-filament-panels::page>
    <link rel="stylesheet" href="{{ asset('css/catalog.css') }}?v={{ filemtime(public_path('css/catalog.css')) }}">
    <div class="catalog-page space-y-6" data-catalog-mode="{{ $this->isDetailMode() ? 'detail' : 'list' }}">
        {{-- Top Scope, Channel & Context Bar --}}
        <div class="catalog-filter-panel">
            <div class="catalog-filter-heading">
                <span class="catalog-section-label"><x-heroicon-o-adjustments-horizontal /> {{ __('Filters') }}</span>
                <span class="catalog-loading" wire:loading.delay role="status">{{ __('Updating…') }}</span>
            </div>
            <div class="catalog-filter-layout">
                <div class="catalog-filter-grid">
                    {{-- Asset Group Scope Selector --}}
                    <div>
                        <label for="catalog-scope-select" class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            {{ __('Asset Scope') }}
                        </label>
                        <x-ui.select-input
                            id="catalog-scope-select"
                            wire:model.live="scope"
                            class="mt-1 block w-full rounded-lg border-gray-300 bg-white text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-gray-800 dark:text-white"
                        >
                            @foreach ($scopeOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </x-ui.select-input>
                    </div>

                    {{-- Channel Selector --}}
                    @if (\App\Services\Catalog\EntityReference::isSupported($entity))
                        <div>
                            <label for="catalog-channel-select" class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                {{ __('Channel') }}
                            </label>
                            <x-ui.select-input
                                id="catalog-channel-select"
                                wire:model.live="channel"
                                :disabled="in_array($entity, ['ad-sets', 'ad-groups', 'posts', 'queries'], true)"
                                class="mt-1 block w-full rounded-lg border-gray-300 bg-white text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 disabled:opacity-60 dark:border-white/10 dark:bg-gray-800 dark:text-white"
                            >
                                @foreach ($this->getChannelOptions() as $chValue => $chLabel)
                                    <option value="{{ $chValue }}">{{ $chLabel }}</option>
                                @endforeach
                            </x-ui.select-input>
                        </div>
                    @endif

                    @if (! $this->isDetailMode() && \App\Services\Catalog\EntityReference::isSupported($entity))
                        {{-- Search Input --}}
                        <div>
                            <label for="catalog-search-input" class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                {{ __('Search') }}
                            </label>
                            <input
                                id="catalog-search-input"
                                type="search"
                                wire:model.live.debounce.350ms="search"
                                placeholder="{{ __('Search by name, URL, query, or ID...') }}"
                                class="mt-1 block w-full rounded-lg border-gray-300 bg-white text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-gray-800 dark:text-white"
                            />
                        </div>

                        {{-- Status or Search Type Filter --}}
                        @if ($entity === 'queries')
                            <div>
                                <label for="catalog-search-type-select" class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    {{ __('Search Type') }}
                                </label>
                                <x-ui.select-input
                                    id="catalog-search-type-select"
                                    wire:model.live="searchType"
                                    class="mt-1 block w-full rounded-lg border-gray-300 bg-white text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-gray-800 dark:text-white"
                                >
                                    @foreach ($this->getSearchTypeOptions() as $stVal => $stLabel)
                                        <option value="{{ $stVal }}">{{ $stLabel }}</option>
                                    @endforeach
                                </x-ui.select-input>
                            </div>
                        @else
                            <div>
                                <label for="catalog-status-select" class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    {{ __('Status') }}
                                </label>
                                <x-ui.select-input
                                    id="catalog-status-select"
                                    wire:model.live="status"
                                    class="mt-1 block w-full rounded-lg border-gray-300 bg-white text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-gray-800 dark:text-white"
                                >
                                    <option value="">{{ __('All statuses') }}</option>
                                    @if ($entity === 'accounts')
                                        <option value="ENABLED">{{ __('Enabled') }}</option>
                                        <option value="DISABLED">{{ __('Disabled') }}</option>
                                    @elseif ($entity === 'posts')
                                        <option value="PUBLISHED">{{ __('Published') }}</option>
                                        <option value="ACTIVE">{{ __('Active') }}</option>
                                        <option value="ARCHIVED">{{ __('Archived') }}</option>
                                    @elseif ($entity === 'customers')
                                        <option value="SUBSCRIBED">{{ __('Subscribed') }}</option>
                                        <option value="UNSUBSCRIBED">{{ __('Unsubscribed') }}</option>
                                        <option value="CLEANED">{{ __('Cleaned') }}</option>
                                        <option value="LEAD">{{ __('Lead') }}</option>
                                        <option value="ACTIVE">{{ __('Active') }}</option>
                                    @else
                                        <option value="ACTIVE">{{ __('Active') }}</option>
                                        <option value="PAUSED">{{ __('Paused') }}</option>
                                        <option value="SENT">{{ __('Sent') }}</option>
                                        <option value="ARCHIVED">{{ __('Archived') }}</option>
                                        <option value="DELETED">{{ __('Deleted') }}</option>
                                    @endif
                                </x-ui.select-input>
                            </div>
                        @endif
                    @endif

                    @if ($this->isDetailMode())
                        {{-- Date Range Pickers for Detail Metrics --}}
                        <div>
                            <label for="catalog-date-start" class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                {{ __('Metrics Start Date') }}
                            </label>
                            <x-ui.date-input
                                id="catalog-date-start"
                                
                                wire:model.live="dateStart"
                                class="mt-1 block w-full rounded-lg border-gray-300 bg-white text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-gray-800 dark:text-white"
                            />
                        </div>

                        <div>
                            <label for="catalog-date-end" class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                {{ __('Metrics End Date') }}
                            </label>
                            <x-ui.date-input
                                id="catalog-date-end"
                                
                                wire:model.live="dateEnd"
                                class="mt-1 block w-full rounded-lg border-gray-300 bg-white text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-gray-800 dark:text-white"
                            />
                        </div>
                    @endif
                </div>

                @if (! $this->isDetailMode() && ($search !== '' || $status !== '' || $objective !== '' || $buyingType !== '' || $optimizationGoal !== '' || $billingEvent !== '' || $searchType !== '' || $parent !== ''))
                    <div>
                        <button
                            type="button"
                            wire:click="resetFilters"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-medium text-gray-700 shadow-sm hover:bg-gray-50 dark:border-white/10 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                        >
                            {{ __('Reset Filters') }}
                        </button>
                    </div>
                @endif
            </div>

            {{-- Detail Mode Query Search Type Selector --}}
            @if ($this->isDetailMode() && $entity === 'queries')
                <div class="catalog-advanced-filters mt-4 grid grid-cols-1 gap-4 border-t border-gray-100 pt-4 sm:grid-cols-3 dark:border-white/5">
                    <div>
                        <label for="catalog-detail-search-type" class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            {{ __('Search Type Dimension') }}
                        </label>
                        <x-ui.select-input
                            id="catalog-detail-search-type"
                            wire:model.live="searchType"
                            class="mt-1 block w-full rounded-lg border-gray-300 bg-white text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-gray-800 dark:text-white"
                        >
                            @foreach ($this->getSearchTypeOptions() as $stVal => $stLabel)
                                <option value="{{ $stVal }}">{{ $stLabel }}</option>
                            @endforeach
                        </x-ui.select-input>
                    </div>
                </div>
            @endif

            {{-- Entity-Specific Secondary Filters in List Mode --}}
            @if (! $this->isDetailMode() && in_array($entity, ['campaigns', 'ad-sets', 'ad-groups'], true))
                <div class="catalog-advanced-filters mt-4 grid grid-cols-1 gap-4 border-t border-gray-100 pt-4 sm:grid-cols-3 dark:border-white/5">
                    @if ($entity === 'campaigns')
                        <div>
                            <label for="catalog-objective-select" class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                {{ __('Objective') }}
                            </label>
                            <x-ui.select-input
                                id="catalog-objective-select"
                                wire:model.live="objective"
                                class="mt-1 block w-full rounded-lg border-gray-300 bg-white text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-gray-800 dark:text-white"
                            >
                                <option value="">{{ __('All objectives') }}</option>
                                <option value="OUTCOME_SALES">OUTCOME_SALES</option>
                                <option value="OUTCOME_LEADS">OUTCOME_LEADS</option>
                                <option value="OUTCOME_TRAFFIC">OUTCOME_TRAFFIC</option>
                                <option value="OUTCOME_ENGAGEMENT">OUTCOME_ENGAGEMENT</option>
                                <option value="OUTCOME_AWARENESS">OUTCOME_AWARENESS</option>
                                <option value="SALES">SALES</option>
                                <option value="LEAD_GENERATION">LEAD_GENERATION</option>
                                <option value="CONVERSIONS">CONVERSIONS</option>
                                <option value="TRAFFIC">TRAFFIC</option>
                                <option value="EMAIL">EMAIL</option>
                            </x-ui.select-input>
                        </div>

                        <div>
                            <label for="catalog-buying-type-select" class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                {{ __('Buying Type') }}
                            </label>
                            <x-ui.select-input
                                id="catalog-buying-type-select"
                                wire:model.live="buyingType"
                                class="mt-1 block w-full rounded-lg border-gray-300 bg-white text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-gray-800 dark:text-white"
                            >
                                <option value="">{{ __('All buying types') }}</option>
                                <option value="AUCTION">AUCTION</option>
                                <option value="RESERVED">RESERVED</option>
                                <option value="REACH_AND_FREQUENCY">REACH_AND_FREQUENCY</option>
                            </x-ui.select-input>
                        </div>
                    @endif

                    @if (in_array($entity, ['ad-sets', 'ad-groups'], true))
                        <div>
                            <label for="catalog-opt-goal-select" class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                {{ __('Optimization Goal') }}
                            </label>
                            <x-ui.select-input
                                id="catalog-opt-goal-select"
                                wire:model.live="optimizationGoal"
                                class="mt-1 block w-full rounded-lg border-gray-300 bg-white text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-gray-800 dark:text-white"
                            >
                                <option value="">{{ __('All optimization goals') }}</option>
                                <option value="OFFSITE_CONVERSIONS">OFFSITE_CONVERSIONS</option>
                                <option value="CLICKS">CLICKS</option>
                                <option value="LINK_CLICKS">LINK_CLICKS</option>
                                <option value="IMPRESSIONS">IMPRESSIONS</option>
                                <option value="REACH">REACH</option>
                                <option value="LEAD_GENERATION">LEAD_GENERATION</option>
                                <option value="LANDING_PAGE_VIEWS">LANDING_PAGE_VIEWS</option>
                                <option value="VALUE">VALUE</option>
                            </x-ui.select-input>
                        </div>

                        <div>
                            <label for="catalog-billing-event-select" class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                {{ __('Billing Event') }}
                            </label>
                            <x-ui.select-input
                                id="catalog-billing-event-select"
                                wire:model.live="billingEvent"
                                class="mt-1 block w-full rounded-lg border-gray-300 bg-white text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-gray-800 dark:text-white"
                            >
                                <option value="">{{ __('All billing events') }}</option>
                                <option value="IMPRESSIONS">IMPRESSIONS</option>
                                <option value="CLICKS">CLICKS</option>
                                <option value="LINK_CLICKS">LINK_CLICKS</option>
                            </x-ui.select-input>
                        </div>
                    @endif

                    @if ($parent !== '')
                        <div class="flex items-end">
                            <div class="inline-flex items-center gap-2 rounded-lg bg-primary-50 px-3 py-2 text-xs font-medium text-primary-700 dark:bg-primary-950/50 dark:text-primary-300">
                                <span>{{ __('Filtered by parent:') }} <code class="font-mono">{{ $parent }}</code></span>
                                <button type="button" wire:click="$set('parent', '')" class="hover:underline">
                                    {{ __('Clear') }}
                                </button>
                            </div>
                        </div>
                    @endif
                </div>
            @elseif (! $this->isDetailMode() && $parent !== '')
                <div class="mt-3 flex items-center gap-2 border-t border-gray-100 pt-3 dark:border-white/5">
                    <div class="inline-flex items-center gap-2 rounded-lg bg-primary-50 px-3 py-1.5 text-xs font-medium text-primary-700 dark:bg-primary-950/50 dark:text-primary-300">
                        <span>{{ __('Filtered by parent:') }} <code class="font-mono">{{ $parent }}</code></span>
                        <button type="button" wire:click="$set('parent', '')" class="hover:underline">
                            {{ __('Clear') }}
                        </button>
                    </div>
                </div>
            @endif
        </div>

        {{-- State Banners (Upgrading, Capacity Exhausted, Unavailable, Deferred, PII Restricted, Empty Scope, Empty Results) --}}
        @if ($stateCode !== null)
            @php
                $bannerStyles = match ($stateCode) {
                    'tenant_upgrading', 'capacity_exhausted' => 'border-amber-300 bg-amber-50 text-amber-900 dark:border-amber-500/30 dark:bg-amber-950/40 dark:text-amber-200',
                    'catalog_unavailable', 'remote_error', 'unauthorized_scope', 'pii_restricted' => 'border-red-300 bg-red-50 text-red-900 dark:border-red-500/30 dark:bg-red-950/40 dark:text-red-200',
                    'phase_deferred' => 'border-blue-300 bg-blue-50 text-blue-900 dark:border-blue-500/30 dark:bg-blue-950/40 dark:text-blue-200',
                    default => 'border-gray-200 bg-gray-50 text-gray-700 dark:border-white/10 dark:bg-gray-800/60 dark:text-gray-300',
                };
            @endphp
            <div data-catalog-state="{{ $stateCode }}" class="rounded-xl border p-6 shadow-sm {{ $bannerStyles }}">
                <div class="flex flex-col gap-2">
                    <h3 class="text-base font-semibold">
                        @switch($stateCode)
                            @case('tenant_upgrading')
                                {{ __('Tenant Upgrade in Progress') }}
                                @break
                            @case('capacity_exhausted')
                                {{ __('Tenant Temporarily Busy') }}
                                @break
                            @case('catalog_unavailable')
                                {{ __('Catalog Endpoints Unavailable on Tenant') }}
                                @break
                            @case('pii_restricted')
                                {{ __('Customer PII Access Restricted') }}
                                @break
                            @case('phase_deferred')
                                {{ __('Scheduled for Upcoming Rollout Phase') }}
                                @break
                            @case('unauthorized_scope')
                                {{ __('Unauthorized Asset Group Scope') }}
                                @break
                            @case('empty_scope')
                                {{ __('No Permitted Assets in Scope') }}
                                @break
                            @case('empty_results')
                                {{ __('No Matching Records') }}
                                @break
                            @default
                                {{ __('Catalog Notice') }}
                        @endswitch
                    </h3>
                    <p class="text-sm opacity-90">{{ $stateMessage }}</p>
                </div>
            </div>
        @endif

        {{-- LIST MODE --}}
        @if (! $this->isDetailMode() && $stateCode === null)
            <div class="catalog-table-panel">
                <div class="catalog-table-heading">
                    <div><h2>{{ __(self::ENTITIES[$entity]) }}</h2><span class="catalog-record-count">{{ number_format((int) ($listMeta['total'] ?? count($items))) }} {{ __('records') }}</span></div>
                    <span class="catalog-table-hint">{{ __('Select a name to view details') }}</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="catalog-table min-w-full divide-y divide-gray-200 text-left text-sm dark:divide-white/10">
                        <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:bg-gray-800/60 dark:text-gray-400">
                            <tr>
                                <th class="px-4 py-3" aria-sort="{{ $sort === 'name' ? ($dir === 'ASC' ? 'ascending' : 'descending') : 'none' }}">
                                    <button type="button" wire:click="sortBy('name')" class="inline-flex items-center gap-1 hover:text-gray-900 dark:hover:text-white">
                                        <span>{{ __('Name') }}</span>
                                        @if ($sort === 'name')
                                            <span>{{ $dir === 'ASC' ? '↑' : '↓' }}</span>
                                        @endif
                                    </button>
                                </th>
                                <th class="px-4 py-3">{{ __('Channel') }}</th>
                                <th class="px-4 py-3" aria-sort="{{ $sort === 'status' ? ($dir === 'ASC' ? 'ascending' : 'descending') : 'none' }}">
                                    <button type="button" wire:click="sortBy('status')" class="inline-flex items-center gap-1 hover:text-gray-900 dark:hover:text-white">
                                        <span>{{ __('Status') }}</span>
                                        @if ($sort === 'status')
                                            <span>{{ $dir === 'ASC' ? '↑' : '↓' }}</span>
                                        @endif
                                    </button>
                                </th>

                                @if ($entity === 'accounts')
                                    <th class="px-4 py-3">{{ __('Type') }}</th>
                                    <th class="px-4 py-3">{{ __('Currency') }}</th>
                                    <th class="px-4 py-3">{{ __('Timezone') }}</th>
                                @elseif ($entity === 'campaigns')
                                    <th class="px-4 py-3">{{ __('Account / Audience') }}</th>
                                    <th class="px-4 py-3" aria-sort="{{ $sort === 'budget' ? ($dir === 'ASC' ? 'ascending' : 'descending') : 'none' }}">
                                        <button type="button" wire:click="sortBy('budget')" class="inline-flex items-center gap-1 hover:text-gray-900 dark:hover:text-white">
                                            <span>{{ __('Budget / Info') }}</span>
                                            @if ($sort === 'budget')
                                                <span>{{ $dir === 'ASC' ? '↑' : '↓' }}</span>
                                            @endif
                                        </button>
                                    </th>
                                    <th class="px-4 py-3">{{ __('Objective / Subject') }}</th>
                                    <th class="px-4 py-3">{{ __('Buying Type') }}</th>
                                    <th class="px-4 py-3">{{ __('Schedule / Sent') }}</th>
                                @elseif (in_array($entity, ['ad-sets', 'ad-groups'], true))
                                    <th class="px-4 py-3">{{ __('Campaign') }}</th>
                                    <th class="px-4 py-3">{{ __('Ad Account') }}</th>
                                    <th class="px-4 py-3">{{ __('Optimization Goal') }}</th>
                                    <th class="px-4 py-3">{{ __('Billing Event') }}</th>
                                    <th class="px-4 py-3">{{ __('Schedule') }}</th>
                                @elseif ($entity === 'ads')
                                    <th class="px-4 py-3">{{ __('Parent Ad Set / Group') }}</th>
                                    <th class="px-4 py-3">{{ __('Campaign') }}</th>
                                    <th class="px-4 py-3">{{ __('Ad Account') }}</th>
                                    <th class="px-4 py-3">{{ __('Creative') }}</th>
                                @elseif ($entity === 'pages')
                                    <th class="px-4 py-3">{{ __('URL / Path') }}</th>
                                    <th class="px-4 py-3">{{ __('Hostname / Category') }}</th>
                                    <th class="px-4 py-3">{{ __('Owning Account') }}</th>
                                @elseif ($entity === 'posts')
                                    <th class="px-4 py-3">{{ __('Account / Page') }}</th>
                                    <th class="px-4 py-3">{{ __('Media Type') }}</th>
                                    <th class="px-4 py-3" aria-sort="{{ $sort === 'published_at' ? ($dir === 'ASC' ? 'ascending' : 'descending') : 'none' }}">
                                        <button type="button" wire:click="sortBy('published_at')" class="inline-flex items-center gap-1 hover:text-gray-900 dark:hover:text-white">
                                            <span>{{ __('Published At') }}</span>
                                            @if ($sort === 'published_at')
                                                <span>{{ $dir === 'ASC' ? '↑' : '↓' }}</span>
                                            @endif
                                        </button>
                                    </th>
                                @elseif ($entity === 'queries')
                                    <th class="px-4 py-3">{{ __('Property') }}</th>
                                    <th class="px-4 py-3">{{ __('Search Types') }}</th>
                                    <th class="px-4 py-3 text-right">{{ __('Observations') }}</th>
                                @elseif ($entity === 'events')
                                    <th class="px-4 py-3">{{ __('Event Name') }}</th>
                                    <th class="px-4 py-3">{{ __('Source Account') }}</th>
                                    <th class="px-4 py-3 text-right">{{ __('Recorded Occurrences') }}</th>
                                @elseif ($entity === 'customers')
                                    <th class="px-4 py-3" aria-sort="{{ $sort === 'email' ? ($dir === 'ASC' ? 'ascending' : 'descending') : 'none' }}">
                                        <button type="button" wire:click="sortBy('email')" class="inline-flex items-center gap-1 hover:text-gray-900 dark:hover:text-white">
                                            <span>{{ __('Email') }}</span>
                                            @if ($sort === 'email')
                                                <span>{{ $dir === 'ASC' ? '↑' : '↓' }}</span>
                                            @endif
                                        </button>
                                    </th>
                                    <th class="px-4 py-3">{{ __('Phone / Country') }}</th>
                                    <th class="px-4 py-3">{{ __('Source Memberships') }}</th>
                                @endif

                                <th class="px-4 py-3 text-right" aria-sort="{{ $sort === 'updated_at' ? ($dir === 'ASC' ? 'ascending' : 'descending') : 'none' }}">
                                    <button type="button" wire:click="sortBy('updated_at')">{{ __('Updated') }} @if ($sort === 'updated_at')<span aria-hidden="true">{{ $dir === 'ASC' ? '↑' : '↓' }}</span>@endif</button>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                            @foreach ($items as $item)
                                @php
                                    $attrs = $item['attributes'] ?? [];
                                    $rels = $item['relationships'] ?? [];
                                    $statusVal = strtoupper((string) ($item['status'] ?? 'UNKNOWN'));
                                    $statusBadge = match ($statusVal) {
                                        'ACTIVE', 'ENABLED', 'PUBLISHED', 'SENT', 'SUBSCRIBED' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-500/10 dark:text-emerald-400',
                                        'PAUSED', 'DISABLED', 'UNSUBSCRIBED', 'CLEANED' => 'bg-amber-50 text-amber-700 ring-amber-600/20 dark:bg-amber-500/10 dark:text-amber-400',
                                        default => 'bg-gray-100 text-gray-700 ring-gray-500/20 dark:bg-gray-800 dark:text-gray-300',
                                    };
                                @endphp
                                <tr class="catalog-record-row hover:bg-gray-50/80 dark:hover:bg-white/5" wire:key="catalog-row-{{ $item['reference'] }}">
                                    <td class="px-4 py-3">
                                        <a
                                            href="{{ $this->catalogUrl($entity, $item['reference']) }}"
                                            class="catalog-record-link font-semibold text-primary-600 hover:underline dark:text-primary-400"
                                        >
                                            {{ $item['name'] }}
                                        </a>
                                        <div class="mt-0.5 text-xs font-mono text-gray-500 dark:text-gray-400">
                                            {{ $item['platform_id'] }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="catalog-channel">
                                            @php
                                                $channelIcon = \App\Support\BrandIcon::forChannel($item['channel']);
                                            @endphp
                                            @if ($channelIcon){{ $channelIcon }}@endif
                                            {{ $item['channel_label'] ?? $item['channel'] }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="catalog-status" data-status="{{ $statusVal }}">
                                            {{ $statusVal }}
                                        </span>
                                    </td>

                                    @if ($entity === 'accounts')
                                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $attrs['type'] ?? 'ACCOUNT' }}</td>
                                        <td class="px-4 py-3 font-mono text-xs text-gray-700 dark:text-gray-300">{{ $attrs['currency'] ?? '—' }}</td>
                                        <td class="px-4 py-3 text-xs text-gray-600 dark:text-gray-400">{{ $attrs['timezone'] ?? '—' }}</td>
                                    @elseif ($entity === 'campaigns')
                                        <td class="px-4 py-3">
                                            @if (! empty($rels['account']))
                                                <a href="{{ $this->catalogUrl('accounts', $rels['account']['reference']) }}" class="text-xs font-medium text-gray-800 hover:text-primary-600 hover:underline dark:text-gray-200">
                                                    {{ $rels['account']['name'] }}
                                                </a>
                                            @else
                                                <span class="text-gray-400">—</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 font-mono text-xs text-gray-800 dark:text-gray-200">
                                            @if (isset($attrs['budget']) && (float) $attrs['budget'] > 0)
                                                {{ ($attrs['currency'] ?? '') !== '' ? $attrs['currency'] . ' ' : '' }}{{ number_format((float) $attrs['budget'], 2) }}
                                            @elseif (! empty($attrs['emails_sent']))
                                                {{ number_format((int) $attrs['emails_sent']) }} {{ __('sent') }}
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-xs text-gray-700 dark:text-gray-300">{{ $attrs['subject_line'] ?? $attrs['objective'] ?? '—' }}</td>
                                        <td class="px-4 py-3 text-xs text-gray-700 dark:text-gray-300">{{ $attrs['buying_type'] ?? '—' }}</td>
                                        <td class="px-4 py-3 text-xs text-gray-600 dark:text-gray-400">
                                            @if (! empty($attrs['send_time']))
                                                {{ $attrs['send_time'] }}
                                            @else
                                                {{ $attrs['start_date'] ?? '—' }} → {{ $attrs['end_date'] ?? __('Ongoing') }}
                                            @endif
                                        </td>
                                    @elseif (in_array($entity, ['ad-sets', 'ad-groups'], true))
                                        <td class="px-4 py-3">
                                            @if (! empty($rels['campaign']))
                                                <a href="{{ $this->catalogUrl('campaigns', $rels['campaign']['reference']) }}" class="text-xs font-medium text-gray-800 hover:text-primary-600 hover:underline dark:text-gray-200">
                                                    {{ $rels['campaign']['name'] }}
                                                </a>
                                            @else
                                                <span class="text-gray-400">—</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3">
                                            @if (! empty($rels['account']))
                                                <a href="{{ $this->catalogUrl('accounts', $rels['account']['reference']) }}" class="text-xs text-gray-600 hover:text-primary-600 hover:underline dark:text-gray-400">
                                                    {{ $rels['account']['name'] }}
                                                </a>
                                            @else
                                                <span class="text-gray-400">—</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-xs text-gray-700 dark:text-gray-300">{{ $attrs['optimization_goal'] ?? '—' }}</td>
                                        <td class="px-4 py-3 text-xs text-gray-700 dark:text-gray-300">{{ $attrs['billing_event'] ?? '—' }}</td>
                                        <td class="px-4 py-3 text-xs text-gray-600 dark:text-gray-400">
                                            {{ $attrs['start_date'] ?? '—' }} → {{ $attrs['end_date'] ?? __('Ongoing') }}
                                        </td>
                                    @elseif ($entity === 'ads')
                                        <td class="px-4 py-3">
                                            @if (! empty($rels['ad_group']))
                                                <a href="{{ $this->catalogUrl($rels['ad_group']['entity_type'], $rels['ad_group']['reference']) }}" class="text-xs font-medium text-gray-800 hover:text-primary-600 hover:underline dark:text-gray-200">
                                                    {{ $rels['ad_group']['name'] }}
                                                </a>
                                            @else
                                                <span class="text-gray-400">—</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3">
                                            @if (! empty($rels['campaign']))
                                                <a href="{{ $this->catalogUrl('campaigns', $rels['campaign']['reference']) }}" class="text-xs text-gray-700 hover:text-primary-600 hover:underline dark:text-gray-300">
                                                    {{ $rels['campaign']['name'] }}
                                                </a>
                                            @else
                                                <span class="text-gray-400">—</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3">
                                            @if (! empty($rels['account']))
                                                <a href="{{ $this->catalogUrl('accounts', $rels['account']['reference']) }}" class="text-xs text-gray-600 hover:text-primary-600 hover:underline dark:text-gray-400">
                                                    {{ $rels['account']['name'] }}
                                                </a>
                                            @else
                                                <span class="text-gray-400">—</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-xs text-gray-700 dark:text-gray-300">
                                            {{ $attrs['creative_name'] ?? $attrs['headline'] ?? '—' }}
                                        </td>
                                    @elseif ($entity === 'pages')
                                        <td class="px-4 py-3 font-mono text-xs text-gray-700 dark:text-gray-300 max-w-xs truncate" title="{{ $attrs['url'] ?? '' }}">
                                            {{ $attrs['url'] ?? '—' }}
                                        </td>
                                        <td class="px-4 py-3 text-xs text-gray-700 dark:text-gray-300">
                                            {{ $attrs['hostname'] ?? $attrs['category'] ?? '—' }}
                                        </td>
                                        <td class="px-4 py-3">
                                            @if (! empty($rels['account']))
                                                <a href="{{ $this->catalogUrl('accounts', $rels['account']['reference']) }}" class="text-xs text-gray-700 hover:text-primary-600 hover:underline dark:text-gray-300">
                                                    {{ $rels['account']['name'] }}
                                                </a>
                                            @else
                                                <span class="text-gray-400">—</span>
                                            @endif
                                        </td>
                                    @elseif ($entity === 'posts')
                                        <td class="px-4 py-3">
                                            @if (! empty($rels['account']))
                                                <a href="{{ $this->catalogUrl('accounts', $rels['account']['reference']) }}" class="text-xs text-gray-700 hover:text-primary-600 hover:underline dark:text-gray-300">
                                                    {{ $rels['account']['name'] }}
                                                </a>
                                            @elseif (! empty($rels['page']))
                                                <a href="{{ $this->catalogUrl('pages', $rels['page']['reference']) }}" class="text-xs text-gray-700 hover:text-primary-600 hover:underline dark:text-gray-300">
                                                    {{ $rels['page']['name'] }}
                                                </a>
                                            @else
                                                <span class="text-gray-400">—</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-xs text-gray-700 dark:text-gray-300">
                                            {{ $attrs['media_type'] ?? 'POST' }}
                                        </td>
                                        <td class="px-4 py-3 font-mono text-xs text-gray-600 dark:text-gray-400">
                                            {{ $attrs['published_at'] ?? '—' }}
                                        </td>
                                    @elseif ($entity === 'queries')
                                        <td class="px-4 py-3">
                                            @if (! empty($rels['account']))
                                                <a href="{{ $this->catalogUrl('accounts', $rels['account']['reference']) }}" class="text-xs text-gray-700 hover:text-primary-600 hover:underline dark:text-gray-300">
                                                    {{ $rels['account']['name'] }}
                                                </a>
                                            @else
                                                <span class="text-gray-400">—</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-xs text-gray-700 dark:text-gray-300">
                                            @php
                                                $stList = $attrs['search_types'] ?? (! empty($attrs['search_type']) ? [$attrs['search_type']] : ['web']);
                                            @endphp
                                            <div class="flex flex-wrap gap-1">
                                                @foreach ((array) $stList as $stBadge)
                                                    <span class="rounded bg-gray-100 px-1.5 py-0.5 text-[11px] font-mono text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                                        {{ $stBadge }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-right font-mono text-xs text-gray-700 dark:text-gray-300">
                                            {{ number_format((int) ($attrs['metric_config_count'] ?? 0)) }}
                                        </td>
                                    @elseif ($entity === 'events')
                                        <td class="px-4 py-3 font-mono text-xs text-gray-800 dark:text-gray-200">
                                            {{ $attrs['event_name'] ?? $item['name'] }}
                                        </td>
                                        <td class="px-4 py-3">
                                            @if (! empty($rels['account']))
                                                <a href="{{ $this->catalogUrl('accounts', $rels['account']['reference']) }}" class="text-xs text-gray-700 hover:text-primary-600 hover:underline dark:text-gray-300">
                                                    {{ $rels['account']['name'] }}
                                                </a>
                                            @else
                                                <span class="text-gray-400">—</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-right font-mono text-xs text-gray-700 dark:text-gray-300">
                                            {{ number_format((int) ($attrs['occurrence_count'] ?? $attrs['metric_config_count'] ?? 0)) }}
                                        </td>
                                    @elseif ($entity === 'customers')
                                        <td class="px-4 py-3 font-mono text-xs text-gray-800 dark:text-gray-200">
                                            {{ $attrs['email'] ?? '—' }}
                                        </td>
                                        <td class="px-4 py-3 text-xs text-gray-700 dark:text-gray-300">
                                            {{ $attrs['phone'] ?? $attrs['country_code'] ?? '—' }}
                                        </td>
                                        <td class="px-4 py-3 text-xs text-gray-600 dark:text-gray-400">
                                            {{ number_format((int) ($attrs['source_record_count'] ?? 1)) }} {{ __('records') }}
                                            @if (! empty($attrs['vip']))
                                                <span class="ml-1 rounded bg-amber-100 px-1.5 py-0.5 text-[10px] font-bold text-amber-800 dark:bg-amber-500/20 dark:text-amber-300">VIP</span>
                                            @endif
                                        </td>
                                    @endif

                                    <td class="px-4 py-3 text-right text-xs text-gray-500 dark:text-gray-400">
                                        {{ $item['provenance']['freshness_timestamp'] ?? '—' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Pagination Footer --}}
                @php
                    $total = (int) ($listMeta['total'] ?? count($items));
                    $currentPage = (int) ($listMeta['page'] ?? $page);
                    $lastPage = (int) ($listMeta['last_page'] ?? 1);
                @endphp
                <div class="catalog-pagination">
                    <label class="catalog-page-size" for="catalog-per-page">
                        <span>{{ __('Rows per page') }}</span>
                        <x-ui.select-input id="catalog-per-page" wire:model.live="perPage" aria-label="{{ __('Rows per page') }}">
                            @foreach ([10, 25, 50, 100] as $size)<option value="{{ $size }}">{{ $size }}</option>@endforeach
                        </x-ui.select-input>
                    </label>
                    <span class="catalog-pagination-summary">{{ __(':from–:to of :total records', [
                        'from' => $total > 0 ? (($currentPage - 1) * $perPage) + 1 : 0,
                        'to' => min($total, (($currentPage - 1) * $perPage) + count($items)), 'total' => number_format($total),
                    ]) }}</span>
                    <nav class="catalog-pagination-actions" aria-label="{{ __('Catalog pagination') }}">
                        <button type="button" wire:click="goToPage({{ $currentPage - 1 }})" wire:loading.attr="disabled" @disabled($currentPage <= 1) aria-label="{{ __('Previous page') }}"><x-heroicon-m-chevron-left /></button>
                        @foreach ($this->paginationPages() as $pageNumber)
                            @if ($pageNumber === null)
                                <span class="catalog-pagination-ellipsis" aria-hidden="true">…</span>
                            @else
                                <button type="button" wire:click="goToPage({{ $pageNumber }})" wire:loading.attr="disabled"
                                        @if ($pageNumber === $currentPage) aria-current="page" @endif
                                        aria-label="{{ __('Page :page', ['page' => $pageNumber]) }}">{{ $pageNumber }}</button>
                            @endif
                        @endforeach
                        <button type="button" wire:click="goToPage({{ $currentPage + 1 }})" wire:loading.attr="disabled" @disabled($currentPage >= $lastPage) aria-label="{{ __('Next page') }}"><x-heroicon-m-chevron-right /></button>
                    </nav>
                </div>
            </div>
        @endif

        {{-- DETAIL MODE --}}
        @if ($this->isDetailMode() && $profileData !== null)
            @php
                $rec = $profileData['record'] ?? [];
                $recChannel = (string) ($rec['channel'] ?? '');
                $attrs = $rec['attributes'] ?? [];
                $prov = $rec['provenance'] ?? [];
                $parents = $profileData['relationships']['parents'] ?? [];
                $children = $profileData['relationships']['children'] ?? [];
                $sourceRecords = $profileData['source_records'] ?? [];
                $metrics = $profileData['metrics'] ?? [];
                $summary = $metrics['summary'] ?? [];
                $coverage = $metrics['coverage'] ?? [];
                $series = $metrics['series'] ?? [];
                $currency = $metrics['currency'] ?? $attrs['currency'] ?? null;
                $currencyPrefix = $currency ? ($currency . ' ') : '';

                // Determine metric presentation family
                $metricFamily = match (true) {
                    $entity === 'queries' || $recChannel === 'google_search_console' => 'search',
                    $entity === 'posts' || $recChannel === 'facebook_organic' => 'organic',
                    $recChannel === 'mailchimp' => 'email',
                    $entity === 'events' || $recChannel === 'google_analytics' => 'analytics',
                    default => 'ads',
                };
            @endphp

            {{-- Back Navigation & Breadcrumb Hierarchy --}}
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex flex-wrap items-center gap-2 text-sm">
                    <a
                        href="{{ $this->catalogUrl($entity) }}"
                        class="inline-flex items-center gap-1 font-medium text-gray-600 hover:text-primary-600 dark:text-gray-400 dark:hover:text-primary-400"
                    >
                        ← {{ __('Back to :entity', ['entity' => __(self::ENTITIES[$entity])]) }}
                    </a>

                    @if (! empty($parents['account']))
                        <span class="text-gray-400">/</span>
                        <a href="{{ $this->catalogUrl('accounts', $parents['account']['reference']) }}" class="text-gray-700 hover:text-primary-600 hover:underline dark:text-gray-300">
                            {{ $parents['account']['name'] }}
                        </a>
                    @endif

                    @if (! empty($parents['page']))
                        <span class="text-gray-400">/</span>
                        <a href="{{ $this->catalogUrl('pages', $parents['page']['reference']) }}" class="text-gray-700 hover:text-primary-600 hover:underline dark:text-gray-300">
                            {{ $parents['page']['name'] }}
                        </a>
                    @endif

                    @if (! empty($parents['campaign']))
                        <span class="text-gray-400">/</span>
                        <a href="{{ $this->catalogUrl('campaigns', $parents['campaign']['reference']) }}" class="text-gray-700 hover:text-primary-600 hover:underline dark:text-gray-300">
                            {{ $parents['campaign']['name'] }}
                        </a>
                    @endif

                    @if (! empty($parents['ad_group']))
                        <span class="text-gray-400">/</span>
                        <a href="{{ $this->catalogUrl($parents['ad_group']['entity_type'], $parents['ad_group']['reference']) }}" class="text-gray-700 hover:text-primary-600 hover:underline dark:text-gray-300">
                            {{ $parents['ad_group']['name'] }}
                        </a>
                    @endif
                </div>

                <div class="text-xs text-gray-500 dark:text-gray-400">
                    {{ __('Last synced:') }} <span class="font-mono">{{ $prov['freshness_timestamp'] ?? '—' }}</span>
                </div>
            </div>

            {{-- Section 1: Entity Profile & Normalized Attributes --}}
            <div class="catalog-profile-panel catalog-panel rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-gray-900">
                <div class="catalog-profile-heading flex flex-col justify-between gap-4 border-b border-gray-100 pb-4 sm:flex-row sm:items-center dark:border-white/10">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2.5">
                            <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ $rec['name'] }}</h2>
                            <span class="catalog-channel">
                                {{ $rec['channel_label'] ?? $rec['channel'] }}
                            </span>
                            <span class="catalog-status" data-status="{{ strtoupper((string) ($rec['status'] ?? 'UNKNOWN')) }}">
                                {{ $rec['status'] ?? 'UNKNOWN' }}
                            </span>
                        </div>
                        <p class="mt-1 break-all font-mono text-xs text-gray-500 dark:text-gray-400">
                            {{ __('Platform ID:') }} {{ $rec['platform_id'] }} · {{ __('Reference:') }} {{ $rec['reference'] }}
                        </p>
                    </div>
                </div>

                <div class="catalog-attributes">
                    @foreach ($attrs as $attrKey => $attrVal)
                        @if ($attrVal !== null && $attrVal !== '' && $attrVal !== [])
                            <div class="catalog-attribute">
                                <div class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    {{ str_replace('_', ' ', $attrKey) }}
                                </div>
                                <div class="mt-1 text-sm font-semibold text-gray-900 dark:text-white break-words">
                                    @if (is_bool($attrVal))
                                        {{ $attrVal ? __('Yes') : __('No') }}
                                    @elseif (is_array($attrVal))
                                        {{ implode(', ', array_map('strval', $attrVal)) }}
                                    @elseif ($attrKey === 'budget' && is_numeric($attrVal))
                                        {{ $currencyPrefix }}{{ number_format((float) $attrVal, 2) }}
                                    @else
                                        {{ (string) $attrVal }}
                                    @endif
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>

                {{-- Source Records Provenance --}}
                @if ($sourceRecords !== [])
                    <div class="mt-6 border-t border-gray-100 pt-4 dark:border-white/10">
                        <h4 class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            {{ __('Source Provenance & Normalization') }}
                        </h4>
                        <div class="mt-2 flex flex-wrap gap-2">
                            @foreach ($sourceRecords as $sr)
                                <div class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-gray-50 px-3 py-1.5 text-xs dark:border-white/10 dark:bg-gray-800">
                                    <span class="font-semibold uppercase text-primary-600 dark:text-primary-400">{{ $sr['layer'] }}</span>
                                    <span class="font-mono text-gray-600 dark:text-gray-300">{{ $sr['table'] }}#{{ $sr['id'] }}</span>
                                    @if (! empty($sr['platform_id']))
                                        <span class="text-gray-400">({{ $sr['platform_id'] }})</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            {{-- Section 2: Related Hierarchy & Child Entities --}}
            @if ($parents !== [] || $children !== [])
                <div class="catalog-panel rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-gray-900">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                        {{ __('Hierarchy & Related Entities') }}
                    </h3>

                    @if ($parents !== [])
                        <div class="catalog-parent-grid mt-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
                            @foreach ($parents as $role => $parentItem)
                                <div class="rounded-lg border border-gray-200 p-3.5 dark:border-white/10">
                                    <div class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                        {{ ucfirst(str_replace('_', ' ', $role)) }}
                                    </div>
                                    <a
                                        href="{{ $this->catalogUrl($parentItem['entity_type'], $parentItem['reference']) }}"
                                        class="mt-1 block font-semibold text-primary-600 hover:underline dark:text-primary-400"
                                    >
                                        {{ $parentItem['name'] }}
                                    </a>
                                    <div class="mt-0.5 font-mono text-xs text-gray-500">
                                        {{ $parentItem['platform_id'] }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if ($children !== [])
                        <div class="catalog-related-groups mt-6 space-y-4">
                            @foreach ($children as $childKey => $childGroup)
                                <div class="rounded-lg border border-gray-200 p-4 dark:border-white/10">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <h4 class="text-sm font-semibold text-gray-900 dark:text-white">
                                                {{ __($childGroup['label'] ?? ucfirst($childKey)) }}
                                            </h4>
                                            <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                                {{ $childGroup['total_count'] ?? 0 }}
                                            </span>
                                        </div>
                                        @if (($childGroup['total_count'] ?? 0) > 0)
                                            <a
                                                href="{{ $this->catalogUrl($childGroup['entity_type'], null, ['parent' => $rec['reference']]) }}"
                                                class="text-xs font-medium text-primary-600 hover:underline dark:text-primary-400"
                                            >
                                                {{ __('View all in Catalog →') }}
                                            </a>
                                        @endif
                                    </div>

                                    @if (! empty($childGroup['items']))
                                        <div class="catalog-related-items mt-3 grid grid-cols-1 gap-2 sm:grid-cols-2">
                                            @foreach ($childGroup['items'] as $childItem)
                                                <a
                                                    href="{{ $this->catalogUrl($childItem['entity_type'], $childItem['reference']) }}"
                                                    class="catalog-related-record flex items-center justify-between rounded-md border border-gray-100 bg-gray-50/70 px-3 py-2 text-xs hover:border-primary-300 dark:border-white/5 dark:bg-gray-800/50"
                                                >
                                                    <div>
                                                        <span class="font-medium text-gray-900 dark:text-white">{{ $childItem['name'] }}</span>
                                                        <span class="ml-1 font-mono text-gray-500">({{ $childItem['platform_id'] }})</span>
                                                    </div>
                                                    <span class="font-semibold text-gray-600 dark:text-gray-400">{{ $childItem['status'] }}</span>
                                                </a>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif

            {{-- Section 3: Performance Metrics --}}
            @if (in_array($entity, \App\Services\Catalog\EntityReference::COMMERCE_ENTITY_TYPES, true))
                <div class="catalog-panel rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-gray-900">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">{{ __('Performance Metrics') }}</h3>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        {{ __('Store order counts and revenue are available in Mailchimp Data Preview.') }}
                    </p>
                    <a href="{{ \App\Filament\App\Pages\MailchimpDashboard::getUrl(['tab' => 'stores'], panel: 'app', tenant: \Filament\Facades\Filament::getTenant()) }}"
                       class="mt-3 inline-flex text-sm font-medium text-primary-600 hover:underline dark:text-primary-400">
                        {{ __('Open Connected Stores') }}
                    </a>
                </div>
            @elseif ($entity !== 'customers')
                <div class="catalog-panel rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-gray-900">
                    <div class="flex flex-col justify-between gap-2 sm:flex-row sm:items-center">
                        <div>
                            <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                                {{ __('Performance Metrics') }}
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ __('Window: :start to :end (:observed of :requested days observed)', [
                                    'start' => $metrics['date_range']['start_date'] ?? $dateStart,
                                    'end' => $metrics['date_range']['end_date'] ?? $dateEnd,
                                    'observed' => $coverage['observed_days'] ?? 0,
                                    'requested' => $coverage['requested_days'] ?? 0,
                                ]) }}
                            </p>
                        </div>

                        <div>
                            @php
                                $covState = $coverage['state'] ?? 'no_data';
                                $covBadge = match ($covState) {
                                    'complete' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400',
                                    'partial'  => 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400',
                                    default    => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
                                };
                            @endphp
                            <span data-metric-coverage="{{ $covState }}" class="inline-flex items-center rounded-md px-2.5 py-1 text-xs font-medium {{ $covBadge }}">
                                @if ($covState === 'complete')
                                    {{ __('Complete History') }}
                                @elseif ($covState === 'partial')
                                    {{ __('Partial Window Coverage') }}
                                @else
                                    {{ __('No Metric Observations in Window') }}
                                @endif
                            </span>
                        </div>
                    </div>

                    @if (empty($coverage['has_data']))
                        <div class="mt-4 rounded-lg border border-dashed border-gray-300 p-6 text-center text-sm text-gray-500 dark:border-white/10 dark:text-gray-400">
                            {{ __('No daily metric observations were recorded for this entity in the selected date window (:start → :end).', ['start' => $dateStart, 'end' => $dateEnd]) }}
                        </div>
                    @elseif ($metricFamily === 'search')
                        {{-- Search Console Metrics (Impressions, Clicks, CTR, Average Position) --}}
                        <div class="catalog-metric-grid mt-4 grid grid-cols-2 gap-4 sm:grid-cols-4">
                            <div class="catalog-metric rounded-lg border border-gray-100 bg-gray-50/70 p-3.5 dark:border-white/5 dark:bg-gray-800/50">
                                <div class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Impressions') }}</div>
                                <div class="mt-1 text-lg font-bold text-gray-900 dark:text-white">
                                    {{ ($summary['impressions'] ?? null) !== null ? number_format((float) $summary['impressions']) : '—' }}
                                </div>
                            </div>
                            <div class="catalog-metric rounded-lg border border-gray-100 bg-gray-50/70 p-3.5 dark:border-white/5 dark:bg-gray-800/50">
                                <div class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Clicks') }}</div>
                                <div class="mt-1 text-lg font-bold text-gray-900 dark:text-white">
                                    {{ ($summary['clicks'] ?? null) !== null ? number_format((float) $summary['clicks']) : '—' }}
                                </div>
                            </div>
                            <div class="catalog-metric rounded-lg border border-gray-100 bg-gray-50/70 p-3.5 dark:border-white/5 dark:bg-gray-800/50">
                                <div class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('CTR') }}</div>
                                <div class="mt-1 text-lg font-bold text-gray-900 dark:text-white">
                                    {{ ($summary['ctr'] ?? null) !== null ? number_format(((float) $summary['ctr']) * 100, 2) . '%' : '—' }}
                                </div>
                            </div>
                            <div class="catalog-metric rounded-lg border border-gray-100 bg-gray-50/70 p-3.5 dark:border-white/5 dark:bg-gray-800/50">
                                <div class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Avg. Position') }}</div>
                                <div class="mt-1 text-lg font-bold text-gray-900 dark:text-white">
                                    {{ ($summary['position'] ?? null) !== null ? number_format((float) $summary['position'], 2) : '—' }}
                                </div>
                            </div>
                        </div>

                        @if ($series !== [])
                            <div class="mt-6 overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 text-left text-xs dark:divide-white/10">
                                    <thead class="bg-gray-50 font-semibold uppercase text-gray-500 dark:bg-gray-800/60 dark:text-gray-400">
                                        <tr>
                                            <th class="px-3 py-2">{{ __('Date') }}</th>
                                            <th class="px-3 py-2 text-right">{{ __('Impressions') }}</th>
                                            <th class="px-3 py-2 text-right">{{ __('Clicks') }}</th>
                                            <th class="px-3 py-2 text-right">{{ __('CTR') }}</th>
                                            <th class="px-3 py-2 text-right">{{ __('Position') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                                        @foreach ($series as $day => $dayMetrics)
                                            <tr>
                                                <td class="px-3 py-2 font-mono text-gray-800 dark:text-gray-200">{{ $day }}</td>
                                                <td class="px-3 py-2 text-right font-mono">{{ number_format((float) ($dayMetrics['impressions'] ?? 0)) }}</td>
                                                <td class="px-3 py-2 text-right font-mono">{{ number_format((float) ($dayMetrics['clicks'] ?? 0)) }}</td>
                                                <td class="px-3 py-2 text-right font-mono">
                                                    {{ ($dayMetrics['ctr'] ?? null) !== null ? number_format(((float) $dayMetrics['ctr']) * 100, 2) . '%' : '—' }}
                                                </td>
                                                <td class="px-3 py-2 text-right font-mono">
                                                    {{ ($dayMetrics['position'] ?? null) !== null ? number_format((float) $dayMetrics['position'], 2) : '—' }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    @elseif ($metricFamily === 'organic')
                        {{-- Meta Organic Metrics (Impressions, Reach, Views, Likes, Comments, Shares, Saves, Total Interactions) --}}
                        <div class="catalog-metric-grid mt-4 grid grid-cols-2 gap-4 sm:grid-cols-4">
                            <div class="catalog-metric rounded-lg border border-gray-100 bg-gray-50/70 p-3.5 dark:border-white/5 dark:bg-gray-800/50">
                                <div class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Impressions') }}</div>
                                <div class="mt-1 text-lg font-bold text-gray-900 dark:text-white">
                                    {{ ($summary['impressions'] ?? null) !== null ? number_format((float) $summary['impressions']) : '—' }}
                                </div>
                            </div>
                            <div class="catalog-metric rounded-lg border border-gray-100 bg-gray-50/70 p-3.5 dark:border-white/5 dark:bg-gray-800/50">
                                <div class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Reach') }}</div>
                                <div class="mt-1 text-lg font-bold text-gray-900 dark:text-white">
                                    {{ ($summary['reach'] ?? null) !== null ? number_format((float) $summary['reach']) : '—' }}
                                </div>
                            </div>
                            <div class="catalog-metric rounded-lg border border-gray-100 bg-gray-50/70 p-3.5 dark:border-white/5 dark:bg-gray-800/50">
                                <div class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Total Interactions') }}</div>
                                <div class="mt-1 text-lg font-bold text-gray-900 dark:text-white">
                                    {{ ($summary['total_interactions'] ?? null) !== null ? number_format((float) $summary['total_interactions']) : '—' }}
                                </div>
                            </div>
                            <div class="catalog-metric rounded-lg border border-gray-100 bg-gray-50/70 p-3.5 dark:border-white/5 dark:bg-gray-800/50">
                                <div class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Likes / Reactions') }}</div>
                                <div class="mt-1 text-lg font-bold text-gray-900 dark:text-white">
                                    {{ ($summary['likes'] ?? null) !== null ? number_format((float) $summary['likes']) : '—' }}
                                </div>
                            </div>
                            <div class="catalog-metric rounded-lg border border-gray-100 bg-gray-50/70 p-3.5 dark:border-white/5 dark:bg-gray-800/50">
                                <div class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Comments') }}</div>
                                <div class="mt-1 text-lg font-bold text-gray-900 dark:text-white">
                                    {{ ($summary['comments'] ?? null) !== null ? number_format((float) $summary['comments']) : '—' }}
                                </div>
                            </div>
                            <div class="catalog-metric rounded-lg border border-gray-100 bg-gray-50/70 p-3.5 dark:border-white/5 dark:bg-gray-800/50">
                                <div class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Shares') }}</div>
                                <div class="mt-1 text-lg font-bold text-gray-900 dark:text-white">
                                    {{ ($summary['shares'] ?? null) !== null ? number_format((float) $summary['shares']) : '—' }}
                                </div>
                            </div>
                            <div class="catalog-metric rounded-lg border border-gray-100 bg-gray-50/70 p-3.5 dark:border-white/5 dark:bg-gray-800/50">
                                <div class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Saves') }}</div>
                                <div class="mt-1 text-lg font-bold text-gray-900 dark:text-white">
                                    {{ ($summary['saves'] ?? null) !== null ? number_format((float) $summary['saves']) : '—' }}
                                </div>
                            </div>
                            <div class="catalog-metric rounded-lg border border-gray-100 bg-gray-50/70 p-3.5 dark:border-white/5 dark:bg-gray-800/50">
                                <div class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Views') }}</div>
                                <div class="mt-1 text-lg font-bold text-gray-900 dark:text-white">
                                    {{ ($summary['views'] ?? null) !== null ? number_format((float) $summary['views']) : '—' }}
                                </div>
                            </div>
                        </div>

                        @if ($series !== [])
                            <div class="mt-6 overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 text-left text-xs dark:divide-white/10">
                                    <thead class="bg-gray-50 font-semibold uppercase text-gray-500 dark:bg-gray-800/60 dark:text-gray-400">
                                        <tr>
                                            <th class="px-3 py-2">{{ __('Date') }}</th>
                                            <th class="px-3 py-2 text-right">{{ __('Impressions') }}</th>
                                            <th class="px-3 py-2 text-right">{{ __('Reach') }}</th>
                                            <th class="px-3 py-2 text-right">{{ __('Interactions') }}</th>
                                            <th class="px-3 py-2 text-right">{{ __('Likes') }}</th>
                                            <th class="px-3 py-2 text-right">{{ __('Comments') }}</th>
                                            <th class="px-3 py-2 text-right">{{ __('Shares') }}</th>
                                            <th class="px-3 py-2 text-right">{{ __('Saves') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                                        @foreach ($series as $day => $dayMetrics)
                                            <tr>
                                                <td class="px-3 py-2 font-mono text-gray-800 dark:text-gray-200">{{ $day }}</td>
                                                <td class="px-3 py-2 text-right font-mono">{{ number_format((float) ($dayMetrics['impressions'] ?? 0)) }}</td>
                                                <td class="px-3 py-2 text-right font-mono">{{ number_format((float) ($dayMetrics['reach'] ?? 0)) }}</td>
                                                <td class="px-3 py-2 text-right font-mono">{{ number_format((float) ($dayMetrics['total_interactions'] ?? 0)) }}</td>
                                                <td class="px-3 py-2 text-right font-mono">{{ number_format((float) ($dayMetrics['likes'] ?? 0)) }}</td>
                                                <td class="px-3 py-2 text-right font-mono">{{ number_format((float) ($dayMetrics['comments'] ?? 0)) }}</td>
                                                <td class="px-3 py-2 text-right font-mono">{{ number_format((float) ($dayMetrics['shares'] ?? 0)) }}</td>
                                                <td class="px-3 py-2 text-right font-mono">{{ number_format((float) ($dayMetrics['saves'] ?? 0)) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    @elseif ($metricFamily === 'email')
                        {{-- Mailchimp Email Metrics (Sends, Opens, Open Rate, Clicks, Click Rate, Bounces, Unsubscribes) --}}
                        <div class="catalog-metric-grid mt-4 grid grid-cols-2 gap-4 sm:grid-cols-4">
                            <div class="catalog-metric rounded-lg border border-gray-100 bg-gray-50/70 p-3.5 dark:border-white/5 dark:bg-gray-800/50">
                                <div class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Emails Sent') }}</div>
                                <div class="mt-1 text-lg font-bold text-gray-900 dark:text-white">
                                    {{ ($summary['sends'] ?? null) !== null ? number_format((float) $summary['sends']) : '—' }}
                                </div>
                            </div>
                            <div class="catalog-metric rounded-lg border border-gray-100 bg-gray-50/70 p-3.5 dark:border-white/5 dark:bg-gray-800/50">
                                <div class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Opens') }}</div>
                                <div class="mt-1 text-lg font-bold text-gray-900 dark:text-white">
                                    {{ ($summary['opens'] ?? null) !== null ? number_format((float) $summary['opens']) : '—' }}
                                </div>
                            </div>
                            <div class="catalog-metric rounded-lg border border-gray-100 bg-gray-50/70 p-3.5 dark:border-white/5 dark:bg-gray-800/50">
                                <div class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Open Rate') }}</div>
                                <div class="mt-1 text-lg font-bold text-gray-900 dark:text-white">
                                    {{ ($summary['open_rate'] ?? null) !== null ? number_format(((float) $summary['open_rate']) * 100, 2) . '%' : '—' }}
                                </div>
                            </div>
                            <div class="catalog-metric rounded-lg border border-gray-100 bg-gray-50/70 p-3.5 dark:border-white/5 dark:bg-gray-800/50">
                                <div class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Clicks') }}</div>
                                <div class="mt-1 text-lg font-bold text-gray-900 dark:text-white">
                                    {{ ($summary['clicks'] ?? null) !== null ? number_format((float) $summary['clicks']) : '—' }}
                                </div>
                            </div>
                            <div class="catalog-metric rounded-lg border border-gray-100 bg-gray-50/70 p-3.5 dark:border-white/5 dark:bg-gray-800/50">
                                <div class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Click Rate') }}</div>
                                <div class="mt-1 text-lg font-bold text-gray-900 dark:text-white">
                                    {{ ($summary['click_rate'] ?? null) !== null ? number_format(((float) $summary['click_rate']) * 100, 2) . '%' : '—' }}
                                </div>
                            </div>
                            <div class="catalog-metric rounded-lg border border-gray-100 bg-gray-50/70 p-3.5 dark:border-white/5 dark:bg-gray-800/50">
                                <div class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Bounces') }}</div>
                                <div class="mt-1 text-lg font-bold text-gray-900 dark:text-white">
                                    {{ ($summary['bounces'] ?? null) !== null ? number_format((float) $summary['bounces']) : '—' }}
                                </div>
                            </div>
                            <div class="catalog-metric rounded-lg border border-gray-100 bg-gray-50/70 p-3.5 dark:border-white/5 dark:bg-gray-800/50">
                                <div class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Unsubscribes') }}</div>
                                <div class="mt-1 text-lg font-bold text-gray-900 dark:text-white">
                                    {{ ($summary['unsubscribes'] ?? null) !== null ? number_format((float) $summary['unsubscribes']) : '—' }}
                                </div>
                            </div>
                        </div>

                        @if ($series !== [])
                            <div class="mt-6 overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 text-left text-xs dark:divide-white/10">
                                    <thead class="bg-gray-50 font-semibold uppercase text-gray-500 dark:bg-gray-800/60 dark:text-gray-400">
                                        <tr>
                                            <th class="px-3 py-2">{{ __('Date') }}</th>
                                            <th class="px-3 py-2 text-right">{{ __('Sends') }}</th>
                                            <th class="px-3 py-2 text-right">{{ __('Opens') }}</th>
                                            <th class="px-3 py-2 text-right">{{ __('Clicks') }}</th>
                                            <th class="px-3 py-2 text-right">{{ __('Bounces') }}</th>
                                            <th class="px-3 py-2 text-right">{{ __('Unsubscribes') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                                        @foreach ($series as $day => $dayMetrics)
                                            <tr>
                                                <td class="px-3 py-2 font-mono text-gray-800 dark:text-gray-200">{{ $day }}</td>
                                                <td class="px-3 py-2 text-right font-mono">{{ number_format((float) ($dayMetrics['sends'] ?? 0)) }}</td>
                                                <td class="px-3 py-2 text-right font-mono">{{ number_format((float) ($dayMetrics['opens'] ?? 0)) }}</td>
                                                <td class="px-3 py-2 text-right font-mono">{{ number_format((float) ($dayMetrics['clicks'] ?? 0)) }}</td>
                                                <td class="px-3 py-2 text-right font-mono">{{ number_format((float) ($dayMetrics['bounces'] ?? 0)) }}</td>
                                                <td class="px-3 py-2 text-right font-mono">{{ number_format((float) ($dayMetrics['unsubscribes'] ?? 0)) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    @elseif ($metricFamily === 'analytics')
                        {{-- GA4 Analytics Metrics (Views, Sessions, Total Users, Event Count, Conversions) --}}
                        <div class="catalog-metric-grid mt-4 grid grid-cols-2 gap-4 sm:grid-cols-5">
                            <div class="catalog-metric rounded-lg border border-gray-100 bg-gray-50/70 p-3.5 dark:border-white/5 dark:bg-gray-800/50">
                                <div class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Event Count') }}</div>
                                <div class="mt-1 text-lg font-bold text-gray-900 dark:text-white">
                                    {{ ($summary['event_count'] ?? null) !== null ? number_format((float) $summary['event_count']) : '—' }}
                                </div>
                            </div>
                            <div class="catalog-metric rounded-lg border border-gray-100 bg-gray-50/70 p-3.5 dark:border-white/5 dark:bg-gray-800/50">
                                <div class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Views') }}</div>
                                <div class="mt-1 text-lg font-bold text-gray-900 dark:text-white">
                                    {{ ($summary['views'] ?? null) !== null ? number_format((float) $summary['views']) : '—' }}
                                </div>
                            </div>
                            <div class="catalog-metric rounded-lg border border-gray-100 bg-gray-50/70 p-3.5 dark:border-white/5 dark:bg-gray-800/50">
                                <div class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Sessions') }}</div>
                                <div class="mt-1 text-lg font-bold text-gray-900 dark:text-white">
                                    {{ ($summary['sessions'] ?? null) !== null ? number_format((float) $summary['sessions']) : '—' }}
                                </div>
                            </div>
                            <div class="catalog-metric rounded-lg border border-gray-100 bg-gray-50/70 p-3.5 dark:border-white/5 dark:bg-gray-800/50">
                                <div class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Total Users') }}</div>
                                <div class="mt-1 text-lg font-bold text-gray-900 dark:text-white">
                                    {{ ($summary['total_users'] ?? null) !== null ? number_format((float) $summary['total_users']) : '—' }}
                                </div>
                            </div>
                            <div class="catalog-metric rounded-lg border border-gray-100 bg-gray-50/70 p-3.5 dark:border-white/5 dark:bg-gray-800/50">
                                <div class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Conversions') }}</div>
                                <div class="mt-1 text-lg font-bold text-gray-900 dark:text-white">
                                    {{ ($summary['conversions'] ?? null) !== null ? number_format((float) $summary['conversions'], 2) : '—' }}
                                </div>
                            </div>
                        </div>

                        @if ($series !== [])
                            <div class="mt-6 overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 text-left text-xs dark:divide-white/10">
                                    <thead class="bg-gray-50 font-semibold uppercase text-gray-500 dark:bg-gray-800/60 dark:text-gray-400">
                                        <tr>
                                            <th class="px-3 py-2">{{ __('Date') }}</th>
                                            <th class="px-3 py-2 text-right">{{ __('Event Count') }}</th>
                                            <th class="px-3 py-2 text-right">{{ __('Views') }}</th>
                                            <th class="px-3 py-2 text-right">{{ __('Sessions') }}</th>
                                            <th class="px-3 py-2 text-right">{{ __('Total Users') }}</th>
                                            <th class="px-3 py-2 text-right">{{ __('Conversions') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                                        @foreach ($series as $day => $dayMetrics)
                                            <tr>
                                                <td class="px-3 py-2 font-mono text-gray-800 dark:text-gray-200">{{ $day }}</td>
                                                <td class="px-3 py-2 text-right font-mono">{{ number_format((float) ($dayMetrics['event_count'] ?? 0)) }}</td>
                                                <td class="px-3 py-2 text-right font-mono">{{ number_format((float) ($dayMetrics['views'] ?? 0)) }}</td>
                                                <td class="px-3 py-2 text-right font-mono">{{ number_format((float) ($dayMetrics['sessions'] ?? 0)) }}</td>
                                                <td class="px-3 py-2 text-right font-mono">{{ number_format((float) ($dayMetrics['total_users'] ?? 0)) }}</td>
                                                <td class="px-3 py-2 text-right font-mono">{{ number_format((float) ($dayMetrics['conversions'] ?? 0), 2) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    @else
                        {{-- Ads Metrics (Spend, Impressions, Clicks, CTR, CPC, CPM, Conversions, Purchase ROAS) --}}
                        <div class="catalog-metric-grid mt-4 grid grid-cols-2 gap-4 sm:grid-cols-4">
                            <div class="catalog-metric rounded-lg border border-gray-100 bg-gray-50/70 p-3.5 dark:border-white/5 dark:bg-gray-800/50">
                                <div class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Spend') }}</div>
                                <div class="mt-1 text-lg font-bold text-gray-900 dark:text-white">
                                    {{ ($summary['spend'] ?? null) !== null ? $currencyPrefix . number_format((float) $summary['spend'], 2) : '—' }}
                                </div>
                            </div>
                            <div class="catalog-metric rounded-lg border border-gray-100 bg-gray-50/70 p-3.5 dark:border-white/5 dark:bg-gray-800/50">
                                <div class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Impressions') }}</div>
                                <div class="mt-1 text-lg font-bold text-gray-900 dark:text-white">
                                    {{ ($summary['impressions'] ?? null) !== null ? number_format((float) $summary['impressions']) : '—' }}
                                </div>
                            </div>
                            <div class="catalog-metric rounded-lg border border-gray-100 bg-gray-50/70 p-3.5 dark:border-white/5 dark:bg-gray-800/50">
                                <div class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Clicks') }}</div>
                                <div class="mt-1 text-lg font-bold text-gray-900 dark:text-white">
                                    {{ ($summary['clicks'] ?? null) !== null ? number_format((float) $summary['clicks']) : '—' }}
                                </div>
                            </div>
                            <div class="catalog-metric rounded-lg border border-gray-100 bg-gray-50/70 p-3.5 dark:border-white/5 dark:bg-gray-800/50">
                                <div class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('CTR') }}</div>
                                <div class="mt-1 text-lg font-bold text-gray-900 dark:text-white">
                                    {{ ($summary['ctr'] ?? null) !== null ? number_format(((float) $summary['ctr']) * 100, 2) . '%' : '—' }}
                                </div>
                            </div>
                            <div class="catalog-metric rounded-lg border border-gray-100 bg-gray-50/70 p-3.5 dark:border-white/5 dark:bg-gray-800/50">
                                <div class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('CPC') }}</div>
                                <div class="mt-1 text-lg font-bold text-gray-900 dark:text-white">
                                    {{ ($summary['cpc'] ?? null) !== null ? $currencyPrefix . number_format((float) $summary['cpc'], 2) : '—' }}
                                </div>
                            </div>
                            <div class="catalog-metric rounded-lg border border-gray-100 bg-gray-50/70 p-3.5 dark:border-white/5 dark:bg-gray-800/50">
                                <div class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('CPM') }}</div>
                                <div class="mt-1 text-lg font-bold text-gray-900 dark:text-white">
                                    {{ ($summary['cpm'] ?? null) !== null ? $currencyPrefix . number_format((float) $summary['cpm'], 2) : '—' }}
                                </div>
                            </div>
                            <div class="catalog-metric rounded-lg border border-gray-100 bg-gray-50/70 p-3.5 dark:border-white/5 dark:bg-gray-800/50">
                                <div class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Conversions') }}</div>
                                <div class="mt-1 text-lg font-bold text-gray-900 dark:text-white">
                                    {{ ($summary['conversions'] ?? null) !== null ? number_format((float) $summary['conversions'], 2) : '—' }}
                                </div>
                            </div>
                            <div class="catalog-metric rounded-lg border border-gray-100 bg-gray-50/70 p-3.5 dark:border-white/5 dark:bg-gray-800/50">
                                <div class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Purchase ROAS') }}</div>
                                <div class="mt-1 text-lg font-bold text-gray-900 dark:text-white">
                                    {{ ($summary['roas_purchase'] ?? null) !== null ? number_format((float) $summary['roas_purchase'], 2) . 'x' : '—' }}
                                </div>
                            </div>
                        </div>

                        @if ($series !== [])
                            <div class="mt-6 overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 text-left text-xs dark:divide-white/10">
                                    <thead class="bg-gray-50 font-semibold uppercase text-gray-500 dark:bg-gray-800/60 dark:text-gray-400">
                                        <tr>
                                            <th class="px-3 py-2">{{ __('Date') }}</th>
                                            <th class="px-3 py-2 text-right">{{ __('Spend') }}</th>
                                            <th class="px-3 py-2 text-right">{{ __('Impressions') }}</th>
                                            <th class="px-3 py-2 text-right">{{ __('Clicks') }}</th>
                                            <th class="px-3 py-2 text-right">{{ __('CTR') }}</th>
                                            <th class="px-3 py-2 text-right">{{ __('CPC') }}</th>
                                            <th class="px-3 py-2 text-right">{{ __('Conversions') }}</th>
                                            <th class="px-3 py-2 text-right">{{ __('ROAS') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                                        @foreach ($series as $day => $dayMetrics)
                                            <tr>
                                                <td class="px-3 py-2 font-mono text-gray-800 dark:text-gray-200">{{ $day }}</td>
                                                <td class="px-3 py-2 text-right font-mono">{{ $currencyPrefix }}{{ number_format((float) ($dayMetrics['spend'] ?? 0), 2) }}</td>
                                                <td class="px-3 py-2 text-right font-mono">{{ number_format((float) ($dayMetrics['impressions'] ?? 0)) }}</td>
                                                <td class="px-3 py-2 text-right font-mono">{{ number_format((float) ($dayMetrics['clicks'] ?? 0)) }}</td>
                                                <td class="px-3 py-2 text-right font-mono">
                                                    {{ ($dayMetrics['ctr'] ?? null) !== null ? number_format(((float) $dayMetrics['ctr']) * 100, 2) . '%' : '—' }}
                                                </td>
                                                <td class="px-3 py-2 text-right font-mono">
                                                    {{ ($dayMetrics['cpc'] ?? null) !== null ? $currencyPrefix . number_format((float) $dayMetrics['cpc'], 2) : '—' }}
                                                </td>
                                                <td class="px-3 py-2 text-right font-mono">{{ number_format((float) ($dayMetrics['conversions'] ?? 0), 2) }}</td>
                                                <td class="px-3 py-2 text-right font-mono">
                                                    {{ ($dayMetrics['roas_purchase'] ?? null) !== null ? number_format((float) $dayMetrics['roas_purchase'], 2) . 'x' : '—' }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    @endif
                </div>
            @endif

            {{-- Section 4: Recorded Activity & Lifecycle Milestones --}}
            @if ($activityData !== null)
                @php
                    $actItems = $activityData['data'] ?? [];
                    $actCoverage = $activityData['coverage'] ?? [];
                    $actMeta = $activityData['meta'] ?? [];
                @endphp
                <div class="catalog-panel rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-gray-900">
                    <div class="catalog-activity-heading flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                        <div>
                            <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                                {{ __('Recorded Activity & Lifecycle Milestones') }}
                            </h3>
                            @if (! empty($actCoverage['notice']))
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    {{ $actCoverage['notice'] }}
                                </p>
                            @endif
                        </div>

                        <div class="catalog-activity-controls flex flex-wrap items-center gap-3">
                            <x-ui.select-input
                                wire:model.live="activityKind" aria-label="{{ __('Activity type') }}"
                                class="rounded-lg border-gray-300 bg-white text-xs text-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-gray-800 dark:text-white"
                            >
                                <option value="">{{ __('All milestone & activity types') }}</option>
                                <option value="created">{{ __('Created') }}</option>
                                <option value="published">{{ __('Published') }}</option>
                                <option value="sent">{{ __('Sent') }}</option>
                                <option value="email_activity">{{ __('Email Recipient Activity') }}</option>
                                <option value="lead_submitted">{{ __('Lead Submitted') }}</option>
                                <option value="scheduled_start">{{ __('Scheduled Start') }}</option>
                                <option value="scheduled_end">{{ __('Scheduled End') }}</option>
                                <option value="first_metric_observed">{{ __('First Metric Observed') }}</option>
                                <option value="latest_metric_observed">{{ __('Latest Metric Observed') }}</option>
                                <option value="first_synced">{{ __('First Synced') }}</option>
                                <option value="synced">{{ __('Latest Sync Snapshot') }}</option>
                            </x-ui.select-input>

                            <x-ui.select-input
                                wire:model.live="activitySort" aria-label="{{ __('Activity order') }}"
                                class="rounded-lg border-gray-300 bg-white text-xs text-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-gray-800 dark:text-white"
                            >
                                <option value="newest">{{ __('Newest first') }}</option>
                                <option value="oldest">{{ __('Oldest first') }}</option>
                            </x-ui.select-input>
                        </div>
                    </div>

                    @if ($actItems === [])
                        <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">
                            {{ __('No recorded lifecycle milestones matched the selected filter.') }}
                        </p>
                    @else
                        <div class="catalog-activity-list mt-4 divide-y divide-gray-100 dark:divide-white/5">
                            @foreach ($actItems as $act)
                                <div class="catalog-activity-item flex flex-col justify-between gap-2 py-3 sm:flex-row sm:items-center">
                                    <div>
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ $act['label'] }}</span>
                                            <span class="rounded bg-gray-100 px-2 py-0.5 text-[11px] font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                                                {{ $act['quality_label'] ?? $act['quality'] }}
                                            </span>
                                            @if (! empty($act['related']['reference']) && ! empty($act['related']['entity_type']))
                                                <a
                                                    href="{{ $this->catalogUrl($act['related']['entity_type'], $act['related']['reference']) }}"
                                                    class="inline-flex items-center gap-1 rounded bg-primary-50 px-2 py-0.5 text-[11px] font-semibold text-primary-700 hover:underline dark:bg-primary-950/60 dark:text-primary-300"
                                                >
                                                    {{ $act['related']['name'] ?? $act['related']['reference'] }} →
                                                </a>
                                            @endif
                                        </div>
                                        <p class="mt-0.5 text-xs text-gray-600 dark:text-gray-400">{{ $act['description'] }}</p>
                                    </div>
                                    <div class="font-mono text-xs text-gray-500 dark:text-gray-400">
                                        {{ $act['occurred_at'] }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if (! empty($actMeta['next_cursor']) || $activityCursor !== '')
                        <div class="mt-4 flex items-center justify-end gap-2 border-t border-gray-100 pt-3 dark:border-white/10">
                            @if ($activityCursor !== '')
                                <button
                                    type="button"
                                    wire:click="resetActivityPagination"
                                    class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-white/10 dark:bg-gray-800 dark:text-gray-200"
                                >
                                    {{ __('First Page') }}
                                </button>
                            @endif
                            @if (! empty($actMeta['next_cursor']))
                                <button
                                    type="button"
                                    wire:click="loadMoreActivity('{{ $actMeta['next_cursor'] }}')"
                                    class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-white/10 dark:bg-gray-800 dark:text-gray-200"
                                >
                                    {{ __('Older Milestones →') }}
                                </button>
                            @endif
                        </div>
                    @endif
                </div>
            @endif
        @endif
    </div>
</x-filament-panels::page>
