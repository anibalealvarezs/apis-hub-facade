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

    $iconClass = match ($color) {
        'primary' => 'confirm-modal-icon-primary',
        'warning' => 'confirm-modal-icon-warning',
        'success' => 'confirm-modal-icon-success',
        'gray' => 'confirm-modal-icon-gray',
        default => 'confirm-modal-icon-danger',
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
        class="confirm-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="confirm-modal-title"
    >
        <div class="confirm-modal-backdrop" @click="{{ $onCancel }}"></div>

        <div
            class="confirm-modal-panel"
            x-show="{{ $open }}"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
        >
            <div class="confirm-modal-header">
                <div class="confirm-modal-icon {{ $iconClass }}">
                    <x-dynamic-component :component="$icon" />
                </div>
                <div class="confirm-modal-body">
                    <h3 class="confirm-modal-title" id="confirm-modal-title">
                        {{ $title }}
                    </h3>
                    <div class="confirm-modal-content">
                        {{ $slot }}
                    </div>
                </div>
            </div>
            <div class="confirm-modal-footer">
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
