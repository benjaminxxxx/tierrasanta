<div>

    <x-flex class="justify-between">
        <x-title>
            Asistencia Mensual
        </x-title>
        <div>
            <x-button href="{{ route('planilla.asistencias') }}" variant="success" target="_blank">
                <i class="fa fa-check"></i> Version Nueva (beta)
            </x-button>
        </div>
    </x-flex>
    @include('comun.selector-mes')
    @can(\App\Constants\Permisos::PLANILLA_ASISTENCIA_VER)
        <livewire:gestion-planilla.administrar-planillero.gestion-planilla-detalle-asistencias-component :mes="$mes"
            :anio="$anio" wire:key="{{ $mes }}_{{ $anio }}" />
    @else
        <x-danger>
            No tienes permiso para ver el detalle de asistencias.
        </x-danger>
    @endcan

    <x-loading wire:loading />
</div>