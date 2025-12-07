<x-card>
    <x-flex class="justify-between">
        <div>
            <x-title>
                Reporte de Kardex de Insumos
            </x-title>
            <x-subtitle>
                Crea y administra los reportes de kardex para los insumos almacenados.
            </x-subtitle>
        </div>
        <div>
            <x-button @click="$wire.dispatch('nuevoInsumoKardexReporte')">
                <i class="fa fa-plus"></i> Crear Nuevo Reporte
            </x-button>
        </div>
    </x-flex>
    
    @include('livewire.gestion-insumos.partials.insumo-kardex-reporte-filtros')
    @include('livewire.gestion-insumos.partials.insumo-kardex-reporte-tabla')
    @include('livewire.gestion-insumos.partials.insumo-kardex-reporte-form')
</x-card>