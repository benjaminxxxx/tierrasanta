<div x-data="bonificacionesDual" class="space-y-5 mt-5">
    <x-card>
        <!-- Encabezado de Bonificación -->
        <x-flex>
            <x-h3>Bonificación</x-h3>
        </x-flex>

        <!-- Estándar de Producción -->
        <div class="space-y-2">
            <x-flex>
                <x-label>Estándar de producción (por 8 horas):</x-label>
                <x-input type="number" x-model="estandarProduccion" @input="calcularBonos" step="0.1" class="!w-32"
                    placeholder="Producción" />
                <x-label>{{ $unidades ?? 'KG' }}</x-label>
            </x-flex>
        </div>
    </x-card>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Tramos Por Sobreestandar -->
        <x-card>
            <x-flex class="mb-4">
                <x-h4 class="text-blue-500">Tramos por Sobreestandar</x-h4>
                <x-button variant="success" @click="agregarTramo('standard')" class="ml-auto">
                    <i class="fa fa-plus"></i> Agregar
                </x-button>
            </x-flex>

            <div class="text-xs text-muted-foreground mb-3 p-2 bg-muted rounded">
                Aplica para trabajadores que ganan a partir del excedente sobre el estándar
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <x-tr>
                            <x-th>Hasta (kg)</x-th>
                            <x-th>S/. por kg</x-th>
                            <x-th>Acción</x-th>
                        </x-tr>
                    </thead>
                    <tbody>
                        <template x-for="(tramo, index) in tramosSobreEstandar" :key="`standard-${index}`">
                            <tr>
                                <td class="px-4 py-2">
                                    <x-input type="number" class="w-full" x-model="tramo.hasta" @input="calcularBonos"
                                        step="0.1" />
                                </td>
                                <td class="px-4 py-2">
                                    <x-input type="number" class="w-full" x-model="tramo.monto" @input="calcularBonos"
                                        step="0.1" />
                                </td>
                                <td class="px-4 py-2 text-center">
                                    <x-button variant="danger" @click="removerTramo(index, 'standard')">
                                        <i class="fa fa-trash"></i>
                                    </x-button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </x-card>
        <!-- Tramos Por Destajo -->
        <x-card>
            <x-flex class="mb-4">
                <x-h4 class="text-green-600">Tramos por Destajo</x-h4>
                <x-button variant="success" @click="agregarTramo('piecework')" class="ml-auto">
                    <i class="fa fa-plus"></i> Agregar
                </x-button>
            </x-flex>

            <div class="text-xs text-muted-foreground mb-3 p-2 bg-muted rounded">
                Aplica para trabajadores que ganan por cada kg producido sin considerar estándar
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <x-tr>
                            <x-th>Hasta (kg)</x-th>
                            <x-th>S/. por kg</x-th>
                            <x-th>Acción</x-th>
                        </x-tr>
                    </thead>
                    <tbody>
                        <template x-for="(tramo, index) in tramosDestajo" :key="`piecework-${index}`">
                            <tr>
                                <td class="px-4 py-2">
                                    <x-input type="number" class="w-full" x-model="tramo.hasta" @input="calcularBonos"
                                        step="0.1" />
                                </td>
                                <td class="px-4 py-2">
                                    <x-input type="number" class="w-full" x-model="tramo.monto" @input="calcularBonos"
                                        step="0.1" />
                                </td>
                                <td class="px-4 py-2 text-center">
                                    <x-button variant="danger" @click="removerTramo(index, 'piecework')">
                                        <i class="fa fa-trash"></i>
                                    </x-button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </x-card>
    </div>

    <!-- Tabla Handsontable con Recojos -->
    <x-card wire:ignore class="mt-6">
        <x-flex class="justify-end mb-3">
            <x-button @click="agregarRecojo">
                <i class="fa fa-plus"></i> Agregar recojo
            </x-button>
            <x-button variant="danger" @click="quitarRecojo">
                <i class="fa fa-minus"></i> Quitar recojo
            </x-button>
        </x-flex>
        <div x-ref="tableBonificacionesCuadrilleros"></div>
    </x-card>

    <!-- Botón Guardar -->
    <x-inferior-derecha>
        <x-button @click="guardarBonificaciones">
            <i class="fa fa-save"></i> Actualizar bonificaciones
        </x-button>
    </x-inferior-derecha>

    <x-loading wire:loading />

</div>

@script
    <script>
        Alpine.data('bonificacionesDual', () => ({
            hot: null,
            tramosSobreEstandar: @entangle('tramosSobreEstandar'),
            tramosDestajo: @entangle('tramosDestajo'),
            recojos: @entangle('recojos'),
            tableDataBonificados: @js($tableDataBonificados),
            estandarProduccion: @entangle('estandarProduccion'),
            isDark: JSON.parse(localStorage.getItem('darkMode')),

            init() {
                this.initTable();

                $watch('darkMode', value => {
                    this.isDark = value;
                    const columns = this.generarColumnasDinamicas();
                    this.hot.updateSettings({
                        themeName: value ? 'ht-theme-main-dark' : 'ht-theme-main',
                        columns: columns
                    });
                });
            },

            initTable() {
                if (this.hot) {
                    this.hot.destroy();
                }

                const container = this.$refs.tableBonificacionesCuadrilleros;
                this.hot = new Handsontable(container, {
                    data: this.tableDataBonificados,
                    themeName: this.isDark ? 'ht-theme-main-dark' : 'ht-theme-main',
                    colHeaders: true,
                    rowHeaders: true,
                    columns: this.generarColumnasDinamicas(),
                    width: '100%',
                    height: 'auto',
                    stretchH: 'all',
                    autoColumnSize: true,
                    fixedColumnsLeft: 3,

                    afterChange: (changes, source) => {
                        if (source === 'loadData' || !changes) return;
                        this.calcularBonos();
                    },
                    licenseKey: 'non-commercial-and-evaluation',
                });
            },

            agregarRecojo() {
                this.recojos++;
                const columns = this.generarColumnasDinamicas();
                this.hot.updateSettings({
                    columns: columns
                });
            },

            quitarRecojo() {
                if (this.recojos <= 1) {
                    return;
                }
                this.recojos--;
                const columns = this.generarColumnasDinamicas();
                this.hot.updateSettings({
                    columns: columns
                });
            },

            generarColumnasDinamicas() {
                const cols = [];

                // Columnas Fijas
                cols.push({
                    data: 'tipo',
                    title: 'Tipo',
                    readOnly: true,
                    className: 'font-bold !text-left !bg-muted'
                }, {
                    data: 'nombre_trabajador',
                    title: 'Trabajador',
                    readOnly: true,
                    className: 'font-bold !text-left !bg-muted'
                }, {
                    data: 'metodo_bonificacion',
                    title: 'Método Bono',
                    type: 'dropdown',
                    source: ['Sin bonificación', 'Por sobreestandar', 'Por destajo'],
                    className: 'font-bold !text-center'
                }, {
                    data: 'campo',
                    title: 'Campo',
                    readOnly: true,
                    className: 'font-bold !text-center !bg-muted'
                }, {
                    data: 'labor',
                    title: 'Labor',
                    readOnly: true,
                    className: 'font-bold !text-center !bg-muted'
                }, {
                    data: 'horarios',
                    title: 'Horarios',
                    readOnly: true,
                    className: 'font-bold !text-center !bg-muted'
                }, {
                    data: 'rango_total_horas',
                    title: 'Rango<br/>Horas',
                    readOnly: true,
                    className: 'font-bold !text-center !bg-muted'
                });

                // Columnas Dinámicas de Producción por Recojo
                for (let i = 1; i <= this.recojos; i++) {
                    cols.push({
                        data: `produccion_${i}`,
                        title: `Recojo ${i}`,
                        type: 'numeric',
                        numericFormat: {
                            pattern: '0,0.00'
                        },
                        allowInvalid: false,
                        className: '!text-center !text-lg'
                    });
                }

                // Columnas Finales
                cols.push({
                    data: 'total_bono',
                    title: 'Total Bono',
                    readOnly: true,
                    type: 'numeric',
                    numericFormat: {
                        pattern: '0,0.00'
                    },
                    className: this.isDark ? '!bg-muted !text-center font-bold' :
                        '!bg-yellow-100 !text-center font-bold',
                }, {
                    data: 'total_horas',
                    title: 'Total Horas',
                    readOnly: true,
                    type: 'numeric',
                    numericFormat: {
                        pattern: '0,0.00'
                    },
                    className: this.isDark ? '!bg-muted !text-center font-bold' :
                        '!bg-yellow-100 !text-center font-bold',
                });

                return cols;
            },

            agregarTramo(tipoTramo) {
                if (tipoTramo === 'standard') {
                    this.tramosSobreEstandar.push({
                        hasta: '',
                        monto: ''
                    });
                } else if (tipoTramo === 'piecework') {
                    this.tramosDestajo.push({
                        hasta: '',
                        monto: ''
                    });
                }
                this.calcularBonos();
            },

            removerTramo(index, tipoTramo) {
                if (tipoTramo === 'standard') {
                    this.tramosSobreEstandar.splice(index, 1);
                } else if (tipoTramo === 'piecework') {
                    this.tramosDestajo.splice(index, 1);
                }
                this.calcularBonos();
            },

            /**
             * Calcula los bonos para todos los trabajadores según su método de bonificación individual.
             * 
             * Cada trabajador puede usar:
             * 1. Sin bonificación: bono = 0
             * 2. Por sobreestandar: usa tramosSobreEstandar
             * 3. Por destajo: usa tramosDestajo
             */
            calcularBonos() {
                const hayTramosSobreEstandar = this.tramosSobreEstandar && this.tramosSobreEstandar.length > 0;
                const hayTramosDestajo = this.tramosDestajo && this.tramosDestajo.length > 0;

                this.tableDataBonificados.forEach(trabajador => {
                    const metodo = trabajador.metodo_bonificacion || 'Sin bonificación';
                    const produccionTotal = this.calcularProduccionTotal(trabajador);

                    if (metodo === 'Sin bonificación') {
                        trabajador.total_bono = '0.00';
                    } else if (metodo === 'Por sobreestandar' && hayTramosSobreEstandar) {
                        trabajador.total_bono = this.calcularBonoPorEstandar(
                            trabajador,
                            produccionTotal,
                            this.tramosSobreEstandar
                        );
                    } else if (metodo === 'Por destajo' && hayTramosDestajo) {
                        trabajador.total_bono = this.calcularBonoPorDestajo(
                            produccionTotal,
                            this.tramosDestajo
                        );
                    } else {
                        trabajador.total_bono = '0.00';
                    }
                });

                this.hot.render();
            },

            /**
             * Calcula la producción total del trabajador sumando todos los recojos.
             */
            calcularProduccionTotal(trabajador) {
                let suma = 0;
                for (let i = 1; i <= this.recojos; i++) {
                    const key = `produccion_${i}`;
                    suma += parseFloat(trabajador[key] || 0);
                }
                return suma;
            },

            /**
             * MÉTODO: Bonificación por sobreestandar.
             * 
             * Fórmula:
             * - Estándar esperado = (estandarProduccion / 8) * horas_trabajadas
             * - Excedente = produccionTotal - estandarEsperado
             * - Bono = aplicar tramos progresivos sobre el excedente
             */
            calcularBonoPorEstandar(trabajador, produccionTotal, tramos) {
                const totalHoras = parseFloat(trabajador.rango_total_horas || 0);

                if (!this.estandarProduccion || this.estandarProduccion <= 0) {
                    return '0.00';
                }

                const estandarPorHora = this.estandarProduccion / 8;
                const estandarEsperado = estandarPorHora * totalHoras;
                const excedente = Math.max(0, produccionTotal - estandarEsperado);

                if (excedente <= 0) {
                    return '0.00';
                }

                const bonoTotal = this.aplicarTramos(excedente, tramos);
                return bonoTotal.toFixed(2);
            },

            /**
             * MÉTODO: Bonificación por destajo.
             * 
             * Pago directo por kg sin considerar estándar.
             * Aplica tramos progresivos sobre toda la producción.
             */
            calcularBonoPorDestajo(produccionTotal, tramos) {
                if (produccionTotal <= 0) {
                    return '0.00';
                }

                const bonoTotal = this.aplicarTramos(produccionTotal, tramos);
                return bonoTotal.toFixed(2);
            },

            /**
             * Aplica tramos progresivos de bonificación.
             * 
             * Los tramos se aplican acumulativamente:
             * - Primer tramo: desde 0 hasta tramo[0].hasta
             * - Segundo tramo: desde tramo[0].hasta hasta tramo[1].hasta
             * - Si queda excedente, se aplica la tarifa del último tramo
             */
            aplicarTramos(cantidad, tramos) {
                if (!tramos || tramos.length === 0) {
                    return 0;
                }

                let bonoTotal = 0;
                let restante = cantidad;

                // Ordenar tramos por 'hasta' (de menor a mayor)
                const tramosOrdenados = [...tramos].sort((a, b) =>
                    parseFloat(a.hasta) - parseFloat(b.hasta)
                );

                tramosOrdenados.forEach(tramo => {
                    const tramoHasta = parseFloat(tramo.hasta) || 0;
                    const tramoMonto = parseFloat(tramo.monto) || 0;

                    if (restante <= 0) return;

                    if (restante >= tramoHasta) {
                        bonoTotal += tramoHasta * tramoMonto;
                        restante -= tramoHasta;
                    } else {
                        bonoTotal += restante * tramoMonto;
                        restante = 0;
                    }
                });

                // Si hay excedente después de todos los tramos,
                // aplicar la tarifa del último tramo
                if (restante > 0) {
                    const ultimoMonto = parseFloat(
                        tramosOrdenados[tramosOrdenados.length - 1].monto
                    ) || 0;
                    bonoTotal += restante * ultimoMonto;
                }

                return bonoTotal;
            },

            /**
             * Guarda la bonificación en la base de datos.
             */
            guardarBonificaciones() {
                let allData = [];
                for (let row = 0; row < this.hot.countRows(); row++) {
                    const rowData = this.hot.getSourceDataAtRow(row);
                    allData.push(rowData);
                }

                const filteredData = allData.filter(row =>
                    row && Object.values(row).some(cell => cell !== null && cell !== '')
                );

                $wire.guardarBonificaciones(filteredData);
            }
        }));
    </script>
@endscript
