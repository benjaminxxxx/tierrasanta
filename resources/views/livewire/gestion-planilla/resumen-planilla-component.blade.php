<div>
    <x-title>
        Resumen de labores realizadas para Planilla
    </x-title>
    <div class="mt-3">

        @include('comun.selector-mes')
        @can(\App\Constants\Permisos::PLANILLA_RESUMEN_MENSUAL_VER)
            <livewire:gestion-planilla.resumen-planilla-detalle-component :mes="$mes" :anio="$anio"
                wire:key="rp_{{ $mes }}_{{ $anio }}" />
        @else
            <x-danger>
                No tienes permiso para ver el resumen de labores realizadas para planilla.
            </x-danger>
        @endcan

    </div>


    <x-loading wire:loading />
</div>