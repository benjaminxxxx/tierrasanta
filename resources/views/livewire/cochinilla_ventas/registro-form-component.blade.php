<div>
    <x-dialog-modal wire:model="mostrarFormulario" maxWidth="full">
        <x-slot name="title">Registrar Entrega de una Venta</x-slot>

        <x-slot name="content" class="space-y-6">
            <div x-data="{{ $idTable }}">

                {{-- Filtros --}}
                <x-flex class="my-4">
                    <x-input-date label="Fecha de venta" class="!w-auto" wire:model="fecha_venta" />
                    <x-select label="Tipo de ingreso" class="!w-auto" wire:model="tipo_ingreso">
                        <option value="filtrados">Ingresos Filtrados</option>
                        <option value="sinfiltrados">Sin Filtrados (para vender fresco)</option>
                    </x-select>
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
                        <div class="text-right mt-2 font-semibold text-lg">
                            Total Venta: Kg. <span x-text="totalVenta"></span>
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
        actualizarTotalVenta() {
            if (!this.hotFuente) {
                return;
            }
            let total = 0;
            const data = this.hotFuente.getSourceData();

            data.forEach(row => {
                const valor = parseFloat(row.venta_cantidad);
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
                columns: [
                {
                    data: 'detalle',
                    type: 'text',
                    title: 'Detalle Cosecha',
                    className: 'text-left !bg-gray-50',
                    readOnly: true
                },
                
                {
                    data: 'detalle_stock',
                    type: 'text',
                    title: 'Detalle Stock',
                    className: 'text-left !bg-gray-50',
                    readOnly: true
                },
                
                {
                    data: 'venta_cantidad',
                    type: 'numeric',
                    className: 'text-center',
                    title: 'Cantidad a Vender'
                },
                {
                    data: 'venta_condicion',
                    type: 'dropdown',
                    source: this.condicionSugerencia,
                    className: 'text-center',
                    title: 'Condición',
                },
                {
                    data: 'venta_cliente',
                    type: 'dropdown',
                    source: this.clienteSugerencia,
                    className: 'text-center',
                    title: 'Cliente',
                },
                {
                    data: 'venta_item',
                    type: 'dropdown',
                    source: this.itemSugerencia,
                    className: 'text-center',
                    title: 'Item',
                },
                {
                    data: 'venta_fecha',
                    type: 'date',
                    className: 'text-center',
                    title: 'Fecha Venta'
                },
                ],
                width: '100%',
                height: 'auto',
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
                afterChange: () => this.actualizarTotalVenta(),


                licenseKey: 'non-commercial-and-evaluation',


            });

            this.hotFuente = hotFuente;
            this.actualizarTotalVenta();
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