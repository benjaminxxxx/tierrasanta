<div class="space-y-4">
    <x-flex class="w-full justify-between">
        <x-flex>
            <x-title>
                Gestión de Almacén - Salida de {{ $destino == 'combustible' ? 'combustible' : 'insumos' }}
            </x-title>
        {{-- -<x-button type="button"
                @click="$wire.dispatch('nuevoRegistro',{mes:{{ $mes }},anio:{{ $anio }}})"
                class="w-full md:w-auto ">
                <i class="fa fa-plus"></i> Nueva Salida
            </x-button> --}}
        </x-flex>
        @if ($destino == 'combustible')
            <x-button type="button" @click="$wire.dispatch('verStock',{tipo:'combustible'})" class="w-full md:w-auto ">
                <i class="fa fa-eye"></i> Ver Stock de Combustible
            </x-button>
        @else
            <x-button type="button" @click="$wire.dispatch('verStock')" class="w-full md:w-auto ">
                <i class="fa fa-eye"></i> Ver Stock de Productos
            </x-button>
        @endif
    </x-flex>

    @include('comun.selector-mes')

    <livewire:almacen-salida-detalle-component :tipo="$destino" wire:key="{{ $mes }}.{{ $anio }}"
        :mes="$mes" :anio="$anio" />

    <x-loading wire:loading />
</div>
