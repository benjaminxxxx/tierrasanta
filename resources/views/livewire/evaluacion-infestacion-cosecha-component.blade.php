<div x-data="{{ $idTable }}">
    <x-card>
        <x-h3>Evaluación de Infestación</x-h3>
        <x-label>Monitoreo del crecimiento de cochinilla en pencas después de la infestación</x-label>

        <x-flex class="mt-4">
            <x-select-campo wire:model.live="campoSeleccionado" label="Seleccionar Campo" />
            <x-select wire:model.live="campaniaSeleccionada" label="Seleccionar Campaña">
                <option value="">Seleccione campaña</option>
                @foreach ($campaniasPorCampo as $campaniaPorCampo)
                    <option value="{{ $campaniaPorCampo->id }}">
                        {{ $campaniaPorCampo->nombre_campania }}
                    </option>
                @endforeach
            </x-select>
        </x-flex>

        @if ($campania && $campoSeleccionado)
            <div class="mt-6">

                @if (!$ultimaInfestacion)
                    {{-- NO HAY INFESTACIÓN --}}
                    <x-warning>
                        No hay infestaciones realizadas en
                        <b>{{ $campoSeleccionado }}</b> –
                        <b>{{ strtoupper($campania->nombre_campania) }}</b>.
                        No se puede realizar la evaluación.
                    </x-warning>

                @else
                    {{-- ÚLTIMA INFESTACIÓN --}}
                    <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-lg">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-emerald-500 flex items-center justify-center shrink-0">
                                <x-icon name="calendar" class="w-5 h-5 text-white" />
                            </div>

                            <div>
                                <p class="font-semibold text-emerald-900">
                                    Última infestación registrada
                                </p>
                                <p class="text-sm text-emerald-700">
                                    {{ \Carbon\Carbon::parse($ultimaInfestacion->fecha)->format('d/m/Y') }}
                                    • {{ ucfirst($ultimaInfestacion->metodo) }}
                                    • {{ number_format($ultimaInfestacion->infestadores, 0) }} infestadores
                                </p>
                            </div>
                        </div>
                    </div>
                @endif

            </div>
        @endif
    </x-card>
    @if ($campoSeleccionado && $campaniaSeleccionada && $ultimaInfestacion)
        <x-alert type="info" class="mt-4">
            Complete los datos de cochinillas por penca en cada columna.
            Las evaluaciones se realizan a los
            <strong>60, 75 y 100 días</strong>
            después de la infestación.
        </x-alert>
    @endif
    <x-card class="mt-4">
        <div wire:ignore>
            <div x-ref="tableContainer"></div>
        </div>
    </x-card>
    @if ($campoSeleccionado && $campaniaSeleccionada && $ultimaInfestacion)
        <x-card class="mt-4">
            <x-h3>
                Promedios Calculados
            </x-h3>

            <div class="grid md:grid-cols-3 gap-4 mt-5">

                {{-- Primera evaluación --}}
                <div class="rounded-lg border border-gray-200 dark:border-gray-700
                        bg-white dark:bg-gray-800 p-4 space-y-3">

                    <div class="text-sm font-medium text-gray-600 dark:text-gray-300">
                        1° Evaluación
                    </div>

                    <div class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                        {{ $campania->promedio_individuos_primera_eval }}
                    </div>

                    <div class="text-xs text-gray-500 dark:text-gray-400">
                        Promedio general (2° y 3° piso)
                    </div>

                    <x-input type="date" wire:model="primeraEvalFecha" class="w-full text-sm" label="Fecha" />
                </div>

                {{-- Segunda evaluación --}}
                <div class="rounded-lg border border-gray-200 dark:border-gray-700
                        bg-white dark:bg-gray-800 p-4 space-y-3">

                    <div class="text-sm font-medium text-gray-600 dark:text-gray-300">
                        2° Evaluación
                    </div>

                    <div class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                        {{ $campania->promedio_individuos_segunda_eval }}
                    </div>

                    <div class="text-xs text-gray-500 dark:text-gray-400">
                        Promedio general (2° y 3° piso)
                    </div>

                    <x-input type="date" wire:model="segundaEvalFecha" class="w-full text-sm" label="Fecha" />
                </div>

                {{-- Tercera evaluación --}}
                <div class="rounded-lg border border-gray-200 dark:border-gray-700
                        bg-white dark:bg-gray-800 p-4 space-y-3">

                    <div class="text-sm font-medium text-gray-600 dark:text-gray-300">
                        3° Evaluación
                    </div>

                    <div class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                        {{ $campania->promedio_individuos_tercera_eval }}
                    </div>

                    <div class="text-xs text-gray-500 dark:text-gray-400">
                        Promedio general (2° y 3° piso)
                    </div>

                    <x-input type="date" wire:model="terceraEvalFecha" class="w-full text-sm" label="Fecha" />
                </div>

            </div>

        </x-card>


        <x-card class="mt-4">
            <x-h3>
                Proyección de Cosecha
            </x-h3>

            {{-- Primera fila: Cochinillas por gramo --}}
            <div class="mb-6">
                <x-input id="cochinillas_gramo" type="number" wire:model="proyeccionCochinillaXGramo"
                    class="text-lg font-semibold" placeholder="Ej: 500" label="N° Cochinillas por Gramo" />
            </div>

            {{-- Segunda fila: Resultados calculados --}}
            <div class="grid md:grid-cols-3 gap-4">

                {{-- Gramos por penca --}}
                <div class="p-4 rounded-md border border-gray-700 bg-gray-700">
                    <p class="text-sm font-medium text-gray-300 mb-1">
                        Gramos Cochinilla por Penca
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-100 mb-2">
                        Promedio individuos / n° de cochinillas por gramo
                    </p>
                    <p class="text-2xl font-semibold text-gray-100">
                        {{ formatear_numero($campania->eval_proj_gramos_cochinilla_x_penca ?? 0) }} g
                    </p>
                </div>

                {{-- Número de pencas infestadas --}}
                <div class="p-4 rounded-md border border-gray-700 bg-gray-700">
                    <p class="text-sm font-medium text-gray-300 mb-1">
                        Número de Pencas Infestadas
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-100 mb-2">
                        Total de pencas
                    </p>
                    <p class="text-2xl font-semibold text-gray-100">
                        {{ formatear_numero($campania->eval_cosch_proj_penca_inf ?? 0) }}
                    </p>
                </div>

                {{-- Rendimiento por hectárea --}}
                <div class="p-4 rounded-md border border-gray-600 bg-gray-700">
                    <p class="text-sm font-medium text-gray-300 mb-1">
                        Rendimiento por Hectárea
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-100 mb-2">
                        (Gramos × Pencas) ÷ 1000
                    </p>
                    <p class="text-3xl font-semibold text-gray-50">
                        {{ formatear_numero($campania->eval_cosch_proj_rdto_ha ?? 0) }} kg
                    </p>
                </div>

            </div>
        </x-card>

        <x-card class="mt-4">
            <x-flex class="justify-end">
                <x-button type="button" @click="sendDataEvaluacionInfestacion()">
                    <i class="fa fa-save"></i> Guardar Evaluación
                </x-button>
            </x-flex>
        </x-card>
    @endif

    <x-loading wire:loading />
</div>
@script
<script>
    Alpine.data('{{ $idTable }}', () => ({
        tableData: @json($table),
        hot: null,
        init() {
            this.initTable();
            Livewire.on('recargarEvaluacion', (data) => {
                this.tableData = data[0].table;
                this.hot.destroy();
                this.initTable();
                this.hot.loadData(this.tableData);
            });
            Livewire.on('guardadoConfirmado', () => {
                this.sendDataPoblacionPlanta();
            });
        },
        initTable() {

            const container = this.$refs.tableContainer;
            const hot = new Handsontable(container, {
                data: this.tableData,
                colHeaders: true,
                columns: this.getColumns(),
                nestedHeaders: this.getNestedHeaders(),
                height: 'auto',
                manualColumnResize: false,
                manualRowResize: true,
                stretchH: 'all',
                autoColumnSize: false,
                licenseKey: 'non-commercial-and-evaluation',

            });

            this.hot = hot;
        },
        getColumns() {
            return [
                {
                    data: 'n_pencas',
                    type: 'numeric',
                    className: 'htCenter htMiddle font-semibold',
                    readOnly: true
                },

                // Evaluación 1
                { data: 'eval_primera_piso_2', type: 'numeric', className: 'htCenter' },
                { data: 'eval_primera_piso_3', type: 'numeric', className: 'htCenter' },

                // Evaluación 2
                { data: 'eval_segunda_piso_2', type: 'numeric', className: 'htCenter' },
                { data: 'eval_segunda_piso_3', type: 'numeric', className: 'htCenter' },

                // Evaluación 3
                { data: 'eval_tercera_piso_2', type: 'numeric', className: 'htCenter' },
                { data: 'eval_tercera_piso_3', type: 'numeric', className: 'htCenter' },
            ];
        },

        getNestedHeaders() {
            return [
                [
                    'N° PENCA',
                    { label: 'Evaluación 1<br><small>60–70 días</small>', colspan: 2 },
                    { label: 'Evaluación 2<br><small>75–85 días</small>', colspan: 2 },
                    { label: 'Evaluación 3<br><small>100–120 días</small>', colspan: 2 },
                ],
                [
                    '', // simula rowspan de "N° PENCA"
                    '2° Piso', '3° Piso',
                    '2° Piso', '3° Piso',
                    '2° Piso', '3° Piso',
                ]
            ];
        },
        sendDataEvaluacionInfestacion() {
            let allData = [];

            // Recorre todas las filas de la tabla y obtiene los datos completos
            for (let row = 0; row < this.hot.countRows(); row++) {
                const rowData = this.hot.getSourceDataAtRow(row);
                allData.push(rowData);
            }

            // Filtra las filas vacías
            const filteredData = allData.filter(row => row && Object.values(row).some(cell => cell !==
                null && cell !== ''));

            $wire.guardarDatosEvaluacionInfestacionCosecha(filteredData);
        }
    }));
</script>
@endscript