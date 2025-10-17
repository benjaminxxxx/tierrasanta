<x-dialog-modal wire:model="mostrarListaPlanillaMensual" maxWidth="xl">
    <x-slot name="title">
        <x-flex>
            <x-h3>Orden Mensual de Empleados</x-h3>
            <x-button variant="success" wire:click="agregarPlanilleros">
                <i class="fa fa-plus"></i> Agregar Toda la Planilla Agraria
            </x-button>
        </x-flex>
    </x-slot>

    <x-slot name="content">
        <div>
            <x-label>
                Esta lista es vigente para el mes de {{ \Carbon\Carbon::parse($fecha)->translatedFormat('F \d\e Y') }}
            </x-label>
        </div>

        <div x-data="ordenMensualPlanilla">
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
                                <x-td x-text="index + 1" class="text-center"></x-td>
                                <x-td x-text="empleado.nombres"></x-td>
                                <x-td class="text-center">
                                    <x-flex>
                                        <button type="button" @click="moverArriba(index)" :disabled="index === 0">
                                            <i class="fa fa-arrow-up text-blue-600 hover:text-blue-800"></i>
                                        </button>
                                        <button type="button" @click="moverAbajo(index)"
                                            :disabled="index === empleados.length - 1">
                                            <i class="fa fa-arrow-down text-blue-600 hover:text-blue-800"></i>
                                        </button>
                                        <x-button variant="danger" type="button" @click="quitar(index)">
                                            <i class="fa fa-trash"></i> Quitar
                                        </x-button>
                                    </x-flex>
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
                @click="$wire.set('mostrarListaPlanillaMensual', false)">Cerrar</x-button>

            <x-button type="submit" wire:click="guardarOrdenMensualEmpleados">
                <i class="fa fa-save"></i> Guardar Orden
            </x-button>
        </x-flex>
    </x-slot>


</x-dialog-modal>
@script
<script>
    Alpine.data('ordenMensualPlanilla', () => ({
        empleados: @entangle('listaPlanilla'),
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

        quitar(index) {
            this.empleados.splice(index, 1);
        },
    }));
</script>
@endscript