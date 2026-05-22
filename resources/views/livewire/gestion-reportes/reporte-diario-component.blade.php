<div class="space-y-4">
    <div>
        <x-title>Reporte Diario</x-title>
        <x-subtitle>Consolidado Diario</x-subtitle>
    </div>
    @include('comun.selector-dia')

    @can(\App\Constants\Permisos::REPORTE_DIARIO_VER)
        <livewire:gestion-reportes.reporte-diario-asistencias-component :fecha="$fecha" wire:key="asistencia{{ $fecha }}" />

        <livewire:gestion-reportes.reporte-diario-actividades-component :fecha="$fecha"
            wire:key="actividades{{ $fecha }}" />
    @else
        <x-danger>
            No tiene permisos para ver la siguiente información.
        </x-danger>
    @endcan




    <x-loading wire:loading />
</div>