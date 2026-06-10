<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $dashboard->name }} — {{ $project->name }}</title>
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
<body class="bg-gray-50 min-h-screen">
    <div x-data="sharedView()" x-init="init()" class="max-w-7xl mx-auto px-4 py-6 space-y-6">
        {{-- Header --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">{{ $dashboard->name }}</h1>
                    @if ($dashboard->description)
                        <p class="text-sm text-gray-500 mt-1">{{ $dashboard->description }}</p>
                    @endif
                </div>
                <span class="text-xs text-gray-400">{{ $project->name }}</span>
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

        {{-- Widget Grid --}}
        <div id="view-grid" class="gap-4">
            @foreach ($widgets as $widget)
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden"
                     style="grid-column: span {{ min($widget->grid_w, 12) }};">
                    @if ($widget->title || $widget->name)
                        <div class="px-4 py-3 border-b border-gray-200 bg-gray-50 flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-gray-900">{{ $widget->title ?? $widget->name }}</h3>
                            @if (!empty($widget->resolved_controls['channel']))
                                <span class="text-xs text-gray-400">{{ \Illuminate\Support\Str::headline($widget->resolved_controls['channel']) }}</span>
                            @endif
                        </div>
                    @endif
                    <div class="widget-content" style="height: {{ max(100, $widget->grid_h * 80) }}px;"
                         x-init="renderWidget({{ $widget->id }}, $el, {{ json_encode($widget->resolved_controls) }})">
                    </div>
                </div>
            @endforeach
        </div>

        @if ($widgets->isEmpty())
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
                <p class="text-gray-400 text-lg">No widgets on this dashboard yet</p>
            </div>
        @endif

        <div class="text-center text-xs text-gray-400 pb-6">
            Powered by APIs Hub
        </div>
    </div>

    <script src="{{ asset('js/dashboard-renderer.js') }}"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
    <script>
        function sharedView() {
            return {
                loadedCount: 0,
                totalCount: {{ $widgets->count() }},
                tenant: '{{ $project->subdomain }}',

                init() {},

                renderWidget(widgetId, el, controls) {
                    window.dashboardRenderer.renderWidget(widgetId, el, controls, this.tenant)
                        .then(() => { this.loadedCount++; })
                        .catch(() => { this.loadedCount++; });
                },
            };
        }
    </script>
</body>
</html>
