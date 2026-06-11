@php
    $tenant = filament()->getTenant();
    if (!$tenant) return;

    $tierLabel = $tenant?->billingProfile?->tier?->getLabel() ?? 'Unknown';
    $tierColors = [
        'Free' => 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300 ring-gray-500/10 dark:ring-gray-400/20',
        'Pro' => 'bg-primary-50 text-primary-700 dark:bg-primary-900/30 dark:text-primary-300 ring-primary-600/10 dark:ring-primary-500/20',
        'Ultra' => 'bg-purple-50 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300 ring-purple-600/10 dark:ring-purple-500/20',
        'Founder' => 'bg-warning-50 text-warning-700 dark:bg-warning-900/30 dark:text-warning-300 ring-warning-600/10 dark:ring-warning-500/20',
        'Enterprise' => 'bg-danger-50 text-danger-700 dark:bg-danger-900/30 dark:text-danger-300 ring-danger-600/10 dark:ring-danger-500/20',
        'Suspended' => 'bg-danger-100 text-danger-800 dark:bg-danger-900/50 dark:text-danger-300 ring-danger-600/20 dark:ring-danger-500/30',
        'Unknown' => 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300 ring-gray-500/10 dark:ring-gray-400/20',
    ];
    $tierColorClass = $tierColors[$tierLabel] ?? $tierColors['Unknown'];

    $tierIcons = [
        'Free' => 'heroicon-m-paper-airplane',
        'Pro' => 'heroicon-m-rocket-launch',
        'Ultra' => 'heroicon-m-bolt',
        'Founder' => 'heroicon-m-building-office-2',
        'Enterprise' => 'heroicon-m-building-office-2',
        'Suspended' => 'heroicon-m-pause-circle',
        'Unknown' => 'heroicon-m-question-mark-circle',
    ];
    $tierIcon = $tierIcons[$tierLabel] ?? $tierIcons['Unknown'];
@endphp

<div class="flex justify-center mt-1">
    <span
        class="inline-flex justify-center items-center gap-1.5 px-2.5 py-1 rounded-md text-[10px] uppercase tracking-widest font-bold {{ $tierColorClass }} shadow-sm ring-1 ring-inset"
        :class="{ 'w-full': $store.sidebar.isOpen, 'w-auto px-1.5': !$store.sidebar.isOpen }"
        title="{{ $tierLabel }}">
        <x-filament::icon :icon="$tierIcon" class="w-4 h-4"/>
        <span x-show="$store.sidebar.isOpen" x-transition>{{ $tierLabel }}</span>
    </span>
</div>
