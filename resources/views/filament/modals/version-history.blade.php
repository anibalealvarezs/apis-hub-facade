<div class="space-y-3 p-2">
    @if($versions->isEmpty())
        <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-8">{{ __('No version history available yet.') }}</p>
    @else
        <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
                        <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">#</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">{{ __('Name') }}</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">{{ __('Label') }}</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">{{ __('Changed by') }}</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">{{ __('Summary') }}</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">{{ __('Date') }}</th>
                        <th class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                    @foreach($versions as $version)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                            <td class="px-3 py-2.5 text-xs font-mono text-gray-600 dark:text-gray-400">v{{ $version->version_number }}</td>
                            <td class="px-3 py-2.5 text-gray-900 dark:text-gray-100 font-medium">{{ $version->name }}</td>
                            <td class="px-3 py-2.5 text-gray-600 dark:text-gray-400 max-w-[120px] truncate">{{ $version->label ?? '-' }}</td>
                            <td class="px-3 py-2.5 text-gray-600 dark:text-gray-400">{{ $version->user?->name ?? __('System') }}</td>
                            <td class="px-3 py-2.5 text-gray-600 dark:text-gray-400 max-w-xs truncate">{{ $version->change_summary }}</td>
                            <td class="px-3 py-2.5 text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ $version->created_at->format('M j, Y H:i') }}</td>
                            <td class="px-3 py-2.5 text-right whitespace-nowrap">
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-1 text-xs font-medium version-restore-btn"
                                    wire:click="restoreVersion({{ $version->id }})"
                                    wire:confirm="{{ __('Restore to version #:version? Save the current state as a version first or it will be lost forever.', ['version' => $version->version_number]) }}"
                                >
                                    {{ __('Restore') }}
                                </button>
                                <span class="mx-1 text-gray-300 dark:text-gray-600">|</span>
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-1 text-xs font-medium version-duplicate-btn"
                                    wire:click="duplicateFromVersion({{ $version->id }})"
                                    wire:confirm="{{ __('Duplicate dashboard from version #:version?', ['version' => $version->version_number]) }}"
                                >
                                    {{ __('Duplicate') }}
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
