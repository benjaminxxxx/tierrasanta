<div>
    <x-title>
        Resumen de labores realizadas para Planilla
    </x-title>
    <div class="mt-3">

        @include('comun.selector-mes')
   
        <livewire:gestion-planilla.resumen-planilla-detalle-component :mes="$mes" :anio="$anio"
            wire:key="rp_{{ $mes }}_{{ $anio }}" />
    </div>


    <x-loading wire:loading />
</div>