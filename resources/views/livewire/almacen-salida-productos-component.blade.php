<div class="space-y-4">
    <x-flex class="w-full justify-between">
        <x-flex>
            <x-title>
                Gestión de Almacén - Salida de {{ $destino == 'combustible' ? 'combustible' : 'insumos' }}
            </x-title>
        </x-flex>
    </x-flex>

    @include('comun.selector-mes')

    <livewire:almacen-salida-detalle-component :tipo="$destino" wire:key="{{ $mes }}.{{ $anio }}"
        :mes="$mes" :anio="$anio" />

    <x-loading wire:loading />
</div>
