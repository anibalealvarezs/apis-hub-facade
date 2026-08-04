@props([
    'model' => 'selectedAssetGroup',
    'options' => 'assetGroups',
    'changeEvent' => 'applyAssetGroup()',
    'placeholder' => __('Select Asset Group...'),
    'emptyOption' => __('All Assets (no filter)'),
    'size' => 'xs',
])

@php
    $sizeClasses = [
        'xs' => 'text-xs px-3 py-2 h-[34px] min-w-[170px]',
        'sm' => 'text-sm px-4 py-2.5 h-[42px] min-w-[220px]',
    ][$size] ?? 'text-xs px-3 py-2 h-[34px] min-w-[170px]';
@endphp

<div class="relative" x-data="{ open: false, searchAssetGroup: '' }" @click.outside="open = false">
    <button @click="open = !open" type="button"
            class="bg-white dark:bg-white/5 border border-gray-300 dark:border-white/10 text-gray-950 dark:text-white {{ $sizeClasses }} rounded-lg focus:ring-primary-500 focus:border-primary-500 flex items-center justify-between">
        <span class="truncate font-medium text-gray-700 dark:text-gray-200"
              x-text="!{{ $model }} ? '{{ $emptyOption }}' : ({{ $options }}[{{ $model }}] || '{{ $placeholder }}')"></span>
        <svg class="w-3.5 h-3.5 ml-2 flex-shrink-0 text-gray-500 dark:text-gray-400 transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
        </svg>
    </button>

    <div x-show="open" x-transition style="display: none; min-width: 240px;"
         class="absolute z-50 w-64 mt-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-xl right-0 flex flex-col">

        <!-- Search Header -->
        <template x-if="Object.keys({{ $options }}).length > 5">
            <div class="p-2 border-b border-gray-200 dark:border-gray-700">
                <input type="text" x-model="searchAssetGroup"
                       class="bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white text-xs rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-1.5"
                       placeholder="{{ __('Search...') }}">
            </div>
        </template>

        <!-- List of Options -->
        <div class="p-1.5 flex flex-col gap-1 overflow-y-auto max-h-60">
            @if($emptyOption)
                <div @click="{{ $model }} = ''; {{ $changeEvent }}; open = false;"
                     class="flex gap-x-2.5 items-center px-3 py-2 text-xs text-gray-700 dark:text-gray-300 rounded-md cursor-pointer transition-all duration-150 border"
                     :class="!{{ $model }} ? 'bg-primary-50 dark:bg-primary-900/20 border-primary-200 dark:border-primary-800' : 'hover:bg-gray-100 dark:hover:bg-gray-700 border-transparent'">
                    <div class="w-4 h-4 shrink-0 flex items-center justify-center rounded-full border-2 transition-colors duration-150"
                         :class="!{{ $model }} ? 'bg-primary-600 border-primary-600' : 'border-gray-300 dark:border-gray-600'">
                        <svg x-show="!{{ $model }}" class="w-2.5 h-2.5 text-white" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                        </svg>
                    </div>
                    <span class="truncate font-medium text-gray-900 dark:text-white" :class="!{{ $model }} ? 'text-primary-700 dark:text-primary-300 font-semibold' : ''">{{ $emptyOption }}</span>
                </div>
            @endif
            <template x-for="(name, id) in {{ $options }}" :key="id">
                <div x-show="searchAssetGroup === '' || name.toLowerCase().includes(searchAssetGroup.toLowerCase())"
                     @click="{{ $model }} = id; {{ $changeEvent }}; open = false;"
                     class="flex gap-x-2.5 items-center px-3 py-2 text-xs text-gray-700 dark:text-gray-300 rounded-md cursor-pointer transition-all duration-150 border"
                     :class="{{ $model }} == id ? 'bg-primary-50 dark:bg-primary-900/20 border-primary-200 dark:border-primary-800' : 'hover:bg-gray-100 dark:hover:bg-gray-700 border-transparent'">
                    <div class="w-4 h-4 shrink-0 flex items-center justify-center rounded-full border-2 transition-colors duration-150"
                         :class="{{ $model }} == id ? 'bg-primary-600 border-primary-600' : 'border-gray-300 dark:border-gray-600'">
                        <svg x-show="{{ $model }} == id" class="w-2.5 h-2.5 text-white" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                        </svg>
                    </div>
                    <span class="truncate font-medium text-gray-900 dark:text-white" :class="{{ $model }} == id ? 'text-primary-700 dark:text-primary-300 font-semibold' : ''" x-text="name"></span>
                </div>
            </template>
        </div>
    </div>
</div>
