<x-card>
    <x-flex class="justify-between">
        <x-button variant="secondary" wire:click="fechaAnterior">
            <i class="fa fa-chevron-left"></i> Fecha Anterior
        </x-button>

        <x-flex>
            <x-selector-dia wire:model.live="fecha"/>
        </x-flex>

        <x-button variant="secondary" wire:click="fechaPosterior">
            Siguiente Fecha <i class="fa fa-chevron-right"></i>
        </x-button>
    </x-flex>
</x-card>
