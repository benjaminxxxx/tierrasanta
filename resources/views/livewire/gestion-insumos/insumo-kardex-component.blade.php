<div x-data="insumoKardexComponent">
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
    </x-card>
    <div class="mt-5" x-show="ayudaActivada">
        <x-kardex-proceso :pasoActivo="1" accionCrearKardex="nuevoInsumoKardex" />
    </div>
    <x-card class="mt-5">

        @include('livewire.gestion-insumos.partials.insumo-kardex-filtros')
        @include('livewire.gestion-insumos.partials.insumo-kardex-tabla')
        @include('livewire.gestion-insumos.partials.insumo-kardex-form')

    </x-card>

    <x-loading wire:loading />
</div>
@script
<script>
    Alpine.data('insumoKardexComponent',()=>({
        ayudaActivada:false,
    }))
</script>
@endscript