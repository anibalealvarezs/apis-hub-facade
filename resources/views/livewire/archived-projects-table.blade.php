<x-filament-breezy::grid-section md=2 title="{{ __('Archived Projects') }}" description="{{ __('Deleted projects that are in the grace period (30 days) before permanent destruction.') }}">
    <x-filament::card>
        {{ $this->table }}
    </x-filament::card>
</x-filament-breezy::grid-section>
