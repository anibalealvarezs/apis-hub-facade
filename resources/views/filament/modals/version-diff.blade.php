<div class="space-y-4 p-4">
    <dl class="grid grid-cols-2 gap-3 text-sm">
        <dt class="font-medium text-gray-500">Version</dt>
        <dd>#{{ $version->version_number }}</dd>

        <dt class="font-medium text-gray-500">Name</dt>
        <dd>{{ $version->name }}</dd>

        <dt class="font-medium text-gray-500">Changed by</dt>
        <dd>{{ $version->user?->name ?? 'System' }}</dd>

        <dt class="font-medium text-gray-500">Changed at</dt>
        <dd>{{ $version->created_at->format('M j, Y H:i') }}</dd>

        @if($version->change_summary)
            <dt class="font-medium text-gray-500">Summary</dt>
            <dd>{{ $version->change_summary }}</dd>
        @endif
    </dl>

    @if($version->description)
        <div>
            <h4 class="text-sm font-medium text-gray-500 mb-1">Description</h4>
            <p class="text-sm">{{ $version->description }}</p>
        </div>
    @endif

    @if($version->ast)
        <div>
            <h4 class="text-sm font-medium text-gray-500 mb-1">Formula (AST)</h4>
            <pre class="text-xs bg-gray-100 rounded p-3 overflow-x-auto max-h-48">{{ json_encode($version->ast, JSON_PRETTY_PRINT) }}</pre>
        </div>
    @endif

    @if(method_exists($version, 'source_series') || property_exists($version, 'source_series'))
        @if($version->source_series)
            <div>
                <h4 class="text-sm font-medium text-gray-500 mb-1">Source Series</h4>
                <pre class="text-xs bg-gray-100 rounded p-3 overflow-x-auto max-h-48">{{ json_encode($version->source_series, JSON_PRETTY_PRINT) }}</pre>
            </div>
        @endif
    @endif

    @if($version->filters)
        <div>
            <h4 class="text-sm font-medium text-gray-500 mb-1">Filters</h4>
            <pre class="text-xs bg-gray-100 rounded p-3 overflow-x-auto max-h-48">{{ json_encode($version->filters, JSON_PRETTY_PRINT) }}</pre>
        </div>
    @endif

    @if($version->grid_layout)
        <div>
            <h4 class="text-sm font-medium text-gray-500 mb-1">Grid Layout</h4>
            <pre class="text-xs bg-gray-100 rounded p-3 overflow-x-auto max-h-48">{{ json_encode($version->grid_layout, JSON_PRETTY_PRINT) }}</pre>
        </div>
    @endif

    @if($version->controls)
        <div>
            <h4 class="text-sm font-medium text-gray-500 mb-1">Controls</h4>
            <pre class="text-xs bg-gray-100 rounded p-3 overflow-x-auto max-h-48">{{ json_encode($version->controls, JSON_PRETTY_PRINT) }}</pre>
        </div>
    @endif

    @if($version->source_config)
        <div>
            <h4 class="text-sm font-medium text-gray-500 mb-1">Source Config</h4>
            <pre class="text-xs bg-gray-100 rounded p-3 overflow-x-auto max-h-48">{{ json_encode($version->source_config, JSON_PRETTY_PRINT) }}</pre>
        </div>
    @endif

    @if($version->widget_type)
        <div>
            <h4 class="text-sm font-medium text-gray-500 mb-1">Widget Type</h4>
            <p class="text-sm">{{ $version->widget_type }}</p>
        </div>
    @endif

    @if($version->grid_x !== null)
        <div>
            <h4 class="text-sm font-medium text-gray-500 mb-1">Grid Position</h4>
            <p class="text-sm">x: {{ $version->grid_x }}, y: {{ $version->grid_y }}, w: {{ $version->grid_w }}, h: {{ $version->grid_h }}</p>
        </div>
    @endif
</div>
