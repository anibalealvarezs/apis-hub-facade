<x-filament-panels::page
    x-data="{}"
>
    <div class="space-y-6">
        {{-- Ticket Info --}}
        <x-filament::section>
            <x-slot name="heading">
                Ticket #{{ $record->id }} — {{ ucfirst(str_replace('_', ' ', $record->type)) }}
            </x-slot>

            <dl class="grid grid-cols-2 gap-4">
                <div>
                    <dt class="text-sm text-gray-500 dark:text-gray-400">Status</dt>
                    <dd>
                        <x-filament::badge>{{ ucfirst(str_replace('_', ' ', $record->status)) }}</x-filament::badge>
                    </dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500 dark:text-gray-400">Type</dt>
                    <dd class="text-sm font-medium">{{ ucfirst(str_replace('_', ' ', $record->type)) }}</dd>
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
            </dl>

            <div class="mt-4">
                <dt class="text-sm text-gray-500 dark:text-gray-400">Description</dt>
                <dd class="mt-1 text-sm whitespace-pre-wrap">{{ $record->description }}</dd>
            </div>
        </x-filament::section>

        {{-- Messages --}}
        <x-filament::section heading="Conversation">
            <div class="space-y-4">
                @forelse ($this->getMessages() as $msg)
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
        @if ($record->status !== 'closed' && $record->isReplyAllowed(auth()->user()))
        <x-filament::section heading="Reply">
            @livewire('ticket-reply-form', ['ticket' => $record], key('ticket-reply-' . $record->id))
        </x-filament::section>
        @elseif ($record->status === 'closed')
        <x-filament::section heading="Reply">
            <p class="text-sm text-gray-500 dark:text-gray-400">This ticket is closed.</p>
        </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
