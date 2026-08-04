@props([
    'model' => 'selectedAssetGroup',
    'options' => 'assetGroups',
    'changeEvent' => '',
    'placeholder' => __('Select Option...'),
    'emptyOption' => null,
    'size' => 'xs',
    'multiple' => false,
    'allKeys' => null,
])

@php
    $sizeClasses = [
        'xs' => 'text-xs px-3 py-2 h-[34px] min-w-[170px]',
        'sm' => 'text-sm px-4 py-2.5 h-[42px] min-w-[220px]',
        'md' => 'text-sm px-4 py-2.5 h-[42px] min-w-[250px]',
    ][$size] ?? 'text-xs px-3 py-2 h-[34px] min-w-[170px]';
@endphp

<div class="relative" x-data="{ open: false, searchAssetGroup: '' }" @click.outside="open = false">
    <button @click="if (!$el.hasAttribute('disabled') && !$el.disabled) open = !open" type="button"
            {{ $attributes->merge([
                'class' => "bg-white dark:bg-white/5 border border-gray-300 dark:border-white/10 text-gray-950 dark:text-white {$sizeClasses} rounded-lg focus:ring-primary-500 focus:border-primary-500 flex items-center justify-between cursor-pointer"
            ]) }}>
        @if($multiple)
            <span class="truncate font-medium text-gray-700 dark:text-gray-200"
                  x-text="!{{ $model }} || {{ $model }}.length === 0 ? '{{ $placeholder }}' : ({{ $model }}.length === 1 ? ({{ $options }}[{{ $model }}[0]] || {{ $model }}[0]) : {{ $model }}.length + ' {{ __('selected') }}')"></span>
        @else
            <span class="truncate font-medium text-gray-700 dark:text-gray-200"
                  x-text="!{{ $model }} ? '{{ $emptyOption ?? $placeholder }}' : ({{ $options }}[{{ $model }}] || '{{ $placeholder }}')"></span>
        @endif
        <svg class="w-3.5 h-3.5 ml-2 flex-shrink-0 text-gray-500 dark:text-gray-400 transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
        </svg>
    </button>

    <div x-show="open" x-transition style="display: none; min-width: 260px;"
         class="absolute z-50 w-64 mt-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-xl right-0 flex flex-col">

        <!-- Search & Actions Header -->
        <div class="p-2 border-b border-gray-200 dark:border-gray-700 space-y-2">
            <input type="text" x-model="searchAssetGroup"
                   class="bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white text-xs rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-1.5"
                   placeholder="{{ __('Search...') }}">
            @if($multiple)
                <div class="flex items-center justify-between px-1">
                    <button type="button" @click="{{ $model }} = {{ $allKeys ?? ('Object.keys(' . $options . ')') }}; {{ $changeEvent }}"
                            class="text-xs font-medium text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300">{{ __('Select All') }}</button>
                    <button type="button" @click="{{ $model }} = []; {{ $changeEvent }}"
                            class="text-xs font-medium text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200">{{ __('Clear All') }}</button>
                </div>
            @endif
        </div>

        <!-- List of Options -->
        <div class="p-1.5 flex flex-col gap-1 overflow-y-auto max-h-60">
            @if(!$multiple && $emptyOption)
                <div @click="{{ $model }} = ''; {{ $changeEvent ? $changeEvent . ';' : '' }} open = false;"
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
                <div x-show="searchAssetGroup === '' || name.toLowerCase().includes(searchAssetGroup.toLowerCase()) || String(id).toLowerCase().includes(searchAssetGroup.toLowerCase())"
                     @if($multiple)
                         @click="{{ $model }}.includes(String(id)) ? {{ $model }} = {{ $model }}.filter(a => a != id) : {{ $model }}.push(String(id)); {{ $changeEvent }}"
                     @else
                         @click="{{ $model }} = id; {{ $changeEvent ? $changeEvent . ';' : '' }} open = false;"
                     @endif
                     class="flex gap-x-2.5 items-center px-3 py-2 text-xs text-gray-700 dark:text-gray-300 rounded-md cursor-pointer transition-all duration-150 border"
                     :class="@if($multiple) {{ $model }}.includes(String(id)) @else {{ $model }} == id @endif ? 'bg-primary-50 dark:bg-primary-900/20 border-primary-200 dark:border-primary-800' : 'hover:bg-gray-100 dark:hover:bg-gray-700 border-transparent'">
                    <div class="w-4 h-4 shrink-0 flex items-center justify-center rounded-full border-2 transition-colors duration-150"
                         :class="@if($multiple) {{ $model }}.includes(String(id)) @else {{ $model }} == id @endif ? 'bg-primary-600 border-primary-600' : 'border-gray-300 dark:border-gray-600'">
                        <svg x-show="@if($multiple) {{ $model }}.includes(String(id)) @else {{ $model }} == id @endif" class="w-2.5 h-2.5 text-white" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                        </svg>
                    </div>
                    <span class="truncate font-medium text-gray-900 dark:text-white" :class="@if($multiple) {{ $model }} == id @endif ? 'text-primary-700 dark:text-primary-300 font-semibold' : ''" x-text="name"></span>
                </div>
            </template>
        </div>
    </div>
</div>
