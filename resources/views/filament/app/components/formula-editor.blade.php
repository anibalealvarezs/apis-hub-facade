@php
    $astStatePath = $astStatePath ?? 'data.ast';

    $operators = [
        '+' => '+ (Add)',
        '-' => '- (Subtract)',
        '*' => '* (Multiply)',
        '/' => '/ (Divide)',
        'ratio' => 'ratio (A / (A+B))',
        'avg' => 'avg ((A+B) / 2)',
        'min' => 'min (A, B)',
        'max' => 'max (A, B)',
        'abs_diff' => '|A - B|',
        'pct_change' => '% change ((A-B)/B)',
    ];

    $wrapperClasses = 'fi-input-wrp flex items-center rounded-lg shadow-sm ring-1 transition duration-75 bg-white dark:bg-white/5 ring-gray-950/10 dark:ring-white/20 focus-within:ring-2 focus-within:ring-primary-600 dark:focus-within:ring-primary-500';
    $selectClasses = 'fi-select-input block w-full border-none bg-transparent py-1.5 pe-8 text-base text-gray-950 transition duration-75 focus:ring-0 disabled:text-gray-500 dark:text-white dark:disabled:text-gray-400 sm:text-sm sm:leading-6 ps-3 [&_optgroup]:bg-white [&_optgroup]:dark:bg-gray-900 [&_option]:bg-white [&_option]:dark:bg-gray-900';
    $inputClasses = 'fi-input block w-full border-none bg-transparent/0 py-1.5 text-base text-gray-950 transition duration-75 placeholder:text-gray-400 focus:ring-0 disabled:text-gray-500 dark:text-white dark:placeholder:text-gray-500 dark:disabled:text-gray-400 sm:text-sm sm:leading-6 ps-3';
@endphp

<div
    x-data="formulaEditor({
        astStatePath: '{{ addslashes($astStatePath) }}',
        initialSeriesKeys: @js($seriesKeys ?? []),
        seriesData: @js($seriesData ?? []),
        initialAst: @js($initialAst ?? null),
        wire: @this,
        operators: @js($operators),
    })"
    x-init="console.log('[FE] x-init running, has hydrateFromServer:', typeof hydrateFromServer); $nextTick(() => hydrateFromServer())"
    class="space-y-3"
>
    <div class="flex items-center justify-between">
        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Formula') }}</span>
        <div class="flex gap-2">
            <button type="button" @click="refreshKeys()" class="text-xs text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300">{{ __('Refresh keys') }}</button>
            <button type="button" @click="reset()" class="text-xs text-danger-600 hover:text-danger-500 dark:text-danger-400 dark:hover:text-danger-300">{{ __('Reset') }}</button>
        </div>
    </div>

    {{-- Root node editor --}}
    <div class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
        <template x-for="(entry, path) in flatNodes" :key="entry.path">
            <div class="flex items-center gap-2 p-3 flex-wrap" :data-flat-key="entry.path" :style="'padding-left: ' + (entry.depth * 24 + 12) + 'px'">
                {{-- Depth indicator --}}
                <template x-if="entry.depth > 0">
                    <span class="text-gray-400 dark:text-gray-500 text-xs mr-1" x-text="'└'"></span>
                </template>

                {{-- Label --}}
                <span class="text-xs text-gray-500 dark:text-gray-400 w-16 shrink-0" x-text="entry.depth === 0 ? 'Formula:' : entry.side + ':'"></span>

                {{-- Left/Operand select --}}
                <div class="{{ $wrapperClasses }} max-w-[180px]">
                    <select
                        class="{{ $selectClasses }}"
                        x-model="entry.leftType"
                        @change="onNodeChange(entry)"
                    >
                        <option value="">{{ __('Select...') }}</option>
                        <optgroup label="{{ __('Source Series') }}">
                            @foreach($seriesKeys as $sk)
                                <option value="{{ 'metric:' . $sk }}">{{ $sk }}@if(!empty($seriesData[$loop->index]['label'])) — {{ $seriesData[$loop->index]['label'] }}@endif</option>
                            @endforeach
                        </optgroup>
                        <optgroup label="{{ __('Number') }}">
                            <option value="value">{{ __('Literal number') }}</option>
                        </optgroup>
                        <optgroup label="{{ __('Sub-formula') }}">
                            <option value="operator">{{ __('( A op B )') }}</option>
                        </optgroup>
                    </select>
                </div>

                {{-- Literal value input --}}
                <template x-if="entry.leftType === 'value'">
                    <div class="{{ $wrapperClasses }} w-28">
                        <input
                            type="number"
                            class="{{ $inputClasses }}"
                            x-model.number="entry.leftValue"
                            @input="onNodeChange(entry)"
                            placeholder="0"
                            step="any"
                        >
                    </div>
                </template>

                {{-- Operator select (only for operator nodes — nested sub-formula) --}}
                <template x-if="entry.leftType === 'operator'">
                    <div class="flex items-center gap-2">
                        <span class="text-gray-400 dark:text-gray-500 text-sm">(</span>
                        <div class="{{ $wrapperClasses }} max-w-[200px]">
                            <select
                                class="{{ $selectClasses }}"
                                x-model="entry.operator"
                                @change="onNodeChange(entry)"
                            >
                                <option value="">{{ __('Operator') }}</option>
                                @foreach($operators as $op => $label)
                                    <option value="{{ $op }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <span class="text-gray-400 dark:text-gray-500 text-sm">)</span>
                    </div>
                </template>

                {{-- Operator for top-level (when leftType is a metric or value) --}}
                <template x-if="entry.leftType !== 'operator' && entry.leftType !== ''">
                    <div class="{{ $wrapperClasses }} max-w-[200px]">
                        <select
                            class="{{ $selectClasses }}"
                            x-model="entry.operator"
                            @change="onNodeChange(entry)"
                        >
                            <option value="">{{ __('Operator') }}</option>
                            @foreach($operators as $op => $label)
                                <option value="{{ $op }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </template>

                {{-- Right operand select --}}
                <template x-if="entry.leftType !== '' && entry.operator !== ''">
                    <div class="{{ $wrapperClasses }} max-w-[180px]">
                        <select
                            class="{{ $selectClasses }}"
                            x-model="entry.rightType"
                            @change="onNodeChange(entry)"
                        >
                            <option value="">{{ __('Select...') }}</option>
                            <optgroup label="{{ __('Source Series') }}">
                                @foreach($seriesKeys as $sk)
                                    <option value="{{ 'metric:' . $sk }}">{{ $sk }}@if(!empty($seriesData[$loop->index]['label'])) — {{ $seriesData[$loop->index]['label'] }}@endif</option>
                                @endforeach
                            </optgroup>
                            <optgroup label="{{ __('Number') }}">
                                <option value="value">{{ __('Literal number') }}</option>
                            </optgroup>
                        </select>
                    </div>
                </template>

                {{-- Right literal value --}}
                <template x-if="entry.rightType === 'value'">
                    <div class="{{ $wrapperClasses }} w-28">
                        <input
                            type="number"
                            class="{{ $inputClasses }}"
                            x-model.number="entry.rightValue"
                            @input="onNodeChange(entry)"
                            placeholder="0"
                            step="any"
                        >
                    </div>
                </template>
            </div>
        </template>
    </div>

    {{-- Empty state --}}
    <template x-if="Object.keys(flatNodes).length === 0">
        <div class="text-center py-4">
            <button type="button" @click="addRootNode()" class="text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300 font-medium text-sm">
                {{ __('+ Build Formula') }}
            </button>
        </div>
    </template>
</div>


