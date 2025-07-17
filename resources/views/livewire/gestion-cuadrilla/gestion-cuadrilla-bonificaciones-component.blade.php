<div x-data="bonificaciones_cuadrilla">
    <x-flex class="w-full justify-between">
        <x-flex class="my-3">
            <x-h3>
                Registro de Bonificaciones
            </x-h3>
        </x-flex>
        <x-button-a href="{{ route('cuadrilleros.gestion') }}">
            <i class="fa fa-arrow-left"></i> Volver a gestión de cuadrilleros
        </x-button-a>
    </x-flex>

    <div class="flex items-center justify-between mb-4">
        <!-- Botón para fecha anterior -->
        <x-button wire:click="fechaAnterior">
            <i class="fa fa-chevron-left"></i> Fecha Anterior
        </x-button>

        <!-- Input para seleccionar la fecha -->
        <x-input type="date" wire:model.live="fecha" class="text-center mx-2 !w-auto" />

        <!-- Botón para fecha posterior -->
        <x-button wire:click="fechaPosterior">
            Fecha Posterior <i class="fa fa-chevron-right"></i>
        </x-button>
    </div>

    <x-card2>
        <x-h3>
            Actividades realizadas
        </x-h3>
        <x-group-field>
            <x-select wire:model.live="actividadSeleccionada" wire:key="select_actividad_{{ $fecha }}">
                <option value="">Seleccionar Actividad</option>
                @foreach ($actividades as $actividad)
                    <option value="{{ $actividad->id }}">
                        {{ 'Campo: ' . $actividad->campo . ' - Labor: ' . $actividad->nombre_labor }}
                    </option>
                @endforeach
            </x-select>
        </x-group-field>
        <div class="grid grid-cols-1 md:grid-cols-2">
            <x-group-field>
                <div class="space-y-4">

                    <x-flex class="mt-3 mb-2">
                        <x-h3>Bonificación</x-h3>
                        <x-secondary-button @click="agregarTramo">
                            <i class="fa fa-plus"></i> Agregar Tramo
                        </x-secondary-button>
                    </x-flex>

                    <div class="overflow-x-auto">
                        <x-flex class="">
                            <x-label>La bonificacion se aplicara a partir de:</x-label>
                            <x-input-number x-model="estandarProduccion" @input="calcularBonos" step="0.1" class="!w-24"
                                placeholder="Producción" />
                            <x-label>{{ $unidades }}</x-label>
                        </x-flex>
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="">
                                <tr>
                                    <th
                                        class="px-4 py-2 text-left text-sm font-semibold text-gray-700 dark:text-gray-300">
                                        Hasta (unidades)
                                    </th>
                                    <th
                                        class="px-4 py-2 text-left text-sm font-semibold text-gray-700 dark:text-gray-300">
                                        Se paga S/.
                                    </th>
                                    <th
                                        class="px-4 py-2 text-center text-sm font-semibold text-gray-700 dark:text-gray-300">
                                        Acciones
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                <template x-for="(tramo, index) in tramos" :key="index">
                                    <tr class="">
                                        <!-- Hasta -->
                                        <td class="px-4 py-2">
                                            <x-input-number class="w-full" x-model="tramo.hasta"
                                                @input="calcularBonos" />
                                        </td>

                                        <!-- Monto -->
                                        <td class="px-4 py-2">
                                            <x-input-number class="w-full" step="0.1" x-model="tramo.monto"
                                                @input="calcularBonos" />
                                        </td>

                                        <!-- Remove button -->
                                        <td class="px-4 py-2 text-center">
                                            <x-danger-button @click="removerTramo(index)">
                                                <i class="fa fa-trash"></i>
                                            </x-danger-button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                </div>

            </x-group-field>
        </div>

        <div wire:ignore class="mt-4">
            <div x-ref="tableBonificacionesCuadrilleros"></div>
        </div>

        <x-flex class="w-full justify-end mt-4">
            <x-button @click="guardarBonificaciones">
                <i class="fa fa-save"></i> Actualizar bonificaciones
            </x-button>
        </x-flex>
    </x-card2>
    <x-loading wire:loading />
</div>
@script
<script>
    Alpine.data('bonificaciones_cuadrilla', () => ({
        hot: null,
        tramos: @entangle('tramos'),
        asistenciaCuadrilla: @json($registros),
        total_horarios: @json($total_horarios),
        estandarProduccion: @entangle('estandarProduccion'),
        unidades: @entangle('unidades'),
        init() {
            this.$nextTick(() => {
                this.initTable();
            });

            Livewire.on('actualizarTablaBonificacionesCuadrilla', (data) => {
                console.log(data);
                this.asistenciaCuadrilla = data[0];
                this.total_horarios = data[1];
                this.$nextTick(() => this.initTable());
            });
        },

        initTable() {
            if (this.hot) {
                this.hot.destroy();
            }

            const container = this.$refs.tableBonificacionesCuadrilleros;
            this.hot = new Handsontable(container, {
                data: this.asistenciaCuadrilla,
                themeName: 'ht-theme-main-dark-auto',
                colHeaders: true,
                rowHeaders: true,
                columns: this.generarColumnasDinamicas(),
                width: '100%',
                height: 'auto',
                stretchH: 'all',
                autoColumnSize: true,
                fixedColumnsLeft: 2,
                afterChange: (changes, source) => {
                    if (source === 'loadData' || !changes) return;

                    // Generar dinámicamente las columnas cantidad_1 .. cantidad_N
                    const cantidadCols = Array.from({ length: this.total_horarios }, (_, idx) => `produccion_${idx + 1}`);

                    const requiereRecalculo = changes.some(change => cantidadCols.includes(change[1]));

                    if (requiereRecalculo) {
                        this.calcularBonos();
                    }
                },
                licenseKey: 'non-commercial-and-evaluation',
            });
        },
        generarColumnasDinamicas() {
            const cols = [];

            // ➤ Trabajador
            cols.push({
                data: 'cuadrillero_nombres',
                title: 'Trabajador',
                readOnly: true,
                className: 'font-bold !text-left !bg-gray-100'
            }, {
                data: 'campo',
                title: 'Campo',
                readOnly: true,
                className: 'font-bold !text-center !bg-gray-100'
            }, {
                data: 'labor',
                title: 'Labor',
                readOnly: true,
                className: 'font-bold !text-center !bg-gray-100'
            }, {
                data: 'horarios',
                title: 'Horarios',
                readOnly: true,
                className: 'font-bold !text-center !bg-gray-100'
            });
            // ➤ Producciones según cantidad máxima
            for (let i = 1; i <= this.total_horarios; i++) {
                cols.push({
                    data: `produccion_${i}`,
                    title: `Cantidad ${i}`,
                    type: 'numeric',
                    numericFormat: { pattern: '0,0.00' },
                    allowInvalid: false,
                    className: '!text-center !text-lg'
                });
            }

            // ➤ Total Bono
            cols.push({
                data: 'total_bono',
                title: 'Total Bono',
                readOnly: true,
                type: 'numeric',
                numericFormat: { pattern: '0,0.00' },
                className: '!bg-yellow-100 !text-center font-bold'
            });

            return cols;
        },
        calcularBonos() {
            console.log('calculando bonos');
            if (!this.estandarProduccion || this.estandarProduccion <= 0) {
                // No hay bono si el estándar no está definido
                this.asistenciaCuadrilla.forEach(t => t.bono = 0);
                return;
            }

            this.asistenciaCuadrilla.forEach(trabajador => {
                // Sumatoria de todas las cantidades
                let sumaCantidad = 0;
                for (let i = 1; i <= this.total_horarios; i++) {
                    const key = `produccion_${i}`;
                    sumaCantidad += parseFloat(trabajador[key] || 0);
                }

                // Calcular excedente
                let excedente = sumaCantidad - this.estandarProduccion;
                if (excedente <= 0) {
                    trabajador.total_bono = 0;
                    return;
                }

                let bonoTotal = 0;
                let restante = excedente;

                // Ordenar tramos por hasta (opcional, seguridad)
                const tramosOrdenados = [...this.tramos].sort((a, b) => a.hasta - b.hasta);

                tramosOrdenados.forEach(tramo => {
                    const tramoHasta = parseFloat(tramo.hasta);
                    const tramoMonto = parseFloat(tramo.monto);

                    if (restante <= 0) return;

                    if (restante >= tramoHasta) {
                        bonoTotal += tramoHasta * tramoMonto;
                        restante -= tramoHasta;
                    } else {
                        bonoTotal += restante * tramoMonto;
                        restante = 0;
                    }
                });

                // Si todavía queda excedente, se paga al último tramo
                if (restante > 0 && this.tramos.length > 0) {
                    const ultimoMonto = parseFloat(this.tramos[this.tramos.length - 1].monto);
                    bonoTotal += restante * ultimoMonto;
                }

                trabajador.total_bono = bonoTotal.toFixed(2);
            });

            this.hot.render(); // actualiza la tabla
        },
        agregarTramo() {
            this.tramos.push({ hasta: '', monto: '' });
            this.calcularBonos();
        },
        removerTramo(index) {
            this.tramos.splice(index, 1);
            this.calcularBonos();
        },
        guardarBonificaciones() {
            let allData = [];
            for (let row = 0; row < this.hot.countRows(); row++) {
                const rowData = this.hot.getSourceDataAtRow(row);
                allData.push(rowData);
            }

            const filteredData = allData.filter(row => row && Object.values(row).some(cell => cell !== null && cell !== ''));
            $wire.guardarBonificaciones(filteredData);
        }
    }));

</script>
@endscript