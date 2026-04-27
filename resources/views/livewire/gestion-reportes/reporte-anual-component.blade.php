<div class="space-y-4">
    @include('comun.selector-anio')

    <livewire:gestion-reportes.reporte-anual-asistencias-component :anio="$anio" wire:key="reporte{{ $anio }}" />

    <x-loading wire:loading/>
</div>