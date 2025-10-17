<div x-data="reportePlanillaAsistencia">
    
    <x-card2 class="mt-5">
        @php
            $idTable = 'planilla' . Str::random(5);
        @endphp
        <div wire:ignore>
            <div x-ref="tableContainer" class="mt-5 overflow-auto"></div>
        </div>
    </x-card2>
    <x-loading wire:loading />
</div>


@script
<script>
    Alpine.data('reportePlanillaAsistencia', () => ({
        listeners: [],
        tableData: @json($empleados),
        hot: null,
        informacionAsistenciaAdicional: @json($informacionAsistenciaAdicional),
        init() {
            this.initTable();
            /*
            Livewire.on('setEmpleados', (data) => {

                    this.informacionAsistenciaAdicional = data[1];


                    this.tableData = data[0];
                    this.hot.loadData(this.tableData);
                    //location.href = location.href;
                }) */
        },
        initTable() {

            const dias = @json($dias);
            const self = this;

            let columns = [{
                data: 'grupo',
                type: 'text',
                className: 'text-center !bg-gray-100',
                title: `GRUPO`,
                readOnly: true
            },
            {
                data: 'nombres',
                type: 'text',
                title: `NOMBRES`,
                renderer: function (instance, td, row, col, prop, value, cellProperties) {

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
                    title: `${dia.titulo} <br/> ${dia.indice}`,
                    className: '!text-center',
                    renderer: function (instance, td, row, col, prop, value,
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

                        if (dia.titulo == 'D') {
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
                type: 'numeric',
                title: 'TOTAL',
                className: '!text-center',
                readOnly: true
            });


            const container = this.$refs.tableContainer;
            const hot = new Handsontable(container, {
                data: this.tableData,
                colHeaders: true,
                themeName: 'ht-theme-main',
                rowHeaders: true,
                columns: columns,
                width: '100%',
                height: 'auto',
                manualColumnResize: false,
                manualRowResize: true,
                stretchH: 'all',
                autoColumnSize: true,
                fixedColumnsLeft: 3,
                licenseKey: 'non-commercial-and-evaluation',
            });

            this.hot = hot;
            //this.hot.render();
        },
    }));
</script>
@endscript