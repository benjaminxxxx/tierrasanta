<div>

    <x-dialog-modal wire:model.live="mostrarFormulario" maxWidth="full">
        <x-slot name="title">
            Evaluación de Población
        </x-slot>

        <x-slot name="content">

            <div class="flex gap-5">
                <div class="w-[10rem] space-y-2">
                    @if ($modoEdicion)
                        <x-h3>{{ $campoSeleccionado }}</x-h3>
                    @else
                        <x-select-campo wire:model.live="campoSeleccionado" />
                    @endif
                    
                    @if ($campoSeleccionado)
                        <x-select label="Campañas" wire:model.live="campaniaSeleccionada" :disabled="$modoEdicion" fullWidth="true">
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

                        <x-input type="date" wire:model.live="fecha_siembra" label="Fecha de Siembra" readonly />

                        <x-input type="date" wire:model.live="fecha_eval_cero" error="fecha_eval_cero"
                            label="Evaluación Cero" />

                        <x-input type="date" wire:model.live="fecha_eval_resiembra" error="fecha_eval_resiembra"
                            label="Evaluación Resiembra" />

                        <x-input-number wire:model="area_lote" label="Área" placeholder="1.5" />

                        <x-group-field>
                            <x-autocomplete wire:model="evaluador" label="Evaluador" :sugerencias="$evaluadoresNombres"
                                placeholder="Buscar evaluador..." />

                            <x-input-error for="evaluador" />
                        </x-group-field>
                        <x-input-number wire:model="metros_cama_ha" label="Metros de Cama/Ha" placeholder="5000"
                            error="metros_cama_ha" />
                    @endif
                </div>
                <div class="flex-1">
                    <div x-data="{{ $idTable }}" wire:ignore class="my-4">
                        <div x-ref="tableContainer"></div>
                    </div>
                    <div>
                        <x-input-error for="detalles" />
                        <x-input-error for="detalle" />
                    </div>

                    @if($campania && $poblacionPlantaId)
                        @php
                            $eval = $campania->evaluacionPoblacionPlantas;
                        @endphp

                        <div class="grid grid-cols-2 gap-4 mt-4">

                            {{-- CARD: Evaluación Cero --}}
                            <div class="border rounded-lg p-4 bg-white shadow-sm dark:bg-gray-800 dark:border-gray-700">
                                <h3 class="font-semibold text-sm text-gray-700 text-center mb-2 dark:text-gray-300">
                                    EVALUACIÓN CERO
                                </h3>

                                <div class="grid grid-cols-2 text-sm">
                                    <div class="p-2 border-r text-center dark:border-gray-600">
                                        <div class="text-xs text-gray-500 dark:text-gray-200">Plantas/cama</div>
                                        <div class="font-bold">
                                            {{ round($eval->promedio_dia_cero, 2) }}
                                        </div>
                                    </div>

                                    <div class="p-2 text-center">
                                        <div class="text-xs text-gray-500 dark:text-gray-200">Plantas/metro</div>
                                        <div class="font-bold">
                                            {{ round($eval->promedio_plantas_metro_cero, 2) }}
                                        </div>
                                    </div>

                                    <div class="col-span-2 p-2 mt-1 bg-gray-50 rounded text-center dark:bg-gray-700">
                                        <div class="text-xs text-gray-500 dark:text-gray-200">Plantas por ha</div>
                                        <div class="font-bold text-blue-600 dark:text-white">
                                            {{ round($eval->promedio_plantas_ha_cero, 2) }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- CARD: Resiembra --}}
                            <div class="border rounded-lg p-4 bg-white shadow-sm dark:bg-gray-800 dark:border-gray-700">
                                <h3 class="font-semibold text-sm text-gray-700 text-center mb-2 dark:text-gray-300">
                                    EVALUACIÓN RESIEMBRA
                                </h3>

                                <div class="grid grid-cols-2 text-sm">
                                    <div class="p-2 border-r text-center dark:border-gray-600">
                                        <div class="text-xs text-gray-500 dark:text-gray-200">Plantas/cama</div>
                                        <div class="font-bold">
                                            {{ round($eval->promedio_resiembra, 2) }}
                                        </div>
                                    </div>

                                    <div class="p-2 text-center">
                                        <div class="text-xs text-gray-500 dark:text-gray-200">Plantas/metro</div>
                                        <div class="font-bold">
                                            {{ round($eval->promedio_plantas_metro_resiembra, 2) }}
                                        </div>
                                    </div>

                                    <div class="col-span-2 p-2 mt-1 bg-gray-50 rounded text-center dark:bg-gray-700">
                                        <div class="text-xs text-gray-500 dark:text-gray-200">Plantas por ha</div>
                                        <div class="font-bold text-blue-600 dark:text-white">
                                            {{ round($eval->promedio_plantas_ha_resiembra, 2) }}
                                        </div>
                                    </div>
                                </div>
                            </div>


                        </div>
                    @endif



                </div>
            </div>

        </x-slot>

        <x-slot name="footer">
            <x-flex class="justify-end w-full">
                <x-button variant="secondary" wire:click="$set('mostrarFormulario', false)"
                    wire:loading.attr="disabled">
                    Cerrar
                </x-button>
                <x-button type="button" @click="$wire.dispatch('guardadoConfirmadoPoblacionPlanta')">
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
        tableData: @json($detalleEvaluacionPoblacionPlanta),
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

                Livewire.on('guardadoConfirmadoPoblacionPlanta', () => {
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
                    data: 'numero_cama',
                    type: 'numeric',
                    className: '!text-center',
                    numericFormat: {
                        pattern: '0,0', // esto muestra 1,000 en lugar de 1000
                        culture: 'en-US'
                    }
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
                    data: 'eval_cero_plantas_x_hilera',
                    type: 'numeric',
                    className: '!text-center',
                    numericFormat: {
                        pattern: '0,0', // esto muestra 1,000 en lugar de 1000
                        culture: 'en-US'
                    }
                },
                {
                    data: 'plantas_x_metro_cero',
                    type: 'numeric',
                    className: '!text-center !bg-[#D8E4BC]',
                    numericFormat: {
                        pattern: '0,0', // esto muestra 1,000 en lugar de 1000
                        culture: 'en-US'
                    },
                    readOnly: true
                },
                {
                    data: 'eval_resiembra_plantas_x_hilera',
                    type: 'numeric',
                    className: '!text-center',
                    numericFormat: {
                        pattern: '0,0', // esto muestra 1,000 en lugar de 1000
                        culture: 'en-US'
                    }
                },
                {
                    data: 'plantas_x_metro_resiembra',
                    type: 'numeric',
                    className: '!text-center !bg-[#D8E4BC]',
                    numericFormat: {
                        pattern: '0,0', // esto muestra 1,000 en lugar de 1000
                        culture: 'en-US'
                    },
                    readOnly: true
                }
                ],
                nestedHeaders: [
                    [{
                        label: 'Cama Info',
                        colspan: 2
                    },
                    {
                        label: 'Evaluación Cero',
                        colspan: 2
                    },
                    {
                        label: 'Evaluación Resiembra',
                        colspan: 2
                    }
                    ],
                    ['N° Cama', 'Longitud Cama', 'Plantas por Hilera', 'Plantas por Metro', 'Plantas por Hilera', 'Plantas por Metro']
                ],
                width: '100%',
                themeName: 'ht-theme-main',
                height: 'auto',
                manualColumnResize: false,
                manualRowResize: true,
                minSpareRows: 1,
                stretchH: 'all',
                autoColumnSize: true,
                licenseKey: 'non-commercial-and-evaluation',
                afterChange: (changes, source) => {
                    if (source === 'loadData' || !changes) return;

                    changes.forEach(([row, prop, oldValue, newValue]) => {

                        const rowData = hot.getDataAtRow(row);
                        const longitud = parseFloat(rowData[1]) || 0; // columna longitud_cama
                        const ceroHilera = parseFloat(rowData[2]) || 0;
                        const resiembraHilera = parseFloat(rowData[4]) || 0;

                        // --- Recalcular plantas_x_metro_cero ---
                        if (['eval_cero_plantas_x_hilera', 'longitud_cama'].includes(prop)) {
                            const calculoCero = longitud > 0 ? (ceroHilera / longitud) : 0;
                            hot.setDataAtCell(row, 3, parseFloat(calculoCero.toFixed(3))); // plantas_x_metro_cero
                        }

                        // --- Recalcular plantas_x_metro_resiembra ---
                        if (['eval_resiembra_plantas_x_hilera', 'longitud_cama'].includes(prop)) {
                            const calculoResiembra = longitud > 0 ? (resiembraHilera / longitud) : 0;
                            hot.setDataAtCell(row, 5, parseFloat(calculoResiembra.toFixed(3))); // plantas_x_metro_resiembra
                        }

                    });
                }
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