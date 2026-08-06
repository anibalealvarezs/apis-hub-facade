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
    @vite(['resources/js/app.js'])
</head>
<body class="bg-gray-50 min-h-screen">
    <div x-data="sharedView({
        totalCount: {{ $widgets->count() }},
        tenant: '{{ $project->subdomain }}'
    })" class="max-w-7xl mx-auto px-4 py-6 space-y-6">
        {{-- Header --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">{{ $dashboard->name }}</h1>
                    @if ($dashboard->description)
                        <p class="text-sm text-gray-500 mt-1">{{ $dashboard->description }}</p>
                    @endif
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-xs text-gray-400">{{ $project->name }}</span>
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

        {{-- Widget Grid --}}
        <div id="view-grid-stack" class="grid-stack">
            @foreach ($widgets as $widget)
                <div class="grid-stack-item"
                     gs-id="{{ $widget->id }}"
                     gs-x="{{ $widget->grid_x }}"
                     gs-y="{{ $widget->grid_y }}"
                     gs-w="{{ $widget->grid_w }}"
                     gs-h="{{ $widget->grid_h }}">
                    <div class="grid-stack-item-content rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-sm relative flex flex-col pd-widget-content">
                        @if ($widget->title || $widget->name)
                            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 flex items-center justify-between flex-shrink-0 rounded-t-xl relative pd-widget-header"
                                 x-data="widgetHeaderPv({{ $widget->id }}, @js($widget->resolved_controls), @js($widget->series_assets_options))"
                                 @reload-widget.window="if ($event.detail.id === {{ $widget->id }}) controls = $event.detail.controls">
                                <div>
                                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">{{ $widget->title ?? $widget->name }}</h3>
                            </div>
                                <div class="flex items-center gap-2">
                                    @if (!empty($widget->series_assets_options))
                                        <div class="relative">
                                            <button @click="openFilters = !openFilters; $el.closest('.grid-stack-item').style.zIndex = openFilters ? 50 : ''" @click.away="openFilters = false; $el.closest('.grid-stack-item').style.zIndex = ''" class="text-xs rounded border border-gray-300 bg-white text-gray-700 py-1 px-2 hover:bg-gray-50 flex items-center gap-1 shadow-sm">
                                                <svg class="w-3 h-3 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
                                                <span class="font-medium">Filters</span>
                                                <span x-show="getActiveFilterCount() > 0" class="ml-1 bg-primary-100 text-primary-700 text-[10px] font-bold px-1.5 py-0.5 rounded-full" x-text="getActiveFilterCount()"></span>
                                            </button>
                                            
                                            <div x-show="openFilters" x-transition x-cloak class="absolute right-0 mt-2 w-72 bg-white rounded-xl shadow-xl border border-gray-200 z-50 flex flex-col overflow-hidden">
                                                <div class="max-h-96 overflow-y-auto p-4 space-y-6">
                                                    <template x-for="(seriesData, seriesKey) in seriesOptions" :key="seriesKey">
                                                        <div class="space-y-2">
                                                            <div class="flex items-center justify-between">
                                                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider" x-text="seriesData.label"></label>
                                                                <div class="flex gap-2">
                                                                    <button @click="selectAll(seriesKey)" class="text-[10px] font-medium text-primary-600 hover:underline">All</button>
                                                                    <button @click="clearAll(seriesKey)" class="text-[10px] font-medium text-gray-500 hover:underline">Clear</button>
                                                                </div>
                                                            </div>
                                                            <div class="relative">
                                                                <div class="absolute inset-y-0 left-0 w-8 flex items-center justify-center pointer-events-none">
                                                                    <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                                                                </div>
                                                                <input type="text" x-model="searchQueries[seriesKey]" placeholder="Search..." class="bg-gray-50 border border-gray-300 text-gray-900 text-[11px] rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full pl-8 p-1.5">
                                                            </div>
                                                            <div class="flex flex-col gap-1 max-h-40 overflow-y-auto pr-1">
                                                                <template x-for="[assetId, assetName] in Object.entries(seriesData.options)" :key="assetId">
                                                                    <div x-show="searchQueries[seriesKey] === '' || assetName.toLowerCase().includes(searchQueries[seriesKey].toLowerCase())"
                                                                         @click="toggleAsset(seriesKey, assetId)"
                                                                         class="flex gap-x-2 items-center px-2 py-1.5 text-xs text-gray-700 rounded cursor-pointer transition-colors"
                                                                         :class="isSelected(seriesKey, assetId) ? 'bg-primary-50' : 'hover:bg-gray-100'">
                                                                        <div class="w-4 h-4 shrink-0 flex items-center justify-center rounded-sm border transition-colors"
                                                                             :class="isSelected(seriesKey, assetId) ? 'bg-primary-600 border-primary-600' : 'border-gray-300 bg-white'">
                                                                            <svg x-show="isSelected(seriesKey, assetId)" class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
                                                                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                                                                            </svg>
                                                                        </div>
                                                                        <span class="truncate font-medium" :class="isSelected(seriesKey, assetId) ? 'text-primary-700' : ''" x-text="assetName"></span>
                                                                    </div>
                                                                </template>
                                                            </div>
                                                        </div>
                                                    </template>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif
                        <div class="widget-content flex-grow p-4 relative overflow-y-auto"
                             x-init="renderWidget({{ $widget->id }}, $el, @js($widget->resolved_controls))">
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

        <div class="text-center text-xs text-gray-400 pb-6">
            Powered by APIs Hub
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/gridstack@12.6.0/dist/gridstack-all.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/gridstack@12.6.0/dist/gridstack.min.css"/>
    <script src="{{ asset('js/dashboard-renderer.js') }}?v={{ filemtime(public_path('js/dashboard-renderer.js')) }}"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
</body>
</html>
