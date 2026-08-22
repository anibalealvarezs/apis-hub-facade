<div class="flex items-center gap-4 mb-4">
    {{-- Search Input --}}
    <div class="relative w-full max-w-sm">
        <div class="absolute inset-y-0 left-0 rtl:right-0 rtl:left-auto w-10 flex items-center justify-center pointer-events-none text-gray-400 dark:text-gray-500">
            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
            </svg>
        </div>
        <input
            type="text"
            x-model="assetFilter"
            class="bg-white dark:bg-white/5 border border-gray-300 dark:border-gray-600 text-gray-950 dark:text-white text-sm h-[42px] rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full pr-3 placeholder-gray-400 dark:placeholder-gray-500 transition-colors"
            style="padding-left: 2.75rem !important;"
            placeholder="{{ __('Live filter assets by name or ID...') }}"
        >
    </div>

    {{-- Unified Status Filter (w-52) --}}
    <div class="w-52 flex-shrink-0">
        <x-ui.asset-selector
            model="assetStatusFilter"
            options="statusOptions"
            placeholder="{{ __('All Statuses') }}"
            class="w-full !min-w-0"
            size="sm"
        />
    </div>

    {{-- Asset Group Filter (w-48) --}}
    <div class="w-48 flex-shrink-0">
        <x-ui.asset-selector
            model="assetGroupFilter"
            options="groupOptions"
            placeholder="{{ __('All Groups') }}"
            class="w-full !min-w-0"
            size="sm"
        />
    </div>
</div>
