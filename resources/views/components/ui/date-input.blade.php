@props([
    'size' => 'sm',
])

@php
    $sizeClasses = [
        'xs' => 'text-xs px-3 py-2',
        'sm' => 'text-sm p-2.5',
        'md' => 'text-base p-3',
    ][$size] ?? 'text-sm p-2.5';
@endphp

<input type="date"
    {{ $attributes->merge([
        'class' => "bg-white dark:bg-white/5 border border-gray-300 dark:border-white/10 text-gray-950 dark:text-white dark:[color-scheme:dark] {$sizeClasses} rounded-lg focus:ring-primary-500 focus:border-primary-500 block cursor-pointer"
    ]) }}
/>
