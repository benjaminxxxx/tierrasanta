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

            @can(\App\Constants\Permisos::INSUMO_KARDEX_REPORTE_CREAR)
                <x-button @click="$wire.dispatch('nuevoInsumoKardexReporte')">
                    <i class="fa fa-plus"></i> Crear Nuevo Reporte
                </x-button>
            @else
                <x-danger>
                    No tiene autorización para crear reportes.
                </x-danger>
            @endcan
        </div>
    </x-flex>
    @include('livewire.gestion-insumos.partials.insumo-kardex-reporte-filtros')
    @include('livewire.gestion-insumos.partials.insumo-kardex-reporte-tabla')
    @include('livewire.gestion-insumos.partials.insumo-kardex-reporte-form')


</x-card>