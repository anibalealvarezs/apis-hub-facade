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
                    '+' => '+',
                    '-' => '−',
                    '*' => '×',
                    '/' => '÷',
                    'ratio' => '÷ (ratio)',
                    'avg' => '∅ (avg)',
                    'min' => '↓ min',
                    'max' => '↑ max',
                    'abs_diff' => '|Δ|',
                    'pct_change' => '%Δ',
                    default => " $op ",
                };
                return "($left $symbol $right)";
            })(),
            default => json_encode($node),
        };
    };

    $formatVal = function ($v) {
        if (is_bool($v)) return $v ? 'Yes' : 'No';
        if ($v === null) return '—';
        if (is_array($v)) return json_encode($v, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        return (string) $v;
    };

    $currentSeries = $version->source_series ?? [];
    $previousSeries = $prev?->source_series ?? [];
    $currentMap = $seriesMap($currentSeries);
    $previousMap = $seriesMap($previousSeries);

    $allSeriesKeys = array_unique(array_merge(
        array_column($currentSeries, 'key'),
        array_column($previousSeries, 'key')
    ));
    sort($allSeriesKeys);

    $seriesByKey = function ($series) {
        $byKey = [];
        foreach ($series as $s) {
            $byKey[$s['key']] = $s;
        }
        return $byKey;
    };
    $currentByKey = $seriesByKey($currentSeries);
    $previousByKey = $seriesByKey($previousSeries);
@endphp

<div class="space-y-6">
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

    {{-- Scalar fields --}}
    @php
        $scalarFields = [
            ['key' => 'name', 'label' => 'Name'],
            ['key' => 'description', 'label' => 'Description'],
            ['key' => 'calculation_type', 'label' => 'Calculation Type'],
            ['key' => 'output_granularity', 'label' => 'Output Granularity'],
            ['key' => 'is_active', 'label' => 'Active', 'type' => 'bool'],
        ];
    @endphp

    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        @foreach($scalarFields as $def)
            @php
                $key = $def['key'];
                $isBool = ($def['type'] ?? null) === 'bool';

                $val = $version->getAttribute($key);
                if ($val === null && !$isBool) continue;

                $prevVal = $prev?->getAttribute($key);
                $changed = $prev && $val !== $prevVal;
            @endphp

            <div class="rounded-xl p-4 ring-1 {{ $changed ? 'bg-warning-50 dark:bg-warning-400/5 ring-warning-300 dark:ring-warning-600' : 'bg-gray-50 dark:bg-white/5 ring-gray-950/5 dark:ring-white/10' }}">
                <div class="flex items-center justify-between mb-1.5">
                    <h4 class="text-xs font-semibold uppercase tracking-wider {{ $changed ? 'text-warning-700 dark:text-warning-300' : 'text-gray-500 dark:text-gray-400' }}">
                        {{ $def['label'] }}
                    </h4>
                    @if($changed)
                        <span class="text-xs font-medium text-warning-600 dark:text-warning-400">changed</span>
                    @endif
                </div>
                @if($changed)
                    <div class="text-sm text-gray-400 dark:text-gray-500 line-through mb-1">{{ $formatVal($prevVal) }}</div>
                @endif
                @if($isBool)
                    <div class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium rounded-full {{ $val ? 'bg-success-50 text-success-700 dark:bg-success-400/10 dark:text-success-300 ring-1 ring-inset ring-success-200 dark:ring-success-700' : 'bg-danger-50 text-danger-700 dark:bg-danger-400/10 dark:text-danger-300 ring-1 ring-inset ring-danger-200 dark:ring-danger-700' }}">
                        <x-filament::icon icon="{{ $val ? 'heroicon-m-check-circle' : 'heroicon-m-x-circle' }}" class="w-3.5 h-3.5" />
                        {{ $formatVal($val) }}
                    </div>
                @else
                    <div class="text-sm {{ $changed ? 'text-warning-700 dark:text-warning-200 font-medium' : 'text-gray-950 dark:text-white' }}">{{ $formatVal($val) }}</div>
                @endif
            </div>
        @endforeach
    </div>

    {{-- Source Series --}}
    @if(!empty($currentSeries) || !empty($previousSeries))
        <div>
            <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-3 flex items-center gap-2">
                <x-filament::icon icon="heroicon-m-table-cells" class="w-4 h-4" />
                Source Series
                @if($prev && $currentSeries !== $previousSeries)
                    <span class="text-xs font-medium text-warning-600 dark:text-warning-400">(changed)</span>
                @endif
            </h3>

            <div class="space-y-2">
                @foreach($allSeriesKeys as $sk)
                    @php
                        $curr = $currentByKey[$sk] ?? null;
                        $prevEnt = $previousByKey[$sk] ?? null;
                        if (!$curr && !$prevEnt) continue;

                        $seriesChanged = $prev && $curr !== $prevEnt;
                        $isNew = !$prevEnt && $curr;
                        $isRemoved = $prevEnt && !$curr;
                        $display = $curr ?? $prevEnt;
                    @endphp

                    <div class="rounded-xl p-4 ring-1 {{ $isNew ? 'bg-success-50 dark:bg-success-400/5 ring-success-300 dark:ring-success-600' : ($isRemoved ? 'bg-danger-50 dark:bg-danger-400/5 ring-danger-300 dark:ring-danger-600' : ($seriesChanged ? 'bg-warning-50 dark:bg-warning-400/5 ring-warning-300 dark:ring-warning-600' : 'bg-gray-50 dark:bg-white/5 ring-gray-950/5 dark:ring-white/10')) }}">
                        <div class="flex items-center justify-between mb-2">
                            <h4 class="text-xs font-semibold uppercase tracking-wider {{ $isNew ? 'text-success-700 dark:text-success-300' : ($isRemoved ? 'text-danger-700 dark:text-danger-300' : ($seriesChanged ? 'text-warning-700 dark:text-warning-300' : 'text-gray-500 dark:text-gray-400')) }}">
                                Key: {{ strtoupper($sk) }}
                                @if($isNew)
                                    <span class="ml-2 text-xs font-medium text-success-600 dark:text-success-400">(added)</span>
                                @elseif($isRemoved)
                                    <span class="ml-2 text-xs font-medium text-danger-600 dark:text-danger-400">(removed)</span>
                                @elseif($seriesChanged)
                                    <span class="ml-2 text-xs font-medium text-warning-600 dark:text-warning-400">(changed)</span>
                                @endif
                            </h4>
                        </div>

                        @if($curr && $prevEnt)
                            @php
                                $seriesFields = ['label', 'channel', 'metric', 'granularity', 'asset_group'];
                                $assetFilterChanged = ($curr['asset_filter'] ?? []) !== ($prevEnt['asset_filter'] ?? []);
                            @endphp
                            @foreach($seriesFields as $sf)
                                @php
                                    $cv = $curr[$sf] ?? '';
                                    $pv = $prevEnt[$sf] ?? '';
                                    $sfChanged = $cv !== $pv;
                                @endphp
                                @if($sfChanged || $sf !== 'asset_group')
                                    <div class="flex items-center gap-2 text-sm {{ $sfChanged ? 'text-warning-700 dark:text-warning-200' : 'text-gray-500 dark:text-gray-400' }}">
                                        <span class="text-xs font-medium uppercase tracking-wider w-16 shrink-0 text-gray-400 dark:text-gray-500">{{ $sf }}</span>
                                        @if($sfChanged)
                                            <span class="text-gray-400 dark:text-gray-500 line-through">{{ $pv ?: '—' }}</span>
                                            <x-filament::icon icon="heroicon-m-arrow-right" class="w-3.5 h-3.5 text-warning-500" />
                                            <span class="font-medium">{{ $cv ?: '—' }}</span>
                                        @else
                                            <span>{{ $cv ?: '—' }}</span>
                                        @endif
                                    </div>
                                @endif
                            @endforeach
                            @if($assetFilterChanged)
                                <div class="flex items-start gap-2 text-sm text-warning-700 dark:text-warning-200">
                                    <span class="text-xs font-medium uppercase tracking-wider w-16 shrink-0 text-gray-400 dark:text-gray-500 pt-0.5">Filters</span>
                                    <div class="flex flex-col gap-0.5">
                                        <span class="text-gray-400 dark:text-gray-500 line-through text-xs">{{ $formatVal($prevEnt['asset_filter'] ?? []) }}</span>
                                        <span class="font-medium text-xs">{{ $formatVal($curr['asset_filter'] ?? []) }}</span>
                                    </div>
                                </div>
                            @endif
                        @elseif($curr)
                            @php
                                $seriesFields = ['label', 'channel', 'metric', 'granularity'];
                            @endphp
                            @foreach($seriesFields as $sf)
                                <div class="text-sm text-gray-500 dark:text-gray-400 flex items-center gap-2">
                                    <span class="text-xs font-medium uppercase tracking-wider w-16 shrink-0 text-gray-400 dark:text-gray-500">{{ $sf }}</span>
                                    <span class="text-success-700 dark:text-success-300 font-medium">{{ $curr[$sf] ?? '—' }}</span>
                                </div>
                            @endforeach
                            @if(!empty($curr['asset_filter'] ?? []))
                                <div class="text-sm text-gray-500 dark:text-gray-400 flex items-start gap-2">
                                    <span class="text-xs font-medium uppercase tracking-wider w-16 shrink-0 text-gray-400 dark:text-gray-500 pt-0.5">Filters</span>
                                    <span class="text-success-700 dark:text-success-300 font-medium text-xs">{{ $formatVal($curr['asset_filter']) }}</span>
                                </div>
                            @endif
                        @elseif($prevEnt)
                            @php
                                $seriesFields = ['label', 'channel', 'metric', 'granularity'];
                            @endphp
                            @foreach($seriesFields as $sf)
                                <div class="text-sm text-gray-400 dark:text-gray-500 flex items-center gap-2">
                                    <span class="text-xs font-medium uppercase tracking-wider w-16 shrink-0 text-gray-400 dark:text-gray-500">{{ $sf }}</span>
                                    <span class="line-through">{{ $prevEnt[$sf] ?? '—' }}</span>
                                </div>
                            @endforeach
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Formula --}}
    @php
        $currentAst = $version->ast;
        $previousAst = $prev?->ast;
        $astChanged = $prev && $currentAst !== $previousAst;

        if ($currentAst && is_array($currentAst)) {
            $currentFormula = $astToString($currentAst, $currentMap);
        } else {
            $currentFormula = $formatVal($currentAst);
        }

        if ($previousAst && is_array($previousAst)) {
            $previousFormula = $astToString($previousAst, $previousMap);
        } else {
            $previousFormula = $formatVal($previousAst);
        }
    @endphp

    @if($currentAst || $previousAst)
        <div>
            <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-3 flex items-center gap-2">
                <x-filament::icon icon="heroicon-m-variable" class="w-4 h-4" />
                Formula
                @if($astChanged)
                    <span class="text-xs font-medium text-warning-600 dark:text-warning-400">(changed)</span>
                @endif
            </h3>

            <div class="rounded-xl p-4 ring-1 {{ $astChanged ? 'bg-warning-50 dark:bg-warning-400/5 ring-warning-300 dark:ring-warning-600' : 'bg-gray-50 dark:bg-white/5 ring-gray-950/5 dark:ring-white/10' }}">
                @if($astChanged)
                    <div class="text-sm text-gray-400 dark:text-gray-500 line-through mb-2 font-mono">{{ $previousFormula }}</div>
                    <x-filament::icon icon="heroicon-m-arrow-down" class="w-4 h-4 text-warning-500 mb-1" />
                @endif
                <div class="text-sm {{ $astChanged ? 'text-warning-700 dark:text-warning-200 font-medium' : 'text-gray-950 dark:text-white' }} font-mono">{{ $currentFormula }}</div>
            </div>
        </div>
    @endif

    @if($prev && $changeType === 'updated')
        <div class="text-xs text-gray-400 dark:text-gray-500 text-right pt-1 border-t border-gray-200 dark:border-gray-700">
            {{ $version->change_summary }}
        </div>
    @endif
</div>
