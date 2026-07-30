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
    function renderDiffValue($old, $new, $indent, $isLast) {
        $comma = $isLast ? '' : ',';

        if ($old === $new) {
            return json_encode($new, JSON_UNESCAPED_UNICODE) . $comma;
        }

        if (is_array($old) && is_array($new)) {
            $keysOld = array_keys($old);
            $keysNew = array_keys($new);
            $isObj = count(array_filter(array_merge($keysOld, $keysNew), 'is_string')) > 0;

            if ($isObj) {
                $result = '{';
                $allKeys = array_values(array_unique(array_merge($keysOld, $keysNew)));
                $total = count($allKeys);
                foreach ($allKeys as $i => $key) {
                    $innerIndent = $indent . '    ';
                    $childIsLast = ($i === $total - 1);
                    $childComma = $childIsLast ? '' : ',';
                    $result .= "\n" . $innerIndent . json_encode($key) . ': ';

                    if (!array_key_exists($key, $new)) {
                        $result .= '<span class="text-danger-700 dark:text-danger-300">' . json_encode($old[$key], JSON_UNESCAPED_UNICODE) . '</span>' . $childComma;
                    } elseif (!array_key_exists($key, $old)) {
                        $result .= '<span class="text-success-700 dark:text-success-300 font-medium">' . json_encode($new[$key], JSON_UNESCAPED_UNICODE) . '</span>' . $childComma;
                    } elseif ($old[$key] !== $new[$key]) {
                        $result .= renderDiffValue($old[$key], $new[$key], $innerIndent, $childIsLast);
                    } else {
                        $result .= json_encode($new[$key], JSON_UNESCAPED_UNICODE) . $childComma;
                    }
                }
                $result .= "\n" . $indent . '}' . $comma;
                return $result;
            }

            $result = '[';
            $count = max(count($old), count($new));
            for ($i = 0; $i < $count; $i++) {
                $innerIndent = $indent . '    ';
                $childIsLast = ($i === $count - 1);
                $childComma = $childIsLast ? '' : ',';
                $result .= "\n" . $innerIndent;

                if (!array_key_exists($i, $new)) {
                    $result .= '<span class="text-danger-700 dark:text-danger-300">' . json_encode($old[$i], JSON_UNESCAPED_UNICODE) . '</span>' . $childComma;
                } elseif (!array_key_exists($i, $old)) {
                    $result .= '<span class="text-success-700 dark:text-success-300 font-medium">' . json_encode($new[$i], JSON_UNESCAPED_UNICODE) . '</span>' . $childComma;
                } elseif ($old[$i] !== $new[$i]) {
                    $result .= renderDiffValue($old[$i], $new[$i], $innerIndent, $childIsLast);
                } else {
                    $result .= json_encode($new[$i], JSON_UNESCAPED_UNICODE) . $childComma;
                }
            }
            $result .= "\n" . $indent . ']' . $comma;
            return $result;
        }

        return '<span class="text-warning-700 dark:text-warning-200 font-medium">' . json_encode($new, JSON_UNESCAPED_UNICODE) . '</span>' . $comma;
    }

    $jsonLines = [];
    $jsonLines[] = '{';
    $total = count($snapshotKeys);
    foreach ($snapshotKeys as $i => $key) {
        $val = $version->getAttribute($key);
        $isChanged = in_array($key, $changedKeys);
        $last = $i === $total - 1;

        if (!$isChanged) {
            $encoded = json_encode($val, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            $valLines = explode("\n", $encoded);
            $line = '    ' . json_encode($key) . ': ';
            $line .= $valLines[0];
            $jsonLines[] = $line;
            for ($j = 1; $j < count($valLines); $j++) {
                $jsonLines[] = '    ' . $valLines[$j];
            }
            $lastIdx = count($jsonLines) - 1;
            if (!$last) $jsonLines[$lastIdx] .= ',';
        } elseif (!is_array($val)) {
            $encoded = json_encode($val, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            $valLines = explode("\n", $encoded);
            $line = '    ' . json_encode($key) . ': ';
            $line .= '<span class="text-warning-700 dark:text-warning-200 font-medium">' . $valLines[0] . '</span>';
            $jsonLines[] = $line;
            for ($j = 1; $j < count($valLines); $j++) {
                $jsonLines[] = '    ' . '<span class="text-warning-700 dark:text-warning-200 font-medium">' . $valLines[$j] . '</span>';
            }
            $lastIdx = count($jsonLines) - 1;
            if (!$last) $jsonLines[$lastIdx] .= ',';
        } else {
            $oldVal = $prev->getAttribute($key);
            $rendered = '    ' . json_encode($key) . ': ' . renderDiffValue($oldVal, $val, '    ', $last);
            foreach (explode("\n", $rendered) as $dl) {
                $jsonLines[] = $dl;
            }
        }
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
    </div>

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
        <pre style="max-height: 55vh; overflow-y: auto;" class="px-5 py-4 bg-white dark:bg-white/5 font-mono text-xs leading-relaxed overflow-x-auto whitespace-pre">{!! $highlightedJson !!}</pre>
    </div>
</div>
