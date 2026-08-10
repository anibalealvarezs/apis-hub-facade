@props([
    'open' => null,
    'title' => null,
    'icon' => 'heroicon-o-exclamation-triangle',
    'color' => 'danger',
    'confirmLabel' => null,
    'confirmColor' => 'danger',
    'confirmIcon' => null,
    'cancelLabel' => null,
    'onConfirm' => null,
    'onCancel' => null,
    'closeOnConfirm' => true,
    'secondaryLabel' => null,
    'secondaryColor' => 'gray',
    'secondaryIcon' => null,
    'onSecondary' => null,
    'closeOnSecondary' => true,
])

@php
    $validColors = ['danger', 'primary', 'warning', 'success', 'gray'];
    $color = in_array($color, $validColors, true) ? $color : 'danger';
    $confirmColor = in_array($confirmColor, $validColors, true) ? $confirmColor : 'danger';
    $secondaryColor = in_array($secondaryColor, $validColors, true) ? $secondaryColor : 'gray';
    $closeOnConfirm = filter_var($closeOnConfirm, FILTER_VALIDATE_BOOLEAN);
    $closeOnSecondary = filter_var($closeOnSecondary, FILTER_VALIDATE_BOOLEAN);

    $circleClass = match ($color) {
        'primary' => 'bg-primary-100 dark:bg-primary-500/20',
        'warning' => 'bg-warning-100 dark:bg-warning-500/20',
        'success' => 'bg-success-100 dark:bg-success-500/20',
        'gray' => 'bg-gray-100 dark:bg-gray-500/20',
        default => 'bg-danger-100 dark:bg-danger-500/20',
    };
    $iconClass = match ($color) {
        'primary' => 'text-primary-600 dark:text-primary-400',
        'warning' => 'text-warning-600 dark:text-warning-400',
        'success' => 'text-success-600 dark:text-success-400',
        'gray' => 'text-gray-600 dark:text-gray-400',
        default => 'text-danger-600 dark:text-danger-400',
    };

    $onCancel = trim($onCancel ?? $open . ' = false;');
    $onConfirm = trim($onConfirm ?? '');
    $onSecondary = trim($onSecondary ?? '');
    $confirmClick = $closeOnConfirm
        ? trim($onConfirm . '; ' . $open . ' = false;')
        : $onConfirm;
    $secondaryClick = $closeOnSecondary
        ? trim($onSecondary . '; ' . $open . ' = false;')
        : $onSecondary;

    $confirmLabel = $confirmLabel ?? __('Confirm');
    $cancelLabel = $cancelLabel ?? __('Cancel');
@endphp

<template x-teleport="body">
    <div
        x-show="{{ $open }}"
        x-cloak
        x-trap.noscroll="{{ $open }}"
        class="bd-modal-root fixed inset-0 z-[100] flex items-start justify-center pt-16 sm:pt-32 overflow-y-auto"
        role="dialog"
        aria-modal="true"
        aria-labelledby="confirm-modal-title"
    >
        <div @click="{{ $onCancel }}"
             class="fixed inset-0 bg-black/50 dark:bg-black/70 backdrop-blur-sm transition-opacity"></div>

        <div
            x-show="{{ $open }}"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="relative bg-white dark:bg-gray-900 rounded-xl shadow-xl mx-4 sm:mx-auto my-4 sm:my-6 flex flex-col ring-1 ring-gray-900/5 dark:ring-white/10 p-6 w-full sm:max-w-lg"
        >
            <div class="flex items-start gap-4">
                <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full sm:mx-0 sm:h-10 sm:w-10 {{ $circleClass }}">
                    <x-dynamic-component :component="$icon" class="h-6 w-6 {{ $iconClass }}" />
                </div>
                <div class="mt-0 text-left flex-grow">
                    <h3 class="text-lg leading-6 font-bold text-gray-900 dark:text-white" id="confirm-modal-title">
                        {{ $title }}
                    </h3>
                    <div class="mt-2">
                        {{ $slot }}
                    </div>
                </div>
            </div>
            <div class="mt-6 flex flex-row-reverse gap-3">
                <x-filament::button
                    color="{{ $confirmColor }}"
                    :icon="$confirmIcon"
                    @click="{{ $confirmClick }}"
                >
                    {{ $confirmLabel }}
                </x-filament::button>
                @if($secondaryLabel !== null)
                    <x-filament::button
                        color="{{ $secondaryColor }}"
                        :icon="$secondaryIcon"
                        @click="{{ $secondaryClick }}"
                    >
                        {{ $secondaryLabel }}
                    </x-filament::button>
                @endif
                <x-filament::button
                    color="gray"
                    @click="{{ $onCancel }}"
                >
                    {{ $cancelLabel }}
                </x-filament::button>
            </div>
        </div>
    </div>
</template>
