@php
    $changedFields = [];
    if ($version->change_summary && str_starts_with($version->change_summary, 'Updated: ')) {
        $summaryPart = substr($version->change_summary, 9);
        $changedDisplayNames = array_map('trim', explode(',', $summaryPart));
        foreach ($changedDisplayNames as $displayName) {
            $changedFields[] = str_replace(' ', '_', $displayName);
        }
    }
    $isChanged = fn(string $attr) => in_array($attr, $changedFields, true);
@endphp

<div class="space-y-6 p-4">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="md:col-span-2 space-y-4">
            <div class="grid grid-cols-2 gap-3 text-sm">
                <div>
                    <span class="font-medium text-gray-500 dark:text-gray-400">Version</span>
                    <p>#{{ $version->version_number }}</p>
                </div>
                <div>
                    <span class="font-medium text-gray-500 dark:text-gray-400">Changed by</span>
                    <p>{{ $version->user?->name ?? 'System' }}</p>
                </div>
                <div>
                    <span class="font-medium text-gray-500 dark:text-gray-400">Changed at</span>
                    <p>{{ $version->created_at->format('M j, Y H:i') }}</p>
                </div>
                @if($version->change_summary)
                    <div>
                        <span class="font-medium text-gray-500 dark:text-gray-400">Summary</span>
                        <p>{{ $version->change_summary }}</p>
                    </div>
                @endif
            </div>

            @if($version->name !== null)
                <div class="rounded-lg p-3 {{ $isChanged('name') ? 'bg-warning-50 dark:bg-warning-400/10 ring-1 ring-warning-300 dark:ring-warning-600' : 'bg-gray-50 dark:bg-gray-900/50' }}">
                    <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">
                        Name
                        @if($isChanged('name'))
                            <span class="ml-2 inline-flex items-center px-1.5 py-0.5 text-xs font-medium rounded bg-warning-100 text-warning-700 dark:bg-warning-400/20 dark:text-warning-300">changed</span>
                        @endif
                    </h4>
                    <p class="text-sm">{{ $version->name }}</p>
                </div>
            @endif

            @if($version->description !== null)
                <div class="rounded-lg p-3 {{ $isChanged('description') ? 'bg-warning-50 dark:bg-warning-400/10 ring-1 ring-warning-300 dark:ring-warning-600' : 'bg-gray-50 dark:bg-gray-900/50' }}">
                    <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">
                        Description
                        @if($isChanged('description'))
                            <span class="ml-2 inline-flex items-center px-1.5 py-0.5 text-xs font-medium rounded bg-warning-100 text-warning-700 dark:bg-warning-400/20 dark:text-warning-300">changed</span>
                        @endif
                    </h4>
                    <p class="text-sm">{{ $version->description ?: '—' }}</p>
                </div>
            @endif

            @if($version->calculation_type !== null || $version->getAttribute('calculation_type') !== null)
                @php $val = $version->calculation_type; @endphp
                <div class="rounded-lg p-3 {{ $isChanged('calculation_type') ? 'bg-warning-50 dark:bg-warning-400/10 ring-1 ring-warning-300 dark:ring-warning-600' : 'bg-gray-50 dark:bg-gray-900/50' }}">
                    <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">
                        Calculation Type
                        @if($isChanged('calculation_type'))
                            <span class="ml-2 inline-flex items-center px-1.5 py-0.5 text-xs font-medium rounded bg-warning-100 text-warning-700 dark:bg-warning-400/20 dark:text-warning-300">changed</span>
                        @endif
                    </h4>
                    <p class="text-sm">{{ $val ?: '—' }}</p>
                </div>
            @endif

            @if($version->is_active !== null)
                <div class="rounded-lg p-3 {{ $isChanged('is_active') ? 'bg-warning-50 dark:bg-warning-400/10 ring-1 ring-warning-300 dark:ring-warning-600' : 'bg-gray-50 dark:bg-gray-900/50' }}">
                    <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">
                        Active
                        @if($isChanged('is_active'))
                            <span class="ml-2 inline-flex items-center px-1.5 py-0.5 text-xs font-medium rounded bg-warning-100 text-warning-700 dark:bg-warning-400/20 dark:text-warning-300">changed</span>
                        @endif
                    </h4>
                    <p class="text-sm">{{ $version->is_active ? 'Yes' : 'No' }}</p>
                </div>
            @endif

            @if($version->output_granularity !== null)
                <div class="rounded-lg p-3 {{ $isChanged('output_granularity') ? 'bg-warning-50 dark:bg-warning-400/10 ring-1 ring-warning-300 dark:ring-warning-600' : 'bg-gray-50 dark:bg-gray-900/50' }}">
                    <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">
                        Output Granularity
                        @if($isChanged('output_granularity'))
                            <span class="ml-2 inline-flex items-center px-1.5 py-0.5 text-xs font-medium rounded bg-warning-100 text-warning-700 dark:bg-warning-400/20 dark:text-warning-300">changed</span>
                        @endif
                    </h4>
                    <p class="text-sm">{{ $version->output_granularity ?: '—' }}</p>
                </div>
            @endif

            @if($version->is_public !== null)
                <div class="rounded-lg p-3 {{ $isChanged('is_public') ? 'bg-warning-50 dark:bg-warning-400/10 ring-1 ring-warning-300 dark:ring-warning-600' : 'bg-gray-50 dark:bg-gray-900/50' }}">
                    <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">
                        Public
                        @if($isChanged('is_public'))
                            <span class="ml-2 inline-flex items-center px-1.5 py-0.5 text-xs font-medium rounded bg-warning-100 text-warning-700 dark:bg-warning-400/20 dark:text-warning-300">changed</span>
                        @endif
                    </h4>
                    <p class="text-sm">{{ $version->is_public ? 'Yes' : 'No' }}</p>
                </div>
            @endif

            @if($version->is_default !== null)
                <div class="rounded-lg p-3 {{ $isChanged('is_default') ? 'bg-warning-50 dark:bg-warning-400/10 ring-1 ring-warning-300 dark:ring-warning-600' : 'bg-gray-50 dark:bg-gray-900/50' }}">
                    <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">
                        Default Dashboard
                        @if($isChanged('is_default'))
                            <span class="ml-2 inline-flex items-center px-1.5 py-0.5 text-xs font-medium rounded bg-warning-100 text-warning-700 dark:bg-warning-400/20 dark:text-warning-300">changed</span>
                        @endif
                    </h4>
                    <p class="text-sm">{{ $version->is_default ? 'Yes' : 'No' }}</p>
                </div>
            @endif

            @if($version->title !== null)
                <div class="rounded-lg p-3 {{ $isChanged('title') ? 'bg-warning-50 dark:bg-warning-400/10 ring-1 ring-warning-300 dark:ring-warning-600' : 'bg-gray-50 dark:bg-gray-900/50' }}">
                    <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">
                        Title
                        @if($isChanged('title'))
                            <span class="ml-2 inline-flex items-center px-1.5 py-0.5 text-xs font-medium rounded bg-warning-100 text-warning-700 dark:bg-warning-400/20 dark:text-warning-300">changed</span>
                        @endif
                    </h4>
                    <p class="text-sm">{{ $version->title ?: '—' }}</p>
                </div>
            @endif

            @if($version->source_type !== null)
                <div class="rounded-lg p-3 {{ $isChanged('source_type') ? 'bg-warning-50 dark:bg-warning-400/10 ring-1 ring-warning-300 dark:ring-warning-600' : 'bg-gray-50 dark:bg-gray-900/50' }}">
                    <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">
                        Source Type
                        @if($isChanged('source_type'))
                            <span class="ml-2 inline-flex items-center px-1.5 py-0.5 text-xs font-medium rounded bg-warning-100 text-warning-700 dark:bg-warning-400/20 dark:text-warning-300">changed</span>
                        @endif
                    </h4>
                    <p class="text-sm">{{ $version->source_type }}</p>
                </div>
            @endif

            @if($version->widget_type !== null)
                <div class="rounded-lg p-3 {{ $isChanged('widget_type') ? 'bg-warning-50 dark:bg-warning-400/10 ring-1 ring-warning-300 dark:ring-warning-600' : 'bg-gray-50 dark:bg-gray-900/50' }}">
                    <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">
                        Widget Type
                        @if($isChanged('widget_type'))
                            <span class="ml-2 inline-flex items-center px-1.5 py-0.5 text-xs font-medium rounded bg-warning-100 text-warning-700 dark:bg-warning-400/20 dark:text-warning-300">changed</span>
                        @endif
                    </h4>
                    <p class="text-sm">{{ $version->widget_type }}</p>
                </div>
            @endif

            @if($version->grid_x !== null)
                <div class="rounded-lg p-3 {{ $isChanged('grid_x') || $isChanged('grid_y') || $isChanged('grid_w') || $isChanged('grid_h') ? 'bg-warning-50 dark:bg-warning-400/10 ring-1 ring-warning-300 dark:ring-warning-600' : 'bg-gray-50 dark:bg-gray-900/50' }}">
                    <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">
                        Grid Position
                        @if($isChanged('grid_x') || $isChanged('grid_y') || $isChanged('grid_w') || $isChanged('grid_h'))
                            <span class="ml-2 inline-flex items-center px-1.5 py-0.5 text-xs font-medium rounded bg-warning-100 text-warning-700 dark:bg-warning-400/20 dark:text-warning-300">changed</span>
                        @endif
                    </h4>
                    <p class="text-sm">x: {{ $version->grid_x }}, y: {{ $version->grid_y }}, w: {{ $version->grid_w }}, h: {{ $version->grid_h }}</p>
                </div>
            @endif

            @if($version->getAttribute('ast'))
                <div class="rounded-lg p-3 {{ $isChanged('ast') ? 'bg-warning-50 dark:bg-warning-400/10 ring-1 ring-warning-300 dark:ring-warning-600' : 'bg-gray-50 dark:bg-gray-900/50' }}">
                    <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">
                        Formula (AST)
                        @if($isChanged('ast'))
                            <span class="ml-2 inline-flex items-center px-1.5 py-0.5 text-xs font-medium rounded bg-warning-100 text-warning-700 dark:bg-warning-400/20 dark:text-warning-300">changed</span>
                        @endif
                    </h4>
                    <pre class="text-xs bg-gray-100 dark:bg-gray-800 dark:text-gray-200 rounded p-3 overflow-x-auto max-h-48 mt-1">{{ json_encode($version->ast, JSON_PRETTY_PRINT) }}</pre>
                </div>
            @endif

            @if($version->getAttribute('source_series'))
                <div class="rounded-lg p-3 {{ $isChanged('source_series') ? 'bg-warning-50 dark:bg-warning-400/10 ring-1 ring-warning-300 dark:ring-warning-600' : 'bg-gray-50 dark:bg-gray-900/50' }}">
                    <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">
                        Source Series
                        @if($isChanged('source_series'))
                            <span class="ml-2 inline-flex items-center px-1.5 py-0.5 text-xs font-medium rounded bg-warning-100 text-warning-700 dark:bg-warning-400/20 dark:text-warning-300">changed</span>
                        @endif
                    </h4>
                    <pre class="text-xs bg-gray-100 dark:bg-gray-800 dark:text-gray-200 rounded p-3 overflow-x-auto max-h-48 mt-1">{{ json_encode($version->source_series, JSON_PRETTY_PRINT) }}</pre>
                </div>
            @endif

            @if($version->getAttribute('filters'))
                <div class="rounded-lg p-3 {{ $isChanged('filters') ? 'bg-warning-50 dark:bg-warning-400/10 ring-1 ring-warning-300 dark:ring-warning-600' : 'bg-gray-50 dark:bg-gray-900/50' }}">
                    <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">
                        Filters
                        @if($isChanged('filters'))
                            <span class="ml-2 inline-flex items-center px-1.5 py-0.5 text-xs font-medium rounded bg-warning-100 text-warning-700 dark:bg-warning-400/20 dark:text-warning-300">changed</span>
                        @endif
                    </h4>
                    <pre class="text-xs bg-gray-100 dark:bg-gray-800 dark:text-gray-200 rounded p-3 overflow-x-auto max-h-48 mt-1">{{ json_encode($version->filters, JSON_PRETTY_PRINT) }}</pre>
                </div>
            @endif

            @if($version->getAttribute('grid_layout'))
                <div class="rounded-lg p-3 {{ $isChanged('grid_layout') ? 'bg-warning-50 dark:bg-warning-400/10 ring-1 ring-warning-300 dark:ring-warning-600' : 'bg-gray-50 dark:bg-gray-900/50' }}">
                    <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">
                        Grid Layout
                        @if($isChanged('grid_layout'))
                            <span class="ml-2 inline-flex items-center px-1.5 py-0.5 text-xs font-medium rounded bg-warning-100 text-warning-700 dark:bg-warning-400/20 dark:text-warning-300">changed</span>
                        @endif
                    </h4>
                    <pre class="text-xs bg-gray-100 dark:bg-gray-800 dark:text-gray-200 rounded p-3 overflow-x-auto max-h-48 mt-1">{{ json_encode($version->grid_layout, JSON_PRETTY_PRINT) }}</pre>
                </div>
            @endif

            @if($version->getAttribute('controls'))
                <div class="rounded-lg p-3 {{ $isChanged('controls') ? 'bg-warning-50 dark:bg-warning-400/10 ring-1 ring-warning-300 dark:ring-warning-600' : 'bg-gray-50 dark:bg-gray-900/50' }}">
                    <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">
                        Controls
                        @if($isChanged('controls'))
                            <span class="ml-2 inline-flex items-center px-1.5 py-0.5 text-xs font-medium rounded bg-warning-100 text-warning-700 dark:bg-warning-400/20 dark:text-warning-300">changed</span>
                        @endif
                    </h4>
                    <pre class="text-xs bg-gray-100 dark:bg-gray-800 dark:text-gray-200 rounded p-3 overflow-x-auto max-h-48 mt-1">{{ json_encode($version->controls, JSON_PRETTY_PRINT) }}</pre>
                </div>
            @endif

            @if($version->getAttribute('source_config'))
                <div class="rounded-lg p-3 {{ $isChanged('source_config') ? 'bg-warning-50 dark:bg-warning-400/10 ring-1 ring-warning-300 dark:ring-warning-600' : 'bg-gray-50 dark:bg-gray-900/50' }}">
                    <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">
                        Source Config
                        @if($isChanged('source_config'))
                            <span class="ml-2 inline-flex items-center px-1.5 py-0.5 text-xs font-medium rounded bg-warning-100 text-warning-700 dark:bg-warning-400/20 dark:text-warning-300">changed</span>
                        @endif
                    </h4>
                    <pre class="text-xs bg-gray-100 dark:bg-gray-800 dark:text-gray-200 rounded p-3 overflow-x-auto max-h-48 mt-1">{{ json_encode($version->source_config, JSON_PRETTY_PRINT) }}</pre>
                </div>
            @endif
        </div>

        <div class="space-y-3">
            <div class="rounded-lg bg-gray-50 dark:bg-gray-900/50 p-3">
                <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Change Summary</h4>
                @if($version->change_summary)
                    @if(str_starts_with($version->change_summary, 'Created'))
                        <div class="flex items-center gap-2 text-sm">
                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-success-100 text-success-700 dark:bg-success-400/20 dark:text-success-300 text-xs font-bold">+</span>
                            <span>Initial creation</span>
                        </div>
                    @elseif(str_starts_with($version->change_summary, 'Updated: '))
                        @php
                            $summaryPart = substr($version->change_summary, 9);
                            $items = array_map('trim', explode(',', $summaryPart));
                        @endphp
                        <ul class="space-y-1.5">
                            @foreach($items as $item)
                                <li class="flex items-center gap-2 text-sm">
                                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-warning-100 text-warning-700 dark:bg-warning-400/20 dark:text-warning-300 text-xs font-bold">~</span>
                                    <span>{{ ucfirst($item) }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ $version->change_summary }}</p>
                    @endif
                @else
                    <p class="text-sm text-gray-500 dark:text-gray-400 italic">No summary recorded</p>
                @endif
            </div>
        </div>
    </div>
</div>