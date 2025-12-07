<x-card>
    <x-flex class="justify-between">
        <div>
            <x-title>
                Kardex de Insumos
            </x-title>
            <x-subtitle>
                Crea y administra los reportes de kardex para los insumos almacenados.
            </x-subtitle>
        </div>
        <div>
            <x-button @click="$wire.dispatch('nuevoInsumoKardex')">
                <i class="fa fa-plus"></i> Crear Nuevo Kardex
            </x-button>
        </div>
    </x-flex>
    
    @include('livewire.gestion-insumos.partials.insumo-kardex-filtros')
    @include('livewire.gestion-insumos.partials.insumo-kardex-tabla')
    @include('livewire.gestion-insumos.partials.insumo-kardex-form')

    <x-loading wire:loading/>
</x-card>