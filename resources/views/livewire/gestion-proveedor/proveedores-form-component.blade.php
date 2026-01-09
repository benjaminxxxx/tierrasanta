<div>
    <x-dialog-modal wire:model="mostrarFormularioProveedores" maxWidth="lg">
        <x-slot name="title">
            <x-title>
                Registro de Proveedor
            </x-title>
        </x-slot>
        <x-slot name="content">
            <form wire:submit.prevent="guardarProveedores">
                <div class="grid grid-cols-2 gap-5">

                    <x-input type="text" label="Nombre de la Empresa" wire:model="nombre" wire:keydown.enter="store"
                        class="uppercase" error="nombre" />

                    <x-input type="text" label="RUC" wire:model="ruc" wire:keydown.enter="store" class="uppercase"
                        error="ruc" />

                    <x-input type="text" label="Número de contacto" wire:model="contacto" wire:keydown.enter="store"
                        class="uppercase" error="contacto" />

                </div>
            </form>
        </x-slot>
        <x-slot name="footer">
            <x-button type="button" variant="secondary" @click="$wire.set('mostrarFormularioProveedores', false)">
                Cancelar
            </x-button>
            <x-button type="submit" wire:click="guardarProveedores">
                <i class="fa fa-save"></i> Guardar
            </x-button>
        </x-slot>
    </x-dialog-modal>
</div>
