<div>
    <x-loading wire:loading/>
    <x-card class="mt-5">
        <x-spacing>
            @php
                $idTable = 'planilla' . Str::random(5);
            @endphp
            <div x-data="{{ $idTable }}" wire:ignore>

                <div x-ref="tableContainer" class="min-h-[20rem] mt-5 overflow-auto"></div>

                <div>
                    <x-button wire:click="cargarInformacion" class="mt-5">
                        Cargar Información de Reporte
                    </x-button>
                </div>
            </div>
        </x-spacing>
    </x-card>
</div>

@script
    <script>
        Alpine.data('{{ $idTable }}', () => ({
            listeners: [],
            tableData: @json($empleados),
            hot: null,
            init() {
                this.initTable();
                this.listeners.push(
                    Livewire.on('setEmpleados', (data) => {
                        console.log(data[0]);
                        this.tableData = data[0];
                        this.hot.loadData(this.tableData);
                    })
                );
            },
            initTable() {

                const dias = @json($dias);

                let columns = [{
                        data: 'orden',
                        type: 'numeric',
                        title: 'N°',
                        className: '!text-center',
                        readOnly: true
                    },
                    {
                        data: 'grupo',
                        type: 'text',
                        width: 60,
                        className: 'text-center',
                        title: `Grupo`,
                        readOnly: true
                    },
                    {
                        data: 'documento',
                        type: 'text',
                        width: 80,
                        title: `DNI`,
                        readOnly: true
                    },
                    {
                        data: 'nombres',
                        type: 'text',
                        title: `Nombres`,
                        readOnly: true
                    }
                ];

                dias.forEach(dia => {
                    columns.push({
                        data: `dia_${dia}`, 
                        type: 'numeric',
                        width: 40,
                        title: `${dia}`,
                        className: '!text-center',
                        readOnly: true
                    });
                });

                // Agregar la columna final de TOTAL
                columns.push({
                    data: 'total_horas',
                    width: 70,
                    type: 'numeric',
                    title: 'TOTAL',
                    className: '!text-center',
                    readOnly: true
                });


                const container = this.$refs.tableContainer;
                const hot = new Handsontable(container, {
                    data:  this.tableData,
                    colHeaders: true,
                    rowHeaders: true,
                    columns: columns,
                    width: '100%',
                    height: '90%',
                    manualColumnResize: false,
                    manualRowResize: true,
                    minSpareRows: 1,
                    stretchH: 'all',
                    autoColumnSize: true,
                    fixedColumnsLeft: 4,
                    licenseKey: 'non-commercial-and-evaluation'
                });

                this.hot = hot;
            },
            obtenerEmpleados() {

            },
            sendData() {
                const rawData = this.hot.getData();

                const filteredData = rawData.filter(row => {
                    return row.some(cell => cell !== null && cell !== '');
                });

                const data = {
                    data: filteredData
                };

                console.log('Datos a enviar:', data);
                $wire.dispatchSelf('storeTableData', data);
            }
        }));
    </script>
@endscript
