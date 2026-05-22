<div class="space-y-4">

    <div>
        <x-title>
            Costos FDM
        </x-title>
        <x-subtitle>Estos costos se suman y se envian a COSTO OPERATIVO / MANO DE OBRA INDIRECTA</x-subtitle>
    </div>

    @include('comun.selector-mes')

    @can(\App\Constants\Permisos::CONTABILIDAD_FDM_VER)
        <livewire:fdm-costos-component :mes="$mes" :anio="$anio" wire:key="k{{ $mes }}-{{ $anio }}" />
    @else
        <x-danger>
            No tiene permiso para ver la siguiente información
        </x-danger>
    @endcan


    <x-loading wire:loading />
</div>