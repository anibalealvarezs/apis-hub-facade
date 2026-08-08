@props([
    'defaultClass' => 'transition duration-150',
    'onClick' => null,
    'clickable' => null,
    'active' => null,
    'hoverClass' => 'hover:bg-gray-50 dark:hover:bg-white/5',
    'selectableClass' => 'bg-primary-50 dark:bg-primary-900/20 shadow-inner',
    'inactiveClass' => null,
    'disabledClass' => 'opacity-50 cursor-not-allowed',
])

@php
    $inactive = $inactiveClass !== null ? $inactiveClass : $hoverClass;
@endphp

<tr {{ $attributes->merge(['class' => $defaultClass]) }}
    @if ($onClick)
        @click="{!! $onClick !!}"
    @endif
    :class="[
        {{ $active ?? 'false' }} ? '{{ $selectableClass }}' : (@if ($clickable){{ $clickable }} ? '{{ $hoverClass }}' : '{{ $inactive }}'@else'{{ $hoverClass }}'@endif)
        @if ($clickable)
        , {{ $clickable }} ? 'cursor-pointer' : '{{ $disabledClass }}'
        @endif
    ]">
    {{ $slot }}
</tr>
