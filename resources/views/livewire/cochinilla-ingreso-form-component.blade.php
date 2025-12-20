<div>
    <!--MODULO COCHINILLA INGRESO-->
    <x-dialog-modal wire:model="mostrarFormulario" maxWidth="2xl">
        <x-slot name="title">
            Registro de Ingreso de Cochinilla
        </x-slot>

        <x-slot name="content">
            <div class="space-y-3">

                @if ($campania)
                    <x-success>
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
                    <x-warning>
                        No hay campañas registradas en este campo y esta fecha
                    </x-warning>
                @endif
                @if ($fechaSiembra)
                    <x-success>
                        Siembra antes de esta fecha: <b>{{ $fechaSiembra }}</b>
                    </x-success>
                @else
                    <x-warning>
                        No hay siembras disponible antes de esta fecha, revise el panel de registro de siembras
                    </x-warning>
                @endif
                <x-success>
                    Ahora solo debe agregar sublote, si es una unica recogida puede usar el codigo + .1
                </x-success>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-3">
                <x-input type="number" label="N° de sublote" wire:model="lote" error="lote" class="w-full" />
                <x-input type="date" label="Fecha" wire:model.live="fecha" class="w-full" />
                <x-select label="Campo" wire:model.live="campoSeleccionado" error="campoSeleccionado" fullWidth="true">
                    <option value="">Seleccionar campo</option>
                    @foreach ($campos as $campo)
                        <option value="{{ $campo->nombre }}">{{ $campo->nombre }}</option>
                    @endforeach
                </x-select>
                <x-input type="number" label="Área" wire:model="area" class="w-full" />
                <x-select label="Observación" wire:model="observacionSeleccionada" fullWidth="true"
                    error="observacionSeleccionada">
                    @if ($observaciones)
                        <option value="">Seleccionar observación</option>
                        @foreach ($observaciones as $observacion)
                            <option value="{{ $observacion->codigo }}">{{ $observacion->descripcion }}</option>
                        @endforeach
                    @endif
                </x-select>
                <x-input type="number" label="Total Kilos" wire:model="kg_total" class="w-full" />
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-form-buttons action="registrar" :id="$cochinillaIngresoDetalleId" />
        </x-slot>
    </x-dialog-modal>

    <x-loading wire:loading />

</div>