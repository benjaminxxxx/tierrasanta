<div>
    @include('comun.selector-mes')
    <livewire:gestion-planilla.administrar-planillero.gestion-planilla-detalle-asistencias-component :mes="$mes" :anio="$anio" wire:key="{{ $mes }}_{{ $anio }}"/>
    <x-loading wire:loading />
</div>