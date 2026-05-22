<div class="space-y-4">

    <div>
        <x-title>
            Costos Mensual
        </x-title>
        <x-subtitle>
            Gestión de Costo Mensual
        </x-subtitle>
    </div>
    @include('comun.selector-mes')

    @can(\App\Constants\Permisos::CONTABILIDAD_COSTO_MENSUAL_VER)
        <livewire:contabilidad-costos-mensuales-detalle-component :anio="$anio" :mes="$mes"
            wire:key="{{ $anio }}-{{ $mes }}" />
    @else
        <x-danger>
            No tiene permiso para ver la siguiente información
        </x-danger>
    @endcan

    <livewire:gestion-costos.costos-mensuales-distribucion-form-component />
    <x-loading wire:loading />
</div>