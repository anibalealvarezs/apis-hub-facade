@php
    $prev = $previousVersion ?? null;

    $isDerivedMetric = $version instanceof \App\Models\DerivedMetricVersion;
    $isCustomKpi = $version instanceof \App\Models\CustomKpiVersion;

    $changeType = 'other';
    if ($version->change_summary) {
        if (str_starts_with($version->change_summary, 'Created')) $changeType = 'created';
        elseif (str_starts_with($version->change_summary, 'Updated')) $changeType = 'updated';
    }

    if ($isDerivedMetric) {
        $snapshotKeys = ['name', 'description', 'calculation_type', 'output_granularity', 'is_active', 'ast', 'source_series'];
    } elseif ($isCustomKpi) {
        $snapshotKeys = ['name', 'description', 'calculation_type', 'is_active', 'ast', 'filters'];
    } else {
        $snapshotKeys = ['name', 'description', 'is_public', 'is_default', 'grid_layout', 'controls'];
    }

    $changedKeys = [];
    if ($prev) {
        foreach ($snapshotKeys as $k) {
            if ($version->getAttribute($k) !== $prev->getAttribute($k)) {
                $changedKeys[] = $k;
            }
        }
    }

    // Build highlighted JSON
    $jsonLines = [];
    $jsonLines[] = '{';
    $total = count($snapshotKeys);
    foreach ($snapshotKeys as $i => $key) {
        $val = $version->getAttribute($key);
        $isChanged = in_array($key, $changedKeys);
        $encoded = json_encode($val, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $valLines = explode("\n", $encoded);
        $last = $i === $total - 1;

        $line = '    ' . json_encode($key) . ': ';
        if ($isChanged) $line .= '<span class="text-warning-700 dark:text-warning-200 font-medium">';
        $line .= $valLines[0];
        $jsonLines[] = $line;

        for ($j = 1; $j < count($valLines); $j++) {
            $jsonLines[] = '    ' . $valLines[$j];
        }

        $lastIdx = count($jsonLines) - 1;
        if ($isChanged) $jsonLines[$lastIdx] .= '</span>';
        if (!$last) $jsonLines[$lastIdx] .= ',';
    }
    $jsonLines[] = '}';
    $highlightedJson = implode("\n", $jsonLines);
@endphp

<div class="space-y-4">
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
                <x-filament::icon icon="heroicon-m-plus" class="w-3.5 h-3.5" /> Created
            </span>
        @elseif($changeType === 'updated')
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium rounded-full bg-warning-50 text-warning-700 dark:bg-warning-400/10 dark:text-warning-300 ring-1 ring-inset ring-warning-200 dark:ring-warning-700">
                <x-filament::icon icon="heroicon-m-arrow-path" class="w-3.5 h-3.5" /> Modified
            </span>
        @else
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400 ring-1 ring-inset ring-gray-200 dark:ring-gray-700">
                {{ $version->change_summary }}
            </span>
        @endif
    @if($prev)
        <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-white/5 rounded-lg px-4 py-2.5 ring-1 ring-gray-950/5 dark:ring-white/10">
            <x-filament::icon icon="heroicon-m-information-circle" class="w-4 h-4 text-gray-400 dark:text-gray-500" />
            <span>Compared to v<strong class="text-gray-950 dark:text-white">#{{ $prev->version_number }}</strong></span>
            @if(!empty($changedKeys))
                <span class="text-gray-400 dark:text-gray-500">·</span>
                <span class="text-warning-600 dark:text-warning-400 font-medium">{{ count($changedKeys) }} change(s)</span>
            @endif
        </div>
    @endif

    <div class="rounded-xl overflow-hidden ring-1 ring-gray-950/5 dark:ring-white/10">
        <div class="flex items-center gap-2 px-5 py-3 bg-gray-50 dark:bg-white/5 border-b border-gray-200 dark:border-gray-700">
            <x-filament::icon icon="heroicon-m-code-bracket" class="w-4 h-4 text-gray-500 dark:text-gray-400" />
            <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Snapshot</span>
            @if(!empty($changedKeys))
                <span class="text-xs font-medium text-warning-600 dark:text-warning-400">(highlighted = changed)</span>
            @endif
        </div>
        <pre class="px-5 py-4 bg-white dark:bg-white/5 font-mono text-xs leading-relaxed overflow-x-auto overflow-y-auto max-h-[60vh] whitespace-pre">{!! $highlightedJson !!}</pre>
    </div>
</div>
