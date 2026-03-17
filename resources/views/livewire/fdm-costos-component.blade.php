<div class="space-y-4">
    <x-card>
        <x-table>
            <x-slot name="thead">
                <x-tr>
                    <x-th class="text-center">
                        N°
                    </x-th>
                    <x-th>
                        Descripción del costo
                    </x-th>
                    <x-th class="text-center">
                        Monto Blanco
                    </x-th>
                    <x-th class="text-center">
                        Monto Negro
                    </x-th>
                    <x-th class="text-center">
                        Reporte Blanco
                    </x-th>
                    <x-th class="text-center">
                        Reporte Negro
                    </x-th>
                    <x-th class="text-center">
                        Gestión
                    </x-th>
                    <x-th class="text-center">
                        Acciones
                    </x-th>
                </x-tr>
            </x-slot>
            <x-slot name="tbody">
                <x-tr>
                    <x-td class="text-center">
                        1
                    </x-td>
                    <x-td>
                        Cuadrilleros FDM
                    </x-td>
                    <x-td class="text-center">
                        -
                    </x-td>
                    <x-td class="text-center">
                        S/ {{ formatear_numero($costoManoIndirecta?->negro_cuadrillero_monto) }}
                    </x-td>
                    <x-td class="text-center">
                        -
                    </x-td>
                    <x-td class="text-center">
                        -
                    </x-td>
                    <x-td class="text-center">
                        @if ($costoManoIndirecta?->negro_cuadrillero_file)
                            <x-button variant="success"
                                href="{{ Storage::disk('public')->url($costoManoIndirecta?->negro_cuadrillero_file) }}">
                                <i class="fa fa-file-excel" aria-hidden="true"></i> Reporte
                            </x-button>
                        @endif
                    </x-td>
                    <x-td class="text-center">
                        <x-button variant="secondary" type="button" wire:click="recalcularCostoFdm('cuadrilleros')">
                            <i class="fa fa-calculator"></i> Recalcular costos
                        </x-button>
                    </x-td>
                </x-tr>
                <x-tr>
                    <x-td class="text-center">
                        2
                    </x-td>
                    <x-td>
                        Planilleros FDM
                    </x-td>
                    <x-td class="text-center">
                        -
                    </x-td>
                    <x-td class="text-center">

                    </x-td>
                    <x-td class="text-center">
                        -
                    </x-td>
                    <x-td class="text-center">
                        -
                    </x-td>
                    <x-td class="text-center">
                        -
                    </x-td>
                    <x-td class="text-center">
                        <x-button type="button" variant="secondary" wire:click="recalcularCostoFdm('planilleros')">
                            <i class="fa fa-calculator"></i> Recalcular costos
                        </x-button>
                    </x-td>
                </x-tr>
                <x-tr>
                    <x-td class="text-center">
                        3
                    </x-td>

                    <x-td>
                        Maquinarias FDM
                    </x-td>

                    {{-- MONTO BLANCO --}}
                    <x-td class="text-center">
                        {{ $parametros['combustible_fdm_monto_blanco']->valor ?? '-' }}
                    </x-td>

                    {{-- MONTO NEGRO --}}
                    <x-td class="text-center">
                        {{ $parametros['combustible_fdm_monto_negro']->valor ?? '-' }}
                    </x-td>

                    {{-- INFORME BLANCO --}}
                    <x-td class="text-center">

                        @php
                            $archivoBlanco = $parametros['combustible_fdm_monto_blanco']->valor_texto ?? null;
                        @endphp

                        @if ($archivoBlanco)
                            <x-button href="{{ Storage::disk('public')->url($archivoBlanco) }}">
                                <i class="fa fa-file-excel"></i> Descargar Informe
                            </x-button>
                        @else
                            -
                        @endif

                    </x-td>

                    {{-- INFORME NEGRO --}}
                    <x-td class="text-center">

                        @php
                            $archivoNegro = $parametros['combustible_fdm_monto_negro']->valor_texto ?? null;
                        @endphp

                        @if ($archivoNegro)
                            <x-button href="{{ Storage::disk('public')->url($archivoNegro) }}">
                                <i class="fa fa-file-excel"></i> Descargar Informe
                            </x-button>
                        @else
                            -
                        @endif

                    </x-td>
                    <x-td class="text-center">

                        <x-pasos-indicador>

                            @foreach ([
        [
            'clave' => 'combustible_fdm_paso1',
            'label' => 'Distribuciones',
            'url' => route('almacen.salida_combustible'),
            'boton' => 'Ir a distribuciones',
        ],
        [
            'clave' => 'combustible_fdm_paso2',
            'label' => 'Asignaciones',
            'url' => route('gestion_insumos.kardex'),
            'boton' => 'Ir a asignaciones',
        ],
        [
            'clave' => 'combustible_fdm_paso3',
            'label' => 'Kardex',
        ],
    ] as $i => $paso)
                                @php
                                    $param = $parametros[$paso['clave']] ?? null;
                                @endphp

                                <x-paso-indicador :label="$paso['label']" :completado="$param?->valor_flag" :observacion="$param?->observacion ?? 'Sin evaluar'"
                                    :url="$paso['url'] ?? null" :labelBoton="$paso['boton'] ?? 'Ir'" />

                                @if (!$loop->last)
                                    <span class="text-muted-foreground text-xs">—</span>
                                @endif
                            @endforeach

                        </x-pasos-indicador>


                    </x-td>
                    <x-td class="text-center">
                        <x-button type="button" variant="secondary" wire:click="recalcularMaquinaria">
                            <i class="fa fa-calculator"></i> Recalcular costos
                        </x-button>
                    </x-td>
                </x-tr>
                <x-tr>
                    <x-td class="text-center">
                        5
                    </x-td>
                    <x-td>
                        Costos adicionales
                    </x-td>
                    <x-td class="text-center">
                        S/ {{ number_format($blancoCostosAdicionales, 2) }}
                    </x-td>
                    <x-td class="text-center">
                        S/ {{ number_format($negroCostosAdicionales, 2) }}
                    </x-td>
                    <x-td class="text-center">
                        -
                    </x-td>
                    <x-td class="text-center">
                        -
                    </x-td>
                    <x-td class="text-center">
                        -
                    </x-td>
                </x-tr>
            </x-slot>
        </x-table>
    </x-card>
    <x-card>

        <div>
            Registre o quite los costos adicionales
        </div>

        <div x-data="{{ $idTable }}" wire:ignore>

            <div x-ref="tableContainer" class="mt-5 overflow-auto"></div>
            <x-button @click="sendDataCostos" class="mt-5">
                <i class="fa fa-save"></i> Guardar Costos
            </x-button>
        </div>
    </x-card>

    <x-loading wire:loading />
</div>
@script
    <script>
        Alpine.data('{{ $idTable }}', () => ({
            listeners: [],
            tableData: @json($costosAdicionalesMensuales),
            hot: null,
            init() {
                this.initTable();
                this.listeners.push(
                    Livewire.on('actualizarGrilla-{{ $idTable }}', (data) => {

                        console.log(data[0]);
                        this.tableData = data[0];
                        this.hot.loadData(this.tableData);
                    })
                );
            },
            initTable() {

                let columns = [{
                        data: 'fecha',
                        type: 'date',
                        dateFormat: 'YYYY-MM-DD',
                        correctFormat: true,
                        width: 60,
                        title: 'Fecha',
                        className: 'text-center'
                    },
                    {
                        data: 'destinatario',
                        type: 'text',
                        width: 60,
                        title: `Destinatario`
                    },
                    {
                        data: 'descripcion',
                        type: 'text',
                        title: `Descripción`
                    },
                    {
                        data: 'monto_blanco',
                        type: 'numeric',
                        width: 60,
                        correctFormat: true,
                        className: 'text-right',
                        title: `Monto Blanco`
                    },
                    {
                        data: 'monto_negro',
                        type: 'numeric',
                        width: 60,
                        correctFormat: true,
                        className: 'text-right',
                        title: `Monto Negro`
                    }
                ];

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
                    licenseKey: 'non-commercial-and-evaluation',

                });

                this.hot = hot;
            },
            sendDataCostos() {
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
                $wire.dispatchSelf('storeTableDataCosto', data);
            }
        }));
    </script>
@endscript
