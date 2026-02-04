<div class="space-y-4" x-data="contratosPlanillaLista">

    <!-- Header -->
    <x-flex class="justify-between">
        <div>
            <x-title>Gestión de Contratos</x-title>
            <x-subtitle>Administra los contratos de planilla de tus empleados</x-subtitle>
        </div>
        <x-flex>

            <div x-data="{ openFileDialog() { $refs.fileContratos.click() } }">
                <x-button variant="success" type="button" @click="openFileDialog()">
                    <i class="fa fa-file-excel"></i> Importar Contratos
                </x-button>
                <input type="file" accept="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                    x-ref="fileContratos" style="display: none;" wire:model.live="fileContratos" />
            </div>
            <x-button wire:click="$dispatch('nuevoContrato')">
                <i class="fas fa-plus"></i> Nuevo Contrato
            </x-button>
        </x-flex>
    </x-flex>

    <!-- Tarjeta Principal -->
    <x-card>
        <!-- Filtros -->
        @include('livewire.gestion-planilla.partials.contratos-planilla-filtros')

        <!-- Tabla -->
        @include('livewire.gestion-planilla.partials.contratos-planilla-table')
    </x-card>

    @include('livewire.gestion-planilla.partials.contratos-planilla-detalle')
    @include('livewire.gestion-planilla.partials.contratos-planilla-finalizar-contrato')

    <x-loading wire:loading />
</div>
@script
    <script>
        Alpine.data('contratosPlanillaLista', () => ({

        }));
    </script>
@endscript
