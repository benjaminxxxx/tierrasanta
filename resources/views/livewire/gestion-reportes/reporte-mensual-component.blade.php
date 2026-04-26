<div class="space-y-4">
    @include('comun.selector-mes')

    <livewire:gestion-reportes.reporte-mensual-asistencias-component :mes="$mes" :anio="$anio" wire:key="reporte{{ $mes }}.{{ $anio }}" />

    <x-loading wire:loading/>
</div>