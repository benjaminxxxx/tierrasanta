<div>
    <x-dialog-modal wire:model.live="mostrarFormularioEvalReinfestacion">
        <x-slot name="title">
            Editar Reinfestación
        </x-slot>

        <x-slot name="content">
            <form wire:submit="guardarEvaluacionReinfestacion" id="form-eval-reinfestacion" class="space-y-4">

                <x-input type="date" label="Fecha Reinfestación" 
                    wire:model="reinfestacion_fecha" />

                <x-input type="date" label="Fecha recojo y vaciado de infestadores" 
                    wire:model="reinfestacion_fecha_recojo_vaciado_infestadores" />

                <x-input type="date" label="Fecha colocación de malla"
                    wire:model="reinfestacion_fecha_colocacion_malla" />

                <x-input type="date" label="Fecha retiro de malla"
                    wire:model="reinfestacion_fecha_retiro_malla" />

            </form>
        </x-slot>

        <x-slot name="footer">
            <x-button variant="secondary" 
                wire:click="$set('mostrarFormularioEvalReinfestacion', false)" 
                wire:loading.attr="disabled">
                Cerrar
            </x-button>

            <x-button class="ml-2"
                wire:click="guardarEvaluacionReinfestacion"
                form="form-eval-reinfestacion"
                type="submit"
                wire:loading.attr="disabled">
                <i class="fa fa-save"></i> Guardar Cambios
            </x-button>
        </x-slot>
    </x-dialog-modal>
</div>
