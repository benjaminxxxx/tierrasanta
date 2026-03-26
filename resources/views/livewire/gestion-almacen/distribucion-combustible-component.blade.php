<div x-data="gestionDistribucionCombustible" class="space-y-4">
    <x-title>
        Distribución de Combustible
    </x-title>

    <x-card>
        <x-flex class="justify-between w-full">
            @include('comun.selector-mes-base')
            <x-flex>
                <div class="mt-4">
                    <x-label value="Maquinaria" />
                    <x-select-dropdown wire:model="filtroMaquinariaId" source="getMaquinarias" placeholder="Filtrar por maquinaria" />
                </div>
            </x-flex>
        </x-flex>
    </x-card>
    <x-card>
        <div wire:ignore>
            <div x-ref="tableContainer"></div>
        </div>
    </x-card>
    <x-inferior-derecha>
        <x-button @click="guardarDistribuciones">
            <i class="fa fa-save"></i> Guardar Distribuciones
        </x-button>
    </x-inferior-derecha>
    <x-loading wire:loading />
</div>
@script
    <script>
        Alpine.data('gestionDistribucionCombustible', () => ({
            isDark: JSON.parse(localStorage.getItem('darkMode')),
            tableData: @js($distribuciones),
            listaCampos: @js($listaCampos),
            filasModificadas: @entangle('filasModificadas'),
            listaMaquinarias: @js($listaMaquinarias),
            init() {
                this.initTable(this.tableData);
                $watch('darkMode', value => {

                    this.isDark = value;
                    const columns = this.getColumns();
                    this.hot.updateSettings({
                        themeName: value ? 'ht-theme-main-dark' : 'ht-theme-main',
                        columns: columns
                    });

                });
                Livewire.on('actualizarDistribuciones', ({
                    data
                }) => {
                    if (!this.$refs.tableContainer) return;
                    this.$nextTick(() => {
                        if (!this.$refs.tableContainer) return;
                        this.tableData = data;
                        this.initTable(data);
                    });
                });
            },
            initTable(tableData) {
                if (this.hot) {
                    try {
                        this.hot.destroy();
                    } catch (e) {}
                    this.hot = null;
                }

                const container = this.$refs.tableContainer;
                if (!container) return;

                const hot = new Handsontable(container, {

                    ...window.HstConfig,
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
                    afterChange: async (changes, source) => {
                        if (source === 'recalculado' || source === 'loadData') return;

                        changes.forEach(([row]) => {
                            if (!this.filasModificadas.includes(row)) {
                                this.filasModificadas = [...this.filasModificadas, row];
                            }
                        });

                        if (!['edit', 'CopyPaste.paste', 'Autofill.fill'].includes(source)) return;
                    },
                    // Bloqueo real: cancela cualquier cambio en filas es_salida
                    beforeChange(changes) {
                        if (!changes) return;
                        changes.forEach((change, i) => {
                            const rowData = hot.getSourceDataAtRow(change[0]);
                            if (rowData?.es_salida) changes[i] = null;
                        });
                    },

                    // readOnly + estilo visual por fila
                    cells(row) {
                        const rowData = this.instance.getSourceDataAtRow(row);
                        if (rowData?.es_salida) {
                            return {
                                readOnly: true,
                                className: '!bg-blue-50 dark:!bg-blue-900/30 opacity-75'
                            };
                        }
                        return {};
                    },
                });

                this.hot = hot;
                this.hot.render();
            },
            getColumns() {
                const camposLabels = this.listaCampos.map(c => c.label);
                const camposRevMap = Object.fromEntries(this.listaCampos.map(c => [c.label, c.label]));
                const camposMap = camposRevMap;

                const maquinasLabels = this.listaMaquinarias.map(m => m.label);
                const maquinasMap = Object.fromEntries(this.listaMaquinarias.map(m => [m.label, m.id]));
                const maquinasRevMap = Object.fromEntries(this.listaMaquinarias.map(m => [m.id, m.label]));

                // Renderer base que oscurece la celda si es fila salida
                const salidaRenderer = (base) => function(instance, td, row, col, prop, value, cellProps) {
                    base.call(this, instance, td, row, col, prop, value, cellProps);
                    const rowData = instance.getSourceDataAtRow(row);
                    if (rowData?.es_salida) {
                        td.classList.add('!bg-muted', '!font-bold', '!text-red-600', '!opacity-75');
                    }
                };

                const autocompleteCol = (labels, map, revMap, prop, title, width) => ({
                    data: prop,
                    title,
                    type: 'autocomplete',
                    source: labels,
                    strict: false,
                    allowInvalid: false,
                    filter: true,
                    width,
                    renderer: salidaRenderer(function(instance, td, row, col, prop, value) {
                        td.classList.remove('!text-gray-400', 'italic', '!text-red-500');
                        if (!value && value !== 0) {
                            td.classList.add('!text-gray-400', 'italic');
                            td.innerText = 'Buscar...';
                            return;
                        }
                        const label = revMap[value] ?? revMap[String(value)];
                        td.innerText = label ?? ('⚠️ ' + value);
                        if (!label) td.classList.add('!text-red-500');
                    }),
                    validator(value, callback) {
                        if (!value || value === '') return callback(true);
                        if (revMap[value] || revMap[String(value)]) return callback(true);
                        if (typeof value === 'string' && map[value]) {
                            setTimeout(() => {
                                this.instance.setDataAtCell(this.row, this.col, map[value],
                                    'validator');
                            }, 0);
                            return callback(true);
                        }
                        callback(false);
                    },
                });

                const T = Handsontable.renderers;

                return [{
                        data: 'fecha',
                        type: 'date',
                        dateFormat: 'YYYY-MM-DD',
                        locale: 'es-ES',
                        correctFormat: true,
                        title: 'FECHA',
                        width: 90,
                        renderer: salidaRenderer(T.TextRenderer),
                    },
                    {
                        data: 'hora_inicio',
                        type: 'time',
                        timeFormat: 'HH:mm',
                        correctFormat: true,
                        title: 'INICIO',
                        width: 70,
                        renderer: salidaRenderer(T.TextRenderer),
                    },
                    {
                        data: 'hora_fin',
                        type: 'time',
                        timeFormat: 'HH:mm',
                        correctFormat: true,
                        title: 'SALIDA',
                        width: 70,
                        renderer: salidaRenderer(T.TextRenderer),
                    },
                    {
                        data: 'n_horas',
                        type: 'numeric',
                        numericFormat: {
                            pattern: '0.00'
                        },
                        title: 'N° HORAS',
                        readOnly: true,
                        width: 70,
                        className: '!bg-muted',
                        renderer: salidaRenderer(T.NumericRenderer),
                    },
                    autocompleteCol(camposLabels, camposMap, camposRevMap, 'campo_nombre', 'CAMPO', 100),
                    {
                        data: 'cant_combustible',
                        type: 'numeric',
                        numericFormat: {
                            pattern: '0.000'
                        },
                        title: 'CANT. COMB.',
                        readOnly: true,
                        className: '!bg-muted',
                        renderer: salidaRenderer(T.NumericRenderer),
                    },
                    {
                        data: 'costo_combustible',
                        type: 'numeric',
                        numericFormat: {
                            pattern: '0.0000'
                        },
                        title: 'COSTO COMB.',
                        readOnly: true,
                        className: '!bg-muted',
                        renderer: salidaRenderer(T.NumericRenderer),
                    },
                    {
                        data: 'ingreso_salida',
                        type: 'numeric',
                        numericFormat: {
                            pattern: '0'
                        },
                        title: 'INGRESO',
                        readOnly: true,
                        width: 80,
                        className: '!bg-muted',
                        renderer: salidaRenderer(T.NumericRenderer),
                    },
                    {
                        data: 'labor_diaria',
                        type: 'text',
                        title: 'LABOR DIARIA',
                        width: 180,
                        renderer: salidaRenderer(T.TextRenderer),
                    },
                    autocompleteCol(maquinasLabels, maquinasMap, maquinasRevMap, 'maquinaria_id', 'TRACTOR',
                        120),
                    {
                        data: 'precio',
                        type: 'numeric',
                        numericFormat: {
                            pattern: '0.000000'
                        },
                        title: 'PRECIO',
                        readOnly: true,
                        width: 90,
                        className: '!bg-muted',
                        renderer: salidaRenderer(T.NumericRenderer),
                    },
                    {
                        data: 'ratio',
                        type: 'numeric',
                        numericFormat: {
                            pattern: '0.0000'
                        },
                        title: 'RATIO',
                        readOnly: true,
                        width: 70,
                        className: '!bg-muted',
                        renderer: salidaRenderer(T.NumericRenderer),
                    },
                    {
                        data: 'costo',
                        type: 'numeric',
                        numericFormat: {
                            pattern: '0.0000'
                        },
                        title: 'VALOR/COSTO',
                        readOnly: true,
                        width: 90,
                        className: '!bg-muted',
                        renderer: salidaRenderer(T.NumericRenderer),
                    },
                ];
            },
            guardarDistribuciones() {
                if (this.filasModificadas.length === 0) {
                    alert('Niguna fila modificada');
                    return;
                };

                const data = [...this.filasModificadas]
                    .map(i => this.hot.getSourceDataAtRow(i))
                    .filter(fila => fila && Object.values(fila).some(v => v !== null && v !== ''));

                $wire.guardarDistribuciones(data);
            }
        }));
    </script>
@endscript
