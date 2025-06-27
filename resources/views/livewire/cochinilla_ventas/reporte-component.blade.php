<div>

    <x-flex class="my-3">
        <x-h3>
            Reporte mensual de Ventas
        </x-h3>

    </x-flex>

    <x-card>
        <x-spacing>


            <div x-data="tableReporteVenta">

                <x-flex class="mb-4">
                    <x-select-meses wire:model.live="mes" />
                    <x-select-anios wire:model.live="anio" max="current" />
                    @if ($puedeVincular)
                        <x-danger-button wire:click="cancelarGenerarReporte">
                            <i class="fa fa-file"></i> Cancelar Generar Reporte
                        </x-danger-button>
                    @else
                        <x-button wire:click="obtenerParaReporte">
                            <i class="fa fa-file"></i> Generar Reporte
                        </x-button>
                    @endif

                    <x-button wire:click="vincularIngreso" :disabled="!$puedeVincular">
                        <i class="fa fa-link"></i> Vincular Ingreso
                    </x-button>
                    <x-button @click="agruparPorIngresos" :disabled="!$registroVinculado">
                        <i class="fa fa-layer-group"></i> Agrupar por Ingresos
                    </x-button>
                </x-flex>

                <div wire:ignore>
                    <x-h3>Detalle de Venta</x-h3>
                    <div x-ref="tableReporteContainer"></div>
                    <div class="text-right mt-2 font-semibold text-lg">
                        Total Venta: Kg. <span x-text="totalVenta"></span>
                    </div>
                    <x-flex class="w-full justify-end my-3">
                        <x-button @click="enviarVentaAContabilidad">
                            <i class="fa fa-paper-plane"></i> Guardar Reporte
                        </x-button>
                    </x-flex>

                </div>
                @if($totalVentaEntrega != 0)
                    <div class="my-3">
                        <x-h3>
                            Total Venta según entrega: {{ $totalVentaEntrega }}
                        </x-h3>
                        <p>Antes de guardar los cambios el total de Kg. debe coincidir</p>
                    </div>
                @endif
            </div>
        </x-spacing>
    </x-card>

    <x-loading wire:loading />
</div>

@script
<script>
    Alpine.data('tableReporteVenta', () => ({
        listeners: [],
        ingresos: [],
        seleccionados: [],
        tableData: @json($reporteCargado),
        totalVenta: '0.00',
        fechaVenta: null,
        selectedRows: [],
        condicionSugerencia: @json($condicionSugerencia),
        clienteSugerencia: @json($clienteSugerencia),
        hot: null,
        hotFuente: null,
        init() {

            this.$nextTick(() => {
                this.initTable();
            });
            this.listeners.push(

                Livewire.on('cargarTabla', (data) => {

                    this.$nextTick(() => {
                        this.tableData = data[0].entregas;
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
                    {
                        data: 'cosecha_fecha_ingreso',
                        type: 'date',
                        className: 'text-center',
                        title: 'Fecha Ingreso'
                    },
                    {
                        data: 'cosecha_campo',
                        type: 'text',
                        className: 'text-center',
                        title: 'Campo'
                    },
                    {
                        data: 'cosecha_procedencia',
                        type: 'text',
                        className: 'text-center',
                        title: 'Procedencia'
                    },
                    {
                        data: 'cosecha_cantidad_fresca',
                        type: 'numeric',
                        className: 'text-center',
                        title: 'Kg Frescos'
                    },
                    {
                        data: 'proceso_fecha_filtrado',
                        type: 'date',
                        className: 'text-center',
                        title: 'Fecha Filtrado'
                    },
                    {
                        data: 'proceso_cantidad_seca',
                        type: 'numeric',
                        className: 'text-center',
                        title: 'Cantidad Seca'
                    },
                    {
                        data: 'proceso_condicion',
                        type: 'dropdown',
                        source: this.condicionSugerencia ?? [],
                        className: 'text-center',
                        title: 'Condición'
                    },
                    {
                        data: 'venta_fecha_venta',
                        type: 'date',
                        className: 'text-center',
                        title: 'Fecha Venta'
                    },
                    {
                        data: 'venta_comprador',
                        type: 'dropdown',
                        source: this.clienteSugerencia ?? [],
                        className: 'text-center',
                        allowInvalid: true,
                        title: 'Comprador'
                    },
                    {
                        data: 'venta_infestadores_del_campo',
                        type: 'text',
                        className: 'text-center',
                        allowInvalid: true,
                        title: 'Campos Infestados'
                    }
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

                    if (rowData?.cosecha_encontrada === true) {
                        cellProperties.className = '!bg-lime-200';
                    }
                    if (rowData?.fusionada === true) {
                        cellProperties.className = '!bg-blue-200';
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
            const data = this.hot.getSourceData();

            data.forEach(row => {
                const valor = parseFloat(row.proceso_cantidad_seca);
                if (!isNaN(valor)) {
                    total += valor;
                }
            });

            this.totalVenta = total.toFixed(2);
        },
        agruparPorIngresos() {
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
            $wire.dispatchSelf('storeTableAgruparPorIngresos', data);
        },

        enviarVentaAContabilidad() {
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
            $wire.dispatchSelf('storeTableDataEnviarAContabilidad', data);
        }
    }));
</script>
@endscript