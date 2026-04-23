<div class="space-y-4">
    @include('comun.selector-dia')

    <livewire:gestion-reportes.reporte-diario-asistencias-component :fecha="$fecha" wire:key="asistencia{{ $fecha }}" />

    <x-loading wire:loading/>
</div>