<div class="space-y-4">
    <div>
        <x-title>Reporte Mensual</x-title>
        <x-subtitle>Consolidado Mensual</x-subtitle>
    </div>
    @include('comun.selector-mes')

    @can(\App\Constants\Permisos::REPORTE_MENSUAL_VER)
        <livewire:reporte.reporte-mensual-asistencias-component :mes="$mes" :anio="$anio"
            wire:key="reporte{{ $mes }}.{{ $anio }}" />
        @can(\App\Constants\Permisos::CONTABILIDAD_COSTO_MENSUAL)
            <livewire:reporte.reporte-mensual-mano-obra-indirecta-component :mes="(int) $mes" :anio="(int) $anio"
                wire:key="moi{{ $mes }}.{{ $anio }}" />
        @endcan
    @else
        <x-danger>
            No tiene permisos para ver la siguiente información.
        </x-danger>
    @endcan
    <x-loading wire:loading />
</div>