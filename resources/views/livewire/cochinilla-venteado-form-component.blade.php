<div>
    <!--MODULO COCHINILLA INGRESO VENTEADO-->
    <x-dialog-modal wire:model.live="mostrarFormulario">
        <x-slot name="title">
           Venteado de cochinilla por lote
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
                    Todos estos registros pertenecen al lote indicado en la parte superior, de pertenecer a un campo diferente debe crear un nuevo lote.
                </li>
                <li>
                    Basura es un campo calculado, es el total ingresado menos la suma de lo que queda limpio + el polvillorecuperado.
                </li>
                <li>
                    Debe ingresar como mínimo la fecha del proceso y los kilos ingresados para realizar el registro.
                </li>
            </ul>
            <div x-data="{{ $idTable }}" wire:ignore class="my-4">
                <div x-ref="tableContainerVenteado" ></div>
            </div>

        </x-slot>

        <x-slot name="footer">
            <x-flex class="justify-end w-full">
                <x-secondary-button wire:click="$set('mostrarFormulario', false)" wire:loading.attr="disabled">
                    Cerrar
                </x-secondary-button>
                <x-button type="button" @click="$wire.dispatch('guardadoConfirmadoVenteado')">
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
            hot: null,
            init() {
                this.initTable();
                this.listeners.push(
                    Livewire.on('cargarDataVenteado', (data) => {
                    
                        this.tableData = data[0];
                        this.hot.destroy();
                        this.initTable();
                        this.hot.loadData(this.tableData);
                    })
                );
                this.listeners.push(

                    Livewire.on('guardadoConfirmadoVenteado', () => {
                        this.senDataCochinillaVenteado();
                    })
                );
            },
            initTable() {
                const container = this.$refs.tableContainerVenteado;
                const hot = new Handsontable(container, {
                    data: this.tableData,
                    colHeaders: true,
                    
                    rowHeaders: true,

                    columns: [{
                            data: 'lote',
                            className: '!text-center',
                            title: 'Lote'
                        },
                        {
                            data: 'fecha_proceso',
                            type: 'date',
                            className: '!text-center',
                            width: 90,
                            title: 'Fecha de proceso'
                        },
                        {
                            data: 'kilos_ingresado',
                            type: 'numeric',
                            className: '!text-center',
                            width: 70,
                            title: 'Kilos ingresados'
                        },
                        {
                            data: 'limpia',
                            type: 'numeric',
                            className: '!text-center',
                            width: 70,
                            title: 'Limpia'
                        },
                        
                        {
                            data: 'polvillo',
                            type: 'numeric',
                            className: '!text-center',
                            width: 70,
                            title: 'Polvillo'
                        },
                        {
                            data: 'basura',
                            type: 'numeric',
                            className: '!text-center !bg-gray-100',
                            readOnly: true,
                            width: 70,
                            title: 'Basura'
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
            senDataCochinillaVenteado() {
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
                $wire.dispatchSelf('storeTableDataCochinillaIngresoVenteado', data);
            }
        }));
    </script>
@endscript
