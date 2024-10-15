@php
    $idTable = 'reporte_diario' . Str::random(5);
@endphp

<div x-data="{{ $idTable }}" wire:ignore>

    <x-loading wire:loading />

    <x-card class="mt-5">
        <x-spacing>
            <div class="w-full flex justify-end gap-3 mb-4">
                <x-button wire:click="addGroupBtn"><i class="fa fa-plus"></i></x-button>
                <x-danger-button wire:click="removeGroupBtn"><i class="fa fa-minus"></i></x-danger-button>
            </div>

            <div x-ref="tableContainer" class="min-h-[45rem] mt-5 overflow-auto"></div>

            <div class="text-right mt-5">
                <x-button @click="sendData">Guardar Información</x-button>
            </div>

            <div class="my-4">
                <x-table>
                    <x-slot name="thead">
                    </x-slot>
                    <x-slot name="tbody">
                        <x-tr>
                            <x-th class="!text-left">TOTAL PLANILLAS ASISTIDO</x-th>
                            <x-td class="w-[10rem]">
                                <div x-ref="total_planillas_asistido" class="p-2"></div>
                            </x-td>
                        </x-tr>
                        <x-tr>
                            <x-th class="!text-left">TOTAL FALTAS</x-th>
                            <x-td>
                                <div x-ref="total_faltas" class="p-2"></div>
                            </x-td>
                        </x-tr>
                        <x-tr>
                            <x-th class="!text-left">TOTAL VACACIONES</x-th>
                            <x-td>
                                <div x-ref="total_vacaciones" class="p-2"></div>
                            </x-td>
                        </x-tr>
                        <x-tr>
                            <x-th class="!text-left">TOTAL LICENCIA MATERNIDAD</x-th>
                            <x-td>
                                <div x-ref="total_licencia_maternidad" class="p-2"></div>
                            </x-td>
                        </x-tr>
                        <x-tr>
                            <x-th class="!text-left">TOTAL LICENCIA SIN GOCE</x-th>
                            <x-td>
                                <div x-ref="total_licencia_sin_goce" class="p-2"></div>
                            </x-td>
                        </x-tr>
                        <x-tr>
                            <x-th class="!text-left">TOTAL LICENCIA CON GOCE</x-th>
                            <x-td>
                                <div x-ref="total_licencia_con_goce" class="p-2"></div>
                            </x-td>
                        </x-tr>
                        <x-tr>
                            <x-th class="!text-left">TOTAL DESCANSO MÉDICO</x-th>
                            <x-td>
                                <div x-ref="total_descanso_medico" class="p-2"></div>
                            </x-td>
                        </x-tr>
                        <x-tr>
                            <x-th class="!text-left">TOTAL ATENCIÓN MÉDICA</x-th>
                            <x-td>
                                <div x-ref="total_atencion_medica" class="p-2"></div>
                            </x-td>
                        </x-tr>
                        <x-tr>
                            <x-th class="!text-left">TOTAL CUADRILLAS</x-th>
                            <x-td>
                                <div x-ref="total_cuadrillas" class="p-2"></div>
                            </x-td>
                        </x-tr>
                        <x-tr>
                            <x-th class="!text-left">TOTAL PLANILLA</x-th>
                            <x-td>
                                <div x-ref="total_planilla" class="p-2"></div>
                            </x-td>
                        </x-tr>
                    </x-slot>
                </x-table>
            </div>

        </x-spacing>
    </x-card>
</div>

@script
    <script>
        Alpine.data('{{ $idTable }}', () => ({
            listeners: [],
            tableData: @json($empleados),
            totales: null,
            hot: null,
            tareas: @json($tareas),
            campos: @json($campos),
            tipoAsistenciasEntidad: @json($tipoAsistenciasEntidad),
            tipoAsistencias: @json($tipoAsistencias),
            init() {
                this.initTable();
                this.listeners.push(
                    Livewire.on('setEmpleados', (data) => {
                        console.log(data);
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
                );
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
                    fixedColumnsLeft: 4,
                    licenseKey: 'non-commercial-and-evaluation',
                    afterChange: (changes, source) => {
                        // Verificar que el cambio no sea causado por un "loadData" o evento de Livewire
                        if (source !== 'loadData') {
                            this.calcularTotales();
                            /*let changedRow1 = changes[0][0];
                            let currentRow1 = changedRow1;
                            const tipoAsistencia = hot.getDataAtCell(currentRow1, 2);
                            if (tipoAsistencia != 'A') {
                                const totalHours1 = this.minutesToTime(this.tipoAsistenciasEntidad[
                                    tipoAsistencia] * 60);
                                hot.setDataAtCell(currentRow1, (4 * this.tareas + 4), totalHours1);
                            }
*/
                        }
                        /*if (source !== 'loadData' && source !== 'edit') {
                            if (!changes) {
                                return;
                            }

                            let changedRow = changes[0][0];
                            let currentRow = changedRow;
                            let startAt = 6;
                            let totalMinutes = 0;



                            for (let indice = 0; indice < this.tareas; indice++) {
                                let hora_inicio = hot.getDataAtCell(currentRow, 4 * indice +
                                    startAt);
                                let hora_salida = hot.getDataAtCell(currentRow, 4 * indice +
                                    startAt + 1);

                                // Verificar que ambas horas sean válidas
                                if (this.isValidTimeFormat(hora_inicio) && this.isValidTimeFormat(
                                        hora_salida)) {

                                    const start = this.timeToMinutes(hora_inicio);
                                    const end = this.timeToMinutes(hora_salida);

                                    // Si las horas son válidas y la hora de inicio es menor que la de fin
                                    if (start < end) {
                                        totalMinutes += end - start;

                                    }
                                }
                            }

                            const totalHours = this.minutesToTime(totalMinutes);
                            hot.setDataAtCell(currentRow, (4 * this.tareas + 4), totalHours);
                        }*/
                    }
                });

                this.hot = hot;
                this.calcularTotales();
            },
            isValidTimeFormat(time) {
                const timePattern = /^([01]\d|2[0-3]):([0-5]\d)$/;
                return timePattern.test(time);
            },
            timeToMinutes(time) {
                const [hours, minutes] = time.split(':').map(Number);
                return hours * 60 + minutes;
            },
            minutesToTime(minutes) {
                const hours = Math.floor(minutes / 60);
                const mins = minutes % 60;
                return `${String(hours).padStart(2, '0')}:${String(mins).padStart(2, '0')}`;
            },
            generateColumns(tareas) {
                let columns = [{
                        data: 'documento',
                        type: 'text',
                        width: 80,
                        title: 'DNI',
                        className: 'text-center',
                        readOnly: true
                    },
                    {
                        data: "empleado_nombre",
                        type: 'text',
                        width: 270,
                        title: 'APELLIDOS Y NOMBRES'
                    },
                    {
                        data: "asistencia",
                        type: 'dropdown',
                        source: this.tipoAsistencias,
                        width: 60,
                        title: 'ASIST.',
                        className: 'text-center'
                    },
                    {
                        data: 'numero_cuadrilleros',
                        type: 'numeric',
                        width: 50,
                        title: 'N° C.',
                        className: '!text-center'
                    }
                ];

                // Generar dinámicamente las columnas según las tareas
                for (let indice = 1; indice <= tareas; indice++) {
                    columns.push({
                        data: "campo_" + indice,
                        type: 'dropdown',
                        width: 70,
                        className: 'text-center',
                        source: this.campos,
                        title: `CAM. ${indice}`
                    }, {
                        data: "labor_" + indice,
                        type: 'text',
                        width: 70,
                        className: 'text-center',
                        title: `LAB. ${indice}`
                    }, {
                        data: "entrada_" + indice,
                        type: 'time',
                        width: 70,
                        timeFormat: 'HH:mm',
                        correctFormat: true,
                        className: 'text-center',
                        title: `ENT. ${indice}`
                    }, {
                        data: "salida_" + indice,
                        type: 'time',
                        width: 70,
                        timeFormat: 'HH:mm',
                        correctFormat: true,
                        className: 'text-center',
                        title: `SAL. ${indice}`
                    });
                }

                // Agregar columna final de TOTAL
                columns.push({
                    data: 'total_horas',
                    width: 70,
                    type: 'time',
                    timeFormat: 'HH:mm',
                    correctFormat: true,
                    title: 'TOTAL',
                    className: '!text-center font-bold text-lg',
                    renderer: function(hotInstance, td, row, col, prop, value, cellProperties) {
                        Handsontable.renderers.TextRenderer.apply(this,
                        arguments); // Render por defecto

                        // Aplicar estilos condicionales
                        if (value === '00:00' || value === '0' || value === null) {
                            // Valor es 0 o nulo -> color rojo
                            td.classList.add('!text-red-600', 'font-bold');
                        } else {
                            // Valor diferente a 0 -> color verde
                            td.classList.add('!text-green-600', 'font-bold');
                        }
                    }
                });

                return columns;
            },
            calcularTotales() {
                let totales = {
                    asistido: 0,
                    faltas: 0,
                    vacaciones: 0,
                    licenciaMaternidad: 0,
                    licenciaSinGoce: 0,
                    licenciaConGoce: 0,
                    descansoMedico: 0,
                    atencionMedica: 0,
                    cuadrillas: 0,
                    totalPlanilla: 0
                };

                const data = this.hot.getData();


                data.forEach(row => {
                    const asistencia = row[
                    2]; // Suponiendo que la columna de asistencia es la tercera (índice 2)
                    const nCuadrillas = row[3]; // Columna de número de cuadrillas

                    if (asistencia === 'A') totales.asistido++;
                    else if (asistencia === 'F') totales.faltas++;
                    else if (asistencia === 'V') totales.vacaciones++;
                    else if (asistencia === 'LM') totales.licenciaMaternidad++;
                    else if (asistencia === 'LSG') totales.licenciaSinGoce++;
                    else if (asistencia === 'LCG') totales.licenciaConGoce++;
                    else if (asistencia === 'DM') totales.descansoMedico++;
                    else if (asistencia === 'AM') totales.atencionMedica++;



                    // Sumar cuadrillas
                    if (row[1] === 'Cuadrilla') {
                        totales.cuadrillas += nCuadrillas ||
                            0; // Asegurarse de que nCuadrillas sea un número
                    }
                });

                // Calcular el total de la planilla
                totales.totalPlanilla = totales.asistido + totales.faltas + totales.vacaciones +
                    totales.licenciaMaternidad + totales.licenciaSinGoce +
                    totales.licenciaConGoce + totales.descansoMedico +
                    totales.atencionMedica;

                this.$refs.total_planillas_asistido.textContent = totales.asistido;
                this.$refs.total_faltas.textContent = totales.faltas;
                this.$refs.total_vacaciones.textContent = totales.vacaciones;
                this.$refs.total_licencia_maternidad.textContent = totales.licenciaMaternidad;
                this.$refs.total_licencia_sin_goce.textContent = totales.licenciaSinGoce;
                this.$refs.total_licencia_con_goce.textContent = totales.licenciaConGoce;
                this.$refs.total_descanso_medico.textContent = totales.descansoMedico;
                this.$refs.total_atencion_medica.textContent = totales.atencionMedica;
                this.$refs.total_cuadrillas.textContent = totales.cuadrillas;
                this.$refs.total_planilla.textContent = totales.totalPlanilla;

                this.totales = totales;
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

                console.log('Datos a enviar:', data);
                $wire.dispatchSelf('GuardarInformacion', data);
            }
        }));
    </script>
@endscript
