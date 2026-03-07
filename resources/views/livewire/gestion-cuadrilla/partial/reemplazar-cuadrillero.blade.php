<x-dialog-modal wire:model.live="mostrarReemplazarCuadrilleroForm">
    <x-slot name="title">
        Reemplazar Cuadrillero
    </x-slot>

    <x-slot name="content">
       <x-select-buscador wire:options="cuadrilleros" search-placeholder="Escriba el nombre del cuadrillero"
                        wire:model="cuadrilleroARemplazarSeleccionado" />
    </x-slot>

    <x-slot name="footer">
        <x-button variant="secondary" wire:click="$set('mostrarReemplazarCuadrilleroForm', false)" wire:loading.attr="disabled">
            Cancelar
        </x-button>

        <x-button class="ms-3" wire:click="confirmarReemplazo" wire:loading.attr="disabled">
            Confirmar Reemplazo
        </x-button>
    </x-slot>
</x-dialog-modal>
