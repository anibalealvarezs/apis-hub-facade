@php
    $prev = $previousVersion ?? null;

    $isDerivedMetric = $version instanceof \App\Models\DerivedMetricVersion;
    $isCustomKpi = $version instanceof \App\Models\CustomKpiVersion;
    $isDashboard = $version instanceof \App\Models\DashboardVersion;

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

    if ($isDerivedMetric) {
        $snapshotKeys = ['name', 'description', 'calculation_type', 'output_granularity', 'is_active', 'ast', 'source_series'];
        $summaryFields = [
            ['key' => 'name', 'label' => 'Name'],
            ['key' => 'description', 'label' => 'Description'],
            ['key' => 'calculation_type', 'label' => 'Calculation'],
            ['key' => 'output_granularity', 'label' => 'Granularity'],
        ];
    } elseif ($isCustomKpi) {
        $snapshotKeys = ['name', 'description', 'calculation_type', 'is_active', 'ast', 'filters'];
        $summaryFields = [
            ['key' => 'name', 'label' => 'Name'],
            ['key' => 'description', 'label' => 'Description'],
            ['key' => 'calculation_type', 'label' => 'Calculation'],
        ];
    } elseif ($isDashboard) {
        $snapshotKeys = ['name', 'description', 'is_public', 'is_default', 'grid_layout', 'controls'];
        $summaryFields = [
            ['key' => 'name', 'label' => 'Name'],
            ['key' => 'description', 'label' => 'Description'],
            ['key' => 'is_public', 'label' => 'Public'],
            ['key' => 'is_default', 'label' => 'Default'],
        ];
    }

    $changedFieldNames = [];
    if ($prev) {
        foreach ($snapshotKeys as $f) {
            if ($version->getAttribute($f) !== $prev->getAttribute($f)) {
                $changedFieldNames[] = $f;
            }
        }
    }
    $isFieldChanged = function ($key) use ($changedFieldNames) {
        return in_array($key, $changedFieldNames, true);
    };

    $seriesMap = function ($series) {
        $map = [];
        foreach ($series as $s) {
            $map[$s['key']] = $s['label'] ?? $s['key'];
        }
        return $map;
    };

    $astToString = function ($node, $map) use (&$astToString) {
        if (!is_array($node)) return (string) $node;
        if (!isset($node['type'])) return json_encode($node);

        return match ($node['type']) {
            'metric' => strtoupper($node['metric']) . ' (' . ($map[$node['metric']] ?? $node['metric']) . ')',
            'value' => (string) ($node['value'] ?? '0'),
            'operator' => (function () use ($node, $map, $astToString) {
                $op = $node['operator'] ?? '+';
                $left = $astToString($node['left'] ?? ['value' => 0], $map);
                $right = $astToString($node['right'] ?? ['value' => 0], $map);
                $symbol = match ($op) {
                    '+' => '+', '-' => '−', '*' => '×', '/' => '÷',
                    'ratio' => '÷ (ratio)', 'avg' => '∅ (avg)',
                    'min' => '↓ min', 'max' => '↑ max',
                    'abs_diff' => '|Δ|', 'pct_change' => '%Δ',
                    default => " $op ",
                };
                return "($left $symbol $right)";
            })(),
            default => json_encode($node),
        };
    };

    $fmt = function ($v) {
        if (is_bool($v)) return $v ? 'Yes' : 'No';
        if ($v === null) return '—';
        if (is_array($v)) return json_encode($v, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        return (string) $v;
    };

    if ($isDerivedMetric) {
        $currentSeries = $version->source_series ?? [];
        $previousSeries = $prev?->source_series ?? [];
        $currentMap = $seriesMap($currentSeries);
        $previousMap = $seriesMap($previousSeries);

        $allSeriesKeys = array_unique(array_merge(
            array_column($currentSeries, 'key'),
            array_column($previousSeries, 'key')
        ));
        sort($allSeriesKeys);

        $byKey = function ($series) {
            $mapped = [];
            foreach ($series as $s) {
                $mapped[$s['key']] = $s;
            }
            return $mapped;
        };
        $currentByKey = $byKey($currentSeries);
        $previousByKey = $byKey($previousSeries);
    } else {
        $currentSeries = $previousSeries = [];
        $currentMap = $previousMap = [];
        $allSeriesKeys = [];
        $currentByKey = $previousByKey = [];
    }

    $currentAst = $version->ast;
    $previousAst = $prev?->ast;
    $astChanged = $prev && $currentAst !== $previousAst;

    $hasFormula = $isDerivedMetric || $isCustomKpi;

    if ($hasFormula && $currentAst && is_array($currentAst)) {
        $currentFormula = $astToString($currentAst, $currentMap);
    } else {
        $currentFormula = $hasFormula ? $fmt($currentAst) : null;
    }

    if ($hasFormula && $previousAst && is_array($previousAst)) {
        $previousFormula = $astToString($previousAst, $previousMap);
    } else {
        $previousFormula = $hasFormula ? $fmt($previousAst) : null;
    }

    $currentFilters = $isCustomKpi ? ($version->filters ?? []) : [];
    $previousFilters = ($isCustomKpi && $prev) ? ($prev->filters ?? []) : [];
    $filtersChanged = $prev && $currentFilters !== $previousFilters;
@endphp

<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    {{-- LEFT COLUMN: Full Summary --}}
    <div class="md:col-span-2 space-y-5">
        {{-- Header --}}
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

        @if($prev)
            <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-white/5 rounded-lg px-4 py-2.5 ring-1 ring-gray-950/5 dark:ring-white/10">
                <x-filament::icon icon="heroicon-m-information-circle" class="w-4 h-4 text-gray-400 dark:text-gray-500" />
                <span>Compared to version <strong class="text-gray-950 dark:text-white">#{{ $prev->version_number }}</strong></span>
            </div>
        @endif

        {{-- Config summary --}}
        <div class="rounded-xl p-4 ring-1 bg-gray-50 dark:bg-white/5 ring-gray-950/5 dark:ring-white/10">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2">
                @foreach($summaryFields as $def)
                    @php
                        $val = $version->getAttribute($def['key']);
                        $prevVal = $prev?->getAttribute($def['key']);
                        $changed = $prev && $val !== $prevVal;
                        if ($def['key'] === 'description' && $val === null && $prevVal === null) continue;
                    @endphp
                    <div class="flex items-baseline gap-2 text-sm py-0.5">
                        <span class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 shrink-0 w-24">{{ $def['label'] }}</span>
                        @if($changed)
                            <span class="text-gray-400 dark:text-gray-500 line-through text-xs truncate">{{ $fmt($prevVal) }}</span>
                            <x-filament::icon icon="heroicon-m-arrow-right" class="w-3 h-3 text-warning-500 shrink-0" />
                            <span class="text-warning-700 dark:text-warning-200 font-medium truncate">{{ $fmt($val) }}</span>
                        @else
                            <span class="text-gray-950 dark:text-white truncate">{{ $fmt($val) }}</span>
                        @endif
                    </div>
                @endforeach
            </div>

            @if($changeType === 'updated' && !empty($changedFieldNames))
                <div class="mt-2.5 pt-2.5 border-t border-gray-200 dark:border-gray-700 flex items-center gap-2 text-xs">
                    <span class="font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 shrink-0">Updated</span>
                    <div class="flex flex-wrap gap-1.5">
                        @foreach($changedFieldNames as $cf)
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-warning-50 text-warning-700 dark:bg-warning-400/10 dark:text-warning-300 ring-1 ring-inset ring-warning-200 dark:ring-warning-700">
                                {{ str_replace('_', ' ', $cf) }}
                            </span>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        {{-- Source Series table --}}
        @if($isDerivedMetric && !empty($allSeriesKeys))
            <div class="rounded-xl overflow-hidden ring-1 ring-gray-950/5 dark:ring-white/10">
                <div class="flex items-center gap-2 px-4 py-3 bg-gray-50 dark:bg-white/5 border-b border-gray-200 dark:border-gray-700">
                    <x-filament::icon icon="heroicon-m-table-cells" class="w-4 h-4 text-gray-500 dark:text-gray-400" />
                    <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Source Series</span>
                    @if($prev && $currentSeries !== $previousSeries)
                        <span class="text-xs font-medium text-warning-600 dark:text-warning-400">(changed)</span>
                    @endif
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-gray-700">
                                <th class="text-left px-4 py-2.5 text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 whitespace-nowrap">Key</th>
                                <th class="text-left px-4 py-2.5 text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 whitespace-nowrap">Label</th>
                                <th class="text-left px-4 py-2.5 text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 whitespace-nowrap">Channel</th>
                                <th class="text-left px-4 py-2.5 text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 whitespace-nowrap">Metric</th>
                                <th class="text-left px-4 py-2.5 text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 whitespace-nowrap">Granularity</th>
                                @php
                                    $anyAssetFilters = false;
                                    foreach ($allSeriesKeys as $sk) {
                                        $curr = $currentByKey[$sk] ?? null;
                                        $prevEnt = $previousByKey[$sk] ?? null;
                                        if (!empty($curr['asset_filter'] ?? []) || !empty($prevEnt['asset_filter'] ?? [])) {
                                            $anyAssetFilters = true;
                                            break;
                                        }
                                    }
                                @endphp
                                @if($anyAssetFilters)
                                    <th class="text-left px-4 py-2.5 text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 whitespace-nowrap">Filters</th>
                                @endif
                                @if($prev)
                                    <th class="text-left px-4 py-2.5 text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 whitespace-nowrap">Status</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($allSeriesKeys as $sk)
                                @php
                                    $curr = $currentByKey[$sk] ?? null;
                                    $prevEnt = $previousByKey[$sk] ?? null;
                                    $isNew = !$prevEnt && $curr;
                                    $isRemoved = $prevEnt && !$curr;
                                    $hasChange = $prev && $curr && $prevEnt && $curr !== $prevEnt;
                                    $rowBg = $isNew ? 'bg-success-50/30 dark:bg-success-400/5' : ($isRemoved ? 'bg-danger-50/30 dark:bg-danger-400/5' : ($hasChange ? 'bg-warning-50/30 dark:bg-warning-400/5' : ''));
                                @endphp
                                <tr class="border-b border-gray-100 dark:border-gray-700/50 {{ $rowBg }}">
                                    <td class="px-4 py-2.5 text-sm font-medium text-gray-950 dark:text-white whitespace-nowrap">{{ $sk }}</td>
                                    @foreach(['label', 'channel', 'metric', 'granularity'] as $sf)
                                        @php
                                            $currVal = $curr[$sf] ?? '—';
                                            $prevVal = $prevEnt[$sf] ?? '—';
                                            $cellChanged = $prev && $curr && $prevEnt && $currVal !== $prevVal;
                                        @endphp
                                        <td class="px-4 py-2.5 text-sm whitespace-nowrap">
                                            @if($cellChanged)
                                                <span class="text-gray-400 dark:text-gray-500 line-through mr-1">{{ $prevVal }}</span>
                                                <x-filament::icon icon="heroicon-m-arrow-right" class="w-3 h-3 text-warning-500 inline shrink-0 -mt-0.5" />
                                                <span class="text-warning-700 dark:text-warning-200 font-medium ml-1">{{ $currVal }}</span>
                                            @elseif($isRemoved)
                                                <span class="text-gray-400 dark:text-gray-500 line-through">{{ $prevVal }}</span>
                                            @elseif($isNew)
                                                <span class="text-success-700 dark:text-success-300 font-medium">{{ $currVal }}</span>
                                            @else
                                                <span class="text-gray-950 dark:text-white">{{ $currVal }}</span>
                                            @endif
                                        </td>
                                    @endforeach
                                    @if($anyAssetFilters)
                                        @php
                                            $currFilter = $curr['asset_filter'] ?? [];
                                            $prevFilter = $prevEnt['asset_filter'] ?? [];
                                            $filterChanged = $prev && $curr && $prevEnt && $currFilter !== $prevFilter;
                                        @endphp
                                        <td class="px-4 py-2.5 text-xs whitespace-nowrap">
                                            @if($filterChanged)
                                                <span class="text-gray-400 dark:text-gray-500 line-through mr-1">{{ $fmt($prevFilter) }}</span>
                                                <x-filament::icon icon="heroicon-m-arrow-right" class="w-3 h-3 text-warning-500 inline shrink-0 -mt-0.5" />
                                                <span class="text-warning-700 dark:text-warning-200 font-medium ml-1">{{ $fmt($currFilter) }}</span>
                                            @elseif(!empty($currFilter))
                                                <span class="text-gray-950 dark:text-white">{{ $fmt($currFilter) }}</span>
                                            @elseif(!empty($prevFilter))
                                                <span class="text-gray-400 dark:text-gray-500 line-through">{{ $fmt($prevFilter) }}</span>
                                            @else
                                                <span class="text-gray-400 dark:text-gray-500">—</span>
                                            @endif
                                        </td>
                                    @endif
                                    @if($prev)
                                        <td class="px-4 py-2.5 text-xs whitespace-nowrap">
                                            @if($isNew)
                                                <span class="text-success-600 dark:text-success-400 font-medium">Added</span>
                                            @elseif($isRemoved)
                                                <span class="text-danger-600 dark:text-danger-400 font-medium">Removed</span>
                                            @elseif($hasChange)
                                                <span class="text-warning-600 dark:text-warning-400 font-medium">Modified</span>
                                            @else
                                                <span class="text-gray-400 dark:text-gray-500">Unchanged</span>
                                            @endif
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- KPI Filters --}}
        @if($isCustomKpi && (!empty($currentFilters) || !empty($previousFilters)))
            <div class="rounded-xl p-4 ring-1 {{ $filtersChanged ? 'bg-warning-50 dark:bg-warning-400/5 ring-warning-300 dark:ring-warning-600' : 'bg-gray-50 dark:bg-white/5 ring-gray-950/5 dark:ring-white/10' }}">
                <div class="flex items-center gap-2 mb-2.5">
                    <x-filament::icon icon="heroicon-m-funnel" class="w-4 h-4 text-gray-500 dark:text-gray-400" />
                    <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Filters</span>
                    @if($filtersChanged)
                        <span class="text-xs font-medium text-warning-600 dark:text-warning-400">(changed)</span>
                    @endif
                </div>
                @if($filtersChanged)
                    <div class="text-xs text-gray-400 dark:text-gray-500 line-through mb-1.5 font-mono whitespace-pre-wrap">{{ $fmt($previousFilters) }}</div>
                    <x-filament::icon icon="heroicon-m-arrow-down" class="w-4 h-4 text-warning-500 mb-1" />
                @endif
                <div class="text-xs {{ $filtersChanged ? 'text-warning-700 dark:text-warning-200 font-medium' : 'text-gray-950 dark:text-white' }} font-mono whitespace-pre-wrap">{{ $fmt($currentFilters) }}</div>
            </div>
        @endif

        {{-- Dashboard Layout --}}
        @if($isDashboard)
            @foreach([['key' => 'grid_layout', 'label' => 'Grid Layout', 'icon' => 'heroicon-m-view-columns'], ['key' => 'controls', 'label' => 'Controls', 'icon' => 'heroicon-m-adjustments-horizontal']] as $section)
                @php
                    $secVal = $version->getAttribute($section['key']);
                    $secPrev = $prev?->getAttribute($section['key']);
                    $secChanged = $prev && $secVal !== $secPrev;
                @endphp
                @if($secVal || $secPrev)
                    <div class="rounded-xl p-4 ring-1 {{ $secChanged ? 'bg-warning-50 dark:bg-warning-400/5 ring-warning-300 dark:ring-warning-600' : 'bg-gray-50 dark:bg-white/5 ring-gray-950/5 dark:ring-white/10' }}">
                        <div class="flex items-center gap-2 mb-2.5">
                            <x-filament::icon icon="{{ $section['icon'] }}" class="w-4 h-4 text-gray-500 dark:text-gray-400" />
                            <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ $section['label'] }}</span>
                            @if($secChanged)
                                <span class="text-xs font-medium text-warning-600 dark:text-warning-400">(changed)</span>
                            @endif
                        </div>
                        @if($secChanged)
                            <div class="text-xs text-gray-400 dark:text-gray-500 line-through mb-1.5 font-mono whitespace-pre-wrap">{{ $fmt($secPrev) }}</div>
                            <x-filament::icon icon="heroicon-m-arrow-down" class="w-4 h-4 text-warning-500 mb-1" />
                        @endif
                        <div class="text-xs {{ $secChanged ? 'text-warning-700 dark:text-warning-200 font-medium' : 'text-gray-950 dark:text-white' }} font-mono whitespace-pre-wrap">{{ $fmt($secVal) }}</div>
                    </div>
                @endif
            @endforeach
        @endif

        {{-- Formula --}}
        @if($hasFormula && ($currentAst || $previousAst))
            <div class="rounded-xl p-4 ring-1 {{ $astChanged ? 'bg-warning-50 dark:bg-warning-400/5 ring-warning-300 dark:ring-warning-600' : 'bg-gray-50 dark:bg-white/5 ring-gray-950/5 dark:ring-white/10' }}">
                <div class="flex items-center gap-2 mb-2.5">
                    <x-filament::icon icon="heroicon-m-variable" class="w-4 h-4 text-gray-500 dark:text-gray-400" />
                    <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Formula</span>
                    @if($astChanged)
                        <span class="text-xs font-medium text-warning-600 dark:text-warning-400">(changed)</span>
                    @endif
                </div>
                @if($astChanged)
                    <div class="text-sm text-gray-400 dark:text-gray-500 line-through mb-1.5 font-mono">{{ $previousFormula }}</div>
                    <x-filament::icon icon="heroicon-m-arrow-down" class="w-4 h-4 text-warning-500 mb-1" />
                @endif
                <div class="text-sm {{ $astChanged ? 'text-warning-700 dark:text-warning-200 font-medium' : 'text-gray-950 dark:text-white' }} font-mono leading-relaxed">{{ $currentFormula }}</div>
            </div>
        @endif
    </div>

    {{-- RIGHT COLUMN: Payload --}}
    <div class="md:col-span-1 space-y-4">
        <div class="rounded-xl overflow-hidden ring-1 ring-gray-950/5 dark:ring-white/10">
            <div class="flex items-center gap-2 px-4 py-3 bg-gray-50 dark:bg-white/5 border-b border-gray-200 dark:border-gray-700">
                <x-filament::icon icon="heroicon-m-code-bracket" class="w-4 h-4 text-gray-500 dark:text-gray-400" />
                <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Payload</span>
            </div>
            <div class="overflow-x-auto bg-white dark:bg-white/5 font-mono text-xs leading-relaxed">
                <div class="px-4 py-2 text-gray-500 dark:text-gray-400">{</div>
                @foreach($snapshotKeys as $fk)
                    @php
                        $val = $version->getAttribute($fk);
                        $isChanged = $isFieldChanged($fk);
                        $isLast = $loop->last;
                    @endphp
                    <div class="px-4 py-1 {{ $isChanged ? 'bg-warning-50 dark:bg-warning-400/10 border-l-2 border-warning-400 dark:border-warning-500' : 'border-l-2 border-transparent' }}">
                        <span class="text-gray-500 dark:text-gray-400">"{{ $fk }}"</span>:
                        @if(is_array($val))
                            <pre class="whitespace-pre-wrap break-all mt-1 ml-2 {{ $isChanged ? 'text-warning-700 dark:text-warning-200' : 'text-gray-950 dark:text-white' }}">{{ json_encode($val, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                            @if(!$isLast)<div class="text-gray-400 dark:text-gray-500 ml-2">,</div>@endif
                        @elseif(is_bool($val))
                            <span class="{{ $isChanged ? 'text-warning-700 dark:text-warning-200' : 'text-gray-950 dark:text-white' }}">{{ $val ? 'true' : 'false' }}</span>
                            @if(!$isLast)<span class="text-gray-400 dark:text-gray-500">,</span>@endif
                        @elseif($val === null)
                            <span class="text-gray-400 dark:text-gray-500">null</span>
                            @if(!$isLast)<span class="text-gray-400 dark:text-gray-500">,</span>@endif
                        @else
                            <span class="{{ $isChanged ? 'text-warning-700 dark:text-warning-200' : 'text-gray-950 dark:text-white' }}">{{ json_encode($val, JSON_UNESCAPED_UNICODE) }}</span>
                            @if(!$isLast)<span class="text-gray-400 dark:text-gray-500">,</span>@endif
                        @endif
                    </div>
                @endforeach
                <div class="px-4 py-2 text-gray-500 dark:text-gray-400">}</div>
            </div>
        </div>
    </div>
</div>
