@php
    $id = $getId();
    $isDisabled = $isDisabled();
    $statePath = $getStatePath();
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div x-data="{ state: $wire.$entangle('{{ $statePath }}') }" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @foreach ($getOptions() as $value => $label)
            @php
                $optionDisabled = $isDisabled || $isOptionDisabled($value, $label);
            @endphp
            <label 
                for="{{ $id . '-' . $value }}"
                class="cursor-pointer block text-left px-4 py-3 rounded-xl border transition-all duration-200 hover:shadow-md focus-within:ring-2 focus-within:ring-primary-500"
                :class="state === '{{ $value }}' ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20' : 'border-gray-200 dark:border-white/10 bg-white dark:bg-white/5 hover:border-primary-300 dark:hover:border-primary-500/50'"
            >
                <input 
                    type="radio" 
                    id="{{ $id . '-' . $value }}" 
                    name="{{ $id }}" 
                    value="{{ $value }}"
                    {{ $applyStateBindingModifiers('wire:model') }}="{{ $statePath }}"
                    @if($optionDisabled) disabled @endif
                    class="sr-only"
                >
                <div class="font-bold text-sm text-gray-900 dark:text-white">{{ $label }}</div>
                @if ($hasDescription($value))
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $getDescription($value) }}</div>
                @endif
            </label>
        @endforeach
    </div>
</x-dynamic-component>
