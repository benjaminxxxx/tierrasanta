<div>

    <x-dialog-modal wire:model.live="mostrarFormulario" maxWidth="full">
        <x-slot name="title">
            Evaluación de Población
        </x-slot>

        <x-slot name="content">

            <div class="flex gap-5">
                <div class="w-[10rem] space-y-2">
                    @if ($modoEdicion)
                        <x-label>Campo</x-label> <x-h3>{{ $campoSeleccionado }}</x-h3>
                    @else
                        <x-select-campo wire:model.live="campoSeleccionado" />
                    @endif

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
                        <x-input type="date" wire:model.live="fecha_siembra" label="Fecha de Siembra" readonly />

                        <x-selector-dia wire:model.live="fecha_eval_cero" error="fecha_eval_cero" label="Evaluación Cero" />

                        <x-selector-dia wire:model.live="fecha_eval_resiembra" error="fecha_eval_resiembra"
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
                    <x-validation-errors />
                    <div>
                        <x-input-error for="detalles" />
                        <x-input-error for="detalle" />
                    </div>

                    @if ($campania && $poblacionPlantaId)
                        @php
                            $eval = $campania->evaluacionPoblacionPlantas;
                        @endphp

                        <div class="mt-4 border border-border rounded-lg overflow-hidden text-xs">

                            {{-- HEADER PRINCIPAL --}}
                            <div class="grid grid-cols-2 divide-x divide-border">
                                <div
                                    class="p-2 text-center font-semibold bg-muted text-muted-foreground uppercase tracking-wide">
                                    Día Cero
                                </div>
                                <div
                                    class="p-2 text-center font-semibold bg-muted text-muted-foreground uppercase tracking-wide">
                                    Resiembra
                                </div>
                            </div>

                            {{-- SUBHEADERS --}}
                            <div class="grid divide-x divide-border border-t border-border"
                                style="grid-template-columns: repeat(6, 1fr) repeat(2, 1fr)">
                                {{-- Día Cero: 6 subcolumnas --}}
                                <div class="p-1 text-center bg-card text-muted-foreground border-b border-border">
                                    Plantas<br>x Hilera</div>
                                <div class="p-1 text-center bg-card text-muted-foreground border-b border-border">
                                    Plantas<br>x Metro</div>
                                <div class="p-1 text-center bg-card text-muted-foreground border-b border-border">B2°<br>x
                                    Hilera</div>
                                <div class="p-1 text-center bg-card text-muted-foreground border-b border-border">B2°<br>x
                                    Metro</div>
                                <div class="p-1 text-center bg-card text-muted-foreground border-b border-border">B3°<br>x
                                    Hilera</div>
                                <div class="p-1 text-center bg-card text-muted-foreground border-b border-border border-r">
                                    B3°<br>x Metro</div>
                                {{-- Resiembra: 2 subcolumnas --}}
                                <div class="p-1 text-center bg-card text-muted-foreground border-b border-border">
                                    Plantas<br>x Hilera</div>
                                <div class="p-1 text-center bg-card text-muted-foreground border-b border-border">
                                    Plantas<br>x Metro</div>
                            </div>

                            {{-- FILA 1: Promedios --}}
                            <div class="grid divide-x divide-border border-t border-border"
                                style="grid-template-columns: repeat(6, 1fr) repeat(2, 1fr)">
                                <div class="p-2 text-center font-bold">{{ round($eval->promedio_dia_cero, 0) }}</div>
                                <div class="p-2 text-center font-bold">{{ round($eval->promedio_plantas_metro_cero, 0) }}
                                </div>
                                <div class="p-2 text-center font-bold">{{ round($eval->promedio_brazos2_hilera_cero, 0) }}
                                </div>
                                <div class="p-2 text-center font-bold">{{ round($eval->promedio_brazos2_metro_cero, 0) }}
                                </div>
                                <div class="p-2 text-center font-bold">{{ round($eval->promedio_brazos3_hilera_cero, 0) }}
                                </div>
                                <div class="p-2 text-center font-bold border-r border-border">
                                    {{ round($eval->promedio_brazos3_metro_cero, 0) }}</div>
                                <div class="p-2 text-center font-bold">{{ round($eval->promedio_resiembra, 0) }}</div>
                                <div class="p-2 text-center font-bold">
                                    {{ round($eval->promedio_plantas_metro_resiembra, 0) }}</div>
                            </div>

                            {{-- FILA 2: x Ha --}}
<div class="grid divide-x divide-border border-t border-border bg-muted/50"
     style="grid-template-columns: repeat(6, 1fr) repeat(2, 1fr)">
    <div class="col-span-2 p-1 text-center border-r border-border">
        <span class="text-muted-foreground">Plantas/ha:</span>
        <span class="font-bold ml-1">{{ round($eval->promedio_plantas_ha_cero, 0) }}</span>
    </div>
    <div class="col-span-2 p-1 text-center border-r border-border">
        <span class="text-muted-foreground">B2°/ha:</span>
        <span class="font-bold ml-1">{{ round($eval->total_brazos2_ha_cero, 0) }}</span>
    </div>
    <div class="col-span-2 p-1 text-center border-r border-border">
        <span class="text-muted-foreground">B3°/ha:</span>
        <span class="font-bold ml-1">{{ round($eval->total_brazos3_ha_cero, 0) }}</span>
    </div>
    <div class="col-span-2 p-1 text-center">
        <span class="text-muted-foreground">Plantas/ha:</span>
        <span class="font-bold ml-1">{{ round($eval->promedio_plantas_ha_resiembra, 0) }}</span>
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
    <style>
        .dark .handsontable .htDimmed {
            color: #ffffff !important;
        }
    </style>
</div>
@script
<script>
    Alpine.data('{{ $idTable }}', () => ({
        listeners: [],
        tableData: @json($detalleEvaluacionPoblacionPlanta),
        isDark: JSON.parse(localStorage.getItem('darkMode')),
        hot: null,
        init() {
            this.initTable();
            Livewire.on('cargarData', (data) => {
                this.tableData = data[0];
                this.hot.destroy();
                this.initTable();
                this.hot.loadData(this.tableData);
            });
            Livewire.on('guardadoConfirmadoPoblacionPlanta', () => {
                this.sendDataPoblacionPlanta();
            });
        },
        initTable() {


            const container = this.$refs.tableContainer;
            const hot = new Handsontable(container, {
                data: this.tableData,
                colHeaders: true,
                rowHeaders: true,
                /*{
                                            data: 'numero_cama',
                                            type: 'numeric',
                                            className: '!text-center',
                                            numericFormat: {
                                                pattern: '0,0', // esto muestra 1,000 en lugar de 1000
                                                culture: 'en-US'
                                            }
                                        }*/
                columns: [{
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
                    className: '!text-center !bg-[#D8E4BC] dark:!bg-green-600 !text-foreground',
                    numericFormat: {
                        pattern: '0,0', // esto muestra 1,000 en lugar de 1000
                        culture: 'en-US'
                    },
                    readOnly: true
                },
                {
                    data: 'brazos2_piso_x_hilera_cero',
                    type: 'numeric',
                    className: '!text-center',
                    numericFormat: {
                        pattern: '0,0', // esto muestra 1,000 en lugar de 1000
                        culture: 'en-US'
                    }
                },
                {
                    data: 'brazos2_piso_x_metro_cero',
                    type: 'numeric',
                    className: '!text-center !bg-[#D8E4BC] dark:!bg-green-600 !text-foreground',
                    numericFormat: {
                        pattern: '0,0', // esto muestra 1,000 en lugar de 1000
                        culture: 'en-US'
                    },
                    readOnly: true
                },
                {
                    data: 'brazos3_piso_x_hilera_cero',
                    type: 'numeric',
                    className: '!text-center',
                    numericFormat: {
                        pattern: '0,0', // esto muestra 1,000 en lugar de 1000
                        culture: 'en-US'
                    }
                },
                {
                    data: 'brazos3_piso_x_metro_cero',
                    type: 'numeric',
                    className: '!text-center !bg-[#D8E4BC] dark:!bg-green-600 !text-foreground',
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
                    className: '!text-center !bg-[#D8E4BC] dark:!bg-green-600 !text-foreground',
                    numericFormat: {
                        pattern: '0,0', // esto muestra 1,000 en lugar de 1000
                        culture: 'en-US'
                    },
                    readOnly: true
                }
                ],
                nestedHeaders: [
                    [{
                        label: '',
                        colspan: 1
                    },
                    {
                        label: 'DIA CERO',
                        colspan: 6
                    },
                    {
                        label: 'RESIEMBRA',
                        colspan: 2
                    }
                    ],
                    [
                        'LONGITUD<br/>CAMA<br/>(metros)',
                        'PLANTAS<br/>POR<br/>HILERA',
                        'PLANTAS<br/>POR<br/>METRO',
                        'BRAZOS 2°<br/>PISO POR<br/>HILERA',
                        'BRAZOS 2°<br/>PISO POR<br/>METRO',
                        'BRAZOS 3°<br/>PISO POR<br/>HILERA',
                        'BRAZOS 3°<br/>PISO POR<br/>METRO',
                        'PLANTAS<br/>POR<br/>HILERA',
                        'PLANTAS<br/>POR<br/>METRO'
                    ]
                ],
                width: '100%',
                themeName: this.isDark ? 'ht-theme-main-dark' : 'ht-theme-main',
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

                        // Leer valores por nombre de columna (independiente del orden)
                        const longitud = parseFloat(hot.getDataAtRowProp(row, 'longitud_cama')) || 0;
                        const plantasHilera = parseFloat(hot.getDataAtRowProp(row, 'eval_cero_plantas_x_hilera')) || 0;
                        const reHilera = parseFloat(hot.getDataAtRowProp(row, 'eval_resiembra_plantas_x_hilera')) || 0;
                        const b2Hilera = parseFloat(hot.getDataAtRowProp(row, 'brazos2_piso_x_hilera_cero')) || 0;
                        const b3Hilera = parseFloat(hot.getDataAtRowProp(row, 'brazos3_piso_x_hilera_cero')) || 0;

                        const COLS_QUE_AFECTAN = [
                            'longitud_cama',
                            'eval_cero_plantas_x_hilera',
                            'eval_resiembra_plantas_x_hilera',
                            'brazos2_piso_x_hilera_cero',
                            'brazos3_piso_x_hilera_cero',
                        ];

                        if (!COLS_QUE_AFECTAN.includes(prop)) return;

                        // Función helper para evitar NaN / Infinity
                        const calc = (hilera) =>
                            longitud > 0 ? parseFloat((hilera / longitud).toFixed(0)) : 0;

                        // Recalcular plantas por metro — día cero
                        if (['longitud_cama', 'eval_cero_plantas_x_hilera'].includes(prop)) {
                            hot.setDataAtRowProp(row, 'plantas_x_metro_cero', calc(plantasHilera));
                        }

                        // Recalcular plantas por metro — resiembra
                        if (['longitud_cama', 'eval_resiembra_plantas_x_hilera'].includes(prop)) {
                            hot.setDataAtRowProp(row, 'plantas_x_metro_resiembra', calc(reHilera));
                        }

                        // Recalcular brazos 2° piso por metro
                        if (['longitud_cama', 'brazos2_piso_x_hilera_cero'].includes(prop)) {
                            hot.setDataAtRowProp(row, 'brazos2_piso_x_metro_cero', calc(b2Hilera));
                        }

                        // Recalcular brazos 3° piso por metro
                        if (['longitud_cama', 'brazos3_piso_x_hilera_cero'].includes(prop)) {
                            hot.setDataAtRowProp(row, 'brazos3_piso_x_metro_cero', calc(b3Hilera));
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

            $wire.storeTableDataPoblacionPlanta(filteredData);
        }
    }));
</script>
@endscript