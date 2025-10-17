<div>
    <x-h3>
        Resumen de labores realizadas para Planilla
    </x-h3>
    <div class="mt-3">

        @include('comun.selector-mes')
   
        <livewire:resumen-planilla-detalle-component :mes="$mes" :anio="$anio"
            wire:key="rp_{{ $mes }}_{{ $anio }}" />
    </div>


    <x-loading wire:loading />
</div>