<div x-data="gestionSalidaAlmacen">
    <x-card>
        <div wire:ignore>
            <div x-ref="tableContainer"></div>
        </div>
    </x-card>
    <x-inferior-derecha>
        <x-button @click="guardarSalidaAlmacen()">
            <i class="fa fa-save"></i> Guardar Salidas Modificadas
        </x-button>
    </x-inferior-derecha>

    <x-loading wire:loading />
</div>
@script
    <script>
        Alpine.data('gestionSalidaAlmacen', () => ({
            filasModificadas: @entangle('filasModificadas'),
            isDark: JSON.parse(localStorage.getItem('darkMode')),
            tableDataSalidas: @js($registros),
            tipo: @js($tipo),
            listaProductos: @js($listaProductos),
            listaMaquinarias: @js($listaMaquinarias),
            listaCampos: @js($listaCampos),
            mes:@js($mes),
            anio:@js($anio),
            init() {
                this.initTable(this.tableDataSalidas);
                $watch('darkMode', value => {

                    this.isDark = value;
                    const columns = this.getColumns();
                    this.hot.updateSettings({
                        themeName: value ? 'ht-theme-main-dark' : 'ht-theme-main',
                        columns: columns
                    });

                });
                Livewire.on('cargarDataSlidaAlmacen', ({
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
                const esCombustible = this.tipo === 'combustible';

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
                    contextMenu: esCombustible ? {
                        items: {
                            'distribucion': {
                                name: '<i class="fa fa-list"></i> &nbsp; Distribución combustible',
                                callback: () => {
                                    const selected = this.hot.getSelected();
                                    if (!selected) return;

                                    // Tomar solo la primera selección, primera fila
                                    const row = selected[0][0];
                                    const fila = this.hot.getSourceDataAtRow(row);

                                    if (!fila?.id) {
                                        alert(
                                            'Guarda el registro antes de ver la distribución.'
                                            );
                                        return;
                                    }

                                    $wire.dispatch('verDistribucionCombustible', {
                                        salidaId: fila.id,
                                        mes: this.mes,
                                        anio: this.anio,
                                    });
                                },
                            },
                            'sep1': '---------',
                        }
                    } : false,
                    afterChange: (changes, source) => {
                        // Corta el bucle: si nosotros mismos disparamos el cambio, ignorar
                        if (source === 'recalculado' || source === 'loadData') return;

                        changes.forEach(([row]) => {
                            if (!this.filasModificadas.includes(row)) {
                                this.filasModificadas = [...this.filasModificadas, row];
                            }
                        });

                        if (!['edit', 'CopyPaste.paste', 'Autofill.fill'].includes(source)) return;


                    }

                });

                this.hot = hot;
                this.hot.render();
            },
            getColumns() {
                const esCombustible = this.tipo === 'combustible';

                // Mapas para productos

                const productosLabels = this.listaProductos.map(p => p.label);
                const productosMap = Object.fromEntries(this.listaProductos.map(p => [p.label, p.id]));
                const productosRevMap = Object.fromEntries(this.listaProductos.map(p => [p.id, p.label]));


                // Mapas para destino (campo o maquinaria)
                const destinoLista = esCombustible ? this.listaMaquinarias : this.listaCampos;
                const destinoLabels = destinoLista.map(d => d.label);
                const destinoMap = Object.fromEntries(destinoLista.map(d => [d.label, d.id ?? d.label]));
                const destinoRevMap = Object.fromEntries(destinoLista.map(d => [(d.id ?? d.label), d.label]));

                const autocompleteCol = (labels, map, revMap, prop, title) => ({
                    data: prop,
                    title,
                    type: 'autocomplete',
                    source: labels,
                    strict: false,
                    allowInvalid: false,
                    filter: true,
                    renderer(instance, td, row, col, prop, value) {
                        td.classList.remove('text-gray-400', 'italic', 'text-red-500');
                        if (value === null || value === undefined || value === '') {
                            td.classList.add('text-gray-400', 'italic');
                            td.innerText = 'Buscar...';
                            return;
                        }
                        const label = revMap[value] ?? revMap[String(value)];
                        if (label) {
                            td.innerText = label;
                        } else {
                            td.classList.add('text-red-500');
                            td.innerText = '⚠️ ' + value;
                        }
                    },
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
                    }
                });

                return [{
                        data: 'item',
                        type: 'numeric',
                        title: 'ITEM',
                        readOnly: true,
                    },
                    {
                        data: 'fecha_reporte',
                        type: 'date',
                        dateFormat: 'YYYY-MM-DD',
                        title: 'FECHA',
                        width: 90,
                    },
                    // Columna PRODUCTO (autocomplete con id interno)
                    autocompleteCol(productosLabels, productosMap, productosRevMap, 'producto_id',
                        'PRODUCTO'),
                    {
                        data: 'unidad_medida',
                        type: 'text',
                        title: 'UND',
                        readOnly: true,
                        className: '!bg-muted !text-center',
                    },
                    {
                        data: 'cantidad',
                        type: 'numeric',
                        numericFormat: {
                            pattern: '0.000'
                        },
                        title: 'CANTIDAD',
                    },
                    // Columna DESTINO dinámica: campo o maquinaria
                    esCombustible ?
                    autocompleteCol(destinoLabels, destinoMap, destinoRevMap, 'maquinaria_id',
                        'MAQUINARIA') :
                    autocompleteCol(destinoLabels, destinoMap, destinoRevMap, 'campo_nombre', 'CAMPO'),
                    {
                        data: 'categoria',
                        type: 'text',
                        readOnly: true,
                        title: 'CATEGORIA',
                        className: '!bg-muted',
                    },
                    {
                        data: 'costo_por_kg',
                        type: 'numeric',
                        title: 'COSTO X UND',
                        readOnly: true,
                        className: '!bg-muted',
                    },
                    {
                        data: 'total_costo',
                        type: 'numeric',
                        readOnly: true,
                        title: 'TOTAL COSTO',
                        className: '!bg-muted',
                    },
                ];
            },
            guardarSalidaAlmacen() {
                if (this.filasModificadas.length === 0) {
                    alert('Niguna fila modificada');
                    return;
                };

                const data = [...this.filasModificadas]
                    .map(i => this.hot.getSourceDataAtRow(i))
                    .filter(fila => fila && Object.values(fila).some(v => v !== null && v !== ''));

                $wire.guardarSalidaAlmacen(data);
            },
        }))
    </script>
@endscript
