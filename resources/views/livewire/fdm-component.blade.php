<div>

    <x-title>
        Costos FDM
    </x-title>
    <x-subtitle class="mb-4">Estos costos se suman y se envian a COSTO OPERATIVO / MANO DE OBRA INDIRECTA</x-subtitle>

    @include('comun.selector-mes')

    <livewire:fdm-costos-component :mes="$mes" :anio="$anio"
        wire:key="k{{ $mes }}-{{ $anio }}" />
        
    <x-loading wire:loading />
</div>
