<div class="space-y-4">
    <div class="flex items-center justify-between gap-4">
        <div>
            <h3 class="text-lg font-medium">Share Codes</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">Use these codes so that any user can join this project from the registration form. Each code can only be used once.</p>
        </div>
        <x-filament::button wire:click="$set('showForm', true)" icon="heroicon-o-link">
            Generate Share Code
        </x-filament::button>
    </div>

    @if(count($codes) > 0)
        <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
            <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Code</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Target Email</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Expires</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Created</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($codes as $code)
                        <tr class="bg-white dark:bg-gray-900 hover:bg-gray-100 dark:hover:bg-gray-800">
                            <td class="px-4 py-3 font-mono text-sm text-gray-900 dark:text-gray-100">
                                <span x-data="{ copied: false }" class="inline-flex items-center gap-2">
                                    <span>{{ $code['token'] }}</span>
                                    <button
                                        type="button"
                                        x-on:click="navigator.clipboard.writeText('{{ $code['token'] }}'); copied = true; setTimeout(() => copied = false, 2000)"
                                        class="text-primary-600 hover:text-primary-500"
                                        title="Copy code"
                                    >
                                        <span x-show="!copied">
                                            <x-filament::icon name="heroicon-o-clipboard" class="w-4 h-4" />
                                        </span>
                                        <span x-show="copied" x-cloak>
                                            <x-filament::icon name="heroicon-o-check" class="w-4 h-4 text-success-600" />
                                        </span>
                                    </button>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">{{ $code['email'] ?? 'Anyone' }}</td>
                            <td class="px-4 py-3 text-sm">
                                @php
                                    $isUsed = !is_null($code['used_at']);
                                    $isExpired = is_null($code['used_at']) && !is_null($code['expires_at']) && \Carbon\Carbon::parse($code['expires_at'])->isPast();
                                @endphp
                                @if($isUsed)
                                    <x-filament::badge color="gray">Used</x-filament::badge>
                                @elseif($isExpired)
                                    <x-filament::badge color="danger">Expired</x-filament::badge>
                                @else
                                    <x-filament::badge color="success">Available</x-filament::badge>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">{{ $code['expires_at'] ? \Carbon\Carbon::parse($code['expires_at'])->format('Y-m-d H:i') : 'Never' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">{{ \Carbon\Carbon::parse($code['created_at'])->format('Y-m-d H:i') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="text-center py-8 text-gray-500 dark:text-gray-400">
            No share codes generated yet.
        </div>
    @endif

    @if($showForm)
        <div
            x-data="{ open: true }"
            x-show="open"
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center"
        >
            <div class="fixed inset-0 bg-gray-500/50 dark:bg-gray-900/80" x-on:click="$wire.$set('showForm', false)"></div>
            <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl p-6 w-full max-w-md mx-4 border border-gray-200 dark:border-gray-700">
                <h4 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Generate Share Code</h4>

                <form wire:submit="generate">
                    <div class="mb-4">
                        <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Target Email (optional)</label>
                        <input
                            type="email"
                            id="email"
                            wire:model="email"
                            placeholder="collaborator@example.com"
                            class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 px-3 py-2 text-sm shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
                        />
                        @error('email')
                            <p class="text-sm text-danger-600 mt-1">{{ $message }}</p>
                        @enderror
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Leave empty to allow anyone to use this code.</p>
                    </div>

                    <div class="flex justify-end gap-3">
                        <x-filament::button color="gray" wire:click="$set('showForm', false)" type="button">
                            Cancel
                        </x-filament::button>
                        <x-filament::button type="submit" icon="heroicon-o-link">
                            Generate
                        </x-filament::button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
