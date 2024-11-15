<div>
    <x-loading wire:loading />
    <x-card class="mt-5">
        <x-spacing>
            @php
                $idTable = 'planilla' . Str::random(5);
            @endphp
            <div x-data="{{ $idTable }}" wire:ignore>

                <div x-ref="tableContainer" class="mt-5 overflow-auto"></div>

                <div>
                    <x-button-a href="{{ route('reporte.reporte_diario') }}" class="mt-5 mr-5">
                        Ver Reporte Diario
                    </x-button-a>
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
            informacionAsistenciaAdicional: @json($informacionAsistenciaAdicional),
            init() {
                this.initTable();
                this.listeners.push(
                    Livewire.on('setEmpleados', (data) => {

                        this.informacionAsistenciaAdicional = data[1];


                        this.tableData = data[0];
                        this.hot.loadData(this.tableData);
                        //location.href = location.href;
                    })
                );
            },
            initTable() {

                const dias = @json($dias);
                const self = this;

                let columns = [{
                        data: 'orden',
                        type: 'numeric',
                        title: 'N°',
                        className: '!text-center !bg-gray-100',
                        readOnly: true
                    },
                    {
                        data: 'grupo',
                        type: 'text',
                        width: 60,
                        className: 'text-center !bg-gray-100',
                        title: `GRUPO`,
                        readOnly: true
                    },
                    {
                        data: 'documento',
                        type: 'text',
                        width: 80,
                        title: `DNI`,
                        className: 'text-center !bg-gray-100',
                        readOnly: true
                    },
                    {
                        data: 'nombres',
                        type: 'text',
                        title: `NOMBRES`,
                        renderer: function(instance, td, row, col, prop, value, cellProperties) {
                          
                            const color = instance.getDataAtRowProp(row, 'empleado_grupo_color');

                            td.style.background = color;
                            td.innerHTML = value;

                            return td;
                        },
                        readOnly: true
                    }
                ];

                dias.forEach(dia => {
                    columns.push({
                        data: `dia_${dia.indice}`,
                        type: 'numeric',
                        width: 30,
                        title: `${dia.titulo} <br/> ${dia.indice}`,
                        className: '!text-center',
                        renderer: function(instance, td, row, col, prop, value,
                            cellProperties) {

                            const documento = instance.getDataAtRowProp(row,
                                'documento');
                            const _dia = `dia_${dia.indice}`;

                            let tipoAsistencia = null;
                            if (self.informacionAsistenciaAdicional && self
                                .informacionAsistenciaAdicional[_dia] && self
                                .informacionAsistenciaAdicional[_dia][documento]) {
                                tipoAsistencia = self.informacionAsistenciaAdicional[_dia][
                                    documento
                                ]['tipo_asistencia'];
                                color = self.informacionAsistenciaAdicional[_dia][
                                    documento
                                ]['color'];

                                descripcion = self.informacionAsistenciaAdicional[_dia][
                                    documento
                                ]['descripcion'];

                                // Establecer el color de fondo
                                td.style.background = color;

                                // Establecer el título (tooltip) si la descripción no está vacía
                                if (descripcion && descripcion.trim() !== '') {
                                    td.title = descripcion;
                                } else {
                                    td.title = ''; // Limpia el title si no hay descripción
                                }

                                if (tipoAsistencia == 'F') {
                                    td.innerHTML = 'F';
                                } else {

                                    if (tipoAsistencia == undefined || tipoAsistencia
                                    .trim() == '') {

                                        td.innerHTML = '';
                                    } else {
                                        if (!isNaN(value) && value !== null) {
                                            // Convertir el valor a número flotante
                                            const numericValue = parseFloat(value);
                                            
                                            // Verificar si tiene decimales o no
                                            if (Number.isInteger(numericValue)) {
                                                formattedValue = numericValue; // Si es un entero, mostrarlo sin decimales
                                            } else {
                                                formattedValue = numericValue.toFixed(1); // Si tiene decimales, mantener un decimal
                                            }

                                            td.innerHTML = formattedValue;
                                        }
                                        
                                    }
                                }
                            } else {
                                td.innerHTML = '';
                            }
                            
                            if(dia.titulo=='D'){
                                td.style.background = '#FFC000';
                            }

                            //td.style.background = color;
                            td.className = '!text-center';

                            return td;
                        },
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
                    data: this.tableData,
                    colHeaders: true,
                    rowHeaders: true,
                    columns: columns,
                    width: '100%',
                    height: 'auto',
                    manualColumnResize: false,
                    manualRowResize: true,
                    minSpareRows: 1,
                    stretchH: 'all',
                    autoColumnSize: true,
                    fixedColumnsLeft: 4,
                    licenseKey: 'non-commercial-and-evaluation',
                    /*afterRender: function() {
                        
                        const htCoreTable = document.querySelector('.htCore');
                        let tableHeight = htCoreTable.offsetHeight;
                        if (tableHeight > 0) {
                            tableHeight = tableHeight + 70;
                            container.style.minHeight = `${tableHeight}px`;
                        }
                    },*/
                });

                this.hot = hot;
                //this.hot.render();
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
