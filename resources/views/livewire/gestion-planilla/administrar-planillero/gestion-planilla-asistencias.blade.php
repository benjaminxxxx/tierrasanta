<div>
    <x-title>
        Asistencia Mensual
    </x-title>
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