<div class="flex flex-wrap items-center gap-3 mb-4">
    {{-- Search Input --}}
    <div class="relative w-full sm:w-80">
        <div class="fi-input-wrp flex items-center rounded-lg shadow-sm ring-1 transition duration-75 bg-white dark:bg-white/5 ring-gray-950/10 dark:ring-white/20 focus-within:ring-2 focus-within:ring-primary-600 dark:focus-within:ring-primary-500">
            <div class="items-center gap-x-3 ps-3 flex pointer-events-none text-gray-400 dark:text-gray-500">
                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                </svg>
            </div>
            <input
                type="text"
                x-model="assetFilter"
                class="fi-input block w-full border-none bg-transparent/0 py-1.5 text-base text-gray-950 transition duration-75 placeholder:text-gray-400 focus:ring-0 disabled:text-gray-500 dark:text-white dark:placeholder:text-gray-500 dark:disabled:text-gray-400 sm:text-sm sm:leading-6 ps-2.5"
                placeholder="{{ __('Live filter assets by name or ID...') }}"
            >
        </div>
    </div>

    {{-- Status Filter --}}
    <div class="w-full sm:w-44">
        <div class="fi-input-wrp flex items-center rounded-lg shadow-sm ring-1 transition duration-75 bg-white dark:bg-white/5 ring-gray-950/10 dark:ring-white/20 focus-within:ring-2 focus-within:ring-primary-600 dark:focus-within:ring-primary-500">
            <select
                x-model="assetStatusFilter"
                class="fi-select-input block w-full border-none bg-transparent py-1.5 pe-8 text-base text-gray-950 transition duration-75 focus:ring-0 disabled:text-gray-500 dark:text-white dark:disabled:text-gray-400 sm:text-sm sm:leading-6 ps-3 [&_optgroup]:bg-white [&_optgroup]:dark:bg-gray-900 [&_option]:bg-white [&_option]:dark:bg-gray-900 cursor-pointer"
            >
                <option value="all">{{ __('All Statuses') }}</option>
                <option value="enabled">{{ __('Enabled Only') }}</option>
                <option value="disabled">{{ __('Disabled Only') }}</option>
            </select>
        </div>
    </div>

    {{-- Billing Grace / Lock Status Filter --}}
    <div class="w-full sm:w-52">
        <div class="fi-input-wrp flex items-center rounded-lg shadow-sm ring-1 transition duration-75 bg-white dark:bg-white/5 ring-gray-950/10 dark:ring-white/20 focus-within:ring-2 focus-within:ring-primary-600 dark:focus-within:ring-primary-500">
            <select
                x-model="assetGraceFilter"
                class="fi-select-input block w-full border-none bg-transparent py-1.5 pe-8 text-base text-gray-950 transition duration-75 focus:ring-0 disabled:text-gray-500 dark:text-white dark:disabled:text-gray-400 sm:text-sm sm:leading-6 ps-3 [&_optgroup]:bg-white [&_optgroup]:dark:bg-gray-900 [&_option]:bg-white [&_option]:dark:bg-gray-900 cursor-pointer"
            >
                <option value="" disabled selected>{{ __('Asset Billing Status') }}</option>
                <option value="all">{{ __('All States') }}</option>
                <option value="grace">{{ __('In Grace Period') }}</option>
                <option value="locked">{{ __('Asset Locked') }}</option>
                <option value="none">{{ __('No Grace Period status') }}</option>
            </select>
        </div>
    </div>

    {{-- Asset Group Filter --}}
    <div class="w-full sm:w-48">
        <div class="fi-input-wrp flex items-center rounded-lg shadow-sm ring-1 transition duration-75 bg-white dark:bg-white/5 ring-gray-950/10 dark:ring-white/20 focus-within:ring-2 focus-within:ring-primary-600 dark:focus-within:ring-primary-500">
            <select
                x-model="assetGroupFilter"
                class="fi-select-input block w-full border-none bg-transparent py-1.5 pe-8 text-base text-gray-950 transition duration-75 focus:ring-0 disabled:text-gray-500 dark:text-white dark:disabled:text-gray-400 sm:text-sm sm:leading-6 ps-3 [&_optgroup]:bg-white [&_optgroup]:dark:bg-gray-900 [&_option]:bg-white [&_option]:dark:bg-gray-900 cursor-pointer"
            >
                <option value="all">{{ __('All Groups') }}</option>
                <template x-for="group in assetGroupsData.groups" :key="group.id">
                    <option :value="group.name" x-text="group.name"></option>
                </template>
            </select>
        </div>
    </div>
</div>
