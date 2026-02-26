<div x-data="bonificacionesDetalle">
    <div class="space-y-4 max-w-[34rem]">

        <x-flex>
            <x-h3>Bonificación</x-h3>
            <x-button @click="agregarTramo">
                <i class="fa fa-plus"></i> Agregar Tramo
            </x-button>
        </x-flex>

        <div class="overflow-x-auto mt-3">
            <x-flex class="">
                <x-label>La bonificacion se aplicara a partir de:</x-label>
                <x-input type="number" x-model="estandarProduccion" @input="calcularBonos" step="0.1" class="!w-24"
                    placeholder="Producción" />
                <x-label>{{ $unidades }}</x-label>
            </x-flex>
            <table class="min-w-full">
                <thead class="">
                    <x-tr>
                        <x-th>
                            Hasta (unidades)
                        </x-th>
                        <x-th>
                            Se paga S/.
                        </x-th>
                        <x-th>
                            Acciones
                        </x-th>
                    </x-tr>
                </thead>
                <tbody class="">
                    <template x-for="(tramo, index) in tramos" :key="index">
                        <tr class="">
                            <!-- Hasta -->
                            <td class="px-4 py-2">
                                <x-input type="number" class="w-full" x-model="tramo.hasta" @input="calcularBonos" />
                            </td>

                            <!-- Monto -->
                            <td class="px-4 py-2">
                                <x-input type="number" class="w-full" step="0.1" x-model="tramo.monto"
                                    @input="calcularBonos" />
                            </td>

                            <!-- Remove button -->
                            <td class="px-4 py-2 text-center">
                                <x-button variant="danger" @click="removerTramo(index)">
                                    <i class="fa fa-trash"></i>
                                </x-button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

    </div>

    <div wire:ignore class="mt-4">
        <x-flex class="justify-end mb-3">
            <x-button @click="agregarRecojo">
                <i class="fa fa-plus"></i> Agregar recojo
            </x-button>
            <x-button variant="danger" @click="quitarRecojo">
                <i class="fa fa-minus"></i> Quitar recojo
            </x-button>
        </x-flex>
        <div x-ref="tableBonificacionesCuadrilleros"></div>
    </div>

    <x-flex class="w-full justify-end mt-4">
        <x-button @click="guardarBonificaciones">
            <i class="fa fa-save"></i> Actualizar bonificaciones
        </x-button>
    </x-flex>

    <x-loading wire:loading />

</div>
@script
    <script>
        Alpine.data('bonificacionesDetalle', () => ({
            hot: null,
            tramos: @entangle('tramos'),
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
                    fixedColumnsLeft: 2,

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

                // ➤ Trabajador
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
                // ➤ Producciones según cantidad máxima
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

                // ➤ Total Bono
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
            agregarTramo() {
                this.tramos.push({
                    hasta: '',
                    monto: ''
                });
                this.calcularBonos();
            },
            removerTramo(index) {
                this.tramos.splice(index, 1);
                this.calcularBonos();
            },
            
            /**
             * Calcula los bonos para los trabajadores según el tipo de bonificación configurado.
             * 
             * Soporta dos métodos de cálculo:
             * 1. BONIFICACIÓN POR ESTÁNDAR DE PRODUCCIÓN:
             *    - Requiere: estandarProduccion > 0
             *    - Calcula excedente sobre el estándar esperado (kg/hora * horas trabajadas)
             *    - Aplica tramos progresivos sobre el excedente
             * 
             * 2. BONIFICACIÓN POR DESTAJO/PRODUCCIÓN DIRECTA:
             *    - Requiere: tramos configurados pero sin estándar (o estándar = 0)
             *    - Paga directamente según la producción total
             *    - Aplica tramos progresivos sobre toda la producción
             * 
             * @returns {void}
             */
            calcularBonos() {
                const hayEstandar = this.estandarProduccion && this.estandarProduccion > 0;
                const hayTramos = this.tramos && this.tramos.length > 0;

                // Si no hay ni estándar ni tramos, no hay bonos
                if (!hayEstandar && !hayTramos) {
                    this.resetearBonos();
                    return;
                }

                this.tableDataBonificados.forEach(trabajador => {
                    const sumaCantidad = this.calcularProduccionTotal(trabajador);

                    if (hayEstandar) {
                        // MÉTODO 1: Bonificación por excedente sobre estándar
                        trabajador.total_bono = this.calcularBonoPorEstandar(trabajador, sumaCantidad);
                    } else {
                        // MÉTODO 2: Bonificación por destajo (producción directa)
                        trabajador.total_bono = this.calcularBonoPorDestajo(sumaCantidad);
                    }
                });

                // Actualizar visualización de la tabla
                this.hot.render();
            },

            /**
             * Calcula la producción total del trabajador sumando todos sus recojos.
             * 
             * @param {Object} trabajador - Objeto con datos del trabajador
             * @returns {number} - Suma total de producción en kg
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
             * MÉTODO 1: Calcula bono basado en excedente sobre estándar de producción.
             * 
             * Fórmula:
             * - Estándar esperado = (estándar/8 horas) * horas_trabajadas
             * - Excedente = producción_total - estándar_esperado
             * - Bono = aplicar tramos progresivos sobre el excedente
             * 
             * @param {Object} trabajador - Objeto con datos del trabajador
             * @param {number} produccionTotal - Kg totales producidos
             * @returns {string} - Monto del bono formateado con 2 decimales
             */
            calcularBonoPorEstandar(trabajador, produccionTotal) {
                const totalHorasTrabajadas = parseFloat(trabajador.total_horas || 0);
                const estandarPorHora = this.estandarProduccion / 8;
                const estandarEsperado = estandarPorHora * totalHorasTrabajadas;

                const excedente = produccionTotal - estandarEsperado;

                // Si no hay excedente, no hay bono
                if (excedente <= 0) {
                    return '0.00';
                }

                // Aplicar tramos progresivos sobre el excedente
                const bonoTotal = this.aplicarTramos(excedente);
                return bonoTotal.toFixed(2);
            },

            /**
             * MÉTODO 2: Calcula bono por destajo (pago directo por producción).
             * 
             * Este método NO considera estándar de producción.
             * Paga directamente según los kg producidos aplicando tramos progresivos.
             * 
             * Ejemplo:
             * - Producción: 50 kg
             * - Tramo 1: hasta 20 kg → 2.00 soles/kg
             * - Tramo 2: hasta 20 kg → 2.50 soles/kg  
             * - Tramo 3: resto → 3.00 soles/kg
             * - Bono = (20*2.00) + (20*2.50) + (10*3.00) = 120 soles
             * 
             * @param {number} produccionTotal - Kg totales producidos
             * @returns {string} - Monto del bono formateado con 2 decimales
             */
            calcularBonoPorDestajo(produccionTotal) {
                // Si no hay producción, no hay bono
                if (produccionTotal <= 0) {
                    return '0.00';
                }

                // Aplicar tramos progresivos sobre toda la producción
                const bonoTotal = this.aplicarTramos(produccionTotal);
                return bonoTotal.toFixed(2);
            },

            /**
             * Aplica tramos progresivos de bonificación sobre una cantidad.
             * 
             * Los tramos se aplican de forma acumulativa:
             * - Primer tramo: desde 0 hasta tramo[0].hasta
             * - Segundo tramo: desde tramo[0].hasta hasta tramo[1].hasta
             * - Y así sucesivamente
             * 
             * Si la cantidad excede todos los tramos, el resto se paga
             * con la tarifa del último tramo.
             * 
             * @param {number} cantidad - Cantidad en kg sobre la cual aplicar tramos
             * @returns {number} - Monto total calculado
             */
            aplicarTramos(cantidad) {
                if (!this.tramos || this.tramos.length === 0) {
                    return 0;
                }

                let bonoTotal = 0;
                let restante = cantidad;

                // Ordenar tramos por el campo 'hasta' (de menor a mayor)
                const tramosOrdenados = [...this.tramos].sort((a, b) => a.hasta - b.hasta);

                tramosOrdenados.forEach(tramo => {
                    const tramoHasta = parseFloat(tramo.hasta);
                    const tramoMonto = parseFloat(tramo.monto);

                    if (restante <= 0) return;

                    // Si la cantidad restante cubre todo el tramo
                    if (restante >= tramoHasta) {
                        bonoTotal += tramoHasta * tramoMonto;
                        restante -= tramoHasta;
                    } else {
                        // Si solo cubre parte del tramo
                        bonoTotal += restante * tramoMonto;
                        restante = 0;
                    }
                });

                // Si queda excedente después de todos los tramos,
                // aplicar la tarifa del último tramo
                if (restante > 0) {
                    const ultimoMonto = parseFloat(tramosOrdenados[tramosOrdenados.length - 1].monto);
                    bonoTotal += restante * ultimoMonto;
                }

                return bonoTotal;
            },

            /**
             * Resetea todos los bonos a cero.
             * 
             * @returns {void}
             */
            resetearBonos() {
                this.tableDataBonificados.forEach(trabajador => {
                    trabajador.total_bono = '0.00';
                });
            },
            guardarBonificaciones() {
                let allData = [];
                for (let row = 0; row < this.hot.countRows(); row++) {
                    const rowData = this.hot.getSourceDataAtRow(row);
                    allData.push(rowData);
                }

                const filteredData = allData.filter(row => row && Object.values(row).some(cell => cell !==
                    null && cell !== ''));
                $wire.guardarBonificaciones(filteredData);
            }
        }));
    </script>
@endscript
