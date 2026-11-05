<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Shared Scope & Date Window Bar --}}
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-900">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3 flex-1">
                    <div>
                        <label for="overview-scope-select" class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            {{ __('Asset Scope') }}
                        </label>
                        <select
                            id="overview-scope-select"
                            wire:model.live="scope"
                            class="mt-1 block w-full rounded-lg border-gray-300 bg-white text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-gray-800 dark:text-white"
                        >
                            @foreach ($scopeOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="overview-date-start" class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            {{ __('Start Date') }}
                        </label>
                        <input
                            id="overview-date-start"
                            type="date"
                            wire:model.live="dateStart"
                            class="mt-1 block w-full rounded-lg border-gray-300 bg-white text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-gray-800 dark:text-white"
                        />
                    </div>

                    <div>
                        <label for="overview-date-end" class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            {{ __('End Date') }}
                        </label>
                        <input
                            id="overview-date-end"
                            type="date"
                            wire:model.live="dateEnd"
                            class="mt-1 block w-full rounded-lg border-gray-300 bg-white text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-gray-800 dark:text-white"
                        />
                    </div>
                </div>

                <div class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-gray-50 p-1 dark:border-white/10 dark:bg-gray-800">
                    @foreach (['7d' => __('7 Days'), '30d' => __('30 Days'), '90d' => __('90 Days')] as $presetKey => $presetLabel)
                        <button
                            type="button"
                            wire:click="setPreset('{{ $presetKey }}')"
                            class="rounded-md px-3 py-1.5 text-xs font-semibold transition {{ $preset === $presetKey ? 'bg-primary-600 text-white shadow-sm' : 'text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white' }}"
                        >
                            {{ $presetLabel }}
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Deduplicated Permitted Asset Summary Pills --}}
            @if ($channelAssetCounts !== [])
                <div class="mt-4 flex flex-wrap items-center gap-2 border-t border-gray-100 pt-3 dark:border-white/5">
                    <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        {{ __('Permitted Assets in Scope (Deduplicated):') }}
                    </span>
                    @foreach ($channelAssetCounts as $channelKey => $assetCount)
                        <span class="inline-flex items-center gap-1.5 rounded-md bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10 dark:bg-blue-400/10 dark:text-blue-400 dark:ring-blue-400/30">
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
            <div data-overview-state="{{ $stateCode }}" class="rounded-xl border p-6 shadow-sm {{ $bannerStyles }}">
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
            <div class="rounded-xl border border-gray-200 bg-gray-50/70 px-4 py-3 text-xs text-gray-600 dark:border-white/10 dark:bg-gray-800/40 dark:text-gray-300 flex flex-wrap items-center justify-between gap-2">
                <div>
                    <span class="font-semibold text-gray-900 dark:text-white">{{ __('Coverage & Capability Status:') }}</span>
                    {{ __('Catalog browsing and profile drilldowns are active for your authorized scope (:start → :end).', ['start' => $dateStart, 'end' => $dateEnd]) }}
                    @if (! $entityMeasuresSupported)
                        {{ __('Entity-derived summary aggregations are part of the companion track and will enrich this overview automatically when enabled on the tenant node.') }}
                    @endif
                </div>
                <span class="inline-flex items-center rounded-md bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400">
                    {{ __('Shared Read Foundation v1') }}
                </span>
            </div>

            {{-- Domain Sections Grid --}}
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                @foreach ($domainSections as $domainKey => $section)
                    <div data-overview-domain="{{ $domainKey }}" class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-gray-900 flex flex-col justify-between">
                        <div>
                            <div class="flex items-start justify-between gap-4">
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
                                <span class="shrink-0 rounded-md px-2.5 py-1 text-xs font-medium {{ $covBadge }}">
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
                            <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3">
                                @foreach ($section['indicators'] ?? [] as $ind)
                                    <a
                                        href="{{ $ind['url'] }}"
                                        class="group rounded-lg border border-gray-100 bg-gray-50/70 p-3 transition hover:border-primary-300 hover:bg-primary-50/30 dark:border-white/5 dark:bg-gray-800/50 dark:hover:border-primary-500/40"
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
                                <div class="mt-5 border-t border-gray-100 pt-4 dark:border-white/5">
                                    <div class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                                        {{ __('Direct Catalog Drilldowns') }}
                                    </div>
                                    <div class="mt-2 divide-y divide-gray-100 dark:divide-white/5">
                                        @foreach ($section['highlights'] as $hl)
                                            <div class="flex items-center justify-between py-2 text-xs">
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
