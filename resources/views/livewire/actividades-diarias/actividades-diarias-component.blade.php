<div>
    <x-flex>
        <x-h3>
            Actividades Diarias
        </x-h3>
        <x-button @click="$wire.dispatch('crearActividadDiaria')">
            <i class="fa fa-plus"></i> Crear nueva actividad
        </x-button>
    </x-flex>

    
    <livewire:actividades-diarias.actividades-diarias-form-component/>
    <livewire:cuadrilla-asistencia-agregar-component/>
</div>
