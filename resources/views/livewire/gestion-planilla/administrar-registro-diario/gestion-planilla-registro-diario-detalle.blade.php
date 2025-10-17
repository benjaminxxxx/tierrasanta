<div x-data="registroDiarioActividades">
    <x-card2 class="mt-4">
        <x-flex class="justify-end">
            <div>
                <x-button @click="agregarTramo">
                    <i class="fa fa-plus"></i> Agregar tramo
                </x-button>
                <x-button @click="quitarTramo" variant="danger">
                    <i class="fa fa-minus"></i> Quitar tramo
                </x-button>
            </div>
        </x-flex>
        <div wire:ignore>
            <div x-ref="tableContainer" class="mt-5"></div>
        </div>

        <div class="my-4">
            <x-table class="max-w-lg border border-gray-400 dark:border-gray-500 rounded">
                <x-slot name="thead">
                </x-slot>
                <x-slot name="tbody">
                    @if ($resumenDiarioPlanilla->totales()->count() > 0)
                        @foreach ($resumenDiarioPlanilla->totales as $resumenTotal)
                            <x-tr>
                                <x-th class="!text-left text-gray-800 dark:text-gray-200">TOTAL
                                    {{mb_strtoupper($resumenTotal->descripcion)}}</x-th>
                                <x-td class="w-[10rem]">
                                    <div x-ref="total_planillas_asistido" class="p-2">{{$resumenTotal->total_asistidos}}</div>
                                </x-td>
                            </x-tr>
                        @endforeach
                    @endif


                    @if ($resumenDiarioPlanilla)
                        <x-tr>
                            <x-th class="!text-left  text-gray-800 dark:text-gray-200">TOTAL CUADRILLAS</x-th>
                            <x-td>
                                <div class="p-2">{{$resumenDiarioPlanilla->total_cuadrillas}}</div>
                            </x-td>
                        </x-tr>
                        <x-tr>
                            <x-th class="!text-left  text-gray-800 dark:text-gray-200"><b>TOTAL PLANILLA</b></x-th>
                            <x-td>
                                <div x-ref="total_planilla" class="p-2">{{$resumenDiarioPlanilla->total_planilla}}</div>
                            </x-td>
                        </x-tr>
                    @endif

                </x-slot>
            </x-table>
        </div>
        <div class="text-right mt-5">
            <x-button @click="enviarRegistrosDiariosPlanilla">
                <i class="fa fa-save"></i> Guardar Información
            </x-button>
        </div>
    </x-card2>
    <x-loading wire:loading />
</div>
@script
<script>
    Alpine.data('registroDiarioActividades', () => ({
        listeners: [],
        tableData: @json($empleados),
        totales: null,
        hot: null,
        totalActividades: @entangle('totalActividades'),
        campos: @json($campos),
        tipoAsistenciasHoras: @json($tipoAsistenciasHoras),
        tipoAsistenciasCodigos: @json($tipoAsistenciasCodigos),
        hasUnsavedChanges: false,
        init() {
            Livewire.on('setEmpleados', (data) => {
                let empleados = data[0];
                this.tableData = empleados;
                this.hot.loadData(this.tableData);
            })
            this.initTable();
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
        initTable() {
            const totalActividades = this.totalActividades;
            const columns = this.generateColumns(totalActividades);
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
                stretchH: 'all',
                autoColumnSize: true,
                autoRowSize: true,
                fixedColumnsLeft: 3,
                licenseKey: 'non-commercial-and-evaluation',
                /*
                contextMenu: {
                    items: {
                        "remove_quadrillero": {
                            name: 'Eliminar planillero(s)',
                            callback: () => this.eliminarPlanilleroSeleccionado()
                        }
                    }
                },
                */
                afterChange: (changes, source) => {

                    //console.log(source);
                    if (source === 'recalculado' || source === 'loadData') {
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
                                filasMap.set(row, this.hot.getDataAtRow(row)); 
                            }
                        });
                        filasMap.forEach((data, row) => {
                            this.recalcularTotales(data, row);
                        });

                    }
                }

            });

            this.hot = hot;

            window.addEventListener('beforeunload', (event) => {
                if (this.hasUnsavedChanges) {
                    const confirmationMessage =
                        'Tienes cambios no guardados. ¿Estás seguro de que deseas salir?';
                    event.returnValue = confirmationMessage; // Mostrar el mensaje de advertencia
                    return confirmationMessage;
                }
            });
        },
        agregarTramo() {
            this.totalActividades++;
            const columns = this.generateColumns(this.totalActividades);
            this.hot.updateSettings({
                columns: columns
            });
        },
        quitarTramo() {
            if (this.totalActividades == 1) {
                return;
            }
            this.totalActividades--;
            const columns = this.generateColumns(this.totalActividades);
            this.hot.updateSettings({
                columns: columns
            });
        },
        generateColumns(totalActividades) {
            let columns = [{
                data: "nombres",
                type: 'text',
                readOnly: true,
                className: '!bg-gray-100',
                title: 'APELLIDOS Y NOMBRES'
            },
            {
                data: "asistencia",
                type: 'dropdown',
                source: this.tipoAsistenciasCodigos,
                strict: true,
                width: 60,
                title: 'ASIST.',
                className: 'text-center !bg-gray-100'
            },
            {
                data: 'numero_cuadrilleros',
                type: 'numeric',
                width: 50,
                title: 'N° C.',
                readOnly: true,
                className: '!text-center !bg-gray-100'
            }
            ];

            for (let indice = 1; indice <= totalActividades; indice++) {
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
                type: 'numeric',
                readOnly: true,
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
                data: 'total_bono',
                width: 70,
                type: 'numeric',
                numericFormat: {
                    pattern: '##,##0.00', // Formato de miles con al menos dos decimales
                    culture: 'en-US' // Cultura para usar coma como separador de miles y punto para decimales
                },
                readOnly: true,
                title: 'BONO',
                className: '!text-center font-bold text-lg !bg-gray-100',
                renderer: function (hotInstance, td, row, col, prop, value, cellProperties) {
                    Handsontable.renderers.TextRenderer.apply(this,
                        arguments); // Render por defecto


                    td.classList.remove('htDimmed');
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
        recalcularTotales(data, row) {
            
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
        enviarRegistrosDiariosPlanilla() {
            const totalVisualRows = this.hot.countRows();
            const resultados = [];

            for (let visual = 0; visual < totalVisualRows; visual++) {
                const physical = this.hot.toPhysicalRow(visual);
                const fuente = this.hot.getSourceDataAtRow(physical); // objeto original con keys

                if (!fuente) continue;

                // ignorar filas vacías (ajusta según tus campos clave)
                const isEmpty = Object.values(fuente).every(v => v === null || v === '');
                if (isEmpty) continue;

                resultados.push(fuente);
            }

            // enviar objetos al backend
            $wire.guardarInformacionRegistroPlanilla(resultados);
            this.hasUnsavedChanges = false;
        }

    }))
</script>
@endscript