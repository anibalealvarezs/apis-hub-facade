<x-filament-breezy::grid-section md=2 title="Proyectos Archivados" description="Proyectos eliminados que están en periodo de gracia (30 días) antes de su destrucción permanente.">
    <x-filament::card>
        {{ $this->table }}
    </x-filament::card>
</x-filament-breezy::grid-section>
