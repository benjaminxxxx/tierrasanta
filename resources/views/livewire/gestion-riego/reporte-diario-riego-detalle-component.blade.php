<div>
    <x-card class="mb-5">
        <div class="lg:flex gap-5">
            <div class="lg:w-[16rem]">
                <div class="mb-3 ">
                    <x-badge>{{ $resumenRiego->alias_origen }}</x-badge>
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
                <div class="space-y-3">
                    @can(\App\Constants\Permisos::CAMPO_RIEGO_REPORTE_GESTIONAR)
                        <x-label>
                            Hora de almuerzo
                        </x-label>
                        <x-flex>
                            <x-input type="time" label="Inicio" wire:model="hora_inicio_almuerzo" />
                            <x-input type="time" label="Fin" wire:model="hora_fin_almuerzo" />
                        </x-flex>
                        <div class="mt-4">
                            <x-label>
                                Acumulación de horas
                            </x-label>
                            <x-input type="checkbox" label="No Acumular Horas" wire:model="noAcumularHoras" />
                        </div>

                    @endcan
                </div>
            </div>
            <div class="flex-1">
                @if ($resumenRiego)
                    <x-flex class="justify-between">
                        <div>
                            <livewire:gestion-riego.resumen-horas-regador-component
                                :trabajadorType="$resumenRiego->trabajador_type"
                                :trabajadorId="$resumenRiego->trabajador_id" :fecha="$fecha"
                                wire:key="resumen_horas_{{ $resumenRiego->trabajador_type }}_{{ $resumenRiego->trabajador_id }}_{{ $fecha }}" />
                            @if ($resumenRiego->trabajador_type === \App\Models\PlanEmpleado::class)
                                @if ($resumenRiego->sincronizado)
                                    <span class="text-green-600 dark:text-green-400 text-sm">
                                        <i class="fa fa-check-circle"></i> Sincronizado
                                    </span>
                                @else
                                    <span class="text-amber-600 dark:text-amber-400 text-sm"
                                        title="Las horas no coinciden con el registro diario de planilla">
                                        <i class="fa fa-exclamation-triangle"></i> No sincronizado
                                    </span>
                                @endif
                            @endif
                        </div>
                        @can(\App\Constants\Permisos::CAMPO_RIEGO_REPORTE_GESTIONAR)
                            <div>
                                @if ($resumenRiego->minutos_acumulados <= 0 && $resumenRiego->minutos_disponibles > 0)
                                    <x-button variant="info"
                                        @click="$wire.dispatch('abrirModalHorasAcumuladas',{resumenRiegoId: {{ $resumenRiego->id }}})">
                                        Usar {{ $resumenRiego->disponible_formateado }} Acumuladas
                                    </x-button>
                                @endif
                                <x-button variant="danger" title="Eliminar Regador"
                                    wire:confirm="¿Estás seguro que desea eliminar este registro?"
                                    wire:click="eliminarRegador({{ $resumenRiego->id }})">
                                    <i class="fa fa-trash"></i>
                                </x-button>
                            </div>
                        @endcan
                    </x-flex>
                @endif
                <div x-data="{{ $idTable }}">

                    <div wire:ignore>
                        <div x-ref="tableContainer" class="mt-5"></div>
                    </div>


                    <x-flex class="justify-between w-full">
                        <div>
                            @if ($registroDiarioAcumulado)
                                <div class="flex items-center gap-3">
                                    <span class="text-sm">
                                        Se usaron {{ $registroDiarioAcumulado->total_horas }} hora(s) de trabajos
                                        acumulados.
                                    </span>
                                    <x-button variant="secondary" wire:click="verDetalleAcumulado">
                                        <i class="fa fa-eye"></i> Ver detalle
                                    </x-button>
                                </div>
                            @endif
                        </div>
                        <div class="space-y-4 mt-4 text-right">
                            @if(!empty($resumenRiego->explicacion_jornal_computable))
                                <x-button type="button" @click="$wire.set('mostrarExplicacion', ! $wire.mostrarExplicacion)"
                                    class="" title="Ver detalle del cálculo de horas" variant="secondary">
                                    Jornal Computable {{ $resumenRiego->jornal_computable }} <i
                                        class="fa-solid fa-circle-question text-lg"></i>
                                </x-button>
                            @endif


                            @can(\App\Constants\Permisos::CAMPO_RIEGO_REPORTE_GESTIONAR)
                                <x-button-save @click="sendDataRegistroDiarioRiego">
                                    Guardar Cambios
                                </x-button-save>
                            @endcan
                        </div>
                    </x-flex>
                </div>
            </div>
        </div>

    </x-card>
    <x-dialog-modal maxWidth="lg" wire:model="mostrarDetalleAcumulado">
        <x-slot name="title">
            Detalle de Horas Acumuladas Usadas
        </x-slot>

        <x-slot name="content">
            <x-subtitle>
                Estas son las fechas de origen de las horas usadas hoy
                ({{ $resumenRiego->fecha }}):
            </x-subtitle>

            <x-table class="mt-4">
                <x-slot name="thead">
                    <x-tr class="">
                        <x-th class="">Fecha origen</x-th>
                        <x-th class="">Trabajador</x-th>
                        <x-th class=" text-right">Horas consumidas</x-th>
                    </x-tr>
                </x-slot>
                <x-slot name="tbody">
                    @forelse ($detalleAcumulado as $fila)
                        <x-tr class="">
                            <x-td class="">{{ $fila['fecha'] }}</x-td>
                            <x-td class="">{{ $fila['trabajador'] }}</x-td>
                            <x-td class=" text-right">{{ $fila['formateado'] }}</x-td>
                        </x-tr>
                    @empty
                        <x-tr>
                            <x-td colspan="3" class="text-center">
                                Sin registros
                            </x-td>
                        </x-tr>
                    @endforelse
                </x-slot>
                <x-slot name="tfoot">
                    <x-tr class="font-semibold">
                        <x-td colspan="2" class="text-right">Total usado:</x-td>
                        <x-td class="text-right">
                            {{ $registroDiarioAcumulado?->total_horas }} h
                        </x-td>
                    </x-tr>
                </x-slot>
            </x-table>
        </x-slot>

        <x-slot name="footer">
            <x-button variant="secondary" wire:click="$set('mostrarDetalleAcumulado', false)">
                Cerrar
            </x-button>

            @can(\App\Constants\Permisos::CAMPO_RIEGO_REPORTE_GESTIONAR)
                <x-button variant="danger" wire:click="quitarAcumulado({{ $registroDiarioAcumulado?->id }})"
                    wire:loading.attr="disabled"
                    onclick="confirm('¿Confirmas liberar estas horas acumuladas?') || event.stopImmediatePropagation()">
                    <i class="fa fa-remove"></i> Eliminar horas acumuladas
                </x-button>
            @endcan
        </x-slot>
    </x-dialog-modal>
    <!-- Desglose desplegable con el detalle del cálculo -->
    @if(!empty($resumenRiego->explicacion_jornal_computable))
        <x-dialog-modal wire:model="mostrarExplicacion" class="mt-4 text-left">
            <x-slot name="title">
                <x-flex class="justify-between">
                    <div>
                        <i class="fa-solid fa-calculator text-blue-400"></i> Desglose de Distribución por Concurrencia
                    </div>
                    <span class="text-slate-400">Total:
                        {{ $resumenRiego->jornal_computable }} hrs</span>
                </x-flex>
            </x-slot>

            <x-slot name="content">

                <div class="space-y-2 max-h-72 overflow-y-auto pr-1">
                    @foreach($resumenRiego->explicacion_jornal_computable as $tramo)
                        <div
                            class="p-2.5 rounded border {{ $tramo['es_almuerzo'] ? 'bg-amber-950/30 border-amber-800/40' : 'bg-muted border-border' }}">
                            <div class="flex justify-between items-center font-mono font-bold text-slate-200">
                                <span>{{ $tramo['tramo'] }} ({{ $tramo['duracion'] }}h)</span>
                                @if($tramo['es_almuerzo'])
                                    <span class="text-amber-400 font-sans">Almuerzo</span>
                                @endif
                            </div>
                            <p class="text-slate-400 mt-1">{{ $tramo['descripcion'] }}
                            </p>

                            @if(!$tramo['es_almuerzo'] && !empty($tramo['reparticion']))
                                <div class="mt-2 flex flex-wrap gap-2">
                                    @foreach($tramo['reparticion'] as $rep)
                                        <x-badge color="indigo">
                                            <strong>{{ $rep['campo'] }}:</strong>
                                            {{ $rep['horas'] }}h
                                        </x-badge>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </x-slot>

            <x-slot name="footer">
                <x-button @click="$wire.set('mostrarExplicacion', false)">
                    <i class="fa fa-check"></i> Aceptar
                </x-button>
            </x-slot>

        </x-dialog-modal>
    @endif


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
                timeFormat: 'H:mm',
                correctFormat: true,
                className: 'text-center',
                title: `HORA INICIO`
            },
            {
                data: 'hora_fin',
                type: 'time',
                width: 60,
                timeFormat: 'H:mm',
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
                width: 50,
                type: 'checkbox',
                title: 'SIN HABERES',
                className: '!text-center',
                checkedTemplate: true,
                uncheckedTemplate: false
            },
            {
                data: 'horas_ponderadas',
                width: 60,
                type: 'text',
                title: 'JORNAL',
                readOnly: true,
                className: '!text-center !bg-muted',
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
                    // Evitar procesar eventos automáticos o cargas iniciales
                    if (!changes || source === 'loadData' || source === 'internal') return;

                    const sourcesValidas = ['edit', 'CopyPaste.paste', 'timeValidator', 'Autofill.fill'];

                    if (sourcesValidas.includes(source)) {
                        changes.forEach((change) => {
                            const changedRow = change[0];
                            const fieldName = change[1];
                            const oldValue = change[2];
                            const newValue = change[3];

                            if (fieldName === 'hora_inicio' || fieldName === 'hora_fin') {
                                if (oldValue !== newValue) {
                                    const hora_inicio = hot.getDataAtCell(changedRow, 1);
                                    const hora_salida = hot.getDataAtCell(changedRow, 2);

                                    if (
                                        hora_inicio &&
                                        hora_salida &&
                                        String(hora_inicio).trim() !== '' &&
                                        String(hora_salida).trim() !== ''
                                    ) {
                                        const start = this.timeToMinutes(String(hora_inicio));
                                        const end = this.timeToMinutes(String(hora_salida));

                                        if (end >= start) {
                                            const totalMinutes = end - start;

                                            // Si prefieres mostrar el total en horas decimales (ej. 1.5):
                                            // const totalHours = (totalMinutes / 60).toFixed(2);

                                            // Si prefieres formato H:mm (ej. 1:30):
                                            const totalHoursDecimal = Number((totalMinutes / 60).toFixed(2));
                                            hot.setDataAtCell(changedRow, 3, totalHoursDecimal, 'internal');
                                        } else {
                                            // Si la hora final es menor a la inicial
                                            hot.setDataAtCell(changedRow, 3, 0, 'internal');
                                        }
                                    } else {
                                        hot.setDataAtCell(changedRow, 3, 0, 'internal');
                                    }
                                }
                            }
                        });
                    }
                }
            });

            this.hot = hot;
        },
        timeToMinutes(time) {
            if (!time || typeof time !== 'string' || !time.includes(':')) {
                return 0;
            }
            const [hours, minutes] = time.split(':').map(Number);
            return (hours || 0) * 60 + (minutes || 0);
        },

        minutesToTime(minutes) {
            if (isNaN(minutes) || minutes <= 0) return '0:00';

            const hours = Math.floor(minutes / 60);
            const mins = minutes % 60;

            // Devuelve horas decimales formateadas en H:mm (o HH:mm)
            return `${hours}:${String(mins).padStart(2, '0')}`;
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