<div class="space-y-4">
    <div class="flex items-center justify-between gap-4">
        <div>
            <h3 class="text-lg font-medium">Share Codes</h3>
            <p class="text-sm text-gray-500">Use these codes so that any user can join this project from the registration form. Each code can only be used once.</p>
        </div>
        <x-filament::button wire:click="generate" icon="heroicon-o-link">
            Generate Share Code
        </x-filament::button>
    </div>

    @if(count($codes) > 0)
        <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Target Email</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Expires</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($codes as $code)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                            <td class="px-4 py-3 font-mono text-sm">
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
                            <td class="px-4 py-3 text-sm">{{ $code['email'] ?? 'Anyone' }}</td>
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
                            <td class="px-4 py-3 text-sm">{{ $code['expires_at'] ? \Carbon\Carbon::parse($code['expires_at'])->format('Y-m-d H:i') : 'Never' }}</td>
                            <td class="px-4 py-3 text-sm">{{ \Carbon\Carbon::parse($code['created_at'])->format('Y-m-d H:i') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="text-center py-8 text-gray-500">
            No share codes generated yet.
        </div>
    @endif
</div>
