@php
    $idTable = 'reporte_diario' . Str::random(5);
@endphp

<div x-data="{{ $idTable }}" wire:ignore>

    <x-loading wire:loading />

    <x-card class="mt-5">
        <x-spacing>
            <div class="w-full lg:flex justify-between gap-3 mb-4">
                <div class="flex items-center gap-3">
                    <x-input type="number" wire:model="minutosDescontados" placeholder="Descuento en minutos" />
                    <x-secondary-button wire:click="aplicarDescuento" class="whitespace-nowrap">
                        Aplicar <span class="hidden lg:inline">descuento</span>
                    </x-secondary-button>
                </div>
                <div class="flex justify-end gap-3 mb-4 my-3 lg:my-0">
                    <x-button wire:click="addGroupBtn" class="w-full lg:w-auto"><i class="fa fa-plus"></i></x-button>
                    <x-danger-button wire:click="removeGroupBtn" class="w-full lg:w-auto"><i
                            class="fa fa-minus"></i></x-danger-button>
                </div>
            </div>

            <div x-ref="tableContainer" class="mt-5 overflow-auto"></div>

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
            hasUnsavedChanges: false,
            minutosDescontados: @json($minutosDescontados),
            init() {
                this.initTable();
                this.listeners.push(
                    Livewire.on('setEmpleados', (data) => {
                        console.log(data);
                        let empleados = data[0];
                        this.tableData = empleados;
                        this.hot.loadData(this.tableData);

                        //location.href = location.href;
                    })
                );
                this.listeners.push(
                    Livewire.on('setColumnas', (data) => {

                        const tareas = data[0];
                        const columns = this.generateColumns(tareas);
                        this.hot.updateSettings({
                            columns: columns
                        });
                        this.tareas = tareas;

                        // Vuelve a cargar los datos actuales en la tabla (si fuera necesario)
                        this.hot.loadData(this.tableData);
                        //location.href = location.href;
                    })
                );
                this.listeners.push(
                    Livewire.on('recalcular', (data) => {
                        const rawData = this.hot.getData();
                        this.minutosDescontados = data[1];
                        this.hasUnsavedChanges = data[0];

                        rawData.forEach((row, rowIndex) => {
                            const camposNoNulos = row[1] != null && row[2] != null;

                            const nombreCuadrilla = row[1].trim();
                            const tipoAsistencia = row[2];
                            const numeroCuadrilla = row[3];
                            const campo1 = row[4];
                            const labor1 = row[5];

                            //FILTRO PARA EVITAR A LOS CUADRILLEROS QUE YA TIENEN TODO CALCULADO
                            if ((labor1 == '81' || labor1 == 81) && campo1 == 'FDM' &&
                                tipoAsistencia == 'A') {

                                console.log('noper' + labor1 + ' campo: ' + campo1 +
                                    ' tipoAsistencia: ' + tipoAsistencia);
                            } else {

                                let totalMinutes = 0;
                                const startAt = 6;

                                const indiceTotal = (4 * this.tareas + 4);

                                for (let indice = 0; indice < this.tareas; indice++) {
                                    const hora_inicio = row[4 * indice + startAt];
                                    const hora_salida = row[4 * indice + startAt + 1];

                                    if (hora_inicio != null && hora_salida != null) {
                                        const start = this.timeToMinutes(hora_inicio);
                                        const end = this.timeToMinutes(hora_salida);

                                        if (start < end) {
                                            totalMinutes += end - start;
                                        }
                                    }
                                }

                                if (totalMinutes != 0) {
                                    const nombreCuadrillaLower = nombreCuadrilla?.toString()
                                        .toLowerCase();
                                    const totalMinutesAdjusted = totalMinutes - this
                                        .minutosDescontados;
                                    let totalHours = this.minutesToTime(totalMinutesAdjusted);

                                    if (nombreCuadrillaLower == 'cuadrilla') {
                                        totalHours = this.minutesToTime(totalMinutesAdjusted *
                                            numeroCuadrilla);
                                    }
                                    this.hot.setDataAtCell(rowIndex, indiceTotal, totalHours);
                                }
                            }
                            /*
                                                        if (camposNoNulos) {
                                                            
                                                        }*/
                        });
                    })
                );
            },
            initTable() {
                const tareas = this.tareas;
                const columns = this.generateColumns(tareas);
                let primeraCarga = 0;
                let isUpdating = false

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
                    autoRowSize: true,
                    fixedColumnsLeft: 4,
                    licenseKey: 'non-commercial-and-evaluation',
                    afterChange: (changes, source) => {

                        if (source === 'loadData') {
                            return; // No hacer nada si los datos se están cargando.
                        }

                        // Verificar que el cambio no sea causado por un "loadData" o evento de Livewire
                        if (source !== 'loadData') {
                            this.calcularTotales();
                        }

                        if (source == 'edit' || source == 'CopyPaste.paste' || source ==
                            'timeValidator' || source == 'Autofill.fill') {

                            this.hasUnsavedChanges = true;

                            changes.forEach((change) => {
                                const changedRow = change[0]; // Fila que cambió
                                const fieldName = change[1]; // Nombre del campo o columna
                                const oldValue = change[2]; // Valor antiguo
                                const newValue = change[3]; // Valor nuevo



                                if (fieldName === 'total_horas' || fieldName ===
                                    'bono_productividad' || fieldName === 'empleado_nombre'
                                    ) {
                                    return;
                                }

                                // Verificar si el nombre del campo comienza con "campo_" o "labor_"
                                if (/^(campo_|labor_)/.test(fieldName)) {
                                    return;
                                }


                                if (fieldName == 'asistencia') {
                                    // Verificar si el nuevo valor es válido en tipoAsistenciasEntidad
                                    if (!this.tipoAsistenciasEntidad.hasOwnProperty(
                                            newValue)) {
                                        return; // Si no existe el valor en tipoAsistenciasEntidad, retornar sin hacer nada
                                    }

                                    // Continuar solo si newValue no es 'A' ni vacío
                                    if (newValue != 'A' && newValue != '') {
                                        const totalHours1 = this.minutesToTime(this
                                            .tipoAsistenciasEntidad[newValue] * 60);
                                        hot.setDataAtCell(changedRow, (4 * this.tareas + 4),
                                            totalHours1);

                                        return;
                                    }

                                }

                                let totalMinutes = 0;

                                const startAt = 6;


                                if (oldValue != newValue) {


                                    for (let indice = 0; indice < this.tareas; indice++) {

                                        const hora_inicio = hot.getDataAtCell(changedRow,
                                            4 * indice +
                                            startAt);
                                        const hora_salida = hot.getDataAtCell(changedRow,
                                            4 * indice +
                                            startAt + 1);



                                        if (hora_inicio != null && hora_salida != null) {


                                            const start = this.timeToMinutes(hora_inicio);
                                            const end = this.timeToMinutes(hora_salida);

                                            // Si las horas son válidas y la hora de inicio es menor que la de fin
                                            if (start < end) {
                                                totalMinutes += end - start;


                                            }
                                        }


                                    }

                                    const numeroCuadrilla = hot.getDataAtCell(changedRow,
                                    3);
                                    const esCuadrilla = hot.getDataAtCell(changedRow, 1)
                                        ?.toString().toLowerCase()
                                    .trim(); // Convertir a string y minúsculas
                                    console.log(esCuadrilla);

                                    const totalMinutesAdjusted = totalMinutes - this
                                        .minutosDescontados;
                                    let totalHours;
                                    // Calcular el total de horas dependiendo de si es "cuadrilla"
                                    if (esCuadrilla == 'cuadrilla') {
                                        totalHours = this.minutesToTime(
                                            totalMinutesAdjusted * numeroCuadrilla);
                                    } else {
                                        totalHours = this.minutesToTime(
                                            totalMinutesAdjusted);
                                    }

                                    // Establecer el valor calculado en la celda correspondiente
                                    hot.setDataAtCell(changedRow, 4 * this.tareas + 4,
                                        totalHours);

                                }

                            });

                        }
                    }
                });

                this.hot = hot;
                this.hot.render();
                this.calcularTotales();

                window.addEventListener('beforeunload', (event) => {
                    if (this.hasUnsavedChanges) {
                        const confirmationMessage =
                            'Tienes cambios no guardados. ¿Estás seguro de que deseas salir?';
                        event.returnValue = confirmationMessage; // Mostrar el mensaje de advertencia
                        return confirmationMessage;
                    }
                });
            },
            isValidTimeFormat(time) {
                const timePattern = /^([01]\d|2[0-3]):([0-5]\d)$/;
                return timePattern.test(time);
            },
            timeToMinutes(time) {
                const [hours, minutes] = time.split('.').map(Number);
                return hours * 60 + minutes;
            },
            minutesToTime(minutes) {
                const hours = Math.floor(minutes / 60);
                const mins = minutes % 60;
                return `${String(hours).padStart(2, '0')}.${String(mins).padStart(2, '0')}`;
            },
            generateColumns(tareas) {
                let columns = [{
                        data: 'documento',
                        type: 'text',
                        width: 80,
                        title: 'DNI',
                        className: 'text-center !bg-gray-100',
                        readOnly: true
                    },
                    {
                        data: "empleado_nombre",
                        type: 'text',
                        width: 270,
                        className: '!bg-gray-100',
                        title: 'APELLIDOS Y NOMBRES'
                    },
                    {
                        data: "asistencia",
                        type: 'dropdown',
                        source: this.tipoAsistencias,
                        width: 60,
                        title: 'ASIST.',
                        className: 'text-center !bg-gray-100'
                    },
                    {
                        data: 'numero_cuadrilleros',
                        type: 'numeric',
                        width: 50,
                        title: 'N° C.',
                        className: '!text-center !bg-gray-100'
                    }
                ];

                // Generar dinámicamente las columnas según las tareas
                for (let indice = 1; indice <= tareas; indice++) {
                    columns.push({
                        data: "campo_" + indice,
                        type: 'dropdown',
                        width: 50,
                        className: 'text-center',
                        source: this.campos,
                        title: `CAM. ${indice}`
                    }, {
                        data: "labor_" + indice,
                        type: 'text',
                        width: 40,
                        className: 'text-center',
                        title: `LAB. ${indice}`
                    }, {
                        data: "entrada_" + indice,
                        type: 'time',
                        width: 50,
                        timeFormat: 'H.mm',
                        correctFormat: true,
                        className: 'text-center',
                        title: `ENT. ${indice}`
                    }, {
                        data: "salida_" + indice,
                        type: 'time',
                        width: 50,
                        timeFormat: 'H.mm',
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
                    timeFormat: 'H.mm',
                    //correctFormat: true,
                    title: 'TOTAL',
                    className: '!text-center font-bold text-lg',
                    renderer: function(hotInstance, td, row, col, prop, value, cellProperties) {
                        Handsontable.renderers.TextRenderer.apply(this,
                            arguments); // Render por defecto

                        // Aplicar estilos condicionales
                        if (value === '00:00' || value === '0' || value === '0.00' || value ===
                            '00.00' || value === null) {
                            // Valor es 0 o nulo -> color rojo
                            td.classList.add('!text-red-600', 'font-bold');
                        } else {
                            // Valor diferente a 0 -> color verde
                            td.classList.add('!text-green-600', 'font-bold');
                        }
                    }
                }, {
                    data: 'bono_productividad',
                    width: 70,
                    type: 'numeric',
                    numericFormat: {
                        pattern: '##,##0.00', // Formato de miles con al menos dos decimales
                        culture: 'en-US' // Cultura para usar coma como separador de miles y punto para decimales
                    },
                    correctFormat: true,
                    title: 'BONO',
                    className: '!text-right font-bold text-lg',
                    renderer: function(hotInstance, td, row, col, prop, value, cellProperties) {
                        Handsontable.renderers.TextRenderer.apply(this,
                            arguments); // Render por defecto

                        // Aplicar estilos condicionales
                        if (value > 0) {
                            // Valor es 0 o nulo -> color rojo

                            td.classList.add('!text-green-600', 'font-bold');
                        } else {
                            // Valor diferente a 0 -> color verde
                            td.classList.add('!text-red-600', 'font-bold');
                        }

                        if (typeof value === 'number') {
                            td.innerHTML = new Intl.NumberFormat('en-US', {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2
                            }).format(value);
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

                $wire.dispatchSelf('GuardarInformacion', data);
                this.hasUnsavedChanges = false;
            }
        }));
    </script>
@endscript
