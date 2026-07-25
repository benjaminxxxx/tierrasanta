<div>
    <x-dialog-modal wire:model.live="mostrarModal">
        <x-slot name="title">
            {{ $cargoId ? 'Editar cargo' : 'Nuevo cargo' }}
        </x-slot>

        <x-slot name="content">
            <div class="space-y-4">
                <x-input
                    label="Nombre del cargo"
                    wire:model="nombre"
                    error="nombre"
                    class="w-full"
                    autofocus
                />

                <x-input
                    type="number"
                    label="Cupo máximo (vacío = sin límite)"
                    wire:model="cupoMaximo"
                    error="cupoMaximo"
                    class="w-full"
                    min="1"
                />

                <x-select label="Estado" wire:model="activo" fullWidth="true" error="activo">
                    <option value="1">Activo</option>
                    <option value="0">Inactivo</option>
                </x-select>
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-button variant="secondary" wire:click="cerrar" wire:loading.attr="disabled">
                Cancelar
            </x-button>
            <x-button wire:click="guardar" wire:loading.attr="disabled">
                <i class="fa fa-save"></i> Guardar
            </x-button>
        </x-slot>
    </x-dialog-modal>
</div>