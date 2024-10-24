<div x-data="planilla_blanco">
    @if ($informacionBlanco)
        <x-card class="mt-5">
            <x-spacing>
                <x-h3>MES DE {{ mb_strtoupper($mesTitulo) }} - {{ $anio }}</x-h3>
                
                <div class="mt-5" wire:ignore>
                    <div x-ref="tableContainer" class="overflow-auto"></div>
                </div>
            </x-spacing>
        </x-card>
    @endif
</div>


@script
    <script>
        Alpine.data('planilla_blanco', () => ({
            listeners: [],
            tableData: @json($informacionBlancoDetalle),
            totales: null,
            hot: null,
            init() {
                this.initTable();
                /*this.listeners.push(
                    Livewire.on('renderTable', (data) => {
                        /*console.log(data);
                        let empleados = data[0];
                        this.tableData = empleados;
                        this.hot.loadData(this.tableData);
                    })
                );
                this.listeners.push(
                    Livewire.on('setColumnas', (data) => {
                        console.log(data);
                        const tareas = data[0];
                        const columns = this.generateColumns(tareas);
                        this.hot.updateSettings({
                            columns: columns
                        });

                        // Vuelve a cargar los datos actuales en la tabla (si fuera necesario)
                        this.hot.loadData(this.tableData);
                    })
                );*/
            },
            initTable() {
                const tareas = this.tareas;
                const columns = this.generateColumns(tareas);

                const container = this.$refs.tableContainer;
                const hot = new Handsontable(container, {
                    data: this.tableData,
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
                    fixedColumnsLeft: 2,
                    licenseKey: 'non-commercial-and-evaluation',
                    afterRender: function() {
                        
                        const htCoreTable = document.querySelector('.htCore');
                        const tableHeight = htCoreTable.offsetHeight;
                        if (tableHeight > 0) {
                            container.style.minHeight = `${tableHeight}px`;
                        }
                    },

                });

                this.hot = hot;
                this.hot.render();

                //this.calcularTotales();
            },
            isValidTimeFormat(time) {
                const timePattern = /^([01]\d|2[0-3]):([0-5]\d)$/;
                return timePattern.test(time);
            },
            generateColumns(tareas) {
                let columns = [{
                        data: 'documento',
                        type: 'text',
                        title: 'DNI',
                        className: '!text-center',
                        readOnly: true
                    },
                    {
                        data: "nombres",
                        type: 'text',
                        title: 'APELLIDOS Y NOMBRES',
                        renderer: function(instance, td, row, col, prop, value, cellProperties) {
                          
                            const color = instance.getDataAtRowProp(row, 'empleado_grupo_color');

                            td.style.background = color;
                            td.innerHTML = value;

                            return td;
                        },
                        readOnly: true
                    },
                    
                    {
                        data: 'negro_diferencia_bonificacion',
                        type: 'text',
                        title: 'DIF. O.<br/> BONIF',
                        className: '!text-center',
                        readOnly: true
                    },
                    {
                        data: 'negro_sueldo_neto_total',
                        type: 'text',
                        title: 'SUELDO NETO<br/>TOTAL',
                        className: '!text-center !text-green-600 font-bold',
                        readOnly: true
                    },
                    {
                        data: 'negro_sueldo_bruto',
                        type: 'text',
                        title: 'SUELDO BRUTO<br/>NEGRO',
                        className: '!text-center !bg-[#FCD5B4]',
                        readOnly: true
                    },
                    {
                        data: 'negro_sueldo_por_dia',
                        type: 'text',
                        title: 'SUELDO<br/>POR DÍA',
                        className: '!text-center !text-blue-700 font-bold',
                        readOnly: true
                    },
                    {
                        data: 'negro_sueldo_por_hora',
                        type: 'text',
                        title: 'SUELDO<br/>POR HORA',
                        className: '!text-center',
                        readOnly: true
                    },
                   
                    {
                        data: 'negro_diferencia_por_hora',
                        type: 'text',
                        title: 'DIFERENCIA<br/>X HORA',
                        className: '!text-center',
                        readOnly: true
                    },
                    {
                        data: 'negro_diferencia_real',
                        type: 'text',
                        title: 'DIFERENCIA<br/>REAL',
                        className: '!text-center !bg-[#F2DCDB] font-bold !text-indigo-700',
                        readOnly: true
                    },
                   
                ];

                return columns;
            },
            calcularTotales() {

            },
            sendData() {
                const rawData = this.hot.getData();

                const filteredData = rawData.filter(row => {
                    return row.some(cell => cell !== null && cell !== '');
                });

                const data = {
                    datos: filteredData,
                    totales: this.totales
                };


                $wire.dispatchSelf('GuardarInformacion', data);
            }
        }));
    </script>
@endscript
