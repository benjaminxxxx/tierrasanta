<div>

    <x-flex class="my-3">
        <x-h3>
            Cuadro de Ventas
        </x-h3>

    </x-flex>

    <x-card>
        <x-spacing>


            <div x-data="tableVentaFacturada">

                <x-flex class="mb-4">
                    <x-select-meses wire:model.live="mes" />
                    <x-select-anios wire:model.live="anio" max="current" />
                    <x-button wire:click="actualizarDesdeReporte">
                        <i class="fa fa-sync"></i> Actualizar desde reporte
                    </x-button>
                </x-flex>

                <div wire:ignore>
                    <x-h3>Detalle de Venta</x-h3>
                    <div x-ref="tableReporteContainer"></div>
                    <div class="text-right mt-2 font-semibold text-lg">
                        Total Venta: Kg. <span x-text="totalVenta"></span>
                        Total US$: $. <span x-text="totalVentaDolares"></span>
                    </div>
                    <x-flex class="w-full justify-end my-3">
                        <x-button @click="guardarRegistroVenta">
                            <i class="fa fa-save"></i> Guardar Registro de Venta
                        </x-button>
                    </x-flex>

                </div>
            </div>
        </x-spacing>
    </x-card>

    <x-loading wire:loading />
</div>

@script
<script>
    Alpine.data('tableVentaFacturada', () => ({
        listeners: [],
        ingresos: [],
        seleccionados: [],
        tableData: @json($ventasFacturadas),
        totalVenta: '0.00',
        campos: @json($campos),
        totalVentaDolares: '0.00',
        fechaVenta: null,
        selectedRows: [],
        hot: null,
        hotFuente: null,
        init() {

            this.$nextTick(() => {
                this.initTable();
            });
            this.listeners.push(

                Livewire.on('cargarTabla', (data) => {
                    this.$nextTick(() => {
                        this.tableData = data[0].ventas;
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
                data: this.tableData,
                colHeaders: true,
                rowHeaders: true,
                columns: [
                    { data: 'fecha', type: 'date', className: '!text-center', title: 'FECHA' },
                    { data: 'factura', type: 'text', className: '!text-center', title: 'N° Factura' },
                    { data: 'tipo_venta', type: 'text', className: '!text-center', title: 'Venta' },
                    { data: 'comprador', type: 'numeric', numericFormat: { pattern: '0.00' }, className: '!text-center', title: 'Comprador' },
                    { data: 'lote', type: 'dropdown', className: '!text-center', title: 'Lote',strict: true, allowInvalid: true, source: this.campos},
                    { data: 'kg', type: 'numeric', numericFormat: { pattern: '0.00' }, className: '!text-center', title: 'Kg' },
                    { data: 'procedencia', type: 'date', dateFormat: 'YYYY-MM-DD', correctFormat: true, className: '!text-center', title: 'Procedencia' },
                    { data: 'precio_venta_dolares', type: 'numeric', numericFormat: { pattern: '0.00' }, className: '!text-center', title: 'P. Venta $' },
                    { data: 'punto_acido_carminico', type: 'numeric', numericFormat: { pattern: '0.00' }, className: '!text-center', title: 'Punto Ac.<br/>Carminico' },
                    { data: 'acido_carminico', type: 'numeric', readOnly: true, numericFormat: { pattern: '0.00' }, className: '!text-center !bg-gray-100', title: 'Ac. Carmi-<br/>nico' },
                    { data: 'sacos', type: 'numeric', readOnly: true, className: '!text-center !bg-gray-100', title: 'SACOS' },
                    { data: 'ingresos', type: 'numeric', readOnly: true, numericFormat: { pattern: '0.00' }, className: '!text-center !bg-gray-100', title: 'Ingresos $' },
                    { data: 'tipo_cambio', type: 'numeric', numericFormat: { pattern: '0.00' }, className: '!text-center', title: 'T.C.' },
                    { data: 'ingreso_contable_soles', type: 'numeric', readOnly: true, numericFormat: { pattern: '0.00' }, className: '!text-center !bg-gray-100', title: 'Ingresos <br/>S/. (Contable<br/> + NG)' },
                ],

                width: '100%',
                height: 'auto',
                manualColumnResize: false,
                manualRowResize: true,
                minSpareRows: 1,
                stretchH: 'all',
                autoColumnSize: true,
                fixedColumnsLeft: 2,
                afterChange: () => this.actualizarTotalVenta(),
                cells: function (row, col) {
                    const cellProperties = {};
                    const rowData = this.instance.getSourceDataAtRow(row);

                    if (rowData?.origen === 'reporte') {
                        cellProperties.className = '!bg-purple-200';
                    }

                    return cellProperties;
                },
                licenseKey: 'non-commercial-and-evaluation',
            });

            this.hot = hot;
            this.actualizarTotalVenta();
        },
        actualizarTotalVenta() {
            if (!this.hot) {
                return;
            }
            let total = 0;
            let totalDolares = 0;
            const data = this.hot.getSourceData();

            data.forEach(row => {
                const valor = parseFloat(row.kg);
                const valorDolares = parseFloat(row.ingresos);
                if (!isNaN(valor)) {
                    total += valor;
                }
                if (!isNaN(valorDolares)) {
                    totalDolares += valorDolares;
                }
            });

            this.totalVenta = total.toFixed(2);
            this.totalVentaDolares = totalDolares.toFixed(2);
        },

        guardarRegistroVenta() {
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
            $wire.dispatchSelf('storeTableDataRegistroVentas', data);
        }

    }));
</script>
@endscript