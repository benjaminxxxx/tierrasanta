<div>


    <x-dialog-modal wire:model.live="mostrarFormulario" maxWidth="full">
        <x-slot name="title">
            Evaluación de Brotes x Piso
        </x-slot>

        <x-slot name="content">
            @if ($campoSeleccionado && $fecha)
                @if ($campania)
                    <x-success class="my-3">
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
                @else
                    <x-warning class="my-3">
                        No hay campañas registradas en este campo y esta fecha
                    </x-warning>
                @endif
            @else
                <x-warning class="my-3">
                    Seleccione el campo y una fecha para buscar la campaña
                </x-warning>
            @endif


            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <x-group-field>
                    @if ($campaniaUnica)
                        <x-input-string label="Campo" readonly value="{{$campoSeleccionado}}" class="!bg-gray-100" />
                    @else
                        <x-select-campo wire:model.live="campoSeleccionado" />
                    @endif

                </x-group-field>
                <x-group-field>
                    <x-input-date wire:model="fecha" error="fecha" label="Fecha de Evaluación" />
                </x-group-field>

                <x-group-field>
                    <x-label value="Evaluador" />
                    @if ($evaluadorSeleccionado)
                        <x-flex class="w-full justify-between">
                            <p>{{ $evaluadorSeleccionado['nombre'] }}</p>
                            <x-danger-button type="button" wire:click="quitarEvaluador">
                                <i class="fa fa-trash"></i>
                            </x-danger-button>
                        </x-flex>
                    @else
                        <div class="relative" x-data="{ isVisible: true }">
                            <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                                <i class="fa fa-search"></i>
                            </div>
                            <x-input type="search" wire:model.live.debounce.1000ms="evaluador" x-ref="inputField"
                                @focus="isVisible = true"
                                @blur="setTimeout(() => { if (!isHovering) isVisible = false }, 200)" class="pl-8" />
                            @if (count($evaluadores) > 0)
                                <div x-show="isVisible" x-ref="dropdown" @mouseenter="isHovering = true"
                                    @mouseleave="isHovering = false; setTimeout(() => { if (!document.activeElement.isSameNode($refs.inputField)) isVisible = false }, 200)"
                                    class="absolute left-0 top-full mt-1 w-full bg-white border border-gray-300 shadow-lg z-[100001] max-h-60 overflow-y-auto">
                                    <ul>
                                        @foreach ($evaluadores as $evaluadorR)
                                            <li>
                                                <a href="#"
                                                    wire:click="seleccionarEvaluador({{ $evaluadorR['id'] }},'{{ $evaluadorR['nombres'] }}')"
                                                    class="px-4 py-2 hover:bg-gray-100 block">
                                                    {{ $evaluadorR['nombres'] }}
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                        </div>
                    @endif
                    <x-input-error for="evaluadorSeleccionado.nombre" />
                </x-group-field>
                <x-input-number wire:model="metros_cama" label="Metros de Cama/Ha" />

            </div>

            <div x-data="{{ $idTable }}" wire:ignore class="my-4">
                <div x-ref="tableContainer" class="overflow-auto mt-5"></div>
            </div>

            <x-table>
                <x-slot name="thead">
                    <x-tr>
                        <x-th colspan="100%" class="text-center">
                            Promedio
                        </x-th>
                    </x-tr>
                    <x-tr>
                        <x-th>
                            -
                        </x-th>
                        <x-th>
                            -
                        </x-th>
                        <x-th>
                            -
                        </x-th>
                        <x-th class="text-center">
                            N° ACTUAL<br />BROTES<br />APTOS<br />2° PISO
                        </x-th>
                        <x-th class="text-center">
                            BROTES<br />APTOS 2° PISO<br />DESPUÉS<br />30 DÍAS
                        </x-th>
                        <x-th class="text-center">
                            N° ACTUAL<br />BROTES<br />APTOS<br />3° PISO
                        </x-th>
                        <x-th class="text-center">
                            BROTES<br />APTOS 3° PISO<br />DESPUÉS<br />30 DÍAS
                        </x-th>
                        <x-th class="text-center">
                            TOTAL<br />BROTES APTOS<br />2° Y 3° PISO
                        </x-th>
                        <x-th class="text-center">
                            TOTAL BROTES<br />APTOS 2° Y<br />3° PISO<br />DESPUÉS 30 DÍAS
                        </x-th>
                    </x-tr>
                </x-slot>
                <x-slot name="tbody">
                    @if ($evaluacionBrotesXPiso)
                        <x-tr>
                            <x-th>
                                -
                            </x-th>
                            <x-th>
                                -
                            </x-th>
                            <x-th>
                                -
                            </x-th>

                            <x-td class="text-center bg-gray-100">
                                {{ $evaluacionBrotesXPiso->promedio_actual_brotes_2piso ?? 0 }}
                            </x-td>

                            <x-td class="text-center bg-gray-100">
                                {{ $evaluacionBrotesXPiso->promedio_brotes_2piso_n_dias }}
                            </x-td>

                            <x-td class="text-center bg-gray-100">
                                {{ $evaluacionBrotesXPiso->promedio_actual_brotes_3piso }}
                            </x-td>

                            <x-td class="text-center !bg-gray-100">
                                {{ $evaluacionBrotesXPiso->promedio_brotes_3piso_n_dias }}
                            </x-td>

                            <x-td class="text-center !bg-orange-100">
                                {{ $evaluacionBrotesXPiso->promedio_actual_total_brotes_2y3piso }}
                            </x-td>
                            <x-td class="text-center !bg-orange-100">
                                {{ $evaluacionBrotesXPiso->promedio_total_brotes_2y3piso_n_dias }}
                            </x-td>
                        </x-tr>
                    @endif
                </x-slot>
            </x-table>

        </x-slot>

        <x-slot name="footer">
            <x-flex class="justify-end w-full">

                <x-secondary-button wire:click="$set('mostrarFormulario', false)" wire:loading.attr="disabled">
                    Cerrar
                </x-secondary-button>
                <x-button type="button" @click="$wire.dispatch('brotesGuardadoConfirmado')">
                    @if ($evaluacionBrotesXPisoId)
                        <i class="fa fa-pencil"></i> Actualizar
                    @else
                        <i class="fa fa-save"></i> Registrar
                    @endif
                </x-button>
            </x-flex>
        </x-slot>
    </x-dialog-modal>

    <x-loading wire:loading />
</div>
@script
    <script>
        Alpine.data('{{ $idTable }}', () => ({
            listeners: [],
            tableData: [],
            hot: null,
            init() {
                this.initTable();
                this.listeners.push(
                    Livewire.on('cargarDataBrotesXPiso', (data) => {
                        this.tableData = data[0];
                        this.hot.destroy();
                        this.initTable();
                        this.hot.loadData(this.tableData);
                    })
                );
                this.listeners.push(

                    Livewire.on('brotesGuardadoConfirmado', () => {
                        this.sendDataBrotesXPiso();
                    })
                );
            },
            initTable() {


                const container = this.$refs.tableContainer;
                const hot = new Handsontable(container, {
                    data: this.tableData,
                    colHeaders: true,
                    rowHeaders: true,

                    columns: [{
                            data: 'numero_cama_muestreada',
                            type: 'numeric',
                            className: '!text-center'
                        },
                        {
                            data: 'longitud_cama',
                            type: 'numeric',
                            className: '!text-center'
                        },
                        {
                            data: 'brotes_aptos_2p_actual',
                            type: 'numeric',
                            className: '!text-center'
                        },
                        {
                            data: 'brotes_aptos_2p_actual_calculado',
                            type: 'numeric',
                            readOnly: true,
                            className: '!text-center !bg-gray-100 htDimmed'
                        },
                        {
                            data: 'brotes_aptos_2p_despues_n_dias',
                            type: 'numeric',
                            className: '!text-center'
                        },
                        {
                            data: 'brotes_aptos_2p_despues_n_dias_calculado',
                            type: 'numeric',
                            className: '!text-center',
                            readOnly: true,
                            className: '!text-center !bg-gray-100 htDimmed'
                        },
                        {
                            data: 'brotes_aptos_3p_actual',
                            type: 'numeric',
                            className: '!text-center'
                        },
                        {
                            data: 'brotes_aptos_3p_actual_calculado',
                            type: 'numeric',
                            className: '!text-center',
                            readOnly: true,
                            className: '!text-center !bg-gray-100 htDimmed'
                        },
                        {
                            data: 'brotes_aptos_3p_despues_n_dias',
                            type: 'numeric',
                            className: '!text-center'
                        },
                        {
                            data: 'brotes_aptos_3p_despues_n_dias_calculado',
                            type: 'numeric',
                            className: '!text-center',
                            readOnly: true,
                            className: '!text-center !bg-gray-100 htDimmed'
                        },
                        {
                            data: 'total_actual_de_brotes_aptos_23_piso_calculado',
                            type: 'numeric',
                            className: '!text-center',
                            readOnly: true,
                            className: '!text-center !bg-orange-100 htDimmed'
                        },
                        {
                            data: 'total_de_brotes_aptos_23_pisos_despues_n_dias_calculado',
                            type: 'numeric',
                            className: '!text-center',
                            readOnly: true,
                            className: '!text-center !bg-orange-100 htDimmed'
                        }
                    ],
                    nestedHeaders: [
                        [{
                                label: 'N° DE<br/>CAMA<br/>MUESTREADA',
                                colspan: 1
                            },
                            {
                                label: 'LONGITUD<br/>CAMA<br/>(m)',
                                colspan: 1
                            },
                            {
                                label: 'N° ACTUAL<br/>BROTES<br/>APTOS<br/>2° PISO',
                                colspan: 2
                            },
                            {
                                label: 'BROTES<br/>APTOS 2° PISO<br/>DESPUÉS<br/>30 DÍAS',
                                colspan: 2
                            },
                            {
                                label: 'N° ACTUAL<br/>BROTES<br/>APTOS<br/>3° PISO',
                                colspan: 2
                            },
                            {
                                label: 'BROTES<br/>APTOS 3° PISO<br/>DESPUÉS<br/>30 DÍAS',
                                colspan: 2
                            },
                            {
                                label: 'TOTAL<br/>BROTES APTOS<br/>2° Y 3° PISO',
                                colspan: 1
                            },
                            {
                                label: 'TOTAL BROTES<br/>APTOS 2° Y<br/>3° PISO<br/>DESPUÉS 30 DÍAS',
                                colspan: 1
                            }
                        ],
                        [
                            '-', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-'
                        ]
                    ],

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
            sendDataBrotesXPiso() {
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
                $wire.dispatchSelf('storeTableDataBrotesXPiso', data);
            }
        }));
    </script>
@endscript
