<div>
    <x-loading wire:loading />
    <x-dialog-modal wire:model.live="mostrarFormularioCuadrillero">
        <x-slot name="title">
            Registrar Cuadrillero
        </x-slot>

        <x-slot name="content">
            <form wire:submit.prevent="guardarCuadrillero" id="formCuadrillero">
                <div class="space-y-4">
                    <!-- Nombre Completo -->
                    <div>
                        <x-label for="nombres" value="Nombre Completo (*)" />
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
                        <x-select wire:model="grupoSeleccionado" label="Grupo Actual">
                            <option value="">Seleccionar su grupo actual</option>
                            @foreach ($grupoCuadrillas as $grupoCuadrillas)
                                <option value="{{ $grupoCuadrillas->codigo }}">{{ $grupoCuadrillas->nombre }}</option>
                            @endforeach
                        </x-select>
                    </div>
                </div>
            </form>
        </x-slot>

        <x-slot name="footer">
            <div class="flex items-center gap-2">
                <x-secondary-button wire:click="$set('mostrarFormularioCuadrillero', false)"
                    wire:loading.attr="disabled">
                    Cerrar
                </x-secondary-button>
                <x-button form="formCuadrillero" type="submit" wire:loading.attr="disabled">
                    <i class="fa fa-save"></i> Registrar
                </x-button>
            </div>
        </x-slot>
    </x-dialog-modal>
</div>