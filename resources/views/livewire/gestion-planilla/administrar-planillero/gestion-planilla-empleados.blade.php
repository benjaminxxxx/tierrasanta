<div x-data="empleados">
    <x-flex class="justify-between">
        <x-flex>
            <x-h3>
                Administración de empleados
            </x-h3>
            <x-button type="button" @click="$wire.dispatch('abrirFormularioNuevoEmpleado')">
                <i class="fa fa-plus"></i> Nuevo Empleado
            </x-button>
        </x-flex>
        @include('livewire.gestion-planilla.administrar-planillero.partials.lista-opciones-adicionales')
    </x-flex>

    @include('livewire.gestion-planilla.administrar-planillero.partials.lista-filtro')
    @include('livewire.gestion-planilla.administrar-planillero.partials.lista-tabla')


    <x-loading wire:loading />
</div>