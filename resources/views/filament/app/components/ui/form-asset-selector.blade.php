<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    @php
        $options = $options ?? (method_exists($field, 'getOptions') ? $field->getOptions() : ($field->getViewData()['options'] ?? []));
        if ($options instanceof \Illuminate\Support\Collection) {
            $options = $options->toArray();
        }
        $placeholder = $placeholder ?? ($field->getPlaceholder() ?? __('Select an option'));
    @endphp

    <div x-data="{
        state: $wire.entangle('{{ $getStatePath() }}'),
        optionsMap: @js($options)
    }" class="w-full">
        <x-ui.asset-selector
            model="state"
            options="optionsMap"
            :placeholder="$placeholder"
            class="w-full !min-w-0"
            size="sm"
        />
    </div>
</x-dynamic-component>
