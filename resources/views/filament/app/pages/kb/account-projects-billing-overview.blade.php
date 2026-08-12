<x-filament-panels::page>
    <div class="space-y-6">
        <div class="prose prose-sm max-w-none text-gray-500 dark:text-gray-400">
            {{ $intro }}
        </div>

        @if (count($links) > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach ($links as $link)
                    <a href="{{ $link['url'] }}" class="group block rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800/50 p-5 transition hover:border-primary-500 hover:shadow-sm">
                        <div class="flex items-start gap-3">
                            <x-filament::icon icon="{{ $link['icon'] }}" class="h-6 w-6 shrink-0 text-primary-500" />
                            <div>
                                <div class="font-medium text-gray-900 dark:text-white group-hover:text-primary-500 transition">{{ $link['title'] }}</div>
                                <div class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $link['description'] }}</div>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        @else
            <x-filament::section>
                <div class="flex items-center gap-3">
                    <x-filament::icon icon="heroicon-o-document-text" class="h-5 w-5 text-gray-400" />
                    <div>
                        <div class="font-medium text-gray-900 dark:text-white">{{ __('No articles yet') }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ __('This section will be filled with reference articles soon.') }}</div>
                    </div>
                </div>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
