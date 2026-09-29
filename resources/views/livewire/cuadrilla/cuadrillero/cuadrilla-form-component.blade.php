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
                    <x-input label="Nombre Completo (*)" wire:model="nombres" error="nombres" />

                    <!-- DNI -->
                    <x-input label="DNI" wire:model="dni" error="dni" />

                    <!-- GRUPO -->
                    <x-select wire:model="grupoSeleccionado" label="Grupo Actual">
                        <option value="">SIN GRUPO</option>
                        @foreach ($grupoCuadrillas as $grupoCuadrillas)
                            <option value="{{ $grupoCuadrillas->codigo }}">{{ $grupoCuadrillas->nombre }}</option>
                        @endforeach
                    </x-select>
                </div>
            </form>
        </x-slot>

        <x-slot name="footer">
            <div class="flex items-center gap-2">
                <x-button variant="secondary" wire:click="$set('mostrarFormularioCuadrillero', false)"
                    wire:loading.attr="disabled">
                    Cerrar
                </x-button>
                <x-button form="formCuadrillero" type="submit" wire:loading.attr="disabled">
                    <i class="fa fa-save"></i> Registrar
                </x-button>
            </div>
        </x-slot>
    </x-dialog-modal>
</div>
