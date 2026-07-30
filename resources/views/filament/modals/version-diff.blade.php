@php
    $prev = $previousVersion ?? null;

    $changeType = null;
    if ($version->change_summary) {
        if (str_starts_with($version->change_summary, 'Created')) {
            $changeType = 'created';
        } elseif (str_starts_with($version->change_summary, 'Updated: ')) {
            $changeType = 'updated';
        } else {
            $changeType = 'other';
        }
    }

    $fmt = function ($v) {
        if (is_bool($v)) return $v ? 'Yes' : 'No';
        if ($v === null) return '—';
        if (is_array($v)) return json_encode($v, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        return (string) $v;
    };

    $isJson = fn ($v) => is_array($v);

    $fieldDefs = [
        ['key' => 'name', 'label' => 'Name'],
        ['key' => 'description', 'label' => 'Description'],
        ['key' => 'title', 'label' => 'Title'],
        ['key' => 'calculation_type', 'label' => 'Calculation Type'],
        ['key' => 'source_type', 'label' => 'Source Type'],
        ['key' => 'widget_type', 'label' => 'Widget Type'],
        ['key' => 'output_granularity', 'label' => 'Output Granularity'],
        ['key' => 'is_active', 'label' => 'Active', 'type' => 'bool'],
        ['key' => 'is_public', 'label' => 'Public', 'type' => 'bool'],
        ['key' => 'is_default', 'label' => 'Default Dashboard', 'type' => 'bool'],
        ['key' => 'grid_x', 'label' => 'Grid X'],
        ['key' => 'grid_y', 'label' => 'Grid Y'],
        ['key' => 'grid_w', 'label' => 'Grid W'],
        ['key' => 'grid_h', 'label' => 'Grid H'],
        ['key' => 'ast', 'label' => 'Formula (AST)', 'type' => 'json'],
        ['key' => 'filters', 'label' => 'Filters', 'type' => 'json'],
        ['key' => 'source_series', 'label' => 'Source Series', 'type' => 'json'],
        ['key' => 'grid_layout', 'label' => 'Grid Layout', 'type' => 'json'],
        ['key' => 'controls', 'label' => 'Controls', 'type' => 'json'],
        ['key' => 'source_config', 'label' => 'Source Config', 'type' => 'json'],
    ];
@endphp

<div class="space-y-5">
    {{-- Header bar --}}
    <div class="flex items-center justify-between flex-wrap gap-3">
        <div class="flex items-center gap-3 text-sm text-gray-500 dark:text-gray-400">
            <span>Version <strong class="text-gray-950 dark:text-white">#{{ $version->version_number }}</strong></span>
            <span class="text-gray-300 dark:text-gray-600">|</span>
            <span>{{ $version->user?->name ?? 'System' }}</span>
            <span class="text-gray-300 dark:text-gray-600">|</span>
            <span>{{ $version->created_at->format('M j, Y H:i') }}</span>
        </div>
        @if($changeType === 'created')
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium rounded-full bg-success-50 text-success-700 dark:bg-success-400/10 dark:text-success-300 ring-1 ring-inset ring-success-200 dark:ring-success-700">
                <x-filament::icon icon="heroicon-m-plus" class="w-3.5 h-3.5" />
                Created
            </span>
        @elseif($changeType === 'updated')
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium rounded-full bg-warning-50 text-warning-700 dark:bg-warning-400/10 dark:text-warning-300 ring-1 ring-inset ring-warning-200 dark:ring-warning-700">
                <x-filament::icon icon="heroicon-m-arrow-path" class="w-3.5 h-3.5" />
                Modified
            </span>
        @elseif($changeType === 'other')
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400 ring-1 ring-inset ring-gray-200 dark:ring-gray-700">
                {{ $version->change_summary }}
            </span>
        @endif
    </div>

    @if($prev && $changeType === 'updated')
        <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-white/5 rounded-lg px-4 py-2.5 ring-1 ring-gray-950/5 dark:ring-white/10">
            <x-filament::icon icon="heroicon-m-information-circle" class="w-4 h-4 text-gray-400 dark:text-gray-500" />
            <span>Compared to version <strong class="text-gray-950 dark:text-white">#{{ $prev->version_number }}</strong></span>
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        @foreach($fieldDefs as $def)
            @php
                $key = $def['key'];
                $isBool = ($def['type'] ?? null) === 'bool';
                $isJson = ($def['type'] ?? null) === 'json';

                $val = $version->getAttribute($key);
                if ($val === null && !$isBool) continue;

                $prevVal = $prev?->getAttribute($key);
                $changed = $prev && $val !== $prevVal;

                $hasOld = $changed && $prev;
            @endphp

            <div class="rounded-xl p-4 ring-1 {{ $changed ? 'bg-warning-50 dark:bg-warning-400/5 ring-warning-300 dark:ring-warning-600' : 'bg-gray-50 dark:bg-white/5 ring-gray-950/5 dark:ring-white/10' }}">
                {{-- Label row --}}
                <div class="flex items-center justify-between mb-1.5">
                    <h4 class="text-xs font-semibold uppercase tracking-wider {{ $changed ? 'text-warning-700 dark:text-warning-300' : 'text-gray-500 dark:text-gray-400' }}">
                        {{ $def['label'] }}
                    </h4>
                    @if($changed)
                        <span class="text-xs font-medium text-warning-600 dark:text-warning-400">changed</span>
                    @endif
                </div>

                {{-- Old value (strikethrough) --}}
                @if($hasOld)
                    @if($isJson)
                        <pre class="text-xs text-gray-400 dark:text-gray-500 line-through mb-1.5 overflow-x-auto max-h-24">{{ $fmt($prevVal) }}</pre>
                    @else
                        <div class="text-sm text-gray-400 dark:text-gray-500 line-through mb-1">{{ $fmt($prevVal) }}</div>
                    @endif
                @endif

                {{-- Current value --}}
                @if($isJson)
                    <pre class="text-xs overflow-x-auto max-h-48 rounded {{ $changed ? 'text-warning-700 dark:text-warning-200' : 'text-gray-950 dark:text-white' }}">{{ $fmt($val) }}</pre>
                @elseif($isBool)
                    <div class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium rounded-full {{ $val ? 'bg-success-50 text-success-700 dark:bg-success-400/10 dark:text-success-300 ring-1 ring-inset ring-success-200 dark:ring-success-700' : 'bg-danger-50 text-danger-700 dark:bg-danger-400/10 dark:text-danger-300 ring-1 ring-inset ring-danger-200 dark:ring-danger-700' }}">
                        <x-filament::icon icon="{{ $val ? 'heroicon-m-check-circle' : 'heroicon-m-x-circle' }}" class="w-3.5 h-3.5" />
                        {{ $fmt($val) }}
                    </div>
                @else
                    <div class="text-sm {{ $changed ? 'text-warning-700 dark:text-warning-200 font-medium' : 'text-gray-950 dark:text-white' }}">{{ $fmt($val) }}</div>
                @endif
            </div>
        @endforeach
    </div>

    @if($prev && $changeType === 'updated')
        <div class="text-xs text-gray-400 dark:text-gray-500 text-right pt-1">
            {{ $version->change_summary }}
        </div>
    @endif
</div>
