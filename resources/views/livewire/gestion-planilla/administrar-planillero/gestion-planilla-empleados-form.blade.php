<div>
    <x-dialog-modal wire:model="mostrarFormularioEmpleados" maxWidth="full">
        <x-slot name="title">
            <x-h3>
                {{ $empleadoId ? 'Editar Empleado' : 'Registro de Empleado' }}
            </x-h3>
        </x-slot>
        <x-slot name="content">
            <x-tabs default-value="datos_generales" storage-key="empleado-tab" orientation="vertical" :remember="false">
                <x-tabs-list orientation="vertical">
                    <x-tabs-trigger value="datos_generales" orientation="vertical">Datos Generales</x-tabs-trigger>
                    <x-tabs-trigger value="contrato" orientation="vertical">Contrato</x-tabs-trigger>
                    <x-tabs-trigger value="sueldos" orientation="vertical">Sueldos</x-tabs-trigger>
                    <x-tabs-trigger value="cargos" orientation="vertical">Cargos</x-tabs-trigger>
                    <x-tabs-trigger value="familiares" orientation="vertical">Derecho-Habientes</x-tabs-trigger>
                </x-tabs-list>

                <x-tabs-content value="datos_generales" orientation="vertical">
                    <form wire:submit.prevent="guardarEmpleado" id="formPlanillaEmpleado">
                        @include('livewire.gestion-planilla.administrar-planillero.partials.form-empleado')
                    </form>
                </x-tabs-content>

                <x-tabs-content value="contrato" orientation="vertical">
                    <x-placeholder-proximamente titulo="Contrato" />
                </x-tabs-content>

                <x-tabs-content value="sueldos" orientation="vertical">
                    <x-placeholder-proximamente titulo="Sueldos" />
                </x-tabs-content>

                <x-tabs-content value="cargos" orientation="vertical">
                    <x-placeholder-proximamente titulo="Cargos" />
                </x-tabs-content>

                <x-tabs-content value="familiares" orientation="vertical">
                    <x-placeholder-proximamente titulo="Derecho-Habientes" />
                </x-tabs-content>
            </x-tabs>
        </x-slot>
        <x-slot name="footer">
            <x-button variant="secondary" type="button" @click="$wire.set('mostrarFormularioEmpleados',false)">
                Cancelar
            </x-button>
            <x-button type="submit" form="formPlanillaEmpleado" class="ml-3">
                <i class="fa fa-save"></i> Guardar
            </x-button>
        </x-slot>
    </x-dialog-modal>
    <x-loading wire:loading />
</div>