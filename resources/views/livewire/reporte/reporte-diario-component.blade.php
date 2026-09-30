<div class="space-y-4">
    <div>
        <x-title>Reporte Diario</x-title>
        <x-subtitle>Consolidado Diario</x-subtitle>
    </div>
    @include('comun.selector-dia')

    @can(\App\Constants\Permisos::REPORTE_DIARIO_VER)
        <livewire:reporte.reporte-diario-asistencias-component :fecha="$fecha" wire:key="asistencia{{ $fecha }}" />

        {{-- LEGACY: el bloque de actividades (reporte-diario-actividades-component) se retiró el 30/09/2026:
             no se usaba y consultaba la vista v_reporte_actividades_diario (muy lenta). Ver legacy/README.md --}}
    @else
        <x-danger>
            No tiene permisos para ver la siguiente información.
        </x-danger>
    @endcan




    <x-loading wire:loading />
</div>