<div x-data="reporteSemanalTramos">
    <x-flex>

        <x-h3>
            Registro Semanal Cuadrilla
        </x-h3>
        <x-button wire:click="crearNuevoTramo" wire:loading.attr="disabled">
            <i class="fa fa-plus"></i> Crear tramo
        </x-button>
        <x-button variant="success" wire:click="buscarTramo" wire:loading.attr="disabled">
            <i class="fa fa-search"></i> Buscar Tramo
        </x-button>
    </x-flex>
    <x-flex class="justify-between items-center mt-5">
        <x-button wire:click="irTramoAnterior" :disabled="!$tramoAnterior">
            <i class="fa fa-chevron-left"></i> Anterior
        </x-button>

        <div class="text-center">
            @if ($tramoActual)
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
                    <x-label>Fecha de inicio del tramo</x-label>
                    <x-input type="date" x-model="fechaInicio" @change="actualizarTitulo"
                        wire:model="fecha_inicio" />
                </x-group-field>

                <x-group-field>
                    <x-label>Fecha final del tramo</x-label>
                    <x-input type="date" x-model="fechaFin" @change="actualizarTitulo" wire:model="fecha_fin" />
                </x-group-field>

                <x-group-field>
                    <x-input type="checkbox" wire:model="acumula_costos" label="Acumula costos" />
                </x-group-field>
            </div>

            <div class="mt-5">
                <x-input-string label="Titulo del reporte" x-model="titulo" />
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-button variant="secondary" wire:click="$set('mostrarFormularioReporteSemanalTramo', false)"
                wire:loading.attr="disabled">
                Cerrar
            </x-button>
            <x-button wire:click="guardarTramoSemanal" wire:loading.attr="disabled">
                @if ($tramoId)
                    <i class="fa fa-sync"></i> Actualizar tramo
                @else
                    <i class="fa fa-save"></i> Registrar nuevo tramo
                @endif

            </x-button>
        </x-slot>
    </x-dialog-modal>
    <x-dialog-modal wire:model.live="mostrarBuscadorDeTramos">
        <x-slot name="title">
            Buscar Tramo
        </x-slot>

        <x-slot name="content">
            <x-flex class="justify-between">
                <x-flex>
                    <x-select-anios label="AÑO" wire:model.live="filtroBuscarTramo.anio" />
                    <x-select-meses label="MES" wire:model.live="filtroBuscarTramo.mes" />
                </x-flex>
                <x-flex>
                    <x-button wire:click="mesAnterior">
                        <i class="fa fa-arrow-left"></i> Mes anterior
                    </x-button>

                    <x-button wire:click="mesSiguiente">
                        Mes siguiente <i class="fa fa-arrow-right"></i>
                    </x-button>
                </x-flex>
            </x-flex>
            {{-- Resultados --}}
            <div class="mt-5 space-y-2">
                @foreach ($resultadoBuquedaTramos as $tramo)
                    <x-resumen-item>
                        <x-slot name="label">
                            {{ $tramo->titulo }}
                        </x-slot>
                        <x-slot name="value">
                            <x-button wire:click="seleccionarTramo({{ $tramo->id }})">
                                Seleccionar <i class="fa fa-arrow-right"></i>
                            </x-button>
                        </x-slot>
                    </x-resumen-item>
                @endforeach
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-button variant="secondary" wire:click="$set('mostrarBuscadorDeTramos', false)"
                wire:loading.attr="disabled">
                Cerrar
            </x-button>
        </x-slot>
    </x-dialog-modal>

    <x-loading wire:loading />
</div>
@script
    <script>
        Alpine.data('reporteSemanalTramos', () => ({
            fechaInicio: '',
            fechaFin: '',
            titulo: @entangle('titulo'),
            actualizarTitulo() {
                if (!this.fechaInicio || !this.fechaFin) {
                    this.titulo = ''
                    return
                }

                const inicio = new Date(this.fechaInicio + 'T00:00:00')
                const fin = new Date(this.fechaFin + 'T00:00:00')


                const optsMes = {
                    month: 'long'
                }
                const optsDia = {
                    weekday: 'long'
                }

                const mesInicio = inicio.toLocaleDateString('es-ES', optsMes).toUpperCase()
                const mesFin = fin.toLocaleDateString('es-ES', optsMes).toUpperCase()

                if (inicio.getTime() === fin.getTime()) {
                    const dia = inicio.toLocaleDateString('es-ES', optsDia).toUpperCase()

                    this.titulo = `CUADRILLA MENSUAL DEL ${dia} ${inicio.getDate()} DE ${mesInicio}`
                    return
                }

                this.titulo =
                    `CUADRILLA MENSUAL DEL ${inicio.getDate()} DE ${mesInicio} AL ${fin.getDate()} DE ${mesFin}`
            }
        }));
    </script>
@endscript
