<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    @php
        $targetOptions = [
            'CAMPAIGN' => __('Campaign Filter'),
            'ADSET'    => __('Adset Filter'),
            'AD'       => __('Ad Filter'),
        ];
    @endphp

    <div x-data="{
        state: $wire.entangle('{{ $getStatePath() }}'),
        targetOptions: @js($targetOptions)
    }" class="w-full">
        <x-ui.asset-selector
            model="state"
            options="targetOptions"
            placeholder="{{ __('Select Target Filter...') }}"
            class="w-full !min-w-0"
            size="sm"
        />
    </div>
</x-dynamic-component>
