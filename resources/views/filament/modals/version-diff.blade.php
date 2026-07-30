@php
    $prev = $previousVersion ?? null;

    $isDerivedMetric = $version instanceof \App\Models\DerivedMetricVersion;
    $isCustomKpi = $version instanceof \App\Models\CustomKpiVersion;
    $isDashboard = $version instanceof \App\Models\DashboardVersion;

    $changeType = 'other';
    if ($version->change_summary) {
        if (str_starts_with($version->change_summary, 'Created')) {
            $changeType = 'created';
        } elseif (str_starts_with($version->change_summary, 'Updated')) {
            $changeType = 'updated';
        }
    }

    $fmt = function ($v) {
        if (is_bool($v)) return $v ? 'Yes' : 'No';
        if ($v === null || $v === '') return '—';
        if (is_array($v)) return json_encode($v, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        return (string) $v;
    };

    if ($isDerivedMetric) {
        $fields = [
            ['key' => 'name', 'label' => 'Name'],
            ['key' => 'description', 'label' => 'Description'],
            ['key' => 'calculation_type', 'label' => 'Calculation Type'],
            ['key' => 'output_granularity', 'label' => 'Output Granularity'],
            ['key' => 'is_active', 'label' => 'Active'],
        ];
    } elseif ($isCustomKpi) {
        $fields = [
            ['key' => 'name', 'label' => 'Name'],
            ['key' => 'description', 'label' => 'Description'],
            ['key' => 'calculation_type', 'label' => 'Calculation Type'],
            ['key' => 'is_active', 'label' => 'Active'],
        ];
    } elseif ($isDashboard) {
        $fields = [
            ['key' => 'name', 'label' => 'Name'],
            ['key' => 'description', 'label' => 'Description'],
            ['key' => 'is_public', 'label' => 'Public'],
            ['key' => 'is_default', 'label' => 'Default'],
        ];
    }

    $changedFields = [];
    if ($prev) {
        foreach (array_column($fields, 'key') as $k) {
            $v = $version->getAttribute($k);
            $pv = $prev->getAttribute($k);
            if ($v !== $pv) {
                $changedFields[] = $k;
            }
        }
    }

    // Formula
    $hasFormula = $isDerivedMetric || $isCustomKpi;
    if ($isDerivedMetric) {
        $currentSeries = $version->source_series ?? [];
        $previousSeries = $prev?->source_series ?? [];
        $seriesMap = function ($series) {
            $map = [];
            foreach ($series as $s) { $map[$s['key']] = $s['label'] ?? $s['key']; }
            return $map;
        };
        $currentMap = $seriesMap($currentSeries);
        $previousMap = $seriesMap($previousSeries);
    } else {
        $currentSeries = $previousSeries = [];
        $currentMap = $previousMap = [];
    }

    $astToString = function ($node, $map) use (&$astToString) {
        if (!is_array($node)) return (string) $node;
        if (!isset($node['type'])) return '';
        return match ($node['type']) {
            'metric' => strtoupper($node['metric']) . ' (' . ($map[$node['metric']] ?? $node['metric']) . ')',
            'value' => (string) ($node['value'] ?? '0'),
            'operator' => (function () use ($node, $map, $astToString) {
                $op = $node['operator'] ?? '+';
                $l = $astToString($node['left'] ?? ['value' => 0], $map);
                $r = $astToString($node['right'] ?? ['value' => 0], $map);
                $sym = match ($op) {
                    '+' => '+', '-' => '−', '*' => '×', '/' => '÷',
                    'ratio' => '÷ (ratio)', 'avg' => '∅ (avg)',
                    'min' => '↓ min', 'max' => '↑ max',
                    'abs_diff' => '|Δ|', 'pct_change' => '%Δ',
                    default => " $op ",
                };
                return "($l $sym $r)";
            })(),
            default => '',
        };
    };

    $renderAst = function ($ast, $map) use ($astToString, $fmt) {
        if (!$ast) return null;
        return is_array($ast) ? $astToString($ast, $map) : $fmt($ast);
    };

    $currentAst = $version->ast;
    $previousAst = $prev?->ast;
    $currentFormulaStr = $renderAst($currentAst, $currentMap);
    $previousFormulaStr = $renderAst($previousAst, $previousMap);
    $astChanged = $prev && $currentAst !== $previousAst;

    // Series table data
    if ($isDerivedMetric) {
        $seriesKeys = array_unique(array_merge(
            array_column($currentSeries, 'key'),
            array_column($previousSeries, 'key')
        ));
        sort($seriesKeys);
        $byKey = function ($series) {
            $m = [];
            foreach ($series as $s) { $m[$s['key']] = $s; }
            return $m;
        };
        $curByKey = $byKey($currentSeries);
        $prevByKey = $byKey($previousSeries);
    }

    // Series changed
    $seriesChanged = $isDerivedMetric && $prev && $currentSeries !== $previousSeries;

    // KPI filters
    $currentFilters = $isCustomKpi ? ($version->filters ?? []) : [];
    $previousFilters = ($isCustomKpi && $prev) ? ($prev->filters ?? []) : [];
    $filtersChanged = $prev && $currentFilters !== $previousFilters;

    // Dashboard sections
    $renderSection = function ($key, $label) use ($version, $prev) {
        $v = $version->getAttribute($key);
        $pv = $prev?->getAttribute($key);
        $changed = $prev && $v !== $pv;
        return compact('v', 'pv', 'changed');
    };
@endphp

<div class="space-y-5">
    {{-- Version Header --}}
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
        @else
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400 ring-1 ring-inset ring-gray-200 dark:ring-gray-700">
                {{ $version->change_summary }}
            </span>
        @endif
    </div>

    @if($prev)
        <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-white/5 rounded-lg px-4 py-2.5 ring-1 ring-gray-950/5 dark:ring-white/10">
            <x-filament::icon icon="heroicon-m-information-circle" class="w-4 h-4 text-gray-400 dark:text-gray-500" />
            <span>Compared to version <strong class="text-gray-950 dark:text-white">#{{ $prev->version_number }}</strong></span>
            @if(!empty($changedFields) || $astChanged || $seriesChanged || $filtersChanged)
                <span class="text-gray-400 dark:text-gray-500">·</span>
                <span class="text-warning-600 dark:text-warning-400 font-medium">{{ count($changedFields) + ($astChanged ? 1 : 0) + ($seriesChanged ? 1 : 0) + ($filtersChanged ? 1 : 0) }} change(s)</span>
            @endif
        </div>
    @endif

    {{-- Details Card --}}
    <div class="rounded-xl ring-1 bg-gray-50 dark:bg-white/5 ring-gray-950/5 dark:ring-white/10 overflow-hidden">
        <div class="flex items-center gap-2 px-5 py-3 bg-gray-50 dark:bg-white/5 border-b border-gray-200 dark:border-gray-700">
            <x-filament::icon icon="heroicon-m-document-text" class="w-4 h-4 text-gray-500 dark:text-gray-400" />
            <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Details</span>
        </div>
        <div class="px-5 py-4 space-y-3">
            @foreach($fields as $def)
                @php
                    $val = $version->getAttribute($def['key']);
                    $prevVal = $prev?->getAttribute($def['key']);
                    $changed = $prev && $val !== $prevVal;
                    if ($def['key'] === 'description' && !$val && !$prevVal) continue;
                @endphp
                <div class="flex items-baseline gap-3">
                    <span class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 shrink-0 w-28">{{ $def['label'] }}</span>
                    <div class="flex-1 min-w-0">
                        @if($changed)
                            <span class="text-gray-400 dark:text-gray-500 line-through text-xs">{{ $fmt($prevVal) }}</span>
                            <x-filament::icon icon="heroicon-m-arrow-right" class="w-3 h-3 text-warning-500 inline shrink-0 mx-1" />
                            <span class="text-warning-700 dark:text-warning-200 font-medium text-sm">{{ $fmt($val) }}</span>
                        @else
                            <span class="text-gray-950 dark:text-white text-sm">{{ $fmt($val) }}</span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Source Series (DM only) --}}
    @if($isDerivedMetric && !empty($seriesKeys))
        <div class="rounded-xl ring-1 ring-gray-950/5 dark:ring-white/10 overflow-hidden">
            <div class="flex items-center gap-2 px-5 py-3 bg-gray-50 dark:bg-white/5 border-b border-gray-200 dark:border-gray-700">
                <x-filament::icon icon="heroicon-m-table-cells" class="w-4 h-4 text-gray-500 dark:text-gray-400" />
                <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Source Series</span>
                @if($seriesChanged)
                    <span class="text-xs font-medium text-warning-600 dark:text-warning-400">(changed)</span>
                @endif
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-white/5">
                            <th class="text-left px-5 py-2.5 text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 whitespace-nowrap">Key</th>
                            <th class="text-left px-5 py-2.5 text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 whitespace-nowrap">Label</th>
                            <th class="text-left px-5 py-2.5 text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 whitespace-nowrap">Channel</th>
                            <th class="text-left px-5 py-2.5 text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 whitespace-nowrap">Metric</th>
                            <th class="text-left px-5 py-2.5 text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 whitespace-nowrap">Granularity</th>
                            @php
                                $anyFilters = false;
                                foreach ($seriesKeys as $sk) {
                                    $c = $curByKey[$sk] ?? null;
                                    $p = $prevByKey[$sk] ?? null;
                                    if (!empty($c['asset_filter'] ?? []) || !empty($p['asset_filter'] ?? [])) {
                                        $anyFilters = true; break;
                                    }
                                }
                            @endphp
                            @if($anyFilters)
                                <th class="text-left px-5 py-2.5 text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 whitespace-nowrap">Filters</th>
                            @endif
                            @if($prev)
                                <th class="text-left px-5 py-2.5 text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 whitespace-nowrap w-24">Status</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($seriesKeys as $sk)
                            @php
                                $curr = $curByKey[$sk] ?? null;
                                $prevEnt = $prevByKey[$sk] ?? null;
                                $isNew = $curr && !$prevEnt;
                                $isRemoved = $prevEnt && !$curr;
                                $hasChanges = $prev && $curr && $prevEnt && $curr !== $prevEnt;
                                $rowBg = $isNew ? 'bg-success-50/30 dark:bg-success-400/5' : ($isRemoved ? 'bg-danger-50/30 dark:bg-danger-400/5' : ($hasChanges ? 'bg-warning-50/30 dark:bg-warning-400/5' : ''));
                            @endphp
                            <tr class="border-b border-gray-100 dark:border-gray-700/50 {{ $rowBg }}">
                                <td class="px-5 py-2.5 font-medium text-gray-950 dark:text-white whitespace-nowrap">{{ $sk }}</td>
                                @foreach(['label', 'channel', 'metric', 'granularity'] as $sf)
                                    @php
                                        $cv = $curr[$sf] ?? '—';
                                        $pv = $prevEnt[$sf] ?? '—';
                                        $cellChanged = $prev && $curr && $prevEnt && $cv !== $pv;
                                    @endphp
                                    <td class="px-5 py-2.5 whitespace-nowrap">
                                        @if($cellChanged)
                                            <span class="text-gray-400 dark:text-gray-500 line-through text-xs mr-1">{{ $pv }}</span>
                                            <x-filament::icon icon="heroicon-m-arrow-right" class="w-3 h-3 text-warning-500 inline shrink-0 -mt-0.5" />
                                            <span class="text-warning-700 dark:text-warning-200 font-medium ml-1">{{ $cv }}</span>
                                        @elseif($isRemoved)
                                            <span class="text-gray-400 dark:text-gray-500 line-through">{{ $pv }}</span>
                                        @elseif($isNew)
                                            <span class="text-success-700 dark:text-success-300 font-medium">{{ $cv }}</span>
                                        @else
                                            <span class="text-gray-950 dark:text-white">{{ $cv }}</span>
                                        @endif
                                    </td>
                                @endforeach
                                @if($anyFilters)
                                    @php
                                        $cf = $curr['asset_filter'] ?? [];
                                        $pf = $prevEnt['asset_filter'] ?? [];
                                        $fCh = $prev && $curr && $prevEnt && $cf !== $pf;
                                    @endphp
                                    <td class="px-5 py-2.5 text-xs whitespace-nowrap">
                                        @if($fCh)
                                            <span class="text-gray-400 dark:text-gray-500 line-through mr-1">{{ $fmt($pf) }}</span>
                                            <x-filament::icon icon="heroicon-m-arrow-right" class="w-3 h-3 text-warning-500 inline shrink-0 -mt-0.5" />
                                            <span class="text-warning-700 dark:text-warning-200 font-medium ml-1">{{ $fmt($cf) }}</span>
                                        @elseif(!empty($cf))
                                            <span class="text-gray-950 dark:text-white">{{ $fmt($cf) }}</span>
                                        @elseif(!empty($pf))
                                            <span class="text-gray-400 dark:text-gray-500 line-through">{{ $fmt($pf) }}</span>
                                        @else
                                            <span class="text-gray-400 dark:text-gray-500">—</span>
                                        @endif
                                    </td>
                                @endif
                                @if($prev)
                                    <td class="px-5 py-2.5 text-xs whitespace-nowrap">
                                        @if($isNew)
                                            <span class="text-success-600 dark:text-success-400 font-medium">Added</span>
                                        @elseif($isRemoved)
                                            <span class="text-danger-600 dark:text-danger-400 font-medium">Removed</span>
                                        @elseif($hasChanges)
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

    {{-- Formula (DM + KPI) --}}
    @if($hasFormula && ($currentFormulaStr || $previousFormulaStr))
        <div class="rounded-xl p-5 ring-1 {{ $astChanged ? 'bg-warning-50 dark:bg-warning-400/5 ring-warning-300 dark:ring-warning-600' : 'bg-gray-50 dark:bg-white/5 ring-gray-950/5 dark:ring-white/10' }}">
            <div class="flex items-center gap-2 mb-3">
                <x-filament::icon icon="heroicon-m-variable" class="w-4 h-4 text-gray-500 dark:text-gray-400" />
                <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Formula</span>
                @if($astChanged)
                    <span class="text-xs font-medium text-warning-600 dark:text-warning-400">(changed)</span>
                @endif
            </div>
            @if($astChanged)
                <div class="text-sm text-gray-400 dark:text-gray-500 line-through mb-1.5 font-mono">{{ $previousFormulaStr }}</div>
                <x-filament::icon icon="heroicon-m-arrow-down" class="w-4 h-4 text-warning-500 mb-1" />
            @endif
            <div class="text-sm {{ $astChanged ? 'text-warning-700 dark:text-warning-200 font-medium' : 'text-gray-950 dark:text-white' }} font-mono leading-relaxed">{{ $currentFormulaStr }}</div>
        </div>
    @endif

    {{-- KPI Filters --}}
    @if($isCustomKpi && (!empty($currentFilters) || !empty($previousFilters)))
        <div class="rounded-xl p-5 ring-1 {{ $filtersChanged ? 'bg-warning-50 dark:bg-warning-400/5 ring-warning-300 dark:ring-warning-600' : 'bg-gray-50 dark:bg-white/5 ring-gray-950/5 dark:ring-white/10' }}">
            <div class="flex items-center gap-2 mb-3">
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

    {{-- Dashboard Layout & Controls --}}
    @if($isDashboard)
        @foreach([['key' => 'grid_layout', 'label' => 'Grid Layout', 'icon' => 'heroicon-m-view-columns'], ['key' => 'controls', 'label' => 'Controls', 'icon' => 'heroicon-m-adjustments-horizontal']] as $section)
            @php
                $data = $renderSection($section['key'], $section['label']);
            @endphp
            @if($data['v'] || $data['pv'])
                <div class="rounded-xl p-5 ring-1 {{ $data['changed'] ? 'bg-warning-50 dark:bg-warning-400/5 ring-warning-300 dark:ring-warning-600' : 'bg-gray-50 dark:bg-white/5 ring-gray-950/5 dark:ring-white/10' }}">
                    <div class="flex items-center gap-2 mb-3">
                        <x-filament::icon icon="{{ $section['icon'] }}" class="w-4 h-4 text-gray-500 dark:text-gray-400" />
                        <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ $section['label'] }}</span>
                        @if($data['changed'])
                            <span class="text-xs font-medium text-warning-600 dark:text-warning-400">(changed)</span>
                        @endif
                    </div>
                    @if($data['changed'])
                        <div class="text-xs text-gray-400 dark:text-gray-500 line-through mb-1.5 font-mono whitespace-pre-wrap">{{ $fmt($data['pv']) }}</div>
                        <x-filament::icon icon="heroicon-m-arrow-down" class="w-4 h-4 text-warning-500 mb-1" />
                    @endif
                    <div class="text-xs {{ $data['changed'] ? 'text-warning-700 dark:text-warning-200 font-medium' : 'text-gray-950 dark:text-white' }} font-mono whitespace-pre-wrap">{{ $fmt($data['v']) }}</div>
                </div>
            @endif
        @endforeach
    @endif
</div>
