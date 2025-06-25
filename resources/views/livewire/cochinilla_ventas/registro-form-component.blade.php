<div>
    <x-dialog-modal wire:model="mostrarFormulario" maxWidth="full">
        <x-slot name="title">Registrar Entrega de una Venta</x-slot>

        <x-slot name="content" class="space-y-6">
            <div x-data="{{ $idTable }}">

                {{-- Filtros --}}
                <x-flex class="my-4">
                    <x-group-field>
                        <x-input-date label="Fecha de venta" class="!w-auto" wire:model="fecha_venta" />
                    </x-group-field>
                    <x-group-field>
                        <x-button wire:click="buscarYCargarTablaFuente">
                            <i class="fa fa-search"></i> Buscar ingresos
                        </x-button>
                    </x-group-field>
                </x-flex>

                {{-- Explicación --}}
                <p class="text-sm text-gray-600">
                    Selecciona una o más cosechas del listado inferior y luego presiona
                    <strong>“Vender cosechas seleccionadas”</strong> para agregarlas al detalle de la venta.
                </p>

                {{-- Estilo Vertical --}}
                <div class="flex flex-col gap-4">

                    {{-- Tabla Fuente (Registro de Cosechas) --}}
                    <div>
                        <x-h3>Registro de Cosechas</x-h3>
                        <div x-ref="tableContainerFuente"></div>
                    </div>

                    {{-- Botón central --}}
                    <div class="text-center">
                        <x-button @click="agregarADetalleVenta()">
                            <i class="fa fa-shopping-cart mr-1"></i> Vender cosechas seleccionadas
                        </x-button>
                    </div>

                    {{-- Tabla de Detalle --}}
                    <div wire:ignore>
                        <x-h3>Detalle de Venta</x-h3>
                        <div x-ref="tableContainer"></div>
                        <div class="text-right mt-2 font-semibold text-lg">
                            Total Venta: S/ <span x-text="totalVenta"></span>
                        </div>
                    </div>

                </div>
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$set('mostrarFormulario', false)">Cancelar</x-secondary-button>
            @if ($editable)
                <x-button @click="$wire.dispatch('sendDataRegistroEntregaVenta')" class="ml-3">Guardar Registro de
                Entrega</x-button>
            @endif
            
        </x-slot>
    </x-dialog-modal>

    <x-loading wire:loading />
</div>

@script
<script>
    Alpine.data('{{ $idTable }}', () => ({
        listeners: [],
        ingresos: [],
        seleccionados: [],
        tableData: [],
        totalVenta: '0.00',
        fechaVenta: null,
        selectedRows: [],
        condicionSugerencia: @json($condicionSugerencia),
        clienteSugerencia: @json($clienteSugerencia),
        itemSugerencia: @json($itemSugerencia),
        hot: null,
        hotFuente: null,



        init() {

            this.listeners.push(
                Livewire.on('regenerarTabla', (data) => {

                    this.$nextTick(() => {
                        const ventas = data[0].ventas;
                        this.initTable(ventas);
                    });
                })
            );
            this.listeners.push(

                Livewire.on('cargarTablaFuente', (data) => {

                    this.$nextTick(() => {

                        const ingresos = data[0].ingresos;
                        this.fechaVenta = data[0].fecha_venta;
                        this.initTableFuente(ingresos);
                    });
                })
            );
            this.listeners.push(

                Livewire.on('sendDataRegistroEntregaVenta', (data) => {

                    this.sendDataRegistroEntregaVenta();
                })
            );
        },
        initTable(datos) {

            if (this.hot != null) {
                this.hot.destroy();
                this.hot = null;
            }

            const tareas = this.tareas;

            const container = this.$refs.tableContainer;
            const hot = new Handsontable(container, {
                data: datos,
                colHeaders: true,
                rowHeaders: true,
                columns: [
                    {
                        data: 'campo',
                        type: 'text',
                        className: 'text-center',
                        title: 'Campo'
                    },
                    {
                        data: 'fecha_filtrado',
                        type: 'date',
                        className: 'text-center',
                        title: 'Fecha Filtrado'
                    },
                    {
                        data: 'cantidad_seca',
                        type: 'numeric',
                        className: 'text-center',
                        title: 'Cantidad Seca'
                    },
                    {
                        data: 'condicion',
                        type: 'dropdown',
                        source: this.condicionSugerencia,
                        className: 'text-center',
                        title: 'Condición',
                    },
                    {
                        data: 'cliente',
                        type: 'dropdown',
                        source: this.clienteSugerencia,
                        className: 'text-center',
                        title: 'Cliente',
                    },
                    {
                        data: 'item',
                        type: 'dropdown',
                        source: this.itemSugerencia,
                        className: 'text-center',
                        title: 'Item',
                    },
                    {
                        data: 'fecha_venta',
                        type: 'date',
                        className: 'text-center',
                        title: 'Fecha Venta'
                    },
                    {
                        data: 'observaciones',
                        type: 'text',
                        className: 'text-center',
                        title: 'Observaciones'
                    }
                ],
                afterChange: () => this.actualizarTotalVenta(),
                width: '100%',
                height: '120',
                manualColumnResize: false,
                manualRowResize: true,
                minSpareRows: 1,
                stretchH: 'all',
                autoColumnSize: true,
                fixedColumnsLeft: 2,
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
                const valor = parseFloat(row.cantidad_seca);
                if (!isNaN(valor)) {
                    total += valor;
                }
            });

            this.totalVenta = total.toFixed(2);
        },
        initTableFuente(datos) {

            if (this.hotFuente != null) {
                this.hotFuente.destroy();
                this.hotFuente = null;
            }

            const container = this.$refs.tableContainerFuente;
            const hotFuente = new Handsontable(container, {
                data: datos,
                colHeaders: true,
                rowHeaders: true,
                columns: [{
                    data: 'campo',
                    type: 'text',
                    title: 'Campo',
                    className: 'text-center !bg-gray-50',
                    readOnly: true
                },
                {
                    data: 'fecha_ingreso',
                    type: 'text',
                    title: 'Fecha Ingreso',
                    className: 'text-center !bg-gray-50',
                    readOnly: true
                },
                {
                    data: "fecha_filtrado",
                    type: 'text',
                    className: 'text-center !bg-gray-50',
                    title: 'Fecha de filtrado',
                    readOnly: true
                },
                {
                    data: 'cantidad_fresca',
                    type: 'text',
                    title: 'Cantidad Fresca',
                    className: 'text-center !bg-gray-50',
                    readOnly: true
                },
                {
                    data: 'cantidad_seca',
                    type: 'text',
                    title: 'Cantidad Seca',
                    className: 'text-center !bg-gray-50',
                    readOnly: true
                },
                {
                    data: 'uso_infestacion',
                    type: 'text',
                    title: 'Uso en infestación',
                    className: 'text-center !bg-gray-50',
                    readOnly: true
                }
                ],
                width: '100%',
                height: '140',
                manualColumnResize: false,
                manualRowResize: true,
                stretchH: 'all',
                autoColumnSize: true,
                fixedColumnsLeft: 1,
                selectionMode: 'multiple',
                className: 'htCenter',
                afterSelectionEnd: (rowStart, colStart, rowEnd, colEnd) => {
                    this.selectedRows = [];
                    for (let row = rowStart; row <= rowEnd; row++) {
                        console.log('evento principal for');
                        const rowData = this.hotFuente.getSourceDataAtRow(row);

                        if (rowData && Object.keys(rowData).length > 0) {
                            // Evitar duplicados
                            if (!this.selectedRows.some(r => r.ingreso_id === rowData.ingreso_id)) {
                                this.selectedRows.push(rowData);
                            }
                        }
                    }

                    // Resaltar selección completa (opcional)
                    // this.hotFuente.selectCell(rowStart, 0, rowEnd, this.hotFuente.countCols() - 1);*/
                },


                licenseKey: 'non-commercial-and-evaluation',


            });

            this.hotFuente = hotFuente;
        },

        agregarADetalleVenta() {
            if (!this.hotFuente || !this.hot) {
                alert('Las tablas no están listas.');
                return;
            }

            if (this.selectedRows.length === 0) {
                alert('No has seleccionado ninguna fila.');
                return;
            }

            console.log('seleccionado', this.selectedRows);

            this.selectedRows.forEach(row => {

                if (!this.tableData.some(r => r.ingreso_id === row.ingreso_id)) {
                    this.tableData.push({
                        ...row,
                        condicion: '',
                        cliente: '',
                        item: row.fecha_filtrado ? 'Cochinilla Seca' : '',
                        fecha_venta: this.fechaVenta,
                        observaciones: '',
                    });
                }
            });


            this.tableData = this.tableData.filter(row => row && Object.keys(row).length > 0);
            this.hot.loadData(this.tableData);

            // Limpiar selección
            this.selectedRows = [];
            this.hotFuente.deselectCell();
        },

        sendDataRegistroEntregaVenta() {
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
            $wire.dispatchSelf('storeTableDataCochinillaEntregaVenta', data);
        }
    }));
</script>
@endscript