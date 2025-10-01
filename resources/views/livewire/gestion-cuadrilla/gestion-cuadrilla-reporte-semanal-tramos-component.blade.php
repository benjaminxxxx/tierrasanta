<div>
    <x-flex>

        <x-h3>
            Registro Semanal Cuadrilla
        </x-h3>
        <x-button wire:click="crearNuevoTramo" wire:loading.attr="disabled">
            <i class="fa fa-plus"></i> Crear tramo
        </x-button>

    </x-flex>
    <x-flex class="justify-between items-center mt-5">
        <x-button wire:click="irTramoAnterior" :disabled="!$tramoAnterior">
            <i class="fa fa-chevron-left"></i> Anterior
        </x-button>

        <div class="text-center">
            @if($tramoActual)
                <x-h3>{{ $tramoActual->titulo }}</x-h3>
                <x-flex class="text-lg text-gray-600 dark:text-gray-300 justify-center mt-2">
                    <div>
                        {{ \Carbon\Carbon::parse($tramoActual->fecha_inicio)->format('d/m/Y') }}
                        -
                        {{ \Carbon\Carbon::parse($tramoActual->fecha_fin)->format('d/m/Y') }}
                    </div>
                </x-flex>
            @else
                <span class="dark:text-gray-200">No hay tramo actual</span>
            @endif
        </div>

        <x-button wire:click="irTramoSiguiente" :disabled="!$tramoSiguiente">
            Siguiente <i class="fa fa-chevron-right"></i>
        </x-button>
    </x-flex>

    @if ($tramoActual)

        <livewire:gestion-cuadrilla.gestion-cuadrilla-reporte-semanal-tramo-component :tramoId="$tramoActual->id"
            wire:key="tramo{{ $tramoActual->id }}-{{ $cambios }}" />
        <livewire:gestion-cuadrilla.gestion-cuadrilla-reporte-semanal-tramo-agregar-cuadrillero-component
            :tramoId="$tramoActual->id" wire:key="tramoAgregarCuadrillero{{ $tramoActual->id }}" />
        <livewire:gestion-cuadrilla.gestion-cuadrilla-asignacion-costos-component />
        <livewire:gestion-cuadrilla.gestion-cuadrilla-gastos-adicionales-component :tramoId="$tramoActual->id"
            wire:key="gastosAdicionales{{ $tramoActual->id }}" />
        <livewire:gestion-cuadrilla.administrar-cuadrillero.cuadrilla-grupo-form-component /> 

    @endif

    <x-dialog-modal wire:model.live="mostrarFormularioReporteSemanalTramo">
        <x-slot name="title">
            @if ($tramoId)
                <x-flex>
                    Editar tramo semanal
                    @if ($tramoActual)
                        <span>{{ $tramoActual->fecha_inicio }} a {{ $tramoActual->fecha_fin }}</span>
                    @endif
                </x-flex>
            @else
                Registrar tramo semanal
            @endif
        </x-slot>

        <x-slot name="content">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <x-group-field>
                    <x-label>
                        Fecha de inicio del tramo
                    </x-label>
                    <x-input type="date" wire:model.live="fecha_inicio" />
                    <x-input-error for="fecha_inicio" />
                </x-group-field>
                <x-group-field>
                    <x-label>
                        Fecha final del tramo
                    </x-label>
                    <x-input type="date" wire:model.live="fecha_fin" />
                    <x-input-error for="fecha_fin" />
                </x-group-field>
                <x-group-field>
                    <x-input type="checkbox" wire:model="acumula_costos" label="Acumula costos" help="Si desmarca esta opción los montos generados aquí no se van a acumular" />
                </x-group-field>
            </div>



            <div class="mt-5">
                <x-input-string label="Titulo del reporte" wire:model="titulo" />
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$set('mostrarFormularioReporteSemanalTramo', false)"
                wire:loading.attr="disabled">
                Cerrar
            </x-secondary-button>
            <x-button wire:click="guardarTramoSemanal" wire:loading.attr="disabled">
                @if ($tramoId)
                    <i class="fa fa-sync"></i> Actualizar tramo
                @else
                    <i class="fa fa-save"></i> Registrar nuevo tramo
                @endif

            </x-button>
        </x-slot>
    </x-dialog-modal>

    <x-loading wire:loading />
</div>