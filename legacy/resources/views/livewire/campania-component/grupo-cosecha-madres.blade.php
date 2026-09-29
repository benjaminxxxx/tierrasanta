<div>
    <x-flex class="w-full justify-between my-5">
        <x-h3>
            Cosecha de Madres
        </x-h3>
    </x-flex>
    <x-flex class="!items-start w-full">
        @if ($campania)
            <x-card class="md:w-[35rem]">
                <x-spacing>
                    <x-h3>Resumen de Cosecha de madres</x-h3>
                    <div x-data="{ editando: false }" class="my-3">
                        <template x-if="editando">
                            <x-flex class="space-x-2 items-center">
                                <x-input-date wire:model="grupoCosechaMadres_cosechamadres_fecha_cosecha" label="Fecha de cosecha de madres" />
                                <x-button type="button" wire:click="registrarCambiosCosechaFecha"
                                    @click="editando = false">
                                    <i class="fa fa-save"></i>
                                </x-button>
                                <x-danger-button type="button" @click="editando = false" color="secondary">
                                    <i class="fa fa-times"></i>
                                </x-danger-button>
                            </x-flex>
                        </template>

                        <template x-if="!editando">
                            <x-flex class="space-x-2 items-center">
                                <span><b>Fecha de cosecha de madres</b>: {{ $campania->cosechamadres_fecha_cosecha }}</span>
                                <x-button type="button" @click="editando = true">
                                    <i class="fa fa-edit"></i>
                                </x-button>
                            </x-flex>
                        </template>
                    </div>
                    <div x-data="table_cochinilla_cosecha_mama" wire:ignore class="my-4">
                        <div x-ref="tableContainer"></div>

                        <x-flex class="justify-end w-full mt-5">
                            <x-button type="button" @click="sendDataCosechaMadres">
                                <i class="fa fa-save"></i> Registrar detalle
                            </x-button>
                        </x-flex>
                    </div>
                </x-spacing>

            </x-card>
            <div class="flex-1 overflow-auto">

                <livewire:cochinilla-cosecha-mamas-component campaniaId="{{ $campania->id }}"
                    campaniaUnica="{{ true }}" wire:key="grupo_cosecha_mamas.{{ $campania->id }}" />

            </div>
        @endif
    </x-flex>
</div>
@script
    <script>
        Alpine.data('table_cochinilla_cosecha_mama', () => ({
            listeners: [],
            tableData: @json($resumenCosechaMadres),
            hot: null,
            init() {
                this.initTable();
                this.listeners.push(
                    Livewire.on('cargarDataCosechaMadres', (data) => {

                        this.tableData = data[0];
                        console.log(this.tableData);
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
                            data: 'descripcion',
                            className: '!text-left !bg-gray-100',
                            readOnly: true,
                            title: 'Descripción'
                        },
                        {
                            data: 'datos',
                            className: '!text-center',
                            width: 20,
                            title: 'Datos'
                        },
                        {
                            data: 'datos_ha',
                            type: 'numeric',
                            className: '!text-center',
                            width: 10,
                            readOnly: true,
                            title: 'Datos x<br/>Ha',
                        }
                    ],
                    cells(row, col) {
                        const cellProperties = {};
                        const filasDestinoFresco = [0, 1, 2, 8, 14];
                        const filasRecuperacionSeco = [15, 16, 17, 18, 19];
                        const filasConversionFrescoSeco = [];

                        if (col === 0) {
                            cellProperties.readOnly = true;
                            cellProperties.className = '!bg-gray-100 !text-left';
                        }

                        if (
                            (filasDestinoFresco.includes(row) ||
                                filasConversionFrescoSeco.includes(row)) &&
                            (col === 0 || col === 1 || col === 2)
                        ) {
                            cellProperties.readOnly = true;
                            cellProperties.className = '!bg-yellow-100 !text-center font-bold';
                        }

                        if (
                            (filasRecuperacionSeco.includes(row)) &&
                            (col === 1)
                        ) {
                            cellProperties.readOnly = true;
                            cellProperties.className = '!bg-yellow-100 !text-center font-bold';
                        }

                        if (col === 2) {
                            cellProperties.readOnly = true;
                            cellProperties.className = (cellProperties.className || '') +
                                ' !bg-blue-50 !text-center';
                        }

                        return cellProperties;
                    },
                    height: 'auto',
                    manualColumnResize: false,
                    manualRowResize: true,
                    stretchH: 'all',
                    autoColumnSize: false,
                    licenseKey: 'non-commercial-and-evaluation',

                });

                this.hot = hot;
            },
            sendDataCosechaMadres() {
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
                $wire.dispatchSelf('registrarDetalleCosechaMadres', data);
            }
        }));
    </script>
@endscript
