<div>
    <!--MODULO COCHINILLA INGRESO-->
    <x-loading wire:loading />
    <x-dialog-modal wire:model="mostrarFormulario" maxWidth="2xl">
        <x-slot name="title">
            Registro de Ingreso de Cochinilla
        </x-slot>

        <x-slot name="content">
            <x-group-field>

                @if ($campania)
                    <x-success class="mb-3">
                        <p>
                            Campo
                            {{ $campania->campo ?? '' }}
                        </p>
                        <p>
                            Campaña
                            {{ $campania->nombre_campania ?? '' }}
                        </p>
                        <p>
                            Variedad
                            {{ $campania->variedad_tuna ?? '' }}
                        </p>
                        <p>
                            Fecha de Inicio
                            {{ $campania->fecha_inicio ?? '' }}
                        </p>
                        <p>
                            Fecha Siembra
                            {{ $campania->fecha_siembra ?? '' }}
                        </p>
                    </x-success>
                @else
                    <x-warning class="mb-3">
                        No hay campañas registradas en este campo y esta fecha
                    </x-warning>
                @endif
                @if ($fechaSiembra)
                    <x-success>
                        Siembra antes de esta fecha: <b>{{ $fechaSiembra }}</b>
                    </x-success>
                @else
                    <x-warning class="mt-2">
                        No hay siembras disponible antes de esta fecha, revise el panel de registro de siembras
                    </x-warning>
                @endif
                <x-success class="mt-2">
                    Ahora solo debe agregar sublote, si es una unica recogida puede usar el codigo + .1
                </x-success>
            </x-group-field>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-3">
                <x-input-number label="N° de sublote" wire:model="lote" error="lote" />
                <x-input-date label="Fecha" wire:model.live="fecha" />
                <x-select label="Campo" wire:model.live="campoSeleccionado" error="campoSeleccionado">
                    <option value="">Seleccionar campo</option>
                    @foreach ($campos as $campo)
                        <option value="{{ $campo->nombre }}">{{ $campo->nombre }}</option>
                    @endforeach
                </x-select>

            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-3">
                <x-group-field>
                    <x-input-number label="Área" wire:model="area" />
                </x-group-field>
                <x-group-field>
                    <x-select label="Observación" wire:model="observacionSeleccionada" error="observacionSeleccionada">
                        @if ($observaciones)
                            <option value="">Seleccionar observación</option>
                            @foreach ($observaciones as $observacion)
                                <option value="{{ $observacion->codigo }}">{{ $observacion->descripcion }}</option>
                            @endforeach
                        @endif
                    </x-select>
                </x-group-field>
                <x-group-field>
                    <x-input-number label="Total Kilos" wire:model="kg_total" />
                </x-group-field>
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-form-buttons action="registrar" :id="$cochinillaIngresoDetalleId" />
        </x-slot>
    </x-dialog-modal>


</div>
