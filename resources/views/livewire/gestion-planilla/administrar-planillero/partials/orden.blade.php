<x-dialog-modal wire:model="mostrarFormularioOrdenEmpleados" maxWidth="lg">
    <x-slot name="title">
        <x-h3>Orden de Empleados</x-h3>
    </x-slot>

    <x-slot name="content">
        <div x-data="ordenarEmpleados">
            <div class="grid grid-cols-12 gap-2 mb-4 p-3 rounded-lg border dark:border-gray-600">
                <div class="col-span-8 relative">
                    <x-label>Buscar Empleado</x-label>
                    <x-input type="text" class="w-full text-sm"
                        placeholder="Escriba apellido o nombre..." x-model="search"
                        @keydown.arrow-down.prevent="navigateResults(1)" @keydown.arrow-up.prevent="navigateResults(-1)"
                        @keydown.enter.prevent="selectCurrent()" x-ref="searchInput" />

                    <template x-if="search.length > 1 && filteredResults.length > 0">
                        <ul
                            class="absolute z-50 w-full bg-white border rounded shadow-lg mt-1 max-h-40 overflow-y-auto dark:bg-gray-900 dark:border-gray-800">
                            <template x-for="(res, i) in filteredResults" :key="res.id">
                                <li class="px-3 py-2 text-sm cursor-pointer"
                                    :class="i === selectedIndex ? 'bg-indigo-600 text-white' : 'hover:bg-gray-100 dark:hover:bg-indigo-500 dark:hover:text-white'"
                                    @click="selectEmployee(res)"
                                    x-text="res.apellido_paterno + ' ' + res.apellido_materno + ', ' + res.nombres">
                                </li>
                            </template>
                        </ul>
                    </template>
                </div>

                <div class="col-span-4">
                    <x-label>Nueva Posición</x-label>
                    <x-input type="number" class="w-full text-sm" x-model="newPosition"
                        x-ref="positionInput" @keydown.enter.prevent="applyNewOrder()" x-bind:disabled="!selectedEmployeeId" />
                </div>

                <div class="col-span-12" x-show="selectedEmployeeId">
                    <p class="text-[10px] text-blue-600 font-bold">
                        Seleccionado: <span x-text="selectedEmployeeName"></span>
                    </p>
                </div>
            </div>

            <div class="overflow-y-auto mt-2 max-h-96 border rounded-lg dark:border-gray-600">
                <x-table>
                    <x-slot name="thead">
                        <tr class="sticky top-0 bg-white z-10 dark:bg-gray-600">
                            <x-th class="text-center">#</x-th>
                            <x-th>Empleado</x-th>
                            <x-th class="text-center">Acciones</x-th>
                        </tr>
                    </x-slot>

                    <x-slot name="tbody">
                        <template x-for="(empleado, index) in empleados" :key="empleado.id">
                            <x-tr x-bind:class="selectedEmployeeId === empleado . id
                                ? 'bg-yellow-50 border-l-4 border-l-yellow-400'
                                : ''">
                                <x-td x-text="index + 1" class="text-center"></x-td>
                                <x-td
                                    x-text="empleado.apellido_paterno + ' ' + empleado.apellido_materno + ', ' + empleado.nombres"></x-td>
                                <x-td class="text-center">
                                    <button type="button" @click="moverArriba(index)" :disabled="index === 0">
                                        <i class="fa fa-arrow-up text-blue-600"></i>
                                    </button>
                                    <button type="button" @click="moverAbajo(index)"
                                        :disabled="index === empleados.length - 1">
                                        <i class="fa fa-arrow-down text-blue-600"></i>
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
            search: '',
            newPosition: '',
            selectedIndex: -1, // Para navegar en los resultados de búsqueda
            selectedEmployeeId: null,
            selectedEmployeeName: '',

            // 1. Filtrar empleados según el input de búsqueda
            get filteredResults() {
                if (this.search.length < 2) return [];
                return this.empleados.filter(e => {
                    const fullName = `${e.apellido_paterno} ${e.apellido_materno} ${e.nombres}`
                        .toLowerCase();
                    return fullName.includes(this.search.toLowerCase());
                }).slice(0, 5); // Limitar a 5 sugerencias
            },

            // 2. Navegar sugerencias con flechas
            navigateResults(direction) {
                if (this.filteredResults.length === 0) return;
                this.selectedIndex = (this.selectedIndex + direction + this.filteredResults.length) % this
                    .filteredResults.length;
            },

            // 3. Seleccionar con Enter
            selectCurrent() {
                if (this.selectedIndex > -1 && this.filteredResults[this.selectedIndex]) {
                    this.selectEmployee(this.filteredResults[this.selectedIndex]);
                }
            },

            // 4. Lógica de selección
            selectEmployee(emp) {
                this.selectedEmployeeId = emp.id;
                this.selectedEmployeeName = `${emp.apellido_paterno}, ${emp.nombres}`;
                this.search = '';
                this.selectedIndex = -1;

                // Pasar el foco al input de posición
                this.$nextTick(() => this.$refs.positionInput.focus());
            },

            // 5. Mover a una posición específica (Lógica de Enter en el segundo input)
            applyNewOrder() {
                const targetPos = parseInt(this.newPosition) - 1;
                if (isNaN(targetPos) || targetPos < 0 || targetPos >= this.empleados.length) {
                    alert('Posición inválida');
                    return;
                }

                const currentIndex = this.empleados.findIndex(e => e.id === this.selectedEmployeeId);
                if (currentIndex !== -1) {
                    // Remover de la posición actual e insertar en la nueva
                    const element = this.empleados.splice(currentIndex, 1)[0];
                    this.empleados.splice(targetPos, 0, element);

                    // Resetear y volver al buscador
                    this.newPosition = '';
                    this.selectedEmployeeId = null;
                    this.$nextTick(() => this.$refs.searchInput.focus());
                }
            },

            moverArriba(index) {
                if (index > 0) {
                    const arr = [...this.empleados];
                    [arr[index], arr[index - 1]] = [arr[index - 1], arr[index]];
                    this.empleados = arr;
                }
            },

            moverAbajo(index) {
                if (index < this.empleados.length - 1) {
                    const arr = [...this.empleados];
                    [arr[index], arr[index + 1]] = [arr[index + 1], arr[index]];
                    this.empleados = arr;
                }
            }
        }));
    </script>
@endscript
