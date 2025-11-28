<div>


    <x-dialog-modal wire:model.live="mostrarFormulario" maxWidth="full">
        <x-slot name="title">
            Evaluación de Brotes x Piso
        </x-slot>

        <x-slot name="content">

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    @if ($modoEdicion)
                        <x-label>Campo</x-label> <x-h3>{{ $campoSeleccionado }}</x-h3>
                    @else
                        <x-select-campo wire:model.live="campoSeleccionado" />
                    @endif
                </div>

                @if ($campoSeleccionado)
                    <x-select label="Campañas" wire:model.live="campaniaSeleccionada" :disabled="$modoEdicion"
                        fullWidth="true">
                        <option value="">-- Seleccione --</option>
                        @foreach ($campaniasDisponibles as $campaniaItem)
                            <option value="{{ $campaniaItem->id }}">
                                {{ $campaniaItem->nombre_campania }} -
                                {{ $campaniaItem->variedad_tuna }}
                            </option>
                        @endforeach
                    </x-select>
                @endif

                @if ($campaniaSeleccionada)
                    <x-group-field>
                        <x-input-date wire:model="fecha" error="fecha" label="Fecha de Evaluación" />
                    </x-group-field>

                    <x-group-field>
                        <x-autocomplete wire:model="evaluador" label="Evaluador" :sugerencias="$evaluadoresNombres"
                            placeholder="Buscar evaluador..." />

                        <x-input-error for="evaluador" />
                    </x-group-field>

                    <x-input-number wire:model="metros_cama_ha" label="Metros de Cama/Ha" />
                @endif
            </div>

            <div x-data="{{ $idTable }}" wire:ignore class="my-4">
                <div x-ref="tableContainer" class="overflow-auto mt-5"></div>
            </div>
            <div>
                <x-input-error for="detalles" />
                <x-input-error for="detalle" />
            </div>
            @if($campania && $evaluacionBrotesXPisoId)
                @php
                    $eval = $campania->evaluacionBrotesXPiso;
                @endphp
                <x-table>
                    <x-slot name="thead">
                        <x-tr>
                            <x-th colspan="100%" class="text-center">
                                Promedio
                            </x-th>
                        </x-tr>
                        <x-tr>
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
                        <x-tr>

                            <x-td class="text-center">
                                {{ number_format($eval->promedio_actual_brotes_2piso, 0) ?? 0 }}
                            </x-td>

                            <x-td class="text-center">
                                {{ number_format($eval->promedio_brotes_2piso_n_dias, 0) }}
                            </x-td>

                            <x-td class="text-center">
                                {{ number_format($eval->promedio_actual_brotes_3piso, 0) }}
                            </x-td>

                            <x-td class="text-center">
                                {{ number_format($eval->promedio_brotes_3piso_n_dias, 0) }}
                            </x-td>

                            <x-td class="text-center !bg-[#FABF8F] font-bold !text-black">
                                {{ number_format($eval->promedio_actual_total_brotes_2y3piso, 0) }}
                            </x-td>
                            <x-td class="text-center !bg-[#FABF8F] font-bold !text-black">
                                {{ number_format($eval->promedio_total_brotes_2y3piso_n_dias, 0) }}
                            </x-td>
                        </x-tr>
                    </x-slot>
                </x-table>
            @endif
        </x-slot>

        <x-slot name="footer">
            <x-flex class="justify-end w-full">

                <x-button variant="secondary" wire:click="$set('mostrarFormulario', false)"
                    wire:loading.attr="disabled">
                    Cerrar
                </x-button>
                @if ($campaniaSeleccionada)
                    <x-button type="button" @click="$wire.dispatch('brotesGuardadoConfirmado')">
                        @if ($evaluacionBrotesXPisoId)
                            <i class="fa fa-pencil"></i> Actualizar
                        @else
                            <i class="fa fa-save"></i> Registrar
                        @endif
                    </x-button>
                @endif

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
                    data: 'numero_cama',
                    type: 'numeric',
                    className: '!text-center'
                },
                {
                    data: 'longitud_cama',
                    type: 'numeric',
                    className: '!text-center',
                    numericFormat: {
                        pattern: '0,0', // esto muestra 1,000 en lugar de 1000
                        culture: 'en-US'
                    }
                },
                {
                    data: 'brotes_aptos_2p_actual',
                    type: 'numeric',
                    className: '!text-center',
                    numericFormat: {
                        pattern: '0,0', // esto muestra 1,000 en lugar de 1000
                        culture: 'en-US'
                    }
                },
                {
                    data: 'brotes_2p_actual_por_mt',
                    type: 'numeric',
                    readOnly: true,
                    numericFormat: {
                        pattern: '0,0', // esto muestra 1,000 en lugar de 1000
                        culture: 'en-US'
                    },
                    className: '!text-center !bg-gray-100 htDimmed'
                },
                {
                    data: 'brotes_aptos_2p_despues_n_dias',
                    type: 'numeric',
                    numericFormat: {
                        pattern: '0,0', // esto muestra 1,000 en lugar de 1000
                        culture: 'en-US'
                    },
                    className: '!text-center'
                },
                {
                    data: 'brotes_2p_despues_por_mt',
                    type: 'numeric',
                    className: '!text-center',
                    readOnly: true,
                    numericFormat: {
                        pattern: '0,0', // esto muestra 1,000 en lugar de 1000
                        culture: 'en-US'
                    },
                    className: '!text-center !bg-gray-100 htDimmed'
                },
                {
                    data: 'brotes_aptos_3p_actual',
                    type: 'numeric',
                    numericFormat: {
                        pattern: '0,0', // esto muestra 1,000 en lugar de 1000
                        culture: 'en-US'
                    },
                    className: '!text-center'
                },
                {
                    data: 'brotes_3p_actual_por_mt',
                    type: 'numeric',
                    className: '!text-center',
                    readOnly: true,
                    numericFormat: {
                        pattern: '0,0', // esto muestra 1,000 en lugar de 1000
                        culture: 'en-US'
                    },
                    className: '!text-center !bg-gray-100 htDimmed'
                },
                {
                    data: 'brotes_aptos_3p_despues_n_dias',
                    type: 'numeric',
                    numericFormat: {
                        pattern: '0,0', // esto muestra 1,000 en lugar de 1000
                        culture: 'en-US'
                    },
                    className: '!text-center'
                },
                {
                    data: 'brotes_3p_despues_por_mt',
                    type: 'numeric',
                    className: '!text-center',
                    readOnly: true,
                    numericFormat: {
                        pattern: '0,0', // esto muestra 1,000 en lugar de 1000
                        culture: 'en-US'
                    },
                    className: '!text-center !bg-gray-100 htDimmed'
                },
                {
                    data: 'total_actual_por_mt',
                    type: 'numeric',
                    readOnly: true,
                    numericFormat: {
                        pattern: '0,0', // esto muestra 1,000 en lugar de 1000
                        culture: 'en-US'
                    },
                    className: '!text-center !bg-[#FABF8F] htDimmed font-bold !text-black'
                },
                {
                    data: 'total_despues_por_mt',
                    type: 'numeric',
                    readOnly: true,
                    numericFormat: {
                        pattern: '0,0', // esto muestra 1,000 en lugar de 1000
                        culture: 'en-US'
                    },
                    className: '!text-center !bg-[#FABF8F] htDimmed font-bold !text-black'
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
                        '-', '-', '-', '(m)', '-', '(m)', '-', '(m)', '-', '(m)', '-', '-'
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