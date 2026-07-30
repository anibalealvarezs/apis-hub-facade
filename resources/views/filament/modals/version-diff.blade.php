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
    $changedClass = 'bg-warning-50 dark:bg-warning-400/10 ring-1 ring-warning-300 dark:ring-warning-600';
    $unchangedClass = 'bg-gray-50 dark:bg-white/5';
@endphp

<div class="space-y-5 p-4">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4 text-sm">
            <span class="text-gray-500 dark:text-gray-400">Version <strong class="text-gray-900 dark:text-white">#{{ $version->version_number }}</strong></span>
            <span class="text-gray-300 dark:text-gray-600">|</span>
            <span class="text-gray-500 dark:text-gray-400">{{ $version->user?->name ?? 'System' }}</span>
            <span class="text-gray-300 dark:text-gray-600">|</span>
            <span class="text-gray-500 dark:text-gray-400">{{ $version->created_at->format('M j, Y H:i') }}</span>
        </div>
        @if($version->change_summary)
            @if(str_starts_with($version->change_summary, 'Created'))
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium rounded-full bg-success-50 text-success-700 dark:bg-success-400/10 dark:text-success-300">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    Created
                </span>
            @elseif(str_starts_with($version->change_summary, 'Updated: '))
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium rounded-full bg-warning-50 text-warning-700 dark:bg-warning-400/10 dark:text-warning-300">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182"/></svg>
                    Modified
                </span>
            @else
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400">{{ $version->change_summary }}</span>
            @endif
        @endif
    </div>

    <div class="space-y-2">
        @if($version->name !== null)
            <div class="rounded-lg p-3 {{ $isChanged('name') ? $changedClass : $unchangedClass }}">
                <div class="flex items-center justify-between">
                    <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Name</h4>
                    @if($isChanged('name'))
                        <span class="text-xs font-medium text-warning-600 dark:text-warning-400">changed</span>
                    @endif
                </div>
                <p class="text-sm text-gray-900 dark:text-white mt-0.5">{{ $version->name }}</p>
            </div>
        @endif

        @if($version->description !== null)
            <div class="rounded-lg p-3 {{ $isChanged('description') ? $changedClass : $unchangedClass }}">
                <div class="flex items-center justify-between">
                    <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Description</h4>
                    @if($isChanged('description'))
                        <span class="text-xs font-medium text-warning-600 dark:text-warning-400">changed</span>
                    @endif
                </div>
                <p class="text-sm text-gray-900 dark:text-white mt-0.5">{{ $version->description ?: '—' }}</p>
            </div>
        @endif

        @if($version->getAttribute('calculation_type') !== null)
            <div class="rounded-lg p-3 {{ $isChanged('calculation_type') ? $changedClass : $unchangedClass }}">
                <div class="flex items-center justify-between">
                    <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Calculation Type</h4>
                    @if($isChanged('calculation_type'))
                        <span class="text-xs font-medium text-warning-600 dark:text-warning-400">changed</span>
                    @endif
                </div>
                <p class="text-sm text-gray-900 dark:text-white mt-0.5">{{ $version->calculation_type ?: '—' }}</p>
            </div>
        @endif

        @if($version->getAttribute('is_active') !== null)
            <div class="rounded-lg p-3 {{ $isChanged('is_active') ? $changedClass : $unchangedClass }}">
                <div class="flex items-center justify-between">
                    <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Active</h4>
                    @if($isChanged('is_active'))
                        <span class="text-xs font-medium text-warning-600 dark:text-warning-400">changed</span>
                    @endif
                </div>
                <p class="text-sm text-gray-900 dark:text-white mt-0.5">{{ $version->is_active ? 'Yes' : 'No' }}</p>
            </div>
        @endif

        @if($version->getAttribute('output_granularity') !== null)
            <div class="rounded-lg p-3 {{ $isChanged('output_granularity') ? $changedClass : $unchangedClass }}">
                <div class="flex items-center justify-between">
                    <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Output Granularity</h4>
                    @if($isChanged('output_granularity'))
                        <span class="text-xs font-medium text-warning-600 dark:text-warning-400">changed</span>
                    @endif
                </div>
                <p class="text-sm text-gray-900 dark:text-white mt-0.5">{{ $version->output_granularity ?: '—' }}</p>
            </div>
        @endif

        @if($version->getAttribute('is_public') !== null)
            <div class="rounded-lg p-3 {{ $isChanged('is_public') ? $changedClass : $unchangedClass }}">
                <div class="flex items-center justify-between">
                    <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Public</h4>
                    @if($isChanged('is_public'))
                        <span class="text-xs font-medium text-warning-600 dark:text-warning-400">changed</span>
                    @endif
                </div>
                <p class="text-sm text-gray-900 dark:text-white mt-0.5">{{ $version->is_public ? 'Yes' : 'No' }}</p>
            </div>
        @endif

        @if($version->getAttribute('is_default') !== null)
            <div class="rounded-lg p-3 {{ $isChanged('is_default') ? $changedClass : $unchangedClass }}">
                <div class="flex items-center justify-between">
                    <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Default Dashboard</h4>
                    @if($isChanged('is_default'))
                        <span class="text-xs font-medium text-warning-600 dark:text-warning-400">changed</span>
                    @endif
                </div>
                <p class="text-sm text-gray-900 dark:text-white mt-0.5">{{ $version->is_default ? 'Yes' : 'No' }}</p>
            </div>
        @endif

        @if($version->getAttribute('title') !== null)
            <div class="rounded-lg p-3 {{ $isChanged('title') ? $changedClass : $unchangedClass }}">
                <div class="flex items-center justify-between">
                    <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Title</h4>
                    @if($isChanged('title'))
                        <span class="text-xs font-medium text-warning-600 dark:text-warning-400">changed</span>
                    @endif
                </div>
                <p class="text-sm text-gray-900 dark:text-white mt-0.5">{{ $version->title ?: '—' }}</p>
            </div>
        @endif

        @if($version->getAttribute('source_type') !== null)
            <div class="rounded-lg p-3 {{ $isChanged('source_type') ? $changedClass : $unchangedClass }}">
                <div class="flex items-center justify-between">
                    <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Source Type</h4>
                    @if($isChanged('source_type'))
                        <span class="text-xs font-medium text-warning-600 dark:text-warning-400">changed</span>
                    @endif
                </div>
                <p class="text-sm text-gray-900 dark:text-white mt-0.5">{{ $version->source_type }}</p>
            </div>
        @endif

        @if($version->getAttribute('widget_type') !== null)
            <div class="rounded-lg p-3 {{ $isChanged('widget_type') ? $changedClass : $unchangedClass }}">
                <div class="flex items-center justify-between">
                    <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Widget Type</h4>
                    @if($isChanged('widget_type'))
                        <span class="text-xs font-medium text-warning-600 dark:text-warning-400">changed</span>
                    @endif
                </div>
                <p class="text-sm text-gray-900 dark:text-white mt-0.5">{{ $version->widget_type }}</p>
            </div>
        @endif

        @if($version->getAttribute('grid_x') !== null)
            <div class="rounded-lg p-3 {{ ($isChanged('grid_x') || $isChanged('grid_y') || $isChanged('grid_w') || $isChanged('grid_h')) ? $changedClass : $unchangedClass }}">
                <div class="flex items-center justify-between">
                    <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Grid Position</h4>
                    @if($isChanged('grid_x') || $isChanged('grid_y') || $isChanged('grid_w') || $isChanged('grid_h'))
                        <span class="text-xs font-medium text-warning-600 dark:text-warning-400">changed</span>
                    @endif
                </div>
                <p class="text-sm text-gray-900 dark:text-white mt-0.5">x: {{ $version->grid_x }}, y: {{ $version->grid_y }}, w: {{ $version->grid_w }}, h: {{ $version->grid_h }}</p>
            </div>
        @endif

        @if($version->getAttribute('ast'))
            <div class="rounded-lg p-3 {{ $isChanged('ast') ? $changedClass : $unchangedClass }}">
                <div class="flex items-center justify-between">
                    <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Formula (AST)</h4>
                    @if($isChanged('ast'))
                        <span class="text-xs font-medium text-warning-600 dark:text-warning-400">changed</span>
                    @endif
                </div>
                <pre class="text-xs bg-white dark:bg-gray-900/50 text-gray-900 dark:text-gray-200 rounded p-3 overflow-x-auto max-h-48 mt-1.5">{{ json_encode($version->ast, JSON_PRETTY_PRINT) }}</pre>
            </div>
        @endif

        @if($version->getAttribute('source_series'))
            <div class="rounded-lg p-3 {{ $isChanged('source_series') ? $changedClass : $unchangedClass }}">
                <div class="flex items-center justify-between">
                    <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Source Series</h4>
                    @if($isChanged('source_series'))
                        <span class="text-xs font-medium text-warning-600 dark:text-warning-400">changed</span>
                    @endif
                </div>
                <pre class="text-xs bg-white dark:bg-gray-900/50 text-gray-900 dark:text-gray-200 rounded p-3 overflow-x-auto max-h-48 mt-1.5">{{ json_encode($version->source_series, JSON_PRETTY_PRINT) }}</pre>
            </div>
        @endif

        @if($version->getAttribute('filters'))
            <div class="rounded-lg p-3 {{ $isChanged('filters') ? $changedClass : $unchangedClass }}">
                <div class="flex items-center justify-between">
                    <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Filters</h4>
                    @if($isChanged('filters'))
                        <span class="text-xs font-medium text-warning-600 dark:text-warning-400">changed</span>
                    @endif
                </div>
                <pre class="text-xs bg-white dark:bg-gray-900/50 text-gray-900 dark:text-gray-200 rounded p-3 overflow-x-auto max-h-48 mt-1.5">{{ json_encode($version->filters, JSON_PRETTY_PRINT) }}</pre>
            </div>
        @endif

        @if($version->getAttribute('grid_layout'))
            <div class="rounded-lg p-3 {{ $isChanged('grid_layout') ? $changedClass : $unchangedClass }}">
                <div class="flex items-center justify-between">
                    <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Grid Layout</h4>
                    @if($isChanged('grid_layout'))
                        <span class="text-xs font-medium text-warning-600 dark:text-warning-400">changed</span>
                    @endif
                </div>
                <pre class="text-xs bg-white dark:bg-gray-900/50 text-gray-900 dark:text-gray-200 rounded p-3 overflow-x-auto max-h-48 mt-1.5">{{ json_encode($version->grid_layout, JSON_PRETTY_PRINT) }}</pre>
            </div>
        @endif

        @if($version->getAttribute('controls'))
            <div class="rounded-lg p-3 {{ $isChanged('controls') ? $changedClass : $unchangedClass }}">
                <div class="flex items-center justify-between">
                    <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Controls</h4>
                    @if($isChanged('controls'))
                        <span class="text-xs font-medium text-warning-600 dark:text-warning-400">changed</span>
                    @endif
                </div>
                <pre class="text-xs bg-white dark:bg-gray-900/50 text-gray-900 dark:text-gray-200 rounded p-3 overflow-x-auto max-h-48 mt-1.5">{{ json_encode($version->controls, JSON_PRETTY_PRINT) }}</pre>
            </div>
        @endif

        @if($version->getAttribute('source_config'))
            <div class="rounded-lg p-3 {{ $isChanged('source_config') ? $changedClass : $unchangedClass }}">
                <div class="flex items-center justify-between">
                    <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Source Config</h4>
                    @if($isChanged('source_config'))
                        <span class="text-xs font-medium text-warning-600 dark:text-warning-400">changed</span>
                    @endif
                </div>
                <pre class="text-xs bg-white dark:bg-gray-900/50 text-gray-900 dark:text-gray-200 rounded p-3 overflow-x-auto max-h-48 mt-1.5">{{ json_encode($version->source_config, JSON_PRETTY_PRINT) }}</pre>
            </div>
        @endif
    </div>
</div>