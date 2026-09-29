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
            @include('livewire.planilla.empleado.partials.lista-opciones-adicionales')
        @endcan

    </x-flex>

    @include('livewire.planilla.empleado.partials.lista-filtro')
    @can(\App\Constants\Permisos::PERSONAL_VER)
        @include('livewire.planilla.empleado.partials.lista-tabla-planilleros')
    @endcan

    @include('livewire.planilla.empleado.partials.sueldos')

    @cannot(\App\Constants\Permisos::PERSONAL_VER)
    <x-danger>
        No tienes permiso para ver la lista de empleados.
    </x-danger>

    @endcannot

    <x-loading wire:loading />
</div>