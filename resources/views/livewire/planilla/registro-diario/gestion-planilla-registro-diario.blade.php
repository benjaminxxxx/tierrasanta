{{--
    Registro diario de planilla. Todo lo que cambia de día, recarga la lista o exporta pasa por registroDiarioGuardia
    (definida por la tabla): si hay cambios sin guardar pregunta "Guardar y continuar / Descartar / Seguir editando".
--}}
<div x-data="{
        guardia(accion, alCancelar = null) {
            (window.registroDiarioGuardia ?? ((a) => a()))(accion, alCancelar);
        },
        init() {
            Livewire.on('registroDiarioPideFecha', ({ fecha }) => {
                this.guardia(() => $wire.irAFecha(fecha), () => $wire.set('fechaElegida', $wire.fecha));
            });
        },
    }">
    <x-flex class="justify-between">
        <x-flex>
            <x-title>
                Reporte Diario de Trabajo
            </x-title>
            <x-button variant="secondary" @click="$wire.dispatch('verLabores')">
                <i class="fa fa-eye"></i> Ver Labores
            </x-button>
            <x-button variant="success" @click="guardia(() => $wire.descargarReporteDiario())" wire:loading.attr="disabled" wire:target="descargarReporteDiario"
                title="Descarga el Excel del reporte diario (planilla, cuadrilla y riego) de la fecha seleccionada">
                <i class="fa fa-file-excel"></i> Enviar reporte diario
            </x-button>
        </x-flex>
        <div>
            @if (\Carbon\Carbon::parse($fecha)->dayOfWeek === 0)
                <x-danger class="animate-pulse">
                    Atención: Día Domingo
                </x-danger>
            @endif
        </div>
    </x-flex>
    <x-card class="w-full mt-4">
        <x-flex class="justify-between w-full">
            <x-button variant="secondary" @click="guardia(() => $wire.fechaAnterior())">
                <i class="fa fa-chevron-left"></i> <span class="hidden lg:inline">Fecha Anterior</span>
            </x-button>

            <div class="lg:flex gap-4 lg:w-auto text-center">
                <x-selector-dia type="date" wire:model.live="fechaElegida" class="text-center !w-auto" />
                @can(\App\Constants\Permisos::PLANILLA_ACTIVIDAD_GESTIONAR)
                {{-- Al guardar el orden se recarga la tabla --}}
                <x-button @click="guardia(() => $wire.dispatch('mostrarModalOrdenPlanilla',{mes:{{ $mes }},anio:{{ $anio }}}))">
                    <i class="fa fa-users"></i> Gestionar Lista
                </x-button>
                @endcan
            </div>

            <x-button variant="secondary" @click="guardia(() => $wire.fechaPosterior())">
                <span class="hidden lg:inline">Fecha Posterior</span> <i class="fa fa-chevron-right"></i>
            </x-button>
        </x-flex>
    </x-card>
    @can(\App\Constants\Permisos::PLANILLA_ACTIVIDAD_VER)
        <livewire:planilla.registro-diario.gestion-planilla-registro-diario-detalle-component
            wire:key="{{ $fecha }}" :fecha="$fecha" />
    @else
        <x-danger class="mt-4">
            No tienes permiso para ver el detalle de las labores diarias.
        </x-danger>
    @endcan

    <x-loading wire:loading wire:target="fechaAnterior,fechaPosterior,irAFecha,descargarReporteDiario" />
</div>
