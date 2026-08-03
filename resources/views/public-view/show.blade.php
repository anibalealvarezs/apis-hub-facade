<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="referrer" content="no-referrer">
    <title>{{ $dashboard->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: { '50': '#eff6ff', '100': '#dbeafe', '200': '#bfdbfe', '300': '#93c5fd', '400': '#60a5fa', '500': '#3b82f6', '600': '#2563eb', '700': '#1d4ed8', '800': '#1e40af', '900': '#1e3a8a', '950': '#172554' },
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="{{ asset('css/dashboard-builder.css') }}" />
    <style>
        body { font-family: 'Outfit', system-ui, sans-serif; }
    </style>
</head>
<body class="bg-gray-50 dark:bg-gray-950 min-h-screen {{ $isEmbedded ? 'p-2' : '' }}">
    <div x-data="publicView()" x-init="init()" class="{{ $isEmbedded ? 'w-full' : 'max-w-7xl mx-auto px-4 py-6' }} space-y-4">
        @if (!$isEmbedded)
            {{-- Toolbar / Header --}}
            <div class="flex items-center justify-between gap-4 rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 p-4 shadow-sm">
                <div class="flex items-center gap-2 min-w-0">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-gray-500 dark:text-gray-400 flex-shrink-0">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
                    </svg>
                    <div class="truncate">
                        <h1 class="text-lg font-medium text-gray-900 dark:text-white truncate">{{ $dashboard->name }}</h1>
                        @if ($dashboard->description)
                            <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $dashboard->description }}</p>
                        @endif
                    </div>
                </div>

                <div class="flex items-center gap-2 flex-shrink-0">
                    <button @click="openDashboardControls()"
                            class="px-3 py-2 rounded-lg text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors flex items-center gap-1.5 border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-gray-500">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <span>{{ __('Controls') }}</span>
                    </button>
                </div>
            </div>
        @endif

        {{-- Widget Grid --}}
        <div id="view-grid-stack" class="grid-stack">
            @foreach ($widgets as $widget)
                <div class="grid-stack-item"
                     gs-id="{{ $widget['id'] ?? $widget->id }}"
                     gs-x="{{ $widget['grid_x'] ?? $widget->grid_x }}"
                     gs-y="{{ $widget['grid_y'] ?? $widget->grid_y }}"
                     gs-w="{{ $widget['grid_w'] ?? $widget->grid_w }}"
                     gs-h="{{ $widget['grid_h'] ?? $widget->grid_h }}">
                    <div class="grid-stack-item-content rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-sm relative flex flex-col overflow-hidden">
                        {{-- Widget Header --}}
                        <div class="widget-header rounded-t-lg flex items-center justify-between px-3 py-2 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 gap-4 flex-shrink-0">
                            <div class="flex items-center gap-2 min-w-0">
                                @php $hasCustom = !empty($widget['controls']) || !empty($widget->controls); @endphp
                                <span class="inline-block w-2 h-2 rounded-full {{ $hasCustom ? 'bg-blue-400' : 'bg-green-400' }} flex-shrink-0"
                                      title="{{ $hasCustom ? 'Has custom controls' : 'Inheriting dashboard controls' }}"></span>
                                <span class="text-sm font-medium text-gray-900 dark:text-white truncate"
                                      title="{{ $widget['title'] ?? $widget['name'] ?? $widget->title ?? $widget->name }}">
                                    {{ $widget['title'] ?? $widget['name'] ?? $widget->title ?? $widget->name }}
                                </span>
                            </div>
                            <div class="flex items-center gap-1 flex-shrink-0">
                                @if (!empty($widget['description']) || !empty($widget->description))
                                    <div class="group relative flex items-center justify-center p-1">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 cursor-help transition-colors">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                                        </svg>
                                        <div class="pointer-events-none absolute bottom-full mb-2 w-64 opacity-0 transition-opacity group-hover:opacity-100 z-50 right-0">
                                            <div class="rounded-lg bg-gray-900 dark:bg-gray-700 px-3 py-2 text-xs text-white shadow-lg whitespace-normal text-left">
                                                <span>{{ $widget['description'] ?? $widget->description }}</span>
                                                <div class="absolute -bottom-1 right-2 h-2 w-2 rotate-45 bg-gray-900 dark:bg-gray-700"></div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Widget Content --}}
                        <div class="widget-content flex-grow p-4 relative overflow-y-auto"
                             x-init="renderWidget({{ $widget['id'] ?? $widget->id }}, $el, {{ json_encode($widget['resolved_controls'] ?? $widget->resolved_controls ?? []) }})">
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @if (empty($widgets) || (is_countable($widgets) && count($widgets) === 0))
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 p-12 text-center">
                <p class="text-gray-400 dark:text-gray-500 text-lg">No widgets on this dashboard yet</p>
            </div>
        @endif

        {{-- ============================================================ --}}
        {{-- DASHBOARD-LEVEL CONTROLS MODAL (Excludes Asset Group)        --}}
        {{-- ============================================================ --}}
        <div x-show="showDashboardControls" x-cloak class="fixed inset-0 z-50 flex items-center justify-center">
            <div class="absolute inset-0 bg-black/50" x-on:click="showDashboardControls = false"></div>
            <div class="relative bg-white dark:bg-gray-900 rounded-xl shadow-xl max-w-xl w-full mx-4 max-h-[90vh] overflow-y-auto p-6 space-y-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('Dashboard Controls') }}</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">These defaults apply to all widgets on this public view.</p>

                {{-- Date Range --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('Date Range') }}</label>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ __('Start') }}</span>
                            <input type="date" x-model="dashboardControls.date_start"
                                   :max="dashboardControls.date_end || '{{ date('Y-m-d', strtotime('-1 day')) }}'"
                                   class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 text-sm"/>
                        </div>
                        <div>
                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ __('End') }}</span>
                            <input type="date" x-model="dashboardControls.date_end" max="{{ date('Y-m-d', strtotime('-1 day')) }}"
                                   class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 text-sm"/>
                        </div>
                    </div>
                </div>

                {{-- Zero Handling --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('Zero / Missing Data') }}</label>
                    <select x-model="dashboardControls.zero_handling"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 text-sm">
                        <option value="remove">{{ __('Remove zeros from results') }}</option>
                        <option value="keep">{{ __('Keep zeros in results') }}</option>
                        <option value="trim">{{ __('Trim leading/trailing zeros') }}</option>
                    </select>
                </div>

                {{-- Granularity --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('Granularity') }}</label>
                    <select x-model="dashboardControls.granularity"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 text-sm">
                        <option value="daily">{{ __('Daily') }}</option>
                        <option value="weekly">{{ __('Weekly') }}</option>
                        <option value="monthly">{{ __('Monthly') }}</option>
                        <option value="query">{{ __('Query') }}</option>
                        <option value="dimensions.page">{{ __('Page') }}</option>
                        <option value="country">{{ __('Country') }}</option>
                        <option value="device">{{ __('Device') }}</option>
                        <option value="post">{{ __('Post') }}</option>
                    </select>
                </div>

                {{-- Edge Cases --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('Edge Cases') }}</label>
                    <div class="space-y-3">
                        <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                            <input type="checkbox" x-model="dashboardControls.edge_case_weighted"
                                   class="rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500"/>
                            {{ __('Weighted regression (WLS)') }}
                        </label>
                        <select x-model="dashboardControls.edge_case_grouping"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 text-sm">
                            <option value="none">{{ __('No grouping') }}</option>
                            <option value="histogram">{{ __('Auto histogram-elbow') }}</option>
                            <option value="percentile">{{ __('Bottom percentile') }}</option>
                        </select>
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-2 border-t border-gray-200 dark:border-gray-700">
                    <button class="px-4 py-2 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 text-sm"
                            x-on:click="showDashboardControls = false">{{ __('Cancel') }}</button>
                    <button class="px-4 py-2 rounded-lg bg-primary-600 text-white hover:bg-primary-500 text-sm font-semibold"
                            x-on:click="confirmDashboardControls()">{{ __('Save Controls') }}</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/gridstack@12.6.0/dist/gridstack-all.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/gridstack@12.6.0/dist/gridstack.min.css"/>
    <script src="{{ asset('js/dashboard-renderer.js') }}?v={{ filemtime(public_path('js/dashboard-renderer.js')) }}"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
    <script>
        function publicView() {
            return {
                loadedCount: 0,
                totalCount: {{ is_array($widgets) ? count($widgets) : $widgets->count() }},
                tenant: '{{ $project->subdomain }}',
                pvToken: '{{ $pv->token }}',
                isEmbedded: {{ $isEmbedded ? 'true' : 'false' }},
                showDashboardControls: false,
                dashboardControls: {
                    date_start: '{{ $dashboard->controls['date_start'] ?? '' }}',
                    date_end: '{{ $dashboard->controls['date_end'] ?? '' }}',
                    granularity: '{{ $dashboard->controls['granularity'] ?? 'daily' }}',
                    zero_handling: '{{ $dashboard->controls['zero_handling'] ?? 'remove' }}',
                    edge_case_weighted: {{ ($dashboard->controls['edge_case_weighted'] ?? true) ? 'true' : 'false' }},
                    edge_case_grouping: '{{ $dashboard->controls['edge_case_grouping'] ?? 'none' }}'
                },

                init() {
                    this.$nextTick(() => {
                        const tryInit = () => {
                            if (typeof GridStack !== 'undefined') {
                                GridStack.init({
                                    staticGrid: true,
                                    float: true,
                                    column: 12,
                                    cellHeight: 100,
                                    margin: 12,
                                    minRow: 6
                                }, '#view-grid-stack');

                                if (this.isEmbedded) {
                                    this.notifyResize();
                                }
                            } else {
                                setTimeout(tryInit, 50);
                            }
                        };
                        tryInit();
                    });
                },

                openDashboardControls() {
                    this.showDashboardControls = true;
                },

                confirmDashboardControls() {
                    this.showDashboardControls = false;
                    const gridItems = document.querySelectorAll('.grid-stack-item');
                    gridItems.forEach(item => {
                        const widgetId = item.getAttribute('gs-id');
                        const el = item.querySelector('.widget-content');
                        if (el) {
                            const rawControls = JSON.parse(el.getAttribute('data-raw-controls') || '{}');
                            const merged = { ...rawControls, ...this.dashboardControls };
                            this.renderWidget(widgetId, el, merged);
                        }
                    });
                },

                notifyResize() {
                    const sendMsg = () => {
                        window.parent.postMessage({
                            type: 'apis-hub-resize',
                            height: document.body.scrollHeight
                        }, '*');
                    };
                    sendMsg();
                    setTimeout(sendMsg, 500);
                    setTimeout(sendMsg, 1500);
                },

                renderWidget(widgetId, el, controls) {
                    el.setAttribute('data-raw-controls', JSON.stringify(controls));
                    let effectiveControls = { ...controls, pv_token: this.pvToken };

                    const tryRender = () => {
                        if (window.dashboardRenderer) {
                            window.dashboardRenderer.renderWidget(widgetId, el, effectiveControls, this.tenant)
                                .then(() => {
                                    this.loadedCount++;
                                    if (this.isEmbedded) this.notifyResize();
                                })
                                .catch(() => {
                                    this.loadedCount++;
                                });
                        } else {
                            setTimeout(tryRender, 50);
                        }
                    };
                    tryRender();
                }
            };
        }
    </script>
</body>
</html>
