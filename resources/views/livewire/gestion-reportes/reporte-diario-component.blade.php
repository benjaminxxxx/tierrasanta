<div class="space-y-4">
    @include('comun.selector-dia')

    <livewire:gestion-reportes.reporte-diario-asistencias-component :fecha="$fecha" wire:key="asistencia{{ $fecha }}" />

    <livewire:gestion-reportes.reporte-diario-actividades-component :fecha="$fecha"
        wire:key="actividades{{ $fecha }}" />


    <x-loading wire:loading />
</div>