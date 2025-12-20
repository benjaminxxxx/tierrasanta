<div>
    <x-dialog-modal wire:model="mostrarFormulario" maxWidth="full">
        <x-slot name="title">Registrar Entrega de una Venta</x-slot>

        <x-slot name="content" class="space-y-6">
            <div x-data="{{ $idTable }}">

                @if (!$registroEntregaGrupoId)
                    {{-- Filtros --}}
                    <x-flex class="my-4 items-start">
                        <x-input type="date" label="Fecha de venta" class="!w-auto" wire:model="fecha_venta" />
                        <x-select label="Tipo de ingreso" class="!w-auto" wire:model="tipo_ingreso">
                            <option value="">Todos los ingresos</option>
                            <option value="filtrados">Ingresos Filtrados</option>
                            <option value="sinfiltrados">Sin Filtrados (para vender fresco)</option>
                        </x-select>
                        <x-button wire:click="buscarYCargarTablaFuente">
                            <i class="fa fa-search"></i> Buscar ingresos
                        </x-button>
                    </x-flex>
                @endif
                {{-- Estilo Vertical --}}
                <div class="flex flex-col gap-4">

                    {{-- Tabla Fuente (Registro de Cosechas) --}}
                    <div>
                        <x-h3>Registro de Cosechas</x-h3>

                        @if ($registroEntregaGrupoId)
                            <x-flex class="my-4">
                                <x-label>Fecha de venta</x-label>
                                <p>{{$fecha_venta}}</p>
                            </x-flex>
                        @endif

                        {{-- Explicación --}}
                        <x-success class="mt-2 mb-4">
                            Estos son los ingresos que puede vender, si desea vender fresca, puede cambiar el tipo de
                            ingreso<br />
                            Solo llene los campos de la cochinilla que va a vender, los tres campos son obligatorios.
                        </x-success>
                        <div wire:ignore>
                            <div x-ref="tableContainerFuente"></div>
                            <div class="text-right mt-2 font-semibold text-lg dark:text-white">
                                Total Venta: Kg. <span x-text="totalVenta"></span>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-button variant="secondary" wire:click="$set('mostrarFormulario', false)">Cancelar</x-button>
            @if ($editable)
                <x-button @click="$wire.dispatch('sendDataRegistroEntregaVenta')" class="ml-3">
                    @if ($registroEntregaGrupoId)
                        <i class="fa fa-sync"></i> Actualizar la Entrega
                    @else
                        <i class="fa fa-save"></i> Registrar la Entrega
                    @endif
                </x-button>
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
            if (!this.hot) {
                return;
            }
            let total = 0;
            const data = this.hot.getSourceData();

            data.forEach(row => {
                const valor = parseFloat(row.venta_cantidad);
                if (!isNaN(valor)) {
                    total += valor;
                }
            });

            this.totalVenta = total.toFixed(2);
        },
        initTableFuente(datos) {

            if (this.hot != null) {
                this.hot.destroy();
                this.hot = null;
            }

            const container = this.$refs.tableContainerFuente;
            const hot = new Handsontable(container, {
                data: datos,
                colHeaders: true,
                rowHeaders: true,
                columns: [
                    {
                        data: 'detalle',
                        type: 'text',
                        title: 'Detalle Cosecha',
                        width: 70,
                        className: 'text-left !bg-gray-50',
                        readOnly: true
                    },

                    {
                        data: 'detalle_stock',
                        type: 'text',
                        title: 'Detalle Stock',
                        width: 45,
                        className: 'text-left !bg-gray-50',
                        readOnly: true
                    },

                    {
                        data: 'venta_cantidad',
                        type: 'numeric',
                        className: 'text-center',
                        width: 50,
                        title: 'Cantidad <br/>a Vender'
                    },
                    {
                        data: 'venta_condicion',
                        type: 'autocomplete',
                        source: this.condicionSugerencia,
                        className: 'text-center',
                        width: 30,
                        title: 'Condición',
                    },
                    {
                        data: 'venta_cliente',
                        type: 'autocomplete',
                        source: this.clienteSugerencia,
                        className: 'text-center',
                        title: 'Cliente',
                    },
                    {
                        data: 'venta_item',
                        type: 'autocomplete',
                        source: this.itemSugerencia,
                        className: 'text-center',
                        title: 'Item',
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
                        const rowData = this.hot.getSourceDataAtRow(row);

                        if (rowData && Object.keys(rowData).length > 0) {
                            // Evitar duplicados
                            if (!this.selectedRows.some(r => r.ingreso_id === rowData.ingreso_id)) {
                                this.selectedRows.push(rowData);
                            }
                        }
                    }
                },
                afterChange: () => this.actualizarTotalVenta(),
                licenseKey: 'non-commercial-and-evaluation',
            });

            this.hot = hot;
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