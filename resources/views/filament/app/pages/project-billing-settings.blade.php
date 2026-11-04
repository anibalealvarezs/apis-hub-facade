<x-filament-panels::page>
    <p class="text-sm text-gray-600 dark:text-gray-300">
        {{ __('This project is linked to a single billing profile that finances its infrastructure costs and sync fees.') }}
    </p>

    <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-2">
        @if($billingProfile)
            @php
                $status = $project->billing_status ?? 'active';
                [$statusLabel, $statusColor] = match ($status) {
                    'active' => [__('Active'), 'success'],
                    'pending_approval' => [__('Pending Approval'), 'warning'],
                    'suspended' => [__('Suspended'), 'danger'],
                    default => [ucfirst($status), 'gray'],
                };
            @endphp

            <x-filament::section icon="heroicon-o-calendar-days">
                <x-slot name="heading">{{ __('Subscription') }}</x-slot>

                <dl class="space-y-5">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <dt class="text-sm text-gray-600 dark:text-gray-300">{{ __('Billing profile plan') }}</dt>
                            <dd class="mt-1 text-xl font-semibold text-gray-950 dark:text-white">
                                {{ strtoupper($billingProfile->tier->value ?? $billingProfile->tier ?? '') ?: '—' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="mb-1 text-sm text-gray-600 dark:text-gray-300">{{ __('Project Payment Status') }}</dt>
                            <dd><x-filament::badge :color="$statusColor">{{ $statusLabel }}</x-filament::badge></dd>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4 border-t border-gray-200 pt-4 dark:border-white/10">
                        <div>
                            <dt class="text-sm text-gray-600 dark:text-gray-300">{{ __('Next Renewal') }}</dt>
                            <dd class="mt-1 font-semibold">{{ $cycleEnds?->translatedFormat('d M, Y') ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-600 dark:text-gray-300">{{ __('Cycle Start') }}</dt>
                            <dd class="mt-1 font-medium">{{ $cycleStarts?->translatedFormat('d M, Y') ?? '—' }}</dd>
                        </div>
                    </div>
                </dl>
            </x-filament::section>
        @endif

        <x-filament::section icon="heroicon-o-credit-card">
            <x-slot name="heading">{{ __('Billing Profile') }}</x-slot>

            @if($billingProfile)
                <p class="break-words text-xl font-semibold text-gray-950 dark:text-white">{{ $billingProfile->display_name }}</p>
                <dl class="project-billing-profile-details text-sm">
                    @if($billingProfile->reference_name)
                        <div class="min-w-0">
                            <dt class="text-gray-600 dark:text-gray-300">{{ __('Legal Name') }}</dt>
                            <dd class="mt-1 break-words font-medium">{{ $billingProfile->name }}</dd>
                        </div>
                    @endif
                    <div>
                        <dt class="text-gray-600 dark:text-gray-300">{{ __('Profile Type') }}</dt>
                        <dd class="mt-1 font-medium">{{ __(ucfirst($billingProfile->type ?? 'individual')) }}</dd>
                    </div>
                </dl>
            @else
                <p class="font-semibold">{{ __('No Billing Profile') }}</p>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                    {{ __('Assign a billing profile to avoid service interruptions.') }}
                </p>
            @endif

            <div class="project-billing-profile-actions border-t border-gray-200 dark:border-white/10">
                {{ $this->getAction('assign_profile') }}
                {{ $this->getAction('cancel_pending_request') }}
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
