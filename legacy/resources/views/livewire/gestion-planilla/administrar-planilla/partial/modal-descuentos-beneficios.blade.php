<x-dialog-modal wire:model.live="mostrarDescuentosBeneficiosPlanilla">
    <x-slot name="title">
        Descuentos y Beneficios
    </x-slot>

    <x-slot name="content">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
            <x-input label="Días Laborables" wire:model="diasLaborables"
                @input="calcularTotalHoras(event.target.value)" />
            <x-input label="Total Horas" wire:model="totalHoras" />
        </div>
    </x-slot>

    <x-slot name="footer">
        <x-button variant="secondary" wire:click="$set('mostrarDescuentosBeneficiosPlanilla', false)"
            wire:loading.attr="disabled">
            Cerrar
        </x-button>
        <x-button wire:click="guardarPlanillaDatos">
            <i class="fa fa-refresh"></i> Guardar cambios
        </x-button>
        
    </x-slot>
</x-dialog-modal>