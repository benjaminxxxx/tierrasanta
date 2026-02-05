<div x-data="ordenMensualPlanilla">
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
                    Esta lista es vigente para el mes de
                    {{ \Carbon\Carbon::parse($fecha)->translatedFormat('F \d\e Y') }}
                </x-label>
            </div>

            <div>
                <div class="mb-4">
                    <x-input type="text" x-model="search" placeholder="Buscar empleado por nombre..."
                        class="w-full" />
                </div>
                <div class="overflow-x-auto mt-2 h-[70vh]">
                    <x-table>
                        <x-slot name="thead">
                            <tr>
                                <x-th class="text-center">#</x-th>
                                <x-th>Empleado</x-th>
                                <x-th class="text-center">Acciones</x-th>
                            </tr>
                        </x-slot>

                        <x-slot name="tbody">
                            <template x-for="(empleado, index) in empleadosFiltrados" :key="empleado.id">
                                <x-tr>
                                    <x-td x-text="index + 1" class="text-center"></x-td>
                                    <x-td x-text="empleado.nombres"></x-td>
                                    <x-td class="text-center">
                                        <x-flex class="min-w-[163px]">
                                            <x-button type="button" @click="moverArriba(index)"
                                                x-bind:disabled="index === 0">
                                                <i class="fa fa-arrow-up"></i>
                                            </x-button>
                                            <x-button type="button" @click="moverAbajo(index)"
                                                x-bind:disabled="index === empleados.length - 1">
                                                <i class="fa fa-arrow-down"></i>
                                            </x-button>
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
</div>

@script
    <script>
        Alpine.data('ordenMensualPlanilla', () => ({
            empleados: @entangle('listaPlanilla'),
            search: '', // Nueva variable para el buscador

            // Getter que filtra la lista y mantiene el índice original para las acciones
            get empleadosFiltrados() {
                if (this.search === '') {
                    return this.empleados.map((emp, idx) => ({
                        ...emp,
                        originalIndex: idx
                    }));
                }
                return this.empleados
                    .map((emp, idx) => ({
                        ...emp,
                        originalIndex: idx
                    }))
                    .filter(emp =>
                        emp.nombres.toLowerCase().includes(this.search.toLowerCase())
                    );
            },

            moverArriba(index) {
                if (index > 0) {
                    // Usamos destructuring para intercambiar posiciones (más limpio)
                    [this.empleados[index], this.empleados[index - 1]] = [this.empleados[index - 1], this
                        .empleados[index]
                    ];

                    // Forzar actualización en Alpine/Livewire si es necesario
                    this.empleados = [...this.empleados];
                }
            },

            moverAbajo(index) {
                if (index < this.empleados.length - 1) {
                    [this.empleados[index], this.empleados[index + 1]] = [this.empleados[index + 1], this
                        .empleados[index]
                    ];

                    this.empleados = [...this.empleados];
                }
            },

            quitar(index) {
                if (confirm('¿Está seguro de quitar a este empleado?')) {
                    this.empleados.splice(index, 1);
                    this.empleados = [...this.empleados];
                }
            },
        }));
    </script>
@endscript
