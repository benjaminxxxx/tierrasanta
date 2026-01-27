<div>
    <x-card class="mb-5">
        @if ($riego)
            <x-flex class="justify-between">
                <div>
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
                </div>
                <div>
                    <x-button variant="danger" wire:confirm="¿Estás seguro que desea eliminar este registro?"
                        wire:click="eliminarRegador({{ $riego->id }})">
                        <i class="fa fa-trash"></i> Eliminar regador
                    </x-button>
                </div>
            </x-flex>
        @endif

        <x-label for="activar_descontar_hora_almuerzo{{ $regador }}" class="mt-4">
            <x-checkbox id="activar_descontar_hora_almuerzo{{ $regador }}"
                wire:model.live="noDescontarHoraAlmuerzo" class="mr-2" />
            No Descontar Hora de Almuerzo
        </x-label>

        <div x-data="{{ $idTable }}" wire:ignore>
            <div x-ref="tableContainer" class="mt-5" ></div>

            <x-button-save @click="sendDataRegistroDiarioRiego" class="mt-5">
                Guardar Cambios
            </x-button-save>
        </div>
    </x-card>
    <x-loading wire:loading />
</div>

@script
    <script>
        Alpine.data('{{ $idTable }}', () => ({

            tableData: [],
            hot: null,
            campos: @js($campos),
            tipoLabores: @js($tipoLabores),
            isDark: JSON.parse(localStorage.getItem('darkMode')),
            init() {

                this.initTable();
              
                Livewire.on('actualizarGrilla-{{ $idTable }}', (data) => {

                    console.log(data[0]);
                    this.tableData = data[0];
                    this.hot.loadData(this.tableData);
                });
                Livewire.on('guardarTodo', (data) => {
                    this.sendDataRegistroDiarioRiego();
                });
                $watch('darkMode', value => {

                    this.isDark = value;
                    const columns = this.generateColumns();
                    this.hot.updateSettings({
                        themeName: value ? 'ht-theme-main-dark' : 'ht-theme-main',
                        columns: columns
                    });
                });
            },
            generateColumns() {
                return [{
                        data: 'campo',
                        type: 'dropdown',
                        source: this.campos,
                        title: 'CAMPO',
                        className: 'text-center'
                    },
                    {
                        data: 'hora_inicio',
                        type: 'time',
                        width: 60,
                        timeFormat: 'H.mm',
                        correctFormat: true,
                        className: 'text-center',
                        title: `HORA INICIO`
                    },
                    {
                        data: 'hora_fin',
                        type: 'time',
                        width: 60,
                        timeFormat: 'H.mm',
                        correctFormat: true,
                        className: 'text-center',
                        title: `HORA FIN`
                    },
                    {
                        data: 'total_horas',
                        type: 'numeric',
                        width: 60,
                        readOnly: true,
                        className: 'text-center',
                        title: `TOTAL HORAS`
                    },
                    {
                        data: 'tipo_labor',
                        type: 'dropdown',
                        source: this.tipoLabores,
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
            },
            initTable() {
                const tableData2 = @json($registros);

                let columns = this.generateColumns();

                const container = this.$refs.tableContainer;
                const hot = new Handsontable(container, {
                    data: tableData2,
                    colHeaders: true,
                    rowHeaders: true,
                    themeName: this.isDark ? 'ht-theme-main-dark' : 'ht-theme-main',
                    columns: columns,
                    width: '100%',
                    manualColumnResize: false,
                    manualRowResize: true,
                    minSpareRows: 1,
                    stretchH: 'all',
                    autoColumnSize: true,
                    licenseKey: 'non-commercial-and-evaluation',
                    afterChange: (changes, source) => {
                        // Verificar que el cambio no sea causado por un "loadData" o evento de Livewire

                        if (source == 'edit' || source == 'CopyPaste.paste' || source ==
                            'timeValidator' || source == 'Autofill.fill') {
                            changes.forEach((change) => {
                                const changedRow = change[0]; // Fila que cambió
                                const fieldName = change[1]; // Nombre del campo o columna
                                const oldValue = change[2]; // Valor antiguo
                                const newValue = change[3]; // Valor nuevo

                                if (fieldName == 'hora_inicio' || fieldName == 'hora_fin') {
                                    if (oldValue != newValue) {
                                        const hora_inicio = hot.getDataAtCell(changedRow,
                                            1);
                                        const hora_salida = hot.getDataAtCell(changedRow,
                                            2);

                                        if (hora_inicio != null && hora_salida != null &&
                                            hora_inicio.trim() != '' && hora_salida
                                            .trim() != '') {


                                            const start = this.timeToMinutes(hora_inicio);
                                            const end = this.timeToMinutes(hora_salida);

                                            // Si las horas son válidas y la hora de inicio es menor que la de fin
                                            if (start <= end) {
                                                totalMinutes = end - start;
                                                const totalHours = this.minutesToTime(
                                                    totalMinutes);
                                                hot.setDataAtCell(changedRow, 3,
                                                    totalHours);

                                            }
                                        } else {
                                            console.log(hora_inicio);
                                            hot.setDataAtCell(changedRow, 3, 0);
                                        }
                                    }
                                }
                            });
                        }

                    }
                });

                this.hot = hot;
            },
            isValidTimeFormat(time) {
                const timePattern = /^([01]\d|2[0-3]).([0-5]\d)$/;
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
            sendDataRegistroDiarioRiego() {
                const rawData = this.hot.getData();

                const filteredData = rawData.filter(row => {
                    return row.some(cell => cell !== null && cell !== '');
                });

                $wire.storeTableDataRegistroDiarioRiego(filteredData);
            }
        }));
    </script>
@endscript
