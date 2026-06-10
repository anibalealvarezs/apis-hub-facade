<x-filament-panels::page>
    <link rel="stylesheet" href="{{ asset('css/dashboard-builder.css') }}" />

    <div x-data="dashboardView()" x-init="init()" class="space-y-4">
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

        {{-- Controls Summary --}}
        @if ($this->dashboard->controls)
            <div class="flex flex-wrap gap-3 text-xs">
                @php $c = $this->dashboard->controls; @endphp
                @if (!empty($c['channel']))
                    <span class="px-2 py-1 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400">
                        Channel: {{ \Illuminate\Support\Str::headline($c['channel']) }}
                    </span>
                @endif
                @if (!empty($c['asset']))
                    <span class="px-2 py-1 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400">
                        Asset: {{ $c['asset'] }}
                    </span>
                @endif
                @if (!empty($c['granularity']))
                    <span class="px-2 py-1 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400">
                        {{ ucfirst($c['granularity']) }}
                    </span>
                @endif
                @if (!empty($c['date_start']) || !empty($c['date_end']))
                    <span class="px-2 py-1 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400">
                        {{ $c['date_start'] ?? '—' }} → {{ $c['date_end'] ?? '—' }}
                    </span>
                @endif
            </div>
        @endif

        {{-- Grid --}}
        <div id="view-grid" class="gap-4">
            @foreach ($this->widgets as $widget)
                <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-sm overflow-hidden"
                     style="grid-column: span {{ min($widget['grid_w'], 12) }};">
                    @if ($widget['title'] || $widget['name'])
                        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 flex items-center justify-between">
                            <h3 class="text-sm font-medium text-gray-900 dark:text-white">{{ $widget['title'] ?? $widget['name'] }}</h3>
                            @if (!empty($widget['resolved_controls']['channel']))
                                <span class="text-xs text-gray-400 dark:text-gray-500">{{ \Illuminate\Support\Str::headline($widget['resolved_controls']['channel']) }}</span>
                            @endif
                        </div>
                    @endif
                    <div class="widget-content" style="height: {{ max(100, $widget['grid_h'] * 80) }}px;"
                         x-init="renderWidget({{ $widget['id'] }}, $el, {{ json_encode($widget['resolved_controls']) }})">
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
        <script src="{{ asset('js/dashboard-renderer.js') }}"></script>
        <script>
            function dashboardView() {
                return {
                    loadedCount: 0,
                    totalCount: {{ count($this->widgets) }},
                    tenant: '{{ \Filament\Facades\Filament::getTenant()?->subdomain ?? '' }}',

                    init() {},

                    renderWidget(widgetId, el, controls) {
                        window.dashboardRenderer.renderWidget(widgetId, el, controls, this.tenant)
                            .then(() => { this.loadedCount++; })
                            .catch(() => { this.loadedCount++; });
                    },

                    refreshAll() {
                        this.loadedCount = 0;
                        document.querySelectorAll('.widget-content').forEach(el => {
                            const widgetId = parseInt(el.getAttribute('x-init')?.match(/\d+/)?.[0] || '0');
                            // Re-trigger by calling renderWidget. We'll just reload the page for simplicity
                            location.reload();
                        });
                    },
                };
            }
        </script>
    @endpush
</x-filament-panels::page>
