<div class="space-y-4">
    <div>
        <x-title>Reporte Mensual</x-title>
        <x-subtitle>Consolidado Mensual</x-subtitle>
    </div>
    @include('comun.selector-mes')

    @can(\App\Constants\Permisos::REPORTE_MENSUAL_VER)
        <livewire:gestion-reportes.reporte-mensual-asistencias-component :mes="$mes" :anio="$anio"
            wire:key="reporte{{ $mes }}.{{ $anio }}" />
    @else
        <x-danger>
            No tiene permisos para ver la siguiente información.
        </x-danger>
    @endcan
    <x-loading wire:loading />
</div>