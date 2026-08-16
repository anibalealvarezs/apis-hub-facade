@php
    $tenant = \Filament\Facades\Filament::getTenant();
    $projectTz = $tenant?->timezone ?? 'UTC';
@endphp

<div x-data="projectClock({ timezone: '{{ $projectTz }}' })"
     x-init="init()"
     class="hidden md:flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-gray-100 dark:bg-gray-800 text-xs font-mono font-medium text-gray-600 dark:text-gray-300 border border-gray-200 dark:border-gray-700 shadow-sm ml-2">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3.5 h-3.5 text-primary-500">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
    </svg>
    <span x-text="formattedTime">--:--:--</span>
    <span class="text-[10px] text-gray-400 font-sans uppercase">({{ $projectTz }})</span>
</div>
