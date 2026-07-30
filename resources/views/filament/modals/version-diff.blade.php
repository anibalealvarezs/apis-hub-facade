@php
    $prev = $previousVersion ?? null;

    if (!$prev && $version->version_number > 1) {
        $fkMap = [
            \App\Models\DerivedMetricVersion::class => 'derived_metric_id',
            \App\Models\CustomKpiVersion::class => 'custom_kpi_id',
            \App\Models\DashboardVersion::class => 'dashboard_id',
        ];
        $modelClass = get_class($version);
        $fk = $fkMap[$modelClass] ?? null;
        if ($fk && $version->{$fk}) {
            $prev = $version->newQuery()
                ->where($fk, $version->{$fk})
                ->where('version_number', $version->version_number - 1)
                ->first();
        }
    }

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
            $v1 = $version->getAttribute($k);
            $v2 = $prev->getAttribute($k);
            if (is_array($v1) && is_array($v2)) {
                if (json_encode($v1) !== json_encode($v2)) {
                    $changedKeys[] = $k;
                }
            } elseif ($v1 !== $v2) {
                $changedKeys[] = $k;
            }
        }
    }

    // Build highlighted JSON
    if (!function_exists('renderDiffValue')) { function renderDiffValue($old, $new, $indent, $isLast) {
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
                        $result .= '<span class="diff-danger">' . json_encode($old[$key], JSON_UNESCAPED_UNICODE) . '</span>' . $childComma;
                    } elseif (!array_key_exists($key, $old)) {
                        $result .= '<span class="diff-success">' . json_encode($new[$key], JSON_UNESCAPED_UNICODE) . '</span>' . $childComma;
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
                    $result .= '<span class="diff-danger">' . json_encode($old[$i], JSON_UNESCAPED_UNICODE) . '</span>' . $childComma;
                } elseif (!array_key_exists($i, $old)) {
                    $result .= '<span class="diff-success">' . json_encode($new[$i], JSON_UNESCAPED_UNICODE) . '</span>' . $childComma;
                } elseif ($old[$i] !== $new[$i]) {
                    $result .= renderDiffValue($old[$i], $new[$i], $innerIndent, $childIsLast);
                } else {
                    $result .= json_encode($new[$i], JSON_UNESCAPED_UNICODE) . $childComma;
                }
            }
            $result .= "\n" . $indent . ']' . $comma;
            return $result;
        }

        return '<span class="diff-warning">' . json_encode($new, JSON_UNESCAPED_UNICODE) . '</span>' . $comma;
    } }

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
            $line .= '<span class="diff-warning">' . $valLines[0] . '</span>';
            $jsonLines[] = $line;
            for ($j = 1; $j < count($valLines); $j++) {
                $jsonLines[] = '    ' . '<span class="diff-warning">' . $valLines[$j] . '</span>';
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
            <span class="diff-badge diff-badge-success">
                <x-filament::icon icon="heroicon-m-plus" class="w-3.5 h-3.5" /> Created
            </span>
        @elseif($changeType === 'updated')
            <span class="diff-badge diff-badge-warning">
                <x-filament::icon icon="heroicon-m-arrow-path" class="w-3.5 h-3.5" /> Modified
            </span>
        @else
            <span class="diff-badge diff-badge-other">
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
            <span class="diff-change-count">{{ count($changedKeys) }} change(s)</span>
        @endif
    </div>
    @endif

    <div class="rounded-xl overflow-hidden ring-1 ring-gray-950/5 dark:ring-white/10">
        <div class="flex items-center gap-2 px-5 py-3 bg-gray-50 dark:bg-white/5 border-b border-gray-200 dark:border-gray-700">
            <x-filament::icon icon="heroicon-m-code-bracket" class="w-4 h-4 text-gray-500 dark:text-gray-400" />
            <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Snapshot</span>
            @if(!empty($changedKeys))
                <span class="text-xs font-medium diff-change-count">(highlighted = changed)</span>
            @endif
        </div>
        <pre style="max-height: 55vh; overflow-y: auto;" class="px-5 py-4 bg-white dark:bg-white/5 font-mono text-xs leading-relaxed overflow-x-auto whitespace-pre">{!! $highlightedJson !!}</pre>
    </div>
</div>

<style>
.diff-warning { color: #d97706; font-weight: 500; }
.diff-danger { color: #dc2626; }
.diff-success { color: #16a34a; font-weight: 500; }
.diff-change-count { color: #d97706; font-weight: 500; }
.diff-badge {
    display: inline-flex; align-items: center; gap: 0.375rem;
    padding: 0.25rem 0.625rem;
    font-size: 0.75rem; font-weight: 500;
    border-radius: 9999px;
    ring: 1px solid;
}
.diff-badge-success {
    background: #f0fdf4; color: #15803d;
    box-shadow: inset 0 0 0 1px #bbf7d0;
}
.diff-badge-warning {
    background: #fffbeb; color: #b45309;
    box-shadow: inset 0 0 0 1px #fde68a;
}
.dark .diff-danger { color: #fca5a5; }
.dark .diff-success { color: #86efac; }
.dark .diff-change-count { color: #fbbf24; }
.dark .diff-badge-success {
    background: rgba(34,197,94,0.1); color: #86efac;
    box-shadow: inset 0 0 0 1px #166534;
}
.dark .diff-badge-warning {
    background: rgba(251,191,36,0.1); color: #fde68a;
    box-shadow: inset 0 0 0 1px #78350f;
}
.dark .diff-badge-other {
    background: rgb(31 41 55 / var(--tw-bg-opacity, 1));
    color: #9ca3af;
    box-shadow: inset 0 0 0 1px rgb(55 65 81 / var(--tw-ring-opacity, 1));
}
</style>
