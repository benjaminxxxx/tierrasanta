<div class="space-y-4" x-data="infestacionesMasivaCochinilla()">

    <x-flex class="justify-between w-full">
        <x-breadcrumb :items="$breadcrumb" />
        <div>
            {{-- Opciones Adicionales --}}
            @include('comun.selector-mes-base')
        </div>
    </x-flex>

    <x-card>
        <div wire:ignore>
            <div x-ref="tableContainer"></div>
        </div>
    </x-card>

    <livewire:gestion-cochinilla.cochinilla-infestacion-resumen-component :mes="$mes" :anio="$anio" wire:key="cpm{{ $anio }}_{{ $mes }}"/>

    <x-inferior-derecha>
        <x-button @click="guardarInfestacionMasivo">
            <i class="fa fa-save"></i> Guardar Información
        </x-button>
    </x-inferior-derecha>

    <x-loading wire:loading />
</div>
@script
    <script>
        Alpine.data('infestacionesMasivaCochinilla', () => ({
            filasModificadas: @entangle('filasModificadas'),
            isDark: JSON.parse(localStorage.getItem('darkMode')),
            init() {
                this.initTable([]);
                $watch('darkMode', value => {

                    this.isDark = value;
                    const columns = this.getColumns();
                    this.hot.updateSettings({
                        themeName: value ? 'ht-theme-main-dark' : 'ht-theme-main',
                        columns: columns
                    });

                });
                Livewire.on('cargarDataInfestacion', ({
                    data
                }) => {
                    this.initTable(data);
                })
            },
            initTable(tableData) {
                if (this.hot) {
                    this.hot.destroy();
                }
                const container = this.$refs.tableContainer;
                const hot = new Handsontable(container, {
                    data: tableData,
                    themeName: this.isDark ? 'ht-theme-main-dark' : 'ht-theme-main',
                    colHeaders: true,
                    rowHeaders: true,
                    columns: this.getColumns(),
                    manualColumnResize: false,
                    manualRowResize: true,
                    stretchH: 'all',
                    minSpareRows: 1,
                    autoColumnSize: false,
                    licenseKey: 'non-commercial-and-evaluation',
                    afterChange: (changes, source) => {
                        // Corta el bucle: si nosotros mismos disparamos el cambio, ignorar
                        if (source === 'recalculado' || source === 'loadData') return;

                        changes.forEach(([row]) => {
                            if (!this.filasModificadas.includes(row)) {
                                this.filasModificadas = [...this.filasModificadas, row];
                            }
                        });

                        if (!['edit', 'CopyPaste.paste', 'Autofill.fill'].includes(source)) return;

                        // Columnas que afectan cada cálculo
                        const afectanKgMadresHa = new Set(['area', 'kg_madres']);
                        const afectanInfestadores = new Set(['numero_envases', 'capacidad_envase']);
                        // infestadores_por_ha y madres_por_infestador dependen de infestadores,
                        // pero como infestadores es readOnly, solo cambia cuando nosotros lo seteamos.
                        // Se recalculan junto con infestadores cuando cambian sus inputs.
                        const afectanTodo = new Set([...afectanKgMadresHa, ...afectanInfestadores]);

                        // Agrupar filas afectadas y qué recalcular en cada una
                        const filasMap = new Map(); // row -> Set de cálculos necesarios

                        changes.forEach(([row, prop]) => {
                            if (!afectanTodo.has(prop))
                                return; // columna irrelevante, ignorar

                            if (!filasMap.has(row)) filasMap.set(row, new Set());

                            if (afectanKgMadresHa.has(prop)) {
                                filasMap.get(row).add('kg_madres_por_ha');
                            }
                            if (afectanInfestadores.has(prop)) {
                                // Si cambian envases/capacidad, recalcular infestadores
                                // y los derivados que dependen de él
                                filasMap.get(row).add('infestadores');
                                filasMap.get(row).add('madres_por_infestador');
                                filasMap.get(row).add('infestadores_por_ha');
                            }
                            // Si cambia area o kg_madres, también afectan a los derivados de infestadores
                            if (afectanKgMadresHa.has(prop)) {
                                filasMap.get(row).add('madres_por_infestador'); // M = F / L
                                filasMap.get(row).add('infestadores_por_ha'); // N = L / D
                            }
                        });

                        filasMap.forEach((calculos, row) => {
                            const area = parseFloat(hot.getDataAtRowProp(row, 'area')) || 0;
                            const kgMadres = parseFloat(hot.getDataAtRowProp(row,
                                'kg_madres')) || 0;
                            const envases = parseFloat(hot.getDataAtRowProp(row,
                                'numero_envases')) || 0;
                            const capacidad = parseFloat(hot.getDataAtRowProp(row,
                                'capacidad_envase')) || 0;

                            // Calcular infestadores primero porque otros dependen de él
                            const infestadores = capacidad * envases || 0;

                            const updates = [];

                            if (calculos.has('kg_madres_por_ha')) {
                                updates.push([row, 'kg_madres_por_ha', area > 0 ? kgMadres /
                                    area : null
                                ]);
                            }
                            if (calculos.has('infestadores')) {
                                updates.push([row, 'infestadores', infestadores || null]);
                            }
                            if (calculos.has('madres_por_infestador')) {
                                updates.push([row, 'madres_por_infestador', infestadores >
                                    0 ? kgMadres / infestadores : null
                                ]);
                            }
                            if (calculos.has('infestadores_por_ha')) {
                                updates.push([row, 'infestadores_por_ha', area > 0 ?
                                    infestadores / area : null
                                ]);
                            }

                            if (updates.length > 0) {
                                hot.setDataAtRowProp(updates, null, null, 'recalculado');
                            }
                        });
                    }

                });

                this.hot = hot;
                this.hot.render();
            },
            getColumns() {
                return [

                    {
                        data: 'tipo_infestacion',
                        type: 'dropdown',
                        source: ['infestacion', 'reinfestacion'],
                        title: 'TIPO',
                        width: 70
                    },

                    {
                        data: 'fecha',
                        type: 'date',
                        dateFormat: 'YYYY-MM-DD',
                        title: 'FECHA INFESTACION',
                        width: 70
                    },

                    {
                        data: 'campo_nombre',
                        type: 'text',
                        title: 'CAMPO'
                    },

                    {
                        data: 'area',
                        type: 'numeric',
                        numericFormat: {
                            pattern: '0.000'
                        },
                        title: 'AREA',
                        className: '!text-center'
                    },

                    {
                        data: 'campania',
                        type: 'text',
                        title: 'CAMPAÑA',
                        readOnly: true,
                        className: '!bg-muted !text-center'
                    },

                    {
                        data: 'kg_madres',
                        type: 'numeric',
                        numericFormat: {
                            pattern: '0.00'
                        },
                        title: 'KG MADRES'
                    },

                    {
                        data: 'kg_madres_por_ha',
                        type: 'numeric',
                        readOnly: true,
                        title: 'KG MADRES/HA',
                        className: '!bg-muted'
                    },

                    {
                        data: 'campo_origen_nombre',
                        type: 'text',
                        title: 'ORIGEN CAMPO'
                    },

                    {
                        data: 'metodo',
                        type: 'dropdown',
                        source: ['carton', 'tubo', 'malla'],
                        title: 'METODO',
                        className: 'uppercase',
                        width: 55
                    },

                    {
                        data: 'capacidad_envase',
                        type: 'numeric',
                        title: 'UND X ENVASE'
                    },

                    {
                        data: 'numero_envases',
                        type: 'numeric',
                        title: 'ENVASES'
                    },

                    {
                        data: 'infestadores',
                        type: 'numeric',
                        readOnly: true,
                        title: 'INFESTADORES',
                        className: '!bg-muted'
                    },

                    {
                        data: 'madres_por_infestador',
                        type: 'numeric',
                        readOnly: true,
                        title: 'MADRES/INFES',
                        className: '!bg-muted'
                    },

                    {
                        data: 'infestadores_por_ha',
                        type: 'numeric',
                        readOnly: true,
                        title: 'INFESTADORES/HA',
                        className: '!bg-muted'
                    }

                ];
            },
            guardarInfestacionMasivo() {
                if (this.filasModificadas.length === 0) {
                    alert('Niguna fila modificada');
                    return;
                };

                const data = [...this.filasModificadas]
                    .map(i => this.hot.getSourceDataAtRow(i))
                    .filter(fila => fila && Object.values(fila).some(v => v !== null && v !== ''));

                $wire.guardarInfestacionMasivo(data);
            },
        }))
    </script>
@endscript
