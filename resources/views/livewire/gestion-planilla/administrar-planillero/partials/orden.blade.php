<x-dialog-modal wire:model="mostrarFormularioOrdenEmpleados" maxWidth="lg">
    <x-slot name="title">
        <x-h3>Orden de Empleados</x-h3>
    </x-slot>

    <x-slot name="content">
        <div x-data="ordenarEmpleados">
            <div class="overflow-x-auto mt-2">
                <x-table>
                    <x-slot name="thead">
                        <tr>
                            <x-th class="text-center">#</x-th>
                            <x-th>Empleado</x-th>
                            <x-th class="text-center">Acciones</x-th>
                        </tr>
                    </x-slot>

                    <x-slot name="tbody">
                        <template x-for="(empleado, index) in empleados" :key="empleado.id">
                            <x-tr>
                                <x-td x-text="index + 1"  class="text-center"></x-td>
                                <x-td x-text="empleado.nombres"></x-td>
                                <x-td  class="text-center">
                                    <button type="button" @click="moverArriba(index)" :disabled="index === 0">
                                        <i class="fa fa-arrow-up text-blue-600 hover:text-blue-800"></i>
                                    </button>
                                    <button type="button" @click="moverAbajo(index)"
                                        :disabled="index === empleados.length - 1">
                                        <i class="fa fa-arrow-down text-blue-600 hover:text-blue-800"></i>
                                    </button>
                                </x-td>
                            </x-tr>
                        </template>
                    </x-slot>
                </x-table>
            </div>
        </div>
    </x-slot>

    <x-slot name="footer">
        <x-flex>
            <x-button type="button" variant="secondary"
                @click="$wire.set('mostrarFormularioOrdenEmpleados', false)">Cerrar</x-button>

            <x-button type="submit" wire:click="guardarOrdenEmpleados">
                <i class="fa fa-save"></i> Guardar Orden
            </x-button>
        </x-flex>
    </x-slot>


</x-dialog-modal>
@script
<script>
    Alpine.data('ordenarEmpleados', () => ({
        empleados: @entangle('empleadosOrdenados'),
        init() {

        },

        moverArriba(index) {
            if (index > 0) {
                const temp = this.empleados[index];
                this.empleados[index] = this.empleados[index - 1];
                this.empleados[index - 1] = temp;
            }
        },

        moverAbajo(index) {
            if (index < this.empleados.length - 1) {
                const temp = this.empleados[index];
                this.empleados[index] = this.empleados[index + 1];
                this.empleados[index + 1] = temp;
            }
        },
    }));
</script>
@endscript