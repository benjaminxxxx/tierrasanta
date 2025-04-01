<div>

    <x-dialog-modal wire:model.live="mostrarFormulario">
        <x-slot name="title">
            @if ($campania)
                Evaluación de Población - Campaña {{ $campania->nombre_campania }}
            @endif
        </x-slot>

        <x-slot name="content">
            @if ($campania)
                <div class="my-4">
                    <p><b>Lote: </b>{{ $campania->campo }}</p>
                    <p><b>Área base: </b>{{ $campania->campo_model->area }}</p>
                    <p><b>Inicio de Campaña: </b>{{ $campania->fecha_inicio }}</p>
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">


                <x-group-field>
                    <x-label value="Evaluador" for="tipo_evaluacion" />
                    <x-select wire:model="tipo_evaluacion">
                        <option value="">Seleccione el tipo de evaluación</option>
                        <option value="dia_cero">Evaluación Cero</option>
                        <option value="resiembra">Evaluación Resiembra</option>
                    </x-select>
                    <x-input-error for="tipo_evaluacion" />
                </x-group-field>
                <x-input-number wire:model="area_lote" label="Área" />

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
                                        @foreach ($evaluadores as $evaluador)
                                            <li>
                                                <a href="#"
                                                    wire:click="seleccionarEvaluador({{ $evaluador['id'] }},'{{ $evaluador['nombres'] }}')"
                                                    class="px-4 py-2 hover:bg-gray-100 block">
                                                    {{ $evaluador['nombres'] }}
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                            
                        </div>
                    @endif
                    <x-input-error for="evaluadorSeleccionado.nombre"/>
                </x-group-field>
                <x-input-number wire:model="metros_cama" label="Metros de Cama/Ha" />
                @if ($campania)
                    <x-input-date wire:model="fecha" error="fecha" label="Fecha de Evaluación"
                        descripcion="La fecha de evaluación debe ser posterior al inicio de campaña"
                        fechaMin="{{ $campania->fecha_inicio }}" />
                @endif
            </div>

            <div x-data="{{ $idTable }}" wire:ignore class="my-4">
                <div x-ref="tableContainer" class="overflow-auto mt-5"></div>
            </div>

            <x-table>
                <x-slot name="thead">
                    <x-tr>
                        <x-th>

                        </x-th>
                        <x-th colspan="2" class="text-center">
                            Promedio
                        </x-th>
                    </x-tr>
                    <x-tr>
                        <x-th>

                        </x-th>
                        <x-th class="text-center">
                            Plantas por<br />cama
                        </x-th>
                        <x-th class="text-center">
                            Plantas por<br />metro
                        </x-th>
                    </x-tr>
                </x-slot>
                <x-slot name="tbody">
                    <x-tr>
                        <x-th>
                            PROMEDIO
                        </x-th>
                        <x-td class="text-center">
                            {{ $promedioPlantasXCama }}
                        </x-td>
                        <x-td class="text-center">
                            {{ $promedioPlantasXMetro }}
                        </x-td>
                    </x-tr>
                    <x-tr>
                        <x-th>
                            PROMEDIO PLANTAS Há
                        </x-th>
                        <x-td class="text-center">
                            {{ $promedioPlantasHA }}
                        </x-td>
                        <x-td>

                        </x-td>
                    </x-tr>
                </x-slot>
            </x-table>

        </x-slot>

        <x-slot name="footer">
            <x-flex class="justify-end w-full">
                <x-secondary-button wire:click="$set('mostrarFormulario', false)" wire:loading.attr="disabled">
                    Cerrar
                </x-secondary-button>
                <x-button  type="button" @click="$wire.dispatch('guardadoConfirmado')">
                    @if ($poblacionPlantaId)
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
            tableData: @json($listaCamasMuestreadas),
            hot: null,
            init() {
                this.initTable();
                this.listeners.push(
                    Livewire.on('cargarData', (data) => {
                        this.tableData = data[0];
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

                    columns: [{
                            data: 'cama_muestreada',
                            type: 'numeric',
                            className: '!text-center'
                        },
                        {
                            data: 'longitud_cama',
                            type: 'numeric',
                            className: '!text-center'
                        },
                        {
                            data: 'plantas_x_cama',
                            type: 'numeric',
                            className: '!text-center'
                        },
                        {
                            data: 'plantas_x_metro',
                            type: 'numeric',
                            className: '!text-center'
                        }
                    ],
                    nestedHeaders: [
                        [{
                                label: 'Cama Info',
                                colspan: 2
                            },
                            {
                                label: 'Evaluación',
                                colspan: 2
                            }
                        ],
                        ['N° Cama', 'Longitud Cama', 'Plantas por Cama', 'Plantas por Metro'
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
            sendDataPoblacionPlanta() {
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
                $wire.dispatchSelf('storeTableDataPoblacionPlanta', data);
            }
        }));
    </script>
@endscript