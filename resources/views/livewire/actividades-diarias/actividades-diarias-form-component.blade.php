<div>
    <x-modal maxWidth="full" wire:model="mostrarFormularioActividadDiaria" class="overflow-y-auto">
        <div x-data="actividades_diarias">
            <div class="px-6 py-4">
                <div class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    <x-h3>
                        Agregar Nueva Actividad
                    </x-h3>
                </div>

                <div class="mt-4 text-sm text-gray-600 dark:text-gray-400">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <x-input-date wire:model="fecha" label="Seleccione una fecha" error="fecha" />
                        <x-select-campo wire:model="campoSeleccionado" />
                        <x-group-field class="col-span-2">
                            <x-label for="laborSeleccionada" value="Seleccione una labor" />
                            <x-searchable-select :options="$laboresSeleccion" search-placeholder="Selecciona una labor"
                                wire:model.live="laborSeleccionada" />
                            <x-input-error for="laborSeleccionada" />
                        </x-group-field>

                        <x-group-field class="col-span-2">
                            <div class="space-y-4">

                                <x-flex class="mt-3 mb-2">
                                    <x-h3>Horarios de Actividad</x-h3>
                                    <x-secondary-button @click="agregarHorario">
                                        <i class="fa fa-plus"></i> Agregar Horario
                                    </x-secondary-button>
                                </x-flex>

                                <div class="overflow-x-auto">
                                    <table class="min-w-full">
                                        <thead>
                                            <tr>
                                                <th
                                                    class="px-4 py-2 text-left text-sm font-semibold text-gray-700 dark:text-gray-300">
                                                    Inicio</th>
                                                <th
                                                    class="px-4 py-2 text-left text-sm font-semibold text-gray-700 dark:text-gray-300">
                                                    Fin</th>
                                                <th
                                                    class="px-4 py-2 text-center text-sm font-semibold text-gray-700 dark:text-gray-300">
                                                    Horas</th>
                                                <th
                                                    class="px-4 py-2 text-center text-sm font-semibold text-gray-700 dark:text-gray-300">
                                                    Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                            <template x-for="(horario, index) in horarios" :key="index">
                                                <tr class="">
                                                    <!-- Inicio -->
                                                    <td class="px-4 py-2">
                                                        <x-input type="time" x-model="horario.inicio"
                                                            @input="calcularHoras(index)" class="w-full" />
                                                    </td>

                                                    <!-- Fin -->
                                                    <td class="px-4 py-2">
                                                        <x-input type="time" x-model="horario.fin"
                                                            @input="calcularHoras(index)" class="w-full" />
                                                    </td>

                                                    <!-- Horas calculadas -->
                                                    <td class="px-4 py-2 text-center">
                                                        <span x-text="horario.horas.toFixed(2)"></span>
                                                    </td>

                                                    <!-- Remove button -->
                                                    <td class="px-4 py-2 text-center">
                                                        <x-danger-button @click="removerHorario(index)">
                                                            <i class="fa fa-trash"></i>
                                                        </x-danger-button>
                                                    </td>
                                                </tr>
                                            </template>
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <td colspan="2" class="px-4 py-2 text-right font-semibold">
                                                    Total Horas:
                                                </td>
                                                <td colspan="2" class="px-4 py-2 text-center font-semibold">
                                                    <span x-text="totalHoras().toFixed(2)"></span>
                                                </td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>

                            </div>

                        </x-group-field>

                        <x-group-field class="col-span-2">
                            <div class="space-y-4">

                                <x-flex class="mt-3 mb-2">
                                    <x-h3>Bonificación</x-h3>
                                    <x-secondary-button @click="agregarTramo">
                                        <i class="fa fa-plus"></i> Agregar Tramo
                                    </x-secondary-button>
                                </x-flex>

                                <div class="overflow-x-auto">
                                    <x-flex>
                                        <p>La bonificacion se aplicara a partir de:</p>
                                        <x-input-number x-model="estandarProduccion" @input="calcularBonos" step="0.1"
                                            class="!w-24" placeholder="Producción" />

                                        <p>{{ $unidades }}</p>
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

                        <x-group-field class="col-span-4">
                            <x-flex class="mt-3 mb-2 w-full">
                                <x-h3>Cuadrilleros y gestión</x-h3>
                                <x-button type="button" wire:click="agregarCuadrilleros">
                                    <i class="fa-solid fa-plus-minus"></i> Gestionar Cuadrillero
                                </x-button>
                            </x-flex>
                            <div wire:ignore>
                                <x-h3>Detalle de trabajadores</x-h3>
                                <div x-ref="tableReporteContainer"></div>

                            </div>
                        </x-group-field>
                    </div>

                    <br>
                </div>
            </div>

            <div class="flex flex-row justify-end px-6 py-4 bg-whiten dark:bg-boxdarkbase text-end gap-4">
                <x-secondary-button @click="$wire.set('mostrarFormularioActividadDiaria', false)">
                    Cancelar
                </x-secondary-button>
                <x-button @click="guardarActividadDiaria">
                    <i class="fa fa-save"></i> Guardar Actividad
                </x-button>
            </div>
        </div>
    </x-modal>
    <x-loading wire:loading />
</div>
@script
<script>
    Alpine.data('actividades_diarias', () => ({
        horarios: @entangle('horarios_actividad'),
        tramos: @entangle('tramos'),
        estandarProduccion: @entangle('estandarProduccion'),
        listeners: [],
        ingresos: [],
        seleccionados: [],
        totalVenta: '0.00',
        fechaVenta: null,
        selectedRows: [],
        grupos: @json($grupos),
        trabajadores: @json($trabajadores),
        hot: null,
        hotFuente: null,
        init() {
            if (!this.horarios || this.horarios.length === 0) {
                this.horarios = [{ inicio: '', fin: '', horas: 0 }];
            }
            this.$nextTick(() => {
                this.initTable();
            });
            this.listeners.push(

                Livewire.on('actualizarTablaCuadrilleros', (data) => {

                    this.$nextTick(() => {
                        this.trabajadores = data[0];
                        this.initTable();
                    });
                })
            );
        },
        initTable() {

            if (this.hot != null) {
                this.hot.destroy();
                this.hot = null;
            }

            const tareas = this.tareas;

            const container = this.$refs.tableReporteContainer;
            const hot = new Handsontable(container, {
                data: this.trabajadores,
                colHeaders: true,
                rowHeaders: true,
                columns: this.generarColumnasDinamicas(),
                width: '100%',
                height: 'auto',
                manualColumnResize: false,
                manualRowResize: true,
                stretchH: 'all',
                autoColumnSize: true,
                fixedColumnsLeft: 2,
                cells: function (row, col) {
                    const cellProperties = {};
                    const rowData = this.instance.getSourceDataAtRow(row);

                    if (rowData?.cosecha_encontrada === true) {
                        cellProperties.className = '!bg-lime-200';
                    }
                    if (rowData?.fusionada === true) {
                        cellProperties.className = '!bg-blue-200';
                    }

                    return cellProperties;
                },
                afterChange: (changes, source) => {
                    if (source === 'loadData' || !changes) return;

                    // Verifica si alguna columna editada es una cantidad
                    const cantidadCols = this.horarios.map((_, idx) => `cantidad_${idx + 1}`);
                    let requiereRecalculo = changes.some(change => cantidadCols.includes(change[1]));

                    if (requiereRecalculo) {
                        this.calcularBonos();
                    }
                },

                licenseKey: 'non-commercial-and-evaluation',
            });

            this.hot = hot;
        },
        generarColumnasDinamicas() {
            const cantidadColumns = this.horarios.map((_, idx) => ({
                data: `cantidad_${idx + 1}`,
                type: 'numeric',
                className: 'text-center',
                title: `Cant. ${idx + 1}`
            }));

            return [
                {
                    data: 'grupo_nombre',
                    type: 'text',
                    readOnly: true,
                    className: '!bg-gray-100',
                    title: 'Grupo'
                },
                {
                    data: 'nombres',
                    type: 'text',
                    readOnly: true,
                    className: '!bg-gray-100',
                    title: 'Trabajador'
                },
                {
                    data: 'horas',
                    type: 'numeric',
                    className: 'text-center',
                    title: 'Horas'
                },
                ...cantidadColumns,
                {
                    data: 'costo_diario',
                    type: 'numeric',
                    className: 'text-center',
                    title: 'Costo Diario'
                },
                {
                    data: 'bono',
                    type: 'numeric',
                    className: 'text-center',
                    title: 'Bono'
                },
                {
                    data: 'total',
                    type: 'numeric',
                    className: 'text-center',
                    title: 'Total'
                },
                {
                    data: 'acciones',
                    type: 'text',
                    className: 'text-center',
                    title: 'Acciones'
                }
            ];
        },
        guardarActividadDiaria() {
            let allData = [];

            // Recorre todas las filas de la tabla y obtiene los datos completos
            for (let row = 0; row < this.hot.countRows(); row++) {
                const rowData = this.hot.getSourceDataAtRow(row);
                allData.push(rowData);
            }

            // Filtra las filas vacías
            const filteredData = allData.filter(row => row && Object.values(row).some(cell => cell !==
                null && cell !== ''));

            const data = {
                datos: filteredData
            };
            $wire.dispatchSelf('storeTableDataGuardarActividadDiaria', data);
        },
        //Logica de horarios
        agregarHorario() {
            this.agregarItem('horarios', { inicio: '', fin: '', horas: 0 });
            this.ajustarDatosPorHorarios();
            this.initTable();
            this.calcularBonos();
        },

        removerHorario(index) {
            this.removerItem('horarios', index);
            this.ajustarDatosPorHorarios();
            this.initTable();
            this.calcularBonos();
        },


        calcularHoras(index) {
            const item = this.horarios[index];
            if (item.inicio && item.fin) {
                const [h1, m1] = item.inicio.split(':').map(Number);
                const [h2, m2] = item.fin.split(':').map(Number);

                let startMin = h1 * 60 + m1;
                let endMin = h2 * 60 + m2;

                let diff = endMin - startMin;
                if (diff < 0) diff += 24 * 60;

                item.horas = parseFloat((diff / 60).toFixed(2));
            } else {
                item.horas = 0;
            }
        },
        totalHoras() {
            return this.horarios.reduce((sum, item) => sum + (item.horas || 0), 0);
        },
        //Logica de tramos
        agregarTramo() {
            this.agregarItem('tramos', { hasta: '', monto: '' });
        },
        removerTramo(index) {
            this.removerItem('tramos', index);
        },
        agregarItem(campo, nuevoItem) {
            this[campo].push({ ...nuevoItem });
        },

        removerItem(campo, index) {
            this[campo].splice(index, 1);
        },
        ajustarDatosPorHorarios() {
            const total = this.horarios.length;
            this.trabajadores.forEach(trab => {
                // Asegurar
                for (let i = 1; i <= total; i++) {
                    if (!(trab[`cantidad_${i}`] >= 0)) {
                        trab[`cantidad_${i}`] = 0;
                    }
                }

                // Eliminar sobrantes
                let j = total + 1;
                while (trab.hasOwnProperty(`cantidad_${j}`)) {
                    delete trab[`cantidad_${j}`];
                    j++;
                }
            });
        },
        calcularBonos() {
            if (!this.estandarProduccion || this.estandarProduccion <= 0) {
                // No hay bono si el estándar no está definido
                this.trabajadores.forEach(t => t.bono = 0);
                return;
            }

            this.trabajadores.forEach(trabajador => {
                // Sumatoria de todas las cantidades
                let sumaCantidad = 0;
                this.horarios.forEach((_, idx) => {
                    const key = `cantidad_${idx + 1}`;
                    sumaCantidad += parseFloat(trabajador[key] || 0);
                });

                // Calcular excedente
                let excedente = sumaCantidad - this.estandarProduccion;
                if (excedente <= 0) {
                    trabajador.bono = 0;
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

                trabajador.bono = bonoTotal.toFixed(2);
            });

            this.hot.render(); // actualiza la tabla
        }
    }));
</script>
@endscript