<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    @php
        $options = [];
        foreach (\App\Services\Analytics\KpiFormBuilder::getActiveChannels() as $channel => $name) {
            $assets = \App\Services\Analytics\KpiFormBuilder::getAllAssetsForChannel($channel);
            if ($assets) {
                $options[$channel] = ['name' => $name, 'assets' => $assets];
            }
        }
    @endphp

    <div x-data="assetSelector({
        options: @js($options),
        existingSelections: @js($this->assetSelections ?? '{}')
    })">
        @if($options)
            <div class="asg-container">
                <template x-for="(channelData, channelKey) in options" :key="channelKey">
                    <section class="min-w-0 rounded-xl border border-gray-200 bg-white dark:border-white/10 dark:bg-white/5"
                             :aria-label="channelData.name">
                        <header class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-200 px-4 py-3 dark:border-white/10">
                            <h3 class="text-sm font-semibold text-gray-950 dark:text-white" x-text="channelData.name"></h3>
                            <span class="flex flex-wrap gap-2 text-xs font-medium text-gray-600 dark:text-gray-300">
                                <span><strong x-text="(state[channelKey] || []).length"></strong> {{ __('Selected') }}</span>
                                <span aria-hidden="true">·</span>
                                <span><strong x-text="Object.values(channelData.assets).filter(asset => asset.enabled).length"></strong> {{ __('Available') }}</span>
                            </span>
                        </header>

                        <div class="space-y-3 p-4">
                            <div class="flex flex-wrap items-center gap-3">
                                <input type="search" x-model="searchQueries[channelKey]"
                                       :aria-label="@js(__('Search assets')) + ' — ' + channelData.name"
                                       placeholder="{{ __('Search assets...') }}"
                                       class="min-w-0 flex-1 rounded-lg border-gray-300 bg-gray-50 text-sm text-gray-950 dark:border-white/10 dark:bg-white/5 dark:text-white">
                                <div class="flex gap-3 text-xs font-semibold">
                                    <button type="button" @click="selectAll(channelKey)" class="text-primary-600 hover:underline dark:text-primary-400">{{ __('Select All') }}</button>
                                    <button type="button" @click="clearAll(channelKey)" class="text-gray-600 hover:underline dark:text-gray-300">{{ __('Clear') }}</button>
                                </div>
                            </div>

                            <div class="asg-asset-list custom-scrollbar space-y-1">
                                <template x-for="[assetId, asset] in Object.entries(channelData.assets)" :key="assetId">
                                    <label x-show="(asset.enabled || (state[channelKey] || []).includes(String(assetId))) && String(asset.name).toLowerCase().includes((searchQueries[channelKey] || '').toLowerCase())"
                                           class="flex cursor-pointer items-start gap-3 rounded-lg px-2 py-2 text-sm text-gray-800 dark:text-gray-200"
                                           :class="(state[channelKey] || []).includes(String(assetId)) ? 'bg-primary-50 dark:bg-primary-500/10' : 'hover:bg-gray-50 dark:hover:bg-white/5'">
                                        <input type="checkbox" :checked="(state[channelKey] || []).includes(String(assetId))"
                                               @change="toggleAsset(channelKey, assetId)"
                                               class="mt-0.5 shrink-0 rounded border-gray-400 text-primary-600 focus:ring-primary-500">
                                        <span class="min-w-0">
                                            <span class="asg-asset-name font-medium" x-text="asset.name"></span>
                                            <span x-show="!asset.enabled" class="text-xs font-medium text-danger-600 dark:text-danger-400">{{ __('Disabled') }}</span>
                                        </span>
                                    </label>
                                </template>
                            </div>
                        </div>
                    </section>
                </template>
            </div>
        @else
            <p class="text-sm text-gray-600 dark:text-gray-300">{{ __('No assets available.') }}</p>
        @endif
    </div>
</x-dynamic-component>
