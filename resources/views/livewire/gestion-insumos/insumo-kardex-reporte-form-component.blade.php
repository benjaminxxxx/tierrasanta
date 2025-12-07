<div>
    <x-dialog-modal wire:model.live="mostrarFormularioInsumoKardexReporte" maxWidth="lg">
        <x-slot name="title">
            Crear un Reporte de Kardex de Insumos
        </x-slot>

        <x-slot name="content">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-input type="text" label="Nombre del Reporte" wire:model="nombre" error="nombre" />
                <x-input type="number" label="Año" wire:model="anio" error="anio" />
                <x-select label="Tipo de Kardex" wire:model="tipoKardex" error="tipoKardex">
                    <x-label>Tipo de Kardex</x-label>
                    <option value="">-- Seleccione Tipo de Kardex --</option>
                    <option value="blanco">Blanco</option>
                    <option value="negro">Negro</option>
                </x-select>

                <x-group-field>
                    <x-label>Categorias</x-label>
                    <div class="space-y-2">
                        @foreach($categoriasDisponibles as $key => $label)
                            <x-label for="seleccion{{ $key }}">
                                <x-checkbox id="seleccion{{ $key }}" wire:model.live="categoriasSeleccionadas.{{ $key }}" />
                                {{ $label }}
                            </x-label>
                        @endforeach
                    </div>
                    
                    <x-input-error for="categoriasSeleccionadas" class="mt-1" />

                </x-group-field>
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-flex>
                <x-button variant="secondary" wire:click="$set('mostrarFormularioInsumoKardexReporte', false)"
                    wire:loading.attr="disabled">
                    Cerrar
                </x-button>
                <x-button wire:click="guardarInsumoKardexReporte" wire:loading.attr="disabled">
                    <i class="fa fa-save"></i> Registrar
                </x-button>
            </x-flex>
        </x-slot>
    </x-dialog-modal>
    <x-loading wire:loading />
</div>