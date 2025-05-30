<div>
    <x-dialog-modal wire:model="mostrarFormulario">
        <x-slot name="title">
            <div class="flex items-center justify-between">
                <x-h3>
                    Registro de Campaña
                </x-h3>
                <div class="flex-shrink-0">
                    <button wire:click="$set('mostrarFormulario',false)" class="focus:outline-none">
                        <i class="fa-solid fa-circle-xmark"></i>
                    </button>
                </div>
            </div>
        </x-slot>
        <x-slot name="content">
            <div>
                @if ($ultimaCampania)
                    <p><b>Nombre de ultima Campaña: </b>{{ $ultimaCampania->nombre_campania }}</p>
                    <p>Rango: {{ $ultimaCampania->fecha_inicio }} - {{ $ultimaCampania->fecha_fin }}</p>
                @else
                    <x-warning class="my-3">
                        <p>No existe una campaña actual.</p>
                    </x-warning>
                @endif
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                @if ($campaniaId)
                    <div>
                        <p>
                            <b>Campo:</b> {{ $campoSeleccionado }}
                        </p>
                        <p>
                            <b>Área original:</b> {{ $areaOriginal }}
                        </p>
                    </div>
                @else
                    <x-select-campo wire:model.live="campoSeleccionado" error="campoSeleccionado" label="Campo" />
                @endif

                <x-input-number wire:model="area" error="area" label="Área" />
                <x-input-date wire:model="fecha_inicio" error="fecha_inicio" label="Fecha de Inicio" />
                <x-input-string wire:model="nombre_campania" error="nombre_campania" label="Nombre de la Campaña" />
                <x-input-string wire:model="variedad_tuna" error="variedad_tuna" label="Variedad de Tuna" />
                <x-input-string wire:model="sistema_cultivo" error="sistema_cultivo" label="Sistema de Cultivo" />
                <x-input-number wire:model="pencas_x_hectarea" error="pencas_x_hectarea" label="Pencas por Hectárea" />
                <x-input-number wire:model="tipo_cambio" error="tipo_cambio" label="Tipo de Cambio" />
                <x-input-date wire:model="fecha_fin" error="fecha_fin" label="Fecha de cierre"
                    descripcion="Este campo se calcula de forma automática al crear la siguiente campaña." />

            </div>
            <div>
                @if (count($errorMensaje))
                    <x-warning>
                        <ul>
                            @foreach ($errorMensaje as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </x-warning>
                @endif
            </div>
        </x-slot>
        <x-slot name="footer">
            <!--Boton cerrar y registrar, parametros action id, si el id existe se cambia el texto a actualizar-->
            <x-form-buttons action="store" id="{{ $campaniaId }}" />
        </x-slot>
    </x-dialog-modal>
</div>
