<x-flex class="justify-between">
    <x-button variant="secondary" wire:click="anioAnterior">
        <i class="fa fa-chevron-left"></i> Año Anterior
    </x-button>

    <x-flex>
        <x-select-anios wire:model.live="anio" class="w-auto" />
    </x-flex>

    <x-button variant="secondary" wire:click="anioSiguiente">
        Año Siguiente <i class="fa fa-chevron-right"></i>
    </x-button>
</x-flex>
