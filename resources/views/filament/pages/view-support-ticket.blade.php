@php
    $messages = $this->getMessages();
@endphp

<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Ticket Info --}}
        <x-filament::section>
            <x-slot name="heading">
                Ticket #{{ $record->id }} — {{ ucfirst(str_replace('_', ' ', $record->type)) }}
            </x-slot>

            <dl class="grid grid-cols-2 gap-4">
                <div>
                    <dt class="text-sm text-gray-500 dark:text-gray-400">User</dt>
                    <dd class="text-sm font-medium">{{ $record->user?->name ?? '—' }} ({{ $record->user?->email ?? '—' }})</dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500 dark:text-gray-400">Status</dt>
                    <dd>
                        <x-filament::badge>{{ ucfirst(str_replace('_', ' ', $record->status)) }}</x-filament::badge>
                    </dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500 dark:text-gray-400">Project</dt>
                    <dd class="text-sm font-medium">{{ $record->project?->name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500 dark:text-gray-400">Billing Profile</dt>
                    <dd class="text-sm font-medium">{{ $record->billingProfile?->name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500 dark:text-gray-400">Created</dt>
                    <dd class="text-sm font-medium">{{ $record->created_at->format('M j, Y H:i') }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500 dark:text-gray-400">Closed At</dt>
                    <dd class="text-sm font-medium">{{ $record->closed_at?->format('M j, Y H:i') ?? '—' }}</dd>
                </div>
                @if ($record->external_ref)
                <div class="col-span-2">
                    <dt class="text-sm text-gray-500 dark:text-gray-400">External Reference</dt>
                    <dd class="text-sm font-mono">{{ $record->external_ref }}</dd>
                </div>
                @endif
            </dl>

            <div class="mt-4">
                <dt class="text-sm text-gray-500 dark:text-gray-400">Description</dt>
                <dd class="mt-1 text-sm whitespace-pre-wrap">{{ $record->description }}</dd>
            </div>
        </x-filament::section>

        {{-- Internal Associations --}}
        @if ($record->internalUsers->isNotEmpty() || $record->internalProjects->isNotEmpty() || $record->internalBillingProfiles->isNotEmpty())
        <x-filament::section heading="Internal Associations">
            <div class="grid grid-cols-3 gap-4">
                @if ($record->internalUsers->isNotEmpty())
                <div>
                    <dt class="text-sm text-gray-500 dark:text-gray-400 mb-2">Related Users</dt>
                    <div class="flex flex-wrap gap-1">
                        @foreach ($record->internalUsers as $u)
                            <x-filament::badge>{{ $u->name }}</x-filament::badge>
                        @endforeach
                    </div>
                </div>
                @endif
                @if ($record->internalProjects->isNotEmpty())
                <div>
                    <dt class="text-sm text-gray-500 dark:text-gray-400 mb-2">Related Projects</dt>
                    <div class="flex flex-wrap gap-1">
                        @foreach ($record->internalProjects as $p)
                            <x-filament::badge>{{ $p->name }}</x-filament::badge>
                        @endforeach
                    </div>
                </div>
                @endif
                @if ($record->internalBillingProfiles->isNotEmpty())
                <div>
                    <dt class="text-sm text-gray-500 dark:text-gray-400 mb-2">Related Billing Profiles</dt>
                    <div class="flex flex-wrap gap-1">
                        @foreach ($record->internalBillingProfiles as $bp)
                            <x-filament::badge>{{ $bp->name }}</x-filament::badge>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        </x-filament::section>
        @endif

        {{-- Messages --}}
        <x-filament::section heading="Conversation">
            <div class="space-y-4">
                @forelse ($messages as $msg)
                    <div class="flex gap-3 {{ $msg->user_id === auth()->id() ? 'justify-end' : '' }}">
                        <div class="max-w-[80%] rounded-lg px-4 py-3 {{ $msg->user_id === auth()->id() ? 'bg-primary-100 dark:bg-primary-900' : 'bg-gray-100 dark:bg-gray-800' }}">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="text-xs font-medium">
                                    {{ $msg->user?->name ?? ($msg->user ? 'Admin' : 'System') }}
                                </span>
                                <span class="text-xs text-gray-400">{{ $msg->created_at->format('M j, H:i') }}</span>
                            </div>
                            <p class="text-sm whitespace-pre-wrap">{{ $msg->message }}</p>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400">No messages yet.</p>
                @endforelse
            </div>
        </x-filament::section>

        {{-- Reply Form --}}
        <x-filament::section heading="Reply">
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
                <x-filament::button wire:click="reply">
                    Send Reply
                </x-filament::button>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
