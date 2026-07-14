<link rel="stylesheet" href="{{ asset('css/dashboard-builder.css') }}"/>

<div x-data="dashboardView()" x-init="init()" id="dashboard-view-container" class="space-y-4"
     @open-widget-settings.window="openWidgetSettings($event.detail.widgetId, $event.detail.controls, $event.detail.builderControls, $event.detail.seriesOptions, $event.detail.variables, $event.detail.granularityOnTheGo, $event.detail.sourceType)"
     @open-pop-out.window="openPopOut($event.detail.widgetId)">
    {{-- Header --}}
    <div class="flex items-center justify-between gap-4 rounded-xl bg-gray-50 dark:bg-gray-900 p-4">
        <div>
            <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ $this->dashboard->name }}</h1>
            @if ($this->dashboard->description)
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $this->dashboard->description }}</p>
            @endif
        </div>
        <div class="flex items-center gap-2">
                <span class="text-xs text-gray-400 dark:text-gray-500">
                    <span x-text="loadedCount"></span>/<span x-text="totalCount"></span> loaded
                    <button x-on:click="refreshAll()" class="ml-2 text-primary-500 hover:underline">Refresh all</button>
                </span>
        </div>
    </div>

    {{-- Dashboard Controls (on-the-go) --}}
    @php $dc = $this->dashboard->controls ?? []; @endphp
    <div class="flex flex-wrap items-center gap-3 text-xs">
        <span class="text-xs text-gray-500 dark:text-gray-400 font-medium">Date range:</span>
        <input type="date" x-model="dashboardOverrides.date_start"
               x-on:change.debounce.500ms="applyDateRange()"
               class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 px-2 py-1.5 text-xs"
               :max="dashboardOverrides.date_end || '{{ date('Y-m-d', strtotime('-1 day')) }}'">
        <span class="text-gray-400">â†’</span>
        <input type="date" x-model="dashboardOverrides.date_end"
               x-on:change.debounce.500ms="applyDateRange()"
               class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 px-2 py-1.5 text-xs"
               :min="dashboardOverrides.date_start || ''" :max="dashboardDefaults.date_end">
        <template x-if="dashboardDefaults.show_asset_group_selector">
            <span class="text-xs text-gray-500 dark:text-gray-400 font-medium ml-2">{{ __('Asset Group:') }}</span>
        </template>
        <template x-if="dashboardDefaults.show_asset_group_selector">
            <select x-model="selectedAssetGroup" x-on:change="applyAssetGroup()"
                    class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 px-2 py-1.5 text-xs">
                <template x-for="(name, id) in assetGroups" :key="id">
                    <option :value="id" x-text="name"></option>
                </template>
            </select>
        </template>
        <span class="text-xs text-gray-400 dark:text-gray-500 ml-2">
                <span x-text="loadedCount"></span>/<span x-text="totalCount"></span> loaded
                <button x-on:click="refreshAll()" class="ml-2 text-primary-500 hover:underline">Refresh all</button>
            </span>
    </div>

    {{-- Grid --}}
    <div id="view-grid-stack" class="grid-stack">
        @foreach ($this->widgets as $widget)
            <div class="grid-stack-item"
                 gs-id="{{ $widget['id'] }}"
                 gs-x="{{ $widget['grid_x'] }}"
                 gs-y="{{ $widget['grid_y'] }}"
                 gs-w="{{ $widget['grid_w'] }}"
                 gs-h="{{ $widget['grid_h'] }}">
                <div
                    class="grid-stack-item-content rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-sm relative flex flex-col"
                    style="overflow: visible !important;">
                    @if ($widget['title'] || $widget['name'])
                        <div
                            class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 flex items-center justify-between flex-shrink-0 rounded-t-xl relative"
                            style="z-index: 10;"
                            x-data="widgetHeader"
                            data-widget-id="{{ $widget['id'] }}"
                            data-controls="{{ json_encode($widget['resolved_controls']) }}"
                            data-series-options="{{ json_encode($widget['series_assets_options']) }}"
                            data-metric-options="{{ json_encode($widget['metric_options']) }}"
                            data-source-type="{{ $widget['source_type'] }}"
                            data-variables="{{ json_encode($widget['variables'] ?? []) }}"
                            @reload-widget.window="if ($event.detail.id === {{ $widget['id'] }}) controls = $event.detail.controls">
                            <div>
                                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">{{ $widget['title'] ?? $widget['name'] }}</h3>
                                <div class="flex flex-wrap gap-1 mt-1" x-show="getBadges().length > 0">
                                    <template x-for="(badge, index) in getBadges()" :key="index">
                                            <span
                                                class="inline-flex items-center rounded-full font-medium bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-300 border border-gray-200 dark:border-gray-600 shadow-sm"
                                                style="font-size: 10px; line-height: 14px; padding: 2px 8px;">
                                                <span class="font-bold mr-1" x-text="badge.label + ':'"></span>
                                                <span x-text="badge.text"></span>
                                            </span>
                                    </template>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                @if (!empty($widget['kpi_theory']))
                                    {{-- KPI Theory info button (rich HTML tooltip) --}}
                                    <div x-data="{ showKpi: false }"
                                         @click.outside="showKpi = false"
                                         class="relative flex items-center justify-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                             stroke-width="2" stroke="currentColor"
                                             @click.stop="showKpi = !showKpi"
                                             class="w-4 h-4 text-indigo-400 hover:text-indigo-600 dark:hover:text-indigo-300 cursor-pointer transition-colors">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                  d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/>
                                        </svg>
                                        <div x-show="showKpi"
                                             x-transition:enter="transition ease-out duration-100"
                                             x-transition:enter-start="opacity-0"
                                             x-transition:enter-end="opacity-100"
                                             x-transition:leave="transition ease-in duration-75"
                                             x-transition:leave-start="opacity-100"
                                             x-transition:leave-end="opacity-0"
                                             @click.stop
                                             class="absolute bottom-full mb-2 w-96 z-50 right-0 sm:right-auto sm:left-1/2 sm:-translate-x-1/2">
                                            <div
                                                class="rounded-lg bg-gray-900 dark:bg-gray-800 px-4 py-3 shadow-xl whitespace-normal text-left">
                                                {{-- Badge / type label --}}
                                                <div class="inline-flex items-center gap-1.5 rounded-full bg-indigo-500/20 px-2.5 py-0.5 mb-2">
                                                    <svg class="w-3 h-3 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 00-2.455 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z"/>
                                                    </svg>
                                                    <span class="text-indigo-300 font-semibold text-[11px] leading-4 tracking-wide uppercase">{{ $widget['kpi_theory']['type_label'] }}</span>
                                                </div>

                                                {{-- Explanation --}}
                                                <p class="text-xs text-gray-200 leading-relaxed mb-2.5">{{ $widget['kpi_theory']['explanation'] }}</p>

                                                {{-- Divider --}}
                                                @if (!empty($widget['kpi_theory']['use_case']) || !empty($widget['kpi_theory']['interpretation']))
                                                    <div class="border-t border-gray-700 dark:border-gray-600 my-2"></div>
                                                @endif

                                                {{-- Use Case --}}
                                                @if (!empty($widget['kpi_theory']['use_case']))
                                                    <div class="mb-2 last:mb-0">
                                                        <div class="flex items-center gap-1.5 mb-0.5">
                                                            <svg class="w-3 h-3 text-emerald-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                            </svg>
                                                            <span class="font-semibold text-emerald-300 text-[11px] uppercase tracking-wide">Use Case</span>
                                                        </div>
                                                        <p class="text-xs text-gray-300 leading-relaxed pl-5">{{ $widget['kpi_theory']['use_case'] }}</p>
                                                    </div>
                                                @endif

                                                {{-- Interpretation --}}
                                                @if (!empty($widget['kpi_theory']['interpretation']))
                                                    <div class="mb-2 last:mb-0">
                                                        <div class="flex items-center gap-1.5 mb-0.5">
                                                            <svg class="w-3 h-3 text-amber-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 18v-5.25m0 0a6.01 6.01 0 001.5-.189m-1.5.189a6.01 6.01 0 01-1.5-.189m3.75 7.478a12.06 12.06 0 01-4.5 0m3.75 2.383a14.406 14.406 0 01-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 10-7.517 0c.85.493 1.509 1.333 1.509 2.316V18"/>
                                                            </svg>
                                                            <span class="font-semibold text-amber-300 text-[11px] uppercase tracking-wide">Interpretation</span>
                                                        </div>
                                                        <p class="text-xs text-gray-300 leading-relaxed pl-5">{{ $widget['kpi_theory']['interpretation'] }}</p>
                                                    </div>
                                                @endif

                                                {{-- Arrow --}}
                                                <div
                                                    class="absolute -bottom-1 right-3 sm:right-auto sm:left-1/2 sm:-translate-x-1/2 h-2 w-2 rotate-45 bg-gray-900 dark:bg-gray-800"></div>
                                            </div>
                                        </div>
                                    </div>
                                @elseif (!empty($widget['description']))
                                    {{-- Info button (simple tooltip) --}}
                                    <div class="group relative flex items-center justify-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                             stroke-width="2" stroke="currentColor"
                                             class="w-4 h-4 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 cursor-help transition-colors">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                  d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/>
                                        </svg>
                                        <div
                                            class="pointer-events-none absolute bottom-full mb-2 w-64 opacity-0 transition-opacity group-hover:opacity-100 z-50 right-0 sm:right-auto sm:left-1/2 sm:-translate-x-1/2">
                                            <div
                                                class="rounded-lg bg-gray-900 dark:bg-gray-700 px-3 py-2 text-xs text-white shadow-lg whitespace-normal text-left">
                                                {{ $widget['description'] }}
                                                <div
                                                    class="absolute -bottom-1 right-2 sm:right-auto sm:left-1/2 sm:-translate-x-1/2 h-2 w-2 rotate-45 bg-gray-900 dark:bg-gray-700"></div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                                {{-- Settings button (gear icon) --}}
                                <button @click="openDashboardSettings()"
                                        class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition-colors p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700"
                                        title="Widget Settings">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                         stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                              d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                              d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                </button>
                                {{-- Expand button (fullscreen pop-out) --}}
                                <button @click="$dispatch('open-pop-out', { widgetId })"
                                        class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition-colors p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700"
                                        title="Expand to Fullscreen">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                         stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                              d="M3.75 3.75v4.5m0-4.5h4.5m-4.5 0L9 9M3.75 20.25v-4.5m0 4.5h4.5m-4.5 0L9 15M20.25 3.75h-4.5m4.5 0v4.5m0-4.5L15 9m5.25 11.25h-4.5m4.5 0v-4.5m0 4.5L15 15"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    @endif
                    <div class="widget-content flex-grow p-4 relative overflow-y-auto"
                         data-widget-id="{{ $widget['id'] }}"
                         x-init="renderWidget({{ $widget['id'] }}, $el, {{ json_encode($widget['resolved_controls']) }})">
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    @if (empty($this->widgets))
        <div class="flex flex-col items-center justify-center py-24 text-center">
            <x-filament::icon name="heroicon-o-squares-2x2" class="w-16 h-16 text-gray-300 dark:text-gray-600 mb-4"/>
            <p class="text-gray-500 dark:text-gray-400 text-lg">No widgets on this dashboard yet</p>
        </div>
    @endif

    {{-- Fullscreen Pop-Out Modal --}}
    <div x-show="popOutActive"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-out duration-300"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         style="display: none; z-index: 999998;"
         class="fixed inset-0 flex items-center justify-center bg-black/50"
         @click.self="closePopOut()">
        <div x-ref="popOutCard"
             class="relative bg-white dark:bg-gray-900 rounded-xl shadow-2xl flex flex-col overflow-hidden"
             style="width: 95vw; height: 95vh; max-width: 1400px;">
            <div
                class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex-shrink-0 bg-gray-50 dark:bg-gray-800">
                <div class="flex items-center gap-2 min-w-0 pr-4">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white truncate" x-text="popOutTitle"></h3>
                    <template x-for="(badge, index) in popOutBadges" :key="index">
                        <span class="inline-flex items-center rounded-full font-medium bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-300 border border-gray-200 dark:border-gray-600 shadow-sm whitespace-nowrap" style="font-size: 10px; line-height: 14px; padding: 2px 8px;">
                            <span class="font-semibold mr-1" x-text="badge.label + ':'"></span>
                            <span x-text="badge.text"></span>
                        </span>
                    </template>
                </div>
                <div class="flex items-center gap-1 flex-shrink-0">
                    <button @click="openModalSettings()"
                            class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition-colors p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700"
                            title="Widget Settings">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                             stroke="currentColor" class="w-4 h-4">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </button>
                    <button @click="closePopOut()"
                            class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition-colors p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700"
                            title="Restore to Dashboard">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                             stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M9 9V4.5M9 9H4.5M9 9L3.75 3.75M9 15v4.5M9 15H4.5M9 15l-5.25 5.25M15 9h4.5M15 9V4.5M15 9l5.25-5.25M15 15h4.5M15 15v4.5m0-4.5l5.25 5.25"/>
                        </svg>
                    </button>
                    <div class="w-px h-5 bg-gray-200 dark:bg-gray-700 mx-1"></div>
                    <button @click="closePopOut()"
                            class="text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300 transition-colors p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700"
                            title="Close">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                             stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>
            <div x-ref="popOutContent" class="flex-grow relative overflow-hidden" style="padding: 2.5rem;">
            </div>
        </div>
    </div>

    {{-- Settings Modal (teleported to body to avoid z-index issues) --}}
    <template x-teleport="body">
        <div x-show="openSettings" style="display: none; z-index: 999999;"
             class="fixed inset-0 flex items-start justify-center pt-10 sm:pt-16"
             x-trap.noscroll="openSettings"
             x-cloak>
            <div @click="closeSettings()"
                 class="fixed inset-0 bg-black/50 dark:bg-black/70 backdrop-blur-sm transition-opacity"></div>

            <div
                class="relative bg-white dark:bg-gray-900 rounded-xl shadow-xl mx-auto my-4 sm:my-6 flex flex-col ring-1 ring-gray-900/5 dark:ring-white/10"
                style="width: 95vw; max-width: 1400px; height: 90vh;"
                @click.away="closeSettings()">
                <div
                    class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-900/50 rounded-t-xl">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white">Widget Settings</h3>
                    <button @click="closeSettings()"
                            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                             stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <style>
                    .modal-body-absolute-wrapper-custom {
                        position: absolute !important;
                        top: 1.5rem !important;
                        bottom: 1.5rem !important;
                        left: 1.5rem !important;
                        right: 1.5rem !important;
                    }

                    .flex-shrink-0 {
                        flex-shrink: 0 !important;
                    }

                    @media (min-width: 768px) {
                        .desktop-overflow-hidden {
                            overflow: hidden !important;
                        }

                        .modal-body-absolute-wrapper-custom {
                            top: 2rem !important;
                            bottom: 2rem !important;
                            left: 2rem !important;
                            right: 2rem !important;
                        }
                    }
                </style>
                <div
                    class="flex-1 bg-gray-50 dark:bg-gray-900 min-h-0 overflow-y-auto desktop-overflow-hidden relative">
                    <div class="modal-body-absolute-wrapper-custom flex flex-col md:flex-row gap-6">
                        {{-- Left Column: Global Configuration --}}
                        <div class="flex flex-col gap-6 overflow-y-auto custom-scrollbar pb-2 min-h-0"
                             style="flex: 1 1 250px; max-width: 100%; height: 100%; padding-right: 5px;">
                            {{-- Card: Date Range --}}
                            <div
                                class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm overflow-hidden flex-shrink-0">
                                <div
                                    class="flex items-center gap-2 px-6 py-3 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                         stroke-width="1.5" stroke="currentColor"
                                         class="w-4 h-4 text-gray-500 dark:text-gray-400">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                              d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/>
                                    </svg>
                                    <span
                                        class="text-xs font-bold text-gray-800 dark:text-white uppercase tracking-wider">Date Range</span>
                                </div>
                                <div class="p-6 flex flex-row items-center gap-3">
                                    <input type="date" x-model="settingsControls.date_start"
                                           :min="settingsBuilderControls.date_start || dashboardDefaults.date_start || ''"
                                           :max="settingsControls.date_end || dashboardDefaults.date_end"
                                           class="w-full text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 py-2.5 px-4 focus:ring-primary-500 focus:border-primary-500">
                                    <span class="text-gray-400 dark:text-gray-500 text-sm">â†’</span>
                                    <input type="date" x-model="settingsControls.date_end"
                                           :min="settingsControls.date_start || settingsBuilderControls.date_start || dashboardDefaults.date_start || ''"
                                           :max="dashboardDefaults.date_end"
                                           class="w-full text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 py-2.5 px-4 focus:ring-primary-500 focus:border-primary-500">
                                </div>
                            </div>

                            {{-- Card: Granularity --}}
                            <template x-if="settingsGranularityOnTheGo">
                                <div
                                    class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm overflow-hidden flex-shrink-0">
                                    <div
                                        class="flex items-center gap-2 px-6 py-3 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                             stroke-width="1.5" stroke="currentColor"
                                             class="w-4 h-4 text-gray-500 dark:text-gray-400">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                  d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        <span
                                            class="text-xs font-bold text-gray-800 dark:text-white uppercase tracking-wider">Granularity</span>
                                    </div>
                                    <div class="p-6">
                                        <select x-model="settingsControls.granularity"
                                                class="w-full text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 py-2.5 px-4 focus:ring-primary-500 focus:border-primary-500">
                                            <option value="">Dashboard Default</option>
                                            <option value="daily">Daily</option>
                                            <option value="weekly">Weekly</option>
                                            <option value="monthly">Monthly</option>
                                            <option value="quarterly">Quarterly</option>
                                            <option value="yearly">Yearly</option>
                                            <option value="query">Query (SEO)</option>
                                            <option value="dimensions.page">Page (SEO)</option>
                                            <option value="country">Country</option>
                                            <option value="device">Device</option>
                                            <option value="post">Post / Media</option>
                                        </select>
                                    </div>
                                </div>
                            </template>
                            {{-- Card: Edge Case Handling (KPI widgets only) --}}
                            <template x-if="settingsSourceType === 'kpi'">
                                <div
                                    class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm overflow-hidden flex-shrink-0">
                                    <div
                                        class="flex items-center gap-2 px-6 py-3 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                             stroke-width="1.5" stroke="currentColor"
                                             class="w-4 h-4 text-gray-500 dark:text-gray-400">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                  d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5"/>
                                        </svg>
                                        <span
                                            class="text-xs font-bold text-gray-800 dark:text-white uppercase tracking-wider">Edge Cases</span>
                                    </div>
                                    <div class="p-6 space-y-4">
                                        <label class="flex items-center gap-3 cursor-pointer">
                                            <input type="checkbox" x-model="settingsControls.edge_case_weighted"
                                                   class="w-4 h-4 rounded border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-primary-600 focus:ring-primary-500">
                                            <span class="text-sm font-medium text-gray-900 dark:text-white">Weighted regression (WLS)</span>
                                        </label>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 -mt-2">Weight each dimension
                                            value by its volume so high-volume items influence the regression line
                                            proportionally more.</p>
                                        <div>
                                            <label
                                                class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">Group
                                                low-frequency values</label>
                                            <select x-model="settingsControls.edge_case_grouping"
                                                    class="w-full text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 py-2.5 px-4 focus:ring-primary-500 focus:border-primary-500">
                                                <option value="none">No grouping</option>
                                                <option value="histogram">Auto histogram-elbow</option>
                                                <option value="percentile">Bottom percentile</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label
                                                class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">Max
                                                ratio cap</label>
                                            <input type="number" step="0.01" min="0"
                                                   x-model="settingsControls.max_ratio"
                                                   class="w-full text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 py-2.5 px-4 focus:ring-primary-500 focus:border-primary-500"
                                                   placeholder="No cap"/>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Filter out ratio
                                                values above this threshold.</p>
                                        </div>
                                        <label class="flex items-center gap-3 cursor-pointer">
                                            <input type="checkbox" x-model="settingsControls.remove_unknown"
                                                   class="w-4 h-4 rounded border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-primary-600 focus:ring-primary-500">
                                            <span class="text-sm font-medium text-gray-900 dark:text-white">Exclude unknown keyword</span>
                                        </label>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 -mt-2">Remove the <code>unknown</code> query from the chart and recalculate the regression line without it.</p>
                                    </div>
                                </div>
                            </template>
                        </div>

                        {{-- Right Column: Variables Configuration --}}
                        <div
                            class="min-w-0 min-h-0 flex overflow-x-auto gap-6 custom-scrollbar pb-2 items-stretch snap-x snap-mandatory"
                            style="flex: 2 1 500px; max-width: 100%; max-height: 100%;">
                            <template x-for="(vConfig, vKey) in settingsVariables" :key="vKey">
                                <template x-if="vConfig.metrics && Object.keys(vConfig.metrics).length > 0">
                                    <div
                                        class="flex-none w-full sm:w-[calc(50%-0.75rem)] min-w-[280px] h-full min-h-0 flex flex-col snap-start"
                                        x-init="console.log('DEBUG loop:', { vKey: vKey, options: settingsSeriesOptions[vKey]?.options, keysLength: settingsSeriesOptions[vKey]?.options ? Object.keys(settingsSeriesOptions[vKey].options).length : 0 })">
                                        <div
                                            class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm overflow-hidden flex flex-col h-full min-h-0">
                                            <div
                                                class="flex items-center justify-between px-6 py-3 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700">
                                                <div class="flex items-center gap-2">
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                         viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                                         class="w-4 h-4 text-gray-500 dark:text-gray-400">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                              d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5m.75-9l3-3 2.148 2.148A12.061 12.061 0 0116.5 7.605"/>
                                                    </svg>
                                                    <span
                                                        class="text-xs font-bold text-gray-800 dark:text-white uppercase tracking-wider"
                                                        x-text="vKey === 'dependent' ? 'Dependent Series' : (settingsSourceType === 'kpi' ? 'Independent Variable ' + (vConfig.index) : 'Series ' + (vConfig.index + 1))"></span>
                                                </div>
                                                <div class="flex flex-col items-end gap-1">
                                                    <template x-if="vConfig.channel">
                                                        <span
                                                            class="text-[10px] font-semibold text-gray-600 dark:text-gray-300 bg-gray-200 dark:bg-gray-700 px-2.5 py-1 rounded-full"
                                                            x-text="vConfig.channel_name || vConfig.channel"></span>
                                                    </template>
                                                    <template
                                                        x-if="settingsSourceType === 'kpi' && vConfig.selected_metric">
                                                        <span
                                                            class="text-[10px] font-medium text-gray-500 dark:text-gray-400"
                                                            x-text="(vConfig.metrics || {})[vConfig.selected_metric] || vConfig.selected_metric"></span>
                                                    </template>
                                                </div>
                                            </div>
                                            <div class="p-6 flex-1 flex flex-col gap-5 min-h-0">
                                                {{-- Metric selector --}}
                                                <template
                                                    x-if="settingsSourceType !== 'kpi' || !vConfig.selected_metric">
                                                    <div class="my-2">
                                                        <label
                                                            class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">Metric</label>
                                                        <select x-model="settingsControls.metrics[vConfig.index]"
                                                                class="w-full text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 py-2.5 px-4 focus:ring-primary-500 focus:border-primary-500">
                                                            <option value=""
                                                                    x-text="vKey === 'dependent' ? 'Select dependent metric...' : 'Select independent metric...'"></option>
                                                            <template x-for="(label, key) in vConfig.metrics"
                                                                      :key="key">
                                                                <option :value="key" x-text="label"
                                                                        :selected="settingsControls.metrics[vConfig.index] == key"></option>
                                                            </template>
                                                        </select>
                                                    </div>
                                                </template>

                                                {{-- Asset filter --}}
                                                <template
                                                    x-if="settingsSeriesOptions[vKey] && Object.keys(settingsSeriesOptions[vKey].options).length > 0">
                                                    <div class="gap-3 flex-1 flex flex-col min-h-0 mt-6">
                                                        <div class="flex items-center justify-between">
                                                            <label
                                                                class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">Assets</label>
                                                            <template
                                                                x-if="(settingsSeriesOptions[vKey].mode || 'multiple') === 'multiple'">
                                                                <div class="flex gap-3">
                                                                    <button @click="settingsSelectAll(vKey)"
                                                                            class="text-[11px] font-semibold text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300 hover:underline">
                                                                        Select All
                                                                    </button>
                                                                </div>
                                                            </template>
                                                        </div>
                                                        <div class="relative">
                                                            <div
                                                                class="absolute inset-y-0 left-0 w-10 flex items-center justify-center pointer-events-none">
                                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                                     viewBox="0 0 24 24" stroke-width="2"
                                                                     stroke="currentColor"
                                                                     class="w-4 h-4 text-gray-400">
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                          d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
                                                                </svg>
                                                            </div>
                                                            <input type="text" x-model="settingsSearchQueries[vKey]"
                                                                   placeholder="Search assets..."
                                                                   class="bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5"
                                                                   style="padding-left: 2.5rem;">
                                                        </div>
                                                        <div class="flex-1 relative min-h-0">
                                                            <div
                                                                class="absolute inset-0 flex flex-col gap-1 overflow-y-auto pr-1 custom-scrollbar">
                                                                <template
                                                                    x-for="[assetId, assetName] in Object.entries(settingsSeriesOptions[vKey].options)"
                                                                    :key="assetId">
                                                                    <div
                                                                        x-show="isViewAssetInGroup(vConfig.channel, assetId) && (settingsSearchQueries[vKey] === '' || assetName.toLowerCase().includes(settingsSearchQueries[vKey].toLowerCase()))"
                                                                        @click="settingsToggleAsset(vKey, assetId)"
                                                                        class="flex gap-x-3 items-center px-3 py-2.5 text-sm text-gray-700 dark:text-gray-200 rounded-lg cursor-pointer transition-colors border border-transparent"
                                                                        :class="((settingsControls.series_assets || {})[vKey] || []).includes(String(assetId)) ? 'bg-primary-50 dark:bg-primary-900/30 border-primary-100 dark:border-primary-900/50' : 'hover:bg-gray-100 dark:hover:bg-white/5'">
                                                                        <div
                                                                            class="w-4 h-4 shrink-0 flex items-center justify-center rounded border transition-colors"
                                                                            :class="((settingsControls.series_assets || {})[vKey] || []).includes(String(assetId)) ? 'bg-primary-600 border-primary-600' : 'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800'">
                                                                            <svg
                                                                                x-show="((settingsControls.series_assets || {})[vKey] || []).includes(String(assetId))"
                                                                                class="w-3 h-3 text-white" fill="none"
                                                                                viewBox="0 0 24 24" stroke-width="3"
                                                                                stroke="currentColor">
                                                                                <path stroke-linecap="round"
                                                                                      stroke-linejoin="round"
                                                                                      d="m4.5 12.75 6 6 9-13.5"/>
                                                                            </svg>
                                                                        </div>
                                                                        <span class="truncate font-medium"
                                                                              :class="((settingsControls.series_assets || {})[vKey] || []).includes(String(assetId)) ? 'text-primary-800 dark:text-primary-200' : ''"
                                                                              x-text="assetName"></span>
                                                                    </div>
                                                                </template>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </template>
                        </div>
                    </div>
                </div>

                <div
                    class="flex items-center justify-end gap-3 p-6 sm:p-6 border-t border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 rounded-b-xl">
                    <button @click="closeSettings()"
                            class="text-sm font-semibold text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-gray-100 px-6 py-2.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors border border-transparent">
                        Cancel
                    </button>
                    <button @click="saveSettings()"
                            class="text-sm font-semibold text-white bg-primary-600 hover:bg-primary-500 px-6 py-2.5 rounded-lg shadow-sm transition-colors border border-transparent">
                        Update Settings
                    </button>
                </div>
            </div>
        </div>
    </template>
</div>

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/gridstack@12.6.0/dist/gridstack-all.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/gridstack@12.6.0/dist/gridstack.min.css"/>
    <script
        src="{{ asset('js/dashboard-renderer.js') }}?v={{ filemtime(public_path('js/dashboard-renderer.js')) }}"></script>
    <script>
        window.dashboardView = function () {
            return {
                loadedCount: 0,
                totalCount: {{ count($this->widgets) }},
                tenant: '{{ \Filament\Facades\Filament::getTenant()?->subdomain ?? '' }}',
                dashboardDefaults: {
                    date_start: '{{ $dc['date_start'] ?? '' }}',
                    date_end: '{{ !empty($dc['date_end']) ? $dc['date_end'] : date('Y-m-d', strtotime('-1 day')) }}',
                    zero_handling: '{{ !empty($dc['zero_handling']) ? $dc['zero_handling'] : 'remove' }}',
                    show_asset_group_selector: {{ !empty($dc['show_asset_group_selector']) ? 'true' : 'false' }},
                },
                dashboardOverrides: {
                    date_start: '{{ $dc['date_start'] ?? '' }}',
                    date_end: '{{ !empty($dc['date_end']) ? $dc['date_end'] : date('Y-m-d', strtotime('-1 day')) }}',
                    zero_handling: '{{ !empty($dc['zero_handling']) ? $dc['zero_handling'] : 'remove' }}',
                },
                hasUserChangedGlobalDate: false,
                assetGroups: @json($this->getAllAssetGroups()),
                channelAssetGroupMap: @json($this->getChannelAssetGroupMap()),
                selectedAssetGroup: '{{ $dc['asset_group'] ?? '' }}',
                _dashboardConfiguredGroup: '{{ $dc['asset_group'] ?? '' }}',
                init() {
                    const groupKeys = Object.keys(this.assetGroups || {});
                    if (!this.selectedAssetGroup && groupKeys.length > 0) {
                        this.selectedAssetGroup = groupKeys[0];
                    }
                    this.$nextTick(() => {
                        // Only apply asset group on init if the dashboard had no configured group.
                        // When a group was configured, PHP already filtered widget data server-side.
                        if (this.selectedAssetGroup && !this._dashboardConfiguredGroup) {
                            this.applyAssetGroup();
                        }
                        const tryInit = () => {
                            if (typeof GridStack !== 'undefined') {
                                GridStack.init({
                                    staticGrid: true,
                                    column: 12,
                                    cellHeight: 100,
                                    margin: 12,
                                    minRow: 6
                                }, '#view-grid-stack');
                            } else {
                                setTimeout(tryInit, 50);
                            }
                        };
                        tryInit();
                    });
                },

                applyDateRange() {
                    this.hasUserChangedGlobalDate = true;
                    this.refreshAll();
                },

                // ─── Asset Group ───

                isViewAssetInGroup(channel, assetId) {
                    if (!this.selectedAssetGroup || !this.channelAssetGroupMap[channel]) return true;
                    const groupAssets = this.channelAssetGroupMap[channel][this.selectedAssetGroup];
                    if (!groupAssets) return true;
                    return groupAssets.map(String).includes(String(assetId));
                },

                applyAssetGroup() {
                    // Case 1: Settings modal is open — filter assets in the modal and reload preview
                    if (this.openSettings) {
                        for (const vKey in this.settingsSeriesOptions) {
                            const channel = this.settingsVariables[vKey]?.channel;
                            if (!channel) continue;
                            const currentAssets = this.settingsControls.series_assets[vKey] || [];
                            if (currentAssets.length === 0) continue;

                            const groupId = this.selectedAssetGroup;
                            if (!groupId || !this.channelAssetGroupMap[channel]?.[groupId]) continue;

                            const allowedAssets = this.channelAssetGroupMap[channel][groupId].map(String);
                            const validAssets = currentAssets.filter(a => allowedAssets.includes(String(a)));

                            if (validAssets.length === 0 && allowedAssets.length > 0) {
                                this.settingsControls.series_assets = {
                                    ...this.settingsControls.series_assets,
                                    [vKey]: [allowedAssets[0]]
                                };
                            }
                        }

                        const widgetId = this.settingsWidgetId;
                        const controls = this.settingsControls;
                        window.dispatchEvent(new CustomEvent('reload-widget', {
                            detail: {id: widgetId, controls: controls}
                        }));
                        this.reloadWidget(widgetId, controls);
                        return;
                    }

                    // Case 2: Main dashboard — filter and reload all rendered widgets
                    const widgets = document.querySelectorAll('.grid-stack-item-content .widget-content');
                    widgets.forEach(el => {
                        const widgetItem = el.closest('.grid-stack-item');
                        if (!widgetItem) return;
                        const widgetId = widgetItem.getAttribute('gs-id');
                        const rawControls = el.getAttribute('data-raw-controls');
                        if (!rawControls) return;

                        try {
                            const controls = JSON.parse(rawControls);

                            const headerEl = el.closest('.grid-stack-item-content')?.querySelector('[data-variables]');
                            let variables = {};
                            if (headerEl) {
                                try {
                                    variables = JSON.parse(headerEl.getAttribute('data-variables') || '{}');
                                } catch (e) {
                                }
                            }

                            let changed = false;

                            for (const vKey in controls.series_assets || {}) {
                                const channel = variables[vKey]?.channel;
                                if (!channel) continue;
                                const currentAssets = controls.series_assets[vKey] || [];
                                if (currentAssets.length === 0) continue;

                                const groupId = this.selectedAssetGroup;
                                if (!groupId || !this.channelAssetGroupMap[channel]?.[groupId]) continue;

                                const allowedAssets = this.channelAssetGroupMap[channel][groupId].map(String);
                                const validAssets = currentAssets.filter(a => allowedAssets.includes(String(a)));

                                let newAssets;
                                if (validAssets.length > 0) {
                                    newAssets = validAssets;
                                } else if (allowedAssets.length > 0) {
                                    newAssets = [allowedAssets[0]];
                                } else {
                                    continue;
                                }

                                if (JSON.stringify(newAssets) !== JSON.stringify(currentAssets)) {
                                    controls.series_assets[vKey] = newAssets;
                                    changed = true;
                                }
                            }

                            if (changed) {
                                el.setAttribute('data-raw-controls', JSON.stringify(controls));
                                this.reloadWidget(parseInt(widgetId), controls);
                            }
                        } catch (e) {
                        }
                    });
                },

                renderWidget(widgetId, el, controls) {
                    el.setAttribute('data-raw-controls', JSON.stringify(controls));
                    let effectiveControls = {...controls};

                    if (this.hasUserChangedGlobalDate) {
                        if (this.dashboardOverrides.date_start) effectiveControls.date_start = this.dashboardOverrides.date_start;
                        if (this.dashboardOverrides.date_end) effectiveControls.date_end = this.dashboardOverrides.date_end;
                    } else {
                        if (!effectiveControls.date_start && this.dashboardDefaults.date_start) effectiveControls.date_start = this.dashboardDefaults.date_start;
                        if (!effectiveControls.date_end && this.dashboardDefaults.date_end) effectiveControls.date_end = this.dashboardDefaults.date_end;
                    }

                    return new Promise(resolve => {
                        const tryRender = () => {
                            if (window.dashboardRenderer) {
                                window.dashboardRenderer.renderWidget(widgetId, el, effectiveControls, this.tenant)
                                    .then(() => {
                                        this.loadedCount++;
                                        resolve();
                                    })
                                    .catch(() => {
                                        this.loadedCount++;
                                        resolve();
                                    });
                            } else {
                                setTimeout(tryRender, 50);
                            }
                        };
                        tryRender();
                    });
                },

                refreshAll() {
                    this.loadedCount = 0;
                    const widgets = document.querySelectorAll('.grid-stack-item-content .widget-content');
                    widgets.forEach(el => {
                        el.innerHTML = '';
                        const widgetId = el.closest('.grid-stack-item').getAttribute('gs-id');
                        const rawControls = el.getAttribute('data-raw-controls');
                        if (rawControls) {
                            try {
                                const controls = JSON.parse(rawControls);
                                if (this.hasUserChangedGlobalDate) {
                                    if (this.dashboardOverrides.date_start) controls.date_start = this.dashboardOverrides.date_start;
                                    if (this.dashboardOverrides.date_end) controls.date_end = this.dashboardOverrides.date_end;
                                } else {
                                    if (!controls.date_start && this.dashboardDefaults.date_start) controls.date_start = this.dashboardDefaults.date_start;
                                    if (!controls.date_end && this.dashboardDefaults.date_end) controls.date_end = this.dashboardDefaults.date_end;
                                }
                                window.dispatchEvent(new CustomEvent('reload-widget', {
                                    detail: {
                                        id: parseInt(widgetId),
                                        controls: controls
                                    }
                                }));
                                // reload-widget listener in widgetHeader already updates controls, but we also pass it directly to renderWidget
                                this.renderWidget(widgetId, el, controls);
                            } catch (e) {
                            }
                        } else {
                            location.reload();
                        }
                    });
                },

                reloadWidget(widgetId, controls) {
                    const widgetItem = document.querySelector(`.grid-stack-item[gs-id="${widgetId}"]`);
                    if (!widgetItem) return Promise.resolve();
                    const el = widgetItem.querySelector('.widget-content');
                    if (!el) return Promise.resolve();
                    el.innerHTML = '';
                    if (this.loadedCount > 0) this.loadedCount--;
                    // Sync controls back to the widget header Alpine component
                    window.dispatchEvent(new CustomEvent('reload-widget', {
                        detail: { id: widgetId, controls: controls }
                    }));
                    const result = this.renderWidget(widgetId, el, controls) || Promise.resolve();
                    if (this.popOutActive && this.popOutWidgetId === widgetId) {
                        Promise.resolve(result).then(() => this.$nextTick(() => this.syncPopOutBadges()));
                    }
                    return result;
                },

                settingsWidgetId: null,
                settingsBuilderControls: {date_start: '', date_end: ''},
                settingsOriginalControls: {
                    date_start: '',
                    date_end: '',
                    granularity: '',
                    metrics: [],
                    series_assets: {}
                },
                settingsControls: {date_start: '', date_end: '', granularity: '', metrics: [], series_assets: {}},
                settingsSeriesOptions: {},
                settingsVariables: {},
                settingsGranularityOnTheGo: false,
                settingsSourceType: '',
                openSettings: false,
                settingsSearchQueries: {},

                openWidgetSettings(widgetId, controls, builderControls, seriesOptions, variables, granularityOnTheGo, sourceType) {
                    this.settingsWidgetId = widgetId;
                    this.settingsBuilderControls = JSON.parse(JSON.stringify(builderControls || {}));
                    this.settingsOriginalControls = JSON.parse(JSON.stringify(controls || {}));
                    this.settingsControls = controls || {};
                    if (!this.settingsControls.metrics) this.settingsControls.metrics = [];
                    if (!this.settingsControls.series_assets) this.settingsControls.series_assets = {};
                    this.settingsSeriesOptions = seriesOptions || {};
                    this.settingsVariables = variables || {};
                    this.settingsGranularityOnTheGo = granularityOnTheGo;
                    this.settingsSourceType = sourceType || '';
                    this.settingsSearchQueries = {};
                    for (const key in seriesOptions) {
                        this.settingsSearchQueries[key] = '';
                    }
                    for (const key in variables) {
                        if (!this.settingsSearchQueries[key]) this.settingsSearchQueries[key] = '';
                    }

                    // Apply global asset group filtering to preselected assets
                    if (this.selectedAssetGroup) {
                        for (const vKey in this.settingsVariables) {
                            const channel = this.settingsVariables[vKey]?.channel;
                            if (!channel) continue;
                            const currentAssets = this.settingsControls.series_assets[vKey] || [];
                            if (currentAssets.length === 0) continue;
                            this.settingsControls.series_assets = {
                                ...this.settingsControls.series_assets,
                                [vKey]: this.ensureValidAssets(vKey, channel, currentAssets)
                            };
                        }
                    }

                    this.openSettings = true;
                },

                closeSettings() {
                    this.openSettings = false;
                    this.settingsWidgetId = null;
                    this.settingsOriginalControls = {
                        date_start: '',
                        date_end: '',
                        granularity: '',
                        metrics: [],
                        series_assets: {}
                    };
                    this.settingsControls = {
                        date_start: '',
                        date_end: '',
                        granularity: '',
                        metrics: [],
                        series_assets: {}
                    };
                    this.settingsVariables = {};
                    this.settingsSeriesOptions = {};
                },

                saveSettings() {
                    const widgetId = this.settingsWidgetId;
                    const controls = this.settingsControls;

                    let dateAdjusted = false;
                    let minStart = this.settingsBuilderControls.date_start || this.dashboardDefaults.date_start || '';
                    let maxEnd = this.settingsBuilderControls.date_end || this.dashboardDefaults.date_end;

                    if (controls.date_start && minStart && controls.date_start < minStart) {
                        controls.date_start = minStart;
                        dateAdjusted = true;
                    }
                    if (controls.date_end && controls.date_end > maxEnd) {
                        controls.date_end = maxEnd;
                        dateAdjusted = true;
                    }

                    if (dateAdjusted) {
                        alert("Warning: The customized date range exceeded the allowed limits and was adjusted to comply.");
                    }

                    window.dispatchEvent(new CustomEvent('reload-widget', {
                        detail: {
                            id: widgetId,
                            controls: controls
                        }
                    }));
                    const reloadPromise = this.reloadWidget(widgetId, controls);
                    if (this.popOutActive && this.popOutWidgetId === widgetId) {
                        Promise.resolve(reloadPromise).then(() => {
                            this.$nextTick(() => {
                                this.syncPopOutBadges();
                                const renderer = window.dashboardRenderer;
                                if (!renderer) return;
                                const target = this.$refs.popOutContent;
                                if (!target) return;
                                const contentEl = document.querySelector(`.grid-stack-item[gs-id="${widgetId}"] .widget-content`);
                                if (!contentEl) return;
                                renderer.popOutWidget(contentEl, target);
                            });
                        });
                    }
                    this.closeSettings();
                },

                settingsToggleAsset(seriesKey, assetId) {
                    const mode = this.settingsSeriesOptions[seriesKey].mode || 'multiple';
                    const current = this.settingsControls.series_assets[seriesKey] || [];
                    let next;

                    if (mode === 'single') {
                        next = [String(assetId)];
                    } else {
                        const idx = current.indexOf(String(assetId));
                        if (idx > -1) {
                            if (current.length <= 1) {
                                // Prevent unselecting the last asset
                                return;
                            }
                            next = current.filter((_, i) => i !== idx);
                        } else {
                            next = [...current, String(assetId)];
                        }
                    }

                    this.settingsControls.series_assets = {
                        ...this.settingsControls.series_assets,
                        [seriesKey]: next
                    };
                },

                settingsSelectAll(seriesKey) {
                    const allIds = Object.keys(this.settingsSeriesOptions[seriesKey].options).map(String);
                    const channel = this.settingsVariables[seriesKey]?.channel;
                    let validIds = allIds;
                    if (this.selectedAssetGroup && channel && this.channelAssetGroupMap[channel]?.[this.selectedAssetGroup]) {
                        const groupAssets = this.channelAssetGroupMap[channel][this.selectedAssetGroup].map(String);
                        validIds = allIds.filter(id => groupAssets.includes(id));
                    }
                    this.settingsControls.series_assets = {
                        ...this.settingsControls.series_assets,
                        [seriesKey]: validIds
                    };
                },

                // ─── Compatibility aliases for asset group filtering ───
                isAssetAllowedByGroups(seriesKey, channel, assetId) {
                    if (!this.selectedAssetGroup || !channel || !this.channelAssetGroupMap[channel]) return true;
                    const groupAssets = this.channelAssetGroupMap[channel][this.selectedAssetGroup];
                    if (!groupAssets) return true;
                    return groupAssets.map(String).includes(String(assetId));
                },

                ensureValidAssets(seriesKey, channel, selectedAssets) {
                    if (!this.selectedAssetGroup || !channel || !this.channelAssetGroupMap[channel]) return selectedAssets;
                    const groupAssets = this.channelAssetGroupMap[channel][this.selectedAssetGroup];
                    if (!groupAssets) return selectedAssets;
                    const allowedAssets = groupAssets.map(String);
                    const validAssets = (selectedAssets || []).filter(a => allowedAssets.includes(String(a)));
                    if (validAssets.length > 0) return validAssets;
                    if (allowedAssets.length > 0) return [allowedAssets[0]];
                    return [];
                },

                // ─── Fullscreen Pop-Out ───
                popOutActive: false,
                popOutTitle: '',
                popOutBadges: [],
                popOutWidgetId: null,

                syncPopOutBadges() {
                    if (!this.popOutWidgetId) { this.popOutBadges = []; return; }
                    const widgetEl = document.querySelector(`.grid-stack-item[gs-id="${this.popOutWidgetId}"]`);
                    const headerDataEl = widgetEl?.querySelector('[data-widget-id]');
                    if (headerDataEl && window.Alpine) {
                        try {
                            const data = window.Alpine.$data(headerDataEl);
                            if (data && typeof data.getBadges === 'function') {
                                this.popOutBadges = data.getBadges();
                                return;
                            }
                        } catch (e) {}
                    }
                    this.popOutBadges = [];
                },

                openPopOut(widgetId) {
                    if (this._popOutAnimating) return;

                    const widgetEl = document.querySelector(`.grid-stack-item[gs-id="${widgetId}"]`);
                    const headerEl = widgetEl?.querySelector('.grid-stack-item-content');
                    const title = headerEl?.querySelector('h3')?.textContent?.trim() || '';
                    const rect = widgetEl?.getBoundingClientRect();

                    this.popOutTitle = title;
                    this.popOutWidgetId = widgetId;
                    this.syncPopOutBadges();
                    this._popFromRect = rect;
                    this._popOutAnimating = true;
                    this.popOutActive = true;

                    this.$nextTick(() => {
                        const card = this.$refs.popOutCard;
                        if (card && rect) {
                            const cardRect = card.getBoundingClientRect();
                            const originX = ((rect.left + rect.width / 2) - cardRect.left) / cardRect.width * 100;
                            const originY = ((rect.top + rect.height / 2) - cardRect.top) / cardRect.height * 100;

                            card.style.transition = 'none';
                            card.style.transformOrigin = `${originX}% ${originY}%`;
                            card.style.transform = 'scale(0.25)';
                            card.style.opacity = '0';

                            void card.offsetHeight;

                            card.style.transition = 'transform 0.4s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.4s ease-out';
                            card.style.transform = 'scale(1)';
                            card.style.opacity = '1';

                            card.addEventListener('transitionend', () => {
                                this._popOutAnimating = false;
                                card.style.transition = '';
                                card.style.transform = '';
                                card.style.opacity = '';

                                const renderer = window.dashboardRenderer;
                                if (!renderer) return;
                                const target = this.$refs.popOutContent;
                                if (!target) return;
                                const contentEl = document.querySelector(`.grid-stack-item[gs-id="${widgetId}"] .widget-content`);
                                if (!contentEl) return;
                                renderer.popOutWidget(contentEl, target);
                            }, {once: true});
                        } else {
                            const renderer = window.dashboardRenderer;
                            if (!renderer) return;
                            const target = this.$refs.popOutContent;
                            if (!target) return;
                            const contentEl = document.querySelector(`.grid-stack-item[gs-id="${widgetId}"] .widget-content`);
                            if (!contentEl) return;
                            renderer.popOutWidget(contentEl, target);
                        }
                    });
                },

                closePopOut() {
                    if (this._popOutAnimating) return;

                    const widgetId = this.popOutWidgetId;

                    if (widgetId) {
                        const widgetEl = document.querySelector(`.grid-stack-item[gs-id="${widgetId}"]`);
                        if (widgetEl) this._popFromRect = widgetEl.getBoundingClientRect();

                        const contentEl = widgetEl?.querySelector('.widget-content');
                        const renderer = window.dashboardRenderer;
                        if (renderer && contentEl) {
                            const target = this.$refs.popOutContent;
                            if (target) {
                                renderer.popInWidget(contentEl, target);
                            }
                        }
                    }

                    const card = this.$refs.popOutCard;
                    if (card) {
                        this._popOutAnimating = true;
                        if (this._popFromRect) {
                            const cardRect = card.getBoundingClientRect();
                            const originX = ((this._popFromRect.left + this._popFromRect.width / 2) - cardRect.left) / cardRect.width * 100;
                            const originY = ((this._popFromRect.top + this._popFromRect.height / 2) - cardRect.top) / cardRect.height * 100;
                            card.style.transformOrigin = `${originX}% ${originY}%`;
                        }
                        card.style.transition = 'transform 0.4s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.4s ease-out';
                        card.style.transform = 'scale(0.25)';
                        card.style.opacity = '0';

                        card.addEventListener('transitionend', () => {
                            this._popFromRect = null;
                            this._popOutAnimating = false;
                            this.popOutActive = false;
                            this.popOutWidgetId = null;
                            this.popOutTitle = '';
                        }, {once: true});
                    } else {
                        this._popFromRect = null;
                        this.popOutActive = false;
                        this.popOutWidgetId = null;
                        this.popOutTitle = '';
                    }
                },

                openModalSettings() {
                    const headerEl = document.querySelector(`[data-widget-id="${this.popOutWidgetId}"]`);
                    if (!headerEl || !window.Alpine) return;
                    const data = Alpine.$data(headerEl);
                    window.dispatchEvent(new CustomEvent('open-widget-settings', {
                        detail: {
                            widgetId: this.popOutWidgetId,
                            builderControls: JSON.parse(JSON.stringify(data.builderControls || {})),
                            controls: JSON.parse(JSON.stringify(data.controls || {})),
                            seriesOptions: JSON.parse(JSON.stringify(data.seriesOptions || {})),
                            variables: JSON.parse(JSON.stringify(data.variables || {})),
                            granularityOnTheGo: data.granularityOnTheGo,
                            sourceType: data.sourceType
                        }
                    }));
                }
            };
        };

        window.widgetHeader = function () {
            return {
                widgetId: null,
                controls: {},
                seriesOptions: {},
                metricOptions: {},
                variables: {},
                sourceType: '',
                searchQueries: {},

                init() {
                    const el = this.$el;
                    this.widgetId = parseInt(el.dataset.widgetId);
                    this.builderControls = JSON.parse(el.dataset.controls || '{}');
                    this.controls = JSON.parse(el.dataset.controls || '{}');
                    this.seriesOptions = JSON.parse(el.dataset.seriesOptions || '{}');
                    this.metricOptions = JSON.parse(el.dataset.metricOptions || '{}');
                    this.sourceType = el.dataset.sourceType || '';
                    this.variables = JSON.parse(el.dataset.variables || '{}');
                    if (!this.controls.metrics) this.controls.metrics = [];
                    if (!Array.isArray(this.controls.metrics)) {
                        this.controls.metrics = Object.values(this.controls.metrics);
                    }
                    if (!this.controls.series_assets) this.controls.series_assets = {};
                    const varCount = Object.keys(this.variables).length;
                    while (this.controls.metrics.length < varCount) {
                        this.controls.metrics.push('');
                    }
                    if (this.controls.assets && this.controls.assets.length > 0) {
                        const firstKey = this.sourceType === 'kpi' ? 'dependent' : '0';
                        if (!this.controls.series_assets[firstKey]) {
                            const validIds = this.seriesOptions[firstKey]
                                ? Object.keys(this.seriesOptions[firstKey].options)
                                : [];
                            this.controls.series_assets[firstKey] = this.controls.assets
                                .map(String)
                                .filter(id => validIds.includes(id));
                        }
                    }
                    this.granularityOnTheGo = !this.controls.granularity || this.controls.granularity === '';
                    if (!this.controls.granularity) this.controls.granularity = '';
                    for (const key in this.seriesOptions) {
                        this.searchQueries[key] = '';
                    }
                    for (const key in this.variables) {
                        if (!this.searchQueries[key]) this.searchQueries[key] = '';
                    }
                },

                openDashboardSettings() {
                    window.dispatchEvent(new CustomEvent('open-widget-settings', {
                        detail: {
                            widgetId: this.widgetId,
                            builderControls: JSON.parse(JSON.stringify(this.builderControls)),
                            controls: JSON.parse(JSON.stringify(this.controls)),
                            seriesOptions: JSON.parse(JSON.stringify(this.seriesOptions)),
                            variables: JSON.parse(JSON.stringify(this.variables)),
                            granularityOnTheGo: this.granularityOnTheGo,
                            sourceType: this.sourceType
                        }
                    }));
                },

                isSelected(seriesKey, assetId) {
                    if (!this.controls.series_assets[seriesKey]) return false;
                    return this.controls.series_assets[seriesKey].includes(String(assetId));
                },

                toggleAsset(seriesKey, assetId) {
                    const current = this.controls.series_assets[seriesKey] || [];
                    const idx = current.indexOf(String(assetId));
                    let next;
                    if (idx > -1) {
                        if (current.length <= 1) return; // Prevent unselecting the last asset
                        next = current.filter((_, i) => i !== idx);
                    } else {
                        next = [...current, String(assetId)];
                    }
                    this.controls.series_assets[seriesKey] = next;
                },

                selectAll(seriesKey) {
                    const allIds = Object.keys(this.seriesOptions[seriesKey].options).map(String);
                    this.controls.series_assets[seriesKey] = allIds;
                },

                getActiveFilterCount() {
                    let count = 0;
                    for (const key in this.seriesOptions) {
                        if (this.controls.series_assets[key] && this.controls.series_assets[key].length > 0 && this.controls.series_assets[key].length < Object.keys(this.seriesOptions[key].options).length) {
                            count++;
                        }
                    }
                    return count;
                },

                updateWidget() {
                    const raw = JSON.stringify(this.controls);
                    const el = document.querySelector(`.grid-stack-item[gs-id="${this.widgetId}"] .widget-content`);
                    if (el) {
                        el.setAttribute('data-raw-controls', raw);
                    }
                    const dbView = document.getElementById('dashboard-view-container');
                    if (dbView && dbView.__x && dbView.__x.getUnobservedData()) {
                        dbView.__x.getUnobservedData().reloadWidget(this.widgetId, this.controls);
                    }
                },

                getBadges() {
                    let badges = [];
                    for (const [key, vConfig] of Object.entries(this.variables)) {
                        const metricKey = this.controls.metrics?.[vConfig.index];
                        const metricLabel = metricKey && vConfig.metrics?.[metricKey]
                            ? vConfig.metrics[metricKey] : metricKey || '';
                        const channelName = vConfig.channel_name || '';
                        const assetText = this._getAssetText(key);
                        let text = metricLabel;
                        if (assetText && assetText !== 'All') {
                            text += ' · ' + assetText;
                        }
                        if (text) {
                            badges.push({label: channelName, text: text});
                        }
                    }
                    if (badges.length === 0) {
                        for (const [key, data] of Object.entries(this.seriesOptions)) {
                            const selected = this.controls.series_assets[key] || [];
                            let label = data.label.replace(/ \(.+\)/, '');
                            let text = this._getAssetText(key);
                            if (text) badges.push({label, text});
                        }
                    }
                    return badges;
                },

                _getAssetText(key) {
                    const data = this.seriesOptions[key];
                    if (!data) return '';
                    const selected = this.controls.series_assets[key] || [];
                    if (selected.length === 0 || selected.length === Object.keys(data.options).length) return '';
                    const names = selected.map(id => data.options[id]).filter(Boolean);
                    if (names.length <= 2) return names.join(', ');
                    return names.slice(0, 2).join(', ') + ' +' + (names.length - 2) + ' more';
                }
            };
        };
    </script>
@endpush
