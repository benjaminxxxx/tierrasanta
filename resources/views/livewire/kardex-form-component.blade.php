<div>
    <x-loading wire:loading />
    <x-dialog-modal wire:model.live="mostrarFormulario" maxWidth="lg">
        <x-slot name="title">
            Registro de Kardex
        </x-slot>

        <x-slot name="content">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-group-field>
                    <x-select label="Año del Kardex" wire:model.live="anioSeleccionado" error="anioSeleccionado">
                        <option value="">Seleccionar año</option>
                        @foreach ($anios as $anio)
                            <option value="{{$anio}}">{{$anio}}</option>
                        @endforeach
                    </x-select>
                </x-group-field>
                <x-group-field>
                    <x-select label="Tipo de Kardex" wire:model.live="tipoKardex" error="tipoKardex">
                        <option value="">Seleccionar el tipo de kardex</option>
                        <option value="normal">Pesticidas y Fertilizantes</option>
                        <option value="combustible">Comubustible</option>
                    </x-select>
                </x-group-field>
                <x-group-field>
                    <x-input-date type="date" label="Fecha de Inicio" wire:model="fecha_inicial"
                        error="fecha_inicial" class="!bg-gray-100" readonly />
                </x-group-field>
                <x-group-field>
                    <x-input-date type="date" label="Fecha de Fin" wire:model="fecha_final" error="fecha_final"
                        class="!bg-gray-100" readonly />
                </x-group-field>
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-flex>
                <x-secondary-button wire:click="$set('mostrarFormulario', false)" wire:loading.attr="disabled">
                    Cerrar
                </x-secondary-button>
                <x-button wire:click="storeKardexForm" wire:loading.attr="disabled">
                    <i class="fa fa-save"></i> Registrar
                </x-button>
            </x-flex>
        </x-slot>
    </x-dialog-modal>
</div>
