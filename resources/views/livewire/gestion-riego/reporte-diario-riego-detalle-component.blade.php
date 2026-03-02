<div>
    <x-card class="mb-5">
        <div class="lg:flex gap-5">
            <div class="lg:w-[16rem]">
                <div class="flex justify-between items-center mb-3 ">
                    <x-h4 class="text-left">{{ $resumenRiego->trabajador_nombre }}</x-h4>
                </div>
                <div class="text-left mb-5">
                    <p class="text-card-foreground">
                        Horas de Riego: <b>{{ formatear_minutos_horas($resumenRiego->minutos_regados) }}</b>
                    </p>
                    <p class="text-card-foreground">
                        Horas de Jornal: <b>{{ formatear_minutos_horas($resumenRiego->minutos_jornal) }}</b>
                        {{ $resumenRiego->minutos_acumulados > 0 ? ' (y se acumuló ' . formatear_minutos_horas($resumenRiego->minutos_acumulados) . ')' : '' }}
                    </p>
                </div>
                <x-label for="activar_descontar_hora_almuerzo{{ $resumenRiego->id }}" class="mt-4">
                    <x-checkbox id="activar_descontar_hora_almuerzo{{ $resumenRiego->id }}"
                        wire:model.live="noDescontarHoraAlmuerzo" class="mr-2" />
                    No Descontar Hora de Almuerzo
                </x-label>
            </div>
            <div class="flex-1">
                @if ($resumenRiego)
                    <x-flex class="justify-between">
                        <div>

                        </div>
                        <div>
                            @if ($resumenRiego->minutos_acumulados <= 0 && $resumenRiego->minutos_disponibles > 0)
                                <x-button variant="info" wire:click="abrirModalHorasAcumuladas">
                                    Usar {{ $resumenRiego->disponible_formateado }} Acumuladas
                                </x-button>
                            @endif
                            <x-button variant="danger" title="Eliminar Regador"
                                wire:confirm="¿Estás seguro que desea eliminar este registro?"
                                wire:click="eliminarRegador({{ $resumenRiego->id }})">
                                <i class="fa fa-trash"></i>
                            </x-button>
                        </div>
                    </x-flex>
                @endif
                <div x-data="{{ $idTable }}">

                    <div wire:ignore>
                        <div x-ref="tableContainer" class="mt-5"></div>
                    </div>


                    <x-flex class="justify-between w-full">
                        <div>
                            @if ($registroDiarioAcumulado)
                                Se usaron {{ $registroDiarioAcumulado->total_horas }} hora(s) de trabajos acumulados.
                                <x-button variant="danger"
                                    wire:click="quitarAcumulado({{ $registroDiarioAcumulado->id }})"><i
                                        class="fa fa-remove"></i> Quitar</x-button>
                            @endif
                        </div>

                        <x-button-save @click="sendDataRegistroDiarioRiego" class="mt-5">
                            Guardar Cambios
                        </x-button-save>
                    </x-flex>
                </div>
            </div>
        </div>

    </x-card>
    <x-dialog-modal maxWidth="lg" wire:model="mostrarHorasAcumuladasForm">
        <x-slot name="title">
            Registrar Uso de Horas Acumuladas
        </x-slot>

        <x-slot name="content">
            <div>
                <x-title value="FDM" />
            </div>
            <div class="mt-5 flex gap-5 items-start" x-data="{
                inicio: @entangle('acumulado.horaInicio'),
                fin: @entangle('acumulado.horaFin'),
                get total() {
                    if (!this.inicio || !this.fin) return '';
            
                    const [hi, mi] = this.inicio.split(':').map(Number);
                    const [hf, mf] = this.fin.split(':').map(Number);
            
                    let inicioMin = hi * 60 + mi;
                    let finMin = hf * 60 + mf;
            
                    // Si la hora final es del día siguiente
                    if (finMin < inicioMin) {
                        finMin += 24 * 60;
                    }
            
                    const diffHoras = (finMin - inicioMin) / 60;
                    return diffHoras.toFixed(2);
                }
            }">

                <x-input type="time" label="Hora de Inicio" x-model="inicio" class="w-auto" />
                <div>
                    <x-input type="time" label="Hora Final" x-model="fin" class="w-auto" />
                    <x-input-error for="acumulado.horaFin" />
                </div>
                <x-input type="number" label="Total Horas" readonly x-model="total" class="w-auto" />
            </div>

        </x-slot>

        <x-slot name="footer">
            <x-button variant="secondary" wire:click="$set('mostrarHorasAcumuladasForm', false)"
                wire:loading.attr="disabled">
                Cerrar
            </x-button>
            <x-button wire:click="registrarUsoHorasAcumuladas" wire:loading.attr="disabled">
                <i class="fa fa-save"></i> Registrar Uso de Horas Acumuladas
            </x-button>
        </x-slot>
    </x-dialog-modal>
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
                        title: 'SIN HABERES',
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
