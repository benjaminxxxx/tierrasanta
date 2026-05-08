<div>
    <x-flex class="justify-between">
        <x-flex>
            <x-title>
                Administración de empleados
            </x-title>
            @can('Planilla Empleados Crear Empleado')
                <x-button type="button" @click="$wire.dispatch('abrirFormularioNuevoEmpleado')">
                    <i class="fa fa-plus"></i> Nuevo Empleado
                </x-button>
            @endcan
        </x-flex>
        @can('Planilla Empleados Gestionar Opciones')
             @include('livewire.gestion-planilla.administrar-planillero.partials.lista-opciones-adicionales')
        @endcan
       
    </x-flex>

    @include('livewire.gestion-planilla.administrar-planillero.partials.lista-filtro')
    @include('livewire.gestion-planilla.administrar-planillero.partials.lista-tabla-planilleros')
    @include('livewire.gestion-planilla.administrar-planillero.partials.sueldos')

    <x-loading wire:loading />
</div>