@php
    $statePath = $getStatePath();
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
        statePath: '{{ addslashes($statePath) }}',
        seriesFieldPath: '{{ addslashes(str_replace('.ast', '.source_series', $statePath)) }}',
        wire: @this,
        operators: @js($operators),
    })"
    x-init="$nextTick(() => hydrateFromLivewire())"
    class="space-y-3"
>
    <input type="hidden" :name="statePath" x-model="jsonAst">

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
            <div class="flex items-center gap-2 p-3 flex-wrap" :style="'padding-left: ' + (entry.depth * 24 + 12) + 'px'">
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
                            <template x-for="sk in seriesKeys" :key="sk">
                                <option :value="'metric:' + sk" x-text="sk"></option>
                            </template>
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
                                <template x-for="(label, op) in operators" :key="op">
                                    <option :value="op" x-text="label"></option>
                                </template>
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
                            <template x-for="(label, op) in operators" :key="op">
                                <option :value="op" x-text="label"></option>
                            </template>
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
                                <template x-for="sk in seriesKeys" :key="sk">
                                    <option :value="'metric:' + sk" x-text="sk"></option>
                                </template>
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

<script>
document.addEventListener('alpine:init', () => {
    if (window.__formulaEditorRegistered) return;
    window.__formulaEditorRegistered = true;

    Alpine.data('formulaEditor', (config) => ({
        statePath: config.statePath,
        seriesFieldPath: config.seriesFieldPath,
        wire: config.wire,
        operators: config.operators,
        flatNodes: {},
        jsonAst: '{}',
        nodeCounter: 0,
        seriesKeys: [],

        getSeriesKeys() {
            try {
                const raw = this.wire.get(this.seriesFieldPath);
                if (!raw || !Array.isArray(raw)) return [];
                return raw.map((s, i) => s.key || String.fromCharCode(97 + i));
            } catch {
                return [];
            }
        },

        hydrateFromLivewire() {
            this.seriesKeys = this.getSeriesKeys();
            const raw = this.wire.get(this.statePath);
            if (!raw || typeof raw !== 'object' || !raw.type) {
                this.flatNodes = {};
                this.jsonAst = '{}';
                return;
            }
            this.flatNodes = {};
            this.nodeCounter = 0;
            this.flatNodes['root'] = this.astToEntry(raw, 'root');
            this.jsonAst = JSON.stringify(raw);
        },

        astToEntry(node, path) {
            if (!node || node.type === undefined) return null;
            const entry = { path, depth: 0, side: 'root', leftType: '', leftValue: 0, operator: '', rightType: '', rightValue: 0, childPaths: [] };

            if (node.type === 'metric') {
                entry.leftType = 'metric:' + (node.metric || '');
            } else if (node.type === 'value') {
                entry.leftType = 'value';
                entry.leftValue = node.value ?? 0;
                entry.operator = '';
                entry.rightType = '';
            } else if (node.type === 'operator') {
                entry.leftType = 'operator';
                entry.operator = node.operator || '';
                if (node.left) {
                    if (node.left.type === 'metric') {
                        entry.leftType = 'metric:' + (node.left.metric || '');
                    } else if (node.left.type === 'value') {
                        entry.leftType = 'value';
                        entry.leftValue = node.left.value ?? 0;
                    } else if (node.left.type === 'operator') {
                        entry.leftType = 'operator';
                        entry.childPaths.push(path + '.left');
                    }
                }
                if (node.right) {
                    if (node.right.type === 'metric') {
                        entry.rightType = 'metric:' + (node.right.metric || '');
                    } else if (node.right.type === 'value') {
                        entry.rightType = 'value';
                        entry.rightValue = node.right.value ?? 0;
                    }
                }
            }
            return entry;
        },

        entryToAst(entry) {
            if (!entry) return null;
            if (entry.leftType === 'operator') {
                const leftChild = this.flatNodes[entry.childPaths[0]] || null;
                return {
                    type: 'operator',
                    operator: entry.operator,
                    left: leftChild ? this.entryToAst(leftChild) : { type: 'value', value: 0 },
                    right: this.makeOperand(entry.rightType, entry.rightValue),
                };
            }
            return {
                type: 'operator',
                operator: entry.operator,
                left: this.makeOperand(entry.leftType, entry.leftValue),
                right: this.makeOperand(entry.rightType, entry.rightValue),
            };
        },

        makeOperand(type, value) {
            if (!type) return { type: 'value', value: 0 };
            if (type === 'value') return { type: 'value', value: value ?? 0 };
            if (type.startsWith('metric:')) return { type: 'metric', metric: type.substring(7) };
            return { type: 'value', value: 0 };
        },

        onNodeChange(entry) {
            if (entry.leftType === 'operator' && entry.childPaths.length === 0) {
                const childPath = entry.path + '.left';
                this.nodeCounter++;
                this.flatNodes[childPath] = {
                    path: childPath,
                    depth: entry.depth + 1,
                    side: 'Left',
                    leftType: '', leftValue: 0, operator: '', rightType: '', rightValue: 0,
                    childPaths: [],
                };
                entry.childPaths = [childPath];
            }
            if (entry.leftType !== 'operator' && entry.childPaths.length > 0) {
                for (const cp of entry.childPaths) {
                    delete this.flatNodes[cp];
                }
                entry.childPaths = [];
            }
            this.rebuildAst();
        },

        rebuildAst() {
            const root = this.flatNodes['root'];
            if (!root || !root.leftType || !root.operator || !root.rightType) {
                this.jsonAst = '{}';
                return;
            }
            const ast = this.entryToAst(root);
            this.jsonAst = JSON.stringify(ast);

            this.wire.set(this.statePath, ast);
        },

        addRootNode() {
            this.flatNodes['root'] = {
                path: 'root', depth: 0, side: 'root',
                leftType: '', leftValue: 0, operator: '', rightType: '', rightValue: 0,
                childPaths: [],
            };
        },

        refreshKeys() {
            this.seriesKeys = this.getSeriesKeys();
        },

        reset() {
            this.flatNodes = {};
            this.jsonAst = '{}';
            this.wire.set(this.statePath, null);
        },
    }));
});
</script>
