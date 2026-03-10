<div x-data="bonificacionesDual" class="space-y-5 mt-5">
    <!-- Encabezado con Botón Agregar Método -->
    <x-card>
        <x-flex class="justify-between">
            <x-h3>Bonificación</x-h3>
            <x-flex>
                <x-input wire:model="unidades" placeholder="Unidades Ejem: Kg" label="Unidad de Producción"
                    class="w-auto" />
                <x-button variant="primary" @click="agregarMetodo()" class="ml-auto">
                    <i class="fa fa-plus"></i> Agregar Método
                </x-button>

            </x-flex>
        </x-flex>
    </x-card>

    <!-- Métodos Dinámicos -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <template x-for="(metodo, index) in metodos" :key="`metodo-${index}`">
            <x-card>
                <!-- Encabezado del Método -->
                <x-flex class="justify-between items-center mb-4 pb-4 border-b border-border">
                    <div class="flex-1">
                        <x-h4 x-bind:class="metodo.estandar ? 'text-blue-500' : 'text-green-600'"
                            x-text="obtenerNombreMetodo(metodo, index)">
                        </x-h4>
                        <x-label class="text-xs text-muted-foreground mt-1"
                            x-text="metodo.estandar ? 'Bonificación por sobreestandar' : 'Bonificación por destajo'">
                        </x-label>
                    </div>
                    <x-button variant="danger" @click="eliminarMetodo(index)">
                        <i class="fa fa-trash"></i> Eliminar
                    </x-button>
                </x-flex>

                <!-- Input Estándar -->
                <div class="mb-6">
                    <x-flex>
                        <x-label>Estándar de producción (por 8 horas):</x-label>
                        <x-input type="number" x-model.number="metodo.estandar" @input="actualizarNombreMetodo"
                            step="0.1" class="!w-32" placeholder="Dejar vacío para destajo" />
                        <x-label>{{ $unidades ?? 'KG' }}</x-label>
                        <x-label class="text-xs text-muted-foreground ml-4"
                            x-text="metodo.estandar ? '(Modo: Sobreestandar)' : '(Modo: Destajo)'">
                        </x-label>
                    </x-flex>
                </div>

                <!-- Tramos -->
                <div>
                    <x-flex class="mb-4">
                        <x-label class="font-semibold">Tramos de Pago:</x-label>
                        <x-button variant="success" @click="metodo.tramos.push({hasta: '', monto: ''})" class="ml-auto">
                            <i class="fa fa-plus"></i> Agregar Tramo
                        </x-button>
                    </x-flex>

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
                                <template x-for="(tramo, tramoIndex) in metodo.tramos"
                                    :key="`tramo-${index}-${tramoIndex}`">
                                    <tr>
                                        <td class="px-4 py-2">
                                            <x-input type="number" class="w-full" x-model.number="tramo.hasta"
                                                step="0.1" @input="recalcularTodo" />
                                        </td>
                                        <td class="px-4 py-2">
                                            <x-input type="number" class="w-full" x-model.number="tramo.monto"
                                                step="0.1" @input="recalcularTodo" />
                                        </td>
                                        <td class="px-4 py-2 text-center">
                                            <x-button variant="danger" @click="removeTramo(metodo, tramoIndex)">
                                                <i class="fa fa-trash"></i>
                                            </x-button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </x-card>
        </template>
    </div>


    <!-- Información de Métodos -->
    <x-card class="text-xs text-muted-foreground space-y-2">
        <x-flex class="gap-2">
            <span class="text-blue-500 font-semibold">ℹ</span>
            <p><strong>Crear métodos:</strong> Haz clic en "Agregar Método" para crear un nuevo método de cálculo.</p>
        </x-flex>
        <x-flex class="gap-2">
            <span class="text-blue-500 font-semibold">ℹ</span>
            <p><strong>Modo automático:</strong> Si ingresas un valor en "Estándar", el método será por sobreestandar.
                Si lo dejas vacío, será por destajo.</p>
        </x-flex>
        <x-flex class="gap-2">
            <span class="text-blue-500 font-semibold">ℹ</span>
            <p><strong>Eliminar método:</strong> Los trabajadores asignados a este método se desvincularan
                automáticamente.</p>
        </x-flex>
    </x-card>

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
            metodos: @entangle('metodos'),
            recojos: @entangle('recojos'),
            tableDataBonificados: @js($tableDataBonificados),
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
            recalcularTodo() {
                this.calcularBonos();
            },
            removeTramo(metodo, tramoIndex) {

                metodo.tramos.splice(tramoIndex, 1)

                this.recalcularTodo()

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
                    source: this.obtenerNombresMetodos(),
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

            agregarMetodo() {
                this.metodos.push({
                    estandar: null,
                    tramos: [{
                        hasta: '',
                        monto: ''
                    }]
                });
                this.hot.updateSettings({
                    columns: this.generarColumnasDinamicas()
                });
            },

            eliminarMetodo(index) {
                // Desvincular trabajadores que usen este método
                const nombreMetodo = this.obtenerNombreMetodo(this.metodos[index], index);
                this.tableDataBonificados.forEach(trabajador => {
                    if (trabajador.metodo_bonificacion === nombreMetodo) {
                        trabajador.metodo_bonificacion = null;
                    }
                });
                // Eliminar el método
                this.metodos.splice(index, 1);
                // Actualizar tabla
                this.hot.updateSettings({
                    columns: this.generarColumnasDinamicas()
                });
                this.calcularBonos();
            },

            obtenerNombreMetodo(metodo, index) {
                const numeroMetodo = index + 1;
                if (metodo.estandar) {
                    return `Método x Sobreestandar #${numeroMetodo}`;
                } else {
                    return `Método x Jornal #${numeroMetodo}`;
                }
            },

            obtenerNombresMetodos() {
                const nombres = [];
                this.metodos.forEach((metodo, index) => {
                    nombres.push(this.obtenerNombreMetodo(metodo, index));
                });
                return nombres;
            },

            actualizarNombreMetodo() {
                // Se actualiza automáticamente por Alpine
                this.hot.updateSettings({
                    columns: this.generarColumnasDinamicas()
                });
                this.calcularBonos();
            },

            /**
             * Calcula los bonos para todos los trabajadores según su método asignado.
             * 
             * Busca el método dinámico asignado al trabajador y lo aplica:
             * - Si el método tiene estándar: Por sobreestandar
             * - Si el método no tiene estándar: Por destajo
             */
            calcularBonos() {
                this.tableDataBonificados.forEach(trabajador => {
                    const nombreMetodoSeleccionado = trabajador.metodo_bonificacion;

                    if (!nombreMetodoSeleccionado) {
                        trabajador.total_bono = '0.00';
                        return;
                    }

                    // Buscar el método en la lista
                    const metodo = this.metodos.find((m, index) =>
                        this.obtenerNombreMetodo(m, index) === nombreMetodoSeleccionado
                    );

                    if (!metodo || !metodo.tramos || metodo.tramos.length === 0) {
                        trabajador.total_bono = '0.00';
                        return;
                    }

                    const produccionTotal = this.calcularProduccionTotal(trabajador);

                    // Determinar si es por estándar o destajo según si el método tiene estándar
                    if (metodo.estandar && metodo.estandar > 0) {
                        // Por sobreestandar
                        trabajador.total_bono = this.calcularBonoPorEstandar(
                            trabajador,
                            produccionTotal,
                            metodo
                        );
                    } else {
                        // Por destajo
                        trabajador.total_bono = this.calcularBonoPorDestajo(
                            produccionTotal,
                            metodo.tramos
                        );
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
            //Esta funcion esta hecha de forma axuliar para resolver un error critico, total_horas no viene como float, sino viene como time desde la tabla, en redondeo iba a fallar
            convertirHorasDecimal(valor) {

                if (valor === null || valor === undefined) return 0;

                // si ya es número
                if (typeof valor === 'number') {
                    return valor;
                }

                const str = String(valor).trim();

                // formato HH:MM
                if (str.includes(':')) {
                    const [h, m] = str.split(':').map(Number);
                    const horas = (h || 0) + ((m || 0) / 60);
                    return horas;
                }

                // formato decimal normal
                return parseFloat(str) || 0;
            },
            /**
             * MÉTODO: Bonificación por sobreestandar.
             * 
             * Fórmula:
             * - Estándar esperado = (metodo.estandar / 8) * horas_trabajadas
             * - Excedente = produccionTotal - estandarEsperado
             * - Bono = aplicar tramos progresivos sobre el excedente
             */
            calcularBonoPorEstandar(trabajador, produccionTotal, metodo) {
                const totalHoras = this.convertirHorasDecimal(trabajador.rango_total_horas);
                console.log(totalHoras);
                if (!metodo.estandar || metodo.estandar <= 0) {
                    return '0.00';
                }

                const estandarPorHora = metodo.estandar / 8;
                const estandarEsperado = estandarPorHora * totalHoras;
                const excedente = Math.max(0, produccionTotal - estandarEsperado);

                if (excedente <= 0) {
                    return '0.00';
                }

                const bonoTotal = this.aplicarTramos(excedente, metodo.tramos);
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
