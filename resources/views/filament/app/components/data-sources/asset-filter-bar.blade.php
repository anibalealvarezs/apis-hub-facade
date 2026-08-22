<div class="flex flex-wrap items-center gap-3 mb-4">
    {{-- Search Input --}}
    <div class="relative w-full sm:w-80">
        <div class="relative w-full">
            <div class="absolute inset-y-0 left-0 rtl:right-0 rtl:left-auto w-10 flex items-center justify-center pointer-events-none text-gray-400 dark:text-gray-500">
                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                </svg>
            </div>
            <input
                type="text"
                x-model="assetFilter"
                class="bg-white dark:bg-white/5 border border-gray-300 dark:border-gray-600 text-gray-950 dark:text-white text-sm h-[42px] rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full pl-10 pr-4 placeholder-gray-400 dark:placeholder-gray-500 transition-colors"
                placeholder="{{ __('Live filter assets by name or ID...') }}"
            >
        </div>
    </div>

    {{-- Status Filter --}}
    <div class="w-full sm:w-auto">
        <x-ui.asset-selector
            model="assetStatusFilter"
            options="statusOptions"
            placeholder="{{ __('All Statuses') }}"
            size="sm"
        />
    </div>

    {{-- Billing Grace / Lock Status Filter --}}
    <div class="w-full sm:w-auto">
        <x-ui.asset-selector
            model="assetGraceFilter"
            options="graceOptions"
            placeholder="{{ __('Asset Billing Status') }}"
            size="sm"
        />
    </div>

    {{-- Asset Group Filter --}}
    <div class="w-full sm:w-auto">
        <x-ui.asset-selector
            model="assetGroupFilter"
            options="groupOptions"
            placeholder="{{ __('All Groups') }}"
            size="sm"
        />
    </div>
</div>
