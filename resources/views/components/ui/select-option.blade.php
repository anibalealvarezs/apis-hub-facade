<option
    {{ $attributes->merge([
        'class' => 'bg-white dark:bg-gray-800 text-gray-900 dark:text-white'
    ]) }}
>
    {{ $slot }}
</option>
