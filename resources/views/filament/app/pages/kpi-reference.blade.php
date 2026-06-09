<x-filament-panels::page>
    <div class="space-y-6">
        <div class="text-sm text-gray-500 dark:text-gray-400 mb-2">
            {{ __('Predefined KPI templates are quick-start configurations that automatically resolve channel placeholders based on your project\'s synced accounts. Select one from the "Quick Start Template" dropdown when creating a new Custom KPI.') }}
        </div>

        <x-filament::grid default="1" md="2" class="gap-6">
            @foreach($this->getKpis() as $key => $kpi)
                <x-filament::section>
                    <x-slot name="heading">
                        <div class="flex items-center gap-2">
                            <x-filament::icon icon="heroicon-o-chart-bar" class="h-5 w-5 text-primary-500" />
                            {{ $kpi['name'] }}
                        </div>
                    </x-slot>

                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                        {{ $kpi['description'] }}
                    </p>

                    <div class="space-y-3">
                        <div>
                            <span class="text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wide">{{ __('Type') }}</span>
                            <div class="mt-1">
                                <x-filament::badge color="primary">
                                    {{ $this->getCalculationTypeLabel($kpi['calculation_type']) }}
                                </x-filament::badge>
                            </div>
                        </div>

                        <div>
                            <span class="text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wide">{{ __('How it works') }}</span>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-300 leading-relaxed">
                                {{ $this->getCalculationTypeDescription($kpi['calculation_type']) }}
                            </p>
                        </div>

                        <div>
                            <span class="text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wide">{{ __('Required Channel Tags') }}</span>
                            <div class="mt-1 flex flex-wrap gap-1.5">
                                @foreach($kpi['required_tags'] as $tag)
                                    <x-filament::badge color="gray">
                                        {{ $tag }}
                                    </x-filament::badge>
                                @endforeach
                            </div>
                        </div>

                        <div>
                            <span class="text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wide">{{ __('Template AST Structure') }}</span>
                            <div class="mt-1">
                                <pre style="background: #1f2937; color: #10b981; padding: 0.75rem; border-radius: 0.5rem; overflow-x: auto; font-size: 0.75rem; line-height: 1.5; max-height: 240px;">{{ json_encode($kpi['template']['ast'], JSON_PRETTY_PRINT) }}</pre>
                            </div>
                        </div>
                    </div>
                </x-filament::section>
            @endforeach
        </x-filament::grid>

        <x-filament::section icon="heroicon-o-information-circle" icon-color="warning">
            <x-slot name="heading">
                <span class="text-warning-600 dark:text-warning-400">{{ __('Channel Placeholders') }}</span>
            </x-slot>
            <p class="text-sm text-gray-600 dark:text-gray-300 leading-relaxed">
                {{ __('Templates use placeholders like <code>__SPENDABLE_CHANNEL_1__</code> that are automatically resolved to your actual synced channels based on the channel\'s capability tags. For example, a channel tagged as <code>spendable</code> will be used to fill <code>__SPENDABLE_CHANNEL_*__</code> placeholders. Only channels you have synced and enabled will be considered.') }}
            </p>
        </x-filament::section>
    </div>
</x-filament-panels::page>
