<div class="space-y-3">
    <div>
        <textarea
            wire:model="newMessage"
            placeholder="Type your reply..."
            rows="3"
            class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm"
        ></textarea>
        @error('newMessage') <p class="text-sm text-danger-600 mt-1">{{ $message }}</p> @enderror
    </div>

    @if ($showStatusPrompt && $ticket->status !== 'waiting_on_user' && $ticket->status !== 'closed')
    <label class="flex items-center gap-2 text-sm">
        <input
            type="checkbox"
            wire:model="changeStatusToWaitingOnUser"
            class="rounded border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-primary-600 shadow-sm focus:ring-primary-500"
        >
        <span>Change status to "Waiting on User"</span>
    </label>
    @endif

    <x-filament::button wire:click="reply">
        Send Reply
    </x-filament::button>
</div>
