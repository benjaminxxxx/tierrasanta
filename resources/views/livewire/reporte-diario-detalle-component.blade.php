@php
    $idTable = 'reporte_diario' . Str::random(5);
@endphp

<div>

    <x-loading wire:loading />

    <x-card class="mt-5">
        <x-spacing>


            <div x-data="{{ $idTable }}" wire:ignore>
                <x-flex class="justify-end">
                    <div>
                        <x-button @click="agregarTramo" class="w-full lg:w-auto">
                            <i class="fa fa-plus"></i> Agregar tramo
                        </x-button>
                        <x-danger-button @click="quitarTramo" class="w-full lg:w-auto">
                            <i class="fa fa-minus"></i> Quitar tramo
                        </x-danger-button>
                    </div>
                </x-flex>
                <div x-ref="tableContainer" class="mt-5"></div>
                <div class="text-right mt-5">
                    <x-button @click="sendDataReporteDiarioPlanilla">
                        <i class="fa fa-save"></i> Guardar Información
                    </x-button>
                </div>
            </div>



            <div class="my-4">
                <x-table>
                    <x-slot name="thead">
                    </x-slot>
                    <x-slot name="tbody">
                        @if ($totalesAsistencias)
                            @foreach ($totalesAsistencias as $totalesAsistencia)
                                <x-tr>
                                    <x-th class="!text-left text-gray-800 dark:text-gray-200">TOTAL
                                        {{mb_strtoupper($totalesAsistencia['descripcion'])}}</x-th>
                                    <x-td class="w-[10rem]">
                                        <div x-ref="total_planillas_asistido" class="p-2">{{$totalesAsistencia['total']}}</div>
                                    </x-td>
                                </x-tr>
                            @endforeach
                        @endif

                        <x-tr>
                            <x-th class="!text-left  text-gray-800 dark:text-gray-200">TOTAL CUADRILLAS</x-th>
                            <x-td>
                                <div x-ref="total_cuadrillas" class="p-2">{{$totalesAsistenciasCuadrilleros}}</div>
                            </x-td>
                        </x-tr>
                        @if ($reporteDiarioCampos)
                            <x-tr>
                                <x-th class="!text-left  text-gray-800 dark:text-gray-200"><b>TOTAL PLANILLA</b></x-th>
                                <x-td>
                                    <div x-ref="total_planilla" class="p-2">{{$reporteDiarioCampos->total_planilla}}</div>
                                </x-td>
                            </x-tr>
                        @endif

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
        tareas: @entangle('tareas'),
        campos: @json($campos),
        tipoAsistenciasHoras: @json($tipoAsistenciasHoras),
        tipoAsistenciasCodigos: @json($tipoAsistenciasCodigos),
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
           
        },
        agregarTramo() {
            this.tareas++;
            const columns = this.generateColumns(this.tareas);
            this.hot.updateSettings({
                columns: columns
            });
        },
        quitarTramo() {
            if (this.tareas == 0) {
                return;
            }
            this.tareas--;
            const columns = this.generateColumns(this.tareas);
            this.hot.updateSettings({
                columns: columns
            });
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
                themeName: 'ht-theme-main',
                rowHeaders: true,
                columns: columns,
                width: '100%',
                height: 'auto',
                manualColumnResize: false,
                manualRowResize: true,
                //minSpareRows: 1,
                stretchH: 'all',
                autoColumnSize: true,
                autoRowSize: true,
                fixedColumnsLeft: 4,
                licenseKey: 'non-commercial-and-evaluation',
                contextMenu: {
                    items: {
                        "remove_quadrillero": {
                            name: 'Eliminar planillero(s)',
                            callback: () => this.eliminarPlanilleroSeleccionado()
                        }
                    }
                },

                afterChange: (changes, source) => {

                    //console.log(source);
                    if (source === 'recalculado' || source === 'loadData') {
                        console.log(source);
                        return; // evitar loops infinitos
                    }

                    if (source === 'edit' || 
                        source === 'CopyPaste.paste' ||
                        source === 'timeValidator' ||
                        source === 'Autofill.fill') {
                            
                        this.hasUnsavedChanges = true;

                        const filasMap = new Map();
                        changes.forEach(([row]) => {
                            if (!filasMap.has(row)) {
                                filasMap.set(row, this.hot.getDataAtRow(row)); // ⛳️ Solo una llamada por fila
                            }
                        });
                        filasMap.forEach((data, row) => {
                            this.recalcularTotales(data, row);
                        });

                    }
                }

            });

            this.hot = hot;
            //this.hot.render();

            window.addEventListener('beforeunload', (event) => {
                if (this.hasUnsavedChanges) {
                    const confirmationMessage =
                        'Tienes cambios no guardados. ¿Estás seguro de que deseas salir?';
                    event.returnValue = confirmationMessage; // Mostrar el mensaje de advertencia
                    return confirmationMessage;
                }
            });
        },
        recalcularTotales(data, row) {
            console.log(data, row);
            const indiceTotal = data.length - 2;
            const tipoAsistencia = data[1]; // antes era data[2]

            if (tipoAsistencia !== 'A' && tipoAsistencia !== '' && tipoAsistencia !== null) {
                const totalHoras = this.minutesToTime(this.tipoAsistenciasHoras[tipoAsistencia] * 60);
                this.hot.setDataAtCell(row, indiceTotal, totalHoras, 'recalculado');
                return;
            }

            let totalMinutos = 0;

            // Empieza desde el índice 3, recorre de 4 en 4 hasta antes de los 2 últimos
            for (let i = 3; i <= data.length - 6; i += 4) {
                const dias = parseFloat(data[i]);
                const cantidad = parseFloat(data[i + 1]);
                const horaInicio = data[i + 2];
                const horaFin = data[i + 3];

                if (horaInicio == null || horaFin == null) {
                    continue;
                }

                const inicioMin = this.timeToMinutes(horaInicio);
                const finMin = this.timeToMinutes(horaFin);

                if (finMin <= inicioMin) {
                    continue;
                }

                const minutos = finMin - inicioMin;
                totalMinutos += minutos;
            }

            const totalHoras = this.minutesToTime(totalMinutos);
            this.hot.setDataAtCell(row, indiceTotal, totalHoras, 'recalculado');
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
                data: "empleado_nombre",
                type: 'text',
                width: 220,
                className: '!bg-gray-100',
                title: 'APELLIDOS Y NOMBRES'
            },
            {
                data: "asistencia",
                type: 'dropdown',
                source: this.tipoAsistenciasCodigos,
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
                const intercalado = (indice % 2 === 0) ? '!bg-blue-100' : '';
                columns.push({
                    data: "campo_" + indice,
                    type: 'dropdown',
                    width: 50,
                    className: `text-center ${intercalado}`,
                    source: this.campos,
                    title: `CAM. ${indice}`
                }, {
                    data: "labor_" + indice,
                    type: 'text',
                    width: 40,
                    className: `text-center ${intercalado}`,
                    title: `LAB. ${indice}`
                }, {
                    data: "entrada_" + indice,
                    type: 'time',
                    width: 50,
                    timeFormat: 'H.mm',
                    correctFormat: true,
                    className: `text-center ${intercalado}`,
                    title: `ENT. ${indice}`
                }, {
                    data: "salida_" + indice,
                    type: 'time',
                    width: 50,
                    timeFormat: 'H.mm',
                    correctFormat: true,
                    className: `text-center ${intercalado}`,
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
                renderer: function (hotInstance, td, row, col, prop, value, cellProperties) {
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
                renderer: function (hotInstance, td, row, col, prop, value, cellProperties) {
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
        eliminarPlanilleroSeleccionado() {
            const selected = this.hot.getSelected();
            let planillerosAEliminar = [];

            if (selected) {
                selected.forEach(range => {
                    const [startRow, , endRow] = range;
                    for (let row = startRow; row <= endRow; row++) {
                        const cuadrillero = this.hot.getSourceDataAtRow(row);
                        planillerosAEliminar.push(cuadrillero);
                    }
                });
                const data = {
                    planillas: planillerosAEliminar
                };
                $wire.dispatch('eliminarPlanilla', data);
            }
        },
        sendDataReporteDiarioPlanilla() {
            const rawData = this.hot.getData(); // solo obtiene los valores visibles
            const sourceData = this.hot.getSourceData(); // incluye columnas ocultas si fuera necesario

            const filteredData = rawData.map((row, index) => {
                const documento = sourceData[index]?.documento ?? ''; // puedes ajustar la fuente del documento aquí
                return [documento, ...row]; // Insertar en índice 0
            }).filter(row => {
                return row.some(cell => cell !== null && cell !== '');
            });

            $wire.guardarInformacionRegistroPlanilla(filteredData);
            this.hasUnsavedChanges = false;
        }
    }));
</script>
@endscript