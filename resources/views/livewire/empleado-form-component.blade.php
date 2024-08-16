<div>
    <x-button type="button" wire:click="CrearEmpleado" class="w-full md:w-auto ">Registrar Empleado</x-button>

    <x-dialog-modal-header wire:model="isFormOpen" maxWidth="full">
        <x-slot name="title">
            <div class="flex items-center justify-between">
                <x-h3>
                    Registro de Empleado
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
                        <x-label for="nombres">Nombres</x-label>
                        <x-inputn type="text" wire:keydown.enter="store" wire:model="nombres" class="uppercase" id="nombres" />
                        <x-input-error for="nombres" />
                    </div>

                    <div class="col-span-2 md:col-span-1 mt-3">
                        <x-label for="apellido_paterno">Apellido Paterno</x-label>
                        <x-inputn type="text" wire:keydown.enter="store" class="uppercase" wire:model="apellido_paterno"
                            id="apellido_paterno" />
                        <x-input-error for="apellido_paterno" />
                    </div>

                    <div class="col-span-2 md:col-span-1 mt-3">
                        <x-label for="apellido_materno">Apellido Materno</x-label>
                        <x-inputn type="text" wire:keydown.enter="store" class="uppercase" wire:model="apellido_materno"
                            id="apellido_materno" />
                        <x-input-error for="apellido_materno" />
                    </div>

                    <div class="col-span-2 md:col-span-1 mt-3">
                        <x-label for="documento">Documento</x-label>
                        <x-inputn type="text" wire:keydown.enter="store" class="uppercase" wire:model="documento" id="documento" />
                        <x-input-error for="documento" />
                    </div>
                </div>
            </form>
        </x-slot>
        <x-slot name="footer">
            <x-secondary-button type="button" wire:click="closeForm" class="mr-2">Cancelar</x-secondary-button>
            <x-button type="submit" wire:click="store" class="ml-3">Guardar</x-button>
        </x-slot>
    </x-dialog-modal-header>
</div>
