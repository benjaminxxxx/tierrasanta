<div>
    <x-dialog-modal wire:model.live="mostrarFormularioEvalInfestacion">
        <x-slot name="title">
            Editar Infestación
        </x-slot>

        <x-slot name="content">
            <form wire:submit="guardarEvaluacionInfestacion" id="form-eval-infestacion" class="space-y-4">
                <x-input type="date" label="Fecha Infestación" wire:model="infestacion_fecha" />
                <x-input type="date" label="Fecha recojo y vaciado de infestadores" wire:model="infestacion_fecha_recojo_vaciado_infestadores" />
                <x-input type="date" label="Fecha colocación de malla" wire:model="infestacion_fecha_colocacion_malla" />
                <x-input type="date" label="Fecha retiro de malla" wire:model="infestacion_fecha_retiro_malla" />
            </form>
        </x-slot>

        <x-slot name="footer">
            <x-button variant="secondary" wire:click="$set('mostrarFormularioEvalInfestacion', false)" wire:loading.attr="disabled">
                Cerrar
            </x-button>
            <x-button class="ml-2" wire:click="guardarEvaluacionInfestacion" form="form-eval-infestacion" type="submit" wire:loading.attr="disabled">
                <i class="fa fa-save"></i> Guardar Cambios
            </x-button>
        </x-slot>
    </x-dialog-modal>
</div>