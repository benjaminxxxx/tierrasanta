<div x-data="ordenMensualPlanilla">
    <x-dialog-modal wire:model="mostrarListaPlanillaMensual" maxWidth="2xl">
        <x-slot name="title">
            <x-flex class="justify-between">
                <x-title>Orden de Empleados</x-title>

                <x-button variant="secondary" wire:click="agregarPlanilleros">
                    <i class="fa fa-sync"></i> Actualizar Contratados
                </x-button>

            </x-flex>
        </x-slot>

        <x-slot name="content">
            <div>
                <x-label>
                    Esta lista es vigente para el mes de
                    <span class="font-semibold">
                        {{ \Carbon\Carbon::parse($fecha)->translatedFormat('F \d\e Y') }}
                    </span>
                </x-label>
            </div>

            <!-- Buscador -->
            <div class="mb-4 mt-4">
                <x-input type="text" x-model="search" placeholder="Buscar empleado por nombre..." class="w-full" />
            </div>

            <!-- Tabla de empleados -->
            <div class="overflow-x-auto mt-4 max-h-[40vh]">
                <x-table class="w-full">
                    <x-slot name="thead">
                        <x-tr>
                            <x-th>#</x-th>
                            <x-th>Empleado</x-th>
                            <x-th>Acciones</x-th>
                        </x-tr>
                    </x-slot>
                    <x-slot name="tbody">
                        <template x-for="(empleado, index) in empleadosFiltrados" :key="empleado.id">
                            <x-tr
                                x-bind:class="{
                                    'bg-amber-50 dark:bg-amber-900/20 border-l-4 border-amber-500': empleado.orden !== empleado.ordenOriginal
                                }"
                                class="hover:bg-muted">

                                <!-- Número de orden -->
                                <x-td>
                                    <x-button @click="abrirDialog(empleado.id)" variant="success"
                                        x-bind:title="`Posición actual: ${empleado.orden}`">
                                        <span x-text="empleado.orden"></span>
                                    </x-button>
                                </x-td>

                                <!-- Nombre del empleado -->
                                <x-td class="text-left">
                                    <p class="font-semibold text-left" x-text="empleado.nombres"></p>
                                    <template x-if="empleado.orden !== empleado.ordenOriginal">
                                        <p class="text-xs text-amber-600 dark:text-amber-400 mt-1">
                                            Cambió de posición <span x-text="empleado.ordenOriginal"></span> →
                                            <span x-text="empleado.orden"></span>
                                        </p>
                                    </template>
                                </x-td>

                                <!-- Botones de control rápido -->
                                <x-td>
                                    <div class="flex gap-2 justify-center">
                                        <x-button
                                            @click="moverArriba(empleado.id)"
                                            x-bind:disabled="empleado.orden === 1"
                                            title="Mover una posición hacia arriba">
                                            <i class="fa fa-arrow-up text-sm"></i>
                                        </x-button>
                                        <x-button
                                            @click="moverAbajo(empleado.id)"
                                            x-bind:disabled="empleado.orden === totalEmpleados"
                                            title="Mover una posición hacia abajo">
                                            <i class="fa fa-arrow-down text-sm"></i>
                                        </x-button>
                                    </div>
                                </x-td>
                            </x-tr>
                        </template>
                    </x-slot>
                </x-table>
            </div>

            <!-- Resumen de cambios -->
            <template x-if="cambiosRealizados.length > 0">
                <div class="mt-6 p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg">
                    <h4 class="font-semibold text-amber-900 dark:text-amber-200 mb-2">Cambios realizados:</h4>
                    <ul class="space-y-1">
                        <template x-for="cambio in cambiosRealizados" :key="cambio.id">
                            <li class="text-sm text-amber-800 dark:text-amber-300">
                                <strong x-text="cambio.nombre"></strong>:
                                <span x-text="cambio.ordenOriginal"></span> → <span x-text="cambio.orden"></span>
                            </li>
                        </template>
                    </ul>
                </div>
            </template>
        </x-slot>

        <x-slot name="footer">
            <x-flex>
                <x-button type="button" variant="secondary"
                    @click="$wire.set('mostrarListaPlanillaMensual', false)">Cerrar</x-button>

                <x-button type="submit" wire:click="guardarOrdenMensualEmpleados">
                    <i class="fa fa-save"></i>
                    Guardar Orden
                    <template x-if="cambiosRealizados.length > 0">
                        <span x-text="`(${cambiosRealizados.length})`"></span>
                    </template>
                </x-button>
            </x-flex>
        </x-slot>
    </x-dialog-modal>

    {{-- 
        Modal secundario: usa x-show en lugar de wire:model para evitar
        conflictos entre Livewire y Alpine en el mismo estado.
    --}}
    <div x-show="dialogOpen" x-cloak>
        <x-dialog-modal wire:model="dialogOpen" maxWidth="lg">
            <x-slot name="title">
                Cambiar posición
            </x-slot>

            <x-slot name="content">
                <template x-if="empleadoActual">
                    <div class="mb-6 p-3 bg-muted text-muted-foreground rounded text-sm">
                        <p>Empleado:</p>
                        <p class="font-semibold" x-text="empleadoActual.nombres"></p>
                    </div>
                </template>

                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Nueva posición (1 - <span x-text="totalEmpleados"></span>)
                    </label>
                    <x-input
                        type="number"
                        x-model.number="nuevoOrden"
                        @keydown.enter="confirmarCambio()"
                        @keydown.escape="cerrarDialog()"
                        :min="1"
                        x-bind:max="totalEmpleados"
                        class="font-bold text-center"
                        size="lg"
                        placeholder="Número de posición"
                        x-ref="inputModal" />
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                        Presiona Enter para confirmar o Esc para cancelar
                    </p>
                </div>
            </x-slot>

            <x-slot name="footer">
                <x-button @click="cerrarDialog()" variant="secondary">Cancelar</x-button>
                <x-button @click="confirmarCambio()">Confirmar</x-button>
            </x-slot>
        </x-dialog-modal>
    </div>
</div>

@script
<script>
    Alpine.data('ordenMensualPlanilla', () => ({
        empleados: @entangle('listaPlanilla'),
        search: '',
        dialogOpen: @entangle('dialogOpen'),
        empleadoActualId: null,
        nuevoOrden: null,

        // ─── Computed: total dinámico ───────────────────────────────────────
        get totalEmpleados() {
            return this.empleados.length;
        },

        // ─── Computed: empleado seleccionado en modal ───────────────────────
        get empleadoActual() {
            return this.empleados.find(e => e.id == this.empleadoActualId) ?? null;
        },

        // ─── Computed: lista filtrada Y ORDENADA por la propiedad "orden" ───
        // FIX PRINCIPAL: sin el .sort() los botones arriba/abajo modifican
        // la propiedad pero la fila no se mueve visualmente en la tabla.
        get empleadosFiltrados() {
            const lista = this.search.trim()
                ? this.empleados.filter(emp =>
                    emp.nombres.toLowerCase().includes(this.search.toLowerCase())
                )
                : [...this.empleados];

            return lista.sort((a, b) => a.orden - b.orden);
        },

        // ─── Computed: cambios pendientes de guardar ────────────────────────
        get cambiosRealizados() {
            return this.empleados
                .filter(emp => emp.orden !== emp.ordenOriginal)
                .map(emp => ({
                    id: emp.id,
                    nombre: emp.nombres,
                    orden: emp.orden,
                    ordenOriginal: emp.ordenOriginal,
                }));
        },

        // ─── Modal: abrir ───────────────────────────────────────────────────
        abrirDialog(empleadoId) {
            const empleado = this.empleados.find(e => e.id == empleadoId);
            if (!empleado) return;

            this.empleadoActualId = empleadoId;
            this.nuevoOrden = empleado.orden;
            this.dialogOpen = true;

            this.$nextTick(() => {
                this.$refs.inputModal?.focus();
                this.$refs.inputModal?.select();
            });
        },

        // ─── Modal: cerrar ──────────────────────────────────────────────────
        cerrarDialog() {
            this.dialogOpen = false;
            this.empleadoActualId = null;
            this.nuevoOrden = null;
        },

        // ─── Modal: confirmar ───────────────────────────────────────────────
        confirmarCambio() {
            if (!this.empleadoActualId || !this.nuevoOrden) {
                this.cerrarDialog();
                return;
            }
            this.cambiarOrdenEmpleado(this.empleadoActualId, this.nuevoOrden);
            this.cerrarDialog();
        },

        // ─── Lógica: mover a posición arbitraria ────────────────────────────
        cambiarOrdenEmpleado(empleadoId, nuevoOrden) {
            nuevoOrden = parseInt(nuevoOrden);

            if (nuevoOrden < 1 || nuevoOrden > this.totalEmpleados) return;

            const empleado = this.empleados.find(e => e.id == empleadoId);
            if (!empleado) return;

            const ordenActual = empleado.orden;
            if (nuevoOrden === ordenActual) return;

            if (nuevoOrden > ordenActual) {
                // Mueve hacia abajo: compacta los de en medio hacia arriba
                this.empleados.forEach(emp => {
                    if (emp.orden > ordenActual && emp.orden <= nuevoOrden) {
                        emp.orden--;
                    }
                });
            } else {
                // Mueve hacia arriba: compacta los de en medio hacia abajo
                this.empleados.forEach(emp => {
                    if (emp.orden >= nuevoOrden && emp.orden < ordenActual) {
                        emp.orden++;
                    }
                });
            }

            empleado.orden = nuevoOrden;
        },

        // ─── Lógica: mover una posición arriba ──────────────────────────────
        // FIX: antes hacía el swap pero `empleadosFiltrados` no ordenaba,
        // así que la fila no subía visualmente. Con el .sort() en el getter
        // ahora funciona correctamente.
        moverArriba(empleadoId) {
            const empleado = this.empleados.find(e => e.id == empleadoId);
            if (!empleado || empleado.orden === 1) return;

            const anterior = this.empleados.find(e => e.orden === empleado.orden - 1);
            if (!anterior) return;

            anterior.orden++;
            empleado.orden--;
        },

        // ─── Lógica: mover una posición abajo ───────────────────────────────
        moverAbajo(empleadoId) {
            const empleado = this.empleados.find(e => e.id == empleadoId);
            if (!empleado || empleado.orden === this.totalEmpleados) return;

            const siguiente = this.empleados.find(e => e.orden === empleado.orden + 1);
            if (!siguiente) return;

            siguiente.orden--;
            empleado.orden++;
        },
    }));
</script>
@endscript