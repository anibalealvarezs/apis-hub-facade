<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    @php
        $project = \Filament\Facades\Filament::getTenant();
        $channels = \App\Services\Analytics\KpiFormBuilder::getActiveChannels();
        $user = auth()->user();
        $isAdmin = $user->role === 'admin' || $user->role === 'owner';
        
        $options = [];
        foreach ($channels as $channel => $name) {
            $allAssets = \App\Services\Analytics\KpiFormBuilder::getAssetOptionsForChannel($channel);
            if (!empty($allAssets)) {
                $options[$channel] = [
                    'name' => $name,
                    'assets' => $allAssets
                ];
            }
        }
    @endphp

    <div x-data="{
        state: JSON.parse(@js($getState() ?: '{}')),
        options: @js($options),
        searchQueries: {},

        init() {
            // If Livewire passed the JSON string down, parse it into a real object
            if (typeof this.state === 'string') {
                try {
                    this.state = JSON.parse(this.state);
                } catch (e) {
                    this.state = {};
                }
            }
            
            if (!this.state || Array.isArray(this.state)) {
                this.state = {};
            }
            
            for (const key in this.options) {
                this.searchQueries[key] = '';
                if (this.state[key] === undefined) {
                    this.state[key] = [];
                }
            }
            
            // Sync initial state to hidden input
            this.$refs.hiddenInput.value = JSON.stringify(this.state);
            this.$refs.hiddenInput.dispatchEvent(new Event('input'));
        },

        updateHidden() {
            const jsonStr = JSON.stringify(this.state);
            this.$refs.hiddenInput.value = jsonStr;
            this.$refs.hiddenInput.dispatchEvent(new Event('input'));
            $wire.set('{{ $getStatePath() }}', jsonStr, false); // defer sync until form submit
        },

        toggleAsset(channelKey, assetId) {
            const current = this.state[channelKey] || [];
            const idStr = String(assetId);
            const idx = current.indexOf(idStr);
            let next;
            
            if (idx > -1) {
                next = current.filter((id) => id !== idStr);
            } else {
                next = [...current, idStr];
            }
            
            this.state[channelKey] = next;
            this.updateHidden();
        },

        selectAll(channelKey) {
            const allIds = Object.keys(this.options[channelKey].assets).map(String);
            this.state[channelKey] = allIds;
            this.updateHidden();
        },

        clearAll(channelKey) {
            this.state[channelKey] = [];
            this.updateHidden();
        }
    }" class="w-full">
        
        <input type="hidden" x-ref="hiddenInput" wire:model="{{ $getStatePath() }}">
        
        <style>
            .custom-scrollbar::-webkit-scrollbar {
                width: 6px;
                height: 6px;
            }
            .custom-scrollbar::-webkit-scrollbar-track {
                background: transparent;
            }
            .custom-scrollbar::-webkit-scrollbar-thumb {
                background-color: rgba(156, 163, 175, 0.5);
                border-radius: 20px;
            }
            .dark .custom-scrollbar::-webkit-scrollbar-thumb {
                background-color: rgba(75, 85, 99, 0.5);
            }
        </style>

        <div class="min-w-0 flex overflow-x-auto gap-6 custom-scrollbar pb-4 items-stretch snap-x snap-mandatory" style="max-width: 100%; min-height: 700px; height: 700px;">
            <template x-for="(channelData, channelKey) in options" :key="channelKey">
                <div class="flex-none w-[calc(100%/3-1rem)] min-w-[280px] h-full min-h-0 flex flex-col snap-start">
                    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm overflow-hidden flex flex-col h-full min-h-0">
                        <div class="flex items-center justify-between px-6 py-3 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700 flex-shrink-0">
                            <div class="flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-gray-500 dark:text-gray-400">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5m.75-9l3-3 2.148 2.148A12.061 12.061 0 0116.5 7.605" />
                                </svg>
                                <span class="text-xs font-bold text-gray-800 dark:text-white uppercase tracking-wider" x-text="channelData.name"></span>
                            </div>
                            <span class="text-[10px] font-semibold text-gray-600 dark:text-gray-300 bg-gray-200 dark:bg-gray-700 px-2.5 py-1 rounded-full" x-text="Object.keys(channelData.assets).length + ' Assets'"></span>
                        </div>
                        
                        <div class="p-6 flex-1 flex flex-col gap-5 min-h-0">
                            <div class="gap-3 flex-1 flex flex-col min-h-0">
                                <div class="flex items-center justify-between">
                                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300">Assets</label>
                                    <div class="flex gap-3">
                                        <button type="button" @click.prevent="selectAll(channelKey)" class="text-[11px] font-semibold text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300 hover:underline">Select All</button>
                                        <button type="button" @click.prevent="clearAll(channelKey)" class="text-[11px] font-semibold text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:underline">Clear</button>
                                    </div>
                                </div>
                                <div class="relative flex-shrink-0">
                                    <div class="absolute inset-y-0 left-0 w-10 flex items-center justify-center pointer-events-none">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4 text-gray-400">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                                        </svg>
                                    </div>
                                    <input type="text" x-model="searchQueries[channelKey]" placeholder="Search assets..." class="bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5" style="padding-left: 2.5rem;">
                                </div>
                                <div class="flex-1 relative min-h-0 mt-2">
                                    <div class="absolute inset-0 flex flex-col gap-1 overflow-y-auto pr-1 custom-scrollbar">
                                        <template x-for="[assetId, assetName] in Object.entries(channelData.assets)" :key="assetId">
                                            <div x-show="searchQueries[channelKey] === '' || String(assetName).toLowerCase().includes(searchQueries[channelKey].toLowerCase())"
                                                 @click="toggleAsset(channelKey, assetId)"
                                                 class="flex gap-x-3 items-center px-3 py-2.5 text-sm text-gray-700 dark:text-gray-200 rounded-lg cursor-pointer transition-colors border border-transparent"
                                                 :class="(state && state[channelKey] || []).includes(String(assetId)) ? 'bg-primary-50 dark:bg-primary-900/30 border-primary-100 dark:border-primary-900/50' : 'hover:bg-gray-100 dark:hover:bg-white/5'">
                                                <div class="w-4 h-4 shrink-0 flex items-center justify-center rounded border transition-colors"
                                                     :class="(state && state[channelKey] || []).includes(String(assetId)) ? 'bg-primary-600 border-primary-600' : 'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800'">
                                                    <svg x-show="(state && state[channelKey] || []).includes(String(assetId))" class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                                                    </svg>
                                                </div>
                                                <span class="truncate font-medium" :class="(state && state[channelKey] || []).includes(String(assetId)) ? 'text-primary-800 dark:text-primary-200' : ''" x-text="assetName"></span>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>
</x-dynamic-component>
