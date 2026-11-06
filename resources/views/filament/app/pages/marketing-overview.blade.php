<x-filament-panels::page>
    <link rel="stylesheet" href="{{ asset('css/catalog.css') }}?v={{ filemtime(public_path('css/catalog.css')) }}">
    <div class="catalog-page catalog-overview space-y-6">
        {{-- Shared Scope & Date Window Bar --}}
        <div class="catalog-filter-panel">
            <div class="catalog-overview-filters">
                <div class="catalog-overview-filter-grid">
                    <div>
                        <label for="overview-scope-select" class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            {{ __('Asset Scope') }}
                        </label>
                        <x-ui.select-input
                            id="overview-scope-select"
                            wire:model.live="scope"
                            class="mt-1 block w-full rounded-lg border-gray-300 bg-white text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-gray-800 dark:text-white"
                        >
                            @foreach ($scopeOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </x-ui.select-input>
                    </div>

                    <div>
                        <label for="overview-date-start" class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            {{ __('Start Date') }}
                        </label>
                        <x-ui.date-input
                            id="overview-date-start"

                            wire:model.live="dateStart"
                            class="mt-1 block w-full rounded-lg border-gray-300 bg-white text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-gray-800 dark:text-white"
                        />
                    </div>

                    <div>
                        <label for="overview-date-end" class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            {{ __('End Date') }}
                        </label>
                        <x-ui.date-input
                            id="overview-date-end"

                            wire:model.live="dateEnd"
                            class="mt-1 block w-full rounded-lg border-gray-300 bg-white text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-gray-800 dark:text-white"
                        />
                    </div>
                </div>

                <div class="catalog-presets" role="group" aria-label="{{ __('Date range') }}">
                    @foreach (['7d' => __('7 Days'), '30d' => __('30 Days'), '90d' => __('90 Days')] as $presetKey => $presetLabel)
                        <button
                            type="button"
                            wire:click="setPreset('{{ $presetKey }}')" aria-pressed="{{ $preset === $presetKey ? 'true' : 'false' }}"
                            class="rounded-md px-3 py-1.5 text-xs font-semibold transition {{ $preset === $presetKey ? 'bg-primary-600 text-white shadow-sm' : 'text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white' }}"
                        >
                            {{ $presetLabel }}
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Deduplicated Permitted Asset Summary Pills --}}
            @if ($channelAssetCounts !== [])
                <div class="catalog-overview-channel-summary">
                    <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        {{ __('Permitted Assets in Scope (Deduplicated):') }}
                    </span>
                    @foreach ($channelAssetCounts as $channelKey => $assetCount)
                        <span class="catalog-channel">
                            <span>{{ \App\Services\Catalog\CatalogScopeResolver::SUPPORTED_CHANNELS[$channelKey] ?? $channelKey }}</span>
                            <span class="rounded bg-blue-100 px-1.5 py-0.2 font-mono text-[11px] font-bold dark:bg-blue-900/60">{{ $assetCount }}</span>
                        </span>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- State Banners --}}
        @if ($stateCode !== null)
            @php
                $bannerStyles = match ($stateCode) {
                    'tenant_upgrading', 'capacity_exhausted' => 'border-amber-300 bg-amber-50 text-amber-900 dark:border-amber-500/30 dark:bg-amber-950/40 dark:text-amber-200',
                    'catalog_unavailable', 'remote_error', 'unauthorized_scope' => 'border-red-300 bg-red-50 text-red-900 dark:border-red-500/30 dark:bg-red-950/40 dark:text-red-200',
                    default => 'border-gray-200 bg-gray-50 text-gray-700 dark:border-white/10 dark:bg-gray-800/60 dark:text-gray-300',
                };
            @endphp
            <div data-overview-state="{{ $stateCode }}" class="catalog-notice" role="status">
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
                        @case('unauthorized_scope')
                            {{ __('Unauthorized Asset Group Scope') }}
                            @break
                        @case('empty_scope')
                            {{ __('No Permitted Assets in Scope') }}
                            @break
                        @default
                            {{ __('Marketing Overview Notice') }}
                    @endswitch
                </h3>
                <p class="mt-1 text-sm opacity-90">{{ $stateMessage }}</p>
            </div>
        @endif

        @if ($stateCode === null)
            {{-- Companion Entity-Measures Capability Notice --}}
            <div class="catalog-overview-coverage">
                <div>
                    <span class="font-semibold text-gray-900 dark:text-white">{{ __('Catalog coverage:') }}</span>
                    {{ __('Showing cached entities for your permitted assets (:start → :end). Open an entity to inspect its available metrics and activity.', ['start' => $dateStart, 'end' => $dateEnd]) }}
                </div>
                <span class="inline-flex items-center rounded-md bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400">
                    {{ __('Current scope') }}
                </span>
            </div>

            {{-- Domain Sections Grid --}}
            <div class="catalog-overview-grid">
                @foreach ($domainSections as $domainKey => $section)
                    <div data-overview-domain="{{ $domainKey }}" class="catalog-domain-card">
                        <div>
                            <div class="catalog-domain-heading">
                                <div>
                                    <h3 class="text-base font-bold text-gray-900 dark:text-white">{{ $section['title'] }}</h3>
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $section['description'] }}</p>
                                </div>
                                @php
                                    $covBadge = match ($section['coverage'] ?? 'no_assets') {
                                        'active' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400',
                                        'sparse' => 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400',
                                        default  => 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400',
                                    };
                                @endphp
                                <span class="catalog-domain-coverage" data-coverage="{{ $section['coverage'] ?? 'no_assets' }}">
                                    @if (($section['coverage'] ?? '') === 'active')
                                        {{ __(':count Permitted Assets', ['count' => $section['asset_count'] ?? 0]) }}
                                    @elseif (($section['coverage'] ?? '') === 'sparse')
                                        {{ __('Sparse Coverage') }}
                                    @else
                                        {{ __('No Assets in Scope') }}
                                    @endif
                                </span>
                            </div>

                            {{-- Indicators --}}
                            <div class="catalog-domain-indicators">
                                @foreach ($section['indicators'] ?? [] as $ind)
                                    <a
                                        href="{{ $ind['url'] }}"
                                        class="catalog-domain-indicator group"
                                    >
                                        <div class="text-xs font-medium text-gray-500 group-hover:text-primary-600 dark:text-gray-400 dark:group-hover:text-primary-400">
                                            {{ $ind['label'] }}
                                        </div>
                                        <div class="mt-1 flex items-baseline justify-between">
                                            <span class="text-lg font-bold text-gray-900 dark:text-white">
                                                {{ number_format((int) ($ind['count'] ?? 0)) }}
                                            </span>
                                            <span class="text-xs text-primary-600 opacity-0 transition group-hover:opacity-100 dark:text-primary-400">→</span>
                                        </div>
                                    </a>
                                @endforeach
                            </div>

                            {{-- Sample Records / Quick Drilldowns --}}
                            @if (! empty($section['highlights']))
                                <div class="catalog-domain-highlights">
                                    <div class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                                        {{ __('Direct Catalog Drilldowns') }}
                                    </div>
                                    <div class="mt-2 divide-y divide-gray-100 dark:divide-white/5">
                                        @foreach ($section['highlights'] as $hl)
                                            <div class="catalog-domain-highlight">
                                                <div class="truncate pr-3">
                                                    <a
                                                        href="{{ $this->catalogUrl($hl['entity_type'], $hl['reference'], ['dateStart' => $dateStart, 'dateEnd' => $dateEnd]) }}"
                                                        class="font-semibold text-gray-900 hover:text-primary-600 hover:underline dark:text-white dark:hover:text-primary-400"
                                                    >
                                                        {{ $hl['name'] }}
                                                    </a>
                                                    <span class="ml-1.5 rounded bg-gray-100 px-1.5 py-0.5 text-[10px] font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                                                        {{ $hl['channel_label'] ?? $hl['channel'] }}
                                                    </span>
                                                </div>
                                                <span class="shrink-0 font-mono text-[11px] text-gray-500 dark:text-gray-400">
                                                    {{ $hl['status'] ?? 'ACTIVE' }}
                                                </span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-filament-panels::page>
