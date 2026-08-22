@php
    $id = $getId();
    $isDisabled = $isDisabled();
    $name = $getName();
    $options = $getOptions();
    $placeholder = $getPlaceholder() ?? $getLabel() ?? __('Select...');
@endphp

<div class="fi-ac-select-action" x-data="{
    state: $wire.entangle('{{ $name }}').live,
    optionsMap: @js($options)
}">
    <x-ui.asset-selector
        model="state"
        options="optionsMap"
        :placeholder="$placeholder"
        change-event="$wire.set('{{ $name }}', state)"
        size="sm"
    />
</div>
