<div class="space-y-4" x-data="contratosPlanillaLista">
    <x-breadcrumb :items="$breadcrumb" />
    <!-- Header -->
    <x-flex class="justify-between">
        <div>

            <x-title>Gestión de Contratos</x-title>
            <x-subtitle>Administra los contratos de planilla de tus empleados</x-subtitle>
        </div>
        <x-flex>

            {{-- La carga masiva de contratos se hace con el Excel de empleados (Inicio → Archivos) --}}

            <x-button wire:click="$dispatch('nuevoContrato')">
                <i class="fas fa-plus"></i> Nuevo Contrato
            </x-button>
        </x-flex>
    </x-flex>

    <!-- Tarjeta Principal -->
    <x-card>
        <!-- Filtros -->
        @include('livewire.planilla.empleado.partials.contratos-planilla-filtros')

        <!-- Tabla -->
        @include('livewire.planilla.empleado.partials.contratos-planilla-table')
    </x-card>

    @include('livewire.planilla.empleado.partials.contratos-planilla-detalle')
    @include('livewire.planilla.empleado.partials.contratos-planilla-finalizar-contrato')

    <x-loading wire:loading />
</div>
@script
<script>
    Alpine.data('contratosPlanillaLista', () => ({

    }));
</script>
@endscript