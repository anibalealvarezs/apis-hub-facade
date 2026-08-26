<x-filament-panels::page>
    <div
        x-data="kpiBrowser({
            kpis: @js($this->getKpisWithGuidance()),
            categoryGroups: @js($this->getCategoryGroups())
        })"
        class="space-y-6"
    >
        <div class="prose prose-sm max-w-none text-gray-500 dark:text-gray-400 mb-2">
            {{ __('The predefined KPI templates let you quickly analyze your marketing data without needing a statistics degree. Each template focuses on a practical business question — pick one that matches what you want to learn about your channels.') }}
        </div>

        <div class="flex flex-col gap-4">
            <div class="relative">
                <x-filament::input.wrapper>
                    <x-filament::input
                        type="search"
                        x-model="search"
                        placeholder="{{ __('Search KPIs by name, type, or description...') }}"
                    />
                </x-filament::input.wrapper>
            </div>

            <template x-for="(group, groupName) in categoryGroups" :key="groupName">
                <div>
                    <div class="bd-text-2xs font-bold uppercase tracking-widest text-gray-400 dark:text-gray-500 mb-1.5 ml-0.5" x-text="groupName"></div>
                    <div class="flex flex-wrap gap-2">
                        <template x-for="(catLabel, catKey) in group" :key="catKey">
                            <button
                                type="button"
                                x-on:click="toggleCategory(catKey)"
                                x-bind:class="selectedCategories.includes(catKey)
                                    ? 'bg-primary-500 text-white ring-2 ring-primary-300 dark:ring-primary-600 shadow-md'
                                    : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 shadow-sm'"
                                class="inline-flex items-center justify-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-medium transition-all duration-150 cursor-pointer"
                            >
                                <span x-text="catLabel"></span>
                            </button>
                        </template>
                    </div>
                </div>
            </template>
        </div>

        <div>
            <p class="text-sm text-gray-500 mb-4">
                <span x-text="filteredKpis.length"></span>
                {{ __('of') }}
                <span x-text="kpis.length"></span>
                {{ __('KPIs shown') }}
            </p>
        </div>

        <x-filament::grid default="1" md="2" class="gap-6">
            <template x-for="kpi in filteredKpis" :key="kpi.key">
                <x-filament::section x-bind:id="kpi.key">
                    <x-slot name="heading">
                        <div class="flex items-center gap-2 group" x-data="copyLink()">
                            <x-filament::icon icon="heroicon-o-chart-bar" class="h-5 w-5 text-primary-500" />
                            <a x-bind:href="'#' + kpi.key"
                               class="flex items-center gap-2 hover:underline text-inherit"
                               @click.prevent="
                                   copy(kpi.key);
                               ">
                                <span x-text="kpi.name"></span>
                                <x-filament::icon icon="heroicon-o-link" class="h-4 w-4 text-gray-400 opacity-0 group-hover:opacity-100 transition-opacity" x-show="!copied" />
                                <x-filament::icon icon="heroicon-o-check" class="h-4 w-4 text-success-500" x-show="copied" x-cloak />
                            </a>
                        </div>
                    </x-slot>

                    <x-slot name="description">
                        <div class="flex items-center gap-2 mt-1">
                            <x-filament::badge color="primary">
                                <span x-text="kpi.type_label"></span>
                            </x-filament::badge>
                            
                            <template x-if="kpi.status === 'unavailable'">
                                <x-filament::badge color="danger" icon="heroicon-o-exclamation-triangle">
                                    {{ __('Not Available') }}
                                </x-filament::badge>
                            </template>
                        </div>
                    </x-slot>

                    <div class="space-y-5">
                        <div>
                            <span class="text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wide">{{ __('What it does') }}</span>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-300 leading-relaxed" x-text="kpi.explanation"></p>
                        </div>

                        <div>
                            <span class="text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wide">{{ __('Golden use case') }}</span>
                            <div class="mt-1 text-sm text-gray-600 dark:text-gray-300 leading-relaxed">
                                <p x-text="kpi.use_case"></p>
                            </div>
                        </div>
                        <a
                            :href="'/app/manage-kpis/create?type=' + kpi.type"
                            class="inline-flex items-center gap-1 text-xs font-semibold text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300"
                        >
                            <span>{{ __('Create this KPI') }}</span>
                            <x-heroicon-m-arrow-right class="w-3.5 h-3.5" />
                        </a>
                    </div>
                </x-filament::section>
            </template>
        </x-filament::grid>

        <div x-show="filteredKpis.length === 0" class="text-center py-12 bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800">
            <x-filament::icon icon="heroicon-o-magnifying-glass" class="h-12 w-12 mx-auto text-gray-300 dark:text-gray-600" />
            <p class="mt-4 text-lg font-semibold text-gray-500 dark:text-gray-400">{{ __('No KPIs match your filters') }}</p>
            <p class="mt-1 text-sm text-gray-400 dark:text-gray-500">{{ __('Try adjusting your search or selecting different categories.') }}</p>
        </div>
    </div>
</x-filament-panels::page>
