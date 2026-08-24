<x-filament-panels::page>
    <link rel="stylesheet" href="{{ asset('css/dashboard-builder.css') }}?v={{ file_exists(public_path('css/dashboard-builder.css')) ? filemtime(public_path('css/dashboard-builder.css')) : time() }}"/>

    <div x-data="dashboardBuilder({
        widgets: @js($this->widgets ?? []),
        gridState: @js($this->gridState ?? []),
        widgetLabels: @js(\App\Services\WidgetTypeRegistry::getWidgetLabels()),
        widgetDescriptions: @js(\App\Services\WidgetTypeRegistry::getWidgetDescriptions()),
        widgetSvgs: @js(\App\Services\WidgetTypeRegistry::getWidgetSvgs()),
        channels: @js($this->getActiveChannels()),
        dashboardControls: @js($this->getDashboardControls()),
        assetGroups: @js($this->getAllAssetGroups()),
        isPublic: {{ $this->dashboard->is_public ? 'true' : 'false' }},
        sourceTypes: @js($this->getAvailableSourceTypes()),
        kpis: @js($this->getKpisForWidgetPicker()),
        derivedMetrics: @js($this->getDerivedMetricsForWidgetPicker()),
        defaultEndDate: @js(date('Y-m-d', strtotime('-1 day'))),
        availableLanguages: @js(\Filament\Facades\Filament::getTenant()?->getAvailableLanguages() ?? \App\Models\Project::getSupportedLanguageCatalog()),
        tenant: @js(\Filament\Facades\Filament::getTenant()?->subdomain ?? \Filament\Facades\Filament::getTenant()?->id ?? '')
    })" wire:ignore.self class="space-y-4">
        {{-- Toolbar --}}
        <div class="builder-toolbar flex items-center justify-between gap-4 rounded-xl p-4 transition-colors">
            <div class="flex items-center gap-2">
                <x-filament::icon name="heroicon-o-squares-2x2" class="w-6 h-6 text-gray-500 dark:text-gray-400"/>
                <span class="text-lg font-medium text-gray-900 dark:text-white">{{ $this->dashboard->name }}</span>
            </div>
            <div class="flex items-center gap-2">
                <button
                    class="px-3 py-2 rounded-lg text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-800 transition-colors"
                    x-on:click="openDashboardControls()">
                    <x-filament::icon name="heroicon-o-cog-6-tooth" class="w-4 h-4 inline mr-1"/>
                    {{ __('Controls') }}
                </button>
                @can('edit_preferences')
                    <button
                        class="px-3 py-2 rounded-lg text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-800 transition-colors"
                        x-on:click="openShareDialog()">
                        <x-filament::icon name="heroicon-o-share" class="w-4 h-4 inline mr-1"/>
                        {{ __('Share') }}
                    </button>
                @endcan

                {{-- Version Unsaved Changes Indicator --}}
                <div x-show="$wire.unsavedChanges" x-cloak class="bd-unsaved-badge">
                    <span class="bd-unsaved-dot"></span>
                    <span>{{ __('Unsaved version') }}</span>
                </div>

                {{-- Layout Dirty Status Badge --}}
                <div x-show="isDirty" x-cloak class="bd-unsaved-badge">
                    <span class="bd-unsaved-dot"></span>
                    <span>{{ __('Unsaved layout changes') }}</span>
                </div>
                <div x-show="!isDirty"
                     class="flex items-center gap-1 px-2.5 py-1 rounded-lg bg-gray-100 dark:bg-gray-800 text-xs font-medium text-gray-500 dark:text-gray-400">
                    <x-filament::icon name="heroicon-m-check" class="w-3.5 h-3.5 text-success-500"/>
                    <span>{{ __('Layout saved') }}</span>
                </div>

                <x-filament::button
                    x-on:click="saveLayout()"
                    ::color="isDirty ? 'warning' : 'primary'"
                    icon="heroicon-o-check"
                    ::class="isDirty ? 'ring-2 ring-warning-500/50 shadow-md animate-pulse' : ''">
                    {{ __('Save Layout') }}
                </x-filament::button>
            </div>
        </div>



        <div class="grid grid-cols-12 gap-4">
            {{-- Widget Palette (sidebar) --}}
            <div class="col-span-2 hidden lg:block">
                <div class="rounded-xl bg-gray-50 dark:bg-gray-900 p-4 space-y-4">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Widget Palette') }}</h3>
                    <div class="space-y-2">
                        <div
                            class="rounded-lg border border-dashed border-gray-300 dark:border-gray-600 p-3 text-center cursor-pointer hover:border-primary-500 hover:bg-primary-50 dark:hover:bg-primary-900/20 transition-colors"
                            x-on:click="openAddWidgetModal()">
                            <x-filament::icon name="heroicon-o-plus-circle"
                                              class="w-8 h-8 mx-auto text-gray-400 dark:text-gray-500"/>
                            <span
                                class="block text-xs text-gray-500 dark:text-gray-400 mt-1">{{ __('Add Widget') }}</span>
                        </div>
                    </div>
                    <div class="pt-4 border-t border-gray-200 dark:border-gray-700 space-y-3">
                        <p class="text-xs text-gray-400 dark:text-gray-500">{{ __('Drag title to reposition. Grab bottom-right corner') }}
                            <span
                                class="inline-block w-3 h-3 align-text-bottom bd-resize-hint"></span> {{ __('to resize.') }}
                        </p>
                        <div class="text-xs text-gray-400 dark:text-gray-500">
                            <div class="flex items-center gap-1 mb-1">
                                <span class="inline-block w-2 h-2 rounded-full bg-green-400"></span>
                                <span>{{ __('Control inherited from dashboard') }}</span>
                            </div>
                            <div class="flex items-center gap-1">
                                <span class="inline-block w-2 h-2 rounded-full bg-blue-400"></span>
                                <span>{{ __('Custom control set') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Grid Area --}}
            <div class="col-span-12 lg:col-span-10">
                <div id="grid-container" wire:ignore
                     class="rounded-xl bg-white dark:bg-gray-950 p-4 border border-gray-200 dark:border-gray-800">
                    <div id="grid-stack" class="grid-stack">
                        <template x-for="(widget, index) in widgets" :key="widget.id">
                            <div class="grid-stack-item transition-all duration-700 ease-in-out"
                                 :data-id="widget.id"
                                 :gs-id="widget.id"
                                 :gs-x="widget.grid_x"
                                 :gs-y="widget.grid_y"
                                 :gs-w="widget.grid_w || 4"
                                 :gs-h="widget.grid_h || 3"
                                 :id="widget.id"
                                 :class="{ 'ring-2 ring-primary-500 shadow-lg shadow-primary-500/50': widget._isNew }">
                                <div
                                    class="grid-stack-item-content rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-sm relative flex flex-col overflow-hidden transition-all"
                                    :class="{ 'ring-2 ring-primary-500 border-primary-500 dark:border-primary-500 shadow-lg shadow-primary-500/20': isWidgetSelected(widget.id) }">
                                    {{-- Widget Header --}}
                                    <div
                                        class="widget-header cursor-grab active:cursor-grabbing rounded-t-lg flex items-center justify-between px-3 py-2 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 gap-4 flex-shrink-0">
                                        <div class="flex items-center gap-2 min-w-0">
                                            <input type="checkbox"
                                                   :checked="isWidgetSelected(widget.id)"
                                                   x-on:click.stop="toggleWidgetSelection(widget.id)"
                                                   class="rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500 cursor-pointer h-4 w-4 flex-shrink-0"
                                                   :title="'{{ __('Select for multi-widget drag/actions') }}'"/>
                                            <span x-show="widgetHasCustomControls(widget)"
                                                  class="inline-block w-2 h-2 rounded-full bg-blue-400 flex-shrink-0"
                                                  :title="'{{ __('Has custom controls') }}'"></span>
                                            <span x-show="!widgetHasCustomControls(widget)"
                                                  class="inline-block w-2 h-2 rounded-full bg-green-400 flex-shrink-0"
                                                  :title="'{{ __('Inheriting dashboard controls') }}'"></span>
                                            <span class="text-sm font-medium text-gray-900 dark:text-white truncate"
                                                  :title="getWidgetTitleText(widget)"
                                                  x-text="getWidgetTitleText(widget)"></span>
                                        </div>
                                        <div class="flex items-center gap-1 flex-shrink-0">
                                            <template x-if="getWidgetDescriptionText(widget)">
                                                <div class="group relative flex items-center justify-center p-1">
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                         viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                                         class="w-4 h-4 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 cursor-help transition-colors">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                              d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/>
                                                    </svg>
                                                    <div
                                                        class="pointer-events-none absolute bottom-full mb-2 w-64 opacity-0 transition-opacity group-hover:opacity-100 z-50 right-0 sm:right-auto sm:left-1/2 sm:-translate-x-1/2">
                                                        <div
                                                            class="rounded-lg bg-gray-900 dark:bg-gray-700 px-3 py-2 text-xs text-white shadow-lg whitespace-normal text-left">
                                                            <span x-text="getWidgetDescriptionText(widget)"></span>
                                                            <div
                                                                class="absolute -bottom-1 right-2 sm:right-auto sm:left-1/2 sm:-translate-x-1/2 h-2 w-2 rotate-45 bg-gray-900 dark:bg-gray-700"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </template>
                                            <button
                                                type="button"
                                                class="p-1 rounded hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 relative transition-colors"
                                                :class="hasSandboxOverrides(widget.id) ? 'text-primary-600 dark:text-primary-400 bg-primary-50 dark:bg-primary-950/40' : ''"
                                                x-on:click.stop="openSandboxModal(widget)"
                                                :title="'{{ __('Test filters (ephemeral preview)') }}'">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                                     stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                          d="M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9.75 0h9.75" />
                                                </svg>
                                                <span x-show="hasSandboxOverrides(widget.id)" class="absolute top-0.5 right-0.5 w-1.5 h-1.5 bg-primary-500 rounded-full"></span>
                                            </button>
                                            <button
                                                class="p-1 rounded hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300"
                                                x-on:click.stop="openWidgetControls(widget)"
                                                :title="'{{ __('Configure') }}'">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                                     stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                          d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                          d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                </svg>
                                            </button>
                                            <button
                                                class="p-1 rounded hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300"
                                                x-on:click.stop="duplicateWidget(widget.id)"
                                                :title="'{{ __('Duplicate') }}'">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                                     stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                          d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 01-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 011.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 00-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 01-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 00-3.375-3.375h-1.5a1.125 1.125 0 01-1.125-1.125v-1.5a3.375 3.375 0 00-3.375-3.375H9.75"/>
                                                </svg>
                                            </button>
                                            <button
                                                class="p-1 rounded hover:bg-red-100 dark:hover:bg-red-900/30 text-gray-500 dark:text-gray-400 hover:text-red-600 dark:hover:text-red-400"
                                                x-on:click.stop="confirmDeleteWidget(widget.id)"
                                                :title="'{{ __('Remove') }}'">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                                     stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                          d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                    {{-- Widget Content (Live Visualizer WYSIWYG) --}}
                                    <div class="flex-1 w-full h-full min-h-0 overflow-hidden relative flex flex-col p-2">
                                        <div :id="'builder-widget-preview-' + widget.id"
                                             class="widget-content flex-1 w-full h-full min-h-0 overflow-hidden relative"></div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                    <template x-if="!widgets || widgets.length === 0">
                        <div class="flex flex-col items-center justify-center py-24 text-center">
                            <x-filament::icon name="heroicon-o-squares-2x2"
                                              class="w-16 h-16 text-gray-300 dark:text-gray-600 mb-4"/>
                            <p class="text-gray-500 dark:text-gray-400 text-lg">{{ __('No widgets yet') }}</p>
                            <p class="text-gray-400 dark:text-gray-500 text-sm mt-1">{{ __('Click "Add Widget" in the palette
                                to get started.') }}</p>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        {{-- ============================================================ --}}
        {{-- DASHBOARD-LEVEL CONTROLS MODAL                              --}}
        {{-- ============================================================ --}}
        <div x-show="showDashboardControls"
             class="bd-modal-root fixed inset-0 z-[100] flex items-start justify-center pt-10 sm:pt-16"
             x-trap.noscroll="showDashboardControls"
             @keydown.escape.window="showDashboardControls = false">
            <div @click="showDashboardControls = false"
                 class="fixed inset-0 bg-black/50 dark:bg-black/70 backdrop-blur-sm transition-opacity"></div>
            <div class="relative bg-white dark:bg-gray-900 rounded-xl shadow-xl mx-auto my-4 sm:my-6 flex flex-col ring-1 ring-gray-900/5 dark:ring-white/10 bd-modal-panel max-w-5xl w-full">
                {{-- Modal Header --}}
                <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-900 rounded-t-xl flex-shrink-0">
                    <div class="flex flex-col gap-1 min-w-0 pr-4">
                        <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" class="w-5 h-5 text-primary-500 flex-shrink-0">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9.75 0h9.75" />
                            </svg>
                            <span>{{ __('Dashboard Controls') }}</span>
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{ __('These defaults apply to all widgets. Widgets can override individually.') }}
                        </p>
                    </div>
                    <div class="flex items-center gap-3 flex-shrink-0">
                        <button type="button"
                                class="bd-modal-header-btn-cancel"
                                x-on:click="showDashboardControls = false">
                            {{ __('Cancel') }}
                        </button>
                        <button type="button"
                                class="bd-modal-header-btn-save"
                                x-on:click="confirmDashboardControls()">
                            {{ __('Save Controls') }}
                        </button>
                        <button type="button"
                                @click="showDashboardControls = false"
                                class="bd-modal-header-close"
                                :title="'{{ __('Close') }}'">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Modal Body --}}
                <div class="p-6 overflow-y-auto max-h-[calc(85vh-100px)] custom-scrollbar">
                    @php
                        $yesterdayDate = date('Y-m-d', strtotime('-1 day'));
                    @endphp

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-start">
                        {{-- Left Column: General Analytics Controls --}}
                        <div class="space-y-6">
                            {{-- Card: Date Range --}}
                            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm overflow-hidden flex-shrink-0">
                                <div class="flex items-center gap-2 px-6 py-3 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-gray-500 dark:text-gray-400">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/>
                                    </svg>
                                    <span class="text-xs font-bold text-gray-800 dark:text-white uppercase tracking-wider">{{ __('Date Range') }}</span>
                                </div>
                                <div class="p-6">
                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <span class="text-xs text-gray-500 dark:text-gray-400 mb-1 block">{{ __('Start') }}</span>
                                            <x-ui.date-input x-model="dashboardControls.date_start"
                                                             x-bind:max="dashboardControls.date_end || '{{ $yesterdayDate }}'"
                                                             class="w-full"/>
                                        </div>
                                        <div>
                                            <span class="text-xs text-gray-500 dark:text-gray-400 mb-1 block">{{ __('End') }}</span>
                                            <x-ui.date-input x-model="dashboardControls.date_end" max="{{ $yesterdayDate }}"
                                                             class="w-full"/>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Card: Data & Granularity --}}
                            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm overflow-hidden flex-shrink-0">
                                <div class="flex items-center gap-2 px-6 py-3 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-gray-500 dark:text-gray-400">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6a7.5 7.5 0 1 0 7.5 7.5h-7.5V6Z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5H21A7.5 7.5 0 0 0 13.5 3v7.5Z"/>
                                    </svg>
                                    <span class="text-xs font-bold text-gray-800 dark:text-white uppercase tracking-wider">{{ __('Data Settings') }}</span>
                                </div>
                                <div class="p-6 space-y-4">
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">{{ __('Zero / Missing Data') }}</label>
                                        <x-ui.select-input x-model="dashboardControls.zero_handling" class="w-full">
                                            <x-ui.select-option value="remove">{{ __('Remove zeros from results') }}</x-ui.select-option>
                                            <x-ui.select-option value="keep">{{ __('Keep zeros in results') }}</x-ui.select-option>
                                            <x-ui.select-option value="trim">{{ __('Trim leading/trailing zeros') }}</x-ui.select-option>
                                        </x-ui.select-input>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">{{ __('Granularity') }}</label>
                                        <x-ui.select-input x-model="dashboardControls.granularity" class="w-full">
                                            <x-ui.select-option value="daily">{{ __('Daily') }}</x-ui.select-option>
                                            <x-ui.select-option value="weekly">{{ __('Weekly') }}</x-ui.select-option>
                                            <x-ui.select-option value="monthly">{{ __('Monthly') }}</x-ui.select-option>
                                            <x-ui.select-option value="query">{{ __('Query') }}</x-ui.select-option>
                                            <x-ui.select-option value="dimensions.page">{{ __('Page') }}</x-ui.select-option>
                                            <x-ui.select-option value="country">{{ __('Country') }}</x-ui.select-option>
                                            <x-ui.select-option value="device">{{ __('Device') }}</x-ui.select-option>
                                            <x-ui.select-option value="post">{{ __('Post') }}</x-ui.select-option>
                                        </x-ui.select-input>
                                    </div>
                                </div>
                            </div>

                            {{-- Card: Edge Cases --}}
                            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm overflow-hidden flex-shrink-0">
                                <div class="flex items-center gap-2 px-6 py-3 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-gray-500 dark:text-gray-400">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"/>
                                    </svg>
                                    <span class="text-xs font-bold text-gray-800 dark:text-white uppercase tracking-wider">{{ __('Edge Cases') }}</span>
                                </div>
                                <div class="p-6 space-y-4">
                                    <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300 cursor-pointer select-none">
                                        <input type="checkbox" x-model="dashboardControls.edge_case_weighted"
                                               class="rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500"/>
                                        <span class="text-xs font-medium">{{ __('Weighted regression (WLS)') }}</span>
                                    </label>
                                    <x-ui.select-input x-model="dashboardControls.edge_case_grouping" class="w-full">
                                        <x-ui.select-option value="none">{{ __('No grouping') }}</x-ui.select-option>
                                        <x-ui.select-option value="histogram">{{ __('Auto histogram-elbow') }}</x-ui.select-option>
                                        <x-ui.select-option value="percentile">{{ __('Bottom percentile') }}</x-ui.select-option>
                                    </x-ui.select-input>
                                </div>
                            </div>
                        </div>

                        {{-- Right Column: Asset Groups & Role-Based Permissions --}}
                        <div class="space-y-6">
                            {{-- Card: Asset Group --}}
                            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm overflow-visible relative z-20 flex-shrink-0">
                                <div class="flex items-center gap-2 px-6 py-3 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700 rounded-t-xl">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-gray-500 dark:text-gray-400">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 7.125C2.25 6.504 2.754 6 3.375 6h6c.621 0 1.125.504 1.125 1.125v2.25c0 .621-.504 1.125-1.125 1.125h-6A1.125 1.125 0 0 1 2.25 9.375v-2.25ZM2.25 14.625c0-.621.504-1.125 1.125-1.125h6c.621 0 1.125.504 1.125 1.125v2.25c0 .621-.504 1.125-1.125 1.125h-6a1.125 1.125 0 0 1-1.125-1.125v-2.25ZM13.5 7.125c0-.621.504-1.125 1.125-1.125h6c.621 0 1.125.504 1.125 1.125v2.25c0 .621-.504 1.125-1.125 1.125h-6A1.125 1.125 0 0 1 13.5 9.375v-2.25ZM13.5 14.625c0-.621.504-1.125 1.125-1.125h6c.621 0 1.125.504 1.125 1.125v2.25c0 .621-.504 1.125-1.125 1.125h-6a1.125 1.125 0 0 1-1.125-1.125v-2.25Z"/>
                                    </svg>
                                    <span class="text-xs font-bold text-gray-800 dark:text-white uppercase tracking-wider">{{ __('Asset Group') }}</span>
                                </div>
                                <div class="p-6 space-y-4">
                                    <p class="text-xs text-gray-500 dark:text-gray-400 leading-relaxed">{{ __('Filters available assets for widgets that don’t have their own asset group selected.') }}</p>
                                    
                                    <div class="w-full relative z-30">
                                        <x-ui.asset-selector model="dashboardControls.asset_group" options="assetGroups" changeEvent="" size="sm"/>
                                    </div>

                                    <label class="flex items-center gap-2 pt-1 text-sm text-gray-700 dark:text-gray-300 cursor-pointer select-none">
                                        <input type="checkbox" x-model="dashboardControls.show_asset_group_selector"
                                               class="rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500"/>
                                        <span class="text-xs text-gray-600 dark:text-gray-300">{{ __('Show this selector in the dashboard view') }}</span>
                                    </label>
                                </div>
                            </div>

                            {{-- Card: Role Permissions --}}
                            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm overflow-hidden flex-shrink-0">
                                <div class="flex items-center gap-2 px-6 py-3 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-gray-500 dark:text-gray-400">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"/>
                                    </svg>
                                    <span class="text-xs font-bold text-gray-800 dark:text-white uppercase tracking-wider">{{ __('Role Permissions') }}</span>
                                </div>
                                <div class="p-6 space-y-3">
                                    <p class="text-xs text-gray-500 dark:text-gray-400 leading-relaxed">
                                        {{ __('Configure feature capabilities by project role. Project Owners and Editors are enabled by default.') }}
                                    </p>

                                    <div class="w-full overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-sm">
                                        <table class="w-full table-auto divide-y divide-gray-200 dark:divide-gray-700 text-xs">
                                            <thead class="bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300 font-medium">
                                                <tr>
                                                    <th class="px-3 py-2.5 text-left font-medium">{{ __('Feature') }}</th>
                                                    <th class="px-2 py-2.5 text-center font-medium" title="{{ __('Always enabled') }}">{{ __('Owner') }}</th>
                                                    <th class="px-2 py-2.5 text-center font-medium" title="{{ __('Always enabled') }}">{{ __('Editor') }}</th>
                                                    <th class="px-2 py-2.5 text-center font-medium">{{ __('Viewer') }}</th>
                                                    <th class="px-2 py-2.5 text-center font-medium">{{ __('User') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-900">
                                                {{-- Row: Export PDF --}}
                                                <tr :class="!dashboardControls.allow_pdf_export ? 'opacity-60 bg-gray-50/50 dark:bg-gray-800/30' : ''">
                                                    <td class="px-3 py-2.5 text-gray-900 dark:text-gray-100 font-medium whitespace-nowrap">
                                                        <label class="flex items-center gap-2 cursor-pointer select-none">
                                                            <input type="checkbox" x-model="dashboardControls.allow_pdf_export"
                                                                   class="rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500"/>
                                                            <span class="text-xs font-semibold text-gray-900 dark:text-white">{{ __('Export PDF') }}</span>
                                                        </label>
                                                    </td>
                                                    <td class="px-2 py-2.5 text-center">
                                                        <input type="checkbox" checked disabled
                                                               class="rounded border-gray-300 dark:border-gray-600 text-gray-400 dark:text-gray-500 cursor-not-allowed opacity-60"/>
                                                    </td>
                                                    <td class="px-2 py-2.5 text-center">
                                                        <input type="checkbox" checked disabled
                                                               class="rounded border-gray-300 dark:border-gray-600 text-gray-400 dark:text-gray-500 cursor-not-allowed opacity-60"/>
                                                    </td>
                                                    <td class="px-2 py-2.5 text-center">
                                                        <input type="checkbox"
                                                               :disabled="!dashboardControls.allow_pdf_export"
                                                               :checked="(dashboardControls.pdf_export_roles || []).includes('project_viewer')"
                                                               @change="
                                                                   let roles = dashboardControls.pdf_export_roles || [];
                                                                   if ($event.target.checked) {
                                                                       if (!roles.includes('project_viewer')) roles.push('project_viewer');
                                                                   } else {
                                                                       roles = roles.filter(r => r !== 'project_viewer');
                                                                   }
                                                                   dashboardControls.pdf_export_roles = roles;
                                                               "
                                                               class="rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500 disabled:opacity-40 disabled:cursor-not-allowed"/>
                                                    </td>
                                                    <td class="px-2 py-2.5 text-center">
                                                        <input type="checkbox"
                                                               :disabled="!dashboardControls.allow_pdf_export"
                                                               :checked="(dashboardControls.pdf_export_roles || []).includes('project_user')"
                                                               @change="
                                                                   let roles = dashboardControls.pdf_export_roles || [];
                                                                   if ($event.target.checked) {
                                                                       if (!roles.includes('project_user')) roles.push('project_user');
                                                                   } else {
                                                                       roles = roles.filter(r => r !== 'project_user');
                                                                   }
                                                                   dashboardControls.pdf_export_roles = roles;
                                                               "
                                                               class="rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500 disabled:opacity-40 disabled:cursor-not-allowed"/>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ============================================================ --}}
        {{-- WIDGET-LEVEL CONTROLS MODAL                                 --}}
        {{-- ============================================================ --}}
        <div x-show="showWidgetControls"
             class="bd-modal-root fixed inset-0 z-[100] flex items-start justify-center pt-10 sm:pt-16"
             x-trap.noscroll="showWidgetControls"
             @keydown.escape.window="if (showWidgetControls && !showUnsavedWidgetControlsModal) { attemptCloseWidgetControls(); }">
            <div @click="attemptCloseWidgetControls()"
                 class="fixed inset-0 bg-black/50 dark:bg-black/70 backdrop-blur-sm transition-opacity"></div>
            <div
                @input="markWidgetControlsDirty()"
                @change="markWidgetControlsDirty()"
                class="relative bg-white dark:bg-gray-900 rounded-xl shadow-xl mx-auto my-4 sm:my-6 flex flex-col ring-1 ring-gray-900/5 dark:ring-white/10 bd-modal-panel">
                <div
                    class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-900 rounded-t-xl">
                    <div class="flex flex-col gap-1.5">
                        <div class="flex flex-wrap items-center gap-3">
                            <h3 class="text-base font-bold text-gray-900 dark:text-white"
                                x-text="'{{ __('Configure:') }} ' + (widgetControlsTarget.title || widgetControlsTarget.name)"></h3>

                            {{-- Data Source Badges --}}
                            <span
                                class="inline-flex items-center rounded-md bg-primary-50 dark:bg-primary-950/40 px-2.5 py-0.5 text-xs font-semibold text-primary-700 dark:text-primary-400 ring-1 ring-inset ring-primary-700/10 dark:ring-primary-400/20"
                                x-text="widgetControlsTarget.source_type === 'kpi' ? '{{ __('Custom KPI') }}' : widgetControlsTarget.source_type === 'derived_metric' ? '{{ __('Derived Metric') }}' : '{{ __('Metric') }}'"></span>

                            <template
                                x-if="widgetControlsTarget.source_type === 'kpi' && widgetControlsTarget.source_config?.custom_kpi_id">
                                <span
                                    class="inline-flex items-center rounded-md bg-gray-100 dark:bg-gray-800 px-2.5 py-0.5 text-xs font-medium text-gray-800 dark:text-gray-200 ring-1 ring-inset ring-gray-500/10 dark:ring-gray-400/20"
                                    x-text="kpis[widgetControlsTarget.source_config?.custom_kpi_id] ? kpis[widgetControlsTarget.source_config?.custom_kpi_id].name : ('{{ __('KPI ID:') }} ' + (widgetControlsTarget.source_config?.custom_kpi_id || ''))"></span>
                            </template>
                            <template
                                x-if="widgetControlsTarget.source_type === 'derived_metric' && widgetControlsTarget.source_config?.derived_metric_id">
                                <span
                                    class="inline-flex items-center rounded-md bg-gray-100 dark:bg-gray-800 px-2.5 py-0.5 text-xs font-medium text-gray-800 dark:text-gray-200 ring-1 ring-inset ring-gray-500/10 dark:ring-gray-400/20"
                                    x-text="derivedMetrics[widgetControlsTarget.source_config?.derived_metric_id] ? derivedMetrics[widgetControlsTarget.source_config?.derived_metric_id].name : ('{{ __('DM ID:') }} ' + (widgetControlsTarget.source_config?.derived_metric_id || ''))"></span>
                            </template>
                        </div>

                        <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                            <span class="inline-block w-1.5 h-1.5 rounded-full bg-green-400"
                                  x-show="!widgetHasCustomControls(widgetControlsTarget)"></span>
                            <span class="inline-block w-1.5 h-1.5 rounded-full bg-blue-400"
                                  x-show="widgetHasCustomControls(widgetControlsTarget)"></span>
                            <span
                                x-show="!widgetHasCustomControls(widgetControlsTarget)">{{ __('All controls inherited from dashboard defaults.') }}</span>
                            <span
                                x-show="widgetHasCustomControls(widgetControlsTarget)">{{ __('Some controls have custom overrides.') }}</span>
                            <button class="ml-2 text-primary-600 dark:text-primary-400 hover:underline font-medium"
                                    x-on:click="resetWidgetControls()">{{ __('Reset all to inherit') }}</button>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        {{-- Series Stepped Navigation Arrows (Visible when > 2 series) --}}
                        <div
                            x-show="(widgetControlsForm.raw_series || []).length > 2 && widgetControlsTarget.source_type !== 'kpi' && widgetControlsTarget.source_type !== 'derived_metric'"
                            class="hidden md:flex items-center gap-1 bg-gray-100 dark:bg-gray-800 p-1 rounded-lg border border-gray-200 dark:border-gray-700">
                            <span class="text-[11px] font-semibold text-gray-500 dark:text-gray-400 px-2 select-none">
                                <span x-text="(widgetControlsForm.raw_series || []).length"></span> {{ __('Series') }}
                            </span>
                            <button
                                type="button"
                                @click="scrollSeriesByStep(-1)"
                                :disabled="!canScrollSeriesLeft"
                                :title="'{{ __('Scroll left') }}'"
                                class="p-1 rounded-md transition-all"
                                :class="canScrollSeriesLeft
                                    ? 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 shadow-xs cursor-pointer'
                                    : 'text-gray-300 dark:text-gray-600 cursor-not-allowed opacity-40'">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"></path>
                                </svg>
                            </button>
                            <button
                                type="button"
                                @click="scrollSeriesByStep(1)"
                                :disabled="!canScrollSeriesRight"
                                :title="'{{ __('Scroll right') }}'"
                                class="p-1 rounded-md transition-all"
                                :class="canScrollSeriesRight
                                    ? 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 shadow-xs cursor-pointer'
                                    : 'text-gray-300 dark:text-gray-600 cursor-not-allowed opacity-40'">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </button>
                        </div>

                        <span x-show="widgetControlsError" x-cloak x-text="widgetControlsError"
                              class="text-xs text-red-600 dark:text-red-400 font-medium max-w-[200px] truncate"></span>

                        <button
                            type="button"
                            class="bd-modal-header-btn-cancel"
                            x-on:click="attemptCloseWidgetControls()">{{ __('Cancel') }}
                        </button>
                        <button
                            type="button"
                            class="bd-modal-header-btn-save"
                            x-on:click="confirmWidgetControls()">{{ __('Save Controls') }}
                        </button>

                        <button
                            type="button"
                            @click="attemptCloseWidgetControls()"
                            class="bd-modal-header-close"
                            :title="'{{ __('Close') }}'">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                                 stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <div
                    class="flex-1 bg-gray-50 dark:bg-gray-900 min-h-0 overflow-y-auto desktop-overflow-hidden relative flex flex-col rounded-b-xl">
                    {{-- Mobile Accordion Navigation Bar (Visible on mobile only) --}}
                    <div
                        class="md:hidden flex border-b border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 sticky top-0 z-20">
                        <button type="button" @click="activeMobileTab = 'config'"
                                class="flex-1 py-3 px-4 text-center text-xs font-bold uppercase tracking-wider transition-colors border-b-2"
                                :class="activeMobileTab === 'config' ? 'border-primary-500 text-primary-600 dark:text-primary-400 bg-primary-50/50 dark:bg-primary-950/20' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400'">
                            {{ __('1. Configuration') }}
                        </button>
                        <button type="button" @click="activeMobileTab = 'series'"
                                class="flex-1 py-3 px-4 text-center text-xs font-bold uppercase tracking-wider transition-colors border-b-2"
                                :class="activeMobileTab === 'series' ? 'border-primary-500 text-primary-600 dark:text-primary-400 bg-primary-50/50 dark:bg-primary-950/20' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400'">
                            {{ __('2. Series & Assets') }}
                        </button>
                    </div>

                    <div class="modal-body-absolute-wrapper flex flex-col md:flex-row gap-6 flex-1 min-h-0">
                        {{-- Left Column: Global Configuration --}}
                        <div
                            class="flex flex-col gap-6 overflow-y-auto custom-scrollbar pr-2 pb-2 h-full max-h-full min-h-0 bd-config-col"
                            :class="{ 'hidden md:flex': activeMobileTab !== 'config' }">

                            {{-- Card: Identity --}}
                            <div
                                class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm overflow-hidden flex-shrink-0">
                                <div
                                    class="flex items-center justify-between px-6 py-3 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700">
                                    <div class="flex items-center gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                             stroke-width="1.5" stroke="currentColor"
                                             class="w-4 h-4 text-gray-500 dark:text-gray-400">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                  d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z"/>
                                        </svg>
                                        <span
                                            class="text-xs font-bold text-gray-800 dark:text-white uppercase tracking-wider">{{ __('Identity') }}</span>
                                    </div>
                                    <div class="flex items-center gap-1 p-0.5 bg-gray-200 dark:bg-gray-800 rounded-lg text-xs font-bold select-none overflow-x-auto">
                                        <template x-for="(label, code) in availableLanguages" :key="code">
                                            <button type="button" @click="activeIdentityLang = code"
                                                    class="px-2.5 py-1 rounded-md transition-all uppercase tracking-wider whitespace-nowrap"
                                                    :class="activeIdentityLang === code ? 'bg-white dark:bg-gray-700 text-primary-600 dark:text-primary-400 shadow-sm' : 'text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'"
                                                    x-text="code">
                                            </button>
                                        </template>
                                    </div>
                                </div>
                                <div class="p-6 space-y-4">
                                    <template x-for="(label, code) in availableLanguages" :key="code">
                                        <div x-show="activeIdentityLang === code" class="space-y-4">
                                            <div>
                                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                                    {{ __('Widget Title') }} (<span x-text="code.toUpperCase()"></span>)
                                                    <span class="text-red-500">*</span>
                                                </label>
                                                <input type="text" x-model="(widgetControlsForm.titles = widgetControlsForm.titles || {})[code]"
                                                       class="w-full text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 py-2.5 px-4 focus:ring-primary-500 focus:border-primary-500"
                                                       :placeholder="'{{ __('Enter widget title') }} (' + label + ')'">
                                            </div>
                                            <div>
                                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                                    {{ __('Description') }} (<span x-text="code.toUpperCase()"></span>)
                                                    <span class="text-gray-400 font-normal">{{ __('(Optional)') }}</span>
                                                </label>
                                                <textarea x-model="(widgetControlsForm.descriptions = widgetControlsForm.descriptions || {})[code]" rows="2"
                                                          class="w-full text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 py-2.5 px-4 focus:ring-primary-500 focus:border-primary-500 resize-none custom-scrollbar"
                                                          :placeholder="'{{ __('Enter description') }} (' + label + ')...'"></textarea>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            {{-- Card: Chart Type --}}
                            <div
                                class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm overflow-hidden flex-shrink-0">
                                <div
                                    class="flex items-center gap-2 px-6 py-3 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                         stroke-width="1.5" stroke="currentColor"
                                         class="w-4 h-4 text-gray-500 dark:text-gray-400">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                              d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/>
                                    </svg>
                                    <span
                                        class="text-xs font-bold text-gray-800 dark:text-white uppercase tracking-wider">{{ __('Chart Type') }}</span>
                                </div>
                                <div class="p-6">
                                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                                        <template x-for="option in availableChartTypesForControls" :key="option.type">
                                            <button @click="widgetControlsForm.widget_type = option.type"
                                                    class="flex flex-col items-center gap-2 p-3 rounded-lg border-2 transition-all text-center"
                                                    :class="widgetControlsForm.widget_type === option.type
                                                    ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20 ring-1 ring-primary-500'
                                                    : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600 bg-white dark:bg-gray-800'">
                                                <div class="w-10 h-7" x-html="getWidgetSvg(option.type)"></div>
                                                <span
                                                    class="text-[11px] font-semibold text-gray-700 dark:text-gray-300 leading-tight"
                                                    x-text="option.label"></span>
                                            </button>
                                        </template>
                                    </div>
                                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-3">{{ __('Changing the chart type affects how data is displayed. Some types may not show all data dimensions.') }}</p>
                                </div>
                            </div>

                            {{-- Card: Date Range --}}
                            <div
                                class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm overflow-hidden flex-shrink-0">
                                <div
                                    class="flex items-center justify-between px-6 py-3 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700">
                                    <div class="flex items-center gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                             stroke-width="1.5" stroke="currentColor"
                                             class="w-4 h-4 text-gray-500 dark:text-gray-400">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                  d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/>
                                        </svg>
                                        <span
                                            class="text-xs font-bold text-gray-800 dark:text-white uppercase tracking-wider">{{ __('Date Range') }}</span>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" x-model="widgetControlsForm.date_inherit"
                                               class="sr-only peer"/>
                                        <div
                                            class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-gray-600 peer-checked:bg-primary-600"></div>
                                        <span
                                            class="ml-2 text-[10px] uppercase font-bold text-gray-500 dark:text-gray-400"
                                            x-text="widgetControlsForm.date_inherit ? '{{ __('Inherit') }}' : '{{ __('Custom') }}'"></span>
                                    </label>
                                </div>
                                <div class="p-6">
                                    <template x-if="widgetControlsForm.date_inherit">
                                        <div
                                            class="w-full text-sm text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-800 p-3 rounded-lg border border-gray-200 dark:border-gray-700"
                                            x-text="'{{ __('Inherited:') }} ' + ((((widgetKpiConfig && widgetKpiConfig.start_date) || dashboardControls.date_start) || '—')) + ' → ' + ((((widgetKpiConfig && widgetKpiConfig.end_date) || dashboardControls.date_end) || '—'))"></div>
                                    </template>
                                    <template x-if="!widgetControlsForm.date_inherit">
                                        <div class="w-full flex flex-row items-center gap-2">
                                            <input type="date" x-model="widgetControlsForm.date_start"
                                                   :min="dashboardControls.date_start || ''"
                                                   :max="widgetControlsForm.date_end || dashboardControls.date_end || '{{ date('Y-m-d', strtotime('-1 day')) }}'"
                                                   class="w-1/2 bg-white dark:bg-white/5 border border-gray-300 dark:border-white/10 text-gray-950 dark:text-white dark:[color-scheme:dark] text-xs rounded-lg focus:ring-primary-500 focus:border-primary-500 block px-2.5 py-2">
                                            <span class="text-gray-400 dark:text-gray-500 text-xs shrink-0">→</span>
                                            <input type="date" x-model="widgetControlsForm.date_end"
                                                   :min="widgetControlsForm.date_start || dashboardControls.date_start || ''"
                                                   :max="dashboardControls.date_end || '{{ date('Y-m-d', strtotime('-1 day')) }}'"
                                                   class="w-1/2 bg-white dark:bg-white/5 border border-gray-300 dark:border-white/10 text-gray-950 dark:text-white dark:[color-scheme:dark] text-xs rounded-lg focus:ring-primary-500 focus:border-primary-500 block px-2.5 py-2">
                                        </div>
                                    </template>
                                </div>
                            </div>

                            {{-- Card: Zero / Missing Data --}}
                            <div
                                class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm overflow-hidden flex-shrink-0">
                                <div
                                    class="flex items-center justify-between px-6 py-3 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700">
                                    <div class="flex items-center gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                             stroke-width="1.5" stroke="currentColor"
                                             class="w-4 h-4 text-gray-500 dark:text-gray-400">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                  d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                        </svg>
                                        <span
                                            class="text-xs font-bold text-gray-800 dark:text-white uppercase tracking-wider">{{ __('Zero / Missing Data') }}</span>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" x-model="widgetControlsForm.zero_inherit"
                                               class="sr-only peer"/>
                                        <div
                                            class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-gray-600 peer-checked:bg-primary-600"></div>
                                        <span
                                            class="ml-2 text-[10px] uppercase font-bold text-gray-500 dark:text-gray-400"
                                            x-text="widgetControlsForm.zero_inherit ? '{{ __('Inherit') }}' : '{{ __('Custom') }}'"></span>
                                    </label>
                                </div>
                                <div class="p-6">
                                    <template x-if="widgetControlsForm.zero_inherit">
                                        <div
                                            class="w-full text-sm text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-800 p-3 rounded-lg border border-gray-200 dark:border-gray-700"
                                            x-text="'{{ __('Inherited:') }} ' + (inheritedControlLabel('zero_handling', (widgetKpiConfig && widgetKpiConfig.zero_handling !== undefined ? widgetKpiConfig.zero_handling : dashboardControls.zero_handling)) || '{{ __('Remove zeros') }}')"></div>
                                    </template>
                                    <template x-if="!widgetControlsForm.zero_inherit">
                                        <x-ui.select-input x-model="widgetControlsForm.zero_handling" class="w-full">
                                            <x-ui.select-option
                                                value="remove">{{ __('Remove zeros from results') }}</x-ui.select-option>
                                            <x-ui.select-option
                                                value="keep">{{ __('Keep zeros in results') }}</x-ui.select-option>
                                            <x-ui.select-option
                                                value="trim">{{ __('Trim leading/trailing zeros') }}</x-ui.select-option>
                                        </x-ui.select-input>
                                    </template>
                                </div>
                            </div>

                            {{-- Card: Granularity --}}
                            <div
                                class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm overflow-hidden flex-shrink-0">
                                <div
                                    class="flex items-center justify-between px-6 py-3 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700">
                                    <div class="flex items-center gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                             stroke-width="1.5" stroke="currentColor"
                                             class="w-4 h-4 text-gray-500 dark:text-gray-400">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                  d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        <span
                                            class="text-xs font-bold text-gray-800 dark:text-white uppercase tracking-wider">{{ __('Granularity') }}</span>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" x-model="widgetControlsForm.granularity_inherit"
                                               class="sr-only peer"/>
                                        <div
                                            class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-gray-600 peer-checked:bg-primary-600"></div>
                                        <span
                                            class="ml-2 text-[10px] uppercase font-bold text-gray-500 dark:text-gray-400"
                                            x-text="widgetControlsForm.granularity_inherit ? '{{ __('Inherit') }}' : '{{ __('Custom') }}'"></span>
                                    </label>
                                </div>
                                <div class="p-6">
                                    <template x-if="widgetControlsForm.granularity_inherit">
                                        <div
                                            class="w-full text-sm text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-800 p-3 rounded-lg border border-gray-200 dark:border-gray-700"
                                            x-text="'{{ __('Inherited:') }} ' + (inheritedControlLabel('granularity', (widgetKpiConfig && widgetKpiConfig.granularity !== undefined ? widgetKpiConfig.granularity : dashboardControls.granularity)) || '{{ __('Default') }}')"></div>
                                    </template>
                                    <template x-if="!widgetControlsForm.granularity_inherit">
                                        <div class="flex flex-col gap-4">
                                            <!-- Dependency/Matrix Selector (KPI only) -->
                                            <template
                                                x-if="widgetControlsTarget && widgetControlsTarget.source_type === 'kpi' && Object.keys(availableDependencies).length > 0">
                                                <div>
                                                    <label
                                                        class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">{{ __('Data Scope / Matrix') }}</label>
                                                    <x-ui.select-input x-model="widgetControlsForm.dependency"
                                                                       @change="updateGranularities()" class="w-full">
                                                        <template x-for="(label, key) in availableDependencies"
                                                                  :key="key">
                                                            <x-ui.select-option x-bind:value="key"
                                                                                x-text="label"></x-ui.select-option>
                                                        </template>
                                                    </x-ui.select-input>
                                                </div>
                                            </template>

                                            <!-- Granularity Selector -->
                                            <div>
                                                <x-ui.select-input x-model="widgetControlsForm.granularity"
                                                                   @change="updateSeriesMetrics()" class="w-full">
                                                    <x-ui.select-option
                                                        value="daily">{{ __('Daily') }}</x-ui.select-option>
                                                    <x-ui.select-option
                                                        value="weekly">{{ __('Weekly') }}</x-ui.select-option>
                                                    <x-ui.select-option
                                                        value="monthly">{{ __('Monthly') }}</x-ui.select-option>
                                                    <x-ui.select-option
                                                        value="quarterly">{{ __('Quarterly') }}</x-ui.select-option>
                                                    <x-ui.select-option
                                                        value="semiannual">{{ __('Semiannual') }}</x-ui.select-option>
                                                    <x-ui.select-option
                                                        value="annually">{{ __('Annually') }}</x-ui.select-option>
                                                    <x-ui.select-option
                                                        value="lifetime">{{ __('Lifetime') }}</x-ui.select-option>
                                                </x-ui.select-input>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            {{-- Card: Edge Case Handling (KPI widgets only) --}}
                            <template x-if="widgetControlsTarget.source_type === 'kpi'">
                                <div
                                    class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm overflow-hidden flex-shrink-0">
                                    <div
                                        class="flex items-center justify-between px-6 py-3 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700">
                                        <div class="flex items-center gap-2">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                                 stroke-width="1.5" stroke="currentColor"
                                                 class="w-4 h-4 text-gray-500 dark:text-gray-400">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                      d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5"/>
                                            </svg>
                                            <span
                                                class="text-xs font-bold text-gray-800 dark:text-white uppercase tracking-wider">{{ __('Edge Cases') }}</span>
                                        </div>
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="checkbox" x-model="widgetControlsForm.edge_case_inherit"
                                                   class="sr-only peer"/>
                                            <div
                                                class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-gray-600 peer-checked:bg-primary-600"></div>
                                            <span
                                                class="ml-2 text-[10px] uppercase font-bold text-gray-500 dark:text-gray-400"
                                                x-text="widgetControlsForm.edge_case_inherit ? '{{ __('Inherit') }}' : '{{ __('Custom') }}'"></span>
                                        </label>
                                    </div>
                                    <div class="p-6 space-y-4">
                                        <template x-if="widgetControlsForm.edge_case_inherit">
                                            <div
                                                class="w-full text-sm text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-800 p-3 rounded-lg border border-gray-200 dark:border-gray-700">
                                                <span
                                                    x-text="`WLS: ${widgetControlsForm.edge_case_weighted ? '{{ __('On') }}' : '{{ __('Off') }}'}, {{ __('Grouping:') }} ${widgetControlsForm.edge_case_grouping === 'none' ? '{{ __('No grouping') }}' : widgetControlsForm.edge_case_grouping === 'histogram' ? '{{ __('Auto histogram-elbow') }}' : '{{ __('Bottom percentile') }}'}`"></span>
                                                <span
                                                    class="block text-xs text-gray-400 mt-1">{{ __('Inherited from KPI configuration') }}</span>
                                            </div>
                                        </template>
                                        <template x-if="!widgetControlsForm.edge_case_inherit">
                                            <div class="space-y-4">
                                                <label class="flex items-center gap-3 cursor-pointer">
                                                    <input type="checkbox"
                                                           x-model="widgetControlsForm.edge_case_weighted"
                                                           class="w-4 h-4 rounded border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-primary-600 focus:ring-primary-500">
                                                    <span
                                                        class="text-sm font-medium text-gray-900 dark:text-white">{{ __('Weighted regression (WLS)') }}</span>
                                                </label>
                                                <p class="text-xs text-gray-500 dark:text-gray-400 -mt-2">{{ __('Weight each dimension value by its volume so high-volume items influence the regression line proportionally more.') }}</p>
                                                <div>
                                                    <label
                                                        class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">{{ __('Group low-frequency values') }}</label>
                                                    <x-ui.select-input x-model="widgetControlsForm.edge_case_grouping"
                                                                       class="w-full">
                                                        <x-ui.select-option
                                                            value="none">{{ __('No grouping') }}</x-ui.select-option>
                                                        <x-ui.select-option
                                                            value="histogram">{{ __('Auto histogram-elbow') }}</x-ui.select-option>
                                                        <x-ui.select-option
                                                            value="percentile">{{ __('Bottom percentile') }}</x-ui.select-option>
                                                    </x-ui.select-input>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </template>

                            {{-- Card: Max Ratio (KPI widgets only) --}}
                            <template x-if="widgetControlsTarget.source_type === 'kpi'">
                                <div
                                    class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm overflow-hidden flex-shrink-0">
                                    <div
                                        class="flex items-center justify-between px-6 py-3 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700">
                                        <div class="flex items-center gap-2">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                                 stroke-width="1.5" stroke="currentColor"
                                                 class="w-4 h-4 text-gray-500 dark:text-gray-400">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                      d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5"/>
                                            </svg>
                                            <span
                                                class="text-xs font-bold text-gray-800 dark:text-white uppercase tracking-wider">{{ __('Max Ratio') }}</span>
                                        </div>
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="checkbox" x-model="widgetControlsForm.max_ratio_inherit"
                                                   class="sr-only peer"/>
                                            <div
                                                class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-gray-600 peer-checked:bg-primary-600"></div>
                                            <span
                                                class="ml-2 text-[10px] uppercase font-bold text-gray-500 dark:text-gray-400"
                                                x-text="widgetControlsForm.max_ratio_inherit ? '{{ __('Inherit') }}' : '{{ __('Custom') }}'"></span>
                                        </label>
                                    </div>
                                    <div class="p-6">
                                        <template x-if="widgetControlsForm.max_ratio_inherit">
                                            <div
                                                class="w-full text-sm text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-800 p-3 rounded-lg border border-gray-200 dark:border-gray-700">
                                                <span
                                                    x-text="widgetControlsForm.max_ratio !== null && widgetControlsForm.max_ratio !== undefined ? '{{ __('Cap at') }} ' + widgetControlsForm.max_ratio : '{{ __('No cap') }}'"></span>
                                                <span
                                                    class="block text-xs text-gray-400 mt-1">{{ __('Inherited from KPI configuration') }}</span>
                                            </div>
                                        </template>
                                        <template x-if="!widgetControlsForm.max_ratio_inherit">
                                            <div>
                                                <label
                                                    class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">{{ __('Value cap (null = no cap)') }}</label>
                                                <input type="number" step="0.01" min="0"
                                                       x-model="widgetControlsForm.max_ratio"
                                                       class="w-full text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 py-2.5 px-4 focus:ring-primary-500 focus:border-primary-500"
                                                       :placeholder="'{{ __('e.g. 1.0') }}'"/>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </template>

                            {{-- Card: Table Options (Table widgets only) --}}
                            <template x-if="widgetControlsTarget.widget_type === 'table'">
                                <div
                                    class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm overflow-hidden flex-shrink-0">
                                    <div
                                        class="flex items-center justify-between px-6 py-3 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700">
                                        <div class="flex items-center gap-2">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                                 stroke-width="1.5" stroke="currentColor"
                                                 class="w-4 h-4 text-gray-500 dark:text-gray-400">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                      d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/>
                                            </svg>
                                            <span
                                                class="text-xs font-bold text-gray-800 dark:text-white uppercase tracking-wider">{{ __('Table Options') }}</span>
                                        </div>
                                    </div>
                                    <div class="p-6">
                                        <label class="flex items-center gap-3 cursor-pointer">
                                            <input type="checkbox" x-model="widgetControlsForm.block_first_col"
                                                   class="w-4 h-4 rounded border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-primary-600 focus:ring-primary-500">
                                            <span
                                                class="text-sm font-medium text-gray-900 dark:text-white">{{ __('Freeze first column') }}</span>
                                        </label>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ __('Keep the first column fixed on the left while horizontal scrolling.') }}</p>
                                    </div>
                                </div>
                            </template>
                        </div>

                        {{-- Right Column: Variables Configuration --}}
                        <div
                            x-ref="seriesScrollContainer"
                            @scroll.passive="updateSeriesScrollState()"
                            class="min-w-0 min-h-0 flex overflow-x-auto gap-6 custom-scrollbar pb-2 items-stretch snap-x snap-mandatory bd-canvas-col"
                            :class="{ 'hidden md:flex': activeMobileTab !== 'series' }">

                            {{-- Series: Raw Metric --}}
                            <template
                                x-if="widgetControlsTarget.source_type !== 'kpi' && widgetControlsTarget.source_type !== 'derived_metric'">
                                <div style="display: contents">
                                    <template x-for="(series, index) in widgetControlsForm.raw_series" :key="index">
                                        <div
                                            class="flex-none shrink-0 h-full min-h-0 flex flex-col snap-start bd-series-card">
                                            <div
                                                class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm overflow-hidden flex flex-col h-full min-h-0">
                                                <div
                                                    class="flex items-center justify-between px-6 py-3 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700">
                                                    <div class="flex items-center gap-2">
                                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                             viewBox="0 0 24 24" stroke-width="1.5"
                                                             stroke="currentColor"
                                                             class="w-4 h-4 text-gray-500 dark:text-gray-400">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                  d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5m.75-9l3-3 2.148 2.148A12.061 12.061 0 0116.5 7.605"/>
                                                        </svg>
                                                        <span
                                                            class="text-xs font-bold text-gray-800 dark:text-white uppercase tracking-wider"
                                                            x-text="'{{ __('Series') }} ' + (index + 1)"></span>
                                                        <span
                                                            class="text-[10px] font-semibold px-2 py-0.5 rounded-full"
                                                            :class="series.type === 'derived_metric'
                                                                ? 'text-teal-700 dark:text-teal-300 bg-teal-50 dark:bg-teal-950/40 border border-teal-200 dark:border-teal-800/60'
                                                                : 'text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800/60'"
                                                            x-text="series.type === 'derived_metric' ? ('DM: ' + (series.dm_name || '')) : '{{ __('Metric') }}'"></span>
                                                    </div>
                                                    <button class="text-red-500 hover:text-red-700"
                                                            x-show="widgetControlsForm.raw_series.length > 1"
                                                            x-on:click="removeSeriesCard(index)">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                             viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                  stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                        </svg>
                                                    </button>
                                                </div>
                                                <div class="p-6 flex-1 flex flex-col gap-5 min-h-0">
                                                    {{-- Channel & Metric (Locked representation for DM Series) --}}
                                                    <template x-if="series.type === 'derived_metric'">
                                                        <div class="flex flex-col gap-4">
                                                            <div>
                                                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">{{ __('Channel') }}</label>
                                                                <div class="bd-dm-fixed-field">
                                                                    <span x-text="channels[series.channel] || series.channel || '—'"></span>
                                                                </div>
                                                            </div>
                                                            <div>
                                                                <div class="flex items-center justify-between mb-2">
                                                                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300">{{ __('Metric') }}</label>
                                                                    <template x-if="(widgetControlsForm.widget_type || widgetControlsTarget.widget_type) === 'combo_chart'">
                                                                        <button type="button"
                                                                                @click.stop="toggleRawMetricComboType(index, series.metrics?.[0] || 'dm')"
                                                                                :title="'{{ __('Switch between Bar and Line chart representation') }}'"
                                                                                class="bd-badge-combo text-[10px] font-bold py-0.5 rounded-md transition-all border shadow-xs"
                                                                                :class="getRawMetricComboType(index, series.metrics?.[0] || 'dm') === 'bar'
                                                                                    ? 'bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-300 border-blue-200 dark:border-blue-800'
                                                                                    : 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800'">
                                                                            <span x-text="getRawMetricComboType(index, series.metrics?.[0] || 'dm') === 'bar' ? '📊 {{ __('Bar') }}' : '📈 {{ __('Line') }}'"></span>
                                                                        </button>
                                                                    </template>
                                                                </div>
                                                                <div class="bd-dm-fixed-field">
                                                                    <span x-text="(allChannelMetrics[series.channel] || {})[series.metrics?.[0]] || series.metrics?.[0] || series.label || '—'"></span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </template>

                                                    {{-- Channel & Metric (Editable for Raw Metric Series) --}}
                                                    <template x-if="series.type !== 'derived_metric'">
                                                        <div class="flex flex-col gap-4">
                                                            <div>
                                                                <label
                                                                    class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">{{ __('Channel') }}</label>
                                                                <x-ui.select-input x-model="series.channel"
                                                                                   x-on:change="onWidgetRawChannelChange(index)"
                                                                                   x-init="$nextTick(() => { $el.value = series.channel })"
                                                                                   class="w-full">
                                                                    <x-ui.select-option
                                                                        value="">{{ __('Select a channel...') }}</x-ui.select-option>
                                                                    <template x-for="(label, key) in channels" :key="key">
                                                                        <x-ui.select-option x-bind:value="key"
                                                                                            x-text="label"></x-ui.select-option>
                                                                    </template>
                                                                </x-ui.select-input>
                                                            </div>

                                                            {{-- Data Scope / Matrix (Per-Series) --}}
                                                            <template x-if="series.channel && allChannelDependencies[series.channel] && Object.keys(allChannelDependencies[series.channel]).length > 0">
                                                                <div>
                                                                    <label
                                                                        class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">{{ __('Data Scope / Matrix') }}</label>
                                                                    <x-ui.select-input x-model="series.dependency"
                                                                                       x-on:change="onWidgetRawSeriesDependencyChange(index)"
                                                                                       x-init="$nextTick(() => { if (series.dependency) $el.value = series.dependency })"
                                                                                       class="w-full">
                                                                        <template x-for="(label, key) in allChannelDependencies[series.channel]" :key="key">
                                                                            <x-ui.select-option x-bind:value="key"
                                                                                                x-bind:selected="series.dependency === key"
                                                                                                x-text="label"></x-ui.select-option>
                                                                        </template>
                                                                    </x-ui.select-input>
                                                                </div>
                                                            </template>

                                                            <div class="my-1 flex flex-col shrink-0">
                                                                <div class="flex items-center justify-between mb-2">
                                                                    <label
                                                                        class="block text-xs font-semibold text-gray-700 dark:text-gray-300">{{ __('Metrics (Leave empty for All Metrics)') }}</label>
                                                                    <template x-if="series.channel">
                                                                        <div class="flex gap-3">
                                                                            <button @click="selectAllRawMetrics(index)"
                                                                                    class="text-[11px] font-semibold text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300 hover:underline">{{ __('Select All') }}</button>
                                                                            <button @click="clearAllRawMetrics(index)"
                                                                                    class="text-[11px] font-semibold text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:underline">{{ __('Clear') }}</button>
                                                                        </div>
                                                                    </template>
                                                                </div>
                                                                <div
                                                                    class="relative border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 shrink-0"
                                                                    style="height: 140px; min-height: 140px;">
                                                                    <div
                                                                        class="absolute inset-0 flex flex-col gap-1 overflow-y-auto p-1 custom-scrollbar">
                                                                        <template
                                                                            x-for="(label, key) in ((widgetControlsForm.series_metrics_map && widgetControlsForm.series_metrics_map[index]) || allChannelMetrics[series.channel] || {})"
                                                                            :key="key">
                                                                            <div
                                                                                class="flex items-center justify-between px-3 py-2 text-sm rounded-md transition-colors border border-transparent"
                                                                                :class="(series.allowed_metrics && series.allowed_metrics.length > 0 ? series.allowed_metrics.includes(key) : true) ? 'bg-gray-50/70 dark:bg-white/[0.03]' : 'opacity-60 hover:opacity-100 hover:bg-gray-100 dark:hover:bg-white/5'">
                                                                                <div @click="toggleRawMetricIncluded(index, key)"
                                                                                     class="flex gap-x-3 items-center cursor-pointer flex-1 min-w-0">
                                                                                    <div
                                                                                        class="w-4 h-4 shrink-0 flex items-center justify-center rounded border transition-colors"
                                                                                        :class="(series.allowed_metrics && series.allowed_metrics.length > 0 ? series.allowed_metrics.includes(key) : false) ? 'bg-primary-600 border-primary-600' : 'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800'">
                                                                                        <svg
                                                                                            x-show="series.allowed_metrics && series.allowed_metrics.includes(key)"
                                                                                            class="w-3 h-3 text-white" fill="none"
                                                                                            viewBox="0 0 24 24" stroke-width="3"
                                                                                            stroke="currentColor">
                                                                                            <path stroke-linecap="round"
                                                                                                  stroke-linejoin="round"
                                                                                                  d="m4.5 12.75 6 6 9-13.5"/>
                                                                                        </svg>
                                                                                    </div>
                                                                                    <span class="truncate font-medium text-gray-700 dark:text-gray-200"
                                                                                          :class="(series.allowed_metrics && series.allowed_metrics.includes(key)) ? 'text-primary-900 dark:text-primary-100 font-semibold' : ''"
                                                                                          x-text="label"></span>
                                                                                </div>
                                                                                {{-- Badge for Default Active on Load --}}
                                                                                 <template x-if="series.allowed_metrics && series.allowed_metrics.includes(key)">
                                                                                     <div class="flex items-center gap-1.5 shrink-0">
                                                                                         {{-- Combo Chart Type Toggle (Bar / Line) --}}
                                                                                         <template x-if="(widgetControlsForm.widget_type || widgetControlsTarget.widget_type) === 'combo_chart'">
                                                                                             <button type="button"
                                                                                                     @click.stop="toggleRawMetricComboType(index, key)"
                                                                                                     :title="'{{ __('Switch between Bar and Line chart representation') }}'"
                                                                                                     class="bd-badge-combo text-[10px] font-bold py-0.5 rounded-md transition-all border shadow-xs"
                                                                                                     :class="getRawMetricComboType(index, key) === 'bar'
                                                                                                         ? 'bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-300 border-blue-200 dark:border-blue-800'
                                                                                                         : 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800'">
                                                                                                 <span x-text="getRawMetricComboType(index, key) === 'bar' ? '📊 {{ __('Bar') }}' : '📈 {{ __('Line') }}'"></span>
                                                                                             </button>
                                                                                         </template>

                                                                                         <button type="button"
                                                                                                @click.stop="toggleRawMetricDefaultActive(index, key)"
                                                                                                :title="(series.metrics || []).includes(key) ? '{{ __('Active by default on widget load') }}' : '{{ __('Available (inactive on load)') }}'"
                                                                                                class="bd-badge-active text-[10px] font-semibold py-0.5 rounded-full transition-all"
                                                                                                :class="(series.metrics || []).includes(key)
                                                                                                    ? 'bg-primary-100 dark:bg-primary-900/50 text-primary-700 dark:text-primary-300 border border-primary-300 dark:border-primary-700'
                                                                                                    : 'bg-gray-100 dark:bg-gray-700 text-gray-400 dark:text-gray-400 border border-gray-200 dark:border-gray-600 hover:text-gray-600 dark:hover:text-gray-200'">
                                                                                            <span x-text="(series.metrics || []).includes(key) ? '★ {{ __('Active') }}' : '☆ {{ __('Available') }}'"></span>
                                                                                        </button>
                                                                                    </div>
                                                                                </template>
                                                                            </div>
                                                                        </template>
                                                                        <template
                                                                            x-if="!series.channel || Object.keys(((widgetControlsForm.series_metrics_map && widgetControlsForm.series_metrics_map[index]) || allChannelMetrics[series.channel] || {})).length === 0">
                                                                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-2 mx-2">{{ __('Select a channel first.') }}</p>
                                                                        </template>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </template>

                                                    <div class="gap-3 flex-1 flex flex-col min-h-0 mt-3">
                                                        <div class="flex items-center justify-between">
                                                            <label
                                                                class="block text-xs font-semibold text-gray-700 dark:text-gray-300">{{ __('Assets (Leave empty for All Assets)') }}</label>
                                                            <template x-if="series.channel">
                                                                <div class="flex gap-3">
                                                                    <button @click="selectAllRawAssets(index)"
                                                                            class="text-[11px] font-semibold text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300 hover:underline">{{ __('Select All') }}</button>
                                                                    <button @click="clearAllRawAssets(index)"
                                                                            class="text-[11px] font-semibold text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:underline">{{ __('Clear') }}</button>
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
                                                            <input type="text" x-model="searchQueries['raw_' + index]"
                                                                   :placeholder="'{{ __('Search assets...') }}'"
                                                                   class="bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 bd-search-input">
                                                        </div>
                                                        <div class="flex-1 relative min-h-0">
                                                            <div
                                                                class="absolute inset-0 flex flex-col gap-1 overflow-y-auto pr-1 custom-scrollbar">
                                                                <template
                                                                    x-for="(name, id) in allChannelAssets[series.channel] || {}"
                                                                    :key="id">
                                                                    <div
                                                                        x-show="(isAssetAllowedByGroups(null, series.channel, id)) && ((searchQueries['raw_' + index] || '') === '' || name.toLowerCase().includes((searchQueries['raw_' + index] || '').toLowerCase()))"
                                                                        class="flex items-center justify-between px-3 py-2 text-sm rounded-md transition-colors border border-transparent"
                                                                        :class="(series.allowed_assets && series.allowed_assets.length > 0 ? series.allowed_assets.includes(String(id)) : (series.assets && series.assets.length > 0 ? series.assets.includes(String(id)) : false)) ? 'bg-gray-50/70 dark:bg-white/[0.03]' : 'opacity-60 hover:opacity-100 hover:bg-gray-100 dark:hover:bg-white/5'">
                                                                        <div @click="toggleRawAssetIncluded(index, id)"
                                                                             class="flex gap-x-3 items-center cursor-pointer flex-1 min-w-0">
                                                                            <div
                                                                                class="w-4 h-4 shrink-0 flex items-center justify-center rounded border transition-colors"
                                                                                :class="(series.allowed_assets && series.allowed_assets.length > 0 ? series.allowed_assets.includes(String(id)) : (series.assets && series.assets.length > 0 ? series.assets.includes(String(id)) : false)) ? 'bg-primary-600 border-primary-600' : 'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800'">
                                                                                <svg
                                                                                    x-show="series.allowed_assets && series.allowed_assets.length > 0 ? series.allowed_assets.includes(String(id)) : (series.assets && series.assets.length > 0 ? series.assets.includes(String(id)) : false)"
                                                                                    class="w-3 h-3 text-white" fill="none"
                                                                                    viewBox="0 0 24 24" stroke-width="3"
                                                                                    stroke="currentColor">
                                                                                    <path stroke-linecap="round"
                                                                                          stroke-linejoin="round"
                                                                                          d="m4.5 12.75 6 6 9-13.5"/>
                                                                                </svg>
                                                                            </div>
                                                                            <span class="truncate font-medium text-gray-700 dark:text-gray-200"
                                                                                  :class="(series.allowed_assets && series.allowed_assets.length > 0 ? series.allowed_assets.includes(String(id)) : (series.assets && series.assets.length > 0 ? series.assets.includes(String(id)) : false)) ? 'text-primary-900 dark:text-primary-100 font-semibold' : ''"
                                                                                  x-text="name"></span>
                                                                        </div>
                                                                        {{-- Badge for Default Active on Load --}}
                                                                        <template x-if="series.allowed_assets && series.allowed_assets.includes(String(id))">
                                                                            <div class="flex items-center gap-1.5 shrink-0 ml-2">
                                                                                <button type="button"
                                                                                        @click.stop="toggleRawAssetDefaultActive(index, id)"
                                                                                        :title="(series.assets || []).includes(String(id)) ? '{{ __('Active by default on widget load') }}' : '{{ __('Available (inactive on load)') }}'"
                                                                                        class="bd-badge-active text-[10px] font-semibold py-0.5 rounded-full transition-all"
                                                                                        :class="(series.assets || []).includes(String(id))
                                                                                            ? 'bg-primary-100 dark:bg-primary-900/50 text-primary-700 dark:text-primary-300 border border-primary-300 dark:border-primary-700'
                                                                                            : 'bg-gray-100 dark:bg-gray-700 text-gray-400 dark:text-gray-400 border border-gray-200 dark:border-gray-600 hover:text-gray-600 dark:hover:text-gray-200'">
                                                                                    <span x-text="(series.assets || []).includes(String(id)) ? '★ {{ __('Active') }}' : '☆ {{ __('Available') }}'"></span>
                                                                                </button>
                                                                            </div>
                                                                        </template>
                                                                    </div>
                                                                </template>
                                                                <template
                                                                    x-if="!series.channel || Object.keys(allChannelAssets[series.channel] || {}).length === 0">
                                                                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">{{ __('Select a channel first.') }}</p>
                                                                </template>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </template>

                            {{-- Variables: Assets per variable (KPI) --}}
                            <template
                                x-if="widgetControlsTarget.source_type === 'kpi' && widgetKpiConfig.dependent_channel && !widgetKpiConfig.dependent_dm_id">
                                <div
                                    class="flex-none w-full sm:w-[calc(50%-0.75rem)] md:w-full min-w-[280px] h-full min-h-0 flex flex-col snap-start">
                                    <div
                                        class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm overflow-hidden flex flex-col h-full min-h-0">
                                        <div
                                            class="flex items-center justify-between px-6 py-3 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700">
                                            <div class="flex items-center gap-2">
                                                <span
                                                    class="text-xs font-bold text-gray-800 dark:text-white uppercase tracking-wider">{{ __('Dependent Series') }}</span>
                                                <span
                                                    class="text-[10px] font-semibold text-primary-600 dark:text-primary-400 bg-primary-50 dark:bg-primary-950/40 border border-primary-200 dark:border-primary-800/60 px-2 py-0.5 rounded-full">KPI</span>
                                            </div>
                                            <div class="flex flex-col items-end gap-1">
                                                    <span
                                                        class="text-[10px] font-semibold text-gray-600 dark:text-gray-300 bg-gray-200 dark:bg-gray-700 px-2.5 py-1 rounded-full"
                                                        x-text="channels[widgetKpiConfig.dependent_channel]"></span>
                                                <template x-if="widgetKpiConfig.dependent_metric">
                                                        <span
                                                            class="text-[10px] font-medium text-gray-500 dark:text-gray-400"
                                                            x-text="(allChannelMetrics[widgetKpiConfig.dependent_channel] || {})[widgetKpiConfig.dependent_metric] || widgetKpiConfig.dependent_metric"></span>
                                                </template>
                                                <template x-if="!widgetKpiConfig.dependent_metric">
                                                        <span
                                                            class="text-[10px] font-semibold text-amber-600 dark:amber-400 bg-amber-100 dark:bg-amber-900/30 px-2.5 py-1 rounded-full">{{ __('Dynamic Metric') }}</span>
                                                </template>
                                            </div>
                                        </div>
                                        <div class="p-6 flex-1 flex flex-col gap-5 min-h-0">

                                            {{-- Data Scope / Matrix (Per-Series for KPI Dependent) --}}
                                            <template x-if="widgetKpiConfig.dependent_channel && allChannelDependencies[widgetKpiConfig.dependent_channel] && Object.keys(allChannelDependencies[widgetKpiConfig.dependent_channel]).length > 0">
                                                <div>
                                                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('Data Scope / Matrix') }}</label>
                                                    <select x-model="widgetControlsForm.series_dependencies.dependent"
                                                            class="bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5">
                                                        <template x-for="(label, key) in allChannelDependencies[widgetKpiConfig.dependent_channel]" :key="key">
                                                            <option :value="key" x-text="label" :selected="widgetControlsForm.series_dependencies.dependent === key"></option>
                                                        </template>
                                                    </select>
                                                </div>
                                            </template>

                                            <template x-if="!widgetKpiConfig.dependent_metric">
                                                <div>
                                                    <label
                                                        class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('Metric') }}</label>
                                                    <select x-model="widgetControlsForm.metrics[0]"
                                                            class="bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5">
                                                        <option value="">{{ __('Select a metric...') }}</option>
                                                        <template
                                                            x-for="(label, key) in allChannelMetrics[widgetKpiConfig.dependent_channel] || {}"
                                                            :key="key">
                                                            <option :value="key" x-text="label"></option>
                                                        </template>
                                                    </select>
                                                </div>
                                            </template>

                                            <div class="gap-3 flex-1 flex flex-col min-h-0 mt-6">
                                                <div class="flex items-center justify-between mt-2">
                                                    <label
                                                        class="block text-xs font-semibold text-gray-700 dark:text-gray-300">{{ __('Assets') }}
                                                        <span>({{ __('Leave empty for All Assets') }})</span></label>
                                                    <div class="flex gap-3">
                                                        <button
                                                            @click="selectAllKpiAssets('dependent', widgetKpiConfig.dependent_channel)"
                                                            class="text-[11px] font-semibold text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300 hover:underline">{{ __('Select All') }}</button>
                                                        <button @click="clearAllKpiAssets('dependent')"
                                                                class="text-[11px] font-semibold text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:underline">{{ __('Clear') }}</button>
                                                    </div>
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
                                                    <input type="text" x-model="searchQueries['dependent']"
                                                           :placeholder="'{{ __('Search assets...') }}'"
                                                           class="bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 bd-search-input">
                                                </div>
                                                <div class="flex-1 relative min-h-0">
                                                    <div
                                                        class="absolute inset-0 flex flex-col gap-1 overflow-y-auto pr-1 custom-scrollbar">
                                                        <template
                                                            x-for="(name, id) in allChannelAssets[widgetKpiConfig.dependent_channel] || {}"
                                                            :key="id">
                                                            <div
                                                                x-show="isAssetAllowedByGroups('dependent', widgetKpiConfig.dependent_channel, id) && ((searchQueries['dependent'] || '') === '' || name.toLowerCase().includes((searchQueries['dependent'] || '').toLowerCase()))"
                                                                class="flex items-center justify-between px-3 py-2 text-sm rounded-md transition-colors border border-transparent"
                                                                :class="((widgetControlsForm.series_allowed_assets && widgetControlsForm.series_allowed_assets.dependent) ? widgetControlsForm.series_allowed_assets.dependent.includes(String(id)) : (widgetControlsForm.series_assets.dependent || []).includes(String(id))) ? 'bg-gray-50/70 dark:bg-white/[0.03]' : 'opacity-60 hover:opacity-100 hover:bg-gray-100 dark:hover:bg-white/5'">
                                                                <div @click="toggleKpiAssetIncluded('dependent', id)"
                                                                     class="flex gap-x-3 items-center cursor-pointer flex-1 min-w-0">
                                                                    <div
                                                                        class="w-4 h-4 shrink-0 flex items-center justify-center rounded border transition-colors"
                                                                        :class="((widgetControlsForm.series_allowed_assets && widgetControlsForm.series_allowed_assets.dependent) ? widgetControlsForm.series_allowed_assets.dependent.includes(String(id)) : (widgetControlsForm.series_assets.dependent || []).includes(String(id))) ? 'bg-primary-600 border-primary-600' : 'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800'">
                                                                        <svg
                                                                            x-show="((widgetControlsForm.series_allowed_assets && widgetControlsForm.series_allowed_assets.dependent) ? widgetControlsForm.series_allowed_assets.dependent.includes(String(id)) : (widgetControlsForm.series_assets.dependent || []).includes(String(id)))"
                                                                            class="w-3 h-3 text-white" fill="none"
                                                                            viewBox="0 0 24 24" stroke-width="3"
                                                                            stroke="currentColor">
                                                                            <path stroke-linecap="round"
                                                                                  stroke-linejoin="round"
                                                                                  d="m4.5 12.75 6 6 9-13.5"/>
                                                                        </svg>
                                                                    </div>
                                                                    <span class="truncate font-medium text-gray-700 dark:text-gray-200"
                                                                          :class="((widgetControlsForm.series_allowed_assets && widgetControlsForm.series_allowed_assets.dependent) ? widgetControlsForm.series_allowed_assets.dependent.includes(String(id)) : (widgetControlsForm.series_assets.dependent || []).includes(String(id))) ? 'text-primary-900 dark:text-primary-100 font-semibold' : ''"
                                                                          x-text="name"></span>
                                                                </div>
                                                                {{-- Badge for Default Active on Load --}}
                                                                <template x-if="widgetControlsForm.series_allowed_assets && widgetControlsForm.series_allowed_assets.dependent && widgetControlsForm.series_allowed_assets.dependent.includes(String(id))">
                                                                    <div class="flex items-center gap-1.5 shrink-0 ml-2">
                                                                        <button type="button"
                                                                                @click.stop="toggleKpiAssetDefaultActive('dependent', id)"
                                                                                :title="(widgetControlsForm.series_assets.dependent || []).includes(String(id)) ? '{{ __('Active by default on widget load') }}' : '{{ __('Available (inactive on load)') }}'"
                                                                                class="bd-badge-active text-[10px] font-semibold py-0.5 rounded-full transition-all"
                                                                                :class="(widgetControlsForm.series_assets.dependent || []).includes(String(id))
                                                                                    ? 'bg-primary-100 dark:bg-primary-900/50 text-primary-700 dark:text-primary-300 border border-primary-300 dark:border-primary-700'
                                                                                    : 'bg-gray-100 dark:bg-gray-700 text-gray-400 dark:text-gray-400 border border-gray-200 dark:border-gray-600 hover:text-gray-600 dark:hover:text-gray-200'">
                                                                            <span x-text="(widgetControlsForm.series_assets.dependent || []).includes(String(id)) ? '★ {{ __('Active') }}' : '☆ {{ __('Available') }}'"></span>
                                                                        </button>
                                                                    </div>
                                                                </template>
                                                            </div>
                                                        </template>
                                                        <template
                                                            x-if="!allChannelAssets[widgetKpiConfig.dependent_channel] || Object.keys(allChannelAssets[widgetKpiConfig.dependent_channel]).length === 0">
                                                            <p class="text-xs text-gray-400 dark:text-gray-500">{{ __('No assets loaded for this channel.') }}</p>
                                                        </template>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            {{-- Dependent Derived Metric source series (KPI) --}}
                            <template
                                x-if="widgetControlsTarget.source_type === 'kpi' && widgetKpiConfig.dependent_dm_id">
                                <template
                                    x-for="(series, sIdx) in ((derivedMetrics[widgetKpiConfig.dependent_dm_id] && derivedMetrics[widgetKpiConfig.dependent_dm_id].source_series) || [])"
                                    :key="sIdx">
                                    <div
                                        class="flex-none w-full sm:w-[calc(50%-0.75rem)] md:w-full min-w-[280px] h-full min-h-0 flex flex-col snap-start">
                                        <div
                                            class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm overflow-hidden flex flex-col h-full min-h-0">
                                            <div
                                                class="flex items-center justify-between px-6 py-3 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700">
                                                <div class="flex items-center gap-2">
                                                    <span
                                                        class="text-xs font-bold text-gray-800 dark:text-white uppercase tracking-wider"
                                                        x-text="series.label || ('{{ __('Source') }} ' + String.fromCharCode(97 + sIdx))"></span>
                                                    <span
                                                        class="text-[10px] font-semibold text-teal-600 dark:text-teal-400 bg-teal-50 dark:bg-teal-950/40 border border-teal-200 dark:border-teal-800/60 px-2 py-0.5 rounded-full">DM</span>
                                                    <template x-if="derivedMetrics[widgetKpiConfig.dependent_dm_id]">
                                                        <span
                                                            class="text-[10px] font-medium text-teal-700 dark:text-teal-300 bg-teal-100/60 dark:bg-teal-900/40 px-2 py-0.5 rounded-md truncate max-w-[130px]"
                                                            x-text="derivedMetrics[widgetKpiConfig.dependent_dm_id].name"></span>
                                                    </template>
                                                </div>
                                                <span
                                                    class="text-[10px] font-semibold text-gray-600 dark:text-gray-300 bg-gray-200 dark:bg-gray-700 px-2.5 py-1 rounded-full"
                                                    x-text="channels[series.channel] || series.channel"></span>
                                            </div>
                                            <div class="p-6 flex-1 flex flex-col gap-5 min-h-0">
                                                <div>
                                                    <label
                                                        class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('Metric') }}</label>
                                                    <p class="text-sm text-gray-900 dark:text-white"
                                                       x-text="series.metric"></p>
                                                </div>

                                                <div class="gap-3 flex-1 flex flex-col min-h-0 mt-6">
                                                    <div class="flex items-center justify-between">
                                                        <label
                                                            class="block text-xs font-semibold text-gray-700 dark:text-gray-300">{{ __('Asset Override') }}
                                                            <span class="text-gray-400 font-normal">({{ __('leave empty for DM default') }})</span></label>
                                                        <div class="flex gap-3">
                                                            <button
                                                                @click="selectAllKpiAssets('dep_dm_' + sIdx, series.channel)"
                                                                class="text-[11px] font-semibold text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300 hover:underline">{{ __('Select All') }}</button>
                                                            <button @click="clearAllKpiAssets('dep_dm_' + sIdx)"
                                                                    class="text-[11px] font-semibold text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:underline">{{ __('Clear') }}</button>
                                                        </div>
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
                                                        <input type="text"
                                                               x-model="searchQueries['dep_dm_' + sIdx]"
                                                               :placeholder="'{{ __('Search assets...') }}'"
                                                               class="bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 bd-search-input">
                                                    </div>
                                                    <div class="flex-1 relative min-h-0">
                                                        <div
                                                            class="absolute inset-0 flex flex-col gap-1 overflow-y-auto pr-1 custom-scrollbar">
                                                            <template
                                                                x-for="(name, id) in allChannelAssets[series.channel] || {}"
                                                                :key="id">
                                                                <div
                                                                    x-show="(searchQueries['dep_dm_' + sIdx] || '') === '' || name.toLowerCase().includes((searchQueries['dep_dm_' + sIdx] || '').toLowerCase())"
                                                                    class="flex items-center justify-between px-3 py-2 text-sm rounded-md transition-colors border border-transparent"
                                                                    :class="((widgetControlsForm.series_allowed_assets && widgetControlsForm.series_allowed_assets['dep_dm_' + sIdx]) ? widgetControlsForm.series_allowed_assets['dep_dm_' + sIdx].includes(String(id)) : (widgetControlsForm.series_assets['dep_dm_' + sIdx] || []).includes(String(id))) ? 'bg-gray-50/70 dark:bg-white/[0.03]' : 'opacity-60 hover:opacity-100 hover:bg-gray-100 dark:hover:bg-white/5'">
                                                                    <div @click="toggleKpiAssetIncluded('dep_dm_' + sIdx, id)"
                                                                         class="flex gap-x-3 items-center cursor-pointer flex-1 min-w-0">
                                                                        <div
                                                                            class="w-4 h-4 shrink-0 flex items-center justify-center rounded border transition-colors"
                                                                            :class="((widgetControlsForm.series_allowed_assets && widgetControlsForm.series_allowed_assets['dep_dm_' + sIdx]) ? widgetControlsForm.series_allowed_assets['dep_dm_' + sIdx].includes(String(id)) : (widgetControlsForm.series_assets['dep_dm_' + sIdx] || []).includes(String(id))) ? 'bg-primary-600 border-primary-600' : 'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800'">
                                                                            <svg
                                                                                x-show="((widgetControlsForm.series_allowed_assets && widgetControlsForm.series_allowed_assets['dep_dm_' + sIdx]) ? widgetControlsForm.series_allowed_assets['dep_dm_' + sIdx].includes(String(id)) : (widgetControlsForm.series_assets['dep_dm_' + sIdx] || []).includes(String(id)))"
                                                                                class="w-3 h-3 text-white" fill="none"
                                                                                viewBox="0 0 24 24" stroke-width="3"
                                                                                stroke="currentColor">
                                                                                <path stroke-linecap="round"
                                                                                      stroke-linejoin="round"
                                                                                      d="m4.5 12.75 6 6 9-13.5"/>
                                                                            </svg>
                                                                        </div>
                                                                        <span class="truncate font-medium text-gray-700 dark:text-gray-200"
                                                                              :class="((widgetControlsForm.series_allowed_assets && widgetControlsForm.series_allowed_assets['dep_dm_' + sIdx]) ? widgetControlsForm.series_allowed_assets['dep_dm_' + sIdx].includes(String(id)) : (widgetControlsForm.series_assets['dep_dm_' + sIdx] || []).includes(String(id))) ? 'text-primary-900 dark:text-primary-100 font-semibold' : ''"
                                                                              x-text="name"></span>
                                                                    </div>
                                                                    {{-- Badge for Default Active on Load --}}
                                                                    <template x-if="widgetControlsForm.series_allowed_assets && widgetControlsForm.series_allowed_assets['dep_dm_' + sIdx] && widgetControlsForm.series_allowed_assets['dep_dm_' + sIdx].includes(String(id))">
                                                                        <div class="flex items-center gap-1.5 shrink-0 ml-2">
                                                                            <button type="button"
                                                                                    @click.stop="toggleKpiAssetDefaultActive('dep_dm_' + sIdx, id)"
                                                                                    :title="(widgetControlsForm.series_assets['dep_dm_' + sIdx] || []).includes(String(id)) ? '{{ __('Active by default on widget load') }}' : '{{ __('Available (inactive on load)') }}'"
                                                                                    class="bd-badge-active text-[10px] font-semibold py-0.5 rounded-full transition-all"
                                                                                    :class="(widgetControlsForm.series_assets['dep_dm_' + sIdx] || []).includes(String(id))
                                                                                        ? 'bg-primary-100 dark:bg-primary-900/50 text-primary-700 dark:text-primary-300 border border-primary-300 dark:border-primary-700'
                                                                                        : 'bg-gray-100 dark:bg-gray-700 text-gray-400 dark:text-gray-400 border border-gray-200 dark:border-gray-600 hover:text-gray-600 dark:hover:text-gray-200'">
                                                                                <span x-text="(widgetControlsForm.series_assets['dep_dm_' + sIdx] || []).includes(String(id)) ? '★ {{ __('Active') }}' : '☆ {{ __('Available') }}'"></span>
                                                                            </button>
                                                                        </div>
                                                                    </template>
                                                                </div>
                                                            </template>
                                                            <template
                                                                x-if="!series.channel || Object.keys(allChannelAssets[series.channel] || {}).length === 0">
                                                                <p class="text-xs text-gray-400 dark:text-gray-500">{{ __('No assets loaded for this channel.') }}</p>
                                                            </template>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </template>

                            <template x-if="widgetControlsTarget.source_type === 'kpi'">
                                <template x-if="widgetKpiConfig.independent_variables">
                                    <template x-for="(varCfg, idx) in widgetKpiConfig.independent_variables" :key="idx">
                                        <template
                                            x-if="varCfg.independent_dm_id || varCfg.independent_channel">
                                            <div style="display: contents">
                                                <template x-if="varCfg.independent_dm_id">
                                                    <template
                                                        x-for="(series, sIdx) in ((derivedMetrics[varCfg.independent_dm_id] && derivedMetrics[varCfg.independent_dm_id].source_series) || [])"
                                                        :key="sIdx">
                                                        <div
                                                            class="flex-none w-full h-full min-h-0 flex flex-col snap-start"
                                                            :class="{
                                                         'md:w-full': Object.keys(widgetKpiConfig.independent_variables || {}).length === 1,
                                                         'sm:w-[calc(50%-0.75rem)]': Object.keys(widgetKpiConfig.independent_variables || {}).length >= 2
                                                     }">
                                                            <div
                                                                class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm overflow-hidden flex flex-col h-full min-h-0">
                                                                <div
                                                                    class="flex items-center justify-between px-6 py-3 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700">
                                                                    <div class="flex items-center gap-2">
                                                                        <span
                                                                            class="text-xs font-bold text-gray-800 dark:text-white uppercase tracking-wider"
                                                                            x-text="series.label || ('{{ __('Source') }} ' + String.fromCharCode(97 + sIdx))"></span>
                                                                        <span
                                                                            class="text-[10px] font-semibold text-teal-600 dark:text-teal-400 bg-teal-50 dark:bg-teal-950/40 border border-teal-200 dark:border-teal-800/60 px-2 py-0.5 rounded-full">DM</span>
                                                                        <template x-if="derivedMetrics[varCfg.independent_dm_id]">
                                                                            <span
                                                                                class="text-[10px] font-medium text-teal-700 dark:text-teal-300 bg-teal-100/60 dark:bg-teal-900/40 px-2 py-0.5 rounded-md truncate max-w-[130px]"
                                                                                x-text="derivedMetrics[varCfg.independent_dm_id].name"></span>
                                                                        </template>
                                                                    </div>
                                                                    <span
                                                                        class="text-[10px] font-semibold text-gray-600 dark:text-gray-300 bg-gray-200 dark:bg-gray-700 px-2.5 py-1 rounded-full"
                                                                        x-text="channels[series.channel] || series.channel"></span>
                                                                </div>
                                                                <div class="p-6 flex-1 flex flex-col gap-5 min-h-0">
                                                                    <div>
                                                                        <label
                                                                            class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('Metric') }}</label>
                                                                        <p class="text-sm text-gray-900 dark:text-white"
                                                                           x-text="series.metric"></p>
                                                                    </div>

                                                                    <div class="gap-3 flex-1 flex flex-col min-h-0 mt-6">
                                                                        <div class="flex items-center justify-between">
                                                                            <label
                                                                                class="block text-xs font-semibold text-gray-700 dark:text-gray-300">{{ __('Asset Override') }}
                                                                                <span class="text-gray-400 font-normal">({{ __('leave empty for DM default') }})</span></label>
                                                                            <div class="flex gap-3">
                                                                                <button
                                                                                    @click="selectAllKpiAssets('ind_' + idx + '_dm_' + sIdx, series.channel)"
                                                                                    class="text-[11px] font-semibold text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300 hover:underline">{{ __('Select All') }}</button>
                                                                                <button
                                                                                    @click="clearAllKpiAssets('ind_' + idx + '_dm_' + sIdx)"
                                                                                    class="text-[11px] font-semibold text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:underline">{{ __('Clear') }}</button>
                                                                            </div>
                                                                        </div>
                                                                        <div class="relative">
                                                                            <div
                                                                                class="absolute inset-y-0 left-0 w-10 flex items-center justify-center pointer-events-none">
                                                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                                                     fill="none" viewBox="0 0 24 24"
                                                                                     stroke-width="2"
                                                                                     stroke="currentColor"
                                                                                     class="w-4 h-4 text-gray-400">
                                                                                    <path stroke-linecap="round"
                                                                                          stroke-linejoin="round"
                                                                                          d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
                                                                                </svg>
                                                                            </div>
                                                                            <input type="text"
                                                                                   x-model="searchQueries['ind_' + idx + '_dm_' + sIdx]"
                                                                                   :placeholder="'{{ __('Search assets...') }}'"
                                                                                   class="bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 bd-search-input">
                                                                        </div>
                                                                        <div class="flex-1 relative min-h-0">
                                                                            <div
                                                                                class="absolute inset-0 flex flex-col gap-1 overflow-y-auto pr-1 custom-scrollbar">
                                                                                <template
                                                                                    x-for="(name, id) in allChannelAssets[series.channel] || {}"
                                                                                    :key="id">
                                                                                    <div
                                                                                        x-show="(searchQueries['ind_' + idx + '_dm_' + sIdx] || '') === '' || name.toLowerCase().includes((searchQueries['ind_' + idx + '_dm_' + sIdx] || '').toLowerCase())"
                                                                                        class="flex items-center justify-between px-3 py-2 text-sm rounded-md transition-colors border border-transparent"
                                                                                        :class="((widgetControlsForm.series_allowed_assets && widgetControlsForm.series_allowed_assets['ind_' + idx + '_dm_' + sIdx]) ? widgetControlsForm.series_allowed_assets['ind_' + idx + '_dm_' + sIdx].includes(String(id)) : (widgetControlsForm.series_assets['ind_' + idx + '_dm_' + sIdx] || []).includes(String(id))) ? 'bg-gray-50/70 dark:bg-white/[0.03]' : 'opacity-60 hover:opacity-100 hover:bg-gray-100 dark:hover:bg-white/5'">
                                                                                        <div @click="toggleKpiAssetIncluded('ind_' + idx + '_dm_' + sIdx, id)"
                                                                                             class="flex gap-x-3 items-center cursor-pointer flex-1 min-w-0">
                                                                                            <div
                                                                                                class="w-4 h-4 shrink-0 flex items-center justify-center rounded border transition-colors"
                                                                                                :class="((widgetControlsForm.series_allowed_assets && widgetControlsForm.series_allowed_assets['ind_' + idx + '_dm_' + sIdx]) ? widgetControlsForm.series_allowed_assets['ind_' + idx + '_dm_' + sIdx].includes(String(id)) : (widgetControlsForm.series_assets['ind_' + idx + '_dm_' + sIdx] || []).includes(String(id))) ? 'bg-primary-600 border-primary-600' : 'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800'">
                                                                                                <svg
                                                                                                    x-show="((widgetControlsForm.series_allowed_assets && widgetControlsForm.series_allowed_assets['ind_' + idx + '_dm_' + sIdx]) ? widgetControlsForm.series_allowed_assets['ind_' + idx + '_dm_' + sIdx].includes(String(id)) : (widgetControlsForm.series_assets['ind_' + idx + '_dm_' + sIdx] || []).includes(String(id)))"
                                                                                                    class="w-3 h-3 text-white"
                                                                                                    fill="none"
                                                                                                    viewBox="0 0 24 24"
                                                                                                    stroke-width="3"
                                                                                                    stroke="currentColor">
                                                                                                    <path stroke-linecap="round"
                                                                                                          stroke-linejoin="round"
                                                                                                          d="m4.5 12.75 6 6 9-13.5"/>
                                                                                                </svg>
                                                                                            </div>
                                                                                            <span class="truncate font-medium text-gray-700 dark:text-gray-200"
                                                                                                  :class="((widgetControlsForm.series_allowed_assets && widgetControlsForm.series_allowed_assets['ind_' + idx + '_dm_' + sIdx]) ? widgetControlsForm.series_allowed_assets['ind_' + idx + '_dm_' + sIdx].includes(String(id)) : (widgetControlsForm.series_assets['ind_' + idx + '_dm_' + sIdx] || []).includes(String(id))) ? 'text-primary-900 dark:text-primary-100 font-semibold' : ''"
                                                                                                  x-text="name"></span>
                                                                                        </div>
                                                                                        {{-- Badge for Default Active on Load --}}
                                                                                        <template x-if="widgetControlsForm.series_allowed_assets && widgetControlsForm.series_allowed_assets['ind_' + idx + '_dm_' + sIdx] && widgetControlsForm.series_allowed_assets['ind_' + idx + '_dm_' + sIdx].includes(String(id))">
                                                                                            <div class="flex items-center gap-1.5 shrink-0 ml-2">
                                                                                                <button type="button"
                                                                                                        @click.stop="toggleKpiAssetDefaultActive('ind_' + idx + '_dm_' + sIdx, id)"
                                                                                                        :title="(widgetControlsForm.series_assets['ind_' + idx + '_dm_' + sIdx] || []).includes(String(id)) ? '{{ __('Active by default on widget load') }}' : '{{ __('Available (inactive on load)') }}'"
                                                                                                        class="bd-badge-active text-[10px] font-semibold py-0.5 rounded-full transition-all"
                                                                                                        :class="(widgetControlsForm.series_assets['ind_' + idx + '_dm_' + sIdx] || []).includes(String(id))
                                                                                                            ? 'bg-primary-100 dark:bg-primary-900/50 text-primary-700 dark:text-primary-300 border border-primary-300 dark:border-primary-700'
                                                                                                            : 'bg-gray-100 dark:bg-gray-700 text-gray-400 dark:text-gray-400 border border-gray-200 dark:border-gray-600 hover:text-gray-600 dark:hover:text-gray-200'">
                                                                                                    <span x-text="(widgetControlsForm.series_assets['ind_' + idx + '_dm_' + sIdx] || []).includes(String(id)) ? '★ {{ __('Active') }}' : '☆ {{ __('Available') }}'"></span>
                                                                                                </button>
                                                             </div>
                                                                                        </template>
                                                                                    </div>
                                                                                </template>
                                                                                <template
                                                                                    x-if="!series.channel || Object.keys(allChannelAssets[series.channel] || {}).length === 0">
                                                                                    <p class="text-xs text-gray-400 dark:text-gray-500">{{ __('No assets loaded for this channel.') }}</p>
                                                                                </template>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </template>
                                                </template>
                                                <template
                                                    x-if="varCfg.independent_channel && !varCfg.independent_dm_id && (!varCfg.independent_source_type || varCfg.independent_source_type !== 'derived_metric')">
                                                    <div
                                                        class="flex-none w-full h-full min-h-0 flex flex-col snap-start"
                                                        :class="{
                                                     'md:w-full': Object.keys(widgetKpiConfig.independent_variables || {}).length === 1,
                                                     'sm:w-[calc(50%-0.75rem)]': Object.keys(widgetKpiConfig.independent_variables || {}).length >= 2
                                                 }">
                                                        <div
                                                            class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm overflow-hidden flex flex-col h-full min-h-0">
                                                            <div
                                                                class="flex items-center justify-between px-6 py-3 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700">
                                                                <div class="flex items-center gap-2">
                                                                    <span
                                                                        class="text-xs font-bold text-gray-800 dark:text-white uppercase tracking-wider"
                                                                        x-text="varCfg.label || '{{ __('Independent Series') }}'"></span>
                                                                    <span
                                                                        class="text-[10px] font-semibold text-primary-600 dark:text-primary-400 bg-primary-50 dark:bg-primary-950/40 border border-primary-200 dark:border-primary-800/60 px-2 py-0.5 rounded-full">KPI</span>
                                                                </div>
                                                                <div class="flex flex-col items-end gap-1">
                                                                <span
                                                                    class="text-[10px] font-semibold text-gray-600 dark:text-gray-300 bg-gray-200 dark:bg-gray-700 px-2.5 py-1 rounded-full"
                                                                    x-text="channels[varCfg.independent_channel]"></span>
                                                                    <template x-if="varCfg.independent_metric">
                                                                    <span
                                                                        class="text-[10px] font-medium text-gray-500 dark:text-gray-400"
                                                                        x-text="(allChannelMetrics[varCfg.independent_channel] || {})[varCfg.independent_metric] || varCfg.independent_metric"></span>
                                                                    </template>
                                                                    <template x-if="!varCfg.independent_metric">
                                                                    <span
                                                                        class="text-[10px] font-semibold text-amber-600 dark:text-amber-400 bg-amber-100 dark:bg-amber-900/30 px-2.5 py-1 rounded-full">{{ __('Dynamic Metric') }}</span>
                                                                    </template>
                                                                </div>
                                                            </div>
                                                            <div class="p-6 flex-1 flex flex-col gap-5 min-h-0">

                                                                {{-- Data Scope / Matrix (Per-Series for KPI Independent) --}}
                                                                <template x-if="varCfg.independent_channel && allChannelDependencies[varCfg.independent_channel] && Object.keys(allChannelDependencies[varCfg.independent_channel]).length > 0">
                                                                    <div>
                                                                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('Data Scope / Matrix') }}</label>
                                                                        <select x-model="widgetControlsForm.series_dependencies['independent_' + idx]"
                                                                                class="bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5">
                                                                            <template x-for="(label, key) in allChannelDependencies[varCfg.independent_channel]" :key="key">
                                                                                <option :value="key" x-text="label" :selected="widgetControlsForm.series_dependencies['independent_' + idx] === key"></option>
                                                                            </template>
                                                                        </select>
                                                                    </div>
                                                                </template>

                                                                <template x-if="!varCfg.independent_metric">
                                                                    <div>
                                                                        <label
                                                                            class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('Metric') }}</label>
                                                                        <select
                                                                            x-model="widgetControlsForm.metrics[idx + 1]"
                                                                            class="bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5">
                                                                            <option
                                                                                value="">{{ __('Select a metric...') }}</option>
                                                                            <template
                                                                                x-for="(label, key) in allChannelMetrics[varCfg.independent_channel] || {}"
                                                                                :key="key">
                                                                                <option :value="key"
                                                                                        x-text="label"></option>
                                                                            </template>
                                                                        </select>
                                                                    </div>
                                                                </template>

                                                                    <div
                                                                        class="gap-3 flex-1 flex flex-col min-h-0 mt-6">
                                                                        <div
                                                                            class="flex items-center justify-between mt-2">
                                                                            <label
                                                                                class="block text-xs font-semibold text-gray-700 dark:text-gray-300">{{ __('Assets') }}
                                                                                <span>({{ __('Leave empty for All Assets') }})</span></label>
                                                                            <div class="flex gap-3">
                                                                                <button
                                                                                    @click="selectAllKpiAssets('independent_' + idx, varCfg.independent_channel)"
                                                                                    class="text-[11px] font-semibold text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300 hover:underline">{{ __('Select All') }}</button>
                                                                                <button
                                                                                    @click="clearAllKpiAssets('independent_' + idx)"
                                                                                    class="text-[11px] font-semibold text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:underline">{{ __('Clear') }}</button>
                                                                            </div>
                                                                        </div>
                                                                        <div class="relative">
                                                                            <div
                                                                                class="absolute inset-y-0 left-0 w-10 flex items-center justify-center pointer-events-none">
                                                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                                                     fill="none" viewBox="0 0 24 24"
                                                                                     stroke-width="2"
                                                                                     stroke="currentColor"
                                                                                     class="w-4 h-4 text-gray-400">
                                                                                    <path stroke-linecap="round"
                                                                                          stroke-linejoin="round"
                                                                                          d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
                                                                                </svg>
                                                                            </div>
                                                                            <input type="text"
                                                                                   x-model="searchQueries['independent_' + idx]"
                                                                                   :placeholder="'{{ __('Search assets...') }}'"
                                                                                   class="bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 bd-search-input">
                                                                        </div>
                                                                        <div class="flex-1 relative min-h-0">
                                                                            <div
                                                                                class="absolute inset-0 flex flex-col gap-1 overflow-y-auto pr-1 custom-scrollbar">
                                                                                <template
                                                                                    x-for="(name, id) in allChannelAssets[varCfg.independent_channel] || {}"
                                                                                    :key="id">
                                                                                    <div
                                                                                        x-show="isAssetAllowedByGroups('independent_' + idx, varCfg.independent_channel, id) && ((searchQueries['independent_' + idx] || '') === '' || name.toLowerCase().includes((searchQueries['independent_' + idx] || '').toLowerCase()))"
                                                                                        class="flex items-center justify-between px-3 py-2 text-sm rounded-md transition-colors border border-transparent"
                                                                                        :class="((widgetControlsForm.series_allowed_assets && widgetControlsForm.series_allowed_assets['independent_' + idx]) ? widgetControlsForm.series_allowed_assets['independent_' + idx].includes(String(id)) : (widgetControlsForm.series_assets['independent_' + idx] || []).includes(String(id))) ? 'bg-gray-50/70 dark:bg-white/[0.03]' : 'opacity-60 hover:opacity-100 hover:bg-gray-100 dark:hover:bg-white/5'">
                                                                                        <div @click="toggleKpiAssetIncluded('independent_' + idx, id)"
                                                                                             class="flex gap-x-3 items-center cursor-pointer flex-1 min-w-0">
                                                                                            <div
                                                                                                class="w-4 h-4 shrink-0 flex items-center justify-center rounded border transition-colors"
                                                                                                :class="((widgetControlsForm.series_allowed_assets && widgetControlsForm.series_allowed_assets['independent_' + idx]) ? widgetControlsForm.series_allowed_assets['independent_' + idx].includes(String(id)) : (widgetControlsForm.series_assets['independent_' + idx] || []).includes(String(id))) ? 'bg-primary-600 border-primary-600' : 'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800'">
                                                                                                <svg
                                                                                                    x-show="((widgetControlsForm.series_allowed_assets && widgetControlsForm.series_allowed_assets['independent_' + idx]) ? widgetControlsForm.series_allowed_assets['independent_' + idx].includes(String(id)) : (widgetControlsForm.series_assets['independent_' + idx] || []).includes(String(id)))"
                                                                                                    class="w-3 h-3 text-white"
                                                                                                    fill="none"
                                                                                                    viewBox="0 0 24 24"
                                                                                                    stroke-width="3"
                                                                                                    stroke="currentColor">
                                                                                                    <path
                                                                                                        stroke-linecap="round"
                                                                                                        stroke-linejoin="round"
                                                                                                        d="m4.5 12.75 6 6 9-13.5"/>
                                                                                                </svg>
                                                                                            </div>
                                                                                            <span
                                                                                                class="truncate font-medium text-gray-700 dark:text-gray-200"
                                                                                                :class="((widgetControlsForm.series_allowed_assets && widgetControlsForm.series_allowed_assets['independent_' + idx]) ? widgetControlsForm.series_allowed_assets['independent_' + idx].includes(String(id)) : (widgetControlsForm.series_assets['independent_' + idx] || []).includes(String(id))) ? 'text-primary-900 dark:text-primary-100 font-semibold' : ''"
                                                                                                x-text="name"></span>
                                                                                        </div>
                                                                                        {{-- Badge for Default Active on Load --}}
                                                                                        <template x-if="widgetControlsForm.series_allowed_assets && widgetControlsForm.series_allowed_assets['independent_' + idx] && widgetControlsForm.series_allowed_assets['independent_' + idx].includes(String(id))">
                                                                                            <div class="flex items-center gap-1.5 shrink-0 ml-2">
                                                                                                <button type="button"
                                                                                                        @click.stop="toggleKpiAssetDefaultActive('independent_' + idx, id)"
                                                                                                        :title="(widgetControlsForm.series_assets['independent_' + idx] || []).includes(String(id)) ? '{{ __('Active by default on widget load') }}' : '{{ __('Available (inactive on load)') }}'"
                                                                                                        class="bd-badge-active text-[10px] font-semibold py-0.5 rounded-full transition-all"
                                                                                                        :class="(widgetControlsForm.series_assets['independent_' + idx] || []).includes(String(id))
                                                                                                            ? 'bg-primary-100 dark:bg-primary-900/50 text-primary-700 dark:text-primary-300 border border-primary-300 dark:border-primary-700'
                                                                                                            : 'bg-gray-100 dark:bg-gray-700 text-gray-400 dark:text-gray-400 border border-gray-200 dark:border-gray-600 hover:text-gray-600 dark:hover:text-gray-200'">
                                                                                                    <span x-text="(widgetControlsForm.series_assets['independent_' + idx] || []).includes(String(id)) ? '★ {{ __('Active') }}' : '☆ {{ __('Available') }}'"></span>
                                                                                                </button>
                                                                                            </div>
                                                                                        </template>
                                                                                    </div>
                                                                                </template>
                                                                                <template
                                                                                    x-if="!allChannelAssets[varCfg.independent_channel] || Object.keys(allChannelAssets[varCfg.independent_channel]).length === 0">
                                                                                    <p class="text-xs text-gray-400 dark:text-gray-500">{{ __('No assets loaded for this channel.') }}</p>
                                                                                </template>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>
                                        </template>
                                    </template>
                                </template>
                            </template>

                            {{-- Series: Derived Metric source series --}}
                            <template x-if="widgetControlsTarget.source_type === 'derived_metric'">
                                <template x-for="(series, index) in (widgetControlsTarget.dmSourceSeries || [])"
                                          :key="index">
                                    <div
                                        class="flex-none w-full h-full min-h-0 flex flex-col snap-start"
                                        :class="{
                                            'md:w-full': (widgetControlsTarget.dmSourceSeries || []).length === 1,
                                            'sm:w-[calc(50%-0.75rem)]': (widgetControlsTarget.dmSourceSeries || []).length >= 2
                                        }">
                                        <div
                                            class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm overflow-hidden flex flex-col h-full min-h-0">
                                            <div
                                                class="flex items-center justify-between px-6 py-3 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700">
                                                <div class="flex items-center gap-2">
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                         viewBox="0 0 24 24" stroke-width="1.5"
                                                         stroke="currentColor"
                                                         class="w-4 h-4 text-gray-500 dark:text-gray-400">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                              d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5m.75-9l3-3 2.148 2.148A12.061 12.061 0 0116.5 7.605"/>
                                                    </svg>
                                                    <span
                                                        class="text-xs font-bold text-gray-800 dark:text-white uppercase tracking-wider"
                                                        x-text="series.label || ('{{ __('Source') }} ' + String.fromCharCode(97 + index))"></span>
                                                    <span
                                                        class="text-[10px] font-semibold text-teal-600 dark:text-teal-400 bg-teal-50 dark:bg-teal-950/40 border border-teal-200 dark:border-teal-800/60 px-2 py-0.5 rounded-full">DM</span>
                                                    <template x-if="widgetControlsTarget.source_config && widgetControlsTarget.source_config.derived_metric_id && derivedMetrics[widgetControlsTarget.source_config.derived_metric_id]">
                                                        <span
                                                            class="text-[10px] font-medium text-teal-700 dark:text-teal-300 bg-teal-100/60 dark:bg-teal-900/40 px-2 py-0.5 rounded-md truncate max-w-[140px]"
                                                            x-text="derivedMetrics[widgetControlsTarget.source_config.derived_metric_id].name"></span>
                                                    </template>
                                                </div>
                                            </div>
                                            <div class="p-6 flex-1 flex flex-col gap-5 min-h-0">
                                                <div>
                                                    <label
                                                        class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">{{ __('Channel') }}</label>
                                                    <p class="text-sm text-gray-900 dark:text-white"
                                                       x-text="channels[series.channel] || series.channel"></p>
                                                </div>
                                                <div class="my-2">
                                                    <label
                                                        class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">{{ __('Metric') }}</label>
                                                    <p class="text-sm text-gray-900 dark:text-white"
                                                       x-text="series.metric"></p>
                                                </div>

                                                <div class="gap-3 flex-1 flex flex-col min-h-0 mt-6">
                                                    <div class="flex items-center justify-between">
                                                        <label
                                                            class="block text-xs font-semibold text-gray-700 dark:text-gray-300">{{ __('Asset Override') }}
                                                            ({{ __('leave empty for DM default') }})</label>
                                                        <button @click="selectAllDmAssets(index)"
                                                                class="text-[11px] font-semibold text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300 hover:underline">{{ __('Select All') }}</button>
                                                        <button @click="clearAllDmAssets(index)"
                                                                class="text-[11px] font-semibold text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:underline">{{ __('Clear') }}</button>
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
                                                        <input type="text" x-model="searchQueries['dm_' + index]"
                                                               :placeholder="'{{ __('Search assets...') }}'"
                                                               class="bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 bd-search-input">
                                                    </div>
                                                    <div class="flex-1 relative min-h-0">
                                                        <div
                                                            class="absolute inset-0 flex flex-col gap-1 overflow-y-auto pr-1 custom-scrollbar">
                                                            <template
                                                                x-for="(name, id) in allChannelAssets[series.channel] || {}"
                                                                :key="id">
                                                                <div
                                                                    x-show="(searchQueries['dm_' + index] || '') === '' || name.toLowerCase().includes((searchQueries['dm_' + index] || '').toLowerCase())"
                                                                    @click="toggleDmAsset(index, id)"
                                                                    class="flex gap-x-3 items-center px-3 py-2.5 text-sm text-gray-700 dark:text-gray-200 rounded-lg cursor-pointer transition-colors border border-transparent"
                                                                    :class="(widgetControlsForm.dm_assets[index] || []).includes(String(id)) ? 'bg-primary-50 dark:bg-primary-900/30 border-primary-100 dark:border-primary-900/50' : 'hover:bg-gray-100 dark:hover:bg-white/5'">
                                                                    <div
                                                                        class="w-4 h-4 shrink-0 flex items-center justify-center rounded border transition-colors"
                                                                        :class="(widgetControlsForm.dm_assets[index] || []).includes(String(id)) ? 'bg-primary-600 border-primary-600' : 'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800'">
                                                                        <svg
                                                                            x-show="(widgetControlsForm.dm_assets[index] || []).includes(String(id))"
                                                                            class="w-3 h-3 text-white" fill="none"
                                                                            viewBox="0 0 24 24" stroke-width="3"
                                                                            stroke="currentColor">
                                                                            <path stroke-linecap="round"
                                                                                  stroke-linejoin="round"
                                                                                  d="m4.5 12.75 6 6 9-13.5"/>
                                                                        </svg>
                                                                    </div>
                                                                    <span class="truncate font-medium"
                                                                          :class="(widgetControlsForm.dm_assets[index] || []).includes(String(id)) ? 'text-primary-800 dark:text-primary-200' : ''"
                                                                          x-text="name"></span>
                                                                </div>
                                                            </template>
                                                            <template
                                                                x-if="!series.channel || Object.keys(allChannelAssets[series.channel] || {}).length === 0">
                                                                <p class="text-xs text-gray-400 dark:text-gray-500">{{ __('No assets loaded for this channel.') }}</p>
                                                            </template>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </template>
                        </div>

                        {{-- Fixed Vertical Add Series Bar (Raw Metric widgets only, outside scrollable container) --}}
                        <template x-if="widgetControlsTarget.source_type !== 'kpi' && widgetControlsTarget.source_type !== 'derived_metric'">
                            <div class="flex-none flex flex-col justify-center items-center py-2 self-stretch pl-3">
                                <button
                                    type="button"
                                    x-on:click="promptAddSeriesType()"
                                    :title="'{{ __('Add Series') }}'"
                                    class="h-full bd-add-series-btn">
                                    <div class="bd-add-icon-circle">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path>
                                        </svg>
                                    </div>
                                </button>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        {{-- ============================================================ --}}
        {{-- ADD SERIES CONFIRMATION MODAL                                --}}
        {{-- ============================================================ --}}
        <x-confirm-modal
            open="showAddSeriesTypeModal"
            title="{{ __('Add Series') }}"
            icon="heroicon-o-plus-circle"
            color="primary"
            confirm-label="{{ __('Add') }}"
            confirm-color="primary"
            confirm-icon="heroicon-o-plus"
            on-confirm="confirmAddSeriesType()"
            :close-on-confirm="false"
            cancel-label="{{ __('Cancel') }}"
        >
            <div class="space-y-4">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ __('Choose whether you want to add a standard raw metric series or import series from a configured Derived Metric.') }}
                </p>

                <div class="space-y-3">
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300">{{ __('Series Type') }}</label>
                    <div class="grid grid-cols-2 gap-3">
                        <button
                            type="button"
                            @click="addSeriesSourceType = 'metric'"
                            class="p-3 rounded-lg border text-center transition-all cursor-pointer flex flex-col items-center justify-center gap-1.5"
                            :class="addSeriesSourceType === 'metric'
                                ? 'border-primary-500 bg-primary-50 dark:bg-primary-950/40 text-primary-700 dark:text-primary-300 font-semibold ring-1 ring-primary-500'
                                : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600 text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800'">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                            </svg>
                            <span class="text-xs">{{ __('Raw Metric') }}</span>
                        </button>

                        <button
                            type="button"
                            @click="addSeriesSourceType = 'derived_metric'"
                            class="p-3 rounded-lg border text-center transition-all cursor-pointer flex flex-col items-center justify-center gap-1.5"
                            :class="addSeriesSourceType === 'derived_metric'
                                ? 'border-primary-500 bg-primary-50 dark:bg-primary-950/40 text-primary-700 dark:text-primary-300 font-semibold ring-1 ring-primary-500'
                                : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600 text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800'">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6a7.5 7.5 0 107.5 7.5h-7.5V6z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5H21A7.5 7.5 0 0013.5 3v7.5z" />
                            </svg>
                            <span class="text-xs">{{ __('Derived Metric') }}</span>
                        </button>
                    </div>
                </div>

                {{-- Derived Metric Selector (Visible when derived_metric selected) --}}
                <div x-show="addSeriesSourceType === 'derived_metric'" x-cloak class="pt-1">
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">{{ __('Select Derived Metric') }}</label>
                    <select
                        x-model="addSeriesDerivedMetricId"
                        class="bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5">
                        <option value="">{{ __('Choose a Derived Metric...') }}</option>
                        <template x-for="(dm, id) in derivedMetrics" :key="id">
                            <option :value="id" x-text="dm.name"></option>
                        </template>
                    </select>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1.5">
                        {{ __('All source series defined in the selected Derived Metric will be added with channels and metrics fixed.') }}
                    </p>
                </div>
            </div>
        </x-confirm-modal>

        {{-- ============================================================ --}}
        {{-- REMOVE DM SERIES GROUP CONFIRMATION MODAL                    --}}
        {{-- ============================================================ --}}
        <x-confirm-modal
            open="showRemoveDmSeriesModal"
            title="{{ __('Remove Derived Metric Series') }}"
            icon="heroicon-o-trash"
            color="danger"
            confirm-label="{{ __('Remove All') }}"
            confirm-color="danger"
            confirm-icon="heroicon-o-trash"
            on-confirm="confirmRemoveDmSeriesGroup()"
            :close-on-confirm="false"
            cancel-label="{{ __('Cancel') }}"
        >
            <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ __('This series is part of the Derived Metric') }} <strong class="text-gray-900 dark:text-white" x-text="pendingRemoveDmName"></strong>.
                {{ __('Removing it will remove all series associated with this Derived Metric from the widget.') }}
            </p>
        </x-confirm-modal>

        {{-- ============================================================ --}}
        {{-- ADD WIDGET MODAL                                            --}}
        {{-- ============================================================ --}}
        <div x-show="showAddWidgetModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center">
            <div class="absolute inset-0 bg-black/50" x-on:click="showAddWidgetModal = false"></div>
            <div
                class="relative bg-white dark:bg-gray-900 rounded-xl shadow-xl max-w-5xl w-full mx-4 p-6 flex flex-col max-h-[90vh]">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">{{ __('Add Widget') }}</h2>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 overflow-y-auto pr-2 pb-2">
                    {{-- Column 1 (Settings) --}}
                    <div class="space-y-6 md:col-span-1">
                        {{-- Name --}}
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('Widget Name') }}</label>
                            <input type="text" x-model="widgetName"
                                   class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100"
                                   :placeholder="'{{ __('My Widget') }}'"/>
                        </div>

                        {{-- Source Type --}}
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('Data Source') }}</label>
                            <div class="grid grid-cols-1 gap-3">
                                <template x-for="item in sourceTypesList" :key="item.type">
                                    <button type="button" class="p-3 rounded-lg border text-center text-sm transition-colors"
                                            :class="selectedSourceType === item.type
                                                ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-300'
                                                : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'"
                                            x-on:click="setSourceType(item.type)">
                                        <span x-text="item.label"></span>
                                    </button>
                                </template>
                            </div>
                        </div>

                        {{-- KPI (if kpi source) --}}
                        <template x-if="selectedSourceType === 'kpi'">
                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('Select KPI') }}</label>
                                <x-ui.select-input x-model="customKpiId" class="w-full">
                                    <x-ui.select-option value="">{{ __('Choose a KPI...') }}</x-ui.select-option>
                                    <template x-for="(kpi, id) in kpis" :key="id">
                                        <x-ui.select-option x-bind:value="id" x-text="kpi.name"></x-ui.select-option>
                                    </template>
                                </x-ui.select-input>
                            </div>
                        </template>

                        {{-- Derived Metric (if derived_metric source) --}}
                        <template x-if="selectedSourceType === 'derived_metric'">
                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('Select Derived Metric') }}</label>
                                <x-ui.select-input x-model="derivedMetricId" class="w-full">
                                    <x-ui.select-option
                                        value="">{{ __('Choose a Derived Metric...') }}</x-ui.select-option>
                                    <template x-for="(dm, id) in derivedMetrics" :key="id">
                                        <x-ui.select-option x-bind:value="id" x-text="dm.name"></x-ui.select-option>
                                    </template>
                                </x-ui.select-input>
                            </div>
                        </template>
                    </div>

                    {{-- Column 2 (Widget Types) --}}
                    <div class="space-y-6 md:col-span-2">
                        {{-- Widget Type --}}
                        <template x-if="selectedSourceType">
                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('Widget Type') }}</label>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <template x-for="item in availableWidgetTypesList" :key="item.type">
                                        <button
                                            type="button"
                                            class="p-3 rounded-xl border text-left transition-colors flex items-center gap-3"
                                            :class="selectedWidgetType === item.type
                                                    ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20 ring-1 ring-primary-500'
                                                    : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 hover:border-gray-300 dark:hover:border-gray-600 shadow-sm'"
                                            x-on:click="selectedWidgetType = item.type">
                                            <div
                                                class="w-12 h-10 flex-shrink-0 bg-gray-50 dark:bg-gray-900/50 rounded-lg border border-gray-100 dark:border-gray-800 flex items-center justify-center"
                                                x-html="item.svg">
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center gap-2">
                                                    <span
                                                        class="block text-sm font-semibold text-gray-900 dark:text-white truncate"
                                                        x-text="item.label"></span>
                                                    <template x-if="optimalWidgetTypes.includes(item.type)">
                                                        <span
                                                            class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-primary-100 text-primary-800 dark:bg-primary-900/30 dark:text-primary-400 shrink-0">{{ __('Recommended') }}</span>
                                                    </template>
                                                </div>
                                                <span
                                                    class="block text-[11px] text-gray-500 dark:text-gray-400 leading-tight mt-0.5 bd-line-clamp-2"
                                                    x-text="item.description"></span>
                                            </div>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <button
                        class="px-4 py-2 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800"
                        x-on:click="showAddWidgetModal = false">{{ __('Cancel') }}
                    </button>
                    <button
                        class="px-4 py-2 rounded-lg bg-primary-600 text-white hover:bg-primary-500 disabled:opacity-50"
                        :disabled="!canAddWidget()"
                        x-on:click="confirmAddWidget()">{{ __('Add Widget') }}
                    </button>
                </div>
            </div>
        </div>

        {{-- ============================================================ --}}
        {{-- SHARE DIALOG                                               --}}
        {{-- ============================================================ --}}
        <div x-show="showShareDialog" x-cloak class="fixed inset-0 z-50 flex items-center justify-center">
            <div class="absolute inset-0 bg-black/50" x-on:click="showShareDialog = false"></div>
            <div class="relative bg-white dark:bg-gray-900 rounded-xl shadow-xl max-w-md w-full mx-4 p-6 space-y-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('Share Dashboard') }}</h2>

                {{-- Public Toggle --}}
                <div
                    class="flex items-center justify-between p-3 rounded-lg border border-gray-200 dark:border-gray-700">
                    <div>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ __('Public access') }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Anyone with the link can view this dashboard') }}</p>
                    </div>
                    <button class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors"
                            :class="isPublic ? 'bg-primary-600' : 'bg-gray-300 dark:bg-gray-600'"
                            x-on:click="togglePublic()">
                        <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"
                              :class="isPublic ? 'translate-x-6' : 'translate-x-1'"></span>
                    </button>
                </div>

                {{-- Shared Users --}}
                <div>
                    <label
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('Shared with') }}</label>
                    <div class="space-y-2 max-h-48 overflow-y-auto">
                        <template x-for="user in sharedUsers" :key="user.id">
                            <div
                                class="flex items-center justify-between px-3 py-2 rounded-lg bg-gray-50 dark:bg-gray-800">
                                <div>
                                    <p class="text-sm text-gray-900 dark:text-white" x-text="user.name"></p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400" x-text="user.email"></p>
                                </div>
                                <button class="text-xs text-red-500 hover:underline"
                                        x-on:click="unshareUser(user.id)">{{ __('Remove') }}
                                </button>
                            </div>
                        </template>
                        <template x-if="!sharedUsers.length">
                            <p class="text-sm text-gray-400 dark:text-gray-500 text-center py-4">{{ __('No users shared yet') }}</p>
                        </template>
                    </div>
                </div>

                {{-- Add User --}}
                <div>
                    <label
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('Add collaborator') }}</label>
                    <div class="flex gap-2">
                        <x-ui.select-input x-model="shareUserId" class="flex-1">
                            <x-ui.select-option value="">{{ __('Select a user...') }}</x-ui.select-option>
                            <template x-for="user in collaborators" :key="user.id">
                                <template x-if="!isShared(user.id)">
                                    <x-ui.select-option x-bind:value="user.id"
                                                        x-text="user.name + ' (' + user.email + ')'"></x-ui.select-option>
                                </template>
                            </template>
                        </x-ui.select-input>
                        <button
                            class="px-3 py-2 rounded-lg bg-primary-600 text-white text-sm hover:bg-primary-500 disabled:opacity-50"
                            :disabled="!shareUserId"
                            x-on:click="addSharedUser()">{{ __('Add') }}
                        </button>
                    </div>
                </div>

                <div class="flex justify-end pt-2 border-t border-gray-200 dark:border-gray-700">
                    <button
                        class="px-4 py-2 rounded-lg text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800"
                        x-on:click="showShareDialog = false">{{ __('Close') }}
                    </button>
                </div>
            </div>
        </div>

        {{-- ============================================================ --}}
        {{-- UNSAVED LAYOUT CONFIRMATION MODAL                             --}}
        {{-- ============================================================ --}}
        <x-confirm-modal
            open="showUnsavedNavModal"
            title="{{ __('Unsaved Layout Changes') }}"
            icon="heroicon-o-exclamation-triangle"
            color="warning"
            confirm-label="{{ __('Save and Leave') }}"
            confirm-color="primary"
            confirm-icon="heroicon-o-check"
            on-confirm="confirmSaveAndLeave()"
            :close-on-confirm="false"
            secondary-label="{{ __('Discard and Leave') }}"
            secondary-color="danger"
            on-secondary="confirmDiscardAndLeave()"
            :close-on-secondary="false"
            cancel-label="{{ __('Keep Editing') }}"
        >
            <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ __('You have made changes to the layout of this dashboard. Would you like to save them before leaving?') }}
            </p>
        </x-confirm-modal>

        {{-- ============================================================ --}}
        {{-- EPHEMERAL SANDBOX TESTING MODAL                              --}}
        {{-- ============================================================ --}}
        <div x-show="showSandboxModal"
             class="bd-modal-root fixed inset-0 z-[100] flex items-start justify-center pt-10 sm:pt-16"
             x-trap.noscroll="showSandboxModal"
             @keydown.escape.window="showSandboxModal = false">
            <div @click="showSandboxModal = false"
                 class="fixed inset-0 bg-black/50 dark:bg-black/70 backdrop-blur-sm transition-opacity"></div>
            <div class="relative bg-white dark:bg-gray-900 rounded-xl shadow-xl mx-auto my-4 sm:my-6 flex flex-col ring-1 ring-gray-900/5 dark:ring-white/10 bd-modal-panel">
                <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-900 rounded-t-xl flex-shrink-0">
                    <div class="flex flex-col gap-1.5 min-w-0 pr-4">
                        <div class="flex items-center gap-3">
                            <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2 truncate">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" class="w-5 h-5 text-primary-500 flex-shrink-0">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9.75 0h9.75" />
                                </svg>
                                <span class="truncate" x-text="'{{ __('Test Filters:') }} ' + (sandboxTargetWidget?.title || sandboxTargetWidget?.name || '')"></span>
                            </h3>
                            <span class="inline-flex items-center rounded-md bg-amber-50 dark:bg-amber-950/40 px-2.5 py-0.5 text-xs font-semibold text-amber-700 dark:text-amber-400 ring-1 ring-inset ring-amber-700/10 dark:ring-amber-400/20 flex-shrink-0">
                                {{ __('Sandbox (Ephemeral)') }}
                            </span>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                            {{ __('Simulate viewer controls in memory. These changes do NOT modify the saved dashboard configuration.') }}
                        </p>
                    </div>
                    <div class="flex items-center gap-3 flex-shrink-0">
                        {{-- Series Stepped Navigation Arrows (Visible when > 2 series) --}}
                        <div
                            x-show="Object.keys(sandboxVariables || {}).length > 2"
                            class="hidden md:flex items-center gap-1 bg-gray-100 dark:bg-gray-800 p-1 rounded-lg border border-gray-200 dark:border-gray-700">
                            <span class="text-[11px] font-semibold text-gray-500 dark:text-gray-400 px-2 select-none">
                                <span x-text="Object.keys(sandboxVariables || {}).length"></span> {{ __('Series') }}
                            </span>
                            <button
                                type="button"
                                @click="scrollSandboxSeriesByStep(-1)"
                                :disabled="!canScrollSandboxSeriesLeft"
                                :title="'{{ __('Scroll left') }}'"
                                class="p-1 rounded-md transition-all"
                                :class="canScrollSandboxSeriesLeft
                                    ? 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 shadow-xs cursor-pointer'
                                    : 'text-gray-300 dark:text-gray-600 cursor-not-allowed opacity-40'">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"></path>
                                </svg>
                            </button>
                            <button
                                type="button"
                                @click="scrollSandboxSeriesByStep(1)"
                                :disabled="!canScrollSandboxSeriesRight"
                                :title="'{{ __('Scroll right') }}'"
                                class="p-1 rounded-md transition-all"
                                :class="canScrollSandboxSeriesRight
                                    ? 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 shadow-xs cursor-pointer'
                                    : 'text-gray-300 dark:text-gray-600 cursor-not-allowed opacity-40'">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </button>
                        </div>

                        <button type="button"
                                class="bd-modal-header-btn-cancel"
                                x-on:click="resetSandboxControls()">
                            {{ __('Reset to Default') }}
                        </button>
                        <button type="button"
                                class="bd-modal-header-btn-save"
                                x-on:click="applySandboxControls()">
                            {{ __('Apply Test') }}
                        </button>
                        <button type="button"
                                @click="showSandboxModal = false"
                                class="bd-modal-header-close"
                                :title="'{{ __('Close') }}'">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="flex-1 bg-gray-50 dark:bg-gray-900 min-h-0 overflow-y-auto desktop-overflow-hidden relative flex flex-col rounded-b-xl">
                    {{-- Mobile Tab Navigation Bar --}}
                    <div class="md:hidden flex border-b border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 sticky top-0 z-20">
                        <button type="button" @click="activeSandboxMobileTab = 'controls'"
                                class="flex-1 py-3 px-4 text-center text-xs font-bold uppercase tracking-wider transition-colors border-b-2"
                                :class="activeSandboxMobileTab === 'controls' ? 'border-primary-500 text-primary-600 dark:text-primary-400 bg-primary-50/50 dark:bg-primary-950/20' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400'">
                            {{ __('1. General Controls') }}
                        </button>
                        <button type="button" @click="activeSandboxMobileTab = 'series'"
                                x-show="Object.keys(sandboxVariables || {}).length > 0"
                                class="flex-1 py-3 px-4 text-center text-xs font-bold uppercase tracking-wider transition-colors border-b-2 flex items-center justify-center gap-1.5"
                                :class="activeSandboxMobileTab === 'series' ? 'border-primary-500 text-primary-600 dark:text-primary-400 bg-primary-50/50 dark:bg-primary-950/20' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400'">
                            <span>{{ __('2. Series & Assets') }}</span>
                            <span class="px-1.5 py-0.5 text-[10px] rounded-full bg-gray-200 dark:bg-gray-700" x-text="Object.keys(sandboxVariables || {}).length"></span>
                        </button>
                    </div>

                    <div class="modal-body-absolute-wrapper flex flex-col md:flex-row gap-6 flex-1 min-h-0">
                        {{-- Left Column: Global Configuration --}}
                        <div class="flex flex-col gap-6 overflow-y-auto custom-scrollbar pr-2 pb-2 h-full max-h-full min-h-0 bd-config-col"
                             :class="{ 'hidden md:flex': activeSandboxMobileTab !== 'controls' }">
                            
                            {{-- Card: Date Range --}}
                            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm overflow-hidden flex-shrink-0">
                                <div class="flex items-center gap-2 px-6 py-3 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-gray-500 dark:text-gray-400">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/>
                                    </svg>
                                    <span class="text-xs font-bold text-gray-800 dark:text-white uppercase tracking-wider">{{ __('Date Range') }}</span>
                                </div>
                                <div class="p-6 flex flex-row items-center gap-3">
                                    <x-ui.date-input x-model="sandboxForm.date_start" class="w-full" />
                                    <span class="text-gray-400 dark:text-gray-500 text-sm">→</span>
                                    <x-ui.date-input x-model="sandboxForm.date_end" class="w-full" />
                                </div>
                            </div>

                            {{-- Card: Granularity --}}
                            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm overflow-hidden flex-shrink-0">
                                <div class="flex items-center gap-2 px-6 py-3 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-gray-500 dark:text-gray-400">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <span class="text-xs font-bold text-gray-800 dark:text-white uppercase tracking-wider">{{ __('Granularity') }}</span>
                                </div>
                                <div class="p-6">
                                    <x-ui.select-input x-model="sandboxForm.granularity" class="w-full">
                                        <x-ui.select-option value="daily">{{ __('Daily') }}</x-ui.select-option>
                                        <x-ui.select-option value="weekly">{{ __('Weekly') }}</x-ui.select-option>
                                        <x-ui.select-option value="monthly">{{ __('Monthly') }}</x-ui.select-option>
                                        <x-ui.select-option value="quarterly">{{ __('Quarterly') }}</x-ui.select-option>
                                        <x-ui.select-option value="annually">{{ __('Annually') }}</x-ui.select-option>
                                    </x-ui.select-input>
                                </div>
                            </div>

                            {{-- Card: Zero / Missing Data --}}
                            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm overflow-hidden flex-shrink-0">
                                <div class="flex items-center gap-2 px-6 py-3 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-gray-500 dark:text-gray-400">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                    </svg>
                                    <span class="text-xs font-bold text-gray-800 dark:text-white uppercase tracking-wider">{{ __('Zero / Missing Data') }}</span>
                                </div>
                                <div class="p-6">
                                    <x-ui.select-input x-model="sandboxForm.zero_handling" class="w-full">
                                        <x-ui.select-option value="remove">{{ __('Remove zeros from results') }}</x-ui.select-option>
                                        <x-ui.select-option value="keep">{{ __('Keep zeros in results') }}</x-ui.select-option>
                                        <x-ui.select-option value="trim">{{ __('Trim leading/trailing zeros') }}</x-ui.select-option>
                                    </x-ui.select-input>
                                </div>
                            </div>

                            {{-- Card: Edge Case Grouping (KPI / Scatter widgets) --}}
                            <template x-if="sandboxTargetWidget?.source_type === 'kpi' || sandboxTargetWidget?.widget_type === 'scatter'">
                                <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm overflow-hidden flex-shrink-0 flex flex-col">
                                    <div class="flex items-center gap-2 px-6 py-3 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-gray-500 dark:text-gray-400">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5"/>
                                        </svg>
                                        <span class="text-xs font-bold text-gray-800 dark:text-white uppercase tracking-wider">{{ __('Edge Cases & Regression') }}</span>
                                    </div>
                                    <div class="p-6 flex flex-col gap-4">
                                        <label class="flex items-center gap-3 cursor-pointer">
                                            <input type="checkbox" x-model="sandboxForm.edge_case_weighted"
                                                   class="w-4 h-4 rounded border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-primary-600 focus:ring-primary-500">
                                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ __('Weighted regression (WLS)') }}</span>
                                        </label>
                                        <div>
                                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">{{ __('Group low-frequency values') }}</label>
                                            <x-ui.select-input x-model="sandboxForm.edge_case_grouping" class="w-full">
                                                <x-ui.select-option value="none">{{ __('No grouping') }}</x-ui.select-option>
                                                <x-ui.select-option value="histogram">{{ __('Auto histogram-elbow') }}</x-ui.select-option>
                                                <x-ui.select-option value="percentile">{{ __('Bottom percentile') }}</x-ui.select-option>
                                            </x-ui.select-input>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">{{ __('Max ratio cap') }}</label>
                                            <input type="number" step="0.01" min="0"
                                                   x-model="sandboxForm.max_ratio"
                                                   class="w-full bg-white dark:bg-white/5 border border-gray-300 dark:border-white/10 text-gray-950 dark:text-white text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block p-2.5"
                                                   placeholder="{{ __('No cap') }}"/>
                                        </div>
                                        <label class="flex items-center gap-3 cursor-pointer">
                                            <input type="checkbox" x-model="sandboxForm.remove_unknown"
                                                   class="w-4 h-4 rounded border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-primary-600 focus:ring-primary-500">
                                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ __('Exclude unknown keyword') }}</span>
                                        </label>
                                    </div>
                                </div>
                            </template>

                            {{-- Card: Table Options --}}
                            <template x-if="sandboxTargetWidget?.widget_type === 'table'">
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
                                            <input type="checkbox" x-model="sandboxForm.block_first_col"
                                                   class="w-4 h-4 rounded border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-primary-600 focus:ring-primary-500">
                                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ __('Freeze first column') }}</span>
                                        </label>
                                    </div>
                                </div>
                            </template>
                        </div>

                        {{-- Right Column: Dynamic Series & Variables Configuration (1/2 width per card with snap) --}}
                        <div x-ref="sandboxSeriesScrollContainer"
                             @scroll.passive="updateSandboxSeriesScrollState()"
                             class="min-w-0 min-h-0 flex overflow-x-auto gap-6 custom-scrollbar pb-2 items-stretch snap-x snap-mandatory bd-canvas-col"
                             :class="{ 'hidden md:flex': activeSandboxMobileTab !== 'series' }">
                            
                            <template x-for="(vConfig, vKey, vIdx) in sandboxVariables" :key="vKey">
                                <div class="flex-none shrink-0 h-full min-h-0 flex flex-col snap-start bd-series-card">
                                    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm overflow-hidden flex flex-col h-full min-h-0">
                                        {{-- Card Header --}}
                                        <div class="flex items-center justify-between px-6 py-3 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700">
                                            <div class="flex items-center gap-2">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                     viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                                     class="w-4 h-4 text-gray-500 dark:text-gray-400">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                          d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5m.75-9l3-3 2.148 2.148A12.061 12.061 0 0116.5 7.605"/>
                                                </svg>
                                                <span class="text-xs font-bold text-gray-800 dark:text-white uppercase tracking-wider"
                                                      x-text="vConfig.dm_name ? (sandboxTargetWidget?.source_type === 'kpi' ? vConfig.dm_name + ' ' + (vConfig.dm_source_label || '') : vConfig.dm_name) : (vKey === 'dependent' ? '{{ __('Dependent Series') }}' : (sandboxTargetWidget?.source_type === 'kpi' ? '{{ __('Independent Variable') }} ' + (vConfig.index) : '{{ __('Series') }} ' + (vConfig.index + 1)))"></span>
                                                <template x-if="vConfig.dm_name">
                                                    <span class="text-[10px] font-semibold text-indigo-600 dark:text-indigo-400 bg-indigo-100 dark:bg-indigo-900/30 px-2 py-1 rounded-full ml-1">DM</span>
                                                </template>
                                            </div>
                                            <div class="flex flex-col items-end gap-1">
                                                <template x-if="vConfig.channel">
                                                    <span class="text-[10px] font-semibold text-gray-600 dark:text-gray-300 bg-gray-200 dark:bg-gray-700 px-2.5 py-1 rounded-full uppercase"
                                                          x-text="vConfig.channel_name || vConfig.channel"></span>
                                                </template>
                                                <template x-if="vConfig.selected_metric">
                                                    <span class="text-[10px] font-medium text-gray-500 dark:text-gray-400"
                                                          x-text="(allChannelMetrics[vConfig.channel] || {})[vConfig.selected_metric] || vConfig.selected_metric"></span>
                                                </template>
                                            </div>
                                        </div>

                                        <div class="p-6 flex-1 flex flex-col gap-6 min-h-0">
                                            {{-- Data Scope / Dependency selector --}}
                                            <template x-if="allChannelDependencies[vConfig.channel] && Object.keys(allChannelDependencies[vConfig.channel]).length > 0">
                                                <div class="my-2">
                                                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">{{ __('Data Scope / Matrix') }}</label>
                                                    <select x-model="sandboxForm.series_dependencies[vKey]"
                                                            @change="onSandboxDependencyChange(vKey, vConfig)"
                                                            class="bg-white dark:bg-white/5 border border-gray-300 dark:border-white/10 text-gray-950 dark:text-white dark:[color-scheme:dark] text-sm p-2.5 rounded-lg focus:ring-primary-500 focus:border-primary-500 block cursor-pointer w-full">
                                                        <template x-for="(label, key) in (allChannelDependencies[vConfig.channel] || {})" :key="key">
                                                            <option :value="key" x-text="label" :selected="sandboxForm.series_dependencies[vKey] === key" class="bg-white dark:bg-gray-800 text-gray-900 dark:text-white"></option>
                                                        </template>
                                                    </select>
                                                </div>
                                            </template>

                                            {{-- Metric selector for KPI Widgets --}}
                                            <template x-if="sandboxTargetWidget?.source_type === 'kpi' && !vConfig.is_dm_source && Object.keys(getSandboxMetricsForSeries(vKey, vConfig)).length > 0">
                                                <div class="my-2">
                                                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">{{ __('Metric') }}</label>
                                                    <select x-model="sandboxForm.metrics[vConfig.index]"
                                                            class="bg-white dark:bg-white/5 border border-gray-300 dark:border-white/10 text-gray-950 dark:text-white dark:[color-scheme:dark] text-sm p-2.5 rounded-lg focus:ring-primary-500 focus:border-primary-500 block cursor-pointer w-full">
                                                        <template x-for="(label, key) in getSandboxMetricsForSeries(vKey, vConfig)" :key="key">
                                                            <option :value="key" x-text="label" :selected="sandboxForm.metrics[vConfig.index] === key" class="bg-white dark:bg-gray-800 text-gray-900 dark:text-white"></option>
                                                        </template>
                                                    </select>
                                                </div>
                                            </template>

                                            {{-- Metric selector for Metric Widgets --}}
                                            <template x-if="sandboxTargetWidget?.source_type !== 'kpi' && sandboxTargetWidget?.source_type !== 'derived_metric' && vConfig.type !== 'derived_metric'">
                                                <div class="my-2 flex-1 flex flex-col min-h-0">
                                                    <div class="flex items-center justify-between mb-2">
                                                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300">{{ __('Metrics (Ctrl/Cmd to multi-select)') }}</label>
                                                        <button type="button"
                                                                @click="sandboxSelectAllMetrics(vKey)"
                                                                class="text-[11px] font-semibold text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300 hover:underline">
                                                            {{ __('Select All') }}
                                                        </button>
                                                    </div>
                                                    <div class="flex-1 relative min-h-0 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800">
                                                        <div class="absolute inset-0 flex flex-col gap-1 overflow-y-auto p-1 custom-scrollbar">
                                                            <template x-for="(label, key) in (vConfig.allowed_metrics && vConfig.allowed_metrics.length > 0 ? Object.fromEntries(Object.entries(getSandboxMetricsForSeries(vKey, vConfig)).filter(([k]) => vConfig.allowed_metrics.includes(k))) : getSandboxMetricsForSeries(vKey, vConfig))" :key="key">
                                                                <div class="flex items-center justify-between px-3 py-2 text-sm text-gray-700 dark:text-gray-200 rounded-md cursor-pointer transition-colors border border-transparent"
                                                                     :class="sandboxIsMetricSelected(vKey, key) ? 'bg-primary-50 dark:bg-primary-900/30 border-primary-100 dark:border-primary-900/50' : 'hover:bg-gray-100 dark:hover:bg-white/5'">
                                                                    <div @click="sandboxToggleMetric(vKey, key)" class="flex gap-x-3 items-center min-w-0 flex-1">
                                                                        <div class="w-4 h-4 shrink-0 flex items-center justify-center rounded border transition-colors"
                                                                             :class="sandboxIsMetricSelected(vKey, key) ? 'bg-primary-600 border-primary-600' : 'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800'">
                                                                            <svg x-show="sandboxIsMetricSelected(vKey, key)" class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
                                                                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                                                                            </svg>
                                                                        </div>
                                                                        <span class="truncate font-medium" :class="sandboxIsMetricSelected(vKey, key) ? 'text-primary-800 dark:text-primary-200' : ''" x-text="label"></span>
                                                                    </div>
                                                                    <template x-if="sandboxTargetWidget?.widget_type === 'combo'">
                                                                        <button type="button"
                                                                                @click.stop="sandboxToggleComboType(vConfig.index, key)"
                                                                                class="px-1.5 py-0.5 rounded text-[10px] font-semibold uppercase border transition-colors flex-shrink-0 ml-2"
                                                                                :class="sandboxGetComboType(vConfig.index, key) === 'bar' ? 'bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300 border-amber-300 dark:border-amber-700' : 'bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300 border-blue-300 dark:border-blue-700'">
                                                                            <span x-text="sandboxGetComboType(vConfig.index, key)"></span>
                                                                        </button>
                                                                    </template>
                                                                </div>
                                                            </template>
                                                            <template x-if="!getSandboxMetricsForSeries(vKey, vConfig) || Object.keys(getSandboxMetricsForSeries(vKey, vConfig)).length === 0">
                                                                <p class="text-xs text-gray-400 dark:text-gray-500 mt-2 mx-2">{{ __('No metrics available.') }}</p>
                                                            </template>
                                                        </div>
                                                    </div>
                                                </div>
                                            </template>

                                            {{-- Asset filter --}}
                                            <template x-if="allChannelAssets[vConfig.channel] && Object.keys(allChannelAssets[vConfig.channel]).length > 0">
                                                <div class="gap-3 flex-1 flex flex-col min-h-0 mt-2">
                                                    <div class="flex items-center justify-between">
                                                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">{{ __('Assets') }}</label>
                                                        <div class="flex gap-3">
                                                            <button @click="sandboxSelectAllAssets(vKey)"
                                                                    class="text-[11px] font-semibold text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300 hover:underline">
                                                                {{ __('Select All') }}
                                                            </button>
                                                        </div>
                                                    </div>
                                                    <div class="relative">
                                                        <div class="absolute inset-y-0 left-0 w-10 flex items-center justify-center pointer-events-none">
                                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                                 viewBox="0 0 24 24" stroke-width="2"
                                                                 stroke="currentColor"
                                                                 class="w-4 h-4 text-gray-400">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                      d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
                                                            </svg>
                                                        </div>
                                                        <input type="text" x-model="sandboxSearchQueries[vKey]"
                                                               placeholder="{{ __('Search assets...') }}"
                                                               class="bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 bd-search-input">
                                                    </div>
                                                    <div class="flex-1 relative min-h-0">
                                                        <div class="absolute inset-0 flex flex-col gap-1 overflow-y-auto pr-1 custom-scrollbar">
                                                            <template x-for="[assetId, assetName] in Object.entries(getSandboxAssetsForSeries(vKey, vConfig))" :key="assetId">
                                                                <div x-show="(!sandboxSearchQueries[vKey] || assetName.toLowerCase().includes(sandboxSearchQueries[vKey].toLowerCase()))"
                                                                     @click="sandboxToggleAsset(vKey, assetId)"
                                                                     class="flex gap-x-3 items-center px-3 py-2.5 text-sm text-gray-700 dark:text-gray-200 rounded-lg cursor-pointer transition-colors border border-transparent"
                                                                     :class="sandboxIsAssetSelected(vKey, assetId) ? 'bg-primary-50 dark:bg-primary-900/30 border-primary-100 dark:border-primary-900/50' : 'hover:bg-gray-100 dark:hover:bg-white/5'">
                                                                    <div class="w-4 h-4 shrink-0 flex items-center justify-center border transition-colors rounded"
                                                                         :class="sandboxIsAssetSelected(vKey, assetId) ? 'bg-primary-600 border-primary-600' : 'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800'">
                                                                        <svg x-show="sandboxIsAssetSelected(vKey, assetId)"
                                                                             class="w-3 h-3 text-white" fill="none"
                                                                             viewBox="0 0 24 24" stroke-width="3"
                                                                             stroke="currentColor">
                                                                            <path stroke-linecap="round"
                                                                                  stroke-linejoin="round"
                                                                                  d="m4.5 12.75 6 6 9-13.5"/>
                                                                        </svg>
                                                                    </div>
                                                                    <span class="truncate font-medium text-sm"
                                                                          :class="sandboxIsAssetSelected(vKey, assetId) ? 'text-primary-800 dark:text-primary-200' : 'text-gray-700 dark:text-gray-200'"
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
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ============================================================ --}}
        {{-- UNSAVED WIDGET CONTROLS CONFIRMATION MODAL                   --}}
        {{-- ============================================================ --}}
        <x-confirm-modal
            open="showUnsavedWidgetControlsModal"
            title="{{ __('Unsaved Widget Changes') }}"
            icon="heroicon-o-exclamation-triangle"
            color="warning"
            confirm-label="{{ __('Save and Close') }}"
            confirm-color="primary"
            confirm-icon="heroicon-o-check"
            on-confirm="confirmSaveAndCloseWidgetControls()"
            :close-on-confirm="false"
            secondary-label="{{ __('Discard and Close') }}"
            secondary-color="danger"
            on-secondary="confirmDiscardAndCloseWidgetControls()"
            :close-on-secondary="false"
            cancel-label="{{ __('Keep Editing') }}"
            on-cancel="cancelUnsavedWidgetControlsModal()"
        >
            <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ __('You have unsaved changes to this widget\'s configuration. Would you like to save them before closing?') }}
            </p>
        </x-confirm-modal>

        {{-- ============================================================ --}}
        {{-- WIDGET REMOVE CONFIRMATION MODAL                             --}}
        {{-- ============================================================ --}}
        <x-confirm-modal
            open="deleteConfirmOpen"
            title="{{ __('Remove widget') }}"
            icon="heroicon-o-trash"
            color="danger"
            confirm-label="{{ __('Remove') }}"
            confirm-color="danger"
            confirm-icon="heroicon-o-trash"
            on-confirm="proceedDelete()"
            :close-on-confirm="false"
            on-cancel="cancelDeleteConfirm()"
        >
            <p class="text-sm text-gray-500 dark:text-gray-400"
               x-show="deleteConfirmTargets.length === 1">{{ __('Remove this widget from the dashboard?') }}</p>
            <p class="text-sm text-gray-500 dark:text-gray-400"
               x-show="deleteConfirmTargets.length > 1">{{ __('Remove') }} <strong class="text-gray-900 dark:text-white" x-text="deleteConfirmTargets.length"></strong> {{ __('selected widgets from the dashboard?') }}</p>
        </x-confirm-modal>

        {{-- Floating Vertical Multi-Select Action Bar --}}
        <div x-show="selectedWidgetIds.length > 0 && !deleteConfirmOpen" x-cloak
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-x-6 scale-95"
             x-transition:enter-end="opacity-100 translate-x-0 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-x-0 scale-100"
             x-transition:leave-end="opacity-0 translate-x-6 scale-95"
             class="floating-selection-bar fixed right-6 top-1/2 -translate-y-1/2 z-[99999] flex flex-col items-center gap-1.5 p-2 rounded-2xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 shadow-2xl ring-1 ring-black/5 dark:ring-white/10">

            {{-- Selected Badge Counter --}}
            <div class="group relative flex flex-col items-center justify-center px-2.5 py-1.5 rounded-xl bg-primary-50 dark:bg-primary-500/10 border border-primary-200/80 dark:border-primary-500/20 text-primary-600 dark:text-primary-400 min-w-[42px]">
                <span class="text-xs font-bold leading-none" x-text="selectedWidgetIds.length"></span>
                <span class="text-[9px] font-bold uppercase tracking-wider opacity-80 mt-0.5" x-text="selectedWidgetIds.length === 1 ? 'item' : 'items'"></span>
                <div class="pointer-events-none absolute right-full top-1/2 -translate-y-1/2 mr-3 opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap z-[100000]">
                    <div class="rounded-lg bg-gray-900 dark:bg-gray-800 border border-gray-800 dark:border-gray-700 px-3 py-1.5 text-xs font-medium text-white dark:text-gray-200 shadow-xl">
                        {{ __('Selected widgets') }}
                    </div>
                </div>
            </div>

            <div class="w-6 h-px bg-gray-200 dark:bg-gray-800 my-0.5"></div>

            {{-- Select All Button --}}
            <div class="group relative flex items-center justify-center">
                <button x-on:click="selectAllWidgets()"
                        type="button"
                        class="p-2 rounded-xl text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                        :title="'{{ __('Select All') }}'">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </button>
                <div class="pointer-events-none absolute right-full top-1/2 -translate-y-1/2 mr-3 opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap z-[100000]">
                    <div class="rounded-lg bg-gray-900 dark:bg-gray-800 border border-gray-800 dark:border-gray-700 px-3 py-1.5 text-xs font-medium text-white dark:text-gray-200 shadow-xl">
                        {{ __('Select All') }}
                    </div>
                </div>
            </div>

            {{-- Clear Selection Button --}}
            <div class="group relative flex items-center justify-center">
                <button x-on:click="clearWidgetSelection()"
                        type="button"
                        class="p-2 rounded-xl text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                        :title="'{{ __('Clear Selection') }}'">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
                <div class="pointer-events-none absolute right-full top-1/2 -translate-y-1/2 mr-3 opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap z-[100000]">
                    <div class="rounded-lg bg-gray-900 dark:bg-gray-800 border border-gray-800 dark:border-gray-700 px-3 py-1.5 text-xs font-medium text-white dark:text-gray-200 shadow-xl">
                        {{ __('Clear Selection') }}
                    </div>
                </div>
            </div>

            <div class="w-6 h-px bg-gray-200 dark:bg-gray-800 my-0.5"></div>

            {{-- Duplicate Selected Button --}}
            <div class="group relative flex items-center justify-center">
                <button x-on:click="duplicateSelectedWidgets()"
                        type="button"
                        class="p-2 rounded-xl text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300 hover:bg-primary-50 dark:hover:bg-primary-500/10 transition-colors"
                        :title="'{{ __('Duplicate Selected') }}'">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 01-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 011.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 00-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 01-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 00-3.375-3.375h-1.5a1.125 1.125 0 01-1.125-1.125v-1.5a3.375 3.375 0 00-3.375-3.375H9.75" />
                    </svg>
                </button>
                <div class="pointer-events-none absolute right-full top-1/2 -translate-y-1/2 mr-3 opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap z-[100000]">
                    <div class="rounded-lg bg-gray-900 dark:bg-gray-800 border border-gray-800 dark:border-gray-700 px-3 py-1.5 text-xs font-medium text-white dark:text-gray-200 shadow-xl">
                        {{ __('Duplicate Selected') }}
                    </div>
                </div>
            </div>

            {{-- Delete Selected Button --}}
            <div class="group relative flex items-center justify-center">
                <button x-on:click="confirmDeleteSelectedWidgets()"
                        type="button"
                        class="p-2 rounded-xl text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 hover:bg-red-50 dark:hover:bg-red-500/10 transition-colors"
                        :title="'{{ __('Delete Selected') }}'">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                    </svg>
                </button>
                <div class="pointer-events-none absolute right-full top-1/2 -translate-y-1/2 mr-3 opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap z-[100000]">
                    <div class="rounded-lg bg-gray-900 dark:bg-gray-800 border border-gray-800 dark:border-gray-700 px-3 py-1.5 text-xs font-medium text-white dark:text-gray-200 shadow-xl">
                        {{ __('Delete Selected') }}
                    </div>
                </div>
            </div>

        </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/gridstack@12.6.0/dist/gridstack-all.min.js"></script>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/gridstack@12.6.0/dist/gridstack.min.css"/>
        <script src="{{ asset('js/dashboard-renderer.js') }}?v={{ file_exists(public_path('js/dashboard-renderer.js')) ? filemtime(public_path('js/dashboard-renderer.js')) : time() }}"></script>
    @endpush
</x-filament-panels::page>
