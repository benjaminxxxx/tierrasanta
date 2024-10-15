<div>
    <x-dialog-modal wire:model="mostrarFormulario" maxWidth="full">
        <x-slot name="title">
            <div class="flex items-center justify-between">
                <x-h3>
                    Registro de Proveedores
                </x-h3>
                <div class="flex-shrink-0">
                    <button wire:click="closeForm" class="focus:outline-none">
                        <i class="fa-solid fa-circle-xmark"></i>
                    </button>
                </div>
            </div>
        </x-slot>
        <x-slot name="content">
            <form wire:submit.prevent="store">
                <div class="grid grid-cols-2 gap-5">

                    <div class="col-span-2 md:col-span-1 mt-3">
                        <x-label for="nombre">Nombre de la Empresa</x-label>
                        <x-input type="text" wire:keydown.enter="store" wire:model="nombre" class="uppercase"
                            id="nombre" />
                        <x-input-error for="nombre" />
                    </div>

                    <div class="col-span-2 md:col-span-1 mt-3">
                        <x-label for="ruc">RUC</x-label>
                        <x-input type="text" wire:keydown.enter="store" class="uppercase"
                            wire:model="ruc" id="ruc" />
                        <x-input-error for="ruc" />
                    </div>

                    <div class="col-span-2 md:col-span-1 mt-3">
                        <x-label for="contacto">Número de contacto</x-label>
                        <x-input type="text" wire:keydown.enter="store" class="uppercase"
                            wire:model="contacto" id="contacto" />
                        <x-input-error for="contacto" />
                    </div>
                </div>
            </form>
        </x-slot>
        <x-slot name="footer">
            <x-secondary-button type="button" wire:click="closeForm" class="mr-2">Cancelar</x-secondary-button>
            <x-button type="submit" wire:click="store" class="ml-3">Guardar</x-button>
        </x-slot>
    </x-dialog-modal>
</div>
