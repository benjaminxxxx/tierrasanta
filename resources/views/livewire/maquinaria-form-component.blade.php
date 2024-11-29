<div>
    <x-dialog-modal wire:model="mostrarFormulario" maxWidth="full">
        <x-slot name="title">
            <div class="flex items-center justify-between">
                <x-h3>
                    Registro de Maquinarias
                </x-h3>
                <div class="flex-shrink-0">
                    <button wire:click="$set('mostrarFormulario',false)" class="focus:outline-none">
                        <i class="fa-solid fa-circle-xmark"></i>
                    </button>
                </div>
            </div>
        </x-slot>
        <x-slot name="content">
            <form wire:submit.prevent="store">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">                  
                    <div class="mt-3">
                        <x-label for="nombre">Nombre de Maquinaria</x-label>
                        <x-input type="text" wire:keydown.enter="store" wire:model="nombre"
                            class="uppercase" id="nombre" />
                        <x-input-error for="nombre" />
                    </div>

                    <div class="mt-3">
                        <x-label for="alias_blanco">Alias para el Kardex Blanco</x-label>
                        <x-input type="text" wire:keydown.enter="store" class="uppercase"
                            wire:model="alias_blanco" id="alias_blanco" />
                        <x-input-error for="alias_blanco" />
                    </div>
                </div>
            </form>
        </x-slot>
        <x-slot name="footer">
            <x-secondary-button type="button" wire:click="$set('mostrarFormulario',false)"  class="mr-2">Cerrar</x-secondary-button>
            <x-button type="submit" wire:click="store" class="ml-3">Guardar</x-button>
        </x-slot>
    </x-dialog-modal>
</div>
