<div>
    <x-card>
        <x-spacing>
            @if ($riego)
                <div class="flex justify-between items-center mb-3 ">
                    <x-h3 class="text-left">REGADOR - {{ $riego->regador_nombre }}</x-h3>
                </div>
                <div class="text-left mb-5">
                    <p class="font-2xl dark:text-primaryTextDark">
                        Total Horas de Riego: <b>{{ $riego->total_horas_riego }}</b>
                    </p>
                    <p class="font-2xl dark:text-primaryTextDark">
                        Total Horas de Jornal: <b>{{ $riego->total_horas_jornal }}</b>
                        {{ $riego->horasAcumuladas != '00:00' ? ' (y se acumuló ' . $riego->horasAcumuladas . ')' : '' }}
                    </p>
                </div>
            @endif

            <x-label for="activar_descontar_hora_almuerzo{{ $regador }}" class="mt-4">
                <x-checkbox id="activar_descontar_hora_almuerzo{{ $regador }}"
                    wire:model.live="noDescontarHoraAlmuerzo" class="mr-2" />
                No Descontar Hora de Almuerzo
            </x-label>

            @php
                $idTable = 'componenteTable' . Str::random(5);
            @endphp
            <div x-data="{{ $idTable }}" wire:ignore>
                <div x-ref="tableContainer" class="min-h-[20rem] mt-5 overflow-auto"></div>

                <x-button @click="sendData" class="mt-5">
                    Guardar Cambios
                </x-button>
            </div>
        </x-spacing>
    </x-card>
</div>

@script
    <script>
        Alpine.data('{{ $idTable }}', () => ({
            listeners: [],
            tableData: [],
            hot: null,
            init() {
                this.initTable();
            },
            initTable() {
                const tipoLabores = @json($tipoLabores);
                const campos = @json($campos);
                const tableData2 = @json($registros);
                console.log(tableData2);
                let columns = [{
                        data: 'campo',
                        type: 'dropdown',
                        source: campos,
                        title: 'CAMPO',
                        className: 'text-center'
                    },
                    {
                        data: 'hora_inicio',
                        type: 'time',
                        width: 60,
                        timeFormat: 'HH:mm',
                        correctFormat: true,
                        className: 'text-center',
                        title: `HORA INICIO`
                    },
                    {
                        data: 'hora_fin',
                        type: 'time',
                        width: 60,
                        timeFormat: 'HH:mm',
                        correctFormat: true,
                        className: 'text-center',
                        title: `HORA FIN`
                    },
                    {
                        data: 'total_horas',
                        type: 'time',
                        width: 60,
                        timeFormat: 'HH:mm',
                        correctFormat: true,
                        className: 'text-center',
                        title: `TOTAL HORAS`
                    },
                    {
                        data: 'tipo_labor',
                        type: 'dropdown',
                        source: tipoLabores,
                        title: 'TIPO LABOR',
                        className: 'text-center'
                    },
                    {
                        data: 'descripcion',
                        type: 'text',
                        title: 'DESCRIPCIÓN',
                        className: '!text-center'
                    },
                    {
                        data: 'sh',
                        width: 40,
                        type: 'checkbox',
                        title: 'SIN HAB.',
                        className: '!text-center',
                        checkedTemplate: true,
                        uncheckedTemplate: false
                    }
                ];

                const container = this.$refs.tableContainer;
                const hot = new Handsontable(container, {
                    data: tableData2,
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
                    licenseKey: 'non-commercial-and-evaluation',
                    afterChange: (changes, source) => {
                        // Verificar que el cambio no sea causado por un "loadData" o evento de Livewire
                        if (source !== 'loadData' && source !== 'edit') {
                            if (!changes) {
                                return;
                            }

                            let changedRow = changes[0][0];
                            let currentRow = changedRow;

                            let hora_inicio = hot.getDataAtCell(currentRow, 1);
                            let hora_salida = hot.getDataAtCell(currentRow, 2);

                            // Verificar que ambas horas sean válidas
                            if (this.isValidTimeFormat(hora_inicio) && this.isValidTimeFormat(
                                    hora_salida)) {
                                console.log(hora_salida);
                                const start = this.timeToMinutes(hora_inicio);
                                const end = this.timeToMinutes(hora_salida);

                                // Si las horas son válidas y la hora de inicio es menor que la de fin
                                if (start < end) {
                                    const totalMinutes = end - start;
                                    const totalHours = this.minutesToTime(totalMinutes);

                                    // Actualizar TOTAL HORAS
                                    hot.setDataAtCell(currentRow, 3, totalHours);
                                }
                            }
                        }
                    }
                });

                this.hot = hot;
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
            /*,
                        destroy() {
                            this.listeners.forEach((listener) => {
                                listener();
                            });
                        }*/
        }));
    </script>
@endscript
