<div>
    @if (!$campaniaUnica)
        <x-flex class="w-full justify-between mb-5">
            <x-h3>Evaluación de Infestación</x-h3>
        </x-flex>
    @endif

    <x-flex class="!items-start w-full">
        @if (!$campaniaUnica)
            <x-card class="md:w-[35rem]">
                <x-spacing>
                    <p class="mb-4">
                        Se evalúa a partir de los 15 días después de retirar la malla raschel 01 penca por planta, 20
                        plantas por campo, se toma fotografía de ambos lados de penca, 02 evaluaciones con intervalo de
                        15
                        días, 01 evaluación antes de la cosecha
                    </p>
                    @if ($campania)
                        <x-success class="mb-3">
                            <p>
                                Campo
                                {{ $campania->campo ?? '' }}
                            </p>
                            <p>
                                Campaña
                                {{ $campania->nombre_campania ?? '' }}
                            </p>
                            <p>
                                Variedad
                                {{ $campania->variedad_tuna ?? '' }}
                            </p>
                            <p>
                                Fecha de Inicio
                                {{ $campania->fecha_inicio ?? '' }}
                            </p>
                            <p>
                                Fecha Siembra
                                {{ $campania->fecha_siembra ?? '' }}
                            </p>
                        </x-success>

                        <p class="mt-4 mb-2">
                            <b>Infestaciones</b>
                        </p>
                        <x-table>
                            <x-slot name="thead">
                                <x-tr>
                                    <x-th class="text-center">Tipo</x-th>
                                    <x-th class="text-center">Fecha</x-th>
                                    <x-th class="text-center">Método</x-th>
                                    <x-th class="text-center">Infestadores</x-th>
                                </x-tr>
                            </x-slot>
                            <x-slot name="tbody">
                                @if ($campania->infestaciones && $campania->infestaciones->count() > 0)
                                    @foreach ($campania->infestaciones as $infestacion)
                                        <x-tr>
                                            <x-td
                                                class="text-center">{{ ucfirst($infestacion->tipo_infestacion) }}</x-td>
                                            <x-td class="text-center">{{ $infestacion->fecha }}</x-td>
                                            <x-td class="text-center">{{ ucfirst($infestacion->metodo) }}</x-td>
                                            <x-td
                                                class="text-center">{{ number_format($infestacion->infestadores, 2) }}</x-td>
                                        </x-tr>
                                    @endforeach
                                @else
                                    <x-tr>
                                        <x-td colspan="100%">No hay infestaciones aún</x-td>
                                    </x-tr>
                                @endif
                            </x-slot>
                        </x-table>

                        <x-h3 class="mt-4 mb-2">
                            Crea una nueva evaluación
                        </x-h3>
                        @php
                            $ultimaFecha = $campania->infestaciones->max('fecha'); // Asegúrate de que 'fecha' es el campo correcto
                            $fechasSugeridas = [
                                ['dias' => 60, 'fecha' => \Carbon\Carbon::parse($ultimaFecha)->addDays(60)],
                                ['dias' => 75, 'fecha' => \Carbon\Carbon::parse($ultimaFecha)->addDays(75)],
                                ['dias' => 100, 'fecha' => \Carbon\Carbon::parse($ultimaFecha)->addDays(100)],
                            ];
                        @endphp

                        <p>
                            Para crear una nueva evaluación seleccione una fecha y dé clic en Crear evaluación.
                            @if ($campania->infestaciones && $campania->infestaciones->count() > 0)
                                <br>
                                <b>Fechas sugeridas considerando la fecha de infestación {{ $ultimaFecha }}:</b>
                                <ul>
                                    @foreach ($fechasSugeridas as $sugerencia)
                                        <li>{{ $sugerencia['fecha']->format('d/m/Y') }} ({{ $sugerencia['dias'] }}
                                            días)
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                Como no hay infestaciones no podemos sugerir fechas de evaluación
                            @endif
                        </p>


                        <x-group-field class="mt-5">
                            <x-input-date wire:model.live="fechaEvaluacion" label="Fecha de evaluación" />
                        </x-group-field>
                        <x-flex class="justify-end w-full">
                            @if ($fechaExiste)
                                <x-danger-button type="button" wire:click="eliminarFecha">
                                    <i class="fa fa-trash"></i> Eliminar fecha
                                </x-danger-button>
                            @endif
                            <x-button type="button" wire:click="crearEvaluacion">
                                <i class="fa fa-plus"></i> Crear evaluación
                            </x-button>
                        </x-flex>
                    @else
                        <x-warning>
                            Seleccione el campo y la campaña para ver los resultados de la evaluación de infestacióm y
                            poder
                            agregar mas evaluaciones.
                        </x-warning>
                    @endif
                </x-spacing>
            </x-card>
        @endif

        <div class="flex-1">
            @if ($campania && $campaniaUnica)
                <x-card>
                    <x-spacing>
                        <x-h3 class="mt-4 mb-2">
                            Crea una nueva evaluación
                        </x-h3>
                        @php
                            $ultimaFecha = $campania->infestaciones->max('fecha'); // Asegúrate de que 'fecha' es el campo correcto
                            $fechasSugeridas = [
                                ['dias' => 60, 'fecha' => \Carbon\Carbon::parse($ultimaFecha)->addDays(60)],
                                ['dias' => 75, 'fecha' => \Carbon\Carbon::parse($ultimaFecha)->addDays(75)],
                                ['dias' => 100, 'fecha' => \Carbon\Carbon::parse($ultimaFecha)->addDays(100)],
                            ];
                        @endphp

                        <p>
                            Para crear una nueva evaluación seleccione una fecha y dé clic en Crear evaluación.
                            @if ($campania->infestaciones && $campania->infestaciones->count() > 0)
                                <br>
                                <b>Fechas sugeridas considerando la fecha de infestación {{ $ultimaFecha }}:</b>
                                <ul>
                                    @foreach ($fechasSugeridas as $sugerencia)
                                        <li>{{ $sugerencia['fecha']->format('d/m/Y') }} ({{ $sugerencia['dias'] }}
                                            días)
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                Como no hay infestaciones no podemos sugerir fechas de evaluación
                            @endif
                        </p>


                        <x-group-field class="mt-5">
                            <x-input-date wire:model.live="fechaEvaluacion" label="Fecha de evaluación" />
                        </x-group-field>
                        <x-flex class="justify-end w-full">
                            @if ($fechaExiste)
                                <x-danger-button type="button" wire:click="eliminarFecha">
                                    <i class="fa fa-trash"></i> Eliminar fecha
                                </x-danger-button>
                            @endif
                            <x-button type="button" wire:click="crearEvaluacion">
                                <i class="fa fa-plus"></i> Crear evaluación
                            </x-button>
                        </x-flex>
                    </x-spacing>
                </x-card>
            @endif
            @if (!$campaniaUnica)
                <x-card>
                    <x-spacing>
                        <x-flex>
                            <x-group-field>
                                <x-select-campo wire:model.live="campoSeleccionado" />
                            </x-group-field>
                            <x-group-field>
                                <x-select wire:model.live="campaniaSeleccionada" label="Campaña">
                                    <option value="">Seleccione campaña</option>
                                    @foreach ($campaniasPorCampo as $campaniaPorCampo)
                                        <option value="{{ $campaniaPorCampo->id }}">
                                            {{ $campaniaPorCampo->nombre_campania }}
                                        </option>
                                    @endforeach
                                </x-select>
                            </x-group-field>
                        </x-flex>
                    </x-spacing>
                </x-card>
            @endif

            <x-card class="mt-4">
                <x-spacing>
                    <div x-data="{{ $idTable }}" wire:ignore>
                        <div x-ref="tableContainer"></div>
                        <x-flex class="justify-end w-full mt-4">
                            <x-button type="button" @click="sendDataEvaluacionInfestacion">
                                <i class="fa fa-save"></i> Registrar detalle
                            </x-button>
                        </x-flex>
                    </div>
                </x-spacing>
            </x-card>
            @if ($campania)
                <x-card class="mt-4">
                <x-spacing>
                    <x-h3 class="mb-4">
                        Proyección cosecha
                    </x-h3>
                    <x-table>
                        <x-slot name="thead">

                        </x-slot>
                        <x-slot name="tbody">
                            <x-tr>
                                <x-th>
                                    N° COCHINILLAS POR GRAMO
                                </x-th>
                                <x-td>
                                    <x-input type="number" wire:model="proyeccion_cochinilla_x_gramo"/>
                                </x-td>
                            </x-tr>
                            <x-tr>
                                <x-td>
                                    <b>GRAMOS COCHINILLA POR PENCA</b>
                                    <p>
                                        N° de individuos promedio / n° de cochinillas x gramo
                                    </p>
                                </x-td>
                                <x-td>
                                    {{number_format($campania->evaluacion_cosecha_proyeccion_gramos_cochinilla_x_penca,0)}}
                                </x-td>
                            </x-tr>
                            <x-tr>
                                <x-td>
                                    <b>NUMERO DE PENCAS INFESTADAS</b>
                                    <p>
                                        <b>Promedio</b> total actual de brotes aptos 2° Y 3° PISO + <b>Promedio</b> total de brotes aptos 2° Y 3° piso despues de 30 dias
                                    </p>
                                </x-td>
                                <x-td>
                                    {{number_format($campania->evaluacion_cosecha_proyeccion_numero_pencas_infestadas,0)}}
                                </x-td>
                            </x-tr>
                            <x-tr>
                                <x-td>
                                    <b>RENDIMIENTO HECTAREA (kg)</b>
                                    <p>
                                        (Gr. de cochinilla x penca x N° de pencas infestadas)/1000
                                    </p>
                                </x-td>
                                <x-td class="bg-pink-200">
                                    {{number_format($campania->evaluacion_cosecha_proyeccion_rendimiento_ha,0)}}
                                </x-td>
                            </x-tr>
                        </x-slot>
                    </x-table>
                </x-spacing>
            </x-card>
            @endif
            
        </div>
    </x-flex>
    <x-loading wire:loading />
</div>
@script
    <script>
        Alpine.data('{{ $idTable }}', () => ({
            listeners: [],
            tableData: @json($table),
            columns: @json($fechas),
            hot: null,
            init() {
                this.initTable();
                this.listeners.push(
                    Livewire.on('recargarEvaluacion', (data) => {
                        this.columns = data[0].fechas;
                        this.tableData = data[0].table;
                        this.hot.destroy();
                        this.initTable();
                        this.hot.loadData(this.tableData);
                    })
                );
                this.listeners.push(

                    Livewire.on('guardadoConfirmado', () => {
                        this.sendDataPoblacionPlanta();
                    })
                );
            },
            initTable() {

                const container = this.$refs.tableContainer;
                const hot = new Handsontable(container, {
                    data: this.tableData,
                    colHeaders: true,
                    rowHeaders: true,
                    columns: this.getHotColumns(),
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
            getHotColumns() {
                const cols = [{
                    data: 'n_pencas',
                    className: '!text-center',
                    title: 'N° PENCA'
                }];

                this.columns.forEach((col, index) => {
                    cols.push({
                        data: `fecha${index + 1}_piso2`,
                        type: 'numeric',
                        className: '!text-center',
                        title: `2° PISO`
                    });
                    cols.push({
                        data: `fecha${index + 1}_piso3`,
                        type: 'numeric',
                        className: '!text-center',
                        title: `3° PISO`
                    });
                });

                return cols;
            },
            getNestedHeaders() {
                const headers = [
                    ['N° PENCA'], // primera columna sola
                ];

                // Cabeceras anidadas (nivel superior)
                const topRow = ['']; // primer celda vacía (columna N° PENCA)

                this.columns.forEach((col) => {
                    const label =
                        `N° DE COCHINILLAS/PENCA<br>${col.fecha}<br>${col.footer}<br>Prom: ${col.promedio}`;
                    topRow.push({
                        label,
                        colspan: 2
                    });
                });

                headers[0] = topRow;

                // Cabecera de segundo nivel
                const secondRow = ['N° PENCA'];
                this.columns.forEach(() => {
                    secondRow.push('2° PISO', '3° PISO');
                });

                headers.push(secondRow);

                return headers;
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

                const data = {
                    datos: filteredData
                };
                $wire.dispatchSelf('storeTableDataEvaluacionInfestacion', data);
            }
        }));
    </script>
@endscript
