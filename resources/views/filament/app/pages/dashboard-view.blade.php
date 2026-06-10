<x-filament-panels::page>
    <div x-data="dashboardView()" x-init="init()" class="space-y-4">
        {{-- Header --}}
        <div class="flex items-center justify-between gap-4 rounded-xl bg-gray-50 dark:bg-gray-900 p-4">
            <div>
                <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ $this->dashboard->name }}</h1>
                @if ($this->dashboard->description)
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $this->dashboard->description }}</p>
                @endif
            </div>
        </div>

        {{-- Grid --}}
        <div id="view-grid" class="grid grid-cols-12 gap-4">
            @foreach ($this->widgets as $widget)
                <div class="col-span-{{ min($widget['grid_w'], 12) }} row-span-{{ $widget['grid_h'] }}"
                     style="grid-column: span {{ min($widget['grid_w'], 12) }}; grid-row: span {{ $widget['grid_h'] }}">
                    <div class="h-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-sm overflow-hidden">
                        @if ($widget['title'] || $widget['name'])
                            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
                                <h3 class="text-sm font-medium text-gray-900 dark:text-white">{{ $widget['title'] ?? $widget['name'] }}</h3>
                            </div>
                        @endif
                        <div class="p-4 h-[calc(100%-3rem)] flex items-center justify-center"
                             x-ref="widget-{{ $widget['id'] }}">
                            <div class="text-center text-gray-400 dark:text-gray-500">
                                <x-filament::icon name="heroicon-o-chart-bar" class="w-8 h-8 mx-auto" />
                                <p class="text-xs mt-2">Loading data...</p>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @if (empty($this->widgets))
            <div class="flex flex-col items-center justify-center py-24 text-center">
                <x-filament::icon name="heroicon-o-squares-2x2" class="w-16 h-16 text-gray-300 dark:text-gray-600 mb-4" />
                <p class="text-gray-500 dark:text-gray-400 text-lg">No widgets on this dashboard yet</p>
            </div>
        @endif
    </div>

    @push('scripts')
        <script>
            function dashboardView() {
                return {
                    init() {
                        // Widget data fetching will be implemented in Phase 4
                    },
                };
            }
        </script>
    @endpush
</x-filament-panels::page>
