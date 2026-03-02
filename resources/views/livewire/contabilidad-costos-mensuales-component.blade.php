<div>

    <x-h3>
        Costos Mensual
    </x-h3>
    @include('comun.selector-mes')
    
    <livewire:contabilidad-costos-mensuales-detalle-component :anio="$anio" :mes="$mes" wire:key="{{ $anio }}-{{ $mes }}"/>
    <livewire:gestion-costos.costos-mensuales-distribucion-form-component/>
    <x-loading wire:loading />
</div>
