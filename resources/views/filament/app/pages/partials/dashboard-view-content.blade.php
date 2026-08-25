<link rel="stylesheet" href="{{ asset('css/dashboard-builder.css') }}"/>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/gridstack@12.6.0/dist/gridstack.min.css"/>
<script src="https://cdn.jsdelivr.net/npm/gridstack@12.6.0/dist/gridstack-all.min.js"></script>
<script src="{{ asset('js/dashboard-renderer.js') }}?v={{ file_exists(public_path('js/dashboard-renderer.js')) ? filemtime(public_path('js/dashboard-renderer.js')) : time() }}"></script>
@php
    $viewObj = $viewObj ?? $viewModel ?? null;
    $dc = $viewObj->dashboard->controls ?? [];
    $allGroups = $viewObj->getAllAssetGroups();
    $rawDcGroups = (array) ($dc['asset_group'] ?? []);
    $rawDcGroups = array_values(array_filter(array_map('strval', $rawDcGroups)));

    $dashboardGroup = '';
    if (!empty($rawDcGroups)) {
        foreach ($rawDcGroups as $gId) {
            if (array_key_exists($gId, $allGroups)) {
                $dashboardGroup = (string) $gId;
                break;
            }
        }
    }
    if ($dashboardGroup === '' && !empty($allGroups)) {
        $dashboardGroup = (string) array_key_first($allGroups);
    }
    $yesterdayDate = date('Y-m-d', strtotime('-1 day'));

    // Determine if PDF Export / Print is allowed for this view context:
    $canExportPdf = false;
    if ($viewObj instanceof \App\Http\Controllers\PublicDashboardViewModel) {
        // Public View level:
        $canExportPdf = !empty($viewObj->pv?->allow_pdf_export);
    } else {
        // Internal / Dashboard level:
        $dashAllowPdf = !empty($dc['allow_pdf_export']);
        if ($dashAllowPdf) {
            $user = auth()->user();
            if ($user) {
                $project = \Filament\Facades\Filament::getTenant() ?? ($viewObj->project ?? null) ?? ($viewObj->dashboard->project ?? null);
                if ($project) {
                    $userRoles = \Illuminate\Support\Facades\DB::table('model_has_roles')
                        ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                        ->where('model_has_roles.model_id', $user->id)
                        ->where('model_has_roles.project_id', $project->id)
                        ->pluck('roles.name')
                        ->toArray();

                    // Owner and Editor can always print if dashboard PDF export is enabled
                    if (in_array('project_owner', $userRoles) || in_array('project_editor', $userRoles)) {
                        $canExportPdf = true;
                    } else {
                        $configuredRoles = (array) ($dc['pdf_export_roles'] ?? []);
                        if (!empty(array_intersect($userRoles, $configuredRoles))) {
                            $canExportPdf = true;
                        }
                    }
                }
            }
        }
    }
@endphp

<div x-data="dashboardView({
    totalCount: {{ count($viewObj->widgets) }},
    tenant: '{{ \Filament\Facades\Filament::getTenant()?->subdomain ?? ($viewObj->project->subdomain ?? '') }}',
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
    assetGroups: @js($viewObj->getAllAssetGroups()),
    channelAssetGroupMap: @js($viewObj->getChannelAssetGroupMap()),
    selectedAssetGroup: '{{ $dashboardGroup }}'
})" x-init="init()" id="dashboard-view-container" class="space-y-4"
     @open-widget-settings.window="openWidgetSettings($event.detail.widgetId, $event.detail.controls, $event.detail.builderControls, $event.detail.seriesOptions, $event.detail.variables, $event.detail.granularityOnTheGo, $event.detail.sourceType)"
     @open-pop-out.window="openPopOut($event.detail.widgetId)">
    {{-- Header --}}
    <div class="flex items-center justify-between gap-4 rounded-xl bg-gray-50 dark:bg-gray-900 p-4 no-print">
        <div>
            <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ $viewObj->dashboard->name }}</h1>
            @if ($viewObj->dashboard->description)
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $viewObj->dashboard->description }}</p>
            @endif
        </div>
        <div class="flex items-center gap-3 no-print">
            <span class="text-xs text-gray-500 dark:text-gray-400 font-medium">
                <span x-text="loadedCount"></span>/<span x-text="totalCount"></span> {{ __('loaded') }}
            </span>
            <button x-on:click="refreshAll()"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 text-xs font-semibold shadow-sm transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3.5 h-3.5 text-gray-500 dark:text-gray-400">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                </svg>
                <span>{{ __('Refresh all') }}</span>
            </button>
            @if ($canExportPdf)
                <button x-on:click="exportPdf()"
                        :disabled="isExporting"
                        title="{{ __('Export to PDF / Print') }}"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 text-xs font-semibold shadow-sm transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500 disabled:opacity-50 disabled:cursor-not-allowed">
                    <template x-if="!isExporting">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3.5 h-3.5 text-gray-500 dark:text-gray-400">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5Zm-3 0h.008v.008H15V10.5Z" />
                        </svg>
                    </template>
                    <template x-if="isExporting">
                        <svg class="animate-spin w-3.5 h-3.5 text-primary-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                    </template>
                    <span x-text="isExporting ? '{{ __('Preparing PDF...') }}' : '{{ __('Export PDF') }}'"></span>
                </button>
            @endif
        </div>
    </div>

    {{-- Print-Only Summary Header --}}
    <div class="print-only-header hidden">
        <div class="flex items-center justify-between gap-4 border-b pb-3 mb-4 border-gray-300 dark:border-gray-700">
            <div>
                <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ $viewObj->dashboard->name }}</h1>
                @if ($viewObj->dashboard->description)
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $viewObj->dashboard->description }}</p>
                @endif
            </div>
            <div class="flex items-center gap-4 text-xs text-gray-600 dark:text-gray-300">
                <div x-show="dashboardOverrides.date_start || dashboardOverrides.date_end">
                    <span class="font-semibold text-gray-500 dark:text-gray-400">{{ __('Date Range:') }}</span>
                    <span x-text="(dashboardOverrides.date_start || '...') + ' → ' + (dashboardOverrides.date_end || '...')"></span>
                </div>
                <div x-show="selectedAssetGroup">
                    <span class="font-semibold text-gray-500 dark:text-gray-400">{{ __('Asset Group:') }}</span>
                    <span x-text="assetGroups[selectedAssetGroup] || selectedAssetGroup"></span>
                </div>
            </div>
        </div>
    </div>

    {{-- Dashboard Controls (on-the-go) --}}
    <div class="dvc-controls-bar flex flex-wrap items-center gap-3 text-xs py-3 px-3">
        <span class="text-xs text-gray-500 dark:text-gray-400 font-medium hidden sm:inline">{{ __('Date range:') }}</span>
        <x-ui.date-input size="xs" x-model="dashboardOverrides.date_start"
                         x-on:change.debounce.500ms="applyDateRange()"
                         x-bind:max="dashboardOverrides.date_end || '{{ $yesterdayDate }}'" />
        <span class="text-gray-400">→</span>
        <x-ui.date-input size="xs" x-model="dashboardOverrides.date_end"
                         x-on:change.debounce.500ms="applyDateRange()"
                         x-bind:min="dashboardOverrides.date_start || ''" x-bind:max="dashboardDefaults.date_end" />
        <template x-if="dashboardDefaults.show_asset_group_selector">
            <span class="text-xs text-gray-500 dark:text-gray-400 font-medium ml-2 hidden sm:inline">{{ __('Asset Group:') }}</span>
        </template>
        <template x-if="dashboardDefaults.show_asset_group_selector">
            <x-ui.asset-selector model="selectedAssetGroup" options="assetGroups" changeEvent="applyAssetGroup()" size="xs" />
        </template>
    </div>

    {{-- Grid --}}
    <div id="view-grid-stack" class="grid-stack relative" wire:ignore>
        @php
            // 1. Find all natural boundaries where no widget is crossed vertically
            $cutCandidates = collect($viewObj->widgets)->map(fn($w) => ($w['grid_y'] ?? 0) + ($w['grid_h'] ?? 1))->unique()->sort()->values();
            $naturalCuts = [];
            foreach ($cutCandidates as $cutY) {
                $crosses = collect($viewObj->widgets)->contains(fn($w) => ($w['grid_y'] ?? 0) < $cutY && (($w['grid_y'] ?? 0) + ($w['grid_h'] ?? 1)) > $cutY);
                if (!$crosses) {
                    $naturalCuts[] = $cutY;
                }
            }

            // 2. Filter empty lines using the 7-row budget (6 for page 1) so only true page breaks between widgets are generated
            $emptyCutLines = [];
            $currentPageStart = 0;
            $isFirstPage = true;

            for ($i = 0; $i < count($naturalCuts) - 1; $i++) {
                $currentCut = $naturalCuts[$i];
                $nextCut = $naturalCuts[$i + 1];
                $maxAllowedRows = $isFirstPage ? 6 : 7;

                // Check if the next segment would exceed the page row budget from the current page start
                if (($nextCut - $currentPageStart) > $maxAllowedRows) {
                    $emptyCutLines[] = $currentCut;
                    $currentPageStart = $currentCut;
                    $isFirstPage = false;
                }
            }

            $insertedCuts = [];
        @endphp

        @foreach ($viewObj->widgets as $wIndex => $widget)
            <div class="grid-stack-item"
                 gs-id="{{ $widget['id'] }}"
                 gs-x="{{ $widget['grid_x'] }}"
                 gs-y="{{ $widget['grid_y'] }}"
                 gs-w="{{ $widget['grid_w'] }}"
                 gs-h="{{ $widget['grid_h'] }}">
                <div
                    class="grid-stack-item-content rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-sm relative flex flex-col pd-widget-content">
                    <div
                        class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 flex items-center justify-between flex-shrink-0 rounded-t-xl relative"
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
                            </div>
                            <div class="flex items-center gap-2">
                                @if (!empty($widget['kpi_theory']))
                                    {{-- KPI Theory info button (rich HTML tooltip) --}}
                                    <div x-data="floatingTooltip({ width: 384 })"
                                         @click.outside="show = false"
                                         class="relative flex items-center justify-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                             stroke-width="2" stroke="currentColor"
                                             @click.stop="toggle($el)"
                                             class="w-4 h-4 text-indigo-400 hover:text-indigo-600 dark:hover:text-indigo-300 cursor-pointer transition-colors">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                  d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/>
                                        </svg>
                                        <div x-show="show"
                                             x-transition:enter="transition ease-out duration-100"
                                             x-transition:enter-start="opacity-0 translate-y-1"
                                             x-transition:enter-end="opacity-100 translate-y-0"
                                             x-transition:leave="transition ease-in duration-75"
                                             x-transition:leave-start="opacity-100 translate-y-0"
                                             x-transition:leave-end="opacity-0 translate-y-1"
                                             @click.stop
                                             :style="ts"
                                             class="w-screen max-w-sm border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900"
                                        >
                                            <div
                                                class="rounded-xl bg-gradient-to-b from-gray-800 to-gray-900 border border-gray-700/60 px-6 py-4 shadow-2xl whitespace-normal text-left">
                                                {{-- Header --}}
                                                <div class="flex items-start gap-3 mb-3">
                                                    <div
                                                        class="flex-shrink-0 w-8 h-8 rounded-lg bg-indigo-500/15 flex items-center justify-center">
                                                        <svg class="w-4 h-4 text-indigo-400" fill="none"
                                                             viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                  d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/>
                                                        </svg>
                                                    </div>
                                                    <div class="min-w-0 flex-1">
                                                        <div
                                                            class="text-[11px] font-semibold text-indigo-400 uppercase tracking-wider">{{ __($widget['kpi_theory']['name']) }}</div>
                                                        <div
                                                            class="text-[10px] text-gray-500 mt-0.5">{{ __($widget['kpi_theory']['type_label']) }}</div>
                                                    </div>
                                                </div>

                                                {{-- Explanation --}}
                                                <p class="text-sm text-gray-200 leading-relaxed mb-4">{{ __($widget['kpi_theory']['explanation']) }}</p>

                                                {{-- Use Case --}}
                                                @if (!empty($widget['kpi_theory']['use_case']))
                                                    <div class="mb-4 last:mb-0">
                                                        <div class="flex items-center gap-2 mb-1.5">
                                                            <div
                                                                class="w-1 h-4 rounded-full bg-emerald-500/60 flex-shrink-0"></div>
                                                            <span
                                                                class="font-semibold text-emerald-400 text-[11px] uppercase tracking-wider">{{ __('Use Case') }}</span>
                                                        </div>
                                                        <p class="text-xs text-gray-300 leading-relaxed pl-3">{{ __($widget['kpi_theory']['use_case']) }}</p>
                                                    </div>
                                                @endif

                                                {{-- Interpretation --}}
                                                @if (!empty($widget['kpi_theory']['interpretation']))
                                                    <div class="mb-0">
                                                        <div class="flex items-center gap-2 mb-1.5">
                                                            <div
                                                                class="w-1 h-4 rounded-full bg-amber-500/60 flex-shrink-0"></div>
                                                            <span
                                                                class="font-semibold text-amber-400 text-[11px] uppercase tracking-wider">{{ __('Interpretation') }}</span>
                                                        </div>
                                                        <p class="text-xs text-gray-300 leading-relaxed pl-3">{{ __($widget['kpi_theory']['interpretation']) }}</p>
                                                    </div>
                                                @endif

                                                {{-- Arrow --}}
                                                <div
                                                    :class="{
                                                        'absolute -bottom-[5px] right-4 rotate-45 border-r border-b border-gray-700/60': pos === 'top-right' || !pos,
                                                        'absolute -bottom-[5px] left-4 rotate-45 border-l border-b border-gray-700/60': pos === 'top-left',
                                                        'absolute -top-[5px] right-4 rotate-45 border-t border-l border-gray-700/60': pos === 'bottom-right',
                                                        'absolute -top-[5px] left-4 rotate-45 border-t border-l border-gray-700/60': pos === 'bottom-left',
                                                    }"
                                                    class="h-2.5 w-2.5 bg-gray-800 dark:bg-gray-800"></div>
                                            </div>
                                        </div>
                                    </div>
                                @elseif (!empty($widget['description']))
                                    {{-- Info button (simple tooltip) --}}
                                    <div x-data="floatingTooltip({ width: 256 })"
                                         @click.outside="show = false"
                                         class="relative flex items-center justify-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                             stroke-width="2" stroke="currentColor"
                                             @click.stop="toggle($el)"
                                             class="w-4 h-4 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 cursor-pointer transition-colors">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                  d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/>
                                        </svg>
                                        <div x-show="show"
                                             x-transition:enter="transition ease-out duration-100"
                                             x-transition:enter-start="opacity-0 translate-y-1"
                                             x-transition:enter-end="opacity-100 translate-y-0"
                                             x-transition:leave="transition ease-in duration-75"
                                             x-transition:leave-start="opacity-100 translate-y-0"
                                             x-transition:leave-end="opacity-0 translate-y-1"
                                             @click.stop
                                             :style="ts"
                                             class="w-64 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900">
                                            <div
                                                class="rounded-lg bg-gray-900 dark:bg-gray-700 px-3 py-2 text-xs text-white shadow-lg whitespace-normal text-left">
                                                {{ $widget['description'] }}
                                                <div
                                                    :class="{
                                                        'absolute -bottom-[5px] right-4 rotate-45 border-r border-b border-gray-700/60': pos === 'top-right' || !pos,
                                                        'absolute -bottom-[5px] left-4 rotate-45 border-l border-b border-gray-700/60': pos === 'top-left',
                                                        'absolute -top-[5px] right-4 rotate-45 border-t border-l border-gray-700/60': pos === 'bottom-right',
                                                        'absolute -top-[5px] left-4 rotate-45 border-t border-l border-gray-700/60': pos === 'bottom-left',
                                                    }"
                                                    class="h-2.5 w-2.5 bg-gray-900 dark:bg-gray-700"></div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                                @if (\Filament\Facades\Filament::getTenant()?->supportsAlerts())
                                    <a :href="'/app/' + tenant + '/alerts/create?widget_id=' + widgetId"
                                       class="relative text-gray-500 dark:text-gray-400 hover:text-amber-500 dark:hover:text-amber-400 transition-colors p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700"
                                       title="{{ __('Manage/Create Alert for this Widget') }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                                        </svg>
                                        @if (!empty($widget['associated_alerts_count']))
                                            <span class="absolute -top-1 -right-1 flex h-3.5 w-3.5 items-center justify-center rounded-full bg-amber-500 text-[9px] font-bold text-white shadow-sm">
                                                {{ $widget['associated_alerts_count'] }}
                                            </span>
                                        @endif
                                    </a>
                                @endif
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
                    <div class="widget-content flex-grow p-4 relative overflow-y-auto"
                         data-widget-id="{{ $widget['id'] }}"
                         data-raw-controls="{{ json_encode($widget['resolved_controls']) }}"
                         x-init="renderWidget({{ $widget['id'] }}, $el, {{ json_encode($widget['resolved_controls']) }})">
                    </div>
                </div>
            </div>

            @php
                $nextWidget = $viewObj->widgets[$wIndex + 1] ?? null;
                $currentEndY = ($widget['grid_y'] ?? 0) + ($widget['grid_h'] ?? 1);
                $nextStartY = $nextWidget ? ($nextWidget['grid_y'] ?? 0) : null;
            @endphp

            @foreach ($emptyCutLines as $cutY)
                @if (!in_array($cutY, $insertedCuts, true) && $currentEndY <= $cutY && ($nextStartY === null || $nextStartY >= $cutY))
                    @php $insertedCuts[] = $cutY; @endphp
                    <div class="dashboard-empty-line"
                         data-cut-y="{{ $cutY }}"
                         style="top: calc({{ $cutY }} * (100px + 12px));"></div>
                @endif
            @endforeach
        @endforeach
    </div>

    @if (empty($viewObj->widgets))
        <div class="flex flex-col items-center justify-center py-24 text-center">
            <x-filament::icon name="heroicon-o-squares-2x2" class="w-16 h-16 text-gray-300 dark:text-gray-600 mb-4"/>
            <p class="text-gray-500 dark:text-gray-400 text-lg">No widgets on this dashboard yet</p>
        </div>
    @endif

    {{-- Fullscreen Pop-Out Modal --}}
    <div x-show="popOutActive" x-cloak
         class="dvc-popout-root fixed inset-0 flex items-center justify-center bg-black/50"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-out duration-300"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click.self="closePopOut()">
        <div x-ref="popOutCard"
             class="relative bg-white dark:bg-gray-900 rounded-xl shadow-2xl flex flex-col overflow-hidden dvc-popout-panel">
            <div
                class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex-shrink-0 bg-gray-50 dark:bg-gray-800">
                <div class="flex items-center gap-2 min-w-0 pr-4">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white truncate" x-text="popOutTitle"></h3>
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
                            class="text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300 transition-colors p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700"
                            title="Close">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                             stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>
            <div x-ref="popOutContent" class="flex-grow relative overflow-hidden dvc-popout-content">
            </div>
        </div>
    </div>

    {{-- Settings Modal (teleported to body to avoid z-index issues) --}}
    <template x-teleport="body">
        <div x-show="openSettings" x-cloak
             class="bd-modal-root fixed inset-0 flex items-start justify-center pt-10 sm:pt-16"
             x-trap.noscroll="openSettings">
            <div @click="closeSettings()"
                 class="fixed inset-0 bg-black/50 dark:bg-black/70 backdrop-blur-sm transition-opacity"></div>
<div
                class="relative bg-white dark:bg-gray-900 rounded-xl shadow-xl mx-auto my-4 sm:my-6 flex flex-col ring-1 ring-gray-900/5 dark:ring-white/10 bd-modal-panel"
                @click.away="closeSettings()">
                <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-900/50 rounded-t-xl">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white">{{ __('Widget Settings') }}</h3>
                    <div class="flex items-center gap-3">
                        {{-- Series Stepped Navigation Arrows (Visible when > 2 series) --}}
                        <div
                            x-show="Object.keys(settingsVariables || {}).filter(k => shouldShowSeries(k)).length > 2"
                            class="hidden md:flex items-center gap-1 bg-gray-100 dark:bg-gray-800 p-1 rounded-lg border border-gray-200 dark:border-gray-700">
                            <span class="text-[11px] font-semibold text-gray-500 dark:text-gray-400 px-2 select-none">
                                <span x-text="Object.keys(settingsVariables || {}).filter(k => shouldShowSeries(k)).length"></span> {{ __('Series') }}
                            </span>
                            <button
                                type="button"
                                @click="scrollSettingsSeriesByStep(-1)"
                                :disabled="!canSettingsScrollSeriesLeft"
                                :title="'{{ __('Scroll left') }}'"
                                class="p-1 rounded-md transition-all"
                                :class="canSettingsScrollSeriesLeft
                                    ? 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 shadow-xs cursor-pointer'
                                    : 'text-gray-300 dark:text-gray-600 cursor-not-allowed opacity-40'">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"></path>
                                </svg>
                            </button>
                            <button
                                type="button"
                                @click="scrollSettingsSeriesByStep(1)"
                                :disabled="!canSettingsScrollSeriesRight"
                                :title="'{{ __('Scroll right') }}'"
                                class="p-1 rounded-md transition-all"
                                :class="canSettingsScrollSeriesRight
                                    ? 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 shadow-xs cursor-pointer'
                                    : 'text-gray-300 dark:text-gray-600 cursor-not-allowed opacity-40'">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </button>
                        </div>

                        <button
                            type="button"
                            @click="closeSettings()"
                            class="bd-modal-header-close"
                            :title="'{{ __('Close') }}'">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                                 stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="flex-1 bg-gray-50 dark:bg-gray-900 min-h-0 overflow-y-auto desktop-overflow-hidden relative flex flex-col">
                    {{-- Mobile Accordion Navigation Bar --}}
                    <div class="md:hidden flex border-b border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 sticky top-0 z-20">
                        <button type="button" @click="activeSettingsMobileTab = 'config'"
                                class="flex-1 py-3 px-4 text-center text-xs font-bold uppercase tracking-wider transition-colors border-b-2"
                                :class="activeSettingsMobileTab === 'config' ? 'border-primary-500 text-primary-600 dark:text-primary-400 bg-primary-50/50 dark:bg-primary-950/20' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400'">
                            {{ __('1. Configuration') }}
                        </button>
                        <button type="button" @click="activeSettingsMobileTab = 'series'"
                                class="flex-1 py-3 px-4 text-center text-xs font-bold uppercase tracking-wider transition-colors border-b-2"
                                :class="activeSettingsMobileTab === 'series' ? 'border-primary-500 text-primary-600 dark:text-primary-400 bg-primary-50/50 dark:bg-primary-950/20' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400'">
                            {{ __('2. Series & Assets') }}
                        </button>
                    </div>

                    <div class="modal-body-absolute-wrapper flex flex-col md:flex-row gap-6 flex-1 min-h-0">
                        {{-- Left Column: Global Configuration --}}
                        <div class="flex flex-col gap-6 overflow-y-auto custom-scrollbar pb-2 min-h-0 bd-config-col"
                             :class="{ 'hidden md:flex': activeSettingsMobileTab !== 'config' }">
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
                                        class="text-xs font-bold text-gray-800 dark:text-white uppercase tracking-wider">{{ __('Date Range') }}</span>
                                </div>
                                <div class="p-6 flex flex-row items-center gap-3">
                                    <x-ui.date-input x-model="settingsControls.date_start"
                                                     x-bind:min="settingsBuilderControls.date_start || dashboardDefaults.date_start || ''"
                                                     x-bind:max="settingsControls.date_end || dashboardDefaults.date_end"
                                                     class="w-full" />
                                    <span class="text-gray-400 dark:text-gray-500 text-sm">→</span>
                                    <x-ui.date-input x-model="settingsControls.date_end"
                                                     x-bind:min="settingsControls.date_start || settingsBuilderControls.date_start || dashboardDefaults.date_start || ''"
                                                     x-bind:max="dashboardDefaults.date_end"
                                                     class="w-full" />
                                </div>
                            </div>

                            {{-- Card: Granularity --}}
                            <template x-if="settingsGranularityOnTheGo || settingsSourceType === 'kpi'">
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
                                            class="text-xs font-bold text-gray-800 dark:text-white uppercase tracking-wider">{{ __('Granularity') }}</span>
                                    </div>
                                    <div class="p-6">
                                        <template x-if="settingsSourceType === 'kpi'">
                                            <div class="w-full text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 py-2.5 px-4 cursor-not-allowed">
                                                <span x-text="{
                                                    'daily': 'Daily',
                                                    'weekly': 'Weekly',
                                                    'monthly': 'Monthly',
                                                    'quarterly': 'Quarterly',
                                                    'semiannual': 'Semiannual',
                                                    'annually': 'Annually',
                                                    'lifetime': 'Lifetime',
                                                    'query': 'Query (SEO)',
                                                    'dimensions.page': 'Page (SEO)',
                                                    'country': 'Country',
                                                    'device': 'Device',
                                                    'post': 'Post / Media'
                                                }[settingsBuilderControls.granularity] || settingsBuilderControls.granularity || 'Daily'"></span>
                                            </div>
                                        </template>
                                        <template x-if="settingsSourceType !== 'kpi'">
                                            <x-ui.select-input x-model="settingsControls.granularity" class="w-full">
                                                <template x-if="['daily', 'weekly', 'monthly', 'quarterly', 'semiannual', 'annually', 'lifetime'].includes(settingsBuilderControls.granularity)">
                                                    <optgroup label="{{ __('Time Granularities') }}" class="bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                                                        <x-ui.select-option value="daily">{{ __('Daily') }}</x-ui.select-option>
                                                        <x-ui.select-option value="weekly">{{ __('Weekly') }}</x-ui.select-option>
                                                        <x-ui.select-option value="monthly">{{ __('Monthly') }}</x-ui.select-option>
                                                        <x-ui.select-option value="quarterly">{{ __('Quarterly') }}</x-ui.select-option>
                                                        <x-ui.select-option value="semiannual">{{ __('Semiannual') }}</x-ui.select-option>
                                                        <x-ui.select-option value="annually">{{ __('Annually') }}</x-ui.select-option>
                                                        <x-ui.select-option value="lifetime">{{ __('Lifetime') }}</x-ui.select-option>
                                                    </optgroup>
                                                </template>
                                                <template x-if="!['daily', 'weekly', 'monthly', 'quarterly', 'semiannual', 'annually', 'lifetime'].includes(settingsBuilderControls.granularity)">
                                                    <optgroup label="{{ __('Dimension') }}" class="bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                                                        <x-ui.select-option x-bind:value="settingsBuilderControls.granularity" x-text="{
                                                            'query': 'Query (SEO)',
                                                            'dimensions.page': 'Page (SEO)',
                                                            'country': 'Country',
                                                            'device': 'Device',
                                                            'post': 'Post / Media'
                                                        }[settingsBuilderControls.granularity] || (String(settingsBuilderControls.granularity).charAt(0).toUpperCase() + String(settingsBuilderControls.granularity).slice(1))"></x-ui.select-option>
                                                    </optgroup>
                                                </template>
                                            </x-ui.select-input>
                                        </template>
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
                                            class="text-xs font-bold text-gray-800 dark:text-white uppercase tracking-wider">{{ __('Edge Cases') }}</span>
                                    </div>
                                    <div class="p-6 space-y-4">
                                        <label class="flex items-center gap-3 cursor-pointer">
                                            <input type="checkbox" x-model="settingsControls.edge_case_weighted"
                                                   class="w-4 h-4 rounded border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-primary-600 focus:ring-primary-500">
                                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ __('Weighted regression (WLS)') }}</span>
                                        </label>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 -mt-2">{{ __('Weight each dimension value by its volume so high-volume items influence the regression line proportionally more.') }}</p>
                                        <div>
                                            <label
                                                class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">{{ __('Group low-frequency values') }}</label>
                                            <x-ui.select-input x-model="settingsControls.edge_case_grouping" class="w-full">
                                                <x-ui.select-option value="none">{{ __('No grouping') }}</x-ui.select-option>
                                                <x-ui.select-option value="histogram">{{ __('Auto histogram-elbow') }}</x-ui.select-option>
                                                <x-ui.select-option value="percentile">{{ __('Bottom percentile') }}</x-ui.select-option>
                                            </x-ui.select-input>
                                        </div>
                                        <div>
                                            <label
                                                class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">{{ __('Max ratio cap') }}</label>
                                            <input type="number" step="0.01" min="0"
                                                   x-model="settingsControls.max_ratio"
                                                   class="w-full bg-white dark:bg-white/5 border border-gray-300 dark:border-white/10 text-gray-950 dark:text-white text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block p-2.5"
                                                   placeholder="{{ __('No cap') }}"/>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ __('Filter out ratio values above this threshold.') }}</p>
                                        </div>
                                        <label class="flex items-center gap-3 cursor-pointer">
                                            <input type="checkbox" x-model="settingsControls.remove_unknown"
                                                   class="w-4 h-4 rounded border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-primary-600 focus:ring-primary-500">
                                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ __('Exclude unknown keyword') }}</span>
                                        </label>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 -mt-2">{{ __('Remove the unknown query from the chart and recalculate the regression line without it.') }}</p>
                                    </div>
                                </div>
                            </template>
                            {{-- Card: Table Options (Table widgets only) --}}
                            <template x-if="settingsSourceType === 'table' || settingsOriginalControls.widget_type === 'table'">
                                <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm overflow-hidden flex-shrink-0">
                                    <div class="flex items-center justify-between px-6 py-3 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700">
                                        <div class="flex items-center gap-2">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-gray-500 dark:text-gray-400">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                                            </svg>
                                            <span class="text-xs font-bold text-gray-800 dark:text-white uppercase tracking-wider">{{ __('Table Options') }}</span>
                                        </div>
                                    </div>
                                    <div class="p-6">
                                        <label class="flex items-center gap-3 cursor-pointer">
                                            <input type="checkbox" x-model="settingsControls.block_first_col"
                                                   class="w-4 h-4 rounded border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-primary-600 focus:ring-primary-500">
                                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ __('Freeze first column') }}</span>
                                        </label>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ __('Keep the first column fixed on the left while horizontal scrolling.') }}</p>
                                    </div>
                                </div>
                            </template>
                        </div>

                        {{-- Right Column: Variables Configuration --}}
                        <div
                            x-ref="settingsSeriesScrollContainer"
                            @scroll.passive="updateSettingsSeriesScrollState()"
                            class="min-w-0 min-h-0 flex overflow-x-auto gap-6 custom-scrollbar pb-2 items-stretch snap-x snap-mandatory bd-canvas-col"
                            :class="{ 'hidden md:flex': activeSettingsMobileTab !== 'series' }">
                            
                            <template x-for="(vConfig, vKey, vIdx) in settingsVariables" :key="vKey">
                                <template x-if="vConfig && shouldShowSeries(vKey)">
                                    <div
                                        class="flex-none shrink-0 h-full min-h-0 flex flex-col snap-start bd-series-card">
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
                                                        x-text="vConfig.dm_name ? (settingsSourceType === 'kpi' ? vConfig.dm_name + ' ' + (vConfig.dm_source_label || '') : vConfig.dm_name) : (vKey === 'dependent' ? 'Dependent Series' : (settingsSourceType === 'kpi' ? 'Independent Variable ' + (vConfig.index) : 'Series ' + (vConfig.index + 1)))"></span>
                                                    <template x-if="vConfig.dm_name">
                                                        <span class="text-[10px] font-semibold text-indigo-600 dark:text-indigo-400 bg-indigo-100 dark:bg-indigo-900/30 px-2 py-1 rounded-full ml-1">DM</span>
                                                    </template>
                                                </div>
                                                <div class="flex flex-col items-end gap-1">
                                                    <template x-if="vConfig.channel">
                                                        <span
                                                            class="text-[10px] font-semibold text-gray-600 dark:text-gray-300 bg-gray-200 dark:bg-gray-700 px-2.5 py-1 rounded-full"
                                                            x-text="vConfig.channel_name || vConfig.channel"></span>
                                                    </template>
                                                    <template
                                                         x-if="vConfig.selected_metric">
                                                        <span
                                                            class="text-[10px] font-medium text-gray-500 dark:text-gray-400"
                                                            x-text="(vConfig.metrics || {})[vConfig.selected_metric] || vConfig.selected_metric"></span>
                                                    </template>
                                                </div>
                                            </div>
                                            <div class="p-6 flex-1 flex flex-col gap-6 min-h-0">
                                                {{-- Data Scope / Dependency selector --}}
                                                <template x-if="vConfig.dependencies && Object.keys(vConfig.dependencies).length > 0">
                                                    <div class="my-2">
                                                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">{{ __('Data Scope / Matrix') }}</label>
                                                        <select x-model="settingsControls.series_dependencies[vKey]"
                                                                class="bg-white dark:bg-white/5 border border-gray-300 dark:border-white/10 text-gray-950 dark:text-white dark:[color-scheme:dark] text-sm p-2.5 rounded-lg focus:ring-primary-500 focus:border-primary-500 block cursor-pointer w-full">
                                                            <template x-for="(label, key) in vConfig.dependencies" :key="key">
                                                                <option :value="key" x-text="label" :selected="settingsControls.series_dependencies[vKey] === key" class="bg-white dark:bg-gray-800 text-gray-900 dark:text-white"></option>
                                                            </template>
                                                        </select>
                                                    </div>
                                                </template>

                                                {{-- Metric selector (KPI Widgets) --}}
                                                <template x-if="settingsSourceType === 'kpi' && !vConfig.selected_metric && vConfig.metrics && Object.keys(vConfig.metrics).length > 0">
                                                    <div class="my-2">
                                                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">Metric</label>
                                                        <select x-model="settingsControls.metrics[vConfig.index]"
                                                                class="bg-white dark:bg-white/5 border border-gray-300 dark:border-white/10 text-gray-950 dark:text-white dark:[color-scheme:dark] text-sm p-2.5 rounded-lg focus:ring-primary-500 focus:border-primary-500 block cursor-pointer w-full">
                                                            <template x-for="(label, key) in (vConfig.metrics || {})" :key="key">
                                                                <option :value="key" x-text="label" :selected="settingsControls.metrics[vConfig.index] === key" class="bg-white dark:bg-gray-800 text-gray-900 dark:text-white"></option>
                                                            </template>
                                                        </select>
                                                    </div>
                                                </template>

                                                {{-- Metric selector (Metric Widgets) --}}
                                                <template x-if="settingsSourceType !== 'kpi' && settingsSourceType !== 'derived_metric' && vConfig.type !== 'derived_metric' && !vConfig.selected_metric && vConfig.metrics && Object.keys(vConfig.metrics).length > 0">
                                                    <div class="my-2">
                                                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">Metrics (Ctrl/Cmd to multi-select)</label>
                                                        <div class="flex-1 relative min-h-0 h-32 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800">
                                                            <div class="absolute inset-0 flex flex-col gap-1 overflow-y-auto p-1 custom-scrollbar">
                                                                <template x-for="(label, key) in vConfig.metrics" :key="key">
                                                                    <div @click="settingsToggleMetric(vKey, key)"
                                                                         class="flex gap-x-3 items-center px-3 py-2 text-sm text-gray-700 dark:text-gray-200 rounded-md cursor-pointer transition-colors border border-transparent"
                                                                         :class="settingsIsMetricSelected(vKey, key) ? 'bg-primary-50 dark:bg-primary-900/30 border-primary-100 dark:border-primary-900/50' : 'hover:bg-gray-100 dark:hover:bg-white/5'">
                                                                        <div
                                                                            class="w-4 h-4 shrink-0 flex items-center justify-center rounded border transition-colors"
                                                                            :class="settingsIsMetricSelected(vKey, key) ? 'bg-primary-600 border-primary-600' : 'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800'">
                                                                            <svg
                                                                                x-show="settingsIsMetricSelected(vKey, key)"
                                                                                class="w-3 h-3 text-white" fill="none"
                                                                                viewBox="0 0 24 24" stroke-width="3"
                                                                                stroke="currentColor">
                                                                                <path stroke-linecap="round"
                                                                                      stroke-linejoin="round"
                                                                                      d="m4.5 12.75 6 6 9-13.5"/>
                                                                            </svg>
                                                                        </div>
                                                                        <span class="truncate font-medium" :class="settingsIsMetricSelected(vKey, key) ? 'text-primary-800 dark:text-primary-200' : ''" x-text="label"></span>
                                                                    </div>
                                                                </template>
                                                                <template x-if="!vConfig.metrics || Object.keys(vConfig.metrics).length === 0">
                                                                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-2 mx-2">No metrics available.</p>
                                                                </template>
                                                            </div>
                                                        </div>
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
                                                                x-if="(settingsSeriesOptions[vKey] && settingsSeriesOptions[vKey].mode ? settingsSeriesOptions[vKey].mode : 'multiple') === 'multiple'">
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
                                                                   class="bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 bd-search-input">
                                                        </div>
                                                        <div class="flex-1 relative min-h-0">
                                                            <div
                                                                class="absolute inset-0 flex flex-col gap-1 overflow-y-auto pr-1 custom-scrollbar">
                                                                <template
                                                                    x-for="[assetId, assetName] in Object.entries((settingsSeriesOptions[vKey] && settingsSeriesOptions[vKey].options) || {})"
                                                                    :key="assetId">
                                                                    <div
                                                                        x-show="isViewAssetInGroup(vConfig.channel, assetId) && (settingsSearchQueries[vKey] === '' || assetName.toLowerCase().includes(settingsSearchQueries[vKey].toLowerCase()))"
                                                                        @click="settingsToggleAsset(vKey, assetId)"
                                                                        class="flex gap-x-3 items-center px-3 py-2.5 text-sm text-gray-700 dark:text-gray-200 rounded-lg cursor-pointer transition-colors border border-transparent"
                                                                        :class="((settingsControls.series_assets || {})[vKey] || []).includes(String(assetId)) ? 'bg-primary-50 dark:bg-primary-900/30 border-primary-100 dark:border-primary-900/50' : 'hover:bg-gray-100 dark:hover:bg-white/5'">
                                                                        <div class="w-4 h-4 shrink-0 flex items-center justify-center border transition-colors"
                                                                             :class="{
                                                                                 'rounded': (settingsSeriesOptions[vKey] && settingsSeriesOptions[vKey].mode ? settingsSeriesOptions[vKey].mode : 'multiple') === 'multiple',
                                                                                 'rounded-full': (settingsSeriesOptions[vKey] && settingsSeriesOptions[vKey].mode ? settingsSeriesOptions[vKey].mode : 'multiple') === 'single',
                                                                                 'bg-primary-600 border-primary-600': ((settingsControls.series_assets || {})[vKey] || []).includes(String(assetId)),
                                                                                 'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800': !((settingsControls.series_assets || {})[vKey] || []).includes(String(assetId))
                                                                             }">
                                                                            <svg x-show="((settingsControls.series_assets || {})[vKey] || []).includes(String(assetId)) && (settingsSeriesOptions[vKey] && settingsSeriesOptions[vKey].mode ? settingsSeriesOptions[vKey].mode : 'multiple') === 'multiple'"
                                                                                 class="w-3 h-3 text-white" fill="none"
                                                                                 viewBox="0 0 24 24" stroke-width="3"
                                                                                 stroke="currentColor">
                                                                                <path stroke-linecap="round"
                                                                                      stroke-linejoin="round"
                                                                                      d="m4.5 12.75 6 6 9-13.5"/>
                                                                            </svg>
                                                                            <div x-show="((settingsControls.series_assets || {})[vKey] || []).includes(String(assetId)) && (settingsSeriesOptions[vKey] && settingsSeriesOptions[vKey].mode ? settingsSeriesOptions[vKey].mode : 'multiple') === 'single'"
                                                                                 class="w-2 h-2 rounded-full bg-white"></div>
                                                                        </div>
                                                                        <span class="truncate font-medium"
                                                                              :class="((settingsControls.series_assets || {})[vKey] || []).includes(String(assetId)) ? 'text-primary-800 dark:text-primary-200' : ''"
                                                                              x-text="assetName"></span>
                                                                    </div>
                                                                </template>
                                                                <template x-if="selectedAssetGroup && vConfig.channel && (!channelAssetGroupMap[vConfig.channel] || !channelAssetGroupMap[vConfig.channel][selectedAssetGroup] || channelAssetGroupMap[vConfig.channel][selectedAssetGroup].length === 0)">
                                                                    <p class="text-xs text-amber-500 font-medium mt-2 mx-2">No assets available in this group for this channel.</p>
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