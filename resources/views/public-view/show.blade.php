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
<body class="bg-gray-50 min-h-screen {{ $isEmbedded ? 'p-2' : '' }}">
    <div x-data="publicView()" x-init="init()" class="{{ $isEmbedded ? 'w-full' : 'max-w-7xl mx-auto px-4 py-6' }} space-y-6">
        @if (!$isEmbedded)
            {{-- Header --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">{{ $dashboard->name }}</h1>
                        @if ($dashboard->description)
                            <p class="text-sm text-gray-500 mt-1">{{ $dashboard->description }}</p>
                        @endif
                    </div>
                </div>
                @if ($dashboard->controls)
                    <div class="flex flex-wrap gap-2 mt-3">
                        @php $c = $dashboard->controls; @endphp
                        @if (!empty($c['channel']))
                            <span class="px-2 py-1 rounded-full bg-gray-100 text-xs text-gray-600">{{ \Illuminate\Support\Str::headline($c['channel']) }}</span>
                        @endif
                        @if (!empty($c['granularity']))
                            <span class="px-2 py-1 rounded-full bg-gray-100 text-xs text-gray-600">{{ ucfirst($c['granularity']) }}</span>
                        @endif
                    </div>
                @endif
            </div>
        @endif

        {{-- Widget Grid --}}
        <div id="view-grid-stack" class="grid-stack">
            @foreach ($widgets as $widget)
                <div class="grid-stack-item"
                     gs-id="{{ $widget->id }}"
                     gs-x="{{ $widget->grid_x }}"
                     gs-y="{{ $widget->grid_y }}"
                     gs-w="{{ $widget->grid_w }}"
                     gs-h="{{ $widget->grid_h }}">
                    <div class="grid-stack-item-content rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-sm relative flex flex-col" style="overflow: visible !important;">
                        @if ($widget->title || $widget->name)
                            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 flex items-center justify-between flex-shrink-0 rounded-t-xl relative" style="z-index: 10;"
                                 x-data="widgetHeader({{ $widget->id }}, '{{ addslashes(json_encode($widget->resolved_controls)) }}', '{{ addslashes(json_encode($widget->series_assets_options)) }}')"
                                 @reload-widget.window="if ($event.detail.id === {{ $widget->id }}) controls = $event.detail.controls">
                                <div>
                                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">{{ $widget->title ?? $widget->name }}</h3>
                                    <div class="flex flex-wrap gap-1 mt-1" x-show="getBadges().length > 0">
                                        <template x-for="(badge, index) in getBadges()" :key="index">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full font-medium bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-300 border border-gray-200 dark:border-gray-600 shadow-sm" style="font-size: 10px; line-height: 14px; padding: 2px 8px;">
                                                <span class="font-bold mr-1" x-text="badge.label + ':'"></span>
                                                <span x-text="badge.text"></span>
                                            </span>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        @endif
                        <div class="widget-content flex-grow p-4 relative overflow-y-auto"
                             x-init="renderWidget({{ $widget->id }}, $el, {{ json_encode($widget->resolved_controls) }})">
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @if ($widgets->isEmpty())
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
                <p class="text-gray-400 text-lg">No widgets on this dashboard yet</p>
            </div>
        @endif
    </div>

    <script src="https://cdn.jsdelivr.net/npm/gridstack@12.6.0/dist/gridstack-all.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/gridstack@12.6.0/dist/gridstack.min.css"/>
    <script src="{{ asset('js/dashboard-renderer.js') }}?v={{ filemtime(public_path('js/dashboard-renderer.js')) }}"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
    <script>
        function publicView() {
            return {
                loadedCount: 0,
                totalCount: {{ $widgets->count() }},
                tenant: '{{ $project->subdomain }}',
                pvToken: '{{ $pv->token }}',
                isEmbedded: {{ $isEmbedded ? 'true' : 'false' }},

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

        document.addEventListener('alpine:init', () => {
            Alpine.data('widgetHeader', (widgetId, rawControls, rawSeriesOptions) => ({
                widgetId: widgetId,
                controls: JSON.parse(rawControls),
                seriesOptions: JSON.parse(rawSeriesOptions) || {},
                init() {},
                getBadges() { return []; }
            }));
        });
    </script>
</body>
</html>
