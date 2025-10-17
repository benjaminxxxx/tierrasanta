<x-dialog-modal wire:model.live="mostrarDescuentosBeneficiosPlanilla">
    <x-slot name="title">
        Descuentos y Beneficios
    </x-slot>

    <x-slot name="content">
        <x-flex class="justify-end">
            <x-button wire:click="guardarPlanillaDatos(2)" variant="success">
                <i class="fa fa-refresh"></i> Recuperar datos desde configuración
            </x-button>
        </x-flex>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 space-y-2 mt-4">
            <x-input label="Días Laborables" wire:model="diasLaborables" size="xs"
                @input="calcularTotalHoras(event.target.value)" />
            <x-input label="Total Horas" wire:model="totalHoras" size="xs" />
            <x-input label="Factor Rem. Básica" wire:model="factorRemuneracionBasica" size="xs" />
            <x-input label="Asignación Familiar" wire:model="asignacionFamiliar" size="xs" />
            <x-input label="CTS (%)" wire:model="ctsPorcentaje" size="xs" />
            <x-input label="Gratificaciones" wire:model="gratificaciones" size="xs" />
            <x-input label="Essalud Gratificaciones" wire:model="essaludGratificaciones" size="xs" />
            <x-input label="RMV" wire:model="rmv" size="xs" />
            <x-input label="Beta 30%" wire:model="beta30" size="xs" />
            <x-input label="Essalud (%)" wire:model="essalud" size="xs" />
            <x-input label="Vida Ley" wire:model="vidaLey" size="xs" />
            <x-input label="Vida Ley (%)" wire:model="vidaLeyPorcentaje" size="xs" />
            <x-input label="Pensión SCTR" wire:model="pensionSctr" size="xs" />
            <x-input label="Pensión SCTR (%)" wire:model="pensionSctrPorcentaje" size="xs" />
            <x-input label="Essalud EPS" wire:model="essaludEps" size="xs" />
            <x-input label="Porcentaje Constante" wire:model="porcentajeConstante" size="xs" />
            <x-input label="Rem. Básica Essalud" wire:model="remBasicaEssalud" size="xs" />
        </div>
    </x-slot>

    <x-slot name="footer">
        <x-button variant="secondary" wire:click="$set('mostrarDescuentosBeneficiosPlanilla', false)"
            wire:loading.attr="disabled">
            Cerrar
        </x-button>
        <x-button wire:click="guardarPlanillaDatos(1)">
            <i class="fa fa-refresh"></i> Guardar cambios
        </x-button>
        
    </x-slot>
</x-dialog-modal>