<x-filament-panels::page>
    <div class="space-y-6">
        <div class="prose prose-sm max-w-none text-gray-500 dark:text-gray-400 mb-2">
            {{ __('The predefined KPI templates let you quickly analyze your marketing data without needing a statistics degree. Each template focuses on a practical business question — pick one that matches what you want to learn about your channels.') }}
        </div>

        <x-filament::grid default="1" md="2" class="gap-6">
            @foreach($this->getKpis() as $key => $kpi)
                @php $guidance = $this->getGuidance($key); @endphp

                <x-filament::section>
                    <x-slot name="heading">
                        <div class="flex items-center gap-2">
                            <x-filament::icon icon="heroicon-o-chart-bar" class="h-5 w-5 text-primary-500" />
                            {{ $kpi['name'] }}
                        </div>
                    </x-slot>

                    <x-slot name="description">
                        <x-filament::badge color="primary">
                            {{ $guidance['type_label'] }}
                        </x-filament::badge>
                    </x-slot>

                    <div class="space-y-5">
                        <div>
                            <span class="text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wide">{{ __('What it does') }}</span>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-300 leading-relaxed">
                                {{ $guidance['explanation'] }}
                            </p>
                        </div>

                        <div>
                            <span class="text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wide">{{ __('Golden use case') }}</span>
                            <div class="mt-1 text-sm text-gray-600 dark:text-gray-300 leading-relaxed">
                                <p>{{ $guidance['use_case'] }}</p>
                            </div>
                        </div>

                        <div>
                            <span class="text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wide">{{ __('Reading the result') }}</span>
                            <div class="mt-1 text-sm text-gray-600 dark:text-gray-300 leading-relaxed">
                                <p>{{ $guidance['interpretation'] }}</p>
                            </div>
                        </div>
                    </div>
                </x-filament::section>
            @endforeach
        </x-filament::grid>
    </div>
</x-filament-panels::page>
