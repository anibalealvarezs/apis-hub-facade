<div class="space-y-3 p-2">
    @if($versions->isEmpty())
        <p class="text-sm text-gray-500 text-center py-8">{{ __('No version history available yet.') }}</p>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-700">
                        <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">#</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">{{ __('Name') }}</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">{{ __('Changed by') }}</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">{{ __('Summary') }}</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">{{ __('Date') }}</th>
                        <th class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($versions as $version)
                        <tr class="border-b border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800/50">
                            <td class="px-3 py-2.5 text-xs font-mono text-gray-600 dark:text-gray-400">v{{ $version->version_number }}</td>
                            <td class="px-3 py-2.5">{{ $version->name }}</td>
                            <td class="px-3 py-2.5 text-gray-600 dark:text-gray-400">{{ $version->user?->name ?? __('System') }}</td>
                            <td class="px-3 py-2.5 text-gray-600 dark:text-gray-400 max-w-xs truncate">{{ $version->change_summary }}</td>
                            <td class="px-3 py-2.5 text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ $version->created_at->format('M j, Y H:i') }}</td>
                            <td class="px-3 py-2.5 text-right">
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-1 text-xs font-medium version-restore-btn"
                                    wire:click="restoreVersion({{ $version->id }})"
                                    wire:confirm="{{ __('Restore to version #:version? This will create a snapshot of the current state first.', ['version' => $version->version_number]) }}"
                                >
                                    {{ __('Restore') }}
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

<style>
.version-restore-btn { color: #2563eb; }
.version-restore-btn:hover { color: #3b82f6; }
.dark .version-restore-btn { color: #60a5fa; }
.dark .version-restore-btn:hover { color: #93c5fd; }
</style>
