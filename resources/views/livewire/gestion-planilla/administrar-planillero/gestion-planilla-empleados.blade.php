<div>
    <x-flex class="justify-between">
        <x-flex>
            <x-title>
                Administración de empleados
            </x-title>
            <x-button type="button" @click="$wire.dispatch('abrirFormularioNuevoEmpleado')">
                <i class="fa fa-plus"></i> Nuevo Empleado
            </x-button>
        </x-flex>
        @include('livewire.gestion-planilla.administrar-planillero.partials.lista-opciones-adicionales')
    </x-flex>

    @include('livewire.gestion-planilla.administrar-planillero.partials.lista-filtro')
    @include('livewire.gestion-planilla.administrar-planillero.partials.lista-tabla-planilleros')
    @include('livewire.gestion-planilla.administrar-planillero.partials.orden')
    @include('livewire.gestion-planilla.administrar-planillero.partials.sueldos')

    <x-loading wire:loading />
</div>