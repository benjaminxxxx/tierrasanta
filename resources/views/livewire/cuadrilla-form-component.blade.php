<div>
    <x-loading wire:loading />
    <x-dialog-modal wire:model.live="mostrarFormulario">
        <x-slot name="title">
            Registrar Cuadrillero
        </x-slot>

        <x-slot name="content">
            <div class="space-y-4">
                <!-- Nombre Completo -->
                <div>
                    <x-label for="nombres" value="Nombre Completo" />
                    <x-input wire:model="nombres" />
                    <x-input-error for="nombres" />
                </div>

                <!-- DNI -->
                <div class="mt-2">
                    <x-label for="dni" value="DNI" />
                    <x-input wire:model="dni" />
                    <x-input-error for="dni" />
                </div>

                <!-- GRUPO -->
                <div class="mt-2">
                    <x-select wire:model="codigo_grupo" label="Grupo Actual">

                        <option value="">Seleccionar su grupo actual</option>
                        @foreach ($grupos as $grupo)
                            <option value="{{ $grupo->codigo }}">{{ $grupo->nombre }}</option>
                        @endforeach
                    </x-select>
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