<div>
    <x-card>
        <x-flex>
            <x-title>Proyección Rendimiento Poda</x-title>
        </x-flex>
        <x-flex class="!items-start w-full mt-4">


            <div class="flex-1">

                @if (!$campaniaUnica)

                    <x-flex>
                        <x-select-campo wire:model.live="campoSeleccionado" />
                        <x-select wire:model.live="campaniaSeleccionada" label="Campaña">
                            <option value="">Seleccione campaña</option>
                            @foreach ($campaniasPorCampo as $campaniaPorCampo)
                                <option value="{{ $campaniaPorCampo->id }}">
                                    {{ $campaniaPorCampo->nombre_campania }}
                                </option>
                            @endforeach
                        </x-select>
                    </x-flex>
                @endif

                <x-flex class="mt-4 !items-start w-full">
                    <div class="md:w-[35rem]">
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
                            <form wire:submit.prevent="guardarDatosRendimientoPoda">
                                <x-table>
                                    <x-slot name="thead">
                                    </x-slot>
                                    <x-slot name="tbody">
                                        <x-tr>
                                            <x-th>
                                                TAMAÑO MUESTRA
                                            </x-th>
                                            <x-td>
                                                <x-input type="number" wire:model="tamanioMuestra" />
                                            </x-td>
                                        </x-tr>
                                        <x-tr>
                                            <x-th>
                                                METROS DE CAMA/HA
                                            </x-th>
                                            <x-td>
                                                <x-input type="number" wire:model="metrosCamaHa" />
                                            </x-td>
                                        </x-tr>
                                        <x-tr>
                                            <x-th>

                                            </x-th>
                                            <x-td class="text-right">
                                                <x-button type="submit">
                                                    <i class="fa fa-save"></i> Guardar
                                                </x-button>
                                            </x-td>
                                        </x-tr>
                                    </x-slot>
                                </x-table>
                            </form>
                        @else
                            <x-warning>
                                Seleccione el campo y luego una campaña para editar sus valores.
                            </x-warning>
                        @endif
                    </div>
                    <div class="flex-1">
                        <div x-data="{{ $idTable }}" wire:ignore>
                            <div x-ref="tableContainer"></div>
                            <x-flex class="justify-end w-full mt-4">
                                <x-button type="button" @click="sendDataProyeccionPoda">
                                    <i class="fa fa-save"></i> Registrar detalle
                                </x-button>
                            </x-flex>
                        </div>
                    </div>
                </x-flex>
            </div>
        </x-flex>
    </x-card>

    <x-loading wire:loading />
</div>
@script
    <script>
        Alpine.data('{{ $idTable }}', () => ({
            listeners: [],
            tableData: @json($table),
            hot: null,
            init() {
                this.initTable();
                this.listeners.push(
                    Livewire.on('recargarRendimientoPoda', (data) => {
                        this.tableData = data[0];
                        this.hot.destroy();
                        this.initTable();
                        this.hot.loadData(this.tableData);
                    })
                );
                this.listeners.push(

                    Livewire.on('guardarDetallePoda', () => {
                        this.sendDataProyeccionPoda();
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
                            data: 'nro_muestra',
                            className: '!text-center !bg-gray-100',
                            title: 'N° MUESTRA',
                            readOnly: true,
                        },
                        {
                            data: 'peso_fresco_kg',
                            className: '!text-center',
                            title: 'PESO FRESCO (kg)'
                        },
                        {
                            data: 'peso_seco_kg',
                            className: '!text-center',
                            title: 'PESO SECO (kg)'
                        },
                        {
                            data: 'rdto_hectarea_kg',
                            className: '!text-center !bg-gray-100',
                            title: 'RDTO/HECTAREA (kg)',
                            readOnly: true,
                        },
                        {
                            data: 'relacion_fresco_seco',
                            className: '!text-center !bg-gray-100',
                            title: 'RELACION FRESCO/SECO',
                            readOnly: true,
                        }
                    ],
                    height: 'auto',
                    manualColumnResize: false,
                    manualRowResize: true,
                    stretchH: 'all',
                    autoColumnSize: false,
                    licenseKey: 'non-commercial-and-evaluation',

                });

                this.hot = hot;
            },
            sendDataProyeccionPoda() {
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
                $wire.dispatchSelf('storeTableDataProyeccionPoda', data);
            }
        }));
    </script>
@endscript
