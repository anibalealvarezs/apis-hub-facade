<x-filament-panels::page class="fi-resource-list-records-page fi-resource-asset-groups">
    <div class="flex flex-col gap-y-6">
        <x-filament-panels::resources.tabs />

        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::RESOURCE_PAGES_LIST_RECORDS_TABLE_BEFORE, scopes: $this->getRenderHookScopes()) }}

        {{ $this->table }}

        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::RESOURCE_PAGES_LIST_RECORDS_TABLE_AFTER, scopes: $this->getRenderHookScopes()) }}

        @if($previewGroup)
            <div wire:key="asset-group-preview-{{ $previewGroup->getKey() }}"
                 x-data
                 x-init="$nextTick(() => $refs.heading.focus())"
                 class="scroll-mt-20">
                <x-filament::section>
                    <x-slot name="heading">
                        <span x-ref="heading" tabindex="-1">{{ $previewGroup->name }}</span>
                    </x-slot>
                    <x-slot name="description">{{ __('Assigned assets by channel') }}</x-slot>
                    @if(\App\Filament\App\Resources\AssetGroupResource::canEdit($previewGroup))
                        <x-slot name="headerEnd">
                            <x-filament::button tag="a" color="gray" icon="heroicon-o-pencil-square"
                                :href="\App\Filament\App\Resources\AssetGroupResource::getUrl('edit', ['record' => $previewGroup])">
                                {{ __('Edit group') }}
                            </x-filament::button>
                        </x-slot>
                    @endif

                    <div class="grid grid-cols-1 items-start gap-4 sm:grid-cols-2 xl:grid-cols-3">
                        @forelse($previewChannels as $channel => $channelData)
                            <div wire:key="asset-group-channel-{{ $channel }}" class="min-w-0 rounded-xl border border-gray-200 dark:border-white/10">
                                <div class="flex items-center justify-between gap-2 border-b border-gray-200 px-4 py-3 dark:border-white/10">
                                    <h3 class="text-sm font-semibold">{{ $channelData['label'] }}</h3>
                                    <x-filament::badge color="gray">{{ $channelData['assets']->count() }}</x-filament::badge>
                                </div>
                                <ul class="divide-y divide-gray-100 dark:divide-white/5">
                                    @foreach($channelData['assets'] as $asset)
                                        <li class="break-words px-4 py-2 text-sm">
                                            <p class="font-medium">{{ $asset['name'] }}</p>
                                            @if($asset['name'] !== $asset['id'])
                                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $asset['id'] }}</p>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @empty
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('No assets assigned to this group.') }}</p>
                        @endforelse
                    </div>
                </x-filament::section>
            </div>
        @endif
    </div>
</x-filament-panels::page>
