<div>
    <x-flex class="w-full justify-between my-5">
        <x-h3>
            Cosecha de Madres
        </x-h3>
        <x-flex>
            <x-button type="button" wire:click="sincronizarInformacionParcial('cosecha_madres')">
                <i class="fa fa-sync"></i> Sincronizar datos
            </x-button>
        </x-flex>
    </x-flex>
    <x-flex class="!items-start w-full">
        @if ($campania)
            <x-card class="md:w-[35rem]">
                <x-spacing>
                    <x-h3>Resumen de Cosecha de madres</x-h3>
<!--
                    <x-table class="mt-3">
                        <x-slot name="thead"></x-slot>
                        <x-slot name="tbody">
                            <x-tr>
                                <x-th>Fecha de cosecha de madres</x-th>
                                <x-td class="bg-cyan-100">{{ $campania->cosechamadres_fecha_cosecha }}</x-td>
                            </x-tr>
                            <x-tr>
                                <x-th>Tiempo de infestación a cosecha</x-th>
                                <x-td>{{ $campania->cosechamadres_tiempo_infestacion_a_cosecha }}</x-td>
                            </x-tr>
                            <x-tr>
                                <x-th>Destino de madres en fresco (kg)</x-th>
                                <x-td>{{ number_format($campania->cosechamadres_destino_madres_fresco, 2) }}</x-td>
                            </x-tr>

                            <x-tr>
                                <x-th>Infestador cartón - campos (kg)</x-th>
                                <x-td>{{ number_format($campania->cosechamadres_infestador_carton_campos, 2) }}</x-td>
                            </x-tr>
                            <x-tr>
                                <x-th>Infestador tubo - campos (kg)</x-th>
                                <x-td>{{ number_format($campania->cosechamadres_infestador_tubo_campos, 2) }}</x-td>
                            </x-tr>
                            <x-tr>
                                <x-th>Infestador mallita - campos (kg)</x-th>
                                <x-td>{{ number_format($campania->cosechamadres_infestador_mallita_campos, 2) }}</x-td>
                            </x-tr>

                            <x-tr>
                                <x-th>Para secado (kg)</x-th>
                                <x-td>{{ number_format($campania->cosechamadres_para_secado, 2) }}</x-td>
                            </x-tr>
                            <x-tr>
                                <x-th>Para venta en fresco (kg)</x-th>
                                <x-td>{{ number_format($campania->cosechamadres_para_venta_fresco, 2) }}</x-td>
                            </x-tr>

                            <x-tr>
                                <x-th>Recuperación madres secas - cartón (kg)</x-th>
                                <x-td>{{ number_format($campania->cosechamadres_recuperacion_madres_seco_carton, 2) }}</x-td>
                            </x-tr>
                            <x-tr>
                                <x-th>Recuperación madres secas - tubo (kg)</x-th>
                                <x-td>{{ number_format($campania->cosechamadres_recuperacion_madres_seco_tubo, 2) }}</x-td>
                            </x-tr>
                            <x-tr>
                                <x-th>Recuperación madres secas - mallita (kg)</x-th>
                                <x-td>{{ number_format($campania->cosechamadres_recuperacion_madres_seco_mallita, 2) }}</x-td>
                            </x-tr>
                            <x-tr>
                                <x-th>Recuperación madres secas - secado (kg)</x-th>
                                <x-td>{{ number_format($campania->cosechamadres_recuperacion_madres_seco_secado, 2) }}</x-td>
                            </x-tr>
                            <x-tr>
                                <x-th>Recuperación madres secas - fresco (kg)</x-th>
                                <x-td>{{ number_format($campania->cosechamadres_recuperacion_madres_seco_fresco, 2) }}</x-td>
                            </x-tr>

                            <x-tr>
                                <x-th>Conversión fresco a seco - cartón</x-th>
                                <x-td>{{ number_format($campania->cosechamadres_conversion_fresco_seco_carton, 2) }}</x-td>
                            </x-tr>
                            <x-tr>
                                <x-th>Conversión fresco a seco - tubo</x-th>
                                <x-td>{{ number_format($campania->cosechamadres_conversion_fresco_seco_tubo, 2) }}</x-td>
                            </x-tr>
                            <x-tr>
                                <x-th>Conversión fresco a seco - mallita</x-th>
                                <x-td>{{ number_format($campania->cosechamadres_conversion_fresco_seco_mallita, 2) }}</x-td>
                            </x-tr>
                            <x-tr>
                                <x-th>Conversión fresco a seco - secado</x-th>
                                <x-td>{{ number_format($campania->cosechamadres_conversion_fresco_seco_secado, 2) }}</x-td>
                            </x-tr>
                            <x-tr>
                                <x-th>Conversión fresco a seco - fresco</x-th>
                                <x-td>{{ number_format($campania->cosechamadres_conversion_fresco_seco_fresco, 2) }}</x-td>
                            </x-tr>
                        </x-slot>
                    </x-table>-->
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
                        const filasDestinoFresco = [0,1,2,8,14];
                        const filasRecuperacionSeco = [15,16,17,18,19];
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
