<div class="space-y-4">
    <div>
        <x-title>Reporte Anual</x-title>
        <x-subtitle>Consolidado Anual General</x-subtitle>
    </div>

    @include('comun.selector-anio')

    @can(\App\Constants\Permisos::REPORTE_ANUAL_VER)
        <livewire:gestion-reportes.reporte-anual-asistencias-component :anio="$anio" wire:key="reporte{{ $anio }}" />
    @else
        <x-danger>
            No tiene permisos para ver la siguiente información.
        </x-danger>
    @endcan
    <x-loading wire:loading />
</div>