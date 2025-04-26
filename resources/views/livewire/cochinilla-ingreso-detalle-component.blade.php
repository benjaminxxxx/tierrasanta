<div>
    <!--MODULO COCHINILLA INGRESO-->
    <x-dialog-modal wire:model.live="mostrarFormulario">
        <x-slot name="title">
            Sublotes de ingreso de cochinilla
        </x-slot>
        
        
        <x-slot name="content">
            @if ($cochinillaIngreso)
            <x-success>
                <table>
                    <tbody>
                        <tr>
                            <th class="text-left">Campo</th>
                            <td>{{$cochinillaIngreso->campo}}</td>
                        </tr>
                        <tr>
                            <th class="text-left">Campaña</th>
                            <td>{{$cochinillaIngreso->campoCampania->nombre_campania}}</td>
                        </tr>
                        <tr>
                            <th class="text-left">Lote principal</th>
                            <td>{{$cochinillaIngreso->lote}}</td>
                        </tr>
                        <tr>
                            <th class="text-left">Total de kilos</th>
                            <td>{{$cochinillaIngreso->total_kilos}}</td>
                        </tr>
                        <tr>
                            <th class="text-left">Fecha (puede cambiar después)</th>
                            <td>{{$cochinillaIngreso->fecha}}</td>
                        </tr>
                        <tr>
                            <th class="text-left">Observación</th>
                            <td>{{$cochinillaIngreso->observacionRelacionada?->descripcion}}</td>
                        </tr>
                    </tbody>
                </table>
            </x-success>
            @endif
            
            <ul class="space-y-1 text-gray-500 list-disc list-inside dark:text-gray-400 mt-4">
                <li>
                    No es necesario digitar el sublote, el sistema brindara su nombre automáticamente.
                </li>
                <li>
                    Todos los campos son obligatorios sino esa fila no se registrará
                </li>
            </ul>
            <div x-data="{{ $idTable }}" wire:ignore class="my-4">
                <div x-ref="tableContainer" ></div>
            </div>

        </x-slot>

        <x-slot name="footer">
            <x-flex class="justify-end w-full">
                <x-secondary-button wire:click="$set('mostrarFormulario', false)" wire:loading.attr="disabled">
                    Cerrar
                </x-secondary-button>
                <x-button type="button" @click="$wire.dispatch('guardadoConfirmado')">
                    <i class="fa fa-save"></i> Registrar detalle
                </x-button>
            </x-flex>
        </x-slot>
    </x-dialog-modal>

    <x-loading wire:loading />
</div>
@script
    <script>
        Alpine.data('{{ $idTable }}', () => ({
            listeners: [],
            tableData: [],
            observacionesOptions: @json($observaciones),
            hot: null,
            init() {
                this.initTable();
                this.listeners.push(
                    Livewire.on('cargarData', (data) => {
                    
                        this.tableData = data[0];
                        this.hot.destroy();
                        this.initTable();
                        this.hot.loadData(this.tableData);
                    })
                );
                this.listeners.push(

                    Livewire.on('guardadoConfirmado', () => {
                        this.sendDataPoblacionPlanta();
                    })
                );
            },
            initTable() {


                const container = this.$refs.tableContainer;
                const hot = new Handsontable(container, {
                    data: this.tableData,
                    colHeaders: true,
                    
                    rowHeaders: true,

                    columns: [{
                            data: 'sublote_codigo',
                            className: '!text-center !bg-gray-100',
                            readOnly: true,
                            title: 'Sublote'
                        },
                        {
                            data: 'fecha',
                            type: 'date',
                            className: '!text-center',
                            width: '50',
                            title: 'Fecha'
                        },
                        {
                            data: 'total_kilos',
                            type: 'numeric',
                            className: '!text-center',
                            width: '40',
                            title: 'Kilos recogidos',
                        },
                        {
                            data: 'observacion',
                            type: 'dropdown',
                            className: '!text-center',
                            width: 100,
                            source: this.observacionesOptions,
                            strict: true,
                            allowInvalid: false,
                            title: 'Observación'
                        }
                    ],

                    height: '200',
                    manualColumnResize: false,
                    manualRowResize: true,
                    minSpareRows: 1,
                    stretchH: 'all',
                    autoColumnSize: false,
                    licenseKey: 'non-commercial-and-evaluation',

                });

                this.hot = hot;
            },
            sendDataPoblacionPlanta() {
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
                $wire.dispatchSelf('storeTableDataCochinillaIngreso', data);
            }
        }));
    </script>
@endscript
