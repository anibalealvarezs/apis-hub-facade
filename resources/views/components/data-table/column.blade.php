@props([
    'state' => '',
    'key' => null,
    'keyBind' => null,
    'label' => null,
    'sortable' => true,
    'class' => '',
])

@php
    $prefix = $state ? $state . '.' : '';
    $sortKey = $keyBind ?? ($key !== null ? "'" . $key . "'" : null);
@endphp

<th {{ $attributes->merge(['class' => $sortable ? trim('metric-cell cursor-pointer ' . $class) : $class]) }}
    @if ($sortable)
        @click="{{ $prefix }}sortBy({{ $sortKey }})"
    @endif>
    @if ($label !== null)
        {{ $label }}
    @endif
    {{ $slot }}
    @if ($sortable)
        <span x-show="{{ $prefix }}sortCol === {{ $sortKey }}"
              x-text="{{ $prefix }}sortDir === 'desc' ? '↓' : '↑'"></span>
    @endif
</th>
