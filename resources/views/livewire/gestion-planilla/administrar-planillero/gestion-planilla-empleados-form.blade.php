<div>
    <x-dialog-modal wire:model="mostrarFormularioEmpleados" maxWidth="lg">
        <x-slot name="title">
            <x-h3>
                Registro de Empleado
            </x-h3>
        </x-slot>
        <x-slot name="content">
            <form wire:submit.prevent="guardarEmpleado" id="formPlanillaEmpleado">
                @include('livewire.gestion-planilla.administrar-planillero.partials.form-empleado')
            </form>
        </x-slot>
        <x-slot name="footer">
            <x-button variant="secondary" type="button" @click="$wire.set('mostrarFormularioEmpleados',false)" >
                Cancelar
            </x-button>
            <x-button type="submit" form="formPlanillaEmpleado" class="ml-3">
                <i class="fa fa-save"></i> Guardar
            </x-button>
        </x-slot>
    </x-dialog-modal>
    <x-loading wire:loading />
</div>