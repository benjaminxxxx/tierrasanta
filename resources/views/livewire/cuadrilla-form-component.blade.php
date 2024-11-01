<div>
    <x-loading wire:loading/>
    <x-dialog-modal wire:model.live="mostrarFormulario">
        <x-slot name="title">
            Registrar Cuadrillero
        </x-slot>

        <x-slot name="content">
            <div class="space-y-4">
                <!-- Nombre Completo -->
                <div>
                    <x-label for="nombres" value="Nombre Completo" />
                    <x-input id="nombres" type="text" class="mt-1 uppercase" wire:model="nombres" />
                    <x-input-error for="nombres" class="mt-2" />
                </div>

                <!-- DNI -->
                <div class="mt-2">
                    <x-label for="dni" value="DNI" />
                    <x-input id="dni" type="text" class="mt-1 block w-full" wire:model="dni" />
                    <x-input-error for="dni" class="mt-2" />
                </div>
            </div>
        </x-slot>

        <x-slot name="footer">
            <div class="flex items-center gap-2">
                <x-secondary-button wire:click="$set('mostrarFormulario', false)" wire:loading.attr="disabled">
                    Cerrar
                </x-secondary-button>
                <x-button wire:click="registrar" wire:loading.attr="disabled">
                    Registrar
                </x-button>
            </div>
        </x-slot>
    </x-dialog-modal>
</div>
