@php
    $mostrarTabla = $campania && $campoSeleccionado && $ultimaInfestacion;
    $promedios = [1 => $campania?->promedio_individuos_primera_eval, 2 => $campania?->promedio_individuos_segunda_eval, 3 => $campania?->promedio_individuos_tercera_eval];
    $fechasModelo = [1 => 'primeraEvalFecha', 2 => 'segundaEvalFecha', 3 => 'terceraEvalFecha'];
@endphp
<div x-data="{{ $idTable }}" class="space-y-3">
    <div>
        <x-title>Evaluación de Infestación</x-title>
        <x-subtitle>Cochinilla por penca después de la infestación y proyección de cosecha</x-subtitle>
    </div>

    <x-card class="space-y-3">
        <div class="flex flex-wrap items-end gap-3">
            @can(\App\Constants\Permisos::INFESTACION_EVALUACION_VER)
                <x-select-campo wire:model.live="campoSeleccionado" label="Campo" class="w-auto" />
                <x-select wire:model.live="campaniaSeleccionada" label="Campaña" class="w-auto">
                    <option value="">{{ $campoSeleccionado ? 'Seleccione campaña' : 'Primero elija el campo' }}</option>
                    @foreach ($campaniasPorCampo as $campaniaPorCampo)
                        <option value="{{ $campaniaPorCampo->id }}">
                            {{ $campaniaPorCampo->nombre_campania }}{{ $campaniaPorCampo->fecha_fin ? ' (cerrada)' : '' }}
                        </option>
                    @endforeach
                </x-select>
            @endcan

            @if ($mostrarTabla)
                <div class="flex items-center gap-2 px-3 py-1.5 rounded-md bg-emerald-50 border border-emerald-200 text-sm text-emerald-800 dark:bg-emerald-900/20 dark:border-emerald-800 dark:text-emerald-300">
                    <i class="fa fa-calendar"></i>
                    <span>
                        Infestación <b>{{ \Carbon\Carbon::parse($ultimaInfestacion->fecha)->format('d/m/Y') }}</b>
                        · {{ ucfirst($ultimaInfestacion->metodo) }}
                        · {{ number_format($ultimaInfestacion->infestadores, 0) }} infestadores
                    </span>
                </div>
                <div class="w-36">
                    <x-input id="cochinillas_gramo" type="number" wire:model="proyeccionCochinillaXGramo" placeholder="Ej: 500" label="Cochinillas por gramo" />
                </div>
            @endif
        </div>

        @if ($campania && $campoSeleccionado && !$ultimaInfestacion)
            <x-warning>
                No hay infestaciones registradas en <b>{{ $campoSeleccionado }}</b> – <b>{{ strtoupper($campania->nombre_campania) }}</b>.
                No se puede registrar la evaluación.
            </x-warning>
        @elseif ($mostrarTabla)
            <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted-foreground">
                <span>
                    <i class="fa fa-info-circle"></i>
                    Complete las cochinillas por penca en cada columna.
                    @if ($textoDias !== '')
                        Las evaluaciones se hacen a los <b>{{ $textoDias }} días</b> después de la infestación.
                    @endif
                </span>
                @foreach ($calendario as $e)
                    @php
                        $vencida = $e['fecha'] && $e['fecha']->lte(now());
                        $color = !$e['registrable'] ? 'border-border'
                            : ($e['registrada'] ? 'border-green-500 text-green-700 dark:text-green-400'
                            : ($vencida ? 'border-amber-500 text-amber-700 dark:text-amber-400' : 'border-border'));
                    @endphp
                    <span class="px-2 py-0.5 rounded-full border {{ $color }}"
                        title="{{ !$e['registrable'] ? 'La tabla registra hasta 3 evaluaciones' : ($e['registrada'] ? 'Registrada' : ($vencida ? 'Ya tocaba: falta registrarla' : 'Próxima')) }}">
                        {{ $e['numero'] }}ª · {{ $e['dias'] }} d · {{ $e['fecha']?->format('d/m/Y') }}
                        @if ($e['registrada']) <i class="fa fa-check"></i> @elseif ($e['registrable'] && $vencida) <i class="fa fa-clock"></i> @endif
                    </span>
                @endforeach
            </div>
        @elseif (!$campania)
            <p class="text-sm text-muted-foreground">Elija el campo y la campaña para registrar la evaluación.</p>
        @endif
    </x-card>

    {{-- La tabla existe siempre (Handsontable vive fuera de Livewire), pero solo se ve con campaña elegida e infestada:
         así no se llena por error antes de elegir y se pierde al cambiar de campo. --}}
    <div @class(['hidden' => !$mostrarTabla])>
        <x-card class="p-2">
            <div wire:ignore>
                <div x-ref="tableContainer"></div>
            </div>
        </x-card>
    </div>

    @if ($mostrarTabla)
        <x-card class="space-y-3">
            <div class="grid md:grid-cols-3 gap-3">
                @foreach ([1, 2, 3] as $n)
                    <div class="rounded-lg border border-border bg-muted text-muted-foreground p-3 space-y-2" wire:key="eval-{{ $n }}">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <div class="text-xs font-medium">{{ $n }}ª evaluación · promedio ponderado</div>
                                <div class="text-xl font-semibold text-foreground">{{ formatear_numero($promedios[$n] ?? 0, 0) }}</div>
                            </div>
                            <div class="w-36">
                                <x-input type="date" wire:model="{{ $fechasModelo[$n] }}" class="text-sm" />
                            </div>
                        </div>
                        @include('livewire.evaluacion.partials.desglose-infestacion', ['d' => $campania->desgloseEvaluacionInfestacion($n)])
                    </div>
                @endforeach
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 border-t border-border pt-3">
                <div title="Promedio individuos / n° de cochinillas por gramo">
                    <p class="text-xs text-muted-foreground">Gramos de cochinilla por penca</p>
                    <p class="text-lg font-semibold">{{ formatear_numero($campania->eval_proj_gramos_cochinilla_x_penca ?? 0) }} g</p>
                </div>
                <div title="Total de pencas">
                    <p class="text-xs text-muted-foreground">Pencas infestadas</p>
                    <p class="text-lg font-semibold">{{ formatear_numero($campania->eval_cosch_proj_penca_inf ?? 0) }}</p>
                </div>
                <div title="(Gramos × Pencas) ÷ 1000">
                    <p class="text-xs text-muted-foreground">Rendimiento proyectado por hectárea</p>
                    <p class="text-lg font-semibold">{{ formatear_numero($campania->eval_cosch_proj_rdto_ha ?? 0) }} kg</p>
                </div>
            </div>
        </x-card>

        @can(\App\Constants\Permisos::INFESTACION_EVALUACION_REGISTRAR)
            <x-inferior-derecha>
                <x-button type="button" @click="sendDataEvaluacionInfestacion()">
                    <i class="fa fa-save"></i> Guardar Evaluación
                </x-button>
            </x-inferior-derecha>
        @endcan
    @endif

    <x-loading wire:loading />
</div>
@script
<script>
    Alpine.data('{{ $idTable }}', () => ({
        tableData: @json($table),
        encabezados: @json($encabezados),
        isDark: JSON.parse(localStorage.getItem('darkMode')),
        hot: null,
        init() {
            this.initTable();
            $watch('darkMode', value => {

                this.isDark = value;
                const columns = this.getColumns();
                this.hot.updateSettings({
                    themeName: value ? 'ht-theme-main-dark' : 'ht-theme-main',
                    columns: columns
                });

            });
            Livewire.on('recargarEvaluacion', (data) => {
                this.tableData = data[0].table;
                this.encabezados = data[0].encabezados;
                // Esperar a que la tabla se muestre (estaba oculta sin campaña) para que tome su ancho
                requestAnimationFrame(() => {
                    this.hot?.destroy();
                    this.initTable();
                });
            });
        },
        initTable() {

            const container = this.$refs.tableContainer;
            const hot = new Handsontable(container, {
                ...window.HstConfig,
                data: this.tableData,
                colHeaders: true,
                themeName: this.isDark ? 'ht-theme-main-dark' : 'ht-theme-main',
                columns: this.getColumns(),
                nestedHeaders: this.getNestedHeaders(),
                minSpareRows: 1,
                manualColumnResize: false,
                manualRowResize: true,
                stretchH: 'all',
                autoColumnSize: false,
                licenseKey: 'non-commercial-and-evaluation',
                beforePaste: (data, coords) => {
                    // Recorremos las filas y celdas que se están pegando
                    for (let i = 0; i < data.length; i++) {
                        for (let j = 0; j < data[i].length; j++) {
                            if (typeof data[i][j] === 'string') {
                                // Expresión regular que detecta si el formato tiene comas de miles (ej: 1,050)
                                // Si es así, elimina la coma para que quede "1050" puro.
                                if (/^\d{1,3}(,\d{3})+(\.\d+)?$/.test(data[i][j].trim())) {
                                    data[i][j] = data[i][j].replace(/,/g, '');
                                }
                            }
                        }
                    }
                }
            });

            this.hot = hot;
        },
        getColumns() {
            return [{
                data: 'n_pencas',
                type: 'numeric',
                className: 'htCenter htMiddle font-semibold',
                //readOnly: true
            },

            // Evaluación 1
            {
                data: 'eval_primera_piso_2',
                type: 'numeric',
                className: 'htCenter'
            },
            {
                data: 'eval_primera_piso_3',
                type: 'numeric',
                className: 'htCenter'
            },

            // Evaluación 2
            {
                data: 'eval_segunda_piso_2',
                type: 'numeric',
                className: 'htCenter'
            },
            {
                data: 'eval_segunda_piso_3',
                type: 'numeric',
                className: 'htCenter'
            },

            // Evaluación 3
            {
                data: 'eval_tercera_piso_2',
                type: 'numeric',
                className: 'htCenter'
            },
            {
                data: 'eval_tercera_piso_3',
                type: 'numeric',
                className: 'htCenter'
            },
            ];
        },

        getNestedHeaders() {
            return [
                [
                    'N° PENCA',
                    ...this.encabezados.map(label => ({ label, colspan: 2 })),
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