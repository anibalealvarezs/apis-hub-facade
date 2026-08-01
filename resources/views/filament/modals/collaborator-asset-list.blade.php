@php
    $entries = $sharedGroups->map(function ($group) {
        $itemsByChannel = $group->getActiveItemsAttribute()->groupBy('channel');

        return [
            'group' => $group,
            'itemsByChannel' => $itemsByChannel,
            'total' => $itemsByChannel->reduce(fn ($carry, $items) => $carry + $items->count(), 0),
        ];
    });

    $totalAssets = $entries->sum('total');
@endphp

<div class="space-y-4">
    <div class="flex items-start gap-3 rounded-lg bg-gray-50 dark:bg-white/5 px-4 py-3 ring-1 ring-gray-950/5 dark:ring-white/10">
        <x-filament::icon icon="heroicon-m-information-circle" class="w-4 h-4 mt-0.5 text-gray-400 dark:text-gray-500 shrink-0" />
        <div class="text-sm text-gray-600 dark:text-gray-300">
            <span>{{ __('This collaborator can access :count enabled assets.', ['count' => $totalAssets]) }}</span>
            @if ($totalAssets === 0)
                <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">
                    {{ __('No enabled assets are currently available in the shared groups.') }}
                </p>
            @endif
        </div>
    </div>

    @foreach ($entries as $entry)
        <div class="rounded-xl overflow-hidden ring-1 ring-gray-950/5 dark:ring-white/10">
            <div class="flex items-center justify-between gap-2 px-4 py-2.5 bg-gray-50 dark:bg-white/5 border-b border-gray-200 dark:border-gray-700">
                <span class="text-sm font-semibold text-gray-950 dark:text-white">{{ $entry['group']->name }}</span>
                <span class="text-xs text-gray-500 dark:text-gray-400">{{ $entry['total'] }}</span>
            </div>
            <div class="divide-y divide-gray-100 dark:divide-white/5">
                @foreach ($entry['itemsByChannel'] as $channel => $items)
                    <div class="flex items-baseline justify-between gap-4 px-4 py-2.5">
                        <span class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400 shrink-0">{{ $channel }}</span>
                        <span class="text-sm font-mono text-gray-700 dark:text-gray-300 text-right break-all">{{ $items->pluck('asset_id')->join(', ') }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach
</div>
