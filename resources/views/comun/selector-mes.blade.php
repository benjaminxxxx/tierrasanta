<x-card>
    <x-flex class="justify-between">
        <x-button variant="secondary" wire:click="mesAnterior">
            <i class="fa fa-chevron-left"></i> Mes Anterior
        </x-button>

        <x-flex>
            <x-select-meses wire:model.live="mes" class="w-auto" />
            <x-select-anios wire:model.live="anio" class="w-auto" />
        </x-flex>

        <x-button variant="secondary" wire:click="mesSiguiente">
            Mes Siguiente <i class="fa fa-chevron-right"></i>
        </x-button>
    </x-flex>
</x-card>
